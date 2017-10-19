<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_POST))
{
		$title = $_POST['title'];

		echo core::nice_title($title);
}
?>
