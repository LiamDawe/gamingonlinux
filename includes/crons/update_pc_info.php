<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$title = "Reminder: Update your PC info for the next round of statistics updates";

$tagline = "This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.";

$text = "<p>This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.</p>

<p>[pcinfo]</p>

<p>You can see the statistics any time <a href=\"https://www.gamingonlinux.com/users/statistics\" target=\"_blank\">on this page</a>.</p>

<p>If you want your details to actually be included in the monthly survey, be sure to <a href=\"https://www.gamingonlinux.com/usercp.php?module=pcinfo\" target=\"_blank\">head here</a> and tick the box labelled &quot;Include your PC details in our Monthly User Statistics?&quot; and hit the &quot;Update&quot; button at the bottom, it&#39;s opt-in and you can uncheck it at any time not to be included in future statistics gathering.</p>

<p>PC Info is automatically purged if it hasn&#39;t been updated, or if you don&#39;t click the link to remain in for 2 years. This way we prevent too much stale data and don&#39;t hold onto your data for longer than required.</p>";

$slug = core::nice_title($title);

$dbl->run("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `gallery_tagline` = 26, `active` = 0, `admin_review` = 1", array(core::$date, $title, $slug, $tagline, $text));

$article_id = $dbl->new_id();

$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 22", array($article_id));
$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 83", array($article_id));

// update admin notifications
$admin_note_content = ' added an article to the admin queue titled: Reminder: Update your PC info for the next round of statistics updates';
$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = 1844, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?, `content` = ?", array('article_admin_queue', core::$date, $article_id, $admin_note_content));
?>
