<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('edit_comment');

if (isset($_GET['view']) && $_GET['view'] == 'Edit' && !isset($_POST['act']))
{
	$templating->set_previous('meta_data', '', 1);

	if (!isset($_GET['comment_id']) || isset($_GET['comment_id']) && !is_numeric($_GET['comment_id']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'comment';
		header('Location: /index.php');
		die();
	}

	$comment_id = strip_tags($_GET['comment_id']);

	$comment = $dbl->run("SELECT c.`author_id`, c.comment_id, c.`comment_text`, c.time_posted, c.`lock_timer`, c.`locked_by_id`, a.`title`, a.article_id, a.`slug`, a.`date` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $comment_id))->fetch();

	$nice_title = core::nice_title($comment['title']);

	// check if author
	if ($_SESSION['user_id'] != $comment['author_id'] && $user->can('mod_edit_comments') == false || !isset($_SESSION['user_id']))
	{
		header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		die();
	}

	if (!is_null($comment['lock_timer']) && strtotime("-5 minutes") <= strtotime($comment['lock_timer']) && $comment['locked_by_id'] != $_SESSION['user_id'])
	{
		$_SESSION['message'] = 'lock_timer';
		header("Location: /articles/$nice_title.{$comment['article_id']}");
		die();
	}

	$dbl->run("UPDATE `articles_comments` SET `lock_timer` = ?, `locked_by_id` = ? WHERE `comment_id` = ?", array(core::$sql_date_now, $_SESSION['user_id'], $comment_id));

	$templating->set_previous('meta_description', 'Editing a comment on GamingOnLinux', 1);
	$templating->set_previous('title', 'Editing a comment', 1);

	$comment_text = $comment['comment_text'];

	if (isset($_GET['error']))
	{
		$comment_text = $_SESSION['acomment'];
	}

	$page = 1;
	if (isset($_GET['page']) && is_numeric($_GET['page']))
	{
		$page = $_GET['page'];
	}

	$templating->block('edit_top', 'edit_comment');

	$comment_editor = new editor($core, $templating, $bbcode);
	$comment_editor->editor(['name' => 'text', 'content' => $comment_text, 'editor_id' => 'comment']);

	$templating->block('edit_comment_buttons', 'edit_comment');
	$templating->set('comment_id', $comment['comment_id']);
	$templating->set('url', $core->config('website_url'));
			
	$cancel_action = $article_class->article_link(array('date' => $comment['date'], 'slug' => $comment['slug'], 'additional' => 'comment_id='.$comment_id));

	$templating->set('cancel_action', $cancel_action);
	$templating->block('preview', 'edit_comment');
}
if (isset($_POST['act']) && $_POST['act'] == 'editcomment')
{
	$comment = $dbl->run("SELECT c.`author_id`, c.`comment_text`, c.`lock_timer`, c.`locked_by_id`, a.`title`, a.`article_id`, a.`slug`, a.`date` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_POST['comment_id']))->fetch();

	$nice_title = core::nice_title($comment['title']);

	if (!is_null($comment['lock_timer']) && strtotime("-5 minutes") <= strtotime($comment['lock_timer']) && $comment['locked_by_id'] != $_SESSION['user_id'])
	{
		$_SESSION['message'] = 'lock_timer';
		header("Location: /articles/$nice_title.{$comment['article_id']}");
		die();
	}

	// check if author or editor/admin
	if ($_SESSION['user_id'] != $comment['author_id'] && $user->can('mod_edit_comments') == false || !isset($_SESSION['user_id']))
	{
		
		header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		die();
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
			$article_link = $article_class->article_link(array('date' => $comment['date'], 'slug' => $comment['slug']));

			header("Location: " . $article_link);
			die();
		}

		// update comment
		else
		{
			$comment_text = core::make_safe($comment_text);

			$dbl->run("UPDATE `articles_comments` SET `comment_text` = ?, `last_edited` = ?, `last_edited_time` = ?, `locked_by_id` = NULL, `lock_timer` = NULL WHERE `comment_id` = ?", array($comment_text, (int) $_SESSION['user_id'], core::$date, (int) $_POST['comment_id']));
				
			$edit_redirect = $article_class->article_link(array('date' => $comment['date'], 'slug' => $comment['slug'], 'additional' => 'comment_id=' . $_POST['comment_id']));

			header("Location: ".$edit_redirect);
			die();
		}
	}
}
?>
