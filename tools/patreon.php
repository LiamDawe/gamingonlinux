<?php
session_start();

if (isset($_POST['go']))
{
	$file_dir = dirname( dirname(__FILE__) );
	
	include $file_dir . '/includes/config.php';

	include($file_dir. '/includes/class_db_mysql.php');
	$dbl = new db_mysql();

	include($file_dir . '/includes/class_core.php');
	$core = new core($dbl, $file_dir);

	include($file_dir . '/includes/class_mail.php');
	
	include($file_dir . '/includes/class_user.php');
	$user = new user($dbl, $core);

	$csv = array_map('str_getcsv', file('patreon.csv'));

	array_splice($csv, 0, 2);
	foreach ($csv as $line)
	{
		// make it a proper decimal number to compare against
		$pledge = (float) $line[3];

		// if they pledge at least 5 dollars a month
		if ($pledge >= 5)
		{
			$user_info = $dbl->run("SELECT `username`, `user_id` FROM `users` WHERE `email` = ?", array($line[2]))->fetch();
			// it didn't find an account, email them
			if (!$user_info)
			{
				if ($core->config('send_emails') == 1 && isset($_POST['emails']))
				{
					$html_message = "Hello from Liam at <a href=\"https://www.gamingonlinux.com\">GamingOnLinux.com</a>! Thank you for supporting me on Patreon.<br />
					<br />
					I have tried to match your Patreon registered email up to a username on the website, but I didn't find anything.<br />
					<br />
					<strong>Don't worry</strong>, if you already have your GOL Supporter badge you can ignore this email! <br />
					<br />
					If you haven't, please reply with your username or email attached to a GOL account. You're likely using a different email address on Patreon to what you use on GOL.<br />
					<br />
					Thank you.";
					
					$plain_message = "Hello from Liam at GamingOnLinux.com! Thank you for supporting me on Patreon. I have tried to match your Patreon registered email up to a username on the website, but I didn't find anything.  Don't worry, if you already have your GOL Supporter badge you can ignore this email! If you haven't, please reply with your username or email attached to a GOL account. You're likely using a different email address on Patreon to what you use on GOL. Thank you.";
					
					$mail = new mailer($core);
					$mail->sendMail($line[2], 'Thank you for supporting GamingOnLinux, more info may be needed', $html_message, $plain_message, ['name' => 'Liam Dawe', 'email' => 'contact@gamingonlinux.com']);

					echo "Email sent to " . $line[2] . '<br />';
				}
			}
			// it found an account, give them their badge
			else
			{
				$their_groups = $user->post_group_list([$user_info['user_id']]);
				if (!in_array(6, $their_groups[$user_info['user_id']]))
				{
					echo 'Username: ' . $user_info['username'] . ' ' . $line[2] . ' | Pledge: '. $pledge .'<pre>';
					print_r($their_groups);
					echo '</pre>';
					
					$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 6", [$user_info['user_id']]);
					
					echo "\nGiven Supporter status\n\n";
				}
				if ($pledge >= 7)
				{
					$their_groups = $user->post_group_list([$user_info['user_id']]);
					if (!in_array(9, $their_groups[$user_info['user_id']]))
					{
						echo 'Username: ' . $user_info['username'] . ' ' . $line[2] . ' | Pledge: '. $pledge .'<pre>';
						print_r($their_groups);
						echo '</pre>';
						
						$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 9", [$user_info['user_id']]);
						
						echo "\nGiven Supporter Plus status\n\n";
					}
				}
			}
		}
		unset($pledge); // don't let them accidentally add up
	}
}
?>
<form method="post" action="patreon.php">
	<label>Send emails?<input type="checkbox" name="emails" /></label><br />
	<button type="submit" name="go" value="1">Process</button>
</form>
