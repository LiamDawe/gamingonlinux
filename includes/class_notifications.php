<?php
class notifications
{
	// the required database connection
	private $dbl;
	// the requred core class
	private $core;
	
	function __construct($dbl, $core)
	{
		$this->dbl = $dbl;
		$this->core = $core;
	}
	
	// give a user a notification if their name was quoted in a post somewhere
	function quote_notification($text, $username, $author_id, $extra_data)
	{
		// $author_id, $article_id, $comment_id
		$new_notification_id = array();
			
		$pattern = '/\[quote\=(.+?)\](.+?)\[\/quote\]/is';
		preg_match($pattern, $text, $matches);
	
		if (!empty($matches[1]))
		{
			if ($matches[1] != $username)
			{
				$quoted_user_id = $this->dbl->run("SELECT `user_id` FROM `users` WHERE `username` = ?", array($matches[1]))->fetchOne();
				if ($quoted_user_id)
				{
					if ($extra_data['type'] == 'article_comment')
					{
						$field1 = 'article_id';
						$field2 = 'comment_id';
					}
					if ($extra_data['type'] == 'forum_reply')
					{
						$field1 = 'forum_topic_id';
						$field2 = 'forum_reply_id';						
					}
					$this->dbl->run("INSERT INTO `user_notifications` SET `seen` = 0, `owner_id` = ?, `notifier_id` = ?, `$field1` = ?, `$field2` = ?, `type` = 'quoted'", array($quoted_user_id, $author_id, $extra_data['thread_id'], $extra_data['post_id']));
					$new_notification_id[$quoted_user_id] = $this->dbl->new_id();
					$new_notification_id['quoted_username'] = $matches[1];
				}
			}
		}
			
		return $new_notification_id;
	}
}
?>
