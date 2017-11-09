<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$title = "Reminder: Update your PC info for the next round of statistics updates";

$tagline = "This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.";

$text = "<p>This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.</p>
[pcinfo]
<p>You can see the statistics any time <a href=\"https://www.gamingonlinux.com/users/statistics\">on this page</a>.</p>
<p>While we don't currently have a drop-off implemented for old/stale data, it will be coming soon. If you want to make sure you're included at any time clicking update without any changes will update the last time you edited them.<br />The drop-off for old data will be done in months, since people aren't likely to change hardware that often.</p>";

$slug = core::nice_title($title);

$dbl->run("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `gallery_tagline` = 26, `active` = 0, `admin_review` = 1", array(core::$date, $title, $slug, $tagline, $text));

$article_id = $dbl->new_id();

$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 22", array($article_id));
$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 83", array($article_id));

// update admin notifications
$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = 1844, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array('article_admin_queue', core::$date, $article_id));
?>
