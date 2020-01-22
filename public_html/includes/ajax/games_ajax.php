<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_GET['q']))
{
	if (!isset($_GET['type']) || isset($_GET['type']) && $_GET['type'] == 'all')
	{
		$get_data = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ? AND `also_known_as` IS NULL ORDER BY `name` ASC", array('%' . $_GET['q'] . '%'))->fetch_all();
	}
	else if (isset($_GET['type']) && $_GET['type'] == 'games_only')
	{
		$get_data = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ? AND `also_known_as` IS NULL AND `is_game` = 1 ORDER BY `name` ASC", array('%' . $_GET['q'] . '%'))->fetch_all();
	}

	// Make sure we have a result
	if(count($get_data) > 0)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('id' => $value['id'], 'text' => $value['name']);
		}
	}
	else
	{
		$data[] = array('text' => 'No games found that match!');
	}
	echo json_encode($data);
}
$dbl = NULL;
?>
