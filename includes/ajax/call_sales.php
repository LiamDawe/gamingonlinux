<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$cat_array = array();

if(isset($_GET['q']))
{
	$game_search = '%' . $_GET['q'] . '%';
	$get_data = $dbl->run("SELECT DISTINCT c.`name` FROM `sales` s INNER JOIN `calendar` c ON c.id = s.game_id WHERE c.`name` LIKE ? ORDER BY c.`name` ASC", [$game_search])->fetch_all();

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
