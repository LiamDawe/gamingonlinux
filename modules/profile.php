<?php
$templating->set_previous('title', 'Linux Gamer User Profile', 1);

// check user exists
if (isset($_GET['user_id']))
{
	$profile_id = (int) $_GET['user_id'];
	
	if ($profile_id == 1844)
	{
		$core->message('This is a bot.');
	}
	else
	{
		$templating->load('profile');

		if (!isset($_GET['view']))
		{
			include('includes/profile_fields.php');

			$db_grab_fields = '';
			foreach ($profile_fields as $field)
			{
				$db_grab_fields .= "{$field['db_field']},";
			}

			$profile = $dbl->run("SELECT `user_id`, `pc_info_public`, `username`, `distro`, `register_date`, `email`, `avatar`, `avatar_uploaded`, `avatar_gallery`, `comment_count`, `forum_posts`, $db_grab_fields `article_bio`, `last_login`, `banned`, `ip`, `game_developer`, `private_profile`, `get_pms` FROM `users` WHERE `user_id` = ?", array($profile_id))->fetch();
			if (!$profile)
			{
				$core->message('That person does not exist here!');
			}
			else
			{
				// check blocked list
				$blocked = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($profile_id, $_SESSION['user_id']))->fetchOne();
				if (($blocked || $profile['private_profile'] == 1) && !$user->check_group([1,2]) && (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profile_id))
				{
					$core->message("Sorry, this user has set their profile to private.", 1);
				}
				else
				{
					if ($profile['banned'] == 1 && $user->check_group([1,2]) == false)
					{
						$core->message("That user is banned so you may not view their profile!", 1);
					}

					else if (($profile['banned'] == 1 && $user->check_group([1,2]) == true) || $profile['banned'] == 0)
					{
						if ($profile['banned'] == 1)
						{
							$core->message("You are viewing a banned users profile!", 2);
						}

						$templating->set_previous('meta_description', "Viewing {$profile['username']} profile on GamingOnLinux.com", 1);

						if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
						{
							$templating->block('top', 'profile');

							$user_action_links = [];

							// give them an edit link if it's their profile
							if ($_SESSION['user_id'] == $_GET['user_id'])
							{
								$user_action_links[] = '<a href="/usercp.php">Click here to edit your profile</a>';						
							}

							// get blocked id's
							$blocked_ids = [];
							if (count($user->blocked_users) > 0)
							{
								foreach ($user->blocked_users as $username => $blocked_id)
								{
									$blocked_ids[] = $blocked_id[0];
								}
							}		
							
							if ($_SESSION['user_id'] != $_GET['user_id'])
							{
								$block = '<a href="/index.php?module=block_user&block='.$_GET['user_id'].'">Block/Ignore User</a>';
								if (in_array($_GET['user_id'], $blocked_ids))
								{
									$block = '<a href="/index.php?module=block_user&unblock='.$_GET['user_id'].'">UnBlock User</a>';
								}

								$user_action_links[] = $block;
							}

							$templating->set('user_actions', implode(' | ', $user_action_links));
						}

						$templating->block('main', 'profile');

						$templating->set('username', $profile['username']);

						$cake_bit = $user->cake_day($profile['register_date'], $profile['username']);
						$templating->set('cake_icon', $cake_bit);
						
						$their_groups = $user->post_group_list([$profile['user_id']]);
						$profile['user_groups'] = $their_groups[$profile['user_id']];
						$badges = user::user_badges($profile);
						$templating->set('badges', implode(' ', $badges));

						$registered_date = $core->human_date($profile['register_date']);
						$templating->set('registered_date', $registered_date);

						$avatar = $user->sort_avatar($profile);

						$templating->set('avatar', $avatar);
						$templating->set('article_comments', $profile['comment_count']);
						$templating->set('forum_posts_counter', $profile['forum_posts']);

						$profile_fields_output = '';

						foreach ($profile_fields as $field)
						{
							if (!empty($profile[$field['db_field']]))
							{
								if ($field['db_field'] == 'website')
								{
									$profile[$field['db_field']] = core::check_url($profile[$field['db_field']]);
									
								}
								
								$url = '';
								if ($field['base_link_required'] == 1 && strpos($profile[$field['db_field']], $field['base_link']) === false ) //base_link_required and not already in the database
								{
									$url = $field['base_link'];
								}

								$image = '';
								if (isset($field['image']) && $field['image'] != NULL)
								{
									$image = "<img src=\"{$field['image']}\" alt=\"{$field['name']}\" />";
								}

								$span = '';
								if (isset($field['span']))
								{
									$span = $field['span'];
								}
								$into_output = '';
								if ($field['name'] != 'Distro')
								{
									$into_output .= "$image$span {$field['name']} <a href=\"$url{$profile[$field['db_field']]}\" target=\"_blank\">$url{$profile[$field['db_field']]}</a><br />";
								}

								$profile_fields_output .= $into_output;
							}
						}

						$templating->set('profile_fields', $profile_fields_output);

						$templating->set('last_login', $core->human_date($profile['last_login']));

						$message_link = '';
						if ($_SESSION['user_id'] != 0 && ($profile['get_pms'] == 1 || $profile['get_pms'] == 0 && $user->check_group([1,2,5])))
						{
							$message_link = "<a href=\"/private-messages/compose/user={$_GET['user_id']}\">Send Private Message</a><br />";
						}
						$templating->set('message_link', $message_link);

						$email = '';
						if ($user->check_group([1,2]) == true)
						{
							$email = "Email: {$profile['email']}<br />";
						}
						$templating->set('email', $email);

						// additional profile info
						if ($profile['pc_info_public'] == 1)
						{
							$templating->block('additional', 'profile');
							$templating->set('username', $profile['username']);
							$templating->set('profile_link', '/profiles/' . $_GET['user_id']);

							$fields_output = '';
							$pc_info = $user->display_pc_info($profile['user_id'], $profile['distro']);
							if ($pc_info['counter'] > 0)
							{
								foreach ($pc_info as $k => $info)
								{
									if ($k != 'counter')
									{
										$fields_output .= '<li>' . $info . '</li>';
									}
								}
							}
							else
							{
								$fields_output = '<li><em>This user has not filled out their PC info!</em></li>';
							}

							$templating->set('fields', $fields_output);
						}

						// gather latest articles
						$article_res = $dbl->run("SELECT `article_id`, `title`, `slug` FROM `articles` WHERE `author_id` = ? AND `admin_review` = 0 AND `active` = 1 ORDER BY `date` DESC LIMIT 5", array($profile['user_id']))->fetch_all();
						if ($article_res)
						{
							$templating->block('articles_top');
							foreach ($article_res as $article_link)
							{
								$templating->block('articles');

								$templating->set('latest_article_link', '<a href="' . $article_class->get_link($article_link['article_id'], $article_link['slug']).'">'.$article_link['title'].'</a>');
							}
							$templating->block('articles_bottom');
							$templating->set('user_id', $profile['user_id']);
							$templating->set('username', $profile['username']);
						}


						if (!empty($profile['article_bio']))
						{
							$templating->block('bio', 'profile');
							$templating->set('bio_text', $bbcode->parse_bbcode($profile['article_bio']));
						}

						$comment_posts = '';
						$view_more_comments = '';
						$comments_execute = $dbl->run("SELECT comment_id, c.`comment_text`, c.`article_id`, c.`time_posted`, a.`title`, a.`slug`, a.comment_count, a.active FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE a.active = 1 AND c.approved = 1 AND c.author_id = ? ORDER BY c.`comment_id` DESC limit 5", array($_GET['user_id']))->fetch_all();

						if ($comments_execute)
						{
							$total_comments = count($comments_execute);

							// comments block
							$templating->block('article_comments_list', 'profile');

							foreach ($comments_execute as $comments)
							{
								$date = $core->human_date($comments['time_posted']);
								$title = $comments['title'];

								// remove quotes, it's not their actual comment, and can leave half-open quotes laying around
								$text = preg_replace('/\[quote\=(.+?)\](.+?)\[\/quote\]/is', "", $comments['comment_text']);
								$text = preg_replace('/\[quote\](.+?)\[\/quote\]/is', "", $text);
								
								$article_link = $article_class->get_link($comments['article_id'], $comments['slug'], 'comment_id=' . $comments['comment_id']);

								$comment_posts .= "<li class=\"list-group-item\">
							<a href=\"".$article_link."\">{$title}</a>
							<div>".substr(strip_tags($bbcode->parse_bbcode($text)), 0, 63)."&hellip;</div>
							<small>{$date}</small>
						</li>";
							}

							if ($total_comments >= 5)
							{
								$view_more_comments = '<li class="list-group-item"><a href="/profiles/'.$_GET['user_id'].'/comments/">View more comments</a></li>';
							}
						}

						$templating->set('view_more_comments', $view_more_comments);

						$templating->set('comment_posts', $comment_posts);

						// latest forum posts from user //

						// need to find forums this viewing user is allowed to see

						$groups_in = str_repeat('?,', count($user->user_groups) - 1) . '?';

						// get the forum ids this user is actually allowed to view
						$forum_ids = $dbl->run("SELECT p.`forum_id` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE `is_category` = 0 AND `can_view` = 1 AND `group_id` IN ($groups_in) GROUP BY forum_id ORDER BY f.name ASC", $user->user_groups)->fetch_all(PDO::FETCH_COLUMN);

						if ($forum_ids)
						{
							$forum_posts = '';

							$forum_id_in  = str_repeat('?,', count($forum_ids) - 1) . '?';

							$posts_sql = "SELECT p.creation_date as 'date', t.topic_title, t.last_post_id, t.topic_id, p.reply_text as 'text', p.is_topic FROM `forum_replies` p JOIN `forum_topics` t ON t.topic_id = p.topic_id WHERE t.approved = 1 and p.approved = 1 AND t.`forum_id` IN (".$forum_id_in.") AND p.author_id = ? ORDER BY p.creation_date DESC LIMIT 5";

							$posts_execute = $dbl->run($posts_sql, array_merge($forum_ids,[$_GET['user_id']]))->fetch_all();

							if ($posts_execute)
							{
								$total_posts = count($posts_execute);

								// comments block
								$templating->block('forum_post_list', 'profile');

								foreach ($posts_execute as $posts)
								{
									$date = $core->human_date($posts['date']);
									$title = $posts['topic_title'];

									// remove quotes, it's not their actual comment, and can leave half-open quotes laying around
									$text = preg_replace('/\[quote\=(.+?)\](.+?)\[\/quote\]/is', "", $posts['text']);
									$text = preg_replace('/\[quote\](.+?)\[\/quote\]/is', "", $text);

									$link_additional = NULL;
									if ($posts['last_post_id'] != NULL)
									{
										$link_additional = 'post_id=' . $posts['last_post_id'];
									}
									
									$post_link = $forum_class->get_link($posts['topic_id'], $link_additional);

									$forum_posts .= "<li class=\"list-group-item\">
								<a href=\"".$post_link."\">{$title}</a>
								<div>".substr(strip_tags($bbcode->parse_bbcode($text)), 0, 63)."&hellip;</div>
								<small>{$date}</small>
							</li>";
								}
							}

							$templating->set('forum_posts', $forum_posts);
						}

						//Do not show end block if it's empty
						if ($user->check_group([1,2]))
						{
							$templating->block('end', 'profile');

							$admin_links = "<form method=\"post\" action=\"/admin.php?module=users&user_id={$profile['user_id']}\">
							<button type=\"submit\" formaction=\"/admin.php?module=users&view=edituser&user_id={$profile['user_id']}\" class=\"btn btn-primary\">Edit User</button>
							<button name=\"act\" value=\"ban\" class=\"btn btn-danger\">Ban User</button>
							<button name=\"act\" value=\"totalban\" class=\"btn btn-danger\">Ban User & Ban IP</button>
							<input type=\"hidden\" name=\"ip\" value=\"{$profile['ip']}\" />";

							if ($profile['banned'] == 1)
							{
								$admin_links .= "&nbsp;&nbsp;
								<button name=\"act\" value=\"delete_user_content\"  class=\"btn btn-danger\">Delete user content</button>
								";
							}

							$admin_links .= "</form>";
							$templating->set('admin_links', $admin_links);
						}
					}
				}
			}
		}

	else if (isset($_GET['view']))
	{
		if ($_GET['view'] == 'more-comments')
		{
			if (isset($_GET['user_id']) && is_numeric($_GET['user_id']))
			{
				$get_username = $dbl->run("SELECT `username`, `private_profile` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']))->fetch();
				if ($get_username)
				{
					// check blocked list
					$blocked = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_GET['user_id'], $_SESSION['user_id']))->fetchOne();
					if (($blocked || $get_username['private_profile'] == 1) && !$user->check_group([1,2]) && (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profile_id))
					{
						$core->message("Sorry, this user has set their profile to private.", 1);
					}
					else
					{
						// count how many there is in total
						$total = $dbl->run("SELECT COUNT(`comment_id`) FROM `articles_comments` WHERE `author_id` = ?", array($_GET['user_id']))->fetchOne();
							
						$page = core::give_page();

						// sort out the pagination link
						$pagination = $core->pagination_link(10, $total, $core->config('website_url')  . "profiles/".$_GET['user_id']."/comments/", $page);

						// get top of comments section
						$templating->block('more_comments');
						$templating->set('username', $get_username['username']);
						$templating->set('profile_link', "/profiles/" . $_GET['user_id']);

						$comment_posts = '';
						$all_comments = $dbl->run("SELECT comment_id, c.`comment_text`, c.`article_id`, c.`time_posted`, a.`title`, a.`slug`, a.comment_count, a.active FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE a.active = 1 AND c.author_id = ? ORDER BY c.`comment_id` DESC LIMIT ?, 10", array($_GET['user_id'], $core->start))->fetch_all();
							
						// make an array of all comment ids to search for likes (instead of one query per comment for likes)
						$like_array = [];
						$sql_replacers = [];
						foreach ($all_comments as $id_loop)
						{
							$like_array[] = $id_loop['comment_id'];
							$sql_replacers[] = '?';
						}
						if (!empty($sql_replacers))
						{
							// Total number of likes for the comments
							$get_likes = $dbl->run("SELECT data_id, COUNT(*) FROM likes WHERE data_id IN ( ".implode(',', $sql_replacers)." ) AND `type` = 'comment' GROUP BY data_id", $like_array)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
						}
							
						foreach ($all_comments as $comments)
						{
							$date = $core->human_date($comments['time_posted']);
							$title = $comments['title'];
								
							// sort out the likes
							$likes = NULL;
							if (isset($get_likes[$comments['comment_id']]))
							{
								$likes = ' <span class="profile-comments-heart icon like"></span> Likes: ' . $get_likes[$comments['comment_id']][0];
							}
							
							$view_comment_link = $article_class->get_link($comments['article_id'], $comments['slug'], 'comment_id=' . $comments['comment_id']);
							$view_article_link = $article_class->get_link($comments['article_id'], $comments['slug']);
							$view_comments_full_link = $article_class->get_link($comments['article_id'], $comments['slug'], '#comments');

							$comment_posts .= "<div class=\"box\"><div class=\"body group\">
							<a href=\"".$view_comment_link."\">{$title}</a><br />
							<small>{$date}" . $likes ."</small><br />
							<hr />
							<div>".$bbcode->parse_bbcode($comments['comment_text'])."</div>
							<hr />
							<div><a href=\"".$view_comment_link."\">View this comment</a> - <a href=\"".$view_article_link."\">View article</a> - <a href=\"".$view_comments_full_link."\">View full comments</a></div>
							</div></div>";
						}

						$templating->set('comment_posts', $comment_posts);
						$templating->set('pagination', $pagination);
					}
				}
				else
				{
					$core->message('User does not exist!');
				}
			}
			else
			{
				$core->message('User does not exist!');
			}
		}
	}
}
}
else
{
	$core->message('No user id asked for to view! <a href="index.php">Click here to return</a>.');
}
