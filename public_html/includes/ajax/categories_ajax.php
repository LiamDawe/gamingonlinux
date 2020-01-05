<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$cat_array = array();

if(isset($_GET['q']))
{
	$get_data = $dbl->run("SELECT `category_id`, `category_name` FROM `articles_categorys` WHERE `category_name` LIKE ? ORDER BY `category_name` ASC", array('%' . $_GET['q'] . '%'))->fetch_all();
	// Make sure we have a result
	if($get_data)
	{
		foreach ($get_data as $key => $value)
		{
			$data[] = array('id' => $value['category_id'], 'text' => $value['category_name']);
		}
	}
	else
	{
		$data[] = array('text' => 'No categories found that match!');
	}
	echo json_encode($data);
}
?>
