<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$title = "Reminder: Update your PC info for the next round of statistics updates";

$tagline = "This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.";

$text = "<p>This is your once a month reminder to make sure your PC information is correct on your user profiles. A fresh batch of statistics is generated on the 1st of each month.</p>
[pcinfo]
<p>You can see the statistics any time <a href=\"https://www.gamingonlinux.com/users/statistics\">on this page</a>.</p>
<p>PC Info is automatically purged if it hasn't been updated, or if you don't click the link to remain in for 2 years. This way we prevent too much stale data and don't hold onto your data for longer than required.</p>
<p>If you want your details to actually be included in the monthly survey, be sure to tick the box labelled \"Include your PC details in our Monthly User Statistics?\" and hit the \"Update\" button at the bottom, it's opt-in by default for new users.";

$slug = core::nice_title($title);

$dbl->run("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `gallery_tagline` = 26, `active` = 0, `admin_review` = 1", array(core::$date, $title, $slug, $tagline, $text));

$article_id = $dbl->new_id();

$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 22", array($article_id));
$dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 83", array($article_id));

// update admin notifications
$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = 1844, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array('article_admin_queue', core::$date, $article_id));
?>
