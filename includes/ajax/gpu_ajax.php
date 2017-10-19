<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$cat_array = array();

if(isset($_GET['q']))
{
	$get_data = $dbl->run("SELECT `id`, `name` FROM `gpu_models` WHERE `name` LIKE ? ORDER BY `name` ASC", array('%' . $_GET['q'] . '%'))->fetch_all();
	// Make sure we have a result
	if($get_data)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('id' => $value['id'], 'text' => $value['name']);
		}
	}
	else
	{
		$data[] = array('id' => '0', 'text' => 'No GPUs found that match!');
	}
	echo json_encode($data);
}
?>
