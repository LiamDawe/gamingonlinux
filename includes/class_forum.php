<?php
class forum_class
{
	// this will subscribe them to an article and generate any possible missing secret key for emails
	function subscribe($topic_id, $emails = NULL)
	{
		global $db;

		if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
		{
			$db->sqlquery("SELECT `user_id`, `topic_id`, `secret_key`, `emails` FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $topic_id));
			$count_subs = $db->num_rows();
			if ($count_subs == 0)
			{
				// have we been given an email option, if so use it
				if ($emails == NULL)
				{
					// find how they like to normally subscribe
					$db->sqlquery("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
					
					$get_email_type = $db->fetch();
					
					$sql_emails = $get_email_type['auto_subscribe_email'];
				}
				else
				{
					$sql_emails = (int) $emails;
				}
        
				// for unsubscribe link in emails
				$secret_key = core::random_id(15);

				$db->sqlquery("INSERT INTO `forum_topics_subscriptions` SET `user_id` = ?, `topic_id` = ?, `emails` = ?, `send_email` = ?, `secret_key` = ?", array($_SESSION['user_id'], $topic_id, $sql_emails, $sql_emails, $secret_key));
			}
			else if ($count_subs == 1)
			{
				$get_key = $db->fetch();
				if (empty($get_key['secret_key']))
				{
					// for unsubscribe link in emails
					$secret_key = core::random_id(15);
					$db->sqlquery("UPDATE `forum_topics_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `topic_id` = ?", array($secret_key, $_SESSION['user_id'], $topic_id));
				}
			}
		}
	}
}
?>
