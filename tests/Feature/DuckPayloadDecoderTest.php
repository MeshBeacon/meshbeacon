<?php

namespace Tests\Feature;

use App\Services\DuckPayloadDecoder;
use Tests\TestCase;

/**
 * Covers the 'dcmd' (OpText) protobuf mapping added to DuckPayloadDecoder:
 * before this, an encrypted MSG_READ/OpText ack (see meshbeacon-firmware's
 * buildOpText(), examples/Basic-Ducks/common/PayloadBuilders.h) decrypted
 * fine in ProcessMqttMessage but decode() had no 'dcmd' case, so it fell
 * back to storing the raw protobuf bytes base64-encoded -- displayed to
 * the operator as cryptic ciphertext-looking text instead of the actual
 * "MSG_READ:TEXT:..." read receipt.
 */
class DuckPayloadDecoderTest extends TestCase
{
    /** Hand-encode a minimal OpText{ text = "..." } protobuf message (field 1, length-delimited). */
    private function encodeOpText(string $text): string
    {
        $tag = chr((1 << 3) | 2); // field 1, wire type 2 (length-delimited)
        $len = $this->encodeVarint(strlen($text));

        return "\x01".$tag.$len.$text; // 0x01 = protobuf format marker
    }

    private function encodeVarint(int $value): string
    {
        $bytes = '';
        do {
            $byte = $value & 0x7F;
            $value >>= 7;
            $bytes .= chr($value > 0 ? ($byte | 0x80) : $byte);
        } while ($value > 0);

        return $bytes;
    }

    public function test_decodes_op_text_on_dcmd_topic_into_its_verbatim_text(): void
    {
        $decoder = new DuckPayloadDecoder();
        $body = $this->encodeOpText('MSG_READ:TEXT:help');

        $this->assertSame('MSG_READ:TEXT:help', $decoder->decode('dcmd', $body));
    }

    public function test_returns_null_for_topics_without_a_known_mapping(): void
    {
        $decoder = new DuckPayloadDecoder();
        $body = $this->encodeOpText('ALERT_ACK');

        $this->assertNull($decoder->decode('cpm', $body));
    }
}
