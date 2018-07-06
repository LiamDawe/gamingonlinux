<?php
$templating->load('admin_modules/giveaways');

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'added')
	{
		$core->message("That giveaway has been added!");
	}
}

if (!isset($_GET['manage']))
{
	$templating->block('giveaway_add', 'admin_modules/giveaways');

	$templating->block('manage_top', 'admin_modules/giveaways');

	$current_res = $dbl->run("SELECT `id`, `game_name` FROM `game_giveaways` ORDER BY `date_created` DESC")->fetch_all();
	foreach ($current_res as $current)
	{
		$templating->block('row', 'admin_modules/giveaways');
		$templating->set('name', $current['game_name']);
		$templating->set('id', $current['id']);
	}
}
else if (isset($_GET['manage']))
{
	$giveaway = $dbl->run("SELECT `game_name` FROM `game_giveaways` WHERE `id` = ?", array($_GET['manage']))->fetch();

	$templating->block('breadcrumb');
	$templating->set('name', $giveaway['game_name']);
	$templating->block('key_list_top');
	$templating->set('name', $giveaway['game_name']);

	$key_totals = $dbl->run("SELECT SUM(CASE WHEN claimed = 1 AND game_id = ? THEN 1 ELSE 0 END) AS claimed, SUM(CASE WHEN game_id = ? THEN 1 ELSE 0 END) AS total FROM game_giveaways_keys", array($_GET['manage'], $_GET['manage']))->fetch();

	$templating->set('claimed', $key_totals['claimed']);
	$templating->set('total', $key_totals['total']);
	$templating->set('id', $_GET['manage']);

	$keys_res = $dbl->run("SELECT k.`game_key`, k.`claimed`, u.`user_id`, u.`username` FROM `game_giveaways_keys` k LEFT JOIN `users` u ON u.`user_id` = k.`claimed_by_id` WHERE k.`game_id` = ? ORDER BY k.`claimed` DESC", array($_GET['manage']))->fetch_all();
	foreach ($keys_res as $keys)
	{
		$templating->block('key_row');
		$templating->set('key', $keys['game_key']);

		$profile_link = '';
		if ($keys['user_id'] != NULL && $keys['user_id'] != 0)
		{
			$profile_link = ' - Claimed by: <a href="/profiles/'.$keys['user_id'].'">' . $keys['username'] . '</a>';
		}
		$templating->set('profile_link', $profile_link);
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		$name = trim($_POST['name']);
		$list = $_POST['list'];
		$check_empty = core::mempty(compact('name', 'list'));
		if ($check_empty !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: /admin.php?module=giveaways");
			die();
		}

		else
		{
			$dbl->run("INSERT INTO `game_giveaways` SET `game_name` = ?, `date_created` = ?", array($_POST['name'], core::$date));
			$new_id = $dbl->new_id();

			// get the keys asked for
			$list = preg_split('/(\\n|\\r)/', $_POST['list'], -1, PREG_SPLIT_NO_EMPTY);

			foreach ($list as $the_key)
			{
				// make the category
				$dbl->run("INSERT INTO `game_giveaways_keys` SET `game_id` = ?, `game_key` = ?", array($new_id, $the_key));
			}

			// notify editors you did this
			$core->new_admin_note(array('completed' => 1, 'content' => ' added a new key giveaway for: ' . $name . '.'));

			$_SESSION['message'] = 'saved';
			$_SESSION['message_extra'] = 'giveaway';
			header("Location: /admin.php?module=giveaways");
			die();
		}
	}
	if ($_POST['act'] == 'add_more')
	{
		if (empty($_POST['list']))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'key list';
			header("Location: /admin.php?module=giveaways&manage=".$_POST['id']);
			die();
		}
		// get the keys asked for
		$list = preg_split('/(\\n|\\r)/', $_POST['list'], -1, PREG_SPLIT_NO_EMPTY);

		foreach ($list as $the_key)
		{
			// make the category
			$dbl->run("INSERT INTO `game_giveaways_keys` SET `game_id` = ?, `game_key` = ?", array($_POST['id'], $the_key));
		}

		$name = $dbl->run("SELECT `game_name` FROM `game_giveaways` WHERE `id` = ?", array($_POST['id']))->fetchOne();

		// notify editors you did this
		$core->new_admin_note(array('completed' => 1, 'content' => ' added more keys to the giveaway for: ' . $name . '.'));

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'key list';
		header("Location: /admin.php?module=giveaways&manage=".$_POST['id']);
	}
}
?>
