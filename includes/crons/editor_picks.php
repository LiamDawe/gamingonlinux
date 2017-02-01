<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

$timeout = 1209600; // 14 days

$stamp = time() - $timeout;

$db->sqlquery("SELECT p.`article_id`, p.`featured_image`, p.hits, a.date, a.title FROM `editor_picks` p INNER JOIN `articles` a ON p.article_id = a.article_id WHERE a.`date` < ?", array($stamp));
$featured = $db->fetch_all_rows();

$games = '';

$total_to_remove = 0;
foreach($featured as $row)
{
	$games = $row['title'] . ' Hits: ' . $row['hits'] . '<br />';

	$db->sqlquery("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($row['article_id']));
	$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($row['article_id']));

	if (!empty($row['featured_image']))
	{
		$image = $path . 'uploads/carousel/' . $row['featured_image'];

		if (file_exists($image))
		{
			unlink($image);
		}
	}
	$total_to_remove++;
}

if ($total_to_remove > 0)
{
	$db->sqlquery("UPDATE `config` SET `data_value` = (data_value - $total_to_remove) WHERE `data_key` = 'total_featured'");
}

// count how many there are
$db->sqlquery("SELECT `article_id` FROM `editor_picks`");

$editor_pick_count = $db->num_rows();

if ($editor_pick_count < core::config('editor_picks_limit'))
{
	// subject
	$subject = "You need to set more editor picks on GamingOnLinux.com";
	
	// message
	$html_message = "<p>Hello <strong>admin</strong>,</p>
	<p>You need to <a href=\"https://www.gamingonlinux.com\">set more articles as an editors pick</a> to fill it all the way up to " . core::config('editor_picks_limit') . "!</p>
	<p>Games removed:</p>
	<p>$games</p>";

	// Mail it
	if (core::config('send_emails') == 1)
	{
		$mail = new mail(core::config['contact_email'], $subject, $html_message, $plain_message);
		$mail->send();
	}
}
