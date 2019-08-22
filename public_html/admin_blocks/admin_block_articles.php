<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('admin_blocks/articles');
$templating->block('main');

// count any submitted admin articles for review
$review_count = $dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE `admin_review` = 1")->fetchOne();
if ($review_count > 0)
{
	$templating->set('review_count', "<span class=\"badge badge-important\">$review_count</span>");
}
else if ($review_count == 0)
{
	$templating->set('review_count', "(0)");
}

// count any submitted articles for review
$submitted_count = $dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE `submitted_unapproved` = 1 AND `active` = 0")->fetchOne();
if ($submitted_count > 0)
{
	$templating->set('submitted_count', "<span class=\"badge badge-important\">$submitted_count</span>");
}
else if ($submitted_count == 0)
{
	$templating->set('submitted_count', "(0)");
}

// count any spam reports on comments
$spam_count = $dbl->run("SELECT COUNT(`comment_id`) FROM `articles_comments` WHERE `spam` = 1")->fetchOne();
if ($spam_count > 0)
{
	$templating->set('spam_count', "<span class=\"badge badge-important\">$spam_count</span>");
}
else if ($spam_count == 0)
{
	$templating->set('spam_count', "(0)");
}

// count any drafts you have
$draft_count = $dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE `draft` = 1")->fetchOne();
if ($draft_count > 0)
{
	$templating->set('draft_count', "<span class=\"badge badge-important\">$draft_count</span>");
}
else if ($draft_count == 0)
{
	$templating->set('draft_count', "(0)");
}

// correction counter
$correction_counter = $dbl->run("SELECT COUNT(`row_id`) FROM `article_corrections`")->fetchOne();
if ($correction_counter > 0)
{
	$templating->set('correction_count', "<span class=\"badge badge-important\">$correction_counter</span>");
}
else if ($correction_counter == 0)
{
	$templating->set('correction_count', "(0)");
}
