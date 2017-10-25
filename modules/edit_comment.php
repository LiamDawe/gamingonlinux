<?php
$templating->load('edit_comment');

if (isset($_GET['view']) && $_GET['view'] == 'Edit' && !isset($_POST['act']))
{
	$templating->set_previous('meta_data', '', 1);

	$comment = $dbl->run("SELECT c.`author_id`, c.comment_id, c.`comment_text`, c.time_posted, a.`title`, a.article_id FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_GET['comment_id']))->fetch();

	$nice_title = core::nice_title($comment['title']);

	// check if author
	if ($_SESSION['user_id'] != $comment['author_id'] && $user->can('mod_edit_comments') == false || $_SESSION['user_id'] == 0)
	{
		header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		die();
	}

	$templating->set_previous('meta_description', 'Editing a comment on GamingOnLinux', 1);
	$templating->set_previous('title', 'Editing a comment', 1);

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

	$templating->block('edit_top', 'edit_comment');

	$core->editor(['name' => 'text', 'content' => $comment_text, 'editor_id' => 'comment']);

	$templating->block('edit_comment_buttons', 'edit_comment');
	$templating->set('comment_id', $comment['comment_id']);
	$templating->set('url', $core->config('website_url'));
		
	$cancel_action = $article_class->get_link($comment['article_id'], $nice_title);

	$templating->set('cancel_action', $cancel_action);
	$templating->block('preview', 'edit_comment');
}
if (isset($_POST['act']) && $_POST['act'] == 'editcomment')
{
	$comment = $dbl->run("SELECT c.`author_id`, c.`comment_text`, a.`title`, a.`article_id`, a.`slug` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_POST['comment_id']))->fetch();

	// check if author or editor/admin
	if ($_SESSION['user_id'] != $comment['author_id'] && $user->can('mod_edit_comments') == false || $_SESSION['user_id'] == 0)
	{
		$nice_title = core::nice_title($comment['title']);
		header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
	}

	// do the edit since we are allowed
	else
	{
		$comment_text = trim($_POST['text']);
		// check empty
		if (empty($comment_text))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'text';
			$article_link = $article_class->get_link($comment['article_id'], $comment['slug']);

			header("Location: " . $article_link);
			die();
		}

		// update comment
		else
		{
			$comment_text = core::make_safe($comment_text);

			$dbl->run("UPDATE `articles_comments` SET `comment_text` = ?, `last_edited` = ?, `last_edited_time` = ?, `edit_counter` = (edit_counter + 1) WHERE `comment_id` = ?", array($comment_text, (int) $_SESSION['user_id'], core::$date, (int) $_POST['comment_id']));
				
			$edit_redirect = $article_class->get_link($comment['article_id'], $comment['slug'], 'comment_id=' . $_POST['comment_id']);

			header("Location: ".$edit_redirect);
		}
	}
}
?>
