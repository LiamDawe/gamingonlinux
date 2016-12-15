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

$templating->block('manage_top', 'admin_modules/giveaways');

$db->sqlquery("SELECT `id`, `game_name` FROM `game_giveaways` ORDER BY `date_created` DESC");
while ($current = $db->fetch())
{
	$templating->block('row', 'admin_modules/giveaways');
	$templating->set('name', $current['game_name']);
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
