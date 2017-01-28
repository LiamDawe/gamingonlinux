<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

if(isset($_POST))
{
		$title = $_POST['title'];

		echo $core->nice_title($title);
}
?>
