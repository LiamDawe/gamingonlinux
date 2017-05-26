<?php
define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$cat_array = array();

if(isset($_GET['q']))
{
	$get_data = $dbl->run("SELECT `group_id`, `group_name` FROM `user_groups` WHERE `group_name` LIKE ? ORDER BY `group_name` ASC", array('%' . $_GET['q'] . '%'))->fetch_all();
	// Make sure we have a result
	if($get_data)
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
