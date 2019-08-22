<form method="post" action="install.php">
	<div>
		<strong>Database information</strong><br />
		Database Name: <input type="text" name="db_name" value="" /><br />
		Database Username: <input type="text" name="db_username" value="" /><br />
		Database Password: <input type="text" type="text" name="db_password" /><br />
		Database Host: <input type="text" value="localhost" />
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
		Site url:
		Site path: 
	</div>
	<p><button type="submit" name="go" value="1">Go for launch</button></p>
</form>
<?php
if (isset($_POST['go']))
{
	// setup tables
	
	// insert general table data
	
	// setup admin user
	
	// redirect to the installed app
	header("Location: ");
}
?>
