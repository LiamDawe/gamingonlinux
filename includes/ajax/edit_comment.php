<?php
/* need to adjust the file for reading/saving */
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_POST['comment_id']))
{
	$comment = $dbl->run("SELECT `author_id`, `comment_id`, `comment_text` FROM `articles_comments` WHERE `comment_id` = ?", array((int) $_POST['comment_id']))->fetch();

	// check if author
	if ($_SESSION['user_id'] != $comment['author_id'] && $user->can('mod_edit_comments') == false || !isset($_SESSION['user_id']))
	{
		die();
	}

	if (isset($_POST['type']) && $_POST['type'] == 'show_plain')
	{
		$comment_text = $comment['comment_text'];

		$templating->load('edit_comment');
		$buttons = $templating->block_store('edit_ajax_buttons', 'edit_comment');
		$buttons = $templating->store_replace($buttons, ['comment_id' => $_POST['comment_id']]);

		$core->editor(['type' => 'simple', 'name' => 'text', 'content' => $comment_text, 'editor_id' => 'ajax_comment_edit', 'buttons' => $buttons]);

		$templating->block('preview', 'edit_comment');

		echo $templating->output();
	}
	if (isset($_POST['type']) && $_POST['type'] == 'do_edit')
	{
		$comment_text = trim($_POST['text']);
		// check empty
		if (empty($comment_text))
		{
			header('Content-Type: application/json');
			$message = $message_map->display_message('main', 'empty', 'text', 1);
			echo json_encode(array("error" => 1, "message" => $message));
			return;
		}

		else
		{

		}
	}
}
