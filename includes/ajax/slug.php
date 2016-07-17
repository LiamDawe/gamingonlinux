<?php
include('../class_core.php');
$core = new core();

if(isset($_POST))
{
		$title = $_POST['title'];

		echo $core->nice_title($title);
}
?>
