<?php
$templating->merge('submit_article');

require_once("includes/curl_data.php");

if (isset($_GET['view']))
{
    if ($_GET['view'] == 'Submit')
    {
        // check for ipban (spammers) and don't let them submit
        $db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
        $get_ip = $db->fetch();
        if ($db->num_rows() > 0)
        {
            header('Location: /index.php');
            die();
        }

        $data = file_get_contents("http://api.stopforumspam.org/api?ip=" . core::$ip);
        if (strpos($data, "<appears>yes</appears>") !== false)
        {
            header('Location: /index.php?module=home&message=spam');
            die();
        }

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

        $templating->set_previous('meta_description', 'Submit an article to GamingOnLinux, news, reviews, interviews', 1);
        $templating->set_previous('title', 'Submit An Article', 1);

        if (!isset($_GET['error']))
        {
            $_SESSION['image_rand'] = rand();
        }

        if (isset ($_GET['error']))
        {
            if ($_GET['error'] == 'email')
            {
                $core->message('You have to fill in your email since you are not logged in!', NULL, 1);
            }

            else if ($_GET['error'] == 'empty')
            {
                $core->message('You have to fill in a title and text!', NULL, 1);
            }

            else if ($_GET['error'] == 'captcha')
            {
                $core->message("You need to complete the captcha to prove you are human and not a bot!", NULL, 1);
            }
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
        $templating->set('url', core::config('website_url'));
        $templating->set('guest_fields', $guest_fields);
        $templating->set('title', $title);

        $tagline_pic = '';
        if (isset($_GET['error']) && isset($_SESSION) && isset($_SESSION['uploads_tagline']))
        {
            $tagline_pic = "<img src=\"/uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" class=\"imgList\"/>";
        }
        $templating->set('tagline_image', $tagline_pic);

        $templating->set('max_height', core::config('article_image_max_height'));
        $templating->set('max_width', core::config('article_image_max_width'));

        $subscribe_box = '';
        if ($_SESSION['user_id'] != 0)
        {
            $subscribe_box = '<label>Subscribe to article to receive comment replies via email <input type="checkbox" name="subscribe" checked /></label><br />';

        }

        $captcha = '';
        if ($parray['article_comments_captcha'] == 1)
        {
            $captcha = '<strong>You do not have to do this captcha just to Preview!</strong><br /><div class="g-recaptcha" data-sitekey="6LcT0gATAAAAAOAGes2jwsVjkan3TZe5qZooyA-z"></div>';
        }

        $core->editor('text', $text, $article_editor = 0, $disabled = 0, $anchor_name = 'commentbox', $ays_ignore = 1);

        $templating->block('submit_bottom', 'submit_article');
        $templating->set('captcha', $captcha);
        $templating->set('subscribe_box', $subscribe_box);
    }
}

if (isset($_POST['act']))
{
    if ($_POST['act'] == 'Submit')
    {
        // check for ipban (spammers) and don't let them submit
        $db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
        $get_ip = $db->fetch();
        if ($db->num_rows() > 0)
        {
            header('Location: /index.php');
        }

        $data = file_get_contents("http://api.stopforumspam.org/api?ip=" . core::$ip);
        if (strpos($data, "<appears>yes</appears>") !== false)
        {
            header('Location: /index.php?module=home&message=spam');
            die();
        }

        $templating->set_previous('title', 'Submit An Article', 1);
        $title = strip_tags($_POST['title']);
        $title = $title;
        $text = htmlentities($_POST['text']);

        if ($_SESSION['user_id'] == 0 && empty($_POST['email']))
        {
            $_SESSION['atitle'] = $_POST['title'];
            $_SESSION['atext'] = $_POST['text'];
            $_SESSION['aname'] = $_POST['name'];
            $_SESSION['aemail'] = $_POST['email'];

            header("Location: /submit-article/error=email");
        }

        // make sure its not empty
        else if (empty($_POST['title']) || empty($_POST['text']))
        {
            $_SESSION['atitle'] = $_POST['title'];
            $_SESSION['atext'] = $_POST['text'];
            $_SESSION['aname'] = $_POST['name'];
            $_SESSION['aemail'] = $_POST['email'];

            header("Location: /submit-article/error=empty");
        }

        else
        {
            if ($parray['submit_article_captcha'] == 1)
            {
                $recaptcha=$_POST['g-recaptcha-response'];
                $google_url="https://www.google.com/recaptcha/api/siteverify";
                $secret='6LcT0gATAAAAAJrRJK0USGyFE4pFo-GdRTYcR-vg';
                $ip=core::$ip;
                $url=$google_url."?secret=".$secret."&response=".$recaptcha."&remoteip=".$ip;
                $res=getCurlData($url);
                $res= json_decode($res, true);
            }

            if ($parray['submit_article_captcha'] == 1 && !$res['success'])
            {
                $_SESSION['atitle'] = $_POST['title'];
                $_SESSION['atext'] = $_POST['text'];
                $_SESSION['aname'] = $_POST['name'];
                $_SESSION['aemail'] = $_POST['email'];

                header("Location: /submit-article/error=captcha");
            }

            // carry on and submit the article
            else if (($parray['submit_article_captcha'] == 1 && $res['success']) || $parray['submit_article_captcha'] == 0)
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
                    $guest_username = $_POST['name'];
                    $username = $_POST['name'];
                }

                else
                {
                    $username = $_SESSION['username'];
                }

                $guest_email = '';
                if (!empty($_POST['email']))
                {
                    $guest_email = $_POST['email'];
                }

                // make the slug
                $title_slug = $core->nice_title($_POST['title']);

                // insert the article itself
                $db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `guest_username` = ?, `guest_email` = ?, `guest_ip` = ?, `date` = ?, `date_submitted` = ?, `title` = ?, `slug` = ?, `text` = ?, `active` = 0, `submitted_article` = 1, `submitted_unapproved` = 1, `preview_code` = ?", array($_SESSION['user_id'], $guest_username, $guest_email, core::$ip, core::$date, core::$date, $title, $title_slug, $text, $core->random_id()));

                $article_id = $db->grab_id();

                $db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `article_id` = ?", array("A submitted article was sent for review.", core::$date, $article_id), 'articles.php');

                // check if they are subscribing
                if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
                {
                    $db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?", array($_SESSION['user_id'], $article_id));
                }

                if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
                {
                    $core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name']);
                }

                    $core->message('Article has been sent to the admins for review before it is posted! <a href="/submit-article/">Click here to post more</a> or <a href="/index.php">click here to go to the site home</a>.');

                    // get all the editor and admin emails apart from sinead
                    $editor_emails = array();
                    $db->sqlquery("SELECT `email` FROM `users` WHERE `user_group` IN (1,2) AND `user_id` != 431");
                    while ($get_emails = $db->fetch())
                    {
                        $editor_emails[] = $get_emails['email'];
                    }

                    $to = implode(', ', $editor_emails);

                    // subject
                    $subject = "GamingOnLinux.com article submission - {$username}";

                    // find what name to use

                    // message
                    $message = "
                    <html>
                    <head>
                    <title>GamingOnLinux.com article submission</title>
                    </head>
                    <body>
                    <img src=\"{$config['website_url']}{$config['path']}templates/default/images/icon.png\" alt=\"Gaming On Linux\">
                    <br />
                    <p>Hello admin,</p>
                    <p>A new article has been submitted that needs reviewing titled <a href=\"{$config['website_url']}{$config['path']}/admin.php?module=articles&view=Submitted\"><strong>{$title}</strong></a> from {$username}</p>
                    <p><a href=\"{$config['website_url']}{$config['path']}/admin.php?module=articles&view=Submitted\">Click here to review it</a>
                    </body>
                    </html>
                    ";

                    // To send HTML mail, the Content-type header must be set
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                    $headers .= "From: GOL Notification of article submission <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

                    // Mail it
                    if ($config['send_emails'] == 1)
                    {
                        @mail($to, $subject, $message, $headers);
                    }

                unset($_SESSION['atitle']);
                unset($_SESSION['atext']);
                unset($_SESSION['aname']);
                unset($_SESSION['aemail']);
                unset($_SESSION['image_rand']);
            }
        }
    }

    if ($_POST['act'] == 'Preview')
    {
        $templating->set_previous('meta_description', 'Previewing a submitted article to GamingOnLinux', 1);
        $templating->set_previous('title', 'Submit An Article Preview', 1);

        // make date human readable
        $date = $core->format_date(core::$date);

        // get the article row template
        $templating->block('preview_row');
        $templating->set('url',$config['website_url']);

        $templating->set('title', $_POST['title']);
        $templating->set('user_id', $_SESSION['user_id']);

        if ($_SESSION['user_id'] == 0)
        {
            $username = 'Guest';
            if (!empty($_POST['name']))
            {
                $username = $_POST['name'];
            }
        }

        else
        {
            $username = "<a href=\"/profiles/{$_SESSION['user_id']}\">" . $_SESSION['username'] . '</a>';
        }

        $templating->set('username', $username);

        $templating->set('date', $date);
        $templating->set('submitted_date', 'Submitted ' . $date);

        $text = htmlentities($_POST['text']);
        $templating->set('text_full', bbcode($text));
        $templating->set('article_link', '#');
        $templating->set('comment_count', '0');

        // setup guest fields again
        $guest_username = '';
        if (isset($_POST['name']))
        {
            $guest_username = $_POST['name'];
        }

        $guest_email = '';
        if (isset($_POST['email']))
        {
            $guest_email = $_POST['email'];
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
        $templating->set('url', core::config('website_url'));
        $templating->set('guest_fields', $guest_fields);
        $templating->set('title', $_POST['title']);

        $top_image = '';
        if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
        {
            $top_image = '<img src="'.core::config('website_url').'uploads/articles/tagline_images/temp/thumbnails/'.$_SESSION['uploads_tagline']['image_name'].'" alt="[articleimage]" class="imgList"><br />
            BBCode: <input type="text" class="form-control input-sm" value="[img]tagline-image[/img]" /><br />';
        }

        $tagline_bbcode = '';
        if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
        {
            $tagline_bbcode = '/temp/' . $_SESSION['uploads_tagline']['image_name'];
        }
        $templating->set('tagline_image', $top_image);

        $templating->set('max_height', core::config('article_image_max_height'));
        $templating->set('max_width', core::config('article_image_max_width'));

        $subscribe_box = '';
        if ($_SESSION['user_id'] != 0)
        {
            $subscribe_box = '<label>Subscribe to article to receive comment replies via email <input type="checkbox" name="subscribe" checked /></label><br />';

        }

        $captcha = '';
        if ($parray['article_comments_captcha'] == 1)
        {
            $captcha = '<strong>You do not have to do this captcha just to Preview!</strong><br /><div class="g-recaptcha" data-sitekey="6LcT0gATAAAAAOAGes2jwsVjkan3TZe5qZooyA-z"></div>';
        }

        $core->editor('text', $text);

        $templating->block('submit_bottom', 'submit_article');
        $templating->set('captcha', $captcha);
        $templating->set('subscribe_box', $subscribe_box);
    }
}
