<?php
session_start();

define("APP_ROOT", dirname( dirname(__FILE__) ) );

require APP_ROOT . "/includes/bootstrap.php";
?>
<form method="post" action="patreon.php">
	<label>Send emails?<input type="checkbox" name="emails" <?php if (isset($_POST['emails'])){echo'checked';}?>/></label><br />
	<label>Test run?<input type="checkbox" name="test_run" <?php if (isset($_POST['test_run'])){echo'checked';}?>/></label><br />
	<label>Don't remove users<input type="checkbox" name="dont_remove" <?php if (isset($_POST['dont_remove'])){echo'checked';}?>/></label><br />
	<em>Tick this if doing it for newer pledges during a month only, otherwise it will remove everyone else!</em><br />
	<br />
	<button type="submit" name="go" value="1">Process</button>
</form>

<?php
if (isset($_POST['go']))
{
    echo 'Working...' . PHP_EOL;
    $csv = array_map('str_getcsv', file('patreon.csv'));

	$email_list = [];

    array_splice($csv, 0, 1);

	foreach ($csv as $line)
	{
		// make it a proper decimal number to compare against
		$pledge = str_replace('$', '', $line[6]);
		$status = trim($line[18]);
        $email = trim($line[1]);

		// if they pledge at least 5 dollars a month
		if ($pledge >= 4 && $status == 'Paid')
		{
			$user_info = $dbl->run("SELECT `username`, `user_id` FROM `users` WHERE `email` = ? OR `supporter_email` = ?", array($email, $email))->fetch();
			// it didn't find an account, email them
			if (!$user_info)
			{
				if ($core->config('send_emails') == 1 && isset($_POST['emails']))
				{
					$html_message = "Hello from <a href=\"https://www.gamingonlinux.com\">GamingOnLinux.com</a>! Thank you for supporting us on Patreon.<br />
					<br />
					We have tried to match your Patreon registered email up to a username on the website, but we didn't find anything.<br />
					<br />
					<strong>Don't worry</strong>, if you already have your GOL Supporter badge you can ignore this email! <br />
					<br />
					If you haven't, please go to the <a href=\"https://www.gamingonlinux.com/usercp.php?module=patreon\">Patreon Linking page</a> and sign-in with Patreon and it should sort it all for you! It will set your Patreon email and refresh your status.<br />
					<br />
					Thank you. Any problems, feel free to reply as this inbox is monitored!";

					$plain_message = "Hello from GamingOnLinux.com! Thank you for supporting us on Patreon. We have tried to match your Patreon registered email up to a username on the website, but we didn't find anything.  Don't worry, if you already have your GOL Supporter badge you can ignore this email! If you haven't, please go to the Patreon Linking page: https://www.gamingonlinux.com/usercp.php?module=patreon and sign-in with Patreon and it should sort it all for you! It will set your Patreon email and refresh your status. Thank you. Any problems, feel free to reply as this inbox is monitored!";

					if (!isset($_POST['test_run']))
					{
						$mail = new mailer($core);
						$mail->sendMail($email, 'Thank you for supporting GamingOnLinux, more info may be needed', $html_message, $plain_message, ['name' => 'Liam Dawe', 'email' => 'contact@gamingonlinux.com']);
					}

					echo "Email sent to " . $email . '<br />';
				}
			}
			// it found an account, give them their badge
			else
			{
				// gather a list of all emails that are eligble for supporter status
				$user_id_list[] = $user_info['user_id'];

				$their_groups = $user->post_group_list([$user_info['user_id']]);

				echo '<p>Username: ' . $user_info['username'] . ' ' . $line[2] . ' | Pledge: '. $pledge .'<br />';

				echo 'Their user groups: ' . implode(', ', $their_groups[$user_info['user_id']]) . '</p>';

				$dbl->run("UPDATE `users` SET `supporter_type` = 'patreon' WHERE `user_id` = ?", array($user_info['user_id']));

				if ($pledge >= 4)
				{
					// they're not currently set as a supporter, give them the status
					if (!in_array(6, $their_groups[$user_info['user_id']]))
					{
						if (!isset($_POST['test_run']))
						{
							$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 6", [$user_info['user_id']]);
						}

						echo "\nGiven Supporter status\n\n";
					}
					// add them to the Patreon user group if they're not in it already
					if (!in_array(13, $their_groups[$user_info['user_id']]))
					{
						if (!isset($_POST['test_run']))
						{
							$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 13", [$user_info['user_id']]);
						}

						echo "\nAdded user to Patreon user group.\n\n";
					}
					if ($pledge <= 6)
					{
						// they don't pledge enough for supporter plus, if they're currently in it then remove them
						if (in_array(9, $their_groups[$user_info['user_id']]))
						{
							if (!isset($_POST['test_run']))
							{
								$dbl->run("DELETE FROM `user_group_membership` WHERE `user_id` = ? AND `group_id` = 9", [$user_info['user_id']]);
							}

							echo "\nRemoved Supporter Plus status\n\n";
						}
					}
				}
				// they pledge enough to be given the Supporter Plus group
				if ($pledge >= 7)
				{
					if (!in_array(9, $their_groups[$user_info['user_id']]))
					{
						if (!isset($_POST['test_run']))
						{
							$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 9", [$user_info['user_id']]);
						}

						echo "\nGiven Supporter Plus status\n\n";
					}
				}
			}
		}
		unset($pledge); // don't let them accidentally add up
		unset($status);
		unset($their_groups);
		unset($email);
	}

	if (!isset($_POST['test_run']) && !empty($user_id_list) && !isset($_POST['dont_remove']))
	{
		// okay, now we need to remove anyone not in that list
		$in = str_repeat('?,', count($user_id_list) - 1) . '?';

		$remove_sql = "DELETE g FROM `user_group_membership` g INNER JOIN `users` u ON u.`user_id` = g.`user_id` WHERE u.`user_id` NOT IN ($in) AND g.group_id IN (9,6,13) AND u.`lifetime_supporter` = 0 AND u.`supporter_type` = 'patreon'";
		$dbl->run($remove_sql, $user_id_list);

		$removed = $dbl->rowcount();

		if ($removed > 0)
		{
			echo PHP_EOL . $removed . ' total users removed as supporters.';
		}

		$dbl->run("UPDATE `users` SET `supporter_type` = NULL WHERE `supporter_type` = 'patreon' AND `user_id` NOT IN ($in)", $user_id_list);
	}
}
?>
