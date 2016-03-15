<?php
include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

if(isset($_POST))
{
    $new_code = core::random_id();
    $db->sqlquery("UPDATE `articles` SET `preview_code` = ? WHERE `article_id` = ?", array($new_code, $_POST['article_id']));
    echo core::config('website_url') . 'index.php?module=articles_full&aid=' . $_POST['article_id'] . '&preview_code=' . $new_code;
}
