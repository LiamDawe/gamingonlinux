<?php
/* need to adjust the file for reading/saving */
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );
define('golapp', TRUE);

require APP_ROOT . "/includes/bootstrap.php";

if (isset($_POST))
{
	if ($_POST['type'] == 'add')
	{
		$comment_return = $article_class->add_comment();

		if (isset($comment_return['error']) && $comment_return['error'] == 1)
		{
			$extra = NULL;
			if (isset($comment_return['message_extra']))
			{
				$extra = $comment_return['message_extra'];
			}
			header('Content-Type: application/json');
			$message_output = $message_map->display_message('articles_full', $comment_return['message'], NULL, 'return_plain');
			echo json_encode(array("result" => 'error', "message" => $message_output, 'message_extra' => $extra));
			die();
		}
		else if (isset($comment_return['result']) && $comment_return['result'] == 'done')
		{
			header('Content-Type: application/json');
			echo json_encode($comment_return);
			die();
		}
		else if (isset($comment_return['result']) && $comment_return['result'] == 'approvals')
		{
			header('Content-Type: application/json');
			$message_output = $message_map->display_message('articles_full', 'mod_queue', NULL, 'return_plain');
			echo json_encode(array_merge($comment_return, ['message' => $message_output]));
			die();	
		}
	}
	if ($_POST['type'] == 'reload')
	{
		$templating->load('articles_full');
		
		$article_info = $dbl->run("SELECT `article_id`, `slug`, `comments_open`, `comment_count`, `date` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();

		$article_link_main = $article_class->article_link(array('date' => $article_info['date'], 'slug' => $article_info['slug']));
		
		$article_class->display_comments(['article' => $article_info, 'pagination_link' => $article_link_main . '/', 'page' => $_POST['page'], 'type' => $_POST['area']]);

		echo $templating->output();
	}
}