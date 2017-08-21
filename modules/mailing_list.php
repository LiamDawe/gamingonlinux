<?php
if (isset($_GET['type']))
{
	if ($_GET['type'] == 'guestsub')
	{
		if (isset($_GET['id']) && isset($_GET['key']))
		{
			// check key
			$check = $dbl->run("SELECT `email` FROM `mailing_list` WHERE `id` = ? AND `activation_key` = ?", array($_GET['id'], $_GET['key']))->fetch();
			if ($check)
			{
				$dbl->run("UPDATE `mailing_list` SET `activated` = 1, `activated_date` = ? WHERE `id` = ?", array(core::$sql_date_now, $_GET['id']));
				$_SESSION['message'] = 'mail_list_subbed';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}				
			}
			else
			{
				$_SESSION['message'] = 'no_key_match';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}
			}
		}
		else
		{
			$_SESSION['message'] = 'keys_missing';
			if ($core->config('pretty_urls') == 1)
			{
				header('Location: '.$core->config('website_url').'mailinglist');
			}
			else
			{
				header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
			}
		}
	}
	
	if ($_GET['type'] == 'remove_guest')
	{
		if (isset($_GET['id']) && isset($_GET['key']))
		{
			// check key
			$check = $dbl->run("SELECT `email` FROM `mailing_list` WHERE `id` = ? AND `unsub_key` = ?", array($_GET['id'], $_GET['key']))->fetch();
			if ($check)
			{
				$dbl->run("DELETE FROM `mailing_list` WHERE `id` = ?", array($_GET['id']));
				$_SESSION['message'] = 'mail_list_unsubbed';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}				
			}
			else
			{
				$_SESSION['message'] = 'no_key_match';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}
			}
		}
		else
		{
			$_SESSION['message'] = 'keys_missing';
			if ($core->config('pretty_urls') == 1)
			{
				header('Location: '.$core->config('website_url').'mailinglist');
			}
			else
			{
				header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
			}
		}		
	}
	
	if ($_GET['type'] == 'remove_user')
	{
		if (isset($_GET['id']) && isset($_GET['key']))
		{
			// check key
			$check = $dbl->run("SELECT `user_id` FROM `users` WHERE `user_id` = ? AND `mailing_list_key` = ?", array($_GET['id'], $_GET['key']))->fetch();
			if ($check)
			{
				$dbl->run("UPDATE `users` SET `email_articles` = NULL WHERE `user_id` = ?", array($_GET['id']));
				$_SESSION['message'] = 'mail_list_unsubbed';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}				
			}
			else
			{
				$_SESSION['message'] = 'no_key_match';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}
			}
		}
		else
		{
			$_SESSION['message'] = 'keys_missing';
			if ($core->config('pretty_urls') == 1)
			{
				header('Location: '.$core->config('website_url').'mailinglist');
			}
			else
			{
				header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
			}
		}		
	}
}

$templating->set_previous('title', 'Daily article email mailing list', 1);
$templating->set_previous('meta_description', 'Sign up to get Linux gaming articles sent to your inbox once a day', 1);

$templating->load('mailing_list');
$templating->block('top', 'mailing_list');

if (!isset($_SESSION['user_id']) || isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0)
{
	$templating->block('guest_add', 'mailing_list');
}
else if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$templating->block('user_edit', 'mailing_list');
	
	// check their sub
	$check_sub = $dbl->run("SELECT `username`, `email_articles`, `email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
	
	if ($check_sub['email_articles'] == 'daily')
	{
		$action = 'user_unsub';
		$text = 'Unsubscribe';
	}
	else if ($check_sub['email_articles'] = '' || $check_sub['email_articles'] == NULL)
	{
		$action = 'user_sub';
		$text = 'Subscribe';		
	}
	$templating->set('action', $action);
	$templating->set('text', $text);
	$templating->set('username', $check_sub['username']);
	$templating->set('email', $check_sub['email']);
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'user_unsub')
	{
		$dbl->run("UPDATE `users` SET `email_articles` = NULL WHERE `user_id` = ?", array($_SESSION['user_id']));
		
		$_SESSION['message'] = 'mail_list_unsubbed';
		if ($core->config('pretty_urls') == 1)
		{
			header('Location: '.$core->config('website_url').'mailinglist');
		}
		else
		{
			header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
		}
	}
	
	if ($_POST['act'] == 'user_sub')
	{
		$activation_key = core::random_id();
		$dbl->run("UPDATE `users` SET `email_articles` = 'daily', `mailing_list_key` = ? WHERE `user_id` = ?", array($activation_key, $_SESSION['user_id']));
		
		$_SESSION['message'] = 'mail_list_subbed';
		if ($core->config('pretty_urls') == 1)
		{
			header('Location: '.$core->config('website_url').'mailinglist');
		}
		else
		{
			header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
		}
	}
	
	if ($_POST['act'] == 'guest_sub')
	{
		$email = trim($_POST['email']);
		if (empty($email))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'email';
			if ($core->config('pretty_urls') == 1)
			{
				header('Location: '.$core->config('website_url').'mailinglist');
			}
			else
			{
				header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
			}
			die();
		}
		else
		{
			// check it's not already in there
			$check_exists = $dbl->run("SELECT `email` FROM `mailing_list` WHERE `email` = ?", array($email))->fetchOne();
			
			if (!$check_exists)
			{
				$activation_key = core::random_id();
				$dbl->run("INSERT INTO `mailing_list` SET `email` = ?, `unsub_key` = ?, `activated` = 0, `activation_key` = ?", array($email, core::random_id(), $activation_key));
				$row_id = $dbl->new_id();
				
				// subject
				$subject = "Confirmation required for the GamingOnLinux article mailing list";
				
				$link = $core->config('website_url') . 'index.php?module=mailing_list&type=guestsub&key=' . $activation_key . '&id=' . $row_id;

				// message
				$html_message = '<p>Hello,</p>
				<p>Please <a href="'.$link.'">click here</a> to confirm you\'re subscribing to the GamingOnLinux daily article mailing list!</em></p>';
				
				$plain_message = PHP_EOL.'Hello, please go here to confirm you\'re subscribing to the GamingOnLinux daily article mailing list: ' . $link;

				// Mail it
				if ($core->config('send_emails') == 1)
				{
					$mail = new mailer($core);
					$mail->sendMail($email, $subject, $html_message, $plain_message);
				}
				
				$_SESSION['message'] = 'mail_list_subbed_guest';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}
			}
			else
			{
				$_SESSION['message'] = 'email_exists';
				if ($core->config('pretty_urls') == 1)
				{
					header('Location: '.$core->config('website_url').'mailinglist');
				}
				else
				{
					header('Location: '.$core->config('website_url').'index.php?module=mailing_list');			
				}				
			}
		}
	}
}
?>
