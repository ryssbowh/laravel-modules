<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class DisableAllCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:disable-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable all modules.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = $this->laravel['modules']->all();

        foreach ($modules as $module) {
            $module->disable();
        }
        
        $this->comment("All modules have been disabled.");
    }
}
