<?php

namespace App\Providers;

use App\Models\User;
use App\Services\QueueStatus;
use Carbon\CarbonImmutable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureQueueObservability();

        Gate::define('admin', fn (User $user) => $user->isAdmin());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Register queue lifecycle listeners without recording serialized job
     * payloads. The payload can contain credentials or device data, while the
     * job class, queue, and identifier are sufficient for operations work.
     */
    protected function configureQueueObservability(): void
    {
        Queue::before(function (JobProcessing $event): void {
            $context = $this->queueContext($event);

            app(QueueStatus::class)->markProcessing($context);
            Log::info('queue.job_processing', $context);
        });

        Queue::after(function (JobProcessed $event): void {
            $context = $this->queueContext($event);

            app(QueueStatus::class)->markProcessed($context);
            Log::info('queue.job_processed', $context);
        });

        Queue::failing(function (JobFailed $event): void {
            $context = $this->queueContext($event);
            $context['exception_class'] = get_class($event->exception);
            $context['exception_message'] = $event->exception->getMessage();

            app(QueueStatus::class)->markFailed($context);
            Log::critical('queue.job_failed', $context);
        });
    }

    /**
     * @param  object{connectionName:string,job:object}  $event
     * @return array<string, mixed>
     */
    private function queueContext(object $event): array
    {
        $job = $event->job;

        try {
            $name = method_exists($job, 'resolveName')
                ? $job->resolveName()
                : get_class($job);
        } catch (\Throwable) {
            $name = 'unknown';
        }

        return [
            'connection' => (string) $event->connectionName,
            'queue' => (string) $job->getQueue(),
            'job' => class_basename((string) $name),
            'job_id' => method_exists($job, 'getJobId') ? $job->getJobId() : null,
        ];
    }
}
