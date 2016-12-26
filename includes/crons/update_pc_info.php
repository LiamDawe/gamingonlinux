<?php
define('path', '/home/gamingonlinux/public_html/includes/');
//define('path', '/mnt/storage/public_html/includes/');

include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path. 'class_core.php');
$core = new core();

include(path . 'bbcode.php');

// setup the templating, if not logged in default theme, if logged in use selected theme
include(path . 'class_template.php');

$templating = new template();

$title = "Reminder: Update your PC info for the next round of statistics updates";

$tagline = "This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.";

$text = "This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.<br />
[pcinfo]<br />
You can see the statistics any time [url=https://www.gamingonlinux.com/users/statistics]on this page[/url].<br />
While we don't currently have a drop-off implemented for old/stale data, it will be coming soon. If you want to make sure you're included at any time clicking update without any changes will update the last time you edited them.<br />The drop-off for old data will be done in months, since people aren't likely to change hardware that often.";

// DEBUG
//echo $text;

$slug = $core->nice_title($title);

$db->sqlquery("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `tagline_image` = 'defaulttagline.png'", array(core::$date, $title, $slug, $tagline, $text));

$article_id = $db->grab_id();

$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 22", array($article_id));
$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 83", array($article_id));

include(core::config('path') . 'includes/telegram_poster.php');
telegram($title . ' ' . core::config('website_url') . "articles/" . $slug . '.' . $article_id);
?>
