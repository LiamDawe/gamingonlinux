<?php
$game_sales = new game_sales($dbl, $templating, $user, $core);

$templating->set_previous('title', 'Free Linux games', 1);
$templating->set_previous('meta_description', 'Free Linux games', 1);

$templating->load('free_games');

$templating->block('head', 'free_games');

$game_sales->display_free();

// paging for pagination
/*
$page = isset($_GET['page'])?intval($_GET['page']-1):0;

$total_rows = $dbl->run("SELECT COUNT(id) FROM `calendar` WHERE `free_game` = 1 ORDER BY `name` ASC")->fetchOne();

$link_extra = '';
$pagination = $core->pagination_link(50, $total_rows, '/index.php?module=free_games&', $page + 1, $link_extra);

$games_res = $dbl->run("SELECT `id`, `name`, `link`, `gog_link`, `steam_link`, `itch_link`, `license`, `small_picture`, `trailer` FROM `calendar` WHERE `free_game` = 1 ORDER BY `name` ASC LIMIT {$core->start}, 50")->fetch_all();

if ($games_res)
{
	foreach ($games_res as $game)
	{
		$templating->block('row', 'free_games');

		$small_pic = '';
		if ($game['small_picture'] != NULL && $game['small_picture'] != '')
		{
			$small_pic = '<img src="' . $core->config('website_url') . 'uploads/gamesdb/small/' . $game['small_picture'] . '" alt="" />';
		}

		if ($game['trailer'] != NULL && $game['trailer'] != '')
		{
			$small_pic = '<a data-fancybox href="'.$game['trailer'].'">' . $small_pic . '</a>';
		}

		$templating->set('small_pic', $small_pic);

		$edit = '';
		if ($user->check_group([1,2,5]))
		{
			$edit = '<a href="/admin.php?module=games&view=edit&id='.$game['id'].'"><span class="icon edit edit-sale-icon"></span></a> ';
		}
		$templating->set('edit', $edit);

		$templating->set('name', $game['name']);

		$links = [];
		$stores = ['link' => 'Official Site', 'gog_link' => 'GOG', 'steam_link' => 'Steam', 'itch_link' => 'itch.io'];
		foreach ($stores as $type => $name)
		{
			if (isset($game[$type]) && !empty($game[$type]))
			{
				$links[] = '<a href="'.$game[$type].'">'.$name.'</a>';
			}
		}
		$templating->set('links', implode(', ', $links));

		$license = '';
		if (isset($game['license']) && $game['license'] != '')
		{
			$license = $game['license'];
		}
		$templating->set('license', $license);
	}
}
else
{
	$core->message("We aren't listing any free games at the moment, come back soon!");
}

$templating->block('bottom', 'free_games');
if ($pagination != '')
{
	$pagination = '<div class="free-games-pagination">'.$pagination.'</div>';
}
$templating->set('pagination', $pagination);*/