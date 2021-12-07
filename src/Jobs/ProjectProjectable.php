<?php

namespace TimothePearce\Quasar\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use TimothePearce\Quasar\Projector;

class ProjectProjectable implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Creates a new job instance.
     */
    public function __construct(
        protected Model  $model,
        protected string $projection,
        protected string $period,
        protected string $eventName
    )
    {
        $this->onQueue(config('quasar.queue_name'));
    }

    /**
     * Gets the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        $key = "{$this->model->guessProjectionStartDate($this->period)}_{$this->period}_{$this->projection}";

        return [new WithoutOverlapping($key)];
    }

    /**
     * Executes the job.
     */
    public function handle()
    {
        (new Projector($this->model, $this->projection, $this->period, $this->eventName))->handle();
    }
}
