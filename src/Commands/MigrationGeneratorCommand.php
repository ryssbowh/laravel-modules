<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nwidart\Modules\Generators\FileGenerator;
use Symfony\Component\Console\Input\InputArgument;

abstract class MigrationGeneratorCommand extends Command
{
    /**
     * The name of 'name' argument.
     *
     * @var string
     */
    protected $argumentName = '';

    /**
     * Get template contents.
     *
     * @return string
     */
    abstract protected function getTemplateContents();

    /**
     * Get the destination file path.
     *
     * @return string
     */
    abstract protected function getDestinationFilePath();

    /**
     * Prefix for the class name
     * @return string
     */
    abstract protected function getPrefix();

    /**
     * Classname being generated
     * 
     * @var string
     */
    private $classname;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = str_replace('\\', '/', $this->getDestinationFilePath());

        if (!$this->laravel['files']->isDirectory($dir = dirname($path))) {
            $this->laravel['files']->makeDirectory($dir, 0777, true);
        }

        $contents = $this->getTemplateContents();

        (new FileGenerator($path, $contents))->generate();

        $this->info("Created : {$path}");
    }

    /**
     * Get file name.
     *
     * @return string
     */
    protected function getFileName()
    {
        return $this->getClass().'.php';
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass()
    {
        if(!$this->classname){
            $this->classname = $this->getPrefix().(new \DateTime)->format('Y_m_d_Hisu').'_'.Str::studly($this->argument('name'));
        }
        return $this->classname;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of class that will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module that will be used.'],
        ];
    }
}
