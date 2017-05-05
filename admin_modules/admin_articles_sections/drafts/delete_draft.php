<?php
$db->sqlquery("SELECT `article_id`, `date`, `author_id`, `title`, 'tagline_image', `active` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
$check = $db->fetch();

// anti-cheese deleting the wrong article feature, not sure this is even needed any more?
if ($check['active'] == 1)
{
	$core->message("WARNING: You are about to delete a live article! THIS IS OBVIOUSLY A BUG AS THIS IS IN THE DRAFTS SECTION, REPORT THIS, DO NOT IGNORE THIS MESSAGE.", NULL, 1);
}

if (!isset($_POST['yes']) && !isset($_POST['no']))
{
	$templating->set_previous('title', 'Deleting a draft article', 1);
	$core->yes_no('Are you sure you want to delete that draft?', $core->config('website_url') . "admin.php?module=articles", 'delete_draft', $_POST['article_id'], 'article_id');
}
else if (isset($_POST['no']))
{
	header("Location: " . $core->config('website_url') . "admin.php?module=articles&view=drafts");
}
else if (isset($_POST['yes']))
{
	$article_class->delete_article($check);

	header("Location: " . $core->config('website_url') . "admin.php?module=articles&view=drafts&message=deleted&extra=draft");
}
