<?php
if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/installer-lock.lock"))
{
	die('Installer lock file exists! Already installed.');
}
?>
<h3>GOL Script Setup</h3>
For Issues: <a href="https://gitlab.com/liamdawe/gamingonlinux">https://gitlab.com/liamdawe/gamingonlinux</a><br />
<br />
Please enter the following info to connect to the DB and install.<br />
<br />
<form method="post" action="install.php">
	<div>
		<strong>Database information</strong><br />
		Database Name: <input type="text" name="db_name" value="" /><br />
		Database Username: <input type="text" name="db_username" value="" /><br />
		Database Password: <input type="password" type="text" name="db_password" /><br />
		Database Host: <input type="text" name="db_host" value="localhost" />
	</div>
	<br />
	<div>
		<strong>Admin user setup</strong><br />
		Username: <input type="text" name="username" /><br />
		Password: <input type="password" name="password" /><br />
		Email: <input type="email" name="email" /><br />
	</div>
	<br />
	<div>
		<strong>Starting configuration</strong><br />
		Site name: <input type="text" name="site_title" value="" /><br />
		Site url: <input type="text" name="site_url" value="" /><br />
		Site path: <input type="text" name="site_path" value="<?php echo dirname(dirname(__FILE__)) . '/'; ?>"/><br />
	</div>
	<p><button type="submit" name="go" value="1">Go for launch</button></p>
</form>
<?php
if (isset($_POST['go']))
{
	define("APP_ROOT", dirname( dirname(__FILE__) ) );
	// create config file

	$config_file = fopen(APP_ROOT . '/includes/config.php', 'w');

	$config_content = '<?php
	define("DB", 
	[
		"DB_HOST_NAME" => "'.$_POST['db_host'].'",
		"DB_USER_NAME" => "'.$_POST['db_username'].'",
		"DB_PASSWORD" => "'.$_POST['db_password'].'",
		"DB_DATABASE" => "'.$_POST['db_name'].'"
	]);';

	fwrite($config_file, $config_content);
	fclose($config_file);

	include (dirname(__FILE__) . '/config.php');

	$dbl = new db_mysql();

	/* SETUP TABLES AND DATA */

	// Admin Blocks
	$dbl->run(
		"CREATE TABLE `admin_blocks` (
			`block_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
			`block_name` varchar(32) COLLATE utf8_bin NOT NULL,
			`activated` int(1) NOT NULL,
			`blocks_custom_content` text COLLATE utf8_bin DEFAULT NULL,
			`admin_only` tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (`block_id`)
		  ) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
	);
	
	$dbl->run("INSERT INTO `admin_blocks` (`block_id`, `block_link`, `block_name`, `activated`, `blocks_custom_content`, `admin_only`) VALUES
	(1, 'main_menu', 'Main Menu', 1, '', 0),
	(3, 'articles', 'Articles Admin', 1, '', 0),
	(8, 'blocks', 'Manage Blocks', 1, NULL, 1),
	(9, 'modules', 'Modules Configuration', 1, NULL, 1),
	(7, 'forum', 'Forum Admin', 1, NULL, 0),
	(5, 'users', 'Users Block', 1, NULL, 0),
	(6, 'goty', 'goty', 1, NULL, 0),
	(4, 'featured', 'featured', 1, NULL, 0),
	(2, 'mod_queue', 'Mod Queue', 1, NULL, 0),
	(10, 'charts', 'charts', 1, NULL, 0),
	(11, 'livestreams', 'livestreams', 1, NULL, 0),
	(12, 'sales', 'sales', 1, NULL, 0),
	(13, 'games', 'games', 1, NULL, 0);");

	// Admin Discussion
	$dbl->run("CREATE TABLE `admin_discussion` (
		`id` int(11) UNSIGNED NOT NULL,
		`user_id` int(11) UNSIGNED NOT NULL,
		`text` text NOT NULL,
		`date_posted` int(11) UNSIGNED NOT NULL,
		PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	  ");
	
	/* SETUP ADMIN ACCOUNT */

	// make a .lock file, if lock file present, don't let installer run - prevent overwrites
	$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/installer-lock.lock","wb");
	fwrite($fp,$content);
	fclose($fp);
	
	// redirect to the installed app
	echo 'All done, you can now use <a href="'.$_POST['site_url'].'">your website</a>. Please ensure you delete this file.';
}
?>
