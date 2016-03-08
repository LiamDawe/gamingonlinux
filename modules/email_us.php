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
		$core->message("You have to enter all the fields to contact us!", NULL, 1);
	}
}

if (isset($_POST['act']))
{
	if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message']))
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
			$ip=$core->ip;
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
			$contact_emails = array($config['contact_email'], 'thesamsai@gmail.com');
			$to = implode(",", $contact_emails);
			// send the email
			$subject = 'GOL Contact Us - ' . $_POST['name'];				
			
			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n" . "Reply-To: {$_POST['name']} <{$_POST['email']}>\r\n";

			if (@mail($to, $subject, email_bbcode($_POST['message']), $headers))
			{
				unset($_SESSION['aname']);
				unset($_SESSION['aemail']);
				unset($_SESSION['amessage']);
				$core->message("Thank you for emailing us, we try to get back to you as soon as possible if needed!");
			}
			
			else
			{
				$core->message("Sadly there was an issue sending the mail, <strong>try emailing liamdawe a-t gmail d-o-t com manually.</strong>", NULL, 1);
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
	$captcha = '<div class="g-recaptcha" data-sitekey="6LcT0gATAAAAAOAGes2jwsVjkan3TZe5qZooyA-z"></div>';
}
		
else
{
	$captcha = '';
}

$templating->set('captcha', $captcha);
?>
