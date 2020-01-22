<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_GET['q']) && isset($_GET['return_type']))
{
	$game_search = '%' . trim($_GET['q']) . '%';

	if ((isset($_GET['type']) && $_GET['type'] == 'all') || !isset($_GET['type']))
	{
		$sql = "SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ? AND `id` NOT IN (SELECT `dupe_id` FROM `item_dupes`) ORDER BY `name` ASC ";	
	}
	if (isset($_GET['type']) && $_GET['type'] == 'sales')
	{
		$sql = "SELECT DISTINCT c.`name` FROM `sales` s INNER JOIN `calendar` c ON c.id = s.game_id WHERE c.`name` LIKE ? AND c.`id` NOT IN (SELECT `dupe_id` FROM `item_dupes`) ORDER BY c.`name` ASC";
	}
	if (isset($_GET['type']) && $_GET['type'] == 'free')
	{
		$sql = "SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ? AND `free_game` = 1 AND `is_application` = 0 AND `is_emulator` = 0 AND `id` NOT IN (SELECT `dupe_id` FROM `item_dupes`) ORDER BY `name` ASC";		
	}
	if (isset($_GET['type']) && $_GET['type'] == 'games_only')
	{
		$sql = "SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ? AND `id` NOT IN (SELECT `dupe_id` FROM `item_dupes`) AND `is_game` = 1 ORDER BY `name` ASC ";	
	}
	if (isset($_GET['type']) && $_GET['type'] == 'all_nodlc')
	{
		$sql = "SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ? AND `is_dlc` = 0 AND `id` NOT IN (SELECT `dupe_id` FROM `item_dupes`) ORDER BY `name` ASC ";	
	}

	$get_data = $dbl->run($sql, [$game_search])->fetch_all();

	$data = [];
	if($get_data)
	{
		foreach ($get_data as $key => $value)
		{
			if ($_GET['return_type'] == 'text') // easy autocomplete
			{
				$data[] = array('data' => $value['name']);
			}
			else if ($_GET['return_type'] == 'option') // select2
			{
				$data[] = array('id' => $value['id'], 'text' => $value['name']);
			}
		}
	}
	else
	{
		if ($_GET['return_type'] == 'option')
		{
			$data[] = array('text' => 'No games found that match!');
		}
	}

	echo json_encode($data);
}
$dbl = NULL;
?>
