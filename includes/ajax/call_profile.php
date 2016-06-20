<?php
session_start();

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

include('../class_template.php');

$templating = new template('default');

if(isset($_GET['user_id']))
{
  $db->sqlquery("SELECT u.`username`, u.`pc_info_public`, u.`distro`, p.`what_bits`, p.`cpu_vendor`, p.`cpu_model`, p.`gpu_vendor`, p.`gpu_model`, p.`gpu_driver`, p.`ram_count`, p.`monitor_count`, p.`gaming_machine_type`, p.`resolution`, p.`dual_boot` FROM `users` u LEFT JOIN `user_profile_info` p ON p.`user_id` = u.`user_id` WHERE p.`user_id` = ?", array($_GET['user_id']));
  if ($db->num_rows() != 1)
	{
		$core->message('That person does not exist here!');
	}
  else
  {
    $grab_fields = $db->fetch();

    if ($grab_fields['pc_info_public'] == 1)
    {
      if (core::config('pretty_urls') == 1)
      {
        $profile_link = '/profiles/' . $_GET['user_id'];
      }
      else
      {
        $profile_link = '/index.php?module=profile&user_id=' . $_GET['user_id'];
      }

      $templating->load('profile');
      $templating->block('additional');
      $templating->set('username', $grab_fields['username']);
      $templating->set('profile_link', $profile_link);

      $counter = 0;

      $distro = '';
      if (!empty($grab_fields['distro']) && $grab_fields['distro'] != 'Not Listed')
      {
        $distro = "<li><strong>Distribution:</strong> <img class=\"distro\" height=\"20px\" width=\"20px\" src=\"/templates/default/images/distros/{$grab_fields['distro']}.svg\" alt=\"{$grab_fields['distro']}\" /> {$grab_fields['distro']}</li>";
        $counter++;
      }
      $templating->set('distro', $distro);

      $dist_arc = '';
      if ($grab_fields['what_bits'] != NULL && !empty($grab_fields['what_bits']))
      {
        $dist_arc = '<li><strong>CPU Architecture:</strong> '.$grab_fields['what_bits'].'</li>';
        $counter++;
      }
      $templating->set('dist_arc', $dist_arc);

      $dual_boot = '';
      if ($grab_fields['dual_boot'] != NULL && !empty($grab_fields['dual_boot']))
      {
        $dual_boot = '<li><strong>Do you dual-boot with a different operating system?</strong> '.$grab_fields['dual_boot'].'</li>';
        $counter++;
      }
      $templating->set('dual_boot', $dual_boot);

      $cpu_vendor = '';
      if ($grab_fields['cpu_vendor'] != NULL && !empty($grab_fields['cpu_vendor']))
      {
        $cpu_vendor = '<li><strong>CPU Vendor:</strong> '.$grab_fields['cpu_vendor'].'</li>';
        $counter++;
      }
      $templating->set('cpu_vendor', $cpu_vendor);

      $cpu_model = '';
      if ($grab_fields['cpu_model'] != NULL && !empty($grab_fields['cpu_model']))
      {
        $cpu_model = '<li><strong>CPU Model:</strong> '.$grab_fields['cpu_model'].'</li>';
        $counter++;
      }
      $templating->set('cpu_model', $cpu_model);

      $gpu_vendor = '';
      if ($grab_fields['gpu_vendor'] != NULL && !empty($grab_fields['gpu_vendor']))
      {
        $gpu_vendor = '<li><strong>GPU Vendor:</strong> '.$grab_fields['gpu_vendor'].'</li>';
        $counter++;
      }
      $templating->set('gpu_vendor', $gpu_vendor);

      $gpu_model = '';
      if ($grab_fields['gpu_model'] != NULL && !empty($grab_fields['gpu_model']))
      {
        $gpu_model = '<li><strong>GPU Model:</strong> '.$grab_fields['gpu_model'].'</li>';
        $counter++;
      }
      $templating->set('gpu_model', $gpu_model);

      $gpu_driver = '';
      if ($grab_fields['gpu_driver'] != NULL && !empty($grab_fields['gpu_driver']))
      {
        $gpu_driver = '<li><strong>GPU Driver:</strong> '.$grab_fields['gpu_driver'].'</li>';
        $counter++;
      }
      $templating->set('gpu_driver', $gpu_driver);

      $ram_count = '';
      if ($grab_fields['ram_count'] != NULL && !empty($grab_fields['ram_count']))
      {
        $ram_count = '<li><strong>RAM:</strong> '.$grab_fields['ram_count'].'</li>';
        $counter++;
      }
      $templating->set('ram_count', $ram_count);

      $monitor_count = '';
      if ($grab_fields['monitor_count'] != NULL && !empty($grab_fields['monitor_count']))
      {
        $monitor_count = '<li><strong>Monitors:</strong> '.$grab_fields['monitor_count'].'</li>';
        $counter++;
      }
      $templating->set('monitor_count', $monitor_count);

      $resolution = '';
      if ($grab_fields['resolution'] != NULL && !empty($grab_fields['resolution']))
      {
        $resolution = '<li><strong>Resolution:</strong> '.$grab_fields['resolution'].'</li>';
        $counter++;
      }
      $templating->set('resolution', $resolution);

      $gaming_machine_type = '';
      if ($grab_fields['gaming_machine_type'] != NULL && !empty($grab_fields['gaming_machine_type']))
      {
        $gaming_machine_type = '<li><strong>Main gaming machine:</strong> '.$grab_fields['gaming_machine_type'].'</li>';
        $counter++;
      }
      $templating->set('gaming_machine_type', $gaming_machine_type);

      $additional_empty = '';
      if ($counter == 0)
      {
        $additional_empty = '<li><em>This user has not filled out their PC info!</em></li>';
      }
      $templating->set('additional_empty', $additional_empty);

      $templating->block('view_full');

      if (core::config('pretty_urls') == 1)
      {
        $stats_link = "/users/statistics";
      }
      else
      {
        $stats_link = "/index.php?module=statistics";
      }
      $templating->set('stats_link', $stats_link);

      $templating->set('profile_link', $profile_link);

      $edit_link = '';
      if (isset($_GET['user_id']))
      {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $_GET['user_id'])
        {
          $edit_link = ' | <a href="/usercp.php">Edit your profile</a>';
        }
      }
      $templating->set('edit_link', $edit_link);

      echo $templating->output();
    }
  }
}
