<?php

namespace App\Console;

use Aloe\Command;
use App\Services\OptimizeService;

class OptimizeCollection extends Command
{
    protected static $defaultName = 'optimize:collection';
    public $description = 'Optimize collection command\'s description';
    public $help = 'Optimize collection command\'s help';
    protected function config()
    {
        $this
            ->setArgument('argument', 'optional', 'argument description')
            ->setOption('option', 'o', 'required', 'option description');
    }

    // Process all unoptimized collections
    // $service->optimizeAll();

    // Process a single collection
    // $service->optimizeOne(collectionId: 42);

    // Re-process everything, even already-optimized
    // $service->forceOptimizeAll();

    // Dry run on any of the above (no writes, returns same stats)
    // $service->optimizeAll(dryRun: true);

    protected function handle()
    {
        
        $service = new OptimizeService();
        $service->optimizeAll(dryRun: true);

        if ($service->hasErrors()) {
            foreach ($service->getErrors() as $error) {
                echo "Collection #{$error['collection_id']} — {$error['file']}: {$error['reason']}\n";
            }
        }
    }
}
