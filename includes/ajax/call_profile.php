<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_template.php');

$templating = new template('default');

include($file_dir . '/includes/class_user.php');
$user = new user();

if(isset($_GET['user_id']))
{
  $db->sqlquery("SELECT `username`, `pc_info_public`, `distro` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
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

      $fields_output = '';
      $pc_info = user::display_pc_info($_GET['user_id'], $grab_fields['distro']);
      if ($pc_info['counter'] > 0)
      {
        foreach ($pc_info as $k => $info)
        {
          if ($k != 'counter')
          {
            $fields_output .= '<li>' . $info . '</li>';
          }
        }
      }
      else
      {
        $fields_output = '<li><em>This user has not filled out their PC info!</em></li>';
      }
      $templating->set('fields', $fields_output);

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
          $edit_link = ' | <a href="/usercp.php?module=pcinfo">Edit your PC info</a>';
        }
      }
      $templating->set('edit_link', $edit_link);

      echo $templating->output();
    }
  }
}
