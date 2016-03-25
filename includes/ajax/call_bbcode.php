<?php
include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

if(isset($_POST))
{
    include('../bbcode.php');
    $text = $_POST['text'];
    $text = htmlspecialchars($text, ENT_QUOTES);
    $text = bbcode($text);

    echo $text;
}
