<?php
$templating->set_previous('title', 'Email Us', 1);
$templating->set_previous('meta_description', 'Email form to contact GamingOnLinux.com', 1);

require_once("includes/curl_data.php");

$templating->merge('email_us');

$templating->block('top');

if (isset($_POST['act']))
{
	if (empty($_POST['message']))
	{
		$_SESSION['aname'] = $_POST['name'];
		$_SESSION['aemail'] = $_POST['email'];
		$_SESSION['atext'] = $_POST['message'];
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'email text';

		if (core::config('pretty_urls') == 1)
		{
			header('Location: /email-us/');
		}
		else
		{
			header('Location: /index.php?module=email_us');
		}
	}

	else
	{
		if ($parray['contact_captcha'] == 1)
		{
			$recaptcha=$_POST['g-recaptcha-response'];
			$google_url="https://www.google.com/recaptcha/api/siteverify";
			$ip=core::$ip;
			$url=$google_url."?secret=".core::config('recaptcha_secret')."&response=".$recaptcha."&remoteip=".$ip;
			$res=getCurlData($url);
			$res= json_decode($res, true);
		}

		if ($parray['contact_captcha'] == 1 && !$res['success'])
		{
			$_SESSION['aname'] = $_POST['name'];
			$_SESSION['aemail'] = $_POST['email'];
			$_SESSION['atext'] = $_POST['message'];
			$_SESSION['message'] = 'captcha';
			
			if (core::config('pretty_urls') == 1)
			{
				header('Location: /email-us/');
			}
			else
			{
				header('Location: /index.php?module=email_us');
			}
		}

		else if (($parray['contact_captcha'] == 1 && $res['success']) || $parray['contact_captcha'] == 0)
		{
			// send the email
			$additional_header = '';
			if (isset($_POST['name']) && !empty($_POST['name']))
			{
				$name = htmlspecialchars($_POST['name']);
			}
			else
			{
				$name = 'Anonymous';
			}
			
			if (isset($_POST['email']) && !empty($_POST['email']))
			{
				if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
				{
					$additional_header = '';
				}
				else
				{
					$additional_header = "Reply-To: $name <{$_POST['email']}>";
				}
			}
			
			$subject = core::config('site_title') . ' Contact Us - ' . $name;
			
			$message = core::make_safe($_POST['message']);
			
			$html_message = '<p>' . $name . ' writes,</p>' . email_bbcode($_POST['message']);
			
			// Mail it
            if (core::config('send_emails') == 1)
            {
				$mail = new mail(core::config('contact_email'), $subject, $html_message, '', $additional_header);
				$mail->send();
				
				unset($_SESSION['aname']);
				unset($_SESSION['aemail']);
				unset($_SESSION['atext']);
				$core->message("Thank you for emailing us, we try to get back to you as soon as possible if needed!");
			}
		}
	}
}

$name = '';
$email = '';
$message = '';

if (($_SESSION['user_id'] != 0) && (!isset($_GET['message']) || isset($_GET['message']) && $_GET['message'] != 'empty'))
{
	$name = $_SESSION['username'];

	$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$get_email = $db->fetch();

	$email = $get_email['email'];
}

if (isset($_SESSION['message']) && $_SESSION['message'] == 'empty')
{
	$name = $_SESSION['aname'];
	$email = $_SESSION['aemail'];
	$message = $_SESSION['atext'];
}

$templating->block('main', 'email_us');
$templating->set('name', $name);
$templating->set('email', $email);
$templating->set('message', $message);
$templating->set('url', url);

// social and sharing icons
$social_icons = [
'twitter_username' => ['config' => 'twitter_username', 'icon' => 'twitter.svg'],
'gplus_page' => ['config' => 'gplus_page', 'icon' => 'google-plus.svg'],
'facebook_page' => ['config' => 'facebook_page', 'icon' => 'facebook.svg']
];

$social_output = '';
foreach ($social_icons as $social)
{
	$extra_url = '';
	if (!empty(core::config($social['config'])))
	{
		if ($social['config'] == 'twitter_username')
		{
			$extra_url = 'https://www.twitter.com/';
		}
		
		$social_output .= '<a class="button small fnone" href="'.$extra_url.core::config($social['config']).'" target="_blank"><img src="'.core::config('website_url').'templates/'.core::config('template').'/images/social/'.$social['icon'].'"></a>';
	}
}
$templating->set('social_icons', $social_output);

if ($parray['contact_captcha'] == 1)
{
	$captcha = '<noscript><strong>You need Javascript turned on to see the captcha, otherwise you won\'t be able to email us!</strong></noscript><div class="g-recaptcha" data-sitekey="'.core::config('recaptcha_public').'"></div>';
}

else
{
	$captcha = '';
}

$templating->set('captcha', $captcha);
?>
