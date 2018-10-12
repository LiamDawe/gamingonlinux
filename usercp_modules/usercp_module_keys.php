<?php
$templating->set_previous('title', 'Key Giveaways' . $templating->get('title', 1)  , 1);

$grab_keys = $dbl->run("SELECT k.`name`, k.`game_key`, g.`giveaway_name` FROM `game_giveaways_keys` k INNER JOIN `game_giveaways` g ON g.id = k.game_id WHERE k.`claimed_by_id` = ?", array($_SESSION['user_id']))->fetch_all();

$templating->load('usercp_modules/usercp_module_keys');
$templating->block('main');

foreach ($grab_keys as $key)
{
	$templating->block('row');
	$name = $key['giveaway_name']; // they always have a name
	if ($key['name'] != NULL) // however, we also bundle different games together in a giveaway, override it if that's true
	{
		$name = $key['name'];
	}
	$templating->set('name', $name);
	$templating->set('key', $key['game_key']);
}
?>
