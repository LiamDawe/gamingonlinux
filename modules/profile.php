<?php
$templating->set_previous('title', 'Linux Gamer User Profile', 1);

// check user exists
if (isset($_GET['user_id']) && core::is_number($_GET['user_id']))
{
	if ($_GET['user_id'] == 1844)
	{
		$core->message('This is a bot.');
	}
	else
	{
		$templating->merge('profile');

		if (!isset($_GET['view']))
		{
			include('includes/profile_fields.php');

			$db_grab_fields = '';
			foreach ($profile_fields as $field)
			{
				$db_grab_fields .= "{$field['db_field']},";
			}

			$db->sqlquery("SELECT `user_id`, `pc_info_public`, `username`, `distro`, `register_date`, `email`, `avatar`, `avatar_gravatar`, `gravatar_email`, `avatar_uploaded`, `avatar_gallery`, `comment_count`, `forum_posts`, $db_grab_fields `article_bio`, `last_login`, `banned`, `user_group`, `secondary_user_group`, `ip`, `game_developer` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($_GET['user_id']));
			if ($db->num_rows() != 1)
			{
				$core->message('That person does not exist here!');
			}

			else
			{
				$profile = $db->fetch();

				if ($profile['banned'] == 1 && $user->check_group([1,2]) == false)
				{
					$core->message("That user is banned so you may not view their profile!", NULL, 1);
				}

				else if (($profile['banned'] == 1 && $user->check_group([1,2]) == true) || $profile['banned'] == 0)
				{
					if ($profile['banned'] == 1)
					{
						$core->message("You are viewing a banned users profile!", NULL, 2);
					}

					$templating->set_previous('meta_description', "Viewing {$profile['username']} profile on GamingOnLinux.com", 1);

					if ($_SESSION['user_id'] == $_GET['user_id'])
					{
						$templating->block('top');
						$templating->set('url', $core->config('website_url'));
					}

					$templating->block('main', 'profile');

					$templating->set('username', $profile['username']);

					$cake_bit = $user->cake_day($profile['register_date'], $profile['username']);
					$templating->set('cake_icon', $cake_bit);
					
					$badges = user::user_badges($profile);
					$templating->set('badges', implode(' ', $badges));

					$registered_date = $core->format_date($profile['register_date']);
					$templating->set('registered_date', $registered_date);

					$avatar = $user->sort_avatar($profile['user_id']);

					$templating->set('avatar', $avatar);
					$templating->set('article_comments', $profile['comment_count']);
					$templating->set('forum_posts', $profile['forum_posts']);

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

					$templating->set('last_login', $core->format_date($profile['last_login']));

					$message_link = '';
					if ($_SESSION['user_id'] != 0)
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
					$db->sqlquery("SELECT `article_id`, `title`, `slug` FROM `articles` WHERE `author_id` = ? AND `admin_review` = 0 AND `active` = 1 ORDER BY `date` DESC LIMIT 5", array($profile['user_id']));
					if ($db->num_rows() != 0)
					{
						$templating->block('articles_top');
						while ($article_link = $db->fetch())
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
					$db->sqlquery("SELECT comment_id, c.`comment_text`, c.`article_id`, c.`time_posted`, a.`title`, a.`slug`, a.comment_count, a.active FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE a.active = 1 AND c.author_id = ? ORDER BY c.`comment_id` DESC limit 5", array($_GET['user_id']));
					$count_comments = $db->num_rows();
					if ($count_comments > 0)
					{
						// comments block
						$templating->block('article_comments_list', 'profile');

						$comments_execute = $db->fetch_all_rows();
						foreach ($comments_execute as $comments)
						{
							$date = $core->format_date($comments['time_posted']);
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

						if ($count_comments >= 5)
						{
							if ($core->config('pretty_urls') == 1)
							{
								$more_comments_href = "/profiles/".$_GET['user_id']."/comments/";
							}
							else
							{
								$more_comments_href = "index.php?module=profile&amp;view=more-comments&amp;user_id=".$_GET['user_id'];
							}
							$view_more_comments = '<li class="list-group-item"><a href="'.$more_comments_href.'">View more comments</a></li>';
						}
					}

					$templating->set('view_more_comments', $view_more_comments);

					$templating->set('comment_posts', $comment_posts);

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

	else if (isset($_GET['view']))
	{
		if ($_GET['view'] == 'more-comments')
		{
			if (isset($_GET['user_id']) && is_numeric($_GET['user_id']))
			{
				$db->sqlquery("SELECT `username` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($_GET['user_id']));
				$exists = $db->num_rows();
				if ($exists == 1)
				{
					$get_username = $db->fetch();

					// count how many there is in total
					$db->sqlquery("SELECT `comment_id` FROM `articles_comments` WHERE `author_id` = ?", array($_GET['user_id']));
					$total = $db->num_rows();

					// paging for pagination
					if (!isset($_GET['page']) || $_GET['page'] <= 0)
					{
						$page = 1;
					}

					else if (is_numeric($_GET['page']))
					{
						$page = $_GET['page'];
					}

					if ($core->config('pretty_urls') == 1)
					{
						$pagination_linky = "profiles/".$_GET['user_id']."/comments/";
					}
					else
					{
						$pagination_linky = "index.php?module=profile&amp;view=more-comments&amp;user_id=".$_GET['user_id']."&amp;";
					}


					// sort out the pagination link
					$pagination = $core->pagination_link(10, $total, $core->config('website_url')  . $pagination_linky, $page);

					// get top of comments section
					$templating->block('more_comments');
					$templating->set('username', $get_username['username']);

					if ($core->config('pretty_urls') == 1)
					{
						$profile_link = "/profiles/" . $_GET['user_id'];
					}
					else 
					{
						$profile_link = url . "index.php?module=profile&amp;user_id=" . $_GET['user_id'];
					}

					$templating->set('profile_link', $profile_link);

					$comment_posts = '';
					$db->sqlquery("SELECT comment_id, c.`comment_text`, c.`article_id`, c.`time_posted`, a.`title`, a.`slug`, a.comment_count, a.active FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE a.active = 1 AND c.author_id = ? ORDER BY c.`comment_id` DESC LIMIT ?, 10", array($_GET['user_id'], $core->start));
					$all_comments = $db->fetch_all_rows();
					
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
						$qtotallikes = $db->sqlquery("SELECT data_id, COUNT(*) FROM likes WHERE data_id IN ( ".implode(',', $sql_replacers)." ) AND `type` = 'comment' GROUP BY data_id", $like_array);
						$get_likes = $db->fetch_all_rows(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
					}
					
					foreach ($all_comments as $comments)
					{
						$date = $core->format_date($comments['time_posted']);
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
