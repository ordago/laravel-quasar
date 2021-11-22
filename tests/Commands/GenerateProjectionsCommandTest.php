<?php

namespace Laravelcargo\LaravelCargo\Tests\Commands;

use Illuminate\Support\Facades\Artisan;
use Laravelcargo\LaravelCargo\Models\Projection;
use Laravelcargo\LaravelCargo\Tests\Models\Log;
use Laravelcargo\LaravelCargo\Tests\TestCase;

class GenerateProjectionsCommandTest extends TestCase
{
    /** @test */
    public function it_regenerates_the_projections()
    {
        $log = Log::factory()->create();
        $this->assertEquals(1, Projection::count());
        Projection::first()->delete();
        $this->assertEquals(0, Projection::count());
        $this->assertNull($log->firstProjection());

        Artisan::call('projections:generate');

        $this->assertEquals(1, Projection::count());
        $this->assertNotNull($log->firstProjection());
    }
}
