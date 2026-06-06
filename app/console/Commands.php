<?php 

namespace App\Console;

class Commands
{
    /**
     * Register commands
     * 
     * @param $console
     * @return void
     * 
     */
    public static function register(mixed $console): void
    {
        $console->register([
            ExampleCommand::class,
            OptimizeCollection::class
        ]);
    }
}
