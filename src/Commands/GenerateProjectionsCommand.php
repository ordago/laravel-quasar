<?php

namespace Laravelcargo\LaravelCargo\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class GenerateProjectionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projections:generate
                            {model* : The name of each model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerates the projections.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ray($this->getModels());
        // Get the model provided by the
        // Get all the model with projectors
        // Delete all existing projections (Projection::all()->each->delete());
        // Regenerate all projections
    }

    /**
     * Get the models registered by the app.
     * @todo Use this function when the CLI provides no model to let the user choose it/them.
     *
     * @return Collection
     */
    private function getModels(): Collection
    {
        ray(app_path());
        $models = collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();

                return sprintf(
                    '\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );
            })
            ->filter(function ($class) {
                ray($class);
                $valid = false;

                if (class_exists($class)) {
                    $reflection = new \ReflectionClass($class);
                    $valid = $reflection->isSubclassOf(Model::class) &&
                        ! $reflection->isAbstract();
                }

                return $valid;
            });

        return $models->values();
    }
}
