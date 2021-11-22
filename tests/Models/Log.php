<?php

namespace Laravelcargo\LaravelCargo\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravelcargo\LaravelCargo\Tests\Projectors\SinglePeriodProjector;
use Laravelcargo\LaravelCargo\WithProjections;

class Log extends Model
{
    use HasFactory;
    use WithProjections;

    /**
     * The lists of the projectors.
     * @todo move this into the projector and renames it $models
     */
    protected array $projectors = [SinglePeriodProjector::class];

//    /**
//     * API wip.
//     */
//    public function relationWithCargoProjection()
//    {
//        $projections = $this->projections()->get(); // Get all the projections
//
//        $this->projectionsFromInterval('5 minutes'); // Get all the projections for the given interval
//        $this->lastProjectionFromInterval('1 hour'); // Get the latest projection for the given interval
//        $this->firstProjectionFromInterval('1 day'); // Get the first projection for the given interval
//        $this->projectionsByIntervals(); // Get all the projections ordered by intervals
//
//        $this->projections; // Give a super set of the default collection instance with useful methods
//    }
}
