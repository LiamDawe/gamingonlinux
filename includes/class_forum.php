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
				// for unsubscribe link in emails
				if (empty($get_key['secret_key']))
				{
					$secret_key = core::random_id(15);
				}
				else
				{
					$secret_key = $get_key['secret_key'];
				}
				
				// check over their email options on this new subscription
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

				$db->sqlquery("UPDATE `forum_topics_subscriptions` SET `secret_key` = ?, `emails` = ?, `send_email` = ? WHERE `user_id` = ? AND `topic_id` = ?", array($secret_key, $sql_emails, $sql_emails, $_SESSION['user_id'], $topic_id));
			}
		}
	}
	
	public static function delete_topic($return_page_done = NULL, $return_page_no = NULL, $post_page = NULL)
	{
		global $core, $db, $parray, $templating;
		
		if (!isset($_GET['forum_id']) || !isset($_GET['author_id']) || !isset($_GET['topic_id']))
		{
			header('Location: ' . $return_page_no);
			die();
		}
		
		if (!core::is_number($_GET['forum_id']) || !core::is_number($_GET['author_id']) || !core::is_number($_GET['topic_id']))
		{
			header('Location: ' . $return_page_no);
			die();
		}

		$core->forum_permissions($_GET['forum_id']);
		if ($parray['delete'] == 0 || !isset($parray['delete']))
		{
			header('Location: ' . $return_page_no);
			die();
		}
		
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			
			$templating->set_previous('title', 'Deleting a forum topic', 1);
			$core->yes_no('Are you sure you want to delete that topic?', $post_page, 'delete_topic');
		}

		else if (isset($_POST['no']))
		{
			header("Location: " . $return_page_no);
		}

		else if (isset($_POST['yes']))
		{
			// check if its been reported first so we can remove the report
			$db->sqlquery("SELECT `reported`, `replys` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']));
			$check = $db->fetch();

			if ($check['reported'] == 1)
			{
				$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'forum_topic_report' AND `data` = ?", array(core::$date, $_GET['topic_id']));
			}

			// delete any replies that may have been reported from the admin notifications
			if ($check['replys'] > 0)
			{
				$db->sqlquery("SELECT `post_id`, `reported` FROM `forum_replies` WHERE `topic_id` = ?", array($_GET['topic_id']));
				$get_replies = $db->fetch_all_rows();

				foreach ($get_replies as $delete_replies)
				{
					if ($delete_replies['reported'] == 1)
					{
						$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'forum_reply_report' AND `data` = ?", array(core::$date, $delete_replies['post_id']));
					}
				}
			}

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'delete_forum_topic', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_GET['topic_id']));

			// count all posts including the topic
			$db->sqlquery("SELECT `post_id` FROM `forum_replies` WHERE `topic_id` = ?", array($_GET['topic_id']));
			$total_count = $db->num_rows() + 1;

			// Here we get each person who has posted along with their post count for the topic ready to remove it from their post count sql
			$db->sqlquery("SELECT `author_id` FROM `forum_replies` WHERE `topic_id` = ?", array($_GET['topic_id']));
			$posts = $db->fetch_all_rows();

			$users_posts = array();
			foreach ($posts as $post)
			{
				$db->sqlquery("SELECT `post_id` FROM `forum_replies` WHERE `author_id` = ? AND `topic_id` = ?", array($post['author_id'], $_GET['topic_id']));
				$user_post_count = $db->num_rows();

				$users_posts[$post['author_id']]['author_id'] = $post['author_id'];
				$users_posts[$post['author_id']]['posts'] = $user_post_count;
			}

			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']));

			// now we can remove all replys
			$db->sqlquery("DELETE FROM `forum_replies` WHERE `topic_id` = ?", array($_GET['topic_id']));

			// now update each users post count
			foreach($users_posts as $post)
			{
				$db->sqlquery("UPDATE `users` SET `forum_posts` = (forum_posts - ?) WHERE `user_id` = ?", array($post['posts'], $post['author_id']));
			}

			// remove a post from the topic author for the topic post itself
			$db->sqlquery("UPDATE `users` SET `forum_posts` = (forum_posts - 1) WHERE `user_id` = ?", array($_GET['author_id']));

			// now update the forums post count
			$db->sqlquery("UPDATE `forums` SET `posts` = (posts - ?) WHERE `forum_id` = ?", array($total_count, $_GET['forum_id']));

			// finally check if this is the latest topic we are deleting to update the latest topic info for the forum
			$db->sqlquery("SELECT `last_post_topic_id` FROM `forums` WHERE `forum_id` = ?", array($_GET['forum_id']));
			$last_post = $db->fetch();

			// if it is then we need to get the *now* newest topic and update the forums info
			if ($last_post['last_post_topic_id'] == $_GET['topic_id'])
			{
				$db->sqlquery("SELECT `topic_id`, `last_post_date`, `last_post_id` FROM `forum_topics` WHERE `forum_id` = ? ORDER BY `last_post_date` DESC LIMIT 1", array($_GET['forum_id']));
				$new_info = $db->fetch();

				$db->sqlquery("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($new_info['last_post_date'], $new_info['last_post_id'], $new_info['topic_id'], $_GET['forum_id']));
			}

			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'post';
			header("Location: " . $return_page_done);
		}
	}
	
	public static function delete_reply($return_page_done = NULL, $return_page_no = NULL, $post_page = NULL)
	{
		global $core, $db, $parray, $templating;
		
		if (!isset($_GET['forum_id']) || !isset($_GET['post_id']) || !isset($_GET['topic_id']))
		{
			header('Location: ' . $return_page_no);
			die();
		}
		
		if (!core::is_number($_GET['forum_id']) || !core::is_number($_GET['post_id']) || !core::is_number($_GET['topic_id']))
		{
			header('Location: ' . $return_page_no);
			die();
		}

		$core->forum_permissions($_GET['forum_id']);
		if ($parray['delete'] == 0 || !isset($parray['delete']))
		{
			header('Location: ' . $return_page_no);
			die();
		}
		
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			
			$templating->set_previous('title', 'Deleting a forum post', 1);
			$core->yes_no('Are you sure you want to delete that forum post?', $post_page, 'delete_topic');
		}

		else if (isset($_POST['no']))
		{
			header("Location: " . $return_page_no);
		}
		
		else if (isset($_POST['yes']))
		{
			// Get the info from the post
			$db->sqlquery("SELECT r.author_id, r.reported, t.forum_id FROM `forum_replies` r INNER JOIN `forum_topics` t ON r.topic_id = t.topic_id WHERE r.`post_id` = ?", array($_GET['post_id']));
			$post_info = $db->fetch();

			// remove the post
			$db->sqlquery("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']));

			// update admin notifications
			if ($post_info['reported'] == 1)
			{
				$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'forum_reply_report' AND `data` = ?", array(core::$date, $_GET['post_id']));
			}

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'delete_forum_reply', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_GET['post_id']));

			// update the authors post count
			if ($post_info['author_id'] != 0)
			{
				$db->sqlquery("UPDATE `users` SET `forum_posts` = (forum_posts - 1) WHERE `user_id` = ?", array($post_info['author_id']));
			}

			// now update the forums post count
			$db->sqlquery("UPDATE `forums` SET `posts` = (posts - 1) WHERE `forum_id` = ?", array($post_info['forum_id']));

			// update the topics info, get the newest last post and update the topics last info with that ones
			$db->sqlquery("SELECT `creation_date`, `author_id`, `guest_username` FROM `forum_replies` WHERE `topic_id` = ? ORDER BY `post_id` DESC LIMIT 1", array($_GET['topic_id']));
			$topic_info = $db->fetch();

			$db->sqlquery("UPDATE `forum_topics` SET `replys` = (replys - 1), `last_post_date` = ?, `last_post_id` = ? WHERE `topic_id` = ?", array($topic_info['creation_date'], $topic_info['author_id'], $_GET['topic_id']));

			// finally check if this is the latest topic we are deleting to update the latest topic info for the forum
			$db->sqlquery("SELECT `last_post_topic_id` FROM `forums` WHERE `forum_id` = ?", array($post_info['forum_id']));
			$last_post = $db->fetch();

			// if it is then we need to get the *now* newest topic and update the forums info
			if ($last_post['last_post_topic_id'] == $_GET['topic_id'])
			{
				$db->sqlquery("SELECT `topic_id`, `last_post_date`, `last_post_id` FROM `forum_topics` WHERE `forum_id` = ? ORDER BY `last_post_date` DESC LIMIT 1", array($post_info['forum_id']));
				$new_info = $db->fetch();

				$db->sqlquery("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($new_info['last_post_date'], $new_info['last_post_id'], $new_info['topic_id'], $post_info['forum_id']));
			}
			
			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'post';
			header("Location: " . $return_page_done);
		}
	}
	
	public static function get_link($id, $additional = NULL)
	{
		$link = '';
		
		if (core::config('pretty_urls') == 1)
		{
			$link = 'forum/topic/'.$id;
			
			if ($additional != NULL)
			{
				$link = $link . '/' . $additional;
			}
		}
		else
		{
			$link = 'index.php?module=viewtopic&topic_id='.$id;
			
			if ($additional != NULL)
			{
				$link = $link . '&' . $additional;
			}
		}
		return core::config('website_url') . $link;
	}
}
?>
