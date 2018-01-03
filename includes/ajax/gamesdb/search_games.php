<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_GET['q']))
{
	$game_search = '%' . $_GET['q'] . '%';

	if (isset($_GET['type']) && $_GET['type'] == 'sales')
	{
		$sql = "SELECT DISTINCT c.`name` FROM `sales` s INNER JOIN `calendar` c ON c.id = s.game_id WHERE c.`name` LIKE ? ORDER BY c.`name` ASC";
	}
	if (isset($_GET['type']) && $_GET['type'] == 'free')
	{
		$sql = "SELECT `name` FROM `calendar` WHERE `name` LIKE ? AND `free_game` = 1 AND `is_application` = 0 AND `is_emulator` = 0 ORDER BY `name` ASC";		
	}
	if (isset($_GET['type']) && $_GET['type'] == 'all')
	{
		$sql = "SELECT `name` FROM `calendar` WHERE `name` LIKE ? AND `also_known_as` IS NULL ORDER BY `name` ASC";	
	}

	$get_data = $dbl->run($sql, [$game_search])->fetch_all();

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
