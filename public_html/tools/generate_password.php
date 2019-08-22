<?php
$file_dir = dirname( dirname(__FILE__) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

$password = 'password';

$safe_password = password_hash($password, PASSWORD_BCRYPT);
echo $safe_password;
?>
