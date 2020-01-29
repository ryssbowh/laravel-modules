<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Vkovic\LaravelCommando\Handlers\Database\AbstractDbHandler;

class ReinstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:reinstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop database schema and re-install all the core modules.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (env('APP_ENV') != 'local' and !$this->option('force')) {
            $this->error('This command is destructive and can\'t be used without the --force option on this environment');
            exit;
        }

        $this->dropAndCreateSchema();

        foreach (\Module::getCoreModules() as $module) {
            $this->migrate($module);
        }

        $this->info('Core modules reinstalled.');
    }

    /**
     * Drop the database schema and re-create it
     */
    public function dropAndCreateSchema()
    {
        $database = $this->option('database')
            ?: config('database.connections.' . config('database.default') . '.database');
        $handler = $this->laravel->make(AbstractDbHandler::class);
        if ($handler->databaseExists($database)) {
            $this->info('Database exists, dropping...');
            $handler->dropDatabase($database);
        }
        $this->info('Creating database '.$database);
        $handler->createDatabase($database);
    }

    /**
     * Run the migration from the specified module.
     *
     * @param Module $module
     */
    protected function migrate(Module $module)
    {
        $installPath = $module->getPath().'/Database/Migrations/Install';
        if (file_exists($installPath)) {
            $this->call('module:migrate', [
                'module' => $module->getName(),
                '--subpath' => 'Install',
                '--database' => $this->option('database'),
                '--force' => $this->option('force'),
            ]);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when not in local.']
        ];
    }
}
