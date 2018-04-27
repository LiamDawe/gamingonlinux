<?php
// check for ipban (spammers) and don't let them submit
$get_ip = $dbl->run("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip))->fetchOne();
if ($get_ip)
{
	$_SESSION['message'] = 'spam';
	header('Location: /index.php?module=home');
	die();
}
$core->check_ip_from_stopforumspam(core::$ip);

$templating->load('submit_article');

require_once("includes/curl_data.php");

$captcha = 0;
if (!$user->can('skip_submit_article_captcha'))
{
	$captcha = 1;
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

        $templating->set_previous('meta_description', 'Submit an article to GamingOnLinux', 1);
        $templating->set_previous('title', 'Submit An Article', 1);

        if (!isset($_GET['error']))
        {
            $_SESSION['image_rand'] = rand();
        }

        // if they have done it before set guest name and email
        $guest_username = '';
        if (isset($_SESSION['aname']))
        {
            $guest_username = $_SESSION['aname'];
        }

        $guest_email = '';
        if (isset($_SESSION['aemail']))
        {
            $guest_email = $_SESSION['aemail'];
        }

        $guest_fields = '';
        if ($_SESSION['user_id'] == 0)
        {
            $guest_fields = $templating->block_store('guest_fields', 'submit_article');
            $guest_fields = $templating->store_replace($guest_fields, array('guest_username' => $guest_username, 'guest_email' => $guest_email));
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
        $templating->set('guest_fields', $guest_fields);
		$templating->set('title', $title);
		
		$email_link = '/index.php?module=email_us';
		if ($core->config('pretty_urls') == 1)
		{
			$email_link = '/email-us/';
		}
		$templating->set('email_link', $email_link);

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

        $captcha_output = '';
        if ($core->config('captcha_disabled') == 0 && $captcha == 1)
        {
            $captcha_output = '<strong>You do not have to do this captcha just to Preview!</strong><br /><div class="g-recaptcha" data-sitekey="'.$core->config('recaptcha_public').'"></div>';
        }

        $core->article_editor(['content' => '']);

        $templating->block('submit_bottom', 'submit_article');
        $templating->set('captcha', $captcha_output);
        $templating->set('subscribe_box', $subscribe_box);
    }
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Submit')
	{
        $templating->set_previous('title', 'Submit An Article', 1);
        
		$title = strip_tags($_POST['title']);
		$title = mb_convert_encoding($title, 'UTF-8');
        $text = trim($_POST['text']);
        
        $name = '';
        if (isset($_POST['name']))
        {
			$name = core::make_safe($_POST['name']);
        }
        
        $guest_email = '';
        if (isset($_POST['email']))
        {
			$guest_email = core::make_safe($_POST['email']);
        }
        
		if ($core->config('pretty_urls') == 1)
		{
			$redirect = '/submit-article/';
		}
		else
		{
			$redirect = '/index.php?module=submit_article&view=Submit&';
		}

        if ($_SESSION['user_id'] == 0 && empty($_POST['email']))
        {
            $_SESSION['atitle'] = $title;
            $_SESSION['atext'] = $text;
            $_SESSION['aname'] = $name;
            $_SESSION['aemail'] = $guest_email;
            
            $_SESSION['message'] = 'empty';
            $_SESSION['message_extra'] = 'email address';
            
            header("Location: " . $redirect . "&error");
            die();
        }
        
        $check_empty = core::mempty(compact('title', 'text'));
        
        if ($check_empty !== true)
        {
            $_SESSION['atitle'] = $title;
            $_SESSION['atext'] = $text;
            $_SESSION['aname'] = $name;
            $_SESSION['aemail'] = $guest_email;
            
			$_SESSION['message'] = 'empty';
            $_SESSION['message_extra'] = $check_empty;

            header("Location: " . $redirect . '&error');
            die();
        }
        
		// prevent ckeditor just giving us a blank article (this is the default for an empty editor)
		// this way if there's an issue and it gets wiped, we still don't get a blank article published
		if ($text == '<p>&nbsp;</p>')
		{
            $_SESSION['atitle'] = $title;
            $_SESSION['atext'] = $text;
            $_SESSION['aname'] = $name;
            $_SESSION['aemail'] = $guest_email;

			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'text';	
			
            header("Location: " . $redirect . '&error');
            die();
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
				$_SESSION['message'] = 'captcha';
				header("Location: " . $redirect . "error");
				die();
			}
		}

		if ($core->config('captcha_disabled') == 0 && $captcha == 1 && !$res['success'])
		{
			$_SESSION['atitle'] = $title;
			$_SESSION['atext'] = $text;
			$_SESSION['aname'] = $name;
			$_SESSION['aemail'] = $guest_email;
			
			$_SESSION['message'] = 'captcha';
			header("Location: " . $redirect . "&error");
			die();
		}

        // carry on and submit the article
        if (($core->config('captcha_disabled') == 0 && $captcha == 1 && $res['success']) || $captcha == 0 || $core->config('captcha_disabled') == 1)
        {
            // setup category if empty
            if (empty($_POST['category']) || !is_numeric($_POST['category']))
            {
                $category_sql = 0;
            }

            else
            {
                $category_sql = $_POST['category'];
            }

            $guest_username = '';
            if (!empty($_POST['name']))
            {
                $guest_username = core::make_safe($_POST['name']);
                $username = core::make_safe($_POST['name']);
            }

            else
            {
                $username = $_SESSION['username'];
            }

			// make the slug
			$title_slug = core::nice_title($_POST['title']);

			// insert the article itself
			$dbl->run("INSERT INTO `articles` SET `author_id` = ?, `guest_username` = ?, `guest_email` = ?, `guest_ip` = ?, `date` = ?, `date_submitted` = ?, `title` = ?, `slug` = ?, `text` = ?, `active` = 0, `submitted_article` = 1, `submitted_unapproved` = 1, `preview_code` = ?", array($_SESSION['user_id'], $guest_username, $guest_email, core::$ip, core::$date, core::$date, $title, $title_slug, $text, core::random_id()));

			$article_id = $dbl->new_id();

			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'submitted_article', core::$date, $article_id));

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

			$subject = "GamingOnLinux article submission from {$username}";
                
			$email_groups = $user->get_group_ids('article_submission_emails');
                
			$in = str_repeat('?,', count($email_groups) - 1) . '?';
			
			$grab_editors = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username` from `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN ($in) AND u.`submission_emails` = 1", $email_groups)->fetch_all();

			foreach ($grab_editors as $get_emails)
			{
				//print_r($get_emails);
				$submitted_link = $core->config('website_url') . "admin.php?module=articles&view=Submitted";
                // message
                $html_message = "<p>Hello {$get_emails['username']},</p>
                <p>A new article has been submitted that needs reviewing titled <a href=\"$submitted_link\"><strong>{$title}</strong></a> from {$username}</p>
                <p><a href=\"$submitted_link\">Click here to review it</a>";

                $plain_message = PHP_EOL."Hello {$get_emails['username']}, A new article has been submitted that needs reviewing titled '<strong>{$title}</strong>' from {$username}, go here to review:  $submitted_link";

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
            unset($_SESSION['aname']);
            unset($_SESSION['aemail']);
            unset($_SESSION['image_rand']);
		}
	}

    if ($_POST['act'] == 'Preview')
    {
        $templating->set_previous('meta_description', 'Previewing a submitted article to GamingOnLinux', 1);
        $templating->set_previous('title', 'Submit An Article Preview', 1);

        // make date human readable
        $date = $core->human_date(core::$date);

        // get the article row template
        $templating->block('preview_row');
        $templating->set('url',$core->config('website_url'));

        $templating->set('title', strip_tags($_POST['title']));
        $templating->set('user_id', $_SESSION['user_id']);

        if ($_SESSION['user_id'] == 0)
        {
            $username = 'Guest';
            if (!empty($_POST['name']))
            {
                $username = core::make_safe($_POST['name']);
            }
        }

        else
        {
            $username = "<a href=\"/profiles/{$_SESSION['user_id']}\">" . $_SESSION['username'] . '</a>';
        }

        $templating->set('username', $username);

        $templating->set('date', $date);
        $templating->set('submitted_date', 'Submitted ' . $date);

        $templating->set('text_full', $_POST['text']);
        $templating->set('article_link', '#');
        $templating->set('comment_count', '0');

        // setup guest fields again
        $guest_username = '';
        if (isset($_POST['name']))
        {
            $guest_username = $core->make_safe($_POST['name']);
        }

        $guest_email = '';
        if (isset($_POST['email']))
        {
            $guest_email = $core->make_safe($_POST['email']);
        }

        $guest_fields = '';
        if ($_SESSION['user_id'] == 0)
        {
            $guest_fields = "Your Name: <em>Not Required, you will be called \"Guest\" if you leave it blank.</em><br />
            <input type=\"text\" name=\"name\" value=\"{$guest_username}\" /><br />
            Your Email: <em><strong>Required</strong>, will not be published, so we can email you when it's denied/accepted!</em><br />
            <input type=\"text\" name=\"email\" value=\"{$guest_email}\" /><br />";
        }

        $templating->block('submit', 'submit_article');
        $templating->set('url', $core->config('website_url'));
        $templating->set('guest_fields', $guest_fields);
        $templating->set('title', $core->make_safe($_POST['title']));

        $top_image = '';
        if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
        {
            $top_image = '<img src="'.$core->config('website_url').'uploads/articles/tagline_images/temp/thumbnails/'.$_SESSION['uploads_tagline']['image_name'].'" alt="[articleimage]" class="imgList"><br />
            BBCode: <input type="text" class="form-control input-sm" value="[img]tagline-image[/img]" /><br />';
        }

        $tagline_bbcode = '';
        if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
        {
            $tagline_bbcode = '/temp/' . $_SESSION['uploads_tagline']['image_name'];
        }
        $templating->set('tagline_image', $top_image);

        $templating->set('max_height', $core->config('article_image_max_height'));
        $templating->set('max_width', $core->config('article_image_max_width'));

        $subscribe_box = '';
        if ($_SESSION['user_id'] != 0)
        {
            $subscribe_box = '<label>Subscribe to article to receive comment replies via email <input type="checkbox" name="subscribe" checked /></label><br />';

        }

        $captcha_output = '';
        if ($captcha == 1)
        {
            $captcha_output = '<strong>You do not have to do this captcha just to Preview!</strong><br /><div class="g-recaptcha" data-sitekey="'.$core->config('recaptcha_public').'"></div>';
        }

        $core->article_editor(['content' => $_POST['text']]);

        $templating->block('submit_bottom', 'submit_article');
        $templating->set('captcha', $captcha_output);
        $templating->set('subscribe_box', $subscribe_box);
    }
}
