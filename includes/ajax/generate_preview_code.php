<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

if(isset($_POST))
{
    $new_code = core::random_id();
    $db->sqlquery("UPDATE `articles` SET `preview_code` = ? WHERE `article_id` = ?", array($new_code, $_POST['article_id']));
    echo core::config('website_url') . 'index.php?module=articles_full&aid=' . $_POST['article_id'] . '&preview_code=' . $new_code;
}
