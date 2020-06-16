<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('admin_blocks/articles');
$templating->block('main');

// count any submitted admin articles for review
$article_issues = $dbl->run("SELECT (SELECT COUNT(`article_id`) FROM `articles` WHERE `admin_review` = 1) + (SELECT COUNT(`article_id`) FROM `articles` WHERE `submitted_unapproved` = 1 AND `active` = 0) + (SELECT COUNT(`row_id`) FROM `article_corrections`) as total")->fetchOne();

if ($article_issues > 0)
{
	$templating->set('manage_counter', "<span class=\"badge badge-important\">$article_issues</span>");
}
else
{
	$templating->set('manage_counter', "(0)");
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