<?php

namespace TimothePearce\Quasar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use TimothePearce\Quasar\Models\Projection;

class Projector
{
    public function __construct(
        protected Model  $projectedModel,
        protected string $projectionName,
        protected string $period,
        protected string $eventName
    ) {
    }

    /**
     * Handles the projection.
     */
    public function handle(): void
    {
        if (! $this->hasCallableMethod()) {
            return;
        }

        if ($this->isGlobalPeriod()) {
            $this->createOrUpdateGlobalPeriod();

            return;
        }

        $this->parsePeriod();
    }

    /**
     * Is the given period a global one or not.
     */
    private function isGlobalPeriod(): bool
    {
        return $this->period === '*';
    }

    /**
     * Handles the global period case.
     */
    private function createOrUpdateGlobalPeriod(): void
    {
        $projection = $this->findGlobalProjection();

        is_null($projection) ?
            $this->createGlobalProjection() :
            $this->updateProjection($projection);
    }

    /**
     * Parses the given period.
     */
    private function parsePeriod(): void
    {
        [$quantity, $periodType] = Str::of($this->period)->split('/[\s]+/');

        $projection = $this->findProjection((int)$quantity, $periodType);

        is_null($projection) ?
            $this->createProjection((int)$quantity, $periodType) :
            $this->updateProjection($projection);
    }

    /**
     * Finds the global projection if it exists.
     */
    private function findGlobalProjection(): Projection|null
    {
        return Projection::firstWhere([
            ['projection_name', $this->projectionName],
            ['key', $this->hasKey() ? $this->key() : null],
            ['period', '*'],
            ['start_date', null],
        ]);
    }

    /**
     * Finds the projection if it exists.
     */
    private function findProjection(int $quantity, string $periodType): Projection|null
    {
        return Projection::firstWhere([
            ['projection_name', $this->projectionName],
            ['key', $this->hasKey() ? $this->key() : null],
            ['period', $this->period],
            ['start_date', $this->projectedModel->created_at->floorUnit($periodType, $quantity)],
        ]);
    }

    /**
     * Creates the projection.
     */
    private function createProjection(int $quantity, string $periodType): void
    {
        $this->projectedModel->projections()->create([
            'projection_name' => $this->projectionName,
            'key' => $this->hasKey() ? $this->key() : null,
            'period' => $this->period,
            'start_date' => $this->projectedModel->created_at->floorUnit($periodType, $quantity),
            'content' => $this->mergeProjectedContent((new $this->projectionName())->defaultContent()),
        ]);
    }

    /**
     * Creates the global projection.
     */
    private function createGlobalProjection()
    {
        $this->projectedModel->projections()->create([
            'projection_name' => $this->projectionName,
            'key' => $this->hasKey() ? $this->key() : null,
            'period' => '*',
            'start_date' => null,
            'content' => $this->mergeProjectedContent((new $this->projectionName())->defaultContent()),
        ]);
    }

    /**
     * Updates the projection.
     */
    private function updateProjection(Projection $projection): void
    {
        $projection->content = $this->mergeProjectedContent($projection->content);

        $projection->save();
    }

    /**
     * Determines whether the class has a key.
     */
    private function hasKey(): bool
    {
        return method_exists($this->projectionName, 'key');
    }

    /**
     * The key used to query the projection.
     */
    public function key(): bool|int|string
    {
        return (new $this->projectionName())->key($this->projectedModel);
    }

    /**
     * Merges the projected content with the given one.
     */
    private function mergeProjectedContent(array $content): array
    {
        return array_merge($content, $this->resolveCallableMethod($content));
    }

    /**
     * Asserts the projection has a callable method for the event name.
     */
    private function hasCallableMethod(): bool
    {
        $modelName = Str::of($this->projectedModel::class)->explode('\\')->last();
        $callableMethod = lcfirst($modelName) . ucfirst($this->eventName);
        $defaultCallable = 'projectable' . ucfirst($this->eventName);

        return method_exists($this->projectionName, $callableMethod) || method_exists($this->projectionName, $defaultCallable);
    }

    /**
     * Resolves the callable method.
     */
    private function resolveCallableMethod(array $content): array
    {
        $modelName = Str::of($this->projectedModel::class)->explode('\\')->last();
        $callableMethod = lcfirst($modelName) . ucfirst($this->eventName);
        $defaultCallable = 'projectable' . ucfirst($this->eventName);

        return method_exists($this->projectionName, $callableMethod) ?
            (new $this->projectionName())->$callableMethod($content, $this->projectedModel) :
            (new $this->projectionName())->$defaultCallable($content, $this->projectedModel);
    }
}
