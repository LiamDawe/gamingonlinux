<?php
$templating->set_previous('title', 'User stats', 1);
$templating->set_previous('meta_description', 'Statistics generated from the users of the GamingOnLinux website', 1);

include(core::config('path') . '/includes/profile_fields.php');

$templating->load('statistics');

// TOTAL USERS
$templating->block('users');
$templating->set('total_users', core::config('total_users'));

// DISTRIBUTION CHOICE
$db->sqlquery("SELECT `id` FROM `charts` WHERE `name` = 'Linux Distributions' ORDER BY `id` DESC LIMIT 1");
$get_distro_chart = $db->fetch();

$distro_chart = $core->stat_chart($get_distro_chart['id']);

$templating->block('info');
$templating->set('date', $distro_chart['date']);

$templating->block('distribution');
$templating->set('graph', $distro_chart['graph']);

// CPU VENDOR CHOICE
$db->sqlquery("SELECT `id` FROM `charts` WHERE `name` = 'CPU Vendor' ORDER BY `id` DESC LIMIT 1");
$get_cpu_chart = $db->fetch();

$cpu_chart = $core->stat_chart($get_cpu_chart['id']);

$templating->block('cpu_vendor');
$templating->set('graph', $cpu_chart['graph']);

// GPU VENDOR
$db->sqlquery("SELECT `id` FROM `charts` WHERE `name` = 'GPU Vendor' ORDER BY `id` DESC LIMIT 1");
$get_gpu_chart = $db->fetch();

$gpu_chart = $core->stat_chart($get_gpu_chart['id']);

$templating->block('gpu_vendor');
$templating->set('graph', $gpu_chart['graph']);

// GPU DRIVER
$db->sqlquery("SELECT `id` FROM `charts` WHERE `name` = 'GPU Driver' ORDER BY `id` DESC LIMIT 1");
$get_gpud_chart = $db->fetch();

$gpud_chart = $core->stat_chart($get_gpud_chart['id']);

$templating->block('gpu_driver');
$templating->set('graph', $gpud_chart['graph']);
