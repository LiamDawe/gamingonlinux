<?php
session_start();

include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
  if (core::config('pretty_urls') == 1)
  {
    header("Location: /users/statistics");
  }
  else
  {
    header("Location: /index.php?module=statistics");
  }
}

$db->sqlquery("SELECT `id` FROM `user_stats_charts` WHERE `id` = ?", array($_GET['id']));
$get_chart_id = $db->fetch();

$grab_chart = $core->stat_chart($get_chart_id['id']);

header('Content-type: image/svg+xml');
header('Content-Disposition: attachment; filename=' . 'gol-graph-' . $get_chart_id['id'] . '.svg');  // Make the browser display the Save As dialog

echo $grab_chart['graph'];
