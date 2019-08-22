<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/bootstrap.php";

$timeout = 31536000; // 1 year

$stamp = time() - $timeout;

$html_message = "Comments closed on these articles:<br />";
$plain_message = "Comments closed on these articles:\r\n";
$closing = $dbl->run("SELECT `title` FROM `articles` WHERE `date` < ? AND `comments_open` = 1", array($stamp))->fetch_all();
foreach ($closing as $to_close)
{
	$html_message .= $to_close['title'] . '<br />';
	$plain_message .= $to_close['title'] . "\r\n";
}

$dbl->run("UPDATE `articles` SET `comments_open` = 0 WHERE `date` < ?", array($stamp));

$subject = 'GamingOnLinux CRON - Comments Closed';

$mail = new mailer($core);
$mail->sendMail($core->config('contact_email'), $subject, $html_message, $plain_message);