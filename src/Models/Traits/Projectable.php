<?php

namespace TimothePearce\Quasar\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use TimothePearce\Quasar\Jobs\ProjectProjectable;
use TimothePearce\Quasar\Models\Projection;
use TimothePearce\Quasar\Projector;

trait Projectable
{
    /**
     * Boots the trait.
     */
    public static function bootProjectable(): void
    {
        static::created(fn(Model $model) => $model->bootProjectors('created'));
        static::updating(fn(Model $model) => $model->bootProjectors('updating'));
        static::updated(fn(Model $model) => $model->bootProjectors('updated'));
        static::deleting(fn(Model $model) => $model->bootProjectors('deleting'));
        static::deleted(fn(Model $model) => $model->bootProjectors('deleted'));
    }

    /**
     * Boots the projectors.
     */
    public function bootProjectors(string $eventName): void
    {
        collect($this->projections)->each(
            fn(string $projection) => collect((new $projection)->periods)->each(
                function (string $period) use ($eventName, $projection) {
                    config('quasar.queue') ?
                        ProjectProjectable::dispatch($this, $projection, $period, $eventName) :
                        (new Projector($this, $projection, $period, $eventName))->handle();
                }
            )
        );
    }

    /**
     * Gets all the projections of the model.
     */
    public function projections(
        string|null       $projectionName = null,
        string|array|null $periods = null,
    ): MorphToMany
    {
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
    ): null|Projection
    {
        return $this->projections($projectionName, $periods)->first();
    }

    /**
     * Sets the projectors.
     */
    public function setProjections(array $projections)
    {
        $this->projections = $projections;
    }

    /**
     * Gets the projection start_date attribute.
     */
    public function guessProjectionStartDate(string $period): string
    {
        [$quantity, $periodType] = Str::of($period)->split('/[\s]+/');

        return isset($this->{$this->getCreatedAtColumn()}) ?
            $this->{$this->getCreatedAtColumn()}->floorUnit($periodType, $quantity) :
            Carbon::today()->floorUnit($periodType, $quantity);
    }
}
