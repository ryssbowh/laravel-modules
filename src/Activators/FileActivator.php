<?php

namespace Nwidart\Modules\Activators;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

class FileActivator implements ActivatorInterface
{
	/**
	 * Laravel cache instance
	 * 
	 * @var CacheManager
	 */
	protected $cache;

	/**
	 * Laravel Filesystem instance
	 * 
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Laravel config instance
	 * 
	 * @var Config
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $cacheKey;

	/**
	 * @var string
	 */
	protected $cacheLifetime;

	/**
	 * Array of modules activation statuses
	 * 
	 * @var array
	 */
	protected $modulesStatuses;

	/**
	 * File used to store activation statuses
	 * 
	 * @var string
	 */
	protected $statusesFile;

	public function __construct(Container $app)
	{
		$this->cache = $app['cache'];
		$this->files = $app['files'];
		$this->config = $app['config'];
		$this->statusesFile = $this->config('statuses-file');
		$this->cacheKey = $this->config('cache-key');
		$this->cacheLifetime = $this->config('cache-lifetime');
		$this->modulesStatuses = $this->getModulesStatuses();
	}

	/**
	 * Get modules statuses, either from the cache or 
	 * from the json statuses file if the cache is disabled.
	 * 
	 * @return array
	 */
	private function getModulesStatuses()
	{
		if(!$this->config->get('modules.cache.enabled')) return $this->readJson();
		
		return $this->cache->remember($this->cacheKey, $this->cacheLifetime, function () {
            return $this->readJson();
        });
	}

	/**
	 * Flushes the modules activation statuses cache
	 */
	private function flushCache()
	{
		$this->cache->forget($this->cacheKey);
	}

	/**
	 * Reads a config parameter
	 * 
	 * @param  string $key     [description]
	 * @param  $default
	 * @return mixed
	 */
	private function config(string $key, $default = null)
    {
        return $this->config->get('modules.activators.file.' . $key, $default);
    }

	/**
	 * Reads the json file that contains the activation statuses.
	 * 
	 * @return array
	 */
	private function readJson()
	{
		if(!$this->files->exists($this->statusesFile)) return [];
		return json_decode($this->files->get($this->statusesFile), true);
	}

	/**
	 * Writes the activation statuses in a file, as json
	 */
	private function writeJson()
	{
		$this->files->put($this->statusesFile, json_encode($this->modulesStatuses, JSON_PRETTY_PRINT));
	}

	/**
	 * Get the path of the file where statuses are stored
	 * 
	 * @return string
	 */
	public function getStatusesFilePath()
	{
		return $this->statusesFile;
	}

	/**
	 * @inheritDoc
	 */
	public function reset()
	{
		if($this->files->exists($this->statusesFile)){
			$this->files->delete($this->statusesFile);
		}
		$this->modulesStatuses = [];
		$this->flushCache();
	}

	/**
     * @inheritDoc
     */
    public function enable(Module $module)
    {
    	$this->setActiveByName($module->getName(), true);
    }

    /**
     * @inheritDoc
     */
    public function disable(Module $module)
    {
		$this->setActiveByName($module->getName(), false);
    }

    /**
     * @inheritDoc
     */
    public function isStatus(Module $module, bool $status): bool
    {
    	if(!isset($this->modulesStatuses[$module->getName()])){
    		return $status === false;
    	}
    	return $this->modulesStatuses[$module->getName()] === $status;
    }

    /**
     * @inheritDoc
     */
    public function setActive(Module $module, bool $active)
    {
    	$this->setActiveByName($module->getName(), $active);
    }

    /**
     * @inheritDoc
     */
    public function setActiveByName(string $name, bool $status)
    {
    	$this->modulesStatuses[$name] = $status;
    	$this->writeJson();
    	$this->flushCache();
    }

    /**
     * @inheritDoc
     */
    public function delete(Module $module)
    {
    	if(!isset($this->modulesStatuses[$module->getName()])) return;
    	unset($this->modulesStatuses[$module->getName()]);
    	$this->writeJson();
    	$this->flushCache();
    }
}
