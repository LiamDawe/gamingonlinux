<?php
$templating->set_previous('meta_description', 'Linux games database search', 1);
$templating->set_previous('title', 'GamingOnLinux Linux games database search', 1);

$templating->merge('game-search');
$templating->block('search_bread', 'game-search');

$search = '';

if (isset($_GET['q']))
{
	$search = trim($_GET['q']);
	$search = str_replace('+', '', $search);
	$search = strip_tags($search);

	if (empty($search))
	{
		header("Location: /index.php?module=calendar&error=emptysearch");
		die();
	}
}

$templating->block('search', 'game-search');
$templating->set('search_text', $search);

if (isset($_GET['q']))
{
	$templating->block('search_result_top');

	$db->sqlquery("SELECT `id`, `name`, `best_guess`, `is_dlc` FROM `calendar` WHERE `name` LIKE ? AND `approved` = 1 AND `also_known_as` IS NULL", array('%'.$search.'%'));
	$total_found = $db->num_rows();
	if ($total_found > 0)
	{
		while ($items = $db->fetch())
		{
			$templating->block('search_items', 'game-search');
			$templating->set('id', $items['id']);
			$templating->set('name', $items['name']);

			$date = '';
			if (!empty($items['date']))
			{
				$date = $items['date'];
			}

			$unreleased = '';
			if (isset($date) && !empty($date) && $date > date('Y-m-d'))
			{
				$unreleased = '<span class="badge blue">Unreleased!</span>';
			}
			$templating->set('unreleased', $unreleased);

			$best_guess = '';
			if ($items['best_guess'] == 1)
			{
				$best_guess = '<span class="badge blue">Best Guess Date!</span>';
			}
			$templating->set('best_guess', $best_guess);

			$dlc = '';
			if ($items['is_dlc'] == 1)
			{
				$dlc = '<span class="badge yellow">DLC</span>';
			}
			$templating->set('dlc', $dlc);
		}
	}
	else
	{
		$core->message('None found. You could try the filters on our <a href="/index.php?module=game&view=all">main games page here.</a>');
	}

	$templating->block('search_result_bottom', 'game-search');
}
