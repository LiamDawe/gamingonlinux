<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

define("POPULAR_COUNTER_DEFAULT", 1000);

if (!$user->check_group(1))
{
	$core->message("You do not have permission to access this page!");
}

else
{
	if (!isset($_POST['Submit']))
	{
		$templating->load('admin_modules/config');

		$templating->block('main');
		$templating->set('form_url', $core->config('website_url'));

		$templating->set('contact_email', $core->config('contact_email'));
		$templating->set('mailer_email', $core->config('mailer_email'));

		// set the default module
		$templating->set('default_module', $core->config('default_module'));

		// are users allowed to register?
		$allow_registrations_check = '';
		if ($core->config('allow_registrations') == 1)
		{
			$allow_registrations_check = 'checked';
		}
		$templating->set('register_check', $allow_registrations_check);

		$templating->set('reg_message', $core->config('register_off_message'));

		// is there a captcha on register?
		$register_captcha_check = '';
		if ($core->config('register_captcha') == 1)
		{
			$register_captcha_check = 'checked';
		}
		$templating->set('register_captcha_check', $register_captcha_check);
		
		$rss_check = '';
		if ($core->config('articles_rss') == 1)
		{
			$rss_check = 'checked';
		}
		$templating->set('article_rss_check', $rss_check);
		
		$forum_rss_check = '';
		if ($core->config('forum_rss') == 1)
		{
			$forum_rss_check = 'checked';
		}
		$templating->set('forum_rss_check', $forum_rss_check);

		$templating->set('popular_counter', $core->config('hot-article-viewcount'));

		// debug mode on?
		$debug_check = '';
		if ($core->config('show_debug') == 1)
		{
			$debug_check = 'checked';
		}
		$templating->set('debug_check', $debug_check);

		$templating->set('url', $core->config('website_url'));
		
		// SOCIAL
		$templating->set('twitter', $core->config('twitter_username'));
		$templating->set('telegram_group', $core->config('telegram_group'));
		$templating->set('telegram_bot_key', $core->config('telegram_bot_key'));
		$templating->set('discord', $core->config('discord'));
		$templating->set('steam_group', $core->config('steam_group'));
		$templating->set('facebook_page', $core->config('facebook_page'));
		$templating->set('gplus_page', $core->config('gplus_page'));
		$templating->set('youtube_channel', $core->config('youtube_channel'));

		// THEMING
		$templating->set('template', $core->config('template'));

		$core->article_editor(['content' => $core->config('support_us_text')]);

		$templating->block('bottom', 'admin_modules/config');
	}

	// We have been asked to edit the config
	else if (isset($_POST['Submit']))
	{
		$allow_registrations = 0;
		if (isset($_POST['allow_registrations']))
		{
			$allow_registrations = 1;
		}

		$register_captcha = 0;
		if (isset($_POST['register_captcha']))
		{
			$register_captcha = 1;
		}
		
		$article_rss = 0;
		if (isset($_POST['article_rss']))
		{
			$article_rss = 1;
		}
		
		$forum_rss = 0;
		if (isset($_POST['forum_rss']))
		{
			$forum_rss = 1;
		}

		$debug = 0;
		if (isset($_POST['debug']))
		{
			$debug = 1;
		}

		$popular_counter = POPULAR_COUNTER_DEFAULT;
		if (isset($_POST['popular_counter']) && is_numeric($_POST['popular_counter']))
		{
			$popular_counter = $_POST['popular_counter'];
		}

		// check empty
		if (empty($_POST['template']) || empty($_POST['default_module']))
		{
			$core->message('You have to set a template and default module! <a href="admin.php?module=config">Go back</a>.');
		}

		// do the update
		else
		{
			$core->set_config($_POST['contact_email'], 'contact_email');

			$core->set_config($_POST['default_module'], 'default_module');

			$core->set_config($allow_registrations, 'allow_registrations');

			$core->set_config($_POST['reg_message'], 'register_off_message');

			$core->set_config($register_captcha, 'register_captcha');

			$core->set_config($_POST['url'], 'website_url');

			$core->set_config($popular_counter, 'hot-article-viewcount');

			$core->set_config($debug, 'show_debug');
			
			$core->set_config($article_rss, 'articles_rss');
			
			$core->set_config($forum_rss, 'forum_rss');
			
			$core->set_config($_POST['mailer_email'], 'mailer_email');
			
			// SOCIAL
			$core->set_config($_POST['twitter'], 'twitter_username');
			$core->set_config($_POST['telegram_group'], 'telegram_group');
			$core->set_config($_POST['telegram_bot_key'], 'telegram_bot_key');
			$core->set_config($_POST['discord'], 'discord');
			$core->set_config($_POST['steam_group'], 'steam_group');
			$core->set_config($_POST['facebook_page'], 'facebook_page');
			$core->set_config($_POST['gplus_page'], 'gplus_page');
			$core->set_config($_POST['youtube_channel'], 'youtube_channel');
			
			// THEMING
			$core->set_config($_POST['template'], 'template');

			$core->set_config($_POST['text'], 'support_us_text');

			// note who did it
			$core->new_admin_note(array('completed' => 1, 'content' => ' updated the website config.'));

			$_SESSION['message'] = 'edited';
			$_SESSION['message_extra'] = 'config';
			header('Location: '.$core->config('website_url').'admin.php?module=config');
		}
	}
}
?>
