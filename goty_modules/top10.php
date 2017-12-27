<?php
// viewing Top 10 of a specific category
if (isset($_GET['category_id']) && isset($_GET['view']) && $_GET['view'] == 'top10')
{
	if (!core::is_number($_GET['category_id']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'category';
		header('Location: /goty.php');
		die();
	}

	if ($core->config('goty_finished') == 1)
	{
		$cat = $dbl->run("SELECT `category_name` FROM `goty_category` WHERE `category_id` = ?", array($_GET['category_id']))->fetch();

		$templating->block('top10_bread', 'goty');
		$templating->set('category_name', $cat['category_name']);
		$templating->set('category_id', $_GET['category_id']);
	}
	else
	{
		$core->message('Voting is currently open! You can only see the top 10 when it is finished to help prevent a voting bias.');
		include(APP_ROOT . '/includes/footer.php');
		die();
	}
}

if ($core->config('goty_voting_open') == 0 && $core->config('goty_finished') == 1)
{
	$templating->block('top10', 'goty');
	$templating->set('category_name', $cat['category_name']);

	require_once(APP_ROOT . '/includes/SVGGraph/SVGGraph.php');
	$labels = array();

	$settings = array('graph_title' => $cat['category_name'], 'auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside', 'show_grid_h' => false, 'label_h' => 'Total Votes');
	$graph = new SVGGraph(400, 300, $settings);
	$colours = array(array('rgb(151,187,205):0.90','rgb(113,140,153):'), array('rgb(152,125,113):0.90','rgb(114,93,84)'));
	$graph->colours = $colours;

	$games_top = $dbl->run("SELECT `id`, `game_id`, `votes` FROM `goty_games` WHERE `accepted` = 1  AND `category_id` = ? ORDER BY `votes` DESC LIMIT 10", array($_GET['category_id']))->fetch_all();

	foreach ($games_top as $label_loop)
	{
		$labels[$label_loop['game']] = $label_loop['votes'];
	}

	$graph->Values($labels);
	$get_graph = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';

	$templating->block('topchart', 'goty');
	$templating->set('chart', $get_graph);
}
$templating->block('games_bottom', 'goty');
?>