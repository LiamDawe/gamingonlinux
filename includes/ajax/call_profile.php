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
    $additionaldb = $db->fetch();
    
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


    foreach($additionaldb as $key => $additional)
    {
      $templating->set($key, $additional);
    }

    $templating->block('view_full');
    $templating->set('profile_link', $profile_link);

    echo $templating->output();
  }
}
