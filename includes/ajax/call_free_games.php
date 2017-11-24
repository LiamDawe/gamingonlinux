<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_GET['q']))
{
	$game_search = '%' . $_GET['q'] . '%';
	$get_data = $dbl->run("SELECT `name` FROM `calendar` WHERE `name` LIKE ? AND `free_game` = 1 ORDER BY `name` ASC", [$game_search])->fetch_all();

	$data = [];
	if($get_data)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('data' => $value['name']);
		}
	}

	echo json_encode($data);
}
?>
