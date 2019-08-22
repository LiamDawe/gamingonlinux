<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Wishlist' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/wishlist');

if (!isset($_GET['go']))
{
	$templating->block('main_top');

	$current = $dbl->run("SELECT c.name, w.wish_id FROM `user_wishlist` w INNER JOIN `calendar` c ON c.id = w.game_id WHERE w.user_id = ?", [$_SESSION['user_id']])->fetch_all();
	if ($current)
	{
		foreach ($current as $wish)
		{
			$templating->block('row');
			$templating->set('name', $wish['name']);
			$templating->set('wish_id', $wish['wish_id']);
		}
	}
	else
	{
		$core->message("You have nothing in your wishlist right now!");
	}
	$templating->block('bottom', 'usercp_modules/wishlist');
}
if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		if (!isset($_POST['game']) || !is_numeric($_POST['game']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message'] = 'game';
			header("Location: /usercp.php?module=wishlist");
			die();			
		}
		// check it doesn't exist
		$test = $dbl->run("SELECT `wish_id` FROM `user_wishlist` WHERE `game_id` = ? AND `user_id` = ?", [$_POST['game'], $_SESSION['user_id']])->fetch();

		if ($test)
		{
			$_SESSION['message'] = 'wish_exists';
			header("Location: /usercp.php?module=wishlist");
			die();
		}
		// add it
		else
		{
			$dbl->run("INSERT INTO `user_wishlist` SET `game_id` = ?, `user_id` = ?", [$_POST['game'], $_SESSION['user_id']]);
		}

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'wishlist entry';
		header("Location: /usercp.php?module=wishlist");
	}

	if ($_POST['act'] == 'delete')
	{
		if (!isset($_POST['wish_id']) || !is_numeric($_POST['wish_id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message'] = 'wishlist item';
			header("Location: /usercp.php?module=wishlist");
			die();			
		}

		// check it doesn't exist
		$dbl->run("DELETE FROM `user_wishlist` WHERE `wish_id` = ? AND `user_id` = ?", [$_POST['wish_id'], $_SESSION['user_id']])->fetch();

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'wishlist entry';
		header("Location: /usercp.php?module=wishlist");
	}
}
?>
