<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMqttMessage;
use App\Services\MqttStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Contracts\MqttClient as MqttClientContract;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to the MeshBeacon MQTT event stream.';

    /**
     * Execute the console command.
     */
    public function handle(MqttStatus $status): int
    {
        $host = config('mqtt-client.connections.default.host');
        $port = config('mqtt-client.connections.default.port');
        $status->markStarting();

        Log::info('mqtt.worker_starting', [
            'host' => $host,
            'port' => $port,
            'topics' => ['hub/event', 'hub/tak/log'],
        ]);

        try {
            $mqtt = MQTT::connection();

            // Refresh the heartbeat only while the client itself reports an
            // active connection, and only when real message traffic hasn't
            // already kept last_heartbeat_at fresh. This keeps the idle
            // keepalive heartbeat from competing with markMessage() for the
            // shared MqttStatus persist-throttle window during busy periods,
            // while still preventing a dead/reconnecting broker link from
            // being masked as healthy.
            $lastCheckedAt = 0.0;
            $mqtt->registerLoopEventHandler(function (MqttClientContract $mqtt) use ($status, &$lastCheckedAt): void {
                if (! $mqtt->isConnected()) {
                    return;
                }

                $now = microtime(true);

                if ($now - $lastCheckedAt < 5) {
                    return;
                }

                $lastCheckedAt = $now;

                if ($status->needsWorkerHeartbeat()) {
                    $status->markWorkerHeartbeat();
                }
            });

            $mqtt->subscribe('hub/event', function (string $topic, string $message) use ($status): void {
                $status->markMessage();

                $data = json_decode($message, true);
                if (($data['eventType'] ?? null) === 'unknown') {
                    Log::debug('mqtt.message_ignored', [
                        'topic' => $topic,
                        'reason' => 'eventType_unknown',
                    ]);

                    return;
                }

                ProcessMqttMessage::dispatch($message);

                Log::debug('mqtt.message_received', [
                    'topic' => $topic,
                    'payload_bytes' => strlen($message),
                ]);
            }, 0);

            $mqtt->subscribe('hub/tak/log', function (string $topic, string $message) use ($status): void {
                $status->markMessage();
                \App\Jobs\ProcessTakLog::dispatch($message);

                Log::debug('mqtt.message_received', [
                    'topic' => $topic,
                    'payload_bytes' => strlen($message),
                ]);
            }, 0);

            $status->markConnected();
            Log::info('mqtt.connected', [
                'host' => $host,
                'port' => $port,
                'topics' => ['hub/event', 'hub/tak/log'],
            ]);

            $mqtt->loop(true);
            $status->markDisconnected('subscriber_loop_exited');
            Log::warning('mqtt.disconnected', ['reason' => 'subscriber_loop_exited']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $status->markError($exception);
            Log::error('mqtt.worker_error', [
                'host' => $host,
                'port' => $port,
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
