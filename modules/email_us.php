<?php
$templating->set_previous('title', 'Email Us', 1);
$templating->set_previous('meta_description', 'Email form to contact GamingOnLinux.com', 1);

require_once("includes/curl_data.php");

$templating->merge('email_us');

$templating->block('top');

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'empty')
	{
		$core->message("You don't have to enter a name or an email address, but you do have to write an actual message to send!", NULL, 1);
	}
}

if (isset($_POST['act']))
{
	if (empty($_POST['message']))
	{
		$_SESSION['aname'] = $_POST['name'];
		$_SESSION['aemail'] = $_POST['email'];
		$_SESSION['amessage'] = $_POST['message'];

		header('Location: /email-us/message=empty');
	}

	else
	{
		if ($parray['contact_captcha'] == 1)
		{
			$recaptcha=$_POST['g-recaptcha-response'];
			$google_url="https://www.google.com/recaptcha/api/siteverify";
			$secret='6LcT0gATAAAAAJrRJK0USGyFE4pFo-GdRTYcR-vg';
			$ip=core::$ip;
			$url=$google_url."?secret=".$secret."&response=".$recaptcha."&remoteip=".$ip;
			$res=getCurlData($url);
			$res= json_decode($res, true);
		}

		if ($parray['contact_captcha'] == 1 && !$res['success'])
		{
			$core->message("You need to complete the captcha to prove you are human and not a bot!", NULL, 1);
		}

		else if (($parray['contact_captcha'] == 1 && $res['success']) || $parray['contact_captcha'] == 0)
		{
			// send the email
			$additional_header = '';
			if (isset($_POST['name']) && !empty($_POST['name']))
			{
				$name = htmlentities($_POST['name']);
			}
			else
			{
				$name = 'Anonymous';
			}
			
			if (isset($_POST['email']) && !empty($_POST['email']))
			{
				$additional_header = "Reply-To: $name <{$_POST['email']}>";
			}
			
			$subject = 'GOL Contact Us - ' . $name;
			
			$html_message = '<p>' . $name . ' writes,</p>' . email_bbcode($_POST['message']);
			
			// Mail it
            if (core::config('send_emails') == 1)
            {
				$mail = new mail(core::config('contact_email'), $subject, $html_message, '', $additional_header);
				$mail->send();
				
				unset($_SESSION['aname']);
				unset($_SESSION['aemail']);
				unset($_SESSION['amessage']);
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

if (isset($_GET['message']) && $_GET['message'] == 'empty')
{
	$name = $_SESSION['aname'];
	$email = $_SESSION['aemail'];
	$message = $_SESSION['amessage'];
}

$templating->block('main', 'email_us');
$templating->set('name', $name);
$templating->set('email', $email);
$templating->set('message', $message);
$templating->set('url', url);

if ($parray['contact_captcha'] == 1)
{
	$captcha = '<noscript><strong>You need Javascript turned on to see the captcha, otherwise you won\'t be able to email us!</strong></noscript><div class="g-recaptcha" data-sitekey="6LcT0gATAAAAAOAGes2jwsVjkan3TZe5qZooyA-z"></div>';
}

else
{
	$captcha = '';
}

$templating->set('captcha', $captcha);
?>
