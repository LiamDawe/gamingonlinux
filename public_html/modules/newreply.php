<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	$_SESSION['message'] = "no_forum_reply_permission";
	header("Location: /forum/");
	die();
}

if (!isset($_GET['forum_id']) || !isset($_GET['topic_id']))
{
	$_SESSION['message'] = 'no_id';
	$_SESSION['message_extra'] = 'forum or topic';
	header("Location: /forum/");
	die();	
}

if ($core->config('forum_posting_open') == 1)
{
	$mod_queue = $user->user_details['in_mod_queue'];
	$forced_mod_queue = $user->can('forced_mod_queue');
	
	$forum_id = (int) $_GET['forum_id'];
	$topic_id = (int) $_GET['topic_id'];

	$parray = $forum_class->forum_permissions($forum_id);

	$name = $dbl->run("SELECT t.`topic_title`, t.`replys`, t.`is_locked`, t.`is_sticky`, f.`name`, f.`forum_id` FROM `forum_topics` t JOIN `forums` f ON t.`forum_id` = f.`forum_id` WHERE topic_id = ?", array($topic_id))->fetch();

	// check topic exists
	if (!$name)
	{
		$_SESSION['message'] = 'none_found';
		$_SESSION['message_extra'] = 'topics with that ID';
		header("Location: /forum/");
	}

	// permissions for forum
	if($parray['can_reply'] == 0)
	{
		$_SESSION['message'] = 'no_forum_reply_permission';
		header("Location: /forum/");
		die();
	}

	if (!isset($_POST['act']))
	{
		// check they don't already have a reply in the mod queue for this forum topic
		$check = $dbl->run("SELECT COUNT(`post_id`) FROM `forum_replies` WHERE `approved` = 0 AND `author_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $_GET['topic_id']))->fetchOne();

		if ($check == 0)
		{
			$subscribe_check = $user->check_subscription($_GET['topic_id'], 'forum');

			if (!isset($_SESSION['activated']))
			{
				$get_active = $dbl->run("SELECT `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
				$_SESSION['activated'] = $get_active['activated'];
			}

			if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
			{
				$templating->load('newreply');
				$templating->block('top', 'newreply');
				$templating->set('title', $name['topic_title']);
				$templating->set('topic_id', $topic_id);

				$templating->load('viewtopic');
				$templating->block('rules', 'viewtopic');
				$templating->set('url', $core->config('website_url'));
	
				$templating->block('reply_top', 'viewtopic');
				$templating->set('url', $core->config('website_url'));
				$templating->set('topic_id', $topic_id);
				$templating->set('forum_id', $_GET['forum_id']);

				if (isset($_GET['qid']) && is_numeric($_GET['qid']))
				{
					if ($_GET['type'] == 'topic')
					{
						$get_comment = $dbl->run("SELECT p.`reply_text`, u.`username` FROM `forum_replies` p LEFT JOIN `users` u ON u.user_id = p.author_id WHERE p.topic_id = ? AND p.is_topic = 1", array($_GET['qid']))->fetch();
					}
					
					if ($_GET['type'] == 'reply')
					{
						$get_comment = $dbl->run("SELECT r.`reply_text`, u.`username` FROM `forum_replies` r LEFT JOIN `users` u ON u.user_id = r.author_id WHERE r.post_id = ?", array($_GET['qid']))->fetch();
					}
				}

				$comment = '';
				if (isset($get_comment))
				{
					$comment = '[quote=' . $get_comment['username'] . ']' . $get_comment['reply_text'] . '[/quote]';
				}

				$core->editor(['name' => 'text', 'editor_id' => 'comment', 'content' => $comment]);

				$templating->block('reply_buttons', 'viewtopic');
				$templating->set('subscribe_check', $subscribe_check['auto_subscribe']);
				$templating->set('subscribe_email_check', $subscribe_check['emails']);
				$templating->set('url', url);
				$templating->set('topic_id', $_GET['topic_id']);
				$templating->set('forum_id', $_GET['forum_id']);

				$reply_options = 'Moderator options after posting: <select name="moderator_options"><option value=""></option>';
				$options_count = 0;

				if ($parray['can_sticky'] == 1)
				{
					if ($name['is_sticky'] == 1)
					{
						$reply_options .= '<option value="unsticky">Unsticky Topic</option>';
					}

					else
					{
						$reply_options .= '<option value="sticky">Sticky Topic</option>';
					}
					$options_count++;
				}

				if ($parray['can_lock'] == 1)
				{
					if ($name['is_locked'] == 1)
					{
						$reply_options .= '<option value="unlock">Unlock Topic</option>';
					}

					else
					{
						$reply_options .= '<option value="lock">Lock Topic</option>';
					}
					$options_count++;
				}

				if ($parray['can_sticky'] == 1 && $parray['can_lock'] == 1)
				{
					if ($name['is_locked'] == 1 && $name['is_sticky'] == 0)
					{
						$reply_options .= '<option value="bothunlock">Unlock & Sticky Topic</option>';
					}

					if ($name['is_sticky'] == 1 && $name['is_locked'] == 0)
					{
						$reply_options .= '<option value="bothunsticky">Lock & Unsticky Topic</option>';
					}

					if ($name['is_sticky'] == 1 && $name['is_locked'] == 1)
					{
						$reply_options .= '<option value="bothundo">Unlock & Unsticky Topic</option>';
					}

					if ($name['is_sticky'] == 0 && $name['is_locked'] == 0)
					{
						$reply_options .= '<option value="both">Lock & Sticky Topic</option>';
					}

					$options_count++;
				}

				if ($options_count > 0)
				{
					$reply_options .= '</select><br />';
				}

				// if they have no moderator abilitys then remove the select box altogether
				else
				{
					$reply_options = '';
				}

				$templating->set('moderator_options', $reply_options);

				$templating->block('preview', 'viewtopic');
			}
			else
			{
				$core->message('To reply you need to activate your account! You were sent an email with instructions on how to activate. <a href="/index.php?module=activate_user&redo=1">Click here to re-send a new activation key</a>');
			}
		}	
	}

	// if the user wants to make their reply
	if (isset($_POST['act']))
	{
		if ($_POST['act'] == 'Add')
		{
			if ($name['is_locked'] == 1 && $user->check_group([1,2]) == false)
			{
				$_SESSION['message'] = 'locked';
				$_SESSION['message_extra'] = 'forum post';
				header("Location: /forum/topic/".$topic_id.'/');
				die();
			}

			// make safe
			$message = core::make_safe($_POST['text']);
			$message = trim($message);
			$author = $_SESSION['user_id'];

			// check empty
			if (empty($message))
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'text';
				header("Location: /forum/topic/".$topic_id);
				die();
			}

			else
			{
				$mod_sql = '';
				if (!empty($_POST['moderator_options']))
				{
					if ($_POST['moderator_options'] == 'sticky')
					{
						$mod_sql = '`is_sticky` = 1,';
					}

					if ($_POST['moderator_options'] == 'unsticky')
					{
						$mod_sql = '`is_sticky` = 0,';
					}

					if ($_POST['moderator_options'] == 'lock')
					{
						$mod_sql = '`is_locked` = 1,';
					}

					if ($_POST['moderator_options'] == 'unlock')
					{
						$mod_sql = '`is_locked` = 0,';
					}

					if ($_POST['moderator_options'] == 'bothunlock')
					{
						$mod_sql = '`is_locked` = 0,`is_sticky` = 1,';
					}

					if ($_POST['moderator_options'] == 'bothunsticky')
					{
						$mod_sql = '`is_locked` = 1,`is_sticky` = 0,';
					}

					if ($_POST['moderator_options'] == 'bothundo')
					{
						$mod_sql = '`is_locked` = 0,`is_sticky` = 0,';
					}

					if ($_POST['moderator_options'] == 'both')
					{
						$mod_sql = '`is_locked` = 1,`is_sticky` = 1,';
					}
				}

				$approved = 1;
				if ($mod_queue == 1 || $forced_mod_queue == true)
				{
					$approved = 0;
				}

				// add the reply
				$dbl->run("INSERT INTO `forum_replies` SET `topic_id` = ?, `author_id` = ?, `reply_text` = ?, `creation_date` = ?, `approved` = ?, `is_topic` = 0", array($topic_id, $author, $message, core::$date, $approved));
				$post_id = $dbl->new_id();

				// update user post counter
				if ($approved == 1)
				{
					$dbl->run("UPDATE `users` SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($author));

					// update forums post counter and last post info
					$dbl->run("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($author, core::$date, $topic_id, $forum_id));

					// update topic reply count and last post info
					$dbl->run("UPDATE `forum_topics` SET `replys` = (replys + 1), `last_post_date` = ?, $mod_sql `last_post_user_id` = ?, `last_post_id` = ? WHERE `topic_id` = ?", array(core::$date, $author, $post_id, $topic_id));

					// get article name for the email and redirect
					$title = $dbl->run("SELECT `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($topic_id))->fetch();
						
					// see if they are subscribed right now, if they are and they untick the subscribe box, remove their subscription as they are unsubscribing
					if (!isset($_POST['subscribe']))
					{
						$sub_res = $dbl->run("SELECT `topic_id`, `emails`, `send_email` FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $topic_id))->fetch();
						if ($sub_res)
						{
							$dbl->run("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $topic_id));
						}
					}

					$new_notification_id = $notifications->quote_notification($message, $_SESSION['username'], $_SESSION['user_id'], array('type' => 'forum_reply', 'thread_id' => $topic_id, 'post_id' => $post_id));

					// are we subscribing?
					if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
					{
						$emails = 0;
						if ($_POST['subscribe-type'] == 'sub-emails')
						{
							$emails = 1;
						}

						$forum_class->subscribe($topic_id, $emails);
					}

					// email anyone subscribed which isn't you
					$users_array = array();
					$fetch_subs = $dbl->run("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`display_comment_alerts` FROM `forum_topics_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`topic_id` = ? AND s.user_id != ? AND NOT EXISTS (SELECT `user_id` FROM `user_block_list` WHERE `blocked_id` = ? AND `user_id` = s.user_id)", array($topic_id, $_SESSION['user_id'], $_SESSION['user_id']))->fetch_all();
					if ($fetch_subs)
					{
						foreach ($fetch_subs as $users_fetch)
						{
							if ($users_fetch['emails'] == 1 && $users_fetch['send_email'] == 1)
							{
								// use existing key, or generate any missing keys
								if (empty($users_fetch['secret_key']))
								{
									$secret_key = core::random_id(15);
									$dbl->run("UPDATE `forum_topics_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `topic_id` = ?", array($secret_key, $users_fetch['user_id'], $topic_id));
								}
								else
								{
									$secret_key = $users_fetch['secret_key'];
								}
								$users_array[$users_fetch['user_id']]['user_id'] = $users_fetch['user_id'];
								$users_array[$users_fetch['user_id']]['email'] = $users_fetch['email'];
								$users_array[$users_fetch['user_id']]['username'] = $users_fetch['username'];
								$users_array[$users_fetch['user_id']]['secret_key'] = $secret_key;
							}

							// notify them, if they haven't been quoted and already given one and they have comment notifications turned on
							if ($users_fetch['display_comment_alerts'] == 1)
							{
								if (isset($new_notification_id['quoted_usernames']) && !in_array($users_fetch['username'], $new_notification_id['quoted_usernames']) || !isset($new_notification_id['quoted_usernames']))
								{
									$get_note_info = $dbl->run("SELECT `id`, `forum_topic_id`, `seen` FROM `user_notifications` WHERE `forum_topic_id` = ? AND `owner_id` = ? AND `type` = 'forum_comment'", array($topic_id, $users_fetch['user_id']))->fetch();
							
									if (!$get_note_info)
									{
										$dbl->run("INSERT INTO `user_notifications` SET `owner_id` = ?, `notifier_id` = ?, `forum_topic_id` = ?, `forum_reply_id` = ?, `total` = 1, `type` = 'forum_comment'", array($users_fetch['user_id'], (int) $_SESSION['user_id'], $topic_id, $post_id));
										$new_notification_id[$users_fetch['user_id']] = $dbl->new_id();
									}
									else if ($get_note_info)
									{
										if ($get_note_info['seen'] == 1)
										{
											// they already have one, refresh it as if it's literally brand new (don't waste the row id)
											$dbl->run("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `last_date` = ?, `total` = 1, `seen_date` = NULL, `forum_reply_id` = ? WHERE `id` = ?", array($_SESSION['user_id'], core::$sql_date_now, $post_id, $get_note_info['id']));
										}
										else if ($get_note_info['seen'] == 0)
										{
											// they haven't seen the last one yet, so only update the time and date
											$dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `total` = (total + 1) WHERE `id` = ?", array(core::$sql_date_now, $get_note_info['id']));
										}
							
										$new_notification_id[$users_fetch['user_id']] = $get_note_info['id'];
									}
								}
							}
						}

						// send the emails
						foreach ($users_array as $users_fetch)
						{
							$clear_note = '';
							if (isset($new_notification_id[$users_fetch['user_id']]))
							{
								$clear_note = '/clear_note='.$new_notification_id[$users_fetch['user_id']];
							}

							$email_message = $bbcode->email_bbcode($message);

							// subject
							$subject = "New reply to forum post {$title['topic_title']} on GamingOnLinux.com";

							// message
							$html_message = "<p>Hello <strong>{$users_fetch['username']}</strong>,</p>
							<p><strong>{$_SESSION['username']}</strong> has replied to a forum topic you follow on titled \"<strong><a href=\"" . $core->config('website_url') . "forum/topic/{$topic_id}/post_id={$post_id}{$clear_note}\">{$title['topic_title']}</a></strong>\". There may be more replies after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
							<div>
							<hr>
							{$email_message}
							<hr>
							You can unsubscribe from this topic by <a href=\"" . $core->config('website_url') . "unsubscribe.php?user_id={$users_fetch['user_id']}&topic_id={$topic_id}&email={$users_fetch['email']}&secret_key={$users_fetch['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $core->config('website_url') . "usercp.php\">User Control Panel</a>.";
							
							$plain_message = "Hello {$users_fetch['username']}, {$_SESSION['username']} has replied to a forum topic you follow on titled {$title['topic_title']} find it here: " . $core->config('website_url') . 'forum/topic/' . $topic_id . '/post_id=' . $post_id . $clear_note;

							// Mail it
							if ($core->config('send_emails') == 1)
							{
								$mail = new mailer($core);
								$mail->sendMail($users_fetch['email'], $subject, $html_message, $plain_message);
							}

							// remove anyones send_emails subscription setting if they have it set to email once
							$update_sub = $dbl->run("SELECT `email_options` FROM `users` WHERE `user_id` = ?", array($users_fetch['user_id']))->fetch();

							if ($update_sub['email_options'] == 2)
							{
								$dbl->run("UPDATE `forum_topics_subscriptions` SET `send_email` = 0 WHERE `topic_id` = ? AND `user_id` = ?", array($topic_id, $users_fetch['user_id']));
							}
						}
					}
					// help stop double postings
					unset($message);

					header("Location: /forum/topic/{$topic_id}/post_id={$post_id}");
					die();
				}

				if ($approved == 0)
				{
					// help stop double postings
					unset($message);

					// get the title
					$topic_title = $dbl->run("SELECT t.`topic_title` FROM `forum_replies` p INNER JOIN `forum_topics` t ON t.`topic_id` = p.`topic_id` WHERE p.`post_id` = ?", array($post_id))->fetchOne();

					// add a new notification for the mod queue
					$core->new_admin_note(array('content' => ' has a new forum post in the mod queue for the topic titled: <a href="/admin.php?module=mod_queue&view=manage">'.$topic_title.'</a>.', 'type' => 'mod_queue_reply', 'data' => $post_id));
					
					$_SESSION['message'] = 'mod_queue';

					header("Location: " . $core->config('website_url') . "index.php?module=viewtopic&topic_id={$topic_id}");
					die();
				}
			}
		}
	}
}
else if ($core->config('forum_posting_open') == 0)
{
	$core->message('Posting is currently down for maintenance.');
}
?>
