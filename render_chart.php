<?php
define("APP_ROOT", dirname(__FILE__));

require APP_ROOT . "/includes/bootstrap.php";

$charts = new charts($dbl);

if (!core::is_number($_GET['id']))
{
	die('That was not a correct chart ID.');
}
else
{
	$id = (int) $_GET['id'];
	header('Content-type: image/svg+xml');
	
	if ($_GET['type'] == 'normal')
	{
		if (isset($_GET['download']))
		{
			$info = $dbl->run("SELECT `name` FROM `charts` WHERE `id` = ?", array($id))->fetch();
			$file = $info['name'] . '.svg';
			header('Content-type: image/svg+xml');
			header("Content-Disposition: attachment; filename=$file");
		}
		echo $charts->render(['title_colour' => '#FFFFFF', 'counter_colour' => '#000000'], ['id' => $id, 'labels_table' => 'charts_labels', 'data_table' => 'charts_data']);
	}

	if ($_GET['type'] == 'stats')
	{
		if (isset($_GET['download']))
		{
			$info = $dbl->run("SELECT `name` FROM `user_stats_charts` WHERE `id` = ?", array($id))->fetch();
			$file = $info['name'] . '.svg';
			header('Content-type: image/svg+xml');
			header("Content-Disposition: attachment; filename=$file");
		}
		$options = ['padding_right' => 70, 'show_top_10' => 1, 'order' => 'ASC', 'title_colour' => '#FFFFFF', 'counter_colour' => '#000000'];
		echo $charts->stat_chart($id, NULL, $options)['graph'];
	}
}
