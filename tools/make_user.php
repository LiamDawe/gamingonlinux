<?php
define("APP_ROOT", dirname( dirname(__FILE__)) );
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

$templating->block('left', 'mainpage');

if (isset($_POST['act']) && $_POST['act'] == 'reg_user')
{
	// tool for making a single user account
	$username = core::make_safe($_POST['username']);
	$email = core::make_safe($_POST['email']);
	$password = $_POST['password'];
	$user_groups = $_POST['user_groups'];

	$check_empty = core::mempty(compact('username', 'email', 'password'));
	if ($check_empty !== true)
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = $check_empty;
		header("Location: /tools/make_user.php");
		die();
	}

	if (empty($user_groups))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'user groups';
		header("Location: /tools/make_user.php");
		die();
	}

	$username = core::make_safe($_POST['username']);
	$safe_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
	$email = core::make_safe($_POST['email']);

	$modqueue = 0;
	if (isset($_POST['modqueue']))
	{
		$modqueue = 1;
	}

	$user_query = "INSERT INTO `users` SET `username` = ?, `password` = ?, `email` = ?, `register_date` = ?, `theme` = 'default', `activated` = 1, `in_mod_queue` = ?";

	$dbl->run($user_query, array($username, $safe_password, $email, core::$date, $modqueue));

	$new_user_id = $dbl->new_id();
	foreach ($_POST['user_groups'] as $key => $group)
	{
		$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = ?", [$new_user_id, $group]);
	}

	// update total users config
	$dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_users'");

	$new_total = $dbl->run("SELECT `data_value` FROM `config` WHERE `data_key` = 'total_users'")->fetchOne();

	// invalidate the cache
	$core->set_dbcache('CONFIG_total_users', $new_total); // no expiry as config hardly ever changes

	echo 'User <strong>' . $username . '</strong> created with password: ' . $_POST['password'];
}

if (isset($_POST['act']) && $_POST['act'] == 'reg_user_random')
{
	// tool for making a bunch of random user accounts
	for ($i = 1; $i <= $_POST['total_users']; $i++) 
	{
		$names = ['Bob', 'Howard', 'Samsai', 'John', 'Liam', 'Sin', 'James', 'Arthur', 'Marco', 'Edwin', 'Jeremy', 'Corbyn'];
		$username = $names[array_rand($names)] . ' ' . $names[array_rand($names)];
		$email = core::make_safe($_POST['email']);
		$password = rand(0,100).time();
	
		$safe_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
	
		$user_query = "INSERT INTO `users` SET `username` = ?, `password` = ?, `email` = ?, `register_date` = ?, `theme` = 'default', `activated` = 1";
	
		$dbl->run($user_query, array($username, $safe_password, $email, core::$date));
	
		$new_user_id = $dbl->new_id();
		foreach ($_POST['user_groups'] as $key => $group)
		{
			$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = ?", [$new_user_id, $group]);
		}
	
		echo 'User <strong>' . $username . '</strong> created with password: ' . $password;
	}
}
if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message(core::$current_module['module_file_name'], $_SESSION['message'], $extra);
}
?>
<div style="margin: auto; width: 50%;">
	You can use this form to make a new user in any user groups. This is not meant for a live site! It will display the plain text password that was given!<br />
	<form method="post" action="make_user.php">
	Username: <input type="text" name="username" /><br />
	Email: <input type="email" name="email" /><br />
	Password: <input type="password" name="password"><br />
	<strong>User Groups</strong><br />
	<select tabindex="-1" multiple="" name="user_groups[]" class="call_user_groups" style="width:300px" class="populate select2-offscreen"></select><br />
	<label>In moderation queue? <input type="checkbox" name="modqueue" /></label><br />
	<button type="submit" name="act" value="reg_user">Create user</button>
	</form>
	<p>Or, you can make a bunch of random users all at once to test a feature:</p>
	<form method="post" action="make_user.php">
	<p><strong>Number of users</strong></p>
	<select name="total_users">
	<?php
		for ($i=1; $i<=100; $i++)
		{
	?>
		<option value="<?php echo $i;?>"><?php echo $i;?></option>
	<?php
		}
	?>
	</select>	
	<p><strong>User Groups</strong></p>
	<select tabindex="-1" multiple="" name="user_groups[]" class="call_user_groups" style="width:300px" class="populate select2-offscreen"></select><br />
	<button type="submit" name="act" value="reg_user_random">Create random users</button>		
	</form>
</div>
<?php 
include(APP_ROOT . '/includes/footer.php');