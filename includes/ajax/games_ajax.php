<?php
include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

$cat_array = array();

if(isset($_GET['q']))
{
		$db->sqlquery("SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ? ORDER BY `name` ASC", array('%' . $_GET['q'] . '%'));
		$get_data = $db->fetch_all_rows();
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
			$data[] = array('id' => '0', 'text' => 'No games found that match!');
	  }
		echo json_encode($data);
}
?>
