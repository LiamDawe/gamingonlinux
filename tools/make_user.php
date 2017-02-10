<?php
$file_dir = dirname( dirname(__FILE__) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir. '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_template.php');
$templating = new template('default');

$groups = '';
$db->sqlquery("SELECT * FROM `user_groups` ORDER BY `group_name` ASC");
while ($get_group = $db->fetch())
{
	$groups .= '<option value="'.$get_group['group_id'].'">'.$get_group['group_name'].'</option>';
}

if (isset($_POST['act']) && $_POST['act'] == 'reg_user')
{
	if ()
  // tool for making a single user account
  $username = core::make_safe($_POST['username']);
  $email = core::make_safe($_POST['email']);
  $password = $_POST['password'];

  $check_empty = core::mempty(compact('username', 'email', 'password'));
  if ($check_empty != true)
  {
    header("Location: /make_user.php&message=missing&extra=".$check_empty);
    die();
  }

  $username = core::make_safe($_POST['username']);
  $safe_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $email = core::make_safe($_POST['email']);

  $query = "INSERT INTO `users` SET `username` = ?, `password` = ?, `email` = ?, `gravatar_email` = ?, `user_group` = ?, `secondary_user_group` = ?, `register_date` = ?, `theme` = 'default', `activated` = 1";

  $db->sqlquery($query, array($username, $safe_password, $email, $email, $_POST['group'], $_POST['group'], core::$date));

  echo 'User <strong>' . $username . '</strong> created with password: ' . $_POST['password'];
}
?>
You can use this form to make a new user with a specified user group. It will allow duplicate emails, since this is a debugging tool and is not meant for a live site!<br />
<form method="post" action="make_user.php">
  Username: <input type="text" name="username" /><br />
  Email: <input type="email" name="email" /><br />
  Password: <input type="password" name="password"><br />
  User group: <select name="group"><?=$groups?></select><br />
  <button type="submit" name="act" value="reg_user">Create user</button>
</form>
