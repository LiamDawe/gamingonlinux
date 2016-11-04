<?php
session_start();

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

include('../class_template.php');

$templating = new template('default');

include('../class_user.php');
$user = new user();

if(isset($_GET['comment_id']) || isset($_GET['article_id']))
{
  if (isset($_GET['comment_id']))
  {
    $table = 'likes';
    $field = 'comment_id';
  }
  if (isset($_GET['article_id']))
  {
    $table = 'article_likes';
    $field = 'article_id';
  }
  $db->sqlquery("SELECT u.`username`, u.`user_id`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_gallery`, u.`avatar`, u.`avatar_gravatar`, u.`avatar_uploaded`, l.like_id FROM `users` u INNER JOIN `$table` l ON u.`user_id` = l.`user_id` WHERE l.`$field` = ? ORDER BY u.`username` ASC LIMIT 50", array($_GET[$field]));
  if ($db->num_rows() == 0)
	{
		$core->message('That does not exist!');
	}
  else
  {
    $templating->load('who_likes');

    $templating->block('top');

    while($grab_users = $db->fetch())
    {
      if (core::config('pretty_urls') == 1)
      {
        $profile_link = '/profiles/' . $grab_users['user_id'];
      }
      else
      {
        $profile_link = '/index.php?module=profile&user_id=' . $grab_users['user_id'];
      }

      $avatar = $user->sort_avatar($grab_users);

      $templating->block('user_row');
      $templating->set('username', $grab_users['username']);
      $templating->set('profile_link', $profile_link);
      $templating->set('avatar', $avatar);
    }

    $templating->block('end');
    echo $templating->output();
  }
}
