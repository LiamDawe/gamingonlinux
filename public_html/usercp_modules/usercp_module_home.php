<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
define('articles_per_page_max', 30);
define('per_page_max', 50); // comments and posts

$templating->set_previous('title', 'Home' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/usercp_module_home');

include('includes/profile_fields.php');

if (isset($_GET['blocktag']) && isset($_GET['tagid']) && is_numeric($_GET['tagid']))
{
	// check it's not blocked already
	$check = $dbl->run("SELECT `ref_id` FROM `user_tags_bar` WHERE `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $_GET['tagid']))->fetch();

	if (!$check)
	{
		$dbl->run("INSERT INTO `user_tags_bar` SET `user_id` = ?, `category_id` = ?", array($_SESSION['user_id'], $_GET['tagid']));
		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'set of excluded article tags';
		header("Location: " . $core->config('website_url') . "usercp.php");
	}
}

if (isset($_GET['unblocktag']) && isset($_GET['tagid']) && is_numeric($_GET['tagid']))
{
	// check it's actually blocked already
	$check = $dbl->run("SELECT `ref_id` FROM `user_tags_bar` WHERE `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $_GET['tagid']))->fetch();

	if ($check)
	{
		$dbl->run("DELETE FROM `user_tags_bar` WHERE  `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $_GET['tagid']));
		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'set of excluded article tags';
		header("Location: " . $core->config('website_url') . "usercp.php");
	}
}

if (!isset($_POST['act']))
{
	$db_grab_fields = '';
	foreach ($profile_fields as $field)
	{
		$db_grab_fields .= "{$field['db_field']},";
	}

	$usercpcp = $dbl->run("SELECT $db_grab_fields `article_bio`, `submission_emails`, `single_article_page`, `per-page`, `articles-per-page`, `twitter_username`, `theme`, `steam_id`, `steam_username`, `google_email`, `timezone`, `email_articles`, `mailing_list_key`, `social_stay_cookie`, `supporter_end_date`, `supporter_type` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
	
	// make sure they have a mailing_list_key
	// if they unsubscribe it's wiped, but if they stay subscribed/make a new sub = use new or existing key
	$mail_list_key = $usercpcp['mailing_list_key'];
	if (empty($usercpcp['mailing_list_key']) || $usercpcp['mailing_list_key'] = NULL)
	{
		$unsub_key = core::random_id();
		$mail_list_key = $unsub_key;

		$dbl->run("UPDATE `users` SET `mailing_list_key` = ? WHERE `user_id` = ?", array($unsub_key, $_SESSION['user_id']));
	}
	$templating->set('unsub_key', $mail_list_key);

	$templating->block('top', 'usercp_modules/usercp_module_home');

	if ($user->can('premium_features'))
	{
		$templating->block('premium', 'usercp_modules/usercp_module_home');

		$end_date = '';
		if ($user->check_group(6))
		{
			if ($usercpcp['supporter_end_date'] != NULL)
			{
				$end_date = '<p>Supporter status end date: '.$usercpcp['supporter_end_date'].'</p>';
			}
		}

		$forum_link = '';
		if ($usercpcp['supporter_type'] == 'patreon')
		{
			$forum_link = '<p><a href="https://www.gamingonlinux.com/forum/23">Patreon Supporter-only forum link</a></p>';
		}

		$templating->set('forum_link', $forum_link);
		$templating->set('end_date_info', $end_date);
	}
	else
	{
		$templating->block('no_premium', 'usercp_modules/usercp_module_home');
	}

	$templating->block('main', 'usercp_modules/usercp_module_home');
	$templating->set('url', $core->config('website_url'));

	/* for content preferences */
	// grab a list of tags they don't want on the homepage
	$tags_list = '';
	$user_tag_bars = $dbl->run("SELECT ut.`category_id`, c.`category_name` FROM `user_tags_bar` ut LEFT JOIN `articles_categorys` c ON ut.category_id = c.category_id WHERE ut.`user_id` = ?", array($_SESSION['user_id']))->fetch_all();
	foreach ($user_tag_bars as $tags)
	{
		$tags_list .= "<option value=\"{$tags['category_id']}\" selected>{$tags['category_name']}</option>";
	}
	$templating->set('tags_list', $tags_list);

	$theme_options = '';
	$theme_list = ['', 'light', 'dark'];

	foreach ($theme_list as $theme)
	{
		$selected = '';
		if ($usercpcp['theme'] == $theme)
		{
			$selected = 'selected';
		}
		
		$theme_options .= '<option value="'.$theme.'" '.$selected.'>'.$theme.'</option>';
	}

	$templating->set('theme_options', $theme_options);
	
	$templating->set('timezone_list', core::timezone_list($usercpcp['timezone']));

	$profile_fields_output = '';

	foreach ($profile_fields as $field)
	{
		$usercpcp[$field['db_field']] = htmlspecialchars($usercpcp[$field['db_field']]);

		$url = '';
		if ($field['base_link_required'] == 1)
		{
			$url = $field['base_link'];
		}

		$span = '';
		if (isset($field['span']))
		{
			$span = $field['span'];
		}

		$description = '';
		if (isset($field['description']))
		{
			$description = ' - ' . $field['description'];
		}

		$form_input = "";
		$preinput = 0;
		if (isset($field['preinput']) && $field['preinput'] != NULL)
		{
			$preinput = 1;
			$form_input  .= '<div class="input-field"><span class="addon">'.$field['preinput'].'</span>';
		}
		else
		{
			$form_input .= "<div style=\"display:inline;\">";
		}
		$form_input .= "<input id=\"{$field['db_field']}_field\" type=\"text\" name=\"{$field['db_field']}\" value=\"{$usercpcp[$field['db_field']]}\" />";
		$form_input .= "</div>";

		$profile_fields_output .= "<label for=\"{$field['name']}\"> $span {$field['name']} $form_input <small>$description</small></label><br />";
	}

	$templating->set('profile_fields', $profile_fields_output);

	$submission_emails = '';
	if ($user->check_group([1,2,5]) == true)
	{
		$submission_emails_check = '';
		if ($usercpcp['submission_emails'] == 1)
		{
			$submission_emails_check = 'checked';
		}
		$submission_emails = "Get article submission emails? <input type=\"checkbox\" name=\"submission_emails\" $submission_emails_check /><br />";
	}
	$templating->set('submission_emails', $submission_emails);
	
	$daily_article_emails = '';
	if ($usercpcp['email_articles'] == 'daily')
	{
		$daily_article_emails = 'checked';
	}
	$templating->set('daily_check', $daily_article_emails);

	$single_article_yes = '';
	if ($usercpcp['single_article_page'] == 1)
	{
		$single_article_yes = 'selected';
	}
	$templating->set('single_article_yes', $single_article_yes);

	$single_article_no = '';
	if ($usercpcp['single_article_page'] == 0)
	{
		$single_article_no = 'selected';
	}
	$templating->set('single_article_no', $single_article_no);

	$templating->set('bio', $usercpcp['article_bio']);

	$page_options = '';
	$per_page_selected = '';
	for ($i = 10; $i <= per_page_max; $i += 5)
	{
		if ($i == $usercpcp['per-page'])
		{
			$per_page_selected = 'selected';
		}
		$page_options .= '<option value="'.$i.'" '.$per_page_selected.'>'.$i.'</a>';
		$per_page_selected = '';
	}
	$templating->set('per-page', $page_options);

	$apage_options = '';
	$aper_page_selected = '';
	for ($i = 15; $i <= articles_per_page_max; $i += 5)
	{
		if ($i == $usercpcp['articles-per-page'])
		{
			$aper_page_selected = 'selected';
		}
		$apage_options .= '<option value="'.$i.'" '.$aper_page_selected.'>'.$i.'</a>';
		$aper_page_selected = '';
	}
	$templating->set('aper-page', $apage_options);

	/* social logins */
	$stay_cookie = '';
	if ($usercpcp['social_stay_cookie'])
	{
		$stay_cookie = 'checked';
	}
	$templating->set('social_stay_check', $stay_cookie);
	
	$twitter_button = '';
	if ($core->config('twitter_login') == 1)
	{
		if (!empty($usercpcp['twitter_username']))
		{
			$twitter_button = '<div class="box"><div class="body group"><form method="post" action="'.$core->config('website_url').'usercp.php?module=home">
			Current twitter handle linked: @'.$usercpcp['twitter_username'].'<br />
			<button type="submit">Remove linked Twitter account</button>
			<input type="hidden" name="act" value="twitter_remove" />
			</form></div></div>';
		}

		else
		{
			$twitter_button = '<div class="box"><div class="body group"><a href="'.$core->config('website_url').'index.php?module=login&twitter" class="btn-auth btn-twitter"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/twitter.png" /> </span>Sign in with <b>Twitter</b></a></div></div>';
		}
	}
	$templating->set('twitter_button', $twitter_button);

	$steam_button = '';
	if ($core->config('steam_login') == 1)
	{
		if (!empty($usercpcp['steam_username']))
		{
			$steam_button = '<div class="box"><div class="body group"><form method="post" action="'.$core->config('website_url').'usercp.php?module=home">
			Current Steam user linked: '.$usercpcp['steam_username'].'<br />
			If this username is old it doesn\'t matter!<br />
			<button type="submit" class="btn btn-danger">Remove a linked Steam account</button>
			<input type="hidden" name="act" value="steam_remove" />
			</form></div></div>';
		}

		else
		{
			$steam_button = '<div class="box"><div class="body group"><a href="'.$core->config('website_url').'index.php?module=login&steam" class="btn-auth btn-steam"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/steam.png" /> </span>Sign in with <b>Steam</b></a></div></div>';
		}
	}
	$templating->set('steam_button', $steam_button);
	
	$google_button = '';
	if ($core->config('google_login') == 1)
	{
		if (!empty($usercpcp['google_email']))
		{
			$google_button = '<div class="box"><div class="body group"><form method="post" action="'.$core->config('website_url').'usercp.php?module=home">
			Current Google Email linked: '.$usercpcp['google_email'].'<br />
			<button type="submit" class="btn btn-danger">Remove a linked Google account</button>
			<input type="hidden" name="act" value="google_remove" />
			</form></div></div>';
		}

		else
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
				
			$google_button = '<div class="box"><div class="body group"><a href="'.$authUrl.'" class="btn-auth btn-google"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/google.svg" /> </span>Link your <b>Google</b> account</a></div></div>';
				
		}
	}
	$templating->set('google_button', $google_button);
}

else if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Update')
	{
		$per_page = 10;
		if (is_numeric($_POST['per-page']))
		{
			if ($_POST['per-page'] > per_page_max)
			{
				$per_page = per_page_max;
			}
			else
			{
				$per_page = $_POST['per-page'];
			}
		}

		$aper_page = 15;
		if (is_numeric($_POST['articles-per-page']))
		{
			if ($_POST['articles-per-page'] > articles_per_page_max)
			{
				$aper_page = articles_per_page_max;
			}
			else
			{
				$aper_page = $_POST['articles-per-page'];
			}
		}

		$single_article_page = 0;
		if ($_POST['single_article_page'] == 1 || $_POST['single_article_page'] == 0)
		{
			$single_article_page = $_POST['single_article_page'];
		}

		$submission_emails = 0;
		if ($user->check_group([1,2,5]) == true)
		{
			if (isset($_POST['submission_emails']))
			{
				$submission_emails = 1;
			}
		}
		
		$daily_articles = NULL;
		$mailing_list_key = NULL;
		if (isset($_POST['daily_news']))
		{
			$daily_articles = 'daily';
			$mailing_list_key = $_POST['mailing_list_key'];
		}

		$bio = core::make_safe($_POST['bio'], ENT_QUOTES);

		$dbl->run("UPDATE `users` SET `email_articles` = ?, `submission_emails` = ?, `single_article_page` = ?, `articles-per-page` = ?, `per-page` = ?, `article_bio` = ?, `timezone` = ?, `theme` = ? WHERE `user_id` = ?", array($daily_articles, $submission_emails, $single_article_page, $aper_page, $per_page, $bio, $_POST['timezone'], $_POST['theme'], $_SESSION['user_id']));

		$_SESSION['per-page'] = $per_page;
		$_SESSION['articles-per-page'] = $aper_page;
		
		$db_grab_fields = '';
		foreach ($profile_fields as $key => $field)
		{
			// tell them to do it properly
			if ($field['db_field'] == 'youtube' && (!empty($_POST['youtube']) && strpos($_POST['youtube'], "youtube.com") === false))
			{
				$_SESSION['message'] = 'youtube-missing';
				header("Location: " . $core->config('website_url') . "usercp.php?module=home");
				die();
			}

			// tell them to do it properly
			if ($field['db_field'] == 'twitch' && (!empty($_POST['twitch']) && strpos($_POST['twitch'], "twitch.tv") === false))
			{
				$_SESSION['message'] = 'twitch-missing';
				header("Location: " . $core->config('website_url') . "usercp.php?module=home");
				die();
			}

			if ($field['plain_link'] == 1 && !empty($_POST[$key]) && (strpos($_POST[$key], 'https://') === false && strpos($_POST[$key], 'http://') === false))
			{
				$_SESSION['message'] = 'broken_link';
				$_SESSION['message_extra'] = $key;
				header("Location: " . $core->config('website_url') . "usercp.php?module=home");
				die();
			}

			// make sure the fields can't be just the basic url for broken junk links
			if ($field['db_field'] == 'steam' && ($_POST['steam'] != 'http://steamcommunity.com/id/' || $_POST['steam'] != 'https://steamcommunity.com/id/'))
			{
				$dbl->run("UPDATE `users` SET `{$field['db_field']}` = '' WHERE `user_id` = ?", array($_SESSION['user_id']));
			}
			else if ($field['db_field'] == 'twitch' && ($_POST['twitch'] != 'https://www.twitch.tv/' || $_POST['twitch'] != 'http://www.twitch.tv/'))
			{
				$dbl->run("UPDATE `users` SET `{$field['db_field']}` = '' WHERE `user_id` = ?", array($_SESSION['user_id']));
			}
			else
			{
				$sanatized = trim(strip_tags($_POST[$field['db_field']]));

				if (!empty($sanatized))
				{
					$check_link = $sanatized;
					if ($field['base_link_required'] == 1)
					{
						$check_link = $field['base_link'] . $sanatized;
					}

					// make doubly sure it's an actual URL
					if (filter_var($check_link, FILTER_VALIDATE_URL)) 
					{	
						$dbl->run("UPDATE `users` SET `{$field['db_field']}` = ? WHERE `user_id` = ?", array($sanatized, $_SESSION['user_id']));
					}	
				}
				else
				{
					$dbl->run("UPDATE `users` SET `{$field['db_field']}` = NULL WHERE `user_id` = ?", array($_SESSION['user_id']));
				}
			}
		}

		$_SESSION['message'] = 'profile_updated';
		header("Location: " . $core->config('website_url') . "usercp.php?module=home");
	}

	if ($_POST['act'] == 'twitter_remove')
	{
		$dbl->run("UPDATE `users` SET `twitter_username` = ?, `oauth_uid` = ?, `oauth_provider` = ? WHERE `user_id` = ?", array('', '', '', $_SESSION['user_id']));

		header("Location: " . $core->config('website_url') . "usercp.php");
	}

	if ($_POST['act'] == 'steam_remove')
	{
		$dbl->run("UPDATE `users` SET `steam_username` = ?, `steam_id` = ? WHERE `user_id` = ?", array('', '', $_SESSION['user_id']));

		header("Location: " . $core->config('website_url') . "usercp.php");
	}
	
	if ($_POST['act'] == 'google_remove')
	{
		$dbl->run("UPDATE `users` SET `google_email` = ? WHERE `user_id` = ?", array('', $_SESSION['user_id']));

		header("Location: " . $core->config('website_url') . "usercp.php");
	}

	if ($_POST['act'] == 'update_social_stay')
	{
		$stay_cookie = 0;
		if (isset($_POST['social_stay']))
		{
			$stay_cookie = 1;
		}

		$dbl->run("UPDATE `users` SET `social_stay_cookie` = ? WHERE `user_id` = ?", array($stay_cookie, $_SESSION['user_id']));

		header("Location: " . $core->config('website_url') . "usercp.php");
	}

	if ($_POST['act'] == 'bar_tags')
	{
		if (isset($_POST['bar_tags']) && !empty($_POST['bar_tags']))
		{
			// delete any existing categories that aren't in the final list for publishing
			$user_tag_bars = $dbl->run("SELECT ut.`ref_id`, ut.`category_id`, c.`category_name` FROM `user_tags_bar` ut LEFT JOIN `articles_categorys` c ON ut.category_id = c.category_id WHERE ut.`user_id` = ?", array($_SESSION['user_id']))->fetch_all();

			if (!empty($user_tag_bars))
			{
				foreach ($user_tag_bars as $tag)
				{
					if (!in_array($tag['category_id'], $_POST['bar_tags']))
					{
						$dbl->run("DELETE FROM `user_tags_bar` WHERE `ref_id` = ?", array($tag['ref_id']));
					}
				}
			}

			// get fresh list of categories, and insert any that don't exist
			$current_tags = $dbl->run("SELECT `category_id` FROM `user_tags_bar` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN, 0);

			foreach($_POST['bar_tags'] as $new_tag)
			{
				if (!in_array($new_tag, $current_tags))
				{
					$dbl->run("INSERT INTO `user_tags_bar` SET `user_id` = ?, `category_id` = ?", array($_SESSION['user_id'], $new_tag));
				}
			}
		}
		if ((isset($_POST['bar_tags']) && empty($_POST['bar_tags']) || !isset($_POST['bar_tags'])))
		{
			$dbl->run("DELETE FROM `user_tags_bar` WHERE `user_id` = ?", array($_SESSION['user_id']));
		}

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'set of excluded article tags';
		header("Location: " . $core->config('website_url') . "usercp.php");
	}
}
?>
