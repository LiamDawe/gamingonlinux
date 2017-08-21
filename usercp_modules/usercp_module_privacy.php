<?php
$templating->load('usercp_modules/privacy');
$templating->block('main');

$check = $user->get(['global_search_visible', 'get_pms', 'private_profile'], $_SESSION['user_id']);

$search = '';
if ($check['global_search_visible'] == 1)
{
	$search = 'checked';
}

$pms = '';
if ($check['get_pms'] == 1)
{
	$pms = 'checked';
}

$private = '';
if ($check['private_profile'] == 1)
{
	$private = 'checked';
}

$templating->set_many(['search_check' => $search, 'pm_check' => $pms, 'private_check' => $private]);

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'update')
	{
		$search_check = 0;
		if (isset($_POST['user_search']))
		{
			$search_check = 1;
		}
		
		$pm_check = 0;
		if (isset($_POST['enable_pm']))
		{
			$pm_check = 1;
		}
		
		$private_check = 0;
		if (isset($_POST['private_profile']))
		{
			$private_check = 1;
		}
		
		$dbl->run("UPDATE `users` SET `global_search_visible` = ?, `get_pms` = ?, `private_profile` = ? WHERE `user_id` = ?", array($search_check, $pm_check, $private_check, $_SESSION['user_id']));
		
		$_SESSION['message'] = 'privacy_updated';
		header("Location: /usercp.php?module=privacy");
	}
}
?>
