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
	$dbl->run("CREATE TABLE `admin_blocks` (
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
	(2, 'mod_queue', 'Mod Queue', 1, NULL, 0),
	(3, 'articles', 'Articles Admin', 1, '', 0),
	(4, 'featured', 'featured', 1, NULL, 0),
	(5, 'users', 'Users Block', 1, NULL, 0),
	(6, 'goty', 'goty', 1, NULL, 0),
	(7, 'forum', 'Forum Admin', 1, NULL, 0),
	(8, 'blocks', 'Manage Blocks', 1, NULL, 1),
	(9, 'modules', 'Modules Configuration', 1, NULL, 1),
	(10, 'charts', 'charts', 1, NULL, 0),
	(11, 'livestreams', 'livestreams', 1, NULL, 0),
	(12, 'sales', 'sales', 1, NULL, 0),
	(13, 'games', 'games', 1, NULL, 0);");

	// Admin Discussion
	$dbl->run("CREATE TABLE `admin_discussion` (
		`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`user_id` int(11) UNSIGNED NOT NULL,
		`text` text NOT NULL,
		`date_posted` int(11) UNSIGNED NOT NULL,
		PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; COLLATE=utf8mb4_bin;
	  ");

	// admin modules
	$dbl->run("CREATE TABLE `admin_modules` (
		`module_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`module_name` varchar(32) COLLATE utf8_bin NOT NULL,
		`module_title` varchar(32) COLLATE utf8_bin NOT NULL,
		`module_link` text COLLATE utf8_bin DEFAULT NULL,
		`show_in_sidebar` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'show a link in the admins main menu, set to 0 if it has a block',
		`activated` tinyint(1) NOT NULL DEFAULT 0,
		`admin_only` tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`module_id`)
	) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_bin; COLLATE=utf8_bin;");

	$dbl->run("INSERT INTO `admin_modules` (`module_id`, `module_name`, `module_title`, `module_link`, `show_in_sidebar`, `activated`, `admin_only`) VALUES
	(1, 'home', 'Admin Home', 'admin.php?module=home', 1, 1, 0),
	(2, 'articles', 'Articles Admin', '', 0, 1, 0),
	(3, 'categorys', 'Content Categorys', 'admin.php?module=categorys', 1, 1, 0),
	(4, 'blocks', 'Blocks', '', 0, 1, 1),
	(5, 'config', 'Configuration', 'admin.php?module=config', 1, 1, 1),
	(6, 'modules', 'Module Configuration', NULL, 0, 1, 1),
	(7, 'forum', 'Forum Admin', NULL, 0, 1, 0),
	(8, 'users', 'Users', NULL, 0, 1, 0),
	(9, 'featured', 'featured', NULL, 0, 1, 0),
	(10, 'goty', 'goty', 'goty', 0, 1, 0),
	(11, 'more_comments', 'view more editor comments', NULL, 0, 1, 0),
	(12, 'calendar', 'calendar', NULL, 0, 1, 0),
	(13, 'mod_queue', 'Moderation Queue', '', 0, 1, 0),
	(14, 'charts', 'charts', NULL, 0, 1, 0),
	(15, 'reviewqueue', 'Admin review queue', NULL, 0, 1, 0),
	(16, 'games', 'Games Database', NULL, 0, 1, 0),
	(17, 'announcements', 'Manage Announcements', 'admin.php?module=announcements&view=manage', 1, 1, 0),
	(18, 'add_article', 'Add New Article', NULL, 0, 1, 0),
	(19, 'comment_reports', 'Comment Reports', NULL, 0, 1, 0),
	(20, 'livestreams', 'Manage Livestreams', NULL, 0, 1, 0),
	(21, 'corrections', 'corrections', NULL, 0, 1, 0),
	(22, 'giveaways', 'Manage Key Giveaways', 'admin.php?module=giveaways', 1, 1, 1),
	(23, 'article_history', 'Article History', NULL, 0, 1, 0),
	(24, 'sales', 'Sales', NULL, 0, 1, 0),
	(25, 'rules', 'Site Rules', 'admin.php?module=rules', 1, 1, 1),
	(26, 'count_articles', 'Count Articles', 'admin.php?module=count_articles', 1, 1, 1);");

	// Admin Notifications
	$dbl->run("CREATE TABLE `admin_notifications` (
		`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`user_id` int(11) UNSIGNED NOT NULL,
		`completed` tinyint(1) NOT NULL DEFAULT 0,
		`created_date` int(11) UNSIGNED DEFAULT NULL,
		`completed_date` int(11) UNSIGNED DEFAULT NULL,
		`type` text COLLATE utf8mb4_bin DEFAULT NULL,
		`data` text COLLATE utf8mb4_bin DEFAULT NULL,
		`content` text COLLATE utf8mb4_bin DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `id` (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;");

	
	$dbl->run("CREATE TABLE `admin_user_notes` (
		`row_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`user_id` int(11) UNSIGNED NOT NULL,
		`notes` text DEFAULT NULL,
		`last_edited` int(11) UNSIGNED DEFAULT NULL,
		`last_edit_by` int(11) UNSIGNED DEFAULT NULL,
		PRIMARY KEY (`row_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;");

	$dbl->run("CREATE TABLE `announcements` (
		`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`text` text NOT NULL,
		`author_id` int(11) UNSIGNED NOT NULL,
		`date_created` datetime NOT NULL DEFAULT current_timestamp(),
		`user_groups` text DEFAULT NULL,
		`type` text DEFAULT NULL,
		`modules` text DEFAULT NULL,
		`can_dismiss` tinyint(1) NOT NULL DEFAULT 1,
		RIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;");

	$dbl->run("CREATE TABLE `articles` (
		`article_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`author_id` int(11) UNSIGNED NOT NULL,
		`guest_username` varchar(255) DEFAULT NULL,
		`guest_email` varchar(255) DEFAULT NULL,
		`guest_ip` varchar(100) DEFAULT NULL,
		`date` int(11) NOT NULL,
		`edit_date` datetime DEFAULT NULL,
		`date_submitted` int(11) DEFAULT NULL,
		`title` varchar(120) CHARACTER SET utf8mb4 NOT NULL,
		`slug` text NOT NULL,
		`tagline` text DEFAULT NULL,
		`text` text CHARACTER SET utf8mb4 NOT NULL,
		`comment_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`active` int(1) NOT NULL DEFAULT 1,
		`show_in_menu` tinyint(1) NOT NULL DEFAULT 0,
		`views` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`submitted_article` tinyint(1) NOT NULL DEFAULT 0,
		`admin_review` tinyint(1) NOT NULL DEFAULT 0,
		`reviewed_by_id` int(11) UNSIGNED DEFAULT NULL,
		`submitted_unapproved` tinyint(1) NOT NULL DEFAULT 0,
		`comments_open` tinyint(1) NOT NULL DEFAULT 1,
		`draft` tinyint(1) NOT NULL DEFAULT 0,
		`tagline_image` text DEFAULT NULL,
		`gallery_tagline` int(10) UNSIGNED NOT NULL DEFAULT 0,
		`locked` tinyint(1) NOT NULL DEFAULT 0,
		`locked_by` int(11) UNSIGNED DEFAULT NULL,
		`locked_date` int(11) DEFAULT NULL,
		`preview_code` varchar(10) DEFAULT NULL,
		`total_likes` int(11) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`article_id`),
		KEY `date` (`date`),
		KEY `author_id` (`author_id`),
		FULLTEXT KEY `title` (`title`,`text`),
		FULLTEXT KEY `title_2` (`title`),
		FULLTEXT KEY `text` (`text`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;");

	$dbl->run("CREATE TABLE `articles_categorys` (
		`category_id` int(11) NOT NULL AUTO_INCREMENT,
		`category_name` varchar(32) CHARACTER SET utf8 NOT NULL,
		`quick_nav` tinyint(1) NOT NULL DEFAULT 0,
		`is_genre` tinyint(1) NOT NULL DEFAULT 0,
		`show_first` tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`category_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

	$dbl->run("CREATE TABLE `articles_comments` (
		`comment_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`article_id` int(11) NOT NULL,
		`author_id` int(11) NOT NULL,
		`guest_username` varchar(255) COLLATE utf8_bin DEFAULT NULL,
		`time_posted` int(11) NOT NULL,
		`comment_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
		`spam` tinyint(1) NOT NULL DEFAULT 0,
		`spam_report_by` int(11) DEFAULT NULL,
		`last_edited` int(11) NOT NULL DEFAULT 0,
		`last_edited_time` int(11) DEFAULT NULL,
		`edit_counter` int(11) NOT NULL DEFAULT 0,
		`approved` tinyint(1) NOT NULL DEFAULT 1,
		`total_likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
		`lock_timer` datetime DEFAULT NULL,
		`locked_by_id` int(10) UNSIGNED DEFAULT NULL,
		`promoted` tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`comment_id`),
		KEY `author_id` (`author_id`),
		KEY `article_id` (`article_id`),
		KEY `approved` (`approved`),
		KEY `last_edited` (`last_edited`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
	
	/* SETUP ADMIN ACCOUNT */

	// make a .lock file, if lock file present, don't let installer run - prevent overwrites
	$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/installer-lock.lock","wb");
	fwrite($fp,$content);
	fclose($fp);
	
	// redirect to the installed app
	echo 'All done, you can now use <a href="'.$_POST['site_url'].'">your website</a>. Please ensure you delete this file.';
}
?>
