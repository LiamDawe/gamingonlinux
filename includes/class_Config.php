<?php

/**
* Config
*/
class Config
{
	use snippet\Signleton;


	function __construct($configFile="includes/config.php")
	{
		$this->load($configFile);
	}

	public function load($configFile)
	{
		if (!file_exists( APP_ROOT . $configFile)){
			return false;
		}
		$data = include APP_ROOT . $configFile;
		if (!is_array($data)) {
			$data = [ $data ];
		}
		$this->data = array_merge($data, $this->data);
		return $this;
	}
}