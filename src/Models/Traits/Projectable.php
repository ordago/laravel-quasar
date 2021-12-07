<?php

namespace TimothePearce\Quasar\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use TimothePearce\Quasar\Jobs\ProcessProjection;
use TimothePearce\Quasar\Models\Projection;
use TimothePearce\Quasar\Projector;

trait Projectable
{
    /**
     * Boots the trait.
     */
    public static function bootProjectable(): void
    {
        static::created(fn (Model $model) => $model->projectModel('created'));
        static::updating(fn (Model $model) => $model->projectModel('updating'));
        static::updated(fn (Model $model) => $model->projectModel('updated'));
        static::deleting(fn (Model $model) => $model->projectModel('deleting'));
        static::deleted(fn (Model $model) => $model->projectModel('deleted'));
    }

    /**
     * Projects the model.
     */
    public function projectModel(string $eventName): void
    {
        config('quasar.queue') ?
            ProcessProjection::dispatch($this, $eventName) :
            $this->bootProjectors($eventName);
    }

    /**
     * Boots the projectors.
     */
    public function bootProjectors(string $eventName): void
    {
        collect($this->projections)->each(
            fn (string $projection) => collect((new $projection())->periods)->each(
                fn (string $period) => (new Projector($this, $projection, $period, $eventName))->handle()
            )
        );
    }

    /**
     * Gets all the projections of the model.
     */
    public function projections(
        string|null       $projectionName = null,
        string|array|null $periods = null,
    ): MorphToMany {
        $query = $this->morphToMany(Projection::class, 'projectable', 'quasar_projectables');

        if (isset($projectionName)) {
            $query->where('projection_name', $projectionName);
        }

        if (isset($periods) && gettype($periods) === 'string') {
            $query->where('period', $periods);
        } elseif (isset($periods) && gettype($periods) === 'array') {
            $query->where(function ($query) use (&$periods) {
                collect($periods)->each(function (string $period, $key) use (&$query) {
                    $key === 0 ?
                        $query->where('period', $period) :
                        $query->orWhere('period', $period);
                });
            });
        }

        return $query;
    }

    /**
     * Gets the first projection.
     */
    public function firstProjection(
        string|null       $projectionName = null,
        string|array|null $periods = null,
    ): null|Projection {
        return $this->projections($projectionName, $periods)->first();
    }

    /**
     * Sets the projectors.
     */
    public function setProjections(array $projections)
    {
        $this->projections = $projections;
    }
}
