<?php
$templating->set_previous('title', 'Create new topic', 1);
if ($_SESSION['user_id'] > 0 && !isset($_SESSION['activated']))
{
	$core->message('You do not have permission to post in this forum! Your account isn\'t activated!');
}
else
{
	$mod_queue = $user->get('in_mod_queue', $_SESSION['user_id']);
	$forced_mod_queue = $user->can('forced_mod_queue');
				
	if ($core->config('forum_posting_open') == 1)
	{
		$in = str_repeat('?,', count($user->user_groups) - 1) . '?';
		
		// get forum permissions
		$db->sqlquery("SELECT p.`forum_id`, f.`name` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE p.`can_topic` = 1 AND p.`group_id` IN ($in) GROUP BY p.forum_id ORDER BY f.`name` ASC ", $user->user_groups);
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
				$forum_class->forum_permissions($_GET['forum_id']);
				if ($parray['can_topic'] == 0)
				{
					$core->message('You do not have permission to post in that selected forum (you shouldn\'t even be able to get here with that forum id set), but you can post in others!', 1);
				}
				if ($parray['can_view'] == 1)
				{
					$show_forum_breadcrumb = 1;
				}
			}

			$tenMinAgo = time() - 600;
			$db->sqlquery("SELECT COUNT(author_id) as c FROM `forum_topics` WHERE `author_id` = ? AND `creation_date` >= ?", array($_SESSION['user_id'], $tenMinAgo));
			$amountOfPosts = $db->fetch();

			if ($amountOfPosts['c'] > 5)
			{
				$_SESSION['message'] = 'toomany';
				if ($core->config('pretty_urls') == 1)
				{
					header("Location: /forum/");
				}
				else
				{
					header("Location: /index.php?module=forum");
				}
			}

			else if (!isset($_POST['act']))
			{
				$templating->load('newtopic');

				$db->sqlquery("SELECT `name` FROM `forums` WHERE forum_id = ?", array($_GET['forum_id']));
				$name = $db->fetch();

				$title = '';
				$text = '';
	
				if (isset($_GET['error']))
				{
					$title = $_SESSION['atitle'];
					$text = $_SESSION['atext'];
				}

				$templating->block('main', 'newtopic');

				$forum_index = '';
				if ($core->config('pretty_urls') == 1)
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
					if ($core->config('pretty_urls') == 1)
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

					if ($parray['can_sticky'] == 1)
					{
						$options .= '<option value="sticky">Sticky Topic</option>';
						$options_count++;
					}

					if ($parray['can_lock'] == 1)
					{
						$options .= '<option value="lock">Lock Topic</option>';
						$options_count++;
					}

					if ($parray['can_sticky'] == 1 && $parray['can_lock'] == 1)
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


				if ($mod_queue == 0)
				{
					$templating->block('poll', 'newtopic');
				}

				if (isset($_GET['forum_id']) && $_GET['forum_id'] == 17)
				{
					$templating->block('support');
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

				$templating->set('title', $title);
				$core->editor(['name' => 'text', 'content' => $text, 'editor_id' => 'comment_text']);

				$templating->block('bottom', 'newtopic');
				$templating->set('options', $options);
				
				$templating->block('preview', 'newtopic');
			}

			else if (isset($_POST['act']))
			{
				if ($_POST['act'] == 'Add')
				{
					// make safe
					$title = strip_tags($_POST['title']);
					$text = core::make_safe($_POST['text']);
					$text = trim($text);
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
					
					// make sure its not empty
					$empty_check = core::mempty(compact('title', 'text'));
					if ($empty_check !== true)
					{
						$_SESSION['atitle'] = $title;
						$_SESSION['atext'] = $text;
						
						$_SESSION['message'] = 'empty';
						$_SESSION['message_extra'] = $empty_check;

						header("Location: /index.php?module=newtopic&forum_id={$_POST['category']}&error");
					}

					else if (strlen($title) < 4)
					{
						$_SESSION['atitle'] = $title;
						$_SESSION['atext'] = $text;
						
						$_SESSION['message'] = 'shorttitle';

						header("Location: /index.php?module=newtopic&forum_id={$_POST['category']}&error");
					}

					else
					{
						// see if we need to add it into the mod queue
						$db->sqlquery("SELECT `in_mod_queue` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
						$check_queue = $db->fetch();

						$approved = 1;
						if ($mod_queue == 1 || $forced_mod_queue == true)
						{
							$approved = 0;
						}

						// update user post counter
						if ($approved == 1)
						{
							$db->sqlquery("UPDATE `users` SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($author));
						}

						// add the topic
						$db->sqlquery("INSERT INTO `forum_topics` SET `forum_id` = ?, `author_id` = ?, $mod_sql `topic_title` = ?, `topic_text` = ?, `creation_date` = ?, `last_post_date` = ?, `last_post_id` = ?, `approved` = ?", array($_POST['category'], $author, $title, $text, core::$date, core::$date, $author, $approved));
						$topic_id = $db->grab_id();

						// update forums post counter and last post info
						if ($approved == 1)
						{
							$db->sqlquery("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($author, core::$date, $topic_id, $_POST['category']));
						}

						// if they are subscribing
						if (isset($_POST['subscribe']))
						{
							$secret_key = core::random_id(15);
							$db->sqlquery("INSERT INTO `forum_topics_subscriptions` SET `user_id` = ?, `topic_id` = ?, `secret_key` = ?", array($_SESSION['user_id'], $topic_id, $secret_key));
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
										$_SESSION['atext'] = $text;
										
										$_SESSION['message'] = 'more_poll_options';

										header("Location: /index.php?module=newtopic&forum_id={$_POST['category']}");
										die();
									}
								}
							}

							unset($_SESSION['atitle']);
							unset($_SESSION['atext']);

							if ($core->config('pretty_urls') == 1)
							{
								header("Location: /forum/topic/{$topic_id}");
							}
							else
							{
								header("Location: " . $core->config('website_url') . "index.php?module=viewtopic&topic_id={$topic_id}");
							}
						}

						else if ($approved == 0)
						{
							$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `created_date` = ?, `data` = ?, `type` = 'mod_queue'", array($_SESSION['user_id'], core::$date, $topic_id));

							$_SESSION['message'] = 'mod_queue';
							
							header("Location: " . $core->config('website_url') . "index.php?module=viewforum&forum_id={$_POST['category']}");
						}
					}
				}
			}
		}
	}
	else if ($core->config('forum_posting_open') == 0)
	{
		$core->message('Posting is currently down for maintenance.');
	}
}
?>
