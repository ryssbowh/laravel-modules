<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class EnableAllCoreCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:enable-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable all core modules.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = $this->laravel['modules']->getCoreModules();

        foreach ($modules as $module) {
            $module->enable();
        }
        
        $this->comment("All core modules have been enabled.");
    }
}
