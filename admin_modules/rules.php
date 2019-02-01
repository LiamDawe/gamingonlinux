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
		$templating->load('admin_modules/rules');

		$templating->block('main');
		$templating->set('form_url', $core->config('website_url'));

		$core->article_editor(['content' => $core->config('rules')]);

		$templating->block('bottom', 'admin_modules/config');
	}

	// We have been asked to edit the config
	else if (isset($_POST['Submit']))
	{
		$rules = trim($_POST['text']);
		// check empty
		if (empty($rules))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'rules text';
			header("Location: /admin.php?module=rules");
			die();
		}

		// do the update
		else
		{
			$core->set_config($rules, 'rules');

			$core->new_admin_note(array('completed' => 1, 'content' => ' edited the <a href="/index.php?module=rules">website rules</a>.'));

			$_SESSION['message'] = 'edited';
			$_SESSION['message_extra'] = 'site rules';
			header('Location: '.$core->config('website_url').'admin.php?module=rules');
		}
	}
}
?>
