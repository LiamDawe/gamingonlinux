<?php
define("POPULAR_COUNTER_DEFAULT", 1000);

if ($_SESSION['user_group'] != 1)
{
	$core->message("You do not have permission to access this page!");
}

else
{
	if (!isset($_POST['Submit']))
	{
		$templating->merge('admin_modules/config');

		$templating->block('main');
		$templating->set('form_url', core::config('website_url'));

		$templating->set('contact_email', core::config('contact_email'));

		// set the current template
		$templating->set('template', core::config('template'));

		// set the default module
		$templating->set('default_module', core::config('default_module'));

		// are users allowed to register?
		$allow_registrations_check = '';
		if (core::config('allow_registrations') == 1)
		{
			$allow_registrations_check = 'checked';
		}
		$templating->set('register_check', $allow_registrations_check);

		$templating->set('reg_message', core::config('register_off_message'));

		// is there a captcha on register?
		$register_captcha_check = '';
		if (core::config('register_captcha') == 1)
		{
			$register_captcha_check = 'checked';
		}
		$templating->set('register_captcha_check', $register_captcha_check);

		$templating->set('popular_counter', core::config('hot-article-viewcount'));

		// debug mode on?
		$debug_check = '';
		if (core::config('show_debug') == 1)
		{
			$debug_check = 'checked';
		}
		$templating->set('debug_check', $debug_check);

		$templating->set('url', core::config('website_url'));
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

			$core->set_config($_POST['template'], 'template');

			$core->set_config($_POST['default_module'], 'default_module');

			$core->set_config($allow_registrations, 'allow_registrations');

			$core->set_config($_POST['reg_message'], 'register_off_message');

			$core->set_config($register_captcha, 'register_captcha');

			$core->set_config($_POST['url'], 'website_url');

			$core->set_config($popular_counter, 'hot-article-viewcount');

			$core->set_config($debug, 'show_debug');

			$_SESSION['message'] = 'edited';
			$_SESSION['message_extra'] = 'config';
			header("Location: /admin.php?module=config&message=done");
		}
	}
}
?>
