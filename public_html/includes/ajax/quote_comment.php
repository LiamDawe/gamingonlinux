<?php
define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/minimal_bootstrap.php";

if(isset($_POST) && isset($_POST['type']))
{
	if ($_POST['type'] == 'article_comment')
	{
		$get_comment = $dbl->run("SELECT c.`comment_text`, u.`username` FROM `articles_comments` c LEFT JOIN `users` u ON u.user_id = c.author_id WHERE c.comment_id = ?", array($_POST['id']))->fetch();
		
		echo json_encode(array("result" => 'done', 'username' => $get_comment['username'], 'text' => $get_comment['comment_text']));
		return;
	}
	
	if ($_POST['type'] == 'forum_topic')
	{
		$get_comment = $dbl->run("SELECT p.`reply_text`, u.`username` FROM `forum_replies` p LEFT JOIN `users` u ON u.user_id = p.author_id WHERE p.topic_id = ? AND p.is_topic = 1", array($_POST['id']))->fetch();
		
		echo json_encode(array("result" => 'done', 'username' => $get_comment['username'], 'text' => $get_comment['reply_text']));
		return;
	}
	
	if ($_POST['type'] == 'forum_reply')
	{
		$get_comment = $dbl->run("SELECT r.`reply_text`, u.`username` FROM `forum_replies` r LEFT JOIN `users` u ON u.user_id = r.author_id WHERE r.post_id = ?", array($_POST['id']))->fetch();
		
		echo json_encode(array("result" => 'done', 'username' => $get_comment['username'], 'text' => $get_comment['reply_text']));
		return;
	}	
	
	if ($_POST['type'] == 'pm')
	{
		$get_comment = $dbl->run("SELECT m.`message`, u.`username` FROM `user_conversations_messages` m LEFT JOIN `users` u ON u.user_id = m.author_id WHERE m.message_id = ?", array($_POST['id']))->fetch();
		
		echo json_encode(array("result" => 'done', 'username' => $get_comment['username'], 'text' => $get_comment['message']));
		return;
	}
}
?>
