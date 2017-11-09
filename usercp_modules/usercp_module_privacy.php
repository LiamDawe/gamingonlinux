<?php
$templating->set_previous('title', 'Privacy' . $templating->get('title', 1)  , 1);

$templating->load('usercp_modules/privacy');
$templating->block('main');

// extra details rarely needed, not included in normal user info grabbed when logged in
$get_details = $dbl->run("SELECT `global_search_visible`, `private_profile`, `get_pms` FROM `users` WHERE `user_id` = ?", [$_SESSION['user_id']])->fetch();

$search = '';
if ($get_details['global_search_visible'] == 1)
{
	$search = 'checked';
}

$pms = '';
if ($get_details['get_pms'] == 1)
{
	$pms = 'checked';
}

$private = '';
if ($get_details['private_profile'] == 1)
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
