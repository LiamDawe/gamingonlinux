<?php
include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

$timeout = 31536000; // 1 year

$stamp = time() - $timeout;

$closed = '';
$db->sqlquery("SELECT `title` FROM `articles` WHERE `date` < ? AND `comments_open` = 1", array($stamp));
while ($closing = $db->fetch())
{
	$closed .= $closing['title'] . '<br />';
}

$db->sqlquery("UPDATE `articles` SET `comments_open` = 0 WHERE `date` < ?", array($stamp));

// multiple recipients
$to = 'liamdwe@gmail.com';
$subject = 'GOL Contact Us - Comments Closed';

// To send HTML mail, the Content-type header must be set
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

mail($to, $subject, "Comments closed on these articles: " . $closed, $headers);
echo "Comments closed on these articles: " . $closed;
