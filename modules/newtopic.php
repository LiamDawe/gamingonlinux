<?php
$templating->set_previous('title', 'Create new topic', 1);
if ($_SESSION['user_id'] > 0 && !isset($_SESSION['activated']))
{
	$core->message('You do not have permission to post in this forum! Your account isn\'t activated!');
}
else
{				
	if ($core->config('forum_posting_open') == 1)
	{
		$in = str_repeat('?,', count($user->user_groups) - 1) . '?';
		
		// get forum permissions
		$forums_list = $dbl->run("SELECT p.`forum_id`, f.`name` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE p.`can_topic` = 1 AND p.`group_id` IN ($in) GROUP BY p.forum_id ORDER BY f.`name` ASC ", $user->user_groups)->fetch_all();
		if (!$forums_list)
		{
			$core->message('You do not have permission to post in any forums!');
		}
		else
		{
			$mod_queue = $user->user_details['in_mod_queue'];
			$forced_mod_queue = $user->can('forced_mod_queue');
			
			$show_forum_breadcrumb = 0;
			if (isset($_GET['forum_id']))
			{
				$parray = $forum_class->forum_permissions($_GET['forum_id']);
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
			$user_post_count = $dbl->run("SELECT COUNT(author_id) FROM `forum_topics` WHERE `author_id` = ? AND `creation_date` >= ?", array($_SESSION['user_id'], $tenMinAgo))->fetchOne();

			if ($user_post_count > 5)
			{
				$_SESSION['message'] = 'toomany';
				header("Location: /forum/");
				die();
			}

			else if (!isset($_POST['act']))
			{
				$templating->load('newtopic');

				$title = '';
				$text = '';
	
				if (isset($_GET['error']))
				{
					$title = $_SESSION['atitle'];
					$text = $_SESSION['atext'];
				}

				$templating->block('main', 'newtopic');
				$templating->set('forum_index', '/forum/');

				$crumbs = '';
				if ($show_forum_breadcrumb == 1)
				{
					$name = $dbl->run("SELECT `name` FROM `forums` WHERE forum_id = ?", array($_GET['forum_id']))->fetch();
					$crumbs = '<li><a href="/forum/' . $_GET['forum_id'] . '/">'.$name['name'].'</a></li>';
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
						$check_queue = $dbl->run("SELECT `in_mod_queue` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

						$approved = 1;
						if ($mod_queue == 1 || $forced_mod_queue == true)
						{
							$approved = 0;
						}

						// update user post counter
						if ($approved == 1)
						{
							$dbl->run("UPDATE `users` SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($author));
						}

						// add the topic
						$dbl->run("INSERT INTO `forum_topics` SET `forum_id` = ?, `author_id` = ?, $mod_sql `topic_title` = ?, `topic_text` = ?, `creation_date` = ?, `last_post_date` = ?, `last_post_user_id` = ?, `approved` = ?", array($_POST['category'], $author, $title, $text, core::$date, core::$date, $author, $approved));
						$topic_id = $dbl->new_id();

						// update forums post counter and last post info
						if ($approved == 1)
						{
							$dbl->run("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($author, core::$date, $topic_id, $_POST['category']));
						}

						// if they are subscribing
						if (isset($_POST['subscribe']))
						{
							$secret_key = core::random_id(15);
							$dbl->run("INSERT INTO `forum_topics_subscriptions` SET `user_id` = ?, `topic_id` = ?, `secret_key` = ?", array($_SESSION['user_id'], $topic_id, $secret_key));
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
										$dbl->run("INSERT INTO `polls` SET `author_id` = ?, `poll_question` = ?, `topic_id` = ?", array($_SESSION['user_id'], $_POST['pquestion'], $topic_id));
										$poll_id = $dbl->new_id();

										$dbl->run("UPDATE `forum_topics` SET `has_poll` = 1 WHERE `topic_id` = ?", array($topic_id));

										foreach ($_POST['poption'] as $option)
										{
											// don't add in empty left-over options for voting
											$option = trim($option);

											if (!empty($option))
											{
												$dbl->run("INSERT INTO `poll_options` SET `poll_id` = ?, `option_title` = ?", array($poll_id, $option));
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

							header("Location: /forum/topic/{$topic_id}");
							die();
						}

						else if ($approved == 0)
						{
							$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `created_date` = ?, `data` = ?, `type` = 'mod_queue'", array($_SESSION['user_id'], core::$date, $topic_id));

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
