<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

if(isset($_POST))
{
    include($file_dir . '/includes/bbcode.php');
    $text = $_POST['text'];
    $text = htmlspecialchars($text, ENT_QUOTES);
    $text = bbcode($text);

    echo $text;
}
