<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

$title = "Reminder: Update your PC info for the next round of statistics updates";

$tagline = "This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.";

$text = "This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.<br />
[pcinfo]<br />
You can see the statistics any time [url=https://www.gamingonlinux.com/users/statistics]on this page[/url].<br />
While we don't currently have a drop-off implemented for old/stale data, it will be coming soon. If you want to make sure you're included at any time clicking update without any changes will update the last time you edited them.<br />The drop-off for old data will be done in months, since people aren't likely to change hardware that often.";

$slug = core::nice_title($title);

$dbl->run("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `gallery_tagline` = 26, `active` = 0, `admin_review` = 1", array(core::$date, $title, $slug, $tagline, $text));

$article_id = $dbl->new_id();

$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 22", array($article_id));
$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 83", array($article_id));

// update admin notifications
$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = 1844, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array('article_admin_queue', core::$date, $article_id));
?>
