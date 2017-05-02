<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'],$db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

if(isset($_POST))
{
	$new_code = core::random_id();
    $dbl->run("UPDATE `articles` SET `preview_code` = ? WHERE `article_id` = ?", array($new_code, $_POST['article_id']));
    echo $core->config('website_url') . 'index.php?module=articles_full&aid=' . $_POST['article_id'] . '&preview_code=' . $new_code;
}
