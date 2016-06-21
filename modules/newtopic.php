<?php
$templating->set_previous('title', 'Create new topic', 1);
if (!isset($_SESSION['activated']))
{
	$core->message('You do not have permission to post in this forum! Your account isn\'t activated!');
}
else
{
	if (core::config('forum_posting_open') == 1)
	{
		// get forum permissions
		$db->sqlquery("SELECT p.`forum_id`, f.`name` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE p.`can_topic` = 1 AND p.`group_id` IN ( ?, ? ) GROUP BY p.forum_id ORDER BY f.`name` ASC ", array($_SESSION['user_group'], $_SESSION['secondary_user_group']));
		if ($db->num_rows() == 0)
		{
			$core->message('You do not have permission to post in any forums!');
		}
		else
		{
			$forums_list = $db->fetch_all_rows();

			$show_forum_breadcrumb = 0;
			if (isset($_GET['forum_id']))
			{
				$core->forum_permissions($_GET['forum_id']);
				if ($parray['topic'] == 0)
				{
					$core->message('You do not have permission to post in that selected forum (you shouldn\'t even be able to get here with that forum id set), but you can post in others!', NULL, 1);
				}
				if ($parray['view'] == 1)
				{
					$show_forum_breadcrumb = 1;
				}
			}

			$tenMinAgo = time() - 600;
			$db->sqlquery("SELECT COUNT(author_id) as c FROM `forum_topics` WHERE `author_id` = ? AND `creation_date` >= ?", array($_SESSION['user_id'], $tenMinAgo));
			$amountOfPosts = $db->fetch();

			if ($amountOfPosts['c'] > 5)
			{
				if (core::config('pretty_urls') == 1)
				{
					header("Location: /forum/message=toomany");
				}
				else
				{
					header("Location: /index.php?module=forum&message=toomany");
				}
			}

			else if (!isset($_POST['act']))
			{
				$templating->merge('newtopic');

				$db->sqlquery("SELECT `name` FROM `forums` WHERE forum_id = ?", array($_GET['forum_id']));
				$name = $db->fetch();

				$title = '';
				$text = '';
				if (isset($_GET['error']))
				{
					if ($_GET['error'] == 'missing')
					{
						$core->message('You have to enter a title and message to post a new topic!', NULL, 1);
					}
					if ($_GET['error'] == 'moreoptions')
					{
						$core->message('Polls need at least two options!', NULL, 1);
					}
					if ($_GET['error'] == 'shorttitle')
					{
						$core->message('Title was too short!', NULL, 1);
					}

					$title = $_SESSION['atitle'];
					$text = $_SESSION['atext'];
				}

				$templating->block('main', 'newtopic');

				$forum_index = '';
				if (core::config('pretty_urls') == 1)
				{
					$forum_index = '/forum/';
				}
				else
				{
					$forum_index = '/index.php?module=forum';
				}
				$templating->set('forum_index', $forum_index);

				$crumbs = '';
				if ($show_forum_breadcrumb == 1)
				{
					if (core::config('pretty_urls') == 1)
					{
						$forum_url = '/forum/' . $_GET['forum_id'] . '/';
					}
					else
					{
						$forum_url = '/index.php?module=viewforum&forum_id=' . $_GET['forum_id'];
					}
					$crumbs = '<li><a href="'.$forum_url.'">'.$name['name'].'</a></li>';
				}
				$templating->set('crumbs', $crumbs);


				if (isset($parray) && !empty($parray))
				{
					$options = 'Moderator Options<br />
					<select name="moderator_options"><option value=""></option>';
					$options_count = 0;

					if ($parray['sticky'] == 1)
					{
						$options .= '<option value="sticky">Sticky Topic</option>';
						$options_count++;
					}

					if ($parray['lock'] == 1)
					{
						$options .= '<option value="lock">Lock Topic</option>';
						$options_count++;
					}

					if ($parray['sticky'] == 1 && $parray['lock'] == 1)
					{
						$options .= '<option value="both">Lock & Sticky Topic</option>';
						$options_count++;
					}

					if ($options_count > 0)
					{
						$options .= '</select><br />';
					}

					// if they have no moderator ability then remove the select box altogether
					else
					{
						$options = '';
					}
				}
				else
				{
					$options = '';
				}
				// see if we will allow them to make polls
				$db->sqlquery("SELECT `in_mod_queue` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
				$check_queue = $db->fetch();

				if ($check_queue['in_mod_queue'] == 0)
				{
					$templating->block('poll', 'newtopic');
				}

				$templating->block('top', 'newtopic');

				$cat_options = '';
				foreach ($forums_list as $cats)
				{
					$selected = '';
					if (isset($_GET['forum_id']))
					{
						if ($_GET['forum_id'] == $cats['forum_id'])
						{
							$selected = 'selected';
						}
					}
					$cat_options .= '<option value="'.$cats['forum_id'].'" '.$selected.'>'.$cats['name'].'</option>';
				}
				$templating->set('category_options', $cat_options);

				$core->editor('text', $text, $article_editor = 0, $disabled = 0, $anchor_name = 'commentbox', $ays_ignore = 1);

				$templating->block('bottom', 'newtopic');
				$templating->set('options', $options);
			}

			else if (isset($_POST['act']))
			{
				if ($_POST['act'] == 'Add')
				{
					// make safe
					$title = strip_tags($_POST['title']);
					$message = htmlspecialchars($_POST['text'], ENT_QUOTES);
					$message = trim($message);
					$author = $_SESSION['user_id'];

					$mod_sql = '';
					if (!empty($_POST['moderator_options']))
					{
						if ($_POST['moderator_options'] == 'sticky')
						{
							$mod_sql = '`is_sticky` = 1,';
						}

						if ($_POST['moderator_options'] == 'lock')
						{
							$mod_sql = '`is_locked` = 1,';
						}

						if ($_POST['moderator_options'] == 'both')
						{
							$mod_sql = '`is_locked` = 1,`is_sticky` = 1,';
						}
					}

					// check empty
					if (empty($title) || empty($message))
					{
						$_SESSION['atitle'] = $title;
						$_SESSION['atext'] = $message;

						header("Location: /index.php?module=newtopic&forum_id={$_POST['category']}&error=missing");
					}

					else if (strlen($title) < 4)
					{
						$_SESSION['atitle'] = $title;
						$_SESSION['atext'] = $message;

						header("Location: /index.php?module=newtopic&forum_id={$_POST['category']}&error=shorttitle");
					}

					else
					{
						// see if we need to add it into the mod queue
						$db->sqlquery("SELECT `in_mod_queue` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
						$check_queue = $db->fetch();

						$approved = 1;
						if ($check_queue['in_mod_queue'] == 1)
						{
							$approved = 0;
						}

						// update user post counter
						if ($approved == 1)
						{
							$db->sqlquery("UPDATE `users` SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($author));
						}

						// add the topic
						$db->sqlquery("INSERT INTO `forum_topics` SET `forum_id` = ?, `author_id` = ?, $mod_sql `topic_title` = ?, `topic_text` = ?, `creation_date` = ?, `last_post_date` = ?, `last_post_id` = ?, `approved` = ?", array($_POST['category'], $author, $title, $message, core::$date, core::$date, $author, $approved));
						$topic_id = $db->grab_id();

						// update forums post counter and last post info
						if ($approved == 1)
						{
							$db->sqlquery("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($author, core::$date, $topic_id, $_POST['category']));
						}

						// if they are subscribing
						if (isset($_POST['subscribe']))
						{
							$db->sqlquery("INSERT INTO `forum_topics_subscriptions` SET `user_id` = ?, `topic_id` = ?", array($_SESSION['user_id'], $topic_id));
						}

						if ($approved == 1)
						{
							// input any polls as required
							if (isset($_POST['pquestion']))
							{
								if (!empty($_POST['pquestion']))
								{
									// if there's actually two or more options (let's not allow single question or broken polls eh!)
									if (count($_POST['poption']) >= 2)
									{
										$db->sqlquery("INSERT INTO `polls` SET `author_id` = ?, `poll_question` = ?, `topic_id` = ?", array($_SESSION['user_id'], $_POST['pquestion'], $topic_id));
										$poll_id = $db->grab_id();

										foreach ($_POST['poption'] as $option)
										{
											// don't add in empty left-over options for voting
											$option = trim($option);

											if (!empty($option))
											{
												$db->sqlquery("INSERT INTO `poll_options` SET `poll_id` = ?, `option_title` = ?", array($poll_id, $option));
											}
										}
									}
									else
									{
										$_SESSION['atitle'] = $title;
										$_SESSION['atext'] = $message;

										header("Location: /index.php?module=newtopic&forum_id={$_POST['category']}&error=moreoptions");
										die();
									}
								}
							}

							unset($_SESSION['atitle']);
							unset($_SESSION['atext']);

							if ($config['pretty_urls'] == 1)
							{
								header("Location: /forum/topic/{$topic_id}");
							}
							else {
								header("Location: " . core::config('website_url') . "index.php?module=viewtopic&topic_id={$topic_id}");
							}
						}

						else if ($approved == 0)
						{
							$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `created` = ?, `topic_id` = ?, `mod_queue` = 1", array("A new forum topic was added to the moderation queue", core::$date, $topic_id));

							header("Location: " . core::config('website_url') . "index.php?module=viewforum&forum_id={$_POST['category']}&message=queue");
						}
					}
				}
			}
		}
	}
	else if (core::config('forum_posting_open') == 0)
	{
		$core->message('Posting is currently down for maintenance.');
	}
}
?>
