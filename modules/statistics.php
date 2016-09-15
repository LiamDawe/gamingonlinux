<?php
$templating->set_previous('title', 'User stats', 1);
$templating->set_previous('meta_description', 'Statistics generated from the users of the GamingOnLinux website', 1);

include(core::config('path') . '/includes/profile_fields.php');

$templating->load('statistics');

// TOTAL USERS
$templating->block('users');
$templating->set('total_users', core::config('total_users'));

$charts = array(
  array("name" => "Linux Distributions (Combined)"),
  array("name" => "Linux Distributions (Split)"),
  array("name" => "Desktop Environment"),
  array("name" => "Distro Architecture"),
  array("name" => "Dual Booting"),
  array("name" => "CPU Vendor"),
  array("name" => "GPU Vendor"),
  array("name" => "GPU Driver", "order" => "drivers"),
  array("name" => "GPU Driver (Nvidia)", "order" => "drivers"),
  array("name" => "GPU Driver (AMD)", "order" => "drivers"),
  array("name" => "RAM"),
  array("name" => "Monitors"),
  array("name" => "Resolution"),
  array("name" => "Main Gaming Machine")
);

$counter = 0;

foreach($charts as $chart)
{
  // DISTRIBUTION CHOICE
  $db->sqlquery("SELECT `id` FROM `user_stats_charts` WHERE `name` = '{$chart['name']}' ORDER BY `id` DESC LIMIT 1");
  $get_chart_id = $db->fetch();

  $order = '';
  if (isset($chart['order']))
  {
    $order = $chart['order'];
  }

  $grab_chart = $core->stat_chart($get_chart_id['id'], $order);

  // only do this once
  if ($counter == 0)
  {
    $templating->block('info');
    $templating->set('date', $grab_chart['date']);
  }

  $templating->block('chart_section');
  $templating->set('title', $chart['name']);
  $templating->set('graph', $grab_chart['graph']);
  $download_link = '';
  if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
  {
    $download_link = '<div style="text-align: center;"><em>Download Graph: (<a href="/download-graph.php?id='.$get_chart_id['id'].'">SVG</a>)</em></div>';
  }
  $templating->set('download_link', $download_link);
  $templating->set('total_users', $grab_chart['total_users_answered']);
  $templating->set('full_info', $grab_chart['full_info']);
  $counter++;
}
