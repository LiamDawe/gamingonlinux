<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_GET['comment_id']))
{
	$comment = $dbl->run("SELECT c.`author_id`, c.comment_id, c.`comment_text`, c.time_posted, a.`title`, a.article_id FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_GET['comment_id']))->fetch();

	$nice_title = core::nice_title($comment['title']);

	// check if author
	if ($_SESSION['user_id'] != $comment['author_id'] && $user->can('mod_edit_comments') == false || $_SESSION['user_id'] == 0)
	{
		die();
	}

	$comment_text = $comment['comment_text'];

	if (isset($_GET['error']))
	{
		$comment_text = $_SESSION['acomment'];
	}

	$page = 1;
	if (isset($_GET['page']))
	{
		$page = $_GET['page'];
	}

	$templating->load('edit_comment');
	$buttons = $templating->block_store('edit_ajax_buttons', 'edit_comment');
	$buttons = $templating->store_replace($buttons, ['comment_id' => $_GET['comment_id']]);

	$core->editor(['type' => 'simple', 'name' => 'text', 'content' => $comment_text, 'editor_id' => 'ajax_comment_edit', 'buttons' => $buttons]);

	$templating->block('preview', 'edit_comment');

	echo $templating->output();
}
