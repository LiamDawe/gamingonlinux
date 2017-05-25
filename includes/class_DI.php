<?php

/**
* Wrapper class for dependancy injection
* This class wraps the PHP-DI container class to make it staticly available
**/
class DI {

	use snippet\Singleton;

	protected $container;

	// getInstance does not support arguments
	public function setContainer($container)
	{
		$this->container = $container;
	}

	/**
	* Setup the DI static class
	*
	*
	**/
	public static function setup($container)
	{
		$c = self::getInstance();
		$c->setContainer($container);
	}

	public function __call($command, $args)
	{
		return $this->container->$command(...$args);
	}

	public static function __callStatic($command, $args)
	{
		$c = static::getInstance();
		return $c->$command(...$args);
	}

}