<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password']);

$cat_array = array();

if(isset($_GET['q']))
{
	$username_search = '%' . $_GET['q'] . '%';
	$get_data = $dbl->run("SELECT `user_id`, `username` FROM `users` WHERE `username` LIKE ? ORDER BY `username` ASC", [$username_search])->fetch_all();

	// Make sure we have a result
	if(count($get_data) > 0)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('id' => $value['user_id'], 'text' => $value['username']);
		}
	}
	else
	{
		$data[] = array('id' => '0', 'text' => 'No users found that match!');
	}
	echo json_encode($data);
}
?>
