<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_mail.php');

$timeout = 1209600; // 14 days

$stamp = time() - $timeout;

$featured = $dbl->run("SELECT p.`article_id`, p.`featured_image`, p.hits, a.date, a.title FROM `editor_picks` p INNER JOIN `articles` a ON p.article_id = a.article_id WHERE a.`date` < ?", array($stamp))->fetch_all();

$games = '';

$total_to_remove = 0;
foreach($featured as $row)
{
	$games = $row['title'] . ' Hits: ' . $row['hits'] . '<br />';

	$dbl->run("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($row['article_id']));
	$dbl->run("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($row['article_id']));

	if (!empty($row['featured_image']))
	{
		$image = $core->config('path') . 'uploads/carousel/' . $row['featured_image'];

		if (file_exists($image))
		{
			unlink($image);
		}
	}
	$total_to_remove++;
}

if ($total_to_remove > 0)
{
	$dbl->run("UPDATE `config` SET `data_value` = (data_value - $total_to_remove) WHERE `data_key` = 'total_featured'");
}

// count how many there are
$editor_pick_count = $dbl->run("SELECT COUNT(`article_id`) FROM `editor_picks`")->fetchOne();

if ($editor_pick_count < $core->config('editor_picks_limit'))
{
	// subject
	$subject = "You need to set more editor picks on " . $core->config('site_title');
	
	// message
	$html_message = "<p>Hello <strong>admin</strong>,</p>
	<p>You need to <a href=\"https://www.gamingonlinux.com\">set more articles as an editors pick</a> to fill it all the way up to " . $core->config('editor_picks_limit') . "!</p>
	<p>Games removed:</p>
	<p>$games</p>";

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mail($core->config('contact_email'), $subject, $html_message, $plain_message);
		$mail->send();
	}
}
