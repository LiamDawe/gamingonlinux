<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Email Us', 1);
$templating->set_previous('meta_description', 'Email form to contact GamingOnLinux.com', 1);

require_once("includes/curl_data.php");

$templating->load('email_us');

$templating->block('top');

$submit_article_text = '';
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$submit_article_text = 'If you are submitting a full article please use the <a href="/submit-article/">Submit Article area!</a> ';
}
$templating->set('submit_article_text', $submit_article_text);

$captcha = 0;
if (!$user->can('skip_contact_captcha'))
{
	$captcha = 1;
}

if (isset($_POST['act']))
{
	if (empty($_POST['message']))
	{
		$_SESSION['aname'] = $_POST['name'];
		$_SESSION['aemail'] = $_POST['email'];
		$_SESSION['atext'] = $_POST['message'];
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'email text';
		header('Location: /email-us/');
	}

	else
	{
		if ($captcha == 1)
		{
			if (!isset($_POST['g-recaptcha-response']))
			{
				$_SESSION['aname'] = $_POST['name'];
				$_SESSION['aemail'] = $_POST['email'];
				$_SESSION['atext'] = $_POST['message'];
				$_SESSION['message'] = 'captcha';
				header('Location: /email-us/');	
				die();		
			}
			$recaptcha=$_POST['g-recaptcha-response'];
			$google_url="https://www.google.com/recaptcha/api/siteverify";
			$ip=core::$ip;
			$url=$google_url."?secret=".$core->config('recaptcha_secret')."&response=".$recaptcha."&remoteip=".$ip;
			$res=getCurlData($url);
			$res= json_decode($res, true);
		}

		if ($captcha == 1 && !$res['success'])
		{
			$_SESSION['aname'] = $_POST['name'];
			$_SESSION['aemail'] = $_POST['email'];
			$_SESSION['atext'] = $_POST['message'];
			$_SESSION['message'] = 'captcha';
			header('Location: /email-us/');
		}

		else if (($captcha == 1 && $res['success']) || $captcha == 0)
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
			
			$subject = 'GamingOnLinux Contact Us - ' . $name;
			
			$message = core::make_safe($_POST['message']);
			
			$html_message = '<p>' . $name . ' writes,</p><p>' . $bbcode->email_bbcode($_POST['message']) . '</p>';

			$plain_message = "$name writes:" . PHP_EOL . $message;
			
			// Mail it
            if ($core->config('send_emails') == 1)
            {
				$mail = new mailer($core);
				$mail->sendMail($core->config('contact_email'), $subject, $html_message, $plain_message, ['name' => $name, 'email' => $_POST['email']]);
				
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
	
	$email = $user->user_details['email'];
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

if ($captcha == 1)
{
	$captcha_output = '<strong>You must do a captcha to register</strong><br />
	We use Google\'s reCAPTCHA, you must agree to their use of cookies to use it. This is to help us prevent spam!
	<button id="accept_captcha" type="button" data-pub-key="'.$core->config('recaptcha_public').'">Accept & Show reCAPTCHA</button>';
}

else
{
	$captcha_output = '';
}

$templating->set('captcha', $captcha_output);
?>
