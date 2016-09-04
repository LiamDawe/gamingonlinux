<?php
$templating->merge('admin_blocks/admin_block_articles');
$templating->block('main');

// count any submitted admin articles for review
$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `admin_review` = 1");
$review_count = $db->num_rows();

if ($review_count > 0)
{
	$templating->set('review_count', "<span class=\"badge badge-important\">$review_count</span>");
}

else if ($review_count == 0)
{
	$templating->set('review_count', "($review_count)");
}

// count any submitted articles for review
$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `submitted_unapproved` = 1 AND `active` = 0");
$submitted_count = $db->num_rows();

if ($submitted_count > 0)
{
	$templating->set('submitted_count', "<span class=\"badge badge-important\">$submitted_count</span>");
}

else if ($submitted_count == 0)
{
	$templating->set('submitted_count', "($submitted_count)");
}

// count any spam reports on comments
$db->sqlquery("SELECT `comment_id` FROM `articles_comments` WHERE `spam` = 1");
$spam_count = $db->num_rows();

if ($spam_count > 0)
{
	$templating->set('spam_count', "<span class=\"badge badge-important\">$spam_count</span>");
}

else if ($spam_count == 0)
{
	$templating->set('spam_count', "($spam_count)");
}

// count any drafts you have
$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `draft` = 1");
$draft_count = $db->num_rows();

if ($draft_count > 0)
{
	$templating->set('draft_count', "<span class=\"badge badge-important\">$draft_count</span>");
}

else if ($draft_count == 0)
{
	$templating->set('draft_count', "($draft_count)");
}
