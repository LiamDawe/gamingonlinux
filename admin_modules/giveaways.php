<?php
$templating->merge('admin_modules/giveaways');

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'added')
	{
		$core->message("That giveaway has been added!");
	}
}

$templating->block('giveaway_add', 'admin_modules/giveaways');

if (!isset($_GET['manage']))
{
	$templating->block('manage_top', 'admin_modules/giveaways');

	$db->sqlquery("SELECT `id`, `game_name` FROM `game_giveaways` ORDER BY `date_created` DESC");
	while ($current = $db->fetch())
	{
		$templating->block('row', 'admin_modules/giveaways');
		$templating->set('name', $current['game_name']);
		$templating->set('id', $current['id']);
	}
}
else if (isset($_GET['manage']))
{
	$db->sqlquery("SELECT `game_name` FROM `game_giveaways` WHERE `id` = ?", array($_GET['manage']));
	$giveaway = $db->fetch();
	$templating->block('key_list_top');
	$templating->set('name', $giveaway['game_name']);

	$db->sqlquery("SELECT SUM(CASE WHEN claimed = 1 AND game_id = ? THEN 1 ELSE 0 END) AS claimed,
  SUM(CASE WHEN game_id = ? THEN 1 ELSE 0 END) AS total
	FROM game_giveaways_keys", array($_GET['manage'], $_GET['manage']));
	$key_totals = $db->fetch();
	$templating->set('claimed', $key_totals['claimed']);
	$templating->set('total', $key_totals['total']);

	$db->sqlquery("SELECT k.`game_key`, k.`claimed`, u.`user_id`, u.`username` FROM `game_giveaways_keys` k LEFT JOIN `users` u ON u.`user_id` = k.`claimed_by_id` WHERE k.`game_id` = ? ORDER BY k.`claimed` DESC", array($_GET['manage']));
	while ($keys = $db->fetch())
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
		if (empty($_POST['list']))
		{
			$core->message('You must enter at least one key!');
		}

		else
		{
			$db->sqlquery("INSERT INTO `game_giveaways` SET `game_name` = ?, `date_created` = ?", array($_POST['name'], core::$date));
			$new_id = $db->grab_id();

			// get the keys asked for
			$list = preg_split('/(\\n|\\r)/', $_POST['list'], -1, PREG_SPLIT_NO_EMPTY);

			foreach ($list as $the_key)
			{
				// make the category
				$db->sqlquery("INSERT INTO `game_giveaways_keys` SET `game_id` = ?, `game_key` = ?", array($new_id, $the_key));
			}

			header("Location: /admin.php?module=giveaways&message=added");
		}
	}
}
?>
