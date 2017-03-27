<?php
$file_dir = dirname(__FILE__);

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir. '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_user.php');
$user = new user();

include($file_dir . '/includes/class_charts.php');

$charts = new golchart();

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
			$db->sqlquery("SELECT `name` FROM `charts` WHERE `id` = ?", array($id));
			$info = $db->fetch();
			$file = $info['name'] . '.svg';
			header('Content-type: image/svg+xml');
			header("Content-Disposition: attachment; filename=$file");
		}
		echo $charts->render($id, ['title_colour' => '#FFFFFF', 'counter_colour' => '#000000'], 'charts_labels', 'charts_data');
	}

	if ($_GET['type'] == 'stats')
	{
		if (isset($_GET['download']))
		{
			$db->sqlquery("SELECT `name` FROM `user_stats_charts` WHERE `id` = ?", array($id));
			$info = $db->fetch();
			$file = $info['name'] . '.svg';
			header('Content-type: image/svg+xml');
			header("Content-Disposition: attachment; filename=$file");
		}
		$options = ['padding_right' => 70, 'show_top_10' => 1, 'order' => 'ASC', 'title_colour' => '#FFFFFF', 'counter_colour' => '#000000'];
		echo $charts->stat_chart($id, NULL, $options)['graph'];
	}
}
