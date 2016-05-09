<?php
include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

include('../class_template.php');

$templating = new template('default');


if(isset($_GET['user_id']))
{
  $db->sqlquery("SELECT `what_bits`, `cpu_vendor`, `gpu_vendor`, `gpu_driver`, `ram_count`, `monitor_count`, `gaming_machine_type` FROM `user_profile_info` WHERE `user_id` = ?", array($_GET['user_id']));
  if ($db->num_rows() != 1)
	{
		$core->message('That person does not exist here!');
	}
  else
  {
    $additional_db = $db->fetch();

    $db->sqlquery("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
    $user_info = $db->fetch();

    if (core::config('pretty_urls') == 1)
    {
      $profile_link = '/profiles/' . $_GET['user_id'];
    }
    else {
      $profile_link = '/index.php?module=profile&user_id=' . $_GET['user_id'];
    }

    $templating->load('profile');
    $templating->block('additional');
    $templating->set('username', $user_info['username']);
    $templating->set('profile_link', $profile_link);

    $counter = 0;
    foreach ($additional_db as $additionaldb)
    {
      $cpu_arc = '';
      if ($additionaldb['what_bits'] != NULL && !empty($additionaldb['what_bits']))
      {
        $cpu_arc = '<li><strong>CPU Architecture:</strong> '.$additionaldb['what_bits'].'</li>';
        $counter++;
      }
      $templating->set('cpu_arc', $cpu_arc);

      $cpu_vendor = '';
      if ($additionaldb['cpu_vendor'] != NULL && !empty($additionaldb['cpu_vendor']))
      {
        $cpu_vendor = '<li><strong>CPU Vendor:</strong> '.$additionaldb['cpu_vendor'].'</li>';
        $counter++;
      }
      $templating->set('cpu_vendor', $cpu_vendor);

      $gpu_vendor = '';
      if ($additionaldb['gpu_vendor'] != NULL && !empty($additionaldb['gpu_vendor']))
      {
        $cpu_vendor = '<li><strong>GPU Vendor:</strong> '.$additionaldb['gpu_vendor'].'</li>';
        $counter++;
      }
      $templating->set('gpu_vendor', $gpu_vendor);

      $gpu_driver = '';
      if ($additionaldb['gpu_driver'] != NULL && !empty($additionaldb['gpu_driver']))
      {
        $gpu_driver = '<li><strong>GPU Driver:</strong> '.$additionaldb['gpu_driver'].'</li>';
        $counter++;
      }
      $templating->set('gpu_driver', $gpu_driver);

      $ram_count = '';
      if ($additionaldb['ram_count'] != NULL && !empty($additionaldb['ram_count']))
      {
        $ram_count = '<li><strong>RAM:</strong> '.$additionaldb['ram_count'].'</li>';
        $counter++;
      }
      $templating->set('ram_count', $ram_count);

      $monitor_count = '';
      if ($additionaldb['monitor_count'] != NULL && !empty($additionaldb['monitor_count']))
      {
        $monitor_count = '<li><strong>Monitors:</strong> '.$additionaldb['monitor_count'].'</li>';
        $counter++;
      }
      $templating->set('monitor_count', $monitor_count);

      $gaming_machine_type = '';
      if ($additionaldb['gaming_machine_type'] != NULL && !empty($additionaldb['gaming_machine_type']))
      {
        $gaming_machine_type = '<li><strong>Main gaming machine:</strong> '.$additionaldb['gaming_machine_type'].'</li>';
        $counter++;
      }
      $templating->set('gaming_machine_type', $gaming_machine_type);
    }
    $additional_empty = '';
    if ($counter == 0)
    {
      $additional_empty = '<li><em>This user has not filled out their PC info!</em></li>';
    }
    $templating->set('additional_empty', $additional_empty);

    $templating->block('view_full');
    $templating->set('profile_link', $profile_link);

    echo $templating->output();
  }
}
