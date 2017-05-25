<?php
class plugins
{	
	// the database connection
	public $database = null;
	// the main core class with all the helpers
	public $core = null;
	public static $hooks = [];
	
	// load all plugin files
	function __construct(db_mysql $database, core $core)
	{
		$this->database = $database;
		$this->core = $core;
		
		$file_dir = dirname( dirname(__FILE__) );
		
		$get_plugins = $this->database->run("SELECT `name` FROM `plugins` WHERE `enabled` = 1 ORDER BY `name` ASC")->fetch_all();
		foreach ($get_plugins as $plugin)
		{
			include($file_dir . '/plugins/' . $plugin['name'] . '/plugin_' . $plugin['name'] . '.php');
		}
	}
	
	// plugin files call this, to register their function needs calling
	public static function register_hook($name, $function_name = NULL)
	{
		self::$hooks[$name]['function_name'] = $function_name;
	}
	
	// this is called by the main php files, which then looks for registers from register_hook
	public function do_hooks($name, $value = NULL)
	{
		$return_value = '';
		if (isset(self::$hooks[$name]))
		{
			foreach (self::$hooks[$name] as $hook)
			{
				$return_value = call_user_func('hook_'.$hook, $this->database, $this->core, $value);
			}
		}
		return $return_value;
	}
}
