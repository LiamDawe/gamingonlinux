<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) );

require APP_ROOT . "/includes/bootstrap.php";

// amount of forum posts to make
$article_total = 1000;

// find existing forum ids to populate at random
$forum_ids = $dbl->run("SELECT `forum_id` FROM `forums` WHERE `is_category` = 0")->fetch_all(PDO::FETCH_COLUMN);
$user_ids = $dbl->run("SELECT `user_id` FROM `users`")->fetch_all(PDO::FETCH_COLUMN);

for ($i = 1; $i <= $article_total; $i++)
{
	// amount of comments each article should get
	$comments_total = rand(500,1000);

	// generate the article
	$title = 'This is a test title from the make forum post tool ' . $i;
	$text = "Lorem Ipsum is simply <em>dummy text</em> of the printing and <u>typesetting industry</u>. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more [b]recently with desktop publishing software[/b] like Aldus PageMaker including versions of Lorem Ipsum.";

	$picked_forum = $forum_ids[array_rand($forum_ids)];
	$picked_user = $user_ids[array_rand($user_ids)];

	// add the topic
	$dbl->run("INSERT INTO `forum_topics` SET `forum_id` = ?, `author_id` = ?, `topic_title` = ?, `creation_date` = ?, `last_post_date` = ?, `last_post_user_id` = ?, `approved` = ?", array($picked_forum, $picked_user, $title, core::$date, core::$date, $picked_user, 1));
	$topic_id = $dbl->new_id();

	// add the post for the topic
	$dbl->run("INSERT INTO `forum_replies` SET `topic_id` = ?, `author_id` = ?, `creation_date` = ?, `reply_text` = ?, `approved` = ?, `is_topic` = 1", array($topic_id, $picked_user, core::$date, $text, 1));

	$dbl->run("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($picked_user, core::$date, $topic_id, $picked_forum));

	// generate comments for those articles
	for ($c = 1; $c <= $comments_total; $c++)
	{
		$reply_user = $user_ids[array_rand($user_ids)];

		$comment_text = "Lorem Ipsum is simply [u]dummy text[/u] of the printing and [i]typesetting industry[/i]. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more [b]recently with desktop publishing software[/b] like Aldus PageMaker including versions of Lorem Ipsum.";

		$dbl->run("INSERT INTO `forum_replies` SET `topic_id` = ?, `author_id` = ?, `reply_text` = ?, `creation_date` = ?, `approved` = ?, `is_topic` = 0", array($topic_id, $reply_user, $comment_text, core::$date, 1));
		$post_id = $dbl->new_id();

		$dbl->run("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($reply_user, core::$date, $topic_id, $picked_forum));

		$dbl->run("UPDATE `forum_topics` SET `replys` = (replys + 1), `last_post_date` = ?, `last_post_user_id` = ?, `last_post_id` = ? WHERE `topic_id` = ?", array(core::$date, $reply_user, $post_id, $topic_id));
	}

	echo 'Forum topic and replies ' . $i . ' done.'.PHP_EOL;
}
echo 'Done';
?>
