<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_GET['q']))
{
	$get_data = $dbl->run("SELECT `module_id`, `nice_title` FROM `modules` WHERE `nice_title` LIKE ? ORDER BY `nice_title` ASC", array('%' . $_GET['q'] . '%'))->fetch_all();
	// Make sure we have a result
	if($get_data)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('id' => $value['module_id'], 'text' => $value['nice_title']);
		}
	}
	else
	{
		$data[] = array('id' => '0', 'text' => 'No modules found that match!');
	}
	echo json_encode($data);
}
?>
