<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_mail.php');

$date = strtotime(gmdate("d-n-Y H:i:s"));
$url = 'https://itch.io/feed/new.xml';
if ($core->file_get_contents_curl($url) == true)
{
	// magic
}
else
{
	$subject = 'GOL ERROR - Cannot reach Itch.io new games importer';
	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";
	mail($core->config('contact_email'), $subject, "Could not reach the new itch games importer!", $headers);
	die('Itch XML not available!');
}

$get_url = $core->file_get_contents_curl($url);
$get_url = preg_replace("^&(?!#38;)^", "&amp;", $get_url);
$xml = simplexml_load_string($get_url);

$games_added = '';
$email = 0;
foreach ($xml->channel->item as $game)
{
	if ($game->{'platforms'}->linux == 'yes')
	{
		$game->plainTitle = html_entity_decode($game->plainTitle, ENT_QUOTES);

		$name = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $game->plainTitle);

		$parsed_release_date = strtotime($game->pubDate);
		$released_date = date('Y-m-d', $parsed_release_date);

		$grab_info = $dbl->run("SELECT `name`, `itch_link` FROM `calendar` WHERE `name` = ?", array($name))->fetch();

		// if it does exist, make sure it's not from itch already
		if (!empty($grab_info) && $grab_info['itch_link'] == NULL)
		{
			$dbl->run("UPDATE `calendar` SET `itch_link` = ? WHERE `name` = ?", array($game->link, $name));

			echo "Updated {$name} with the latest information<br />";
		}
	}

}

if (!empty($games_added))
{
	if ($core->config('send_emails') == 1)
	{
		$mail = new mail($core->config('contact_email'), 'The itch new games importer has added new games', 'New games added to the <a href="https://www.gamingonlinux.com/index.php?module=calendar">calendar</a> from itch.io!<br />' . $games_added, '');
		$mail->send();
	}
}
