<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) );

require APP_ROOT . "/includes/bootstrap.php";

$get_comments = $dbl->run("SELECT `comment_id` FROM `articles_comments` ORDER BY `comment_id` DESC")->fetch_all();
foreach ($get_comments as $comment)
{
	$total_likes = $dbl->run("SELECT COUNT(like_id) as `total` FROM `likes` WHERE `data_id` = ?", array($comment['comment_id']))->fetchOne();

	$dbl->run("UPDATE `articles_comments` SET `total_likes` = ? WHERE `comment_id` = ?", array($total_likes, $comment['comment_id']));
	
	echo "Comment " . $comment['comment_id'] . " updated!\n";
}

echo 'Done';
?>