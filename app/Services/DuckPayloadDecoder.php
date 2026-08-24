<?php

namespace App\Services;

/**
 * Decodes duck_payloads.proto messages recovered from a decrypted
 * sealed_uplink/encrypted_data plaintext body (protobuf format marker
 * 0x01 -- see meshbeacon-firmware's src/payloads/DuckPayloads.h) and
 * reconstructs the exact same "legacy text" representation the gateway's
 * own duckpayload::*ToLegacyText() helpers produce for UNENCRYPTED
 * gps/alert/health/status traffic (see
 * meshbeacon-uplink/clusterduck/src/payloads/DuckPayloads.cpp), so an
 * encrypted report and a plaintext report of the same data end up with
 * byte-identical ClusterData.payload strings.
 *
 * Only needed for payloads that arrived ENCRYPTED: the gateway already
 * does this exact protobuf -> legacy-text conversion itself for plaintext
 * gps/alert/health/status topics before OpenDMS ever sees them (it can't
 * do the same for sealed_uplink/encrypted_data, since it has no private
 * key to decrypt those -- only OpenDMS does), so this class mirrors that
 * gateway-side step for the cases the gateway couldn't perform.
 *
 * Hand-rolled minimal protobuf wire-format parser rather than the full
 * google/protobuf runtime + protoc codegen: duck_payloads.proto has no
 * repeated fields, maps, or deeply nested messages, so a small tailored
 * decoder (varint/zigzag/length-delimited only) is enough and avoids a
 * new heavy dependency plus a generated-code build step that would need
 * to be kept in sync across three repos.
 */
class DuckPayloadDecoder
{
    private const WIRE_VARINT = 0;
    private const WIRE_FIXED64 = 1;
    private const WIRE_LENGTH_DELIMITED = 2;
    private const WIRE_FIXED32 = 5;

    // duckcdp.GpsSource
    private const GPS_SOURCE_NAMES = [0 => 'NONE', 1 => 'DEVICE', 2 => 'PHONE'];

    // duckcdp.GpsNoFixReason
    private const GPS_NO_FIX_REASON_NAMES = [0 => 'NONE', 1 => 'NO_SIGNAL', 2 => 'NO_RESPONSE'];

    // duckcdp.SosOrigin
    private const SOS_ORIGIN_NAMES = [0 => 'UNKNOWN', 1 => 'DEVICE', 2 => 'PHONE'];

    // duckcdp.StatusMsgSrc
    private const STATUS_MSG_SRC_DEVICE = 2;

    /**
     * @param  string  $topicName  recovered app-level topic name (e.g. 'gps', 'alert', 'health', 'status')
     * @param  string  $body  decrypted plaintext body AFTER stripping the
     *                        app-topic byte (see
     *                        ProcessMqttMessage::splitDecryptedTopic()) --
     *                        still starts with the protobuf format marker
     *                        (0x01) followed by the raw protobuf-encoded
     *                        message bytes.
     * @return string|null legacy-text representation, or null if this
     *                      topic has no known protobuf mapping or the
     *                      bytes fail to parse (caller should fall back
     *                      to storing the raw bytes rather than guess).
     */
    public function decode(string $topicName, string $body): ?string
    {
        if ($body === '' || ord($body[0]) !== 0x01) {
            return null;
        }

        if (!in_array($topicName, ['gps', 'alert', 'health', 'status'], true)) {
            return null;
        }

        try {
            $fields = $this->parseFields(substr($body, 1));
        } catch (\Throwable) {
            return null;
        }

        return match ($topicName) {
            'gps' => $this->gpsToLegacyText($fields),
            'alert' => $this->sosToLegacyText($fields),
            'health' => $this->healthToLegacyText($fields),
            'status' => $this->statusReportToLegacyText($fields),
        };
    }

    /**
     * Minimal protobuf wire-format parser: field number => wire type +
     * raw value. Non-repeated fields only (last occurrence wins, per
     * proto3 semantics) -- duck_payloads.proto has no repeated fields.
     *
     * @return array<int, array{wire: int, value: int|string}>
     */
    private function parseFields(string $bytes): array
    {
        $fields = [];
        $len = strlen($bytes);
        $pos = 0;

        while ($pos < $len) {
            [$tag, $pos] = $this->readVarint($bytes, $pos);
            $fieldNum = $tag >> 3;
            $wireType = $tag & 0x7;

            $value = match ($wireType) {
                self::WIRE_VARINT => $this->readVarintValue($bytes, $pos),
                self::WIRE_FIXED64 => $this->readFixed($bytes, $pos, 8),
                self::WIRE_LENGTH_DELIMITED => $this->readLengthDelimited($bytes, $pos),
                self::WIRE_FIXED32 => $this->readFixed($bytes, $pos, 4),
                default => throw new \RuntimeException("Unsupported protobuf wire type {$wireType}"),
            };

            [$value, $pos] = $value;
            $fields[$fieldNum] = ['wire' => $wireType, 'value' => $value];
        }

        return $fields;
    }

    /** @return array{0: int|string, 1: int} */
    private function readVarintValue(string $bytes, int $pos): array
    {
        return $this->readVarint($bytes, $pos);
    }

    /** @return array{0: string, 1: int} */
    private function readFixed(string $bytes, int $pos, int $size): array
    {
        if ($pos + $size > strlen($bytes)) {
            throw new \RuntimeException('Truncated fixed-width field');
        }

        return [substr($bytes, $pos, $size), $pos + $size];
    }

    /** @return array{0: string, 1: int} */
    private function readLengthDelimited(string $bytes, int $pos): array
    {
        [$length, $pos] = $this->readVarint($bytes, $pos);

        if ($length < 0 || $pos + $length > strlen($bytes)) {
            throw new \RuntimeException('Truncated length-delimited field');
        }

        return [substr($bytes, $pos, $length), $pos + $length];
    }

    /** @return array{0: int, 1: int} [decoded value, new position] */
    private function readVarint(string $bytes, int $pos): array
    {
        $result = 0;
        $shift = 0;

        while (true) {
            if (!isset($bytes[$pos])) {
                throw new \RuntimeException('Truncated varint');
            }

            $byte = ord($bytes[$pos]);
            $pos++;
            $result |= ($byte & 0x7F) << $shift;

            if (($byte & 0x80) === 0) {
                break;
            }

            $shift += 7;

            if ($shift > 63) {
                throw new \RuntimeException('Varint too long');
            }
        }

        return [$result, $pos];
    }

    private function getVarint(array $fields, int $num, int $default = 0): int
    {
        return isset($fields[$num]) && $fields[$num]['wire'] === self::WIRE_VARINT
            ? (int) $fields[$num]['value']
            : $default;
    }

    private function getBool(array $fields, int $num): bool
    {
        return $this->getVarint($fields, $num) !== 0;
    }

    /** proto3 zigzag-encoded sint32 (lat_e7/lng_e7/alt_m). */
    private function getZigzag32(array $fields, int $num): int
    {
        $n = $this->getVarint($fields, $num);

        return ($n >> 1) ^ -($n & 1);
    }

    /** proto3 plain (non-zigzag) int32 -- sign-extend from the low 32 bits. */
    private function getInt32(array $fields, int $num): int
    {
        $n = $this->getVarint($fields, $num) & 0xFFFFFFFF;

        return $n > 0x7FFFFFFF ? $n - 0x100000000 : $n;
    }

    private function getString(array $fields, int $num, string $default = ''): string
    {
        return isset($fields[$num]) && $fields[$num]['wire'] === self::WIRE_LENGTH_DELIMITED
            ? (string) $fields[$num]['value']
            : $default;
    }

    /** @return array<int, array{wire: int, value: int|string}>|null */
    private function getSubMessage(array $fields, int $num): ?array
    {
        if (!isset($fields[$num]) || $fields[$num]['wire'] !== self::WIRE_LENGTH_DELIMITED) {
            return null;
        }

        return $this->parseFields((string) $fields[$num]['value']);
    }

    /** Mirrors duckpayload::gpsToLegacyText() exactly, byte for byte. */
    private function gpsToLegacyText(array $f): string
    {
        $hasFix = $this->getBool($f, 1);
        $source = self::GPS_SOURCE_NAMES[$this->getVarint($f, 2)] ?? 'NONE';
        $battPct = $this->getVarint($f, 10);

        if (!$hasFix) {
            $reason = self::GPS_NO_FIX_REASON_NAMES[$this->getVarint($f, 3)] ?? 'NONE';

            return sprintf('GPS,FIX:0,SRC:%s,REASON:%s,BATT:%d', $source, $reason, $battPct);
        }

        $out = sprintf(
            'GPS,SRC:%s,LAT:%.7f,LNG:%.7f',
            $source,
            $this->getZigzag32($f, 4) / 1e7,
            $this->getZigzag32($f, 5) / 1e7
        );

        $altM = $this->getZigzag32($f, 6);
        if ($altM !== 0) {
            $out .= sprintf(',ALT:%d', $altM);
        }

        $spdDkmh = $this->getVarint($f, 7);
        if ($spdDkmh !== 0) {
            $out .= sprintf(',SPD:%.1f', $spdDkmh / 10.0);
        }

        $hdgDeg = $this->getVarint($f, 8);
        if ($hdgDeg !== 0) {
            $out .= sprintf(',HDG:%u', $hdgDeg);
        }

        $sats = $this->getVarint($f, 9);
        if ($sats !== 0) {
            $out .= sprintf(',SATS:%u', $sats);
        }

        return $out.sprintf(',BATT:%d', $battPct);
    }

    /** Mirrors duckpayload::sosToLegacyText() exactly, byte for byte. */
    private function sosToLegacyText(array $f): string
    {
        $origin = self::SOS_ORIGIN_NAMES[$this->getVarint($f, 1)] ?? 'UNKNOWN';
        $out = sprintf('SOS,SRC:%s', $origin);

        if ($this->getBool($f, 3)) {
            $out .= sprintf(
                ',LAT:%.7f,LNG:%.7f',
                $this->getZigzag32($f, 4) / 1e7,
                $this->getZigzag32($f, 5) / 1e7
            );

            $altM = $this->getZigzag32($f, 6);
            if ($altM !== 0) {
                $out .= sprintf(',ALT:%d', $altM);
            }

            $spdDkmh = $this->getVarint($f, 7);
            if ($spdDkmh !== 0) {
                $out .= sprintf(',SPD:%.1f', $spdDkmh / 10.0);
            }

            $hdgDeg = $this->getVarint($f, 8);
            if ($hdgDeg !== 0) {
                $out .= sprintf(',HDG:%u', $hdgDeg);
            }

            $sats = $this->getVarint($f, 11);
            if ($sats !== 0) {
                $out .= sprintf(',SATS:%u', $sats);
            }

            $out .= ','.'GPS:'.(self::GPS_SOURCE_NAMES[$this->getVarint($f, 2)] ?? 'NONE');
        }

        return $out.sprintf(',BATT:%d', $this->getVarint($f, 9));
    }

    /** Mirrors duckpayload::healthToLegacyText() exactly, byte for byte. */
    private function healthToLegacyText(array $f): string
    {
        return sprintf('C:%u|FM:%d', $this->getVarint($f, 1), $this->getInt32($f, 2));
    }

    /** Mirrors duckpayload::statusMsgToLegacyText() exactly, byte for byte. */
    private function statusMsgToLegacyText(array $f): string
    {
        $text = $this->getString($f, 6);

        if ($this->getVarint($f, 1) === self::STATUS_MSG_SRC_DEVICE) {
            return 'MSG,SRC:DEVICE,TEXT:'.$text;
        }

        $lat = '';
        $lng = '';

        if ($this->getBool($f, 3)) {
            $lat = sprintf('%.7f', $this->getZigzag32($f, 4) / 1e7);
            $lng = sprintf('%.7f', $this->getZigzag32($f, 5) / 1e7);
        }

        return 'MSG,URGENCY:'.$this->getString($f, 2).',LAT:'.$lat.',LNG:'.$lng.',TEXT:'.$text;
    }

    /** Mirrors duckpayload::statusReportToLegacyText() exactly, byte for byte. */
    private function statusReportToLegacyText(array $f): string
    {
        $sos = $this->getSubMessage($f, 1);
        if ($sos !== null) {
            return $this->sosToLegacyText($sos);
        }

        $msg = $this->getSubMessage($f, 2);
        if ($msg !== null) {
            return $this->statusMsgToLegacyText($msg);
        }

        return '';
    }
}
