<?php
$templating->set_previous('title', 'User stats', 1);
$templating->set_previous('meta_description', 'Statistics generated from the users of the GamingOnLinux website', 1);

include(core::config('path') . '/includes/profile_fields.php');

$templating->load('statistics');

// TOTAL USERS
$templating->block('users');
$templating->set('total_users', core::config('total_users'));

$charts = array(
  array("name" => "Linux Distributions"),
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
  $get_distro_chart = $db->fetch();

  $order = '';
  if (isset($chart['order']))
  {
    $order = $chart['order'];
  }

  $grab_chart = $core->stat_chart($get_distro_chart['id'], $order);

  // only do this once
  if ($counter == 0)
  {
    $templating->block('info');
    $templating->set('date', $grab_chart['date']);
  }

  $templating->block('chart_section');
  $templating->set('title', $chart['name']);
  $templating->set('graph', $grab_chart['graph']);
  $templating->set('total_users', $grab_chart['total_users_answered']);
  $counter++;
}
