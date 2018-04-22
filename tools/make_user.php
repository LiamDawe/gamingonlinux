<?php
$file_dir = dirname( dirname(__FILE__) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_template.php');
$templating = new template($core, $core->config('template'));

if (isset($_POST['act']) && $_POST['act'] == 'reg_user')
{
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
	core::$redis->set('CONFIG_total_users', $new_total); // no expiry as config hardly ever changes

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
?>
<html lang="en">
<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>GOL - User Creation</title>
<link rel="stylesheet" type="text/css" href="../includes/jscripts/select2/select2.min.css">
<script src="../includes/jscripts/jquery-3.2.1.min.js"></script>
<script src="../includes/jscripts/select2/select2.min.js?v=1"></script>
<script async type="text/javascript" src="../includes/jscripts/GOL/header.js?v=8.7.3"></script>
</head>

<body itemscope itemtype="http://schema.org/Article">
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
</form>
</body>
</html>
