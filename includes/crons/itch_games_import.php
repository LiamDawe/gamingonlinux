<?php
echo "Itch importer started on " .date('d-m-Y H:m:s'). "\n";

$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_mail.php');

$date = strtotime(gmdate("d-n-Y H:i:s"));
$url = 'https://itch.io/feed/new.xml';
if ($core->file_get_contents_curl($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach Itch.io new games importer';
	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";
	mail($to, $subject, "Could not reach the new itch games importer!", $headers);
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

		$get_info = $db->sqlquery("SELECT `name`, `itch_link` FROM `calendar` WHERE `name` = ?", array($name));
		$grab_info = $get_info->fetch();

		$count_rows = $db->num_rows();

		// if it does exist, make sure it's not from itch already
		if ($count_rows == 1 && $grab_info['itch_link'] == NULL)
		{
			$db->sqlquery("UPDATE `calendar` SET `itch_link` = ? WHERE `name` = ?", array($game->link, $name));

			echo "Updated {$name} with the latest information<br />";
		}
	}

}

if (!empty($games_added))
{
  if (core::config('send_emails') == 1)
  {
    $mail = new mail(core::config('contact_email'), 'The itch new games importer has added new games', 'New games added to the <a href="https://www.gamingonlinux.com/index.php?module=calendar">calendar</a> from itch.io!<br />' . $games_added, '');
    $mail->send();
  }
}
echo "End of Itch.io import @ " . date('d-m-Y H:m:s');
