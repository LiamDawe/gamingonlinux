<?php
if ($_SESSION['user_group'] != 1)
{
	$core->message("You do not have permission to access this page!");
}

else
{
	if (!isset($_POST['Submit']))
	{
		$templating->merge('admin_modules/admin_module_config');
	
		$templating->block('main');
		$templating->set('form_url', $config['path']);
	
		// get current config
		$sql_config = $db->sqlquery("SELECT * FROM `config`");
		$config = array();
	
		$fetch_config = $db->fetch_all_rows();
		foreach ($fetch_config as $config_set)
		{
			$config[$config_set['data_key']] = $config_set['data_value'];
		}

		$templating->set('contact_email', $config['contact_email']);

		// set the current template
		$templating->set('template', $config['template']);

		// set the default module
		$templating->set('default_module', $config['default_module']);

		// are users allowed to register?
		$allow_registrations_check = '';
		if ($config['allow_registrations'] == 1)
		{
			$allow_registrations_check = 'checked';
		}
		$templating->set('register_check', $allow_registrations_check);
	
		$templating->set('reg_message', $config['register_off_message']);

		// is there a captcha on register?
		$register_captcha_check = '';
		if ($config['register_captcha'] == 1)
		{
			$register_captcha_check = 'checked';
		}
		$templating->set('register_captcha_check', $register_captcha_check);
	
		$templating->set('url', $config['website_url']);
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
		
			$core->message('Config updated! <a href="admin.php?module=config">Go back</a>.');
		}
	}
}
?>
