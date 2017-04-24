<?php
class plugins
{	
	// the database connection
	public $database = null;
	public static $hooks = [];
	
	// load all plugin files
	function __construct($database, $file_dir)
	{
		$this->database = $database;
		
		$get_plugins = $this->database->select('plugins', '`name`')->order('`name` ASC');
		while ($plugin = $get_plugins->fetch())
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
	public static function do_hooks($name, $value = NULL)
	{
		$return_value = '';
		if (isset(self::$hooks[$name]))
		{
			foreach (self::$hooks[$name] as $hook)
			{
				$return_value = call_user_func('hook_'.$hook, $value);
			}
		}
		return $return_value;
	}
}
