<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/cron_bootstrap.php";

$stamp = strtotime("-1 year", time());

$html_message = "Comments closed on these articles:<br />";
$plain_message = "Comments closed on these articles:\r\n";
$id_list = array();
$closing = $dbl->run("SELECT `article_id`, `title` FROM `articles` WHERE `date` < ? AND `comments_open` = 1", array($stamp))->fetch_all();
foreach ($closing as $to_close)
{
	$id_list[] = $to_close['article_id'];
	$html_message .= $to_close['title'] . '<br />';
	$plain_message .= $to_close['title'] . "\r\n";
}

if ($closing)
{
	$dbl->run("UPDATE `articles` SET `comments_open` = 0 WHERE `date` < ?", array($stamp));

	$in  = str_repeat('?,', count($id_list) - 1) . '?';
	$dbl->run("DELETE FROM `articles_subscriptions` WHERE `article_id` IN ($in)", $id_list);

	$subject = 'GamingOnLinux CRON - Comments Closed';

	$mail = new mailer($core);
	$mail->sendMail($core->config('contact_email'), $subject, $html_message, $plain_message);
}