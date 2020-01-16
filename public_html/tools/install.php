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
	<div>
		<strong>Starting configuration</strong>
		Site name: <input type="text" name="site_title" /><br />
		Site url: <input type="text" name="site_url" /><br />
		Site path: <input type="text" name="site_path" /><br />
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

	// setup tables
	
	// insert general table data
	
	// setup admin user
	
	// redirect to the installed app
	echo 'All done, you can now use <a href="'.$_POST['site_url'].'">your website</a>. Please ensure you delete this file.';
}
?>
