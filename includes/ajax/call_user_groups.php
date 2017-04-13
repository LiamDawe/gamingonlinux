<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

$cat_array = array();

if(isset($_GET['q']))
{
	$db->sqlquery("SELECT `group_id`, `group_name` FROM `user_groups` WHERE `group_name` LIKE ? ORDER BY `group_name` ASC", array('%' . $_GET['q'] . '%'));
	$get_data = $db->fetch_all_rows();
	// Make sure we have a result
	if(count($get_data) > 0)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('id' => $value['group_id'], 'text' => $value['group_name']);
		}
	}
	else
	{
		$data[] = array('id' => '0', 'text' => 'No groups found that match!');
	}
	echo json_encode($data);
}
?>
