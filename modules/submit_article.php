<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
// check for ipban (spammers) and don't let them submit
$get_ip = $dbl->run("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip))->fetchOne();
if ($get_ip)
{
	$_SESSION['message'] = 'spam';
	header('Location: /index.php?module=home');
	die();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
    $templating->set_previous('title', 'No Access', 1);
	$core->message('You do not have permissions to view this page! You need to be logged in to submit an article.');

	$templating->load('login');
	$templating->block('small');
	$templating->set('current_page', core::current_page_url());
	$templating->set('url', $core->config('website_url'));
	
	$twitter_button = '';
	if ($core->config('twitter_login') == 1)
	{	
		$twitter_button = '<a href="'.$core->config('website_url').'index.php?module=login&twitter" class="btn-auth btn-twitter"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/twitter.png" /> </span>Sign in with <b>Twitter</b></a>';
	}
	$templating->set('twitter_button', $twitter_button);
	
	$steam_button = '';
	if ($core->config('steam_login') == 1)
	{
		$steam_button = '<a href="'.$core->config('website_url').'index.php?module=login&steam" class="btn-auth btn-steam"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/steam.png" /> </span>Sign in with <b>Steam</b></a>';
	}
	$templating->set('steam_button', $steam_button);
	
	$google_button = '';
	if ($core->config('google_login') == 1)
	{
		$client_id = $core->config('google_login_public'); 
		$client_secret = $core->config('google_login_secret');
		$redirect_uri = $core->config('website_url') . 'includes/google/login.php';
		require_once ($core->config('path') . 'includes/google/libraries/Google/autoload.php');
		$client = new Google_Client();
		$client->setClientId($client_id);
		$client->setClientSecret($client_secret);
		$client->setRedirectUri($redirect_uri);
		$client->addScope("email");
		$client->addScope("profile");
		$service = new Google_Service_Oauth2($client);
		$authUrl = $client->createAuthUrl();
		
		$google_button = '<a href="'.$authUrl.'" class="btn-auth btn-google"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/google-plus.png" /> </span>Sign in with <b>Google</b></a>';
	}
	$templating->set('google_button', $google_button);

	include(APP_ROOT . '/includes/footer.php');
	die();
}

$templating->set_previous('meta_description', 'Submit an article to GamingOnLinux', 1);
$templating->set_previous('title', 'Submit An Article', 1);

$templating->load('submit_article');

// check the user has their required details setup before allowing article submission
if (!isset($user->user_details['author_picture']) || empty($user->user_details['author_picture']) || $user->user_details['author_picture'] == NULL)
{
    $templating->block('setup_profile');
    include(APP_ROOT . '/includes/footer.php');
	die();
}
if (!isset($user->user_details['article_bio']) || empty($user->user_details['article_bio']) || $user->user_details['article_bio'] == NULL)
{
    $templating->block('setup_profile');
    include(APP_ROOT . '/includes/footer.php');
	die();
}

require_once("includes/curl_data.php");

$captcha = 0;
if (!$user->can('skip_submit_article_captcha'))
{
	$captcha = 1;
}

$captcha_output = '';
if ($core->config('captcha_disabled') == 0 && $captcha == 1)
{
	$captcha_output = '<strong>You must do a captcha to help prevent spam.</strong><br />
	We use Google\'s reCAPTCHA, you must agree to their use of cookies to use it.
	<button id="accept_captcha" type="button" data-pub-key="'.$core->config('recaptcha_public').'">Accept & Show reCAPTCHA</button>';
}

if (isset($_GET['view']))
{
    if ($_GET['view'] == 'Submit')
    {
        // allow people to go back from say previewing (if they hit the back button) and not have some browsers wipe what they wrote
        // only do this if they haven't just logged in (to prevent the cache'd content showing guest boxes)
        if (isset($_SESSION['new_login']) && $_SESSION['new_login'] == 1)
        {
            $_SESSION['new_login'] = 0;
        }
        else
        {
            header_remove("Expires");
            header_remove("Cache-Control");
            header_remove("Pragma");
            header_remove("Last-Modified");
        }

        if (!isset($_GET['error']))
        {
            $_SESSION['image_rand'] = rand();
        }

        // if they have done it before set text and tagline
        $title = '';
        $text = '';

        if (isset($_SESSION['atitle']))
        {
            $title = $_SESSION['atitle'];
        }
        if (isset($_SESSION['atext']))
        {
            $text = $_SESSION['atext'];
        }

        $templating->block('submit', 'submit_article');
        $templating->set('url', $core->config('website_url'));
		$templating->set('title', $title);

        $tagline_pic = '';
        if (isset($_GET['error']) && isset($_SESSION) && isset($_SESSION['uploads_tagline']))
        {
            $tagline_pic = "<img src=\"/uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" class=\"imgList\"/>";
        }
        $templating->set('tagline_image', $tagline_pic);

        $templating->set('max_height', $core->config('article_image_max_height'));
        $templating->set('max_width', $core->config('article_image_max_width'));

        $subscribe_box = '';
        if ($_SESSION['user_id'] != 0)
        {
            $subscribe_box = '<label>Subscribe to article to receive comment replies via email <input type="checkbox" name="subscribe" checked /></label><br />';
        }

        $core->article_editor(['content' => $text]);

        // sort out previously uploaded images
        $previously_uploaded = '';
        $previously_uploaded = $article_class->display_previous_uploads();

        // the rest of the form stuff
        $templating->block('submit_bottom', 'submit_article');
        $templating->set('hidden_upload_fields', $previously_uploaded['hidden']);
        $templating->set('captcha', $captcha_output);
        $templating->set('subscribe_box', $subscribe_box);

        $templating->block('uploads', 'submit_article');
        $templating->set('previously_uploaded', $previously_uploaded['output']);
    }
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Submit')
	{
		$redirect = '/submit-article/';

		$title = trim($_POST['title']);
		$title = strip_tags($title);
		$title = mb_convert_encoding($title, 'UTF-8');
		$text = trim($_POST['text']);
		
		if (isset($_POST['uploads']))
		{
			$_SESSION['uploads']['article_media'] = $_POST['uploads'];
		}

		if ($core->config('captcha_disabled') == 0 && $captcha == 1)
		{
			if (isset($_POST['g-recaptcha-response']))
			{
                $recaptcha=$_POST['g-recaptcha-response'];
                $google_url="https://www.google.com/recaptcha/api/siteverify";
                $ip=core::$ip;
                $url=$google_url."?secret=".$core->config('recaptcha_secret')."&response=".$recaptcha."&remoteip=".$ip;
                $res=getCurlData($url);
                $res= json_decode($res, true);
			}
			else
			{
				$_SESSION['atitle'] = $title;
				$_SESSION['atext'] = $text;

				$_SESSION['message'] = 'captcha';
				header("Location: " . $redirect);
				die();
			}
		}

		if ($core->config('captcha_disabled') == 0 && $captcha == 1 && !$res['success'])
		{
			$_SESSION['atitle'] = $title;
			$_SESSION['atext'] = $text;

			$_SESSION['message'] = 'captcha';
			header("Location: " . $redirect);
			die();
		}

		if (!isset($_POST['spam_list']))
		{
			$_SESSION['atitle'] = $title;
			$_SESSION['atext'] = $text;

			$_SESSION['message'] = 'spam_check_agree';
			header("Location: ".$redirect);
			die();
		}

        $check_empty = core::mempty(compact('title', 'text'));

        if ($check_empty !== true)
        {
            $_SESSION['atitle'] = $title;
            $_SESSION['atext'] = $text;

			$_SESSION['message'] = 'empty';
            $_SESSION['message_extra'] = $check_empty;

            header("Location: " . $redirect);
            die();
        }

		// prevent ckeditor just giving us a blank article (this is the default for an empty editor)
		// this way if there's an issue and it gets wiped, we still don't get a blank article published
		if ($text == '<p>&nbsp;</p>')
		{
            $_SESSION['atitle'] = $title;
            $_SESSION['atext'] = $text;

			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'text';

            header("Location: " . $redirect);
            die();
		}

		$core->check_ip_from_stopforumspam(core::$ip);

        // carry on and submit the article
        if (($core->config('captcha_disabled') == 0 && $captcha == 1 && $res['success']) || $captcha == 0 || $core->config('captcha_disabled') == 1)
        {
			// make the slug
			$title_slug = core::nice_title($_POST['title']);

			// insert the article itself
			$dbl->run("INSERT INTO `articles` SET `author_id` = ?, `date` = ?, `date_submitted` = ?, `title` = ?, `slug` = ?, `text` = ?, `active` = 0, `submitted_article` = 1, `submitted_unapproved` = 1, `preview_code` = ?", array($_SESSION['user_id'], core::$date, core::$date, $title, $title_slug, $text, core::random_id()));

			$article_id = $dbl->new_id();

			if (isset($_POST['uploads']))
			{
				foreach($_POST['uploads'] as $key)
				{
					$dbl->run("UPDATE `article_images` SET `article_id` = ? WHERE `id` = ?", array($article_id, $key));
				}
			}

			$core->new_admin_note(array('content' => ' submitted a new article titled: <a href="/admin.php?module=articles&view=Submitted&aid='.$article_id.'">'.$title.'</a>.', 'type' => 'submitted_article', 'data' => $article_id));

			// check if they are subscribing
			if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
			{
				$article_class->subscribe($article_id, 1);
			}

			if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
			{
				$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name'], $text);
			}

			// get all the editor and admin emails apart from sinead
			$editor_emails = array();

			$subject = "GamingOnLinux article submission from {$_SESSION['username']}";

			$email_groups = $user->get_group_ids('article_submission_emails');

			$in = str_repeat('?,', count($email_groups) - 1) . '?';

			$grab_editors = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username` from `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN ($in) AND u.`submission_emails` = 1", $email_groups)->fetch_all();

			foreach ($grab_editors as $get_emails)
			{
				$submitted_link = $core->config('website_url') . "admin.php?module=articles&view=Submitted";
                // message
                $html_message = "<p>Hello {$get_emails['username']},</p>
                <p>A new article has been submitted that needs reviewing titled <a href=\"$submitted_link\"><strong>{$title}</strong></a> from {$_SESSION['username']}</p>
                <p><a href=\"$submitted_link\">Click here to review it</a>";

                $plain_message = PHP_EOL."Hello {$get_emails['username']}, A new article has been submitted that needs reviewing titled '<strong>{$title}</strong>' from {$_SESSION['username']}, go here to review:  $submitted_link";

				// Mail it
                if ($core->config('send_emails') == 1)
                {
					$mail = new mailer($core);
					$mail->sendMail($get_emails['email'], $subject, $html_message, $plain_message);
				}
			}

			$core->message('Thank you for submitting an article to us! The article has been sent to the admins for review before it is posted, please allow time for us to go over it properly. Keep an eye on your email in case we send it back to you with feedback. <a href="/submit-article/">Click here to post more</a> or <a href="/index.php">click here to go to the site home</a>.');

            unset($_SESSION['atitle']);
            unset($_SESSION['atext']);
            unset($_SESSION['image_rand']);
		}
	}
}
