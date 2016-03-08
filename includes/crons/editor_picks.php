<?php
include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

// get config
$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
}

$timeout = 1209600; // 14 days


$stamp = time() - $timeout;

$db->sqlquery("SELECT `article_id`, `featured_image` FROM `articles` WHERE `date` < ?", array($stamp));
$featured = $db->fetch_all_rows();

// $_SERVER['DOCUMENT_ROOT'] does not exist in CLI mode
$_SERVER['DOCUMENT_ROOT'] = (isset($_SERVER['DOCUMENT_ROOT'])? $_SERVER['DOCUMENT_ROOT'] : "/home/gamingonlinux/public_html");

foreach($featured as $row)
{
	$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 0, `featured_image` = '' WHERE `article_id` = ?", array($row['article_id']));
	$image = $_SERVER['DOCUMENT_ROOT'] . $config['path'] . 'uploads/carousel/' . $row['featured_image'];

	if (file_exists($image))
		unlink($image);
}

// count how many there are
$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");

$editor_pick_count = $db->num_rows();

if ($editor_pick_count < $config['editor_picks_limit'])
{
	$to = "liamdawe@gmail.com";

	// subject
	$subject = "You need to set more editor picks on GamingOnLinux.com";

	// message
	$message = "
	<html>
	<head>
		<title>You need to set more editor picks on GamingOnLinux.com</title>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
	</head>
	<body>
		<img src=\"{$config['website_url']}{$config['path']}/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
		<br />
		<p>Hello <strong>liamdawe</strong>,</p>
		<p>You need to <a href=\"https://www.gamingonlinux.com\">set more articles as an editors pick</a> to fill it all the way up to 3!</p>
		<div>

	</body>
	</html>";

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

	// Mail it
	mail($to, $subject, $message, $headers);
}
