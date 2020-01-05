<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$cat_array = array();

if(isset($_GET['q']))
{
	$username_search = '%' . $_GET['q'] . '%';
	$get_data = $dbl->run("SELECT `user_id`, `username` FROM `users` WHERE `username` LIKE ? ORDER BY `username` ASC", [$username_search])->fetch_all();

	// Make sure we have a result
	if($get_data)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('id' => $value['user_id'], 'text' => $value['username']);
		}
	}
	else
	{
		$data[] = array('text' => 'No users found that match!');
	}
	echo json_encode($data);
}
?>
