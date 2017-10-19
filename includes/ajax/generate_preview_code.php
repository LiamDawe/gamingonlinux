<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_POST))
{
	$new_code = core::random_id();
    $dbl->run("UPDATE `articles` SET `preview_code` = ? WHERE `article_id` = ?", array($new_code, $_POST['article_id']));
    echo $core->config('website_url') . 'index.php?module=articles_full&aid=' . $_POST['article_id'] . '&preview_code=' . $new_code;
}
