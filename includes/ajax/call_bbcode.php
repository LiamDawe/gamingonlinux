<?php
session_start();

$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_plugins.php');
$plugins = new plugins($dbl, $core, $file_dir);

include($file_dir . '/includes/bbcode.php');
$bbcode = new bbcode($dbl, $core, $plugins);

include($file_dir . '/includes/class_article.php');
$article_class = new article_class($dbl, $bbcode);

if(isset($_POST))
{
    $text = $_POST['text'];
    $text = htmlspecialchars($text, ENT_QUOTES);
    $text = $bbcode->parse_bbcode($text);

    echo $text;
}
