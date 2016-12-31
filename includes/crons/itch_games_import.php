<?php
echo "Itch importer started on " .date('d-m-Y H:m:s'). "\n";

//define('path', '/home/gamingonlinux/public_html/includes/');
define('path', '/mnt/storage/public_html/includes/');

include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'class_core.php');
$core = new core();

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

// for seeing what we have available
/*
echo '<pre>';
print_r($xml);
echo '</pre>';*/

$games = '';
$email = 0;
foreach ($xml->channel->item as $game)
{

	// for seeing what we have available
	/*
	echo '<pre>';
	print_r($game);
	echo '</pre>';*/

	// put Operating Systems into an array so we can loop through the Linux ones
	if ($game->{'platforms'}->linux == 'yes')
	{
		$game->plainTitle = html_entity_decode($game->plainTitle, ENT_QUOTES);
		echo $game->plainTitle . '<br />';
		/*
		$db->sqlquery("SELECT `name` FROM `game_list` WHERE `name` = ?", array($game->plainTitle));
		if ($db->num_rows() == 0)
		{
			$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($game->plainTitle));
		}*/
	}
echo "\n";
}
echo "\n\n";
/*
if ($email == 1)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - Itch.io sales added';
	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";
	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto Itch.io salesman has added<br />$games", $headers);
	echo "Mail sent!";
}*/
echo "End of Itch.io import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
