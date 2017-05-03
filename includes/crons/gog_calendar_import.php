<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_mail.php');

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'http://www.gog.com/games/feed?format=json&page=1';
if ($core->file_get_contents_curl($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach GOG calendar importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the calendar importer!", $headers);
	die('GOG XML not available!');
}

$games_added = '';
$email = 0;

$urlMask = 'http://www.gog.com/games/feed?format=json&page=%d';

$page = 0;
do {
	$url = sprintf($urlMask, ++$page);
	$array = json_decode($core->file_get_contents_curl($url), true);
	$count = count($array['games']);
	printf("Page #%d: %d product(s)\n", $page, $count);

	foreach ($array['games'] as $games)
	{
		if ($games['linux_compatible'] == 1)
		{
			$dont_use = 0;
			
			$website = $games['short_link'];
			
			// we don't want any of this junk, they aren't games
			$dont_want = ['Soundtrack', 'Soundtracks', 'Sound Track', ' OST', ' Pre-Order', ' Demo', 'Artbook'];
			foreach ($dont_want as $check_for)
			{
				if (strpos($games['title'], $check_for) !== false)
				{
					$dont_use = 1;
				}
			}
			
			// we don't want upgrades, they aren't games
			if (strpos($website, '_upgrade') !== false)
			{
				$dont_use = 1;
			}

			// what the fuck GOG, seriously, stop re-ordering the fucking "The", "Witcher 2, The" is not natural or pretty
			if (strpos($games['title'], ', The - The') !== false)
			{
				$games['title'] = str_replace(', The - The', ' - The', $games['title']);
				$games['title'] = 'The ' . $games['title'];
			}
			if (strpos($games['title'], ', The') !== false)
			{
				$games['title'] = str_replace(', The', '', $games['title']);
				$games['title'] = 'The ' . $games['title'];
			}

			if ($dont_use == 0)
			{
				$dlc = 0;
				if (strpos($website, '_dlc') !== false)
				{
					$dlc = 1;
				}

				$games['title'] = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $games['title']);

				echo $games['title'] . "<br />\n";
				echo "* Original release date: ". $games['original_release_date'] ."<br />\n";

				$grab_info = $dbl->run("SELECT `name`, `gog_link` FROM `calendar` WHERE `name` = ?", array($games['title']))->fetch();

				// if it does exist, make sure it's not from GOG already
				if (!$grab_info)
				{
					$dbl->run("INSERT INTO `calendar` SET `name` = ?, `gog_link` = ?, `date` = ?, `approved` = 1, `is_dlc` = ?", array($games['title'], $games['short_link'], $games['original_release_date'], $dlc));

					$calendar_id = $dbl->new_id();

					echo "\tAdded this game to the calendar DB with id: " . $calendar_id . ".\n";

					$games_added .= $games['title'] . '<br />';
				}

				// if we already have it, just update it
				else if (!empty($grab_info) && $grab_info['gog_link'] == NULL)
				{
					$dbl->run("UPDATE `calendar` SET `gog_link` = ?, `is_dlc` = ? WHERE `name` = ?", array($games['short_link'], $dlc, $games['title']));

					echo "Updated {$games['title']} with the latest information<br />";
				}
			}
		}
	}
} while ($count > 0);


echo "\n\n";//More whitespace, just to make the output look a bit more pretty

if (!empty($games_added))
{
	if ($core->config('send_emails') == 1)
	{
		$mail = new mail($core->config('contact_email'), 'The GOG calendar importer has added new games', 'New games added to the <a href="https://www.gamingonlinux.com/index.php?module=calendar">calendar</a> from GOG!<br />' . $games_added, '');
		$mail->send();
	}
}
