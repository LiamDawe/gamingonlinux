<?php
$templating->merge('articles_full');

if (isset($_GET['view']))
{
	$templating->set_previous('article_image', '', 1);
}

if (!isset($_GET['go']))
{
	if (!isset($_GET['view']))
	{
		// make sure the id is set
		if (!isset($_GET['aid']))
		{
			http_response_code(404);
			$templating->set_previous('meta_data', '', 1);
			$templating->set_previous('title', 'No id entered', 1);
			$core->message('That is not a correct article id!');
		}

		else
		{
			// get the article
			$db->sqlquery("SELECT a.`article_id`, a.`title`, a.`text`, a.`tagline`, a.`date`, a.`date_submitted`, a.`author_id`, a.`active`, a.`guest_username`, a.`views`, a.`article_top_image`, a.`article_top_image_filename`, a.`tagline_image`, a.`comments_open`, u.`username`, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u.`article_bio`, u.`user_group`, u.`twitter_on_profile` FROM `articles` a LEFT JOIN `users` u on a.`author_id` = u.`user_id` WHERE a.`article_id` = ?", array($_GET['aid']));
			$article = $db->fetch();

			if ($db->num_rows() == 0)
			{
				http_response_code(404);
				$templating->set_previous('meta_data', '', 1);
				$templating->set_previous('title', 'Couldn\'t find article', 1);
				$core->message('That is not a correct article id! We have loaded a search box for you if you\'re lost!');

				$templating->merge('search');
				$templating->block('top');
				$templating->set('url', core::config('website_url'));
				$templating->set('search_text', '');
			}

			else if ($article['active'] == 0)
			{
				$templating->set_previous('meta_description', 'Article error on GamingOnLinux', 1);
				$templating->set_previous('title', 'Article Inactive', 1);
				$templating->set_previous('meta_data', '', 1);
				$core->message('This article is currently inactive!');
			}

			else
			{
				$templating->set_previous('meta_description', $article['tagline'], 1);
				$templating->set_previous('title', $article['title'], 1);

				// update the view counter if it is not a preview
				$db->sqlquery("UPDATE `articles` SET `views` = (views + 1) WHERE `article_id` = ?", array($article['article_id']), 'articles_full.php');

				// set the article image meta
				$article_meta_image = '';
				if ($article['article_top_image'] == 1)
				{
					$article_meta_image = core::config('website_url') . "/uploads/articles/topimages/{$article['article_top_image_filename']}";
				}
				if (!empty($article['tagline_image']))
				{
					$article_meta_image = core::config('website_url') . "/uploads/articles/tagline_images/{$article['tagline_image']}";
				}

				$nice_title = $core->nice_title($article['title']);

				// twitter info card
				$twitter_card = "<!-- twitter card -->\n";
				$twitter_card .= '<meta name="twitter:card" content="summary_large_image">';
				$twitter_card .= '<meta name="twitter:site" content="@gamingonlinux">';
				if (!empty($article['twitter_on_profile']) && $article['twitter_on_profile'] !== 'gamingonlinux' )
				{
					$twitter_card .= '<meta name="twitter:creator" content="@'.$article['twitter_on_profile'].'">';
				}

				$twitter_card .= '<meta name="twitter:title" content="'.$article['title'].'">';
				$twitter_card .= '<meta name="twitter:description" content="'.strip_tags(bbcode($article['tagline'], 0)).'">'; //Piratelv @ 06/06/14 -- Fixed iframes showing up in page header
				$twitter_card .= '<meta name="twitter:image" content="'.$article_meta_image.'">';
				$twitter_card .= '<meta name="twitter:image:src" content="'.$article_meta_image.'">';

				// meta tags for g+, facebook and twitter images
				$templating->set_previous('meta_data', "<meta property=\"og:image\" content=\"$article_meta_image\"/>\n<meta property=\"og:image_url\" content=\"$article_meta_image\"/>\n<meta property=\"og:type\" content=\"article\">\n<meta property=\"og:title\" content=\"" . $article['title'] . "\" />\n<meta property=\"og:description\" content=\"{$article['tagline']}\" />\n<meta property=\"og:url\" content=\"{$config['website_url']}/articles/$nice_title.{$article['article_id']}\" />\n<meta itemprop=\"image\" content=\"$article_meta_image\" />\n<meta itemprop=\"title\" content=\"" . $article['title'] . "\" />\n<meta itemprop=\"description\" content=\"{$article['tagline']}\" />\n$twitter_card", 1);

				// make date human readable
				$date = $core->format_date($article['date']);

				$templating->block('article', 'articles_full');
				$templating->set('url', core::config('website_url'));
				$templating->set('share_url', "http://www.gamingonlinux.com/articles/$nice_title.{$_GET['aid']}/");

				$templating->set('rules', $config['rules']);

				if (($user->check_group(1,2) == true || $user->check_group(5) == true) && !isset($_GET['preview']))
				{
					$templating->set('edit_link', " <a href=\"" . core::config('website_url') . "admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"><i class=\"icon-pencil\"></i><strong>Edit</strong></a>");
					$templating->set('admin_button', '');
				}

				else if (($user->check_group(1,2) == true || $user->check_group(5) == true) && isset($_GET['preview']))
				{
					$page = 'admin.php?module=adminreview';
					if (isset($_GET['submitted']) && $_GET['submitted'] == 1)
					{
						$page ='admin.php?module=articles&view=Submitted';
					}
					if (isset($_GET['draft']) && $_GET['draft'] == 1)
					{
						$page ='admin.php?module=articles&view=drafts';
					}
					$templating->set('edit_link', '');
					$templating->set('admin_button', "<form method=\"post\"><button type=\"submit\" class=\"btn btn-info\" formaction=\"{$config['url']}{$page}\">Back</button> <button type=\"submit\" formaction=\"{$config['url']}{$page}&aid={$_GET['aid']}\" class=\"btn btn-info\">Edit</button></form>");
				}

				if ($user->check_group(1,2) == false && $user->check_group(5) == false)
				{
					$templating->set('edit_link', '');
					$templating->set('admin_button', '');
				}

				$templating->set('title', $article['title']);
				$templating->set('user_id', $article['author_id']);

				$view_more = '';
				if ($article['author_id'] == 0)
				{
					if (empty($article['guest_username']))
					{
						$username = 'Guest';
					}

					else
					{
						$username = $article['guest_username'];
					}
				}

				else
				{
					$username = "<a rel=\"author\" href=\"/profiles/{$article['author_id']}\"><span class=\"glyphicon glyphicon-user\"></span> {$article['username']}</a>";
					$view_more = "<a href=\"/index.php?module=search&amp;author_id={$article['author_id']}\"><span class=\"glyphicon glyphicon-search\"></span> View more articles from {$article['username']}</a>";
				}

				$templating->set('username', $username);

				$templating->set('date', $date);

				$templating->set('article_views', $article['views']);

				$categories_list = '';
				// sort out the categories (tags)
				$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ?", array($article['article_id']), 'articles_full.php');
				while ($get_categories = $db->fetch())
				{
					if ($get_categories['category_id'] == 60)
					{
						$categories_list .= " <li class=\"ea\"><a href=\"/articles/category/{$get_categories['category_id']}\">{$get_categories['category_name']}</a></li> ";
					}

					else
					{
						$categories_list .= " <li><a href=\"/articles/category/{$get_categories['category_id']}\">{$get_categories['category_name']}</a></li> ";
					}
				}

				$templating->set('categories_list', $categories_list);

				$article_bottom = '';
				if ($article['user_group'] != 1 && $article['user_group'] != 2 && $article['user_group'] != 5)
				{
					$article_bottom = "\n<br /><br /><p class=\"small muted\">This article was submitted by a guest, we encourage anyone to <a href=\"//www.gamingonlinux.com/submit-article/\">submit their own articles</a>.</p>";
				}

				//piratelv timeago: 12/11/14
				$templating->set('article_meta', "<meta itemprop=\"image\" content=\"$article_meta_image\" /> <script>var postdate=new Date('".date('c', $article['date'])."')</script>");

				if ($article['article_top_image'] == 1)
				{
					$tagline_bbcode = $article['article_top_image_filename'];
				}

				if (!empty($article['tagline_image']))
				{
					$tagline_bbcode  = $article['tagline_image'];
				}
				else
				{
					$tagline_bbcode = "";
				}

				$article_page = 1;
				if (isset($_GET['article_page']) && is_numeric($_GET['article_page']))
				{
					$article_page = $_GET['article_page'];
				}

				// paging for pagination
				if (!isset($_GET['page']) || $_GET['page'] == 0)
				{
					$page = 1;
				}

				else if (is_numeric($_GET['page']))
				{
					$page = $_GET['page'];
				}

				// sort out the pages and pagination and only return the page requested
				$pages_array = explode('<*PAGE*>', $article['text']);
				$article_page_count = count($pages_array);
				$pages_array = array_combine(range(1, count($pages_array)), $pages_array);

				$templating->set('text', bbcode($pages_array[$article_page], 1, 1, $tagline_bbcode) . $article_bottom);

				$article_link = "/articles/$nice_title.{$_GET['aid']}/";
				if (isset($_GET['preview']))
				{
					$article_link = "/index.php?module=articles_full&amp;aid={$_GET['aid']}&amp;preview&amp;";
				}

				$article_pagination = $core->article_pagination($article_page, $article_page_count, $article_link);

				$templating->set('paging', $article_pagination);

				if (!empty($article['article_bio']) && ($article['user_group'] == 1 || $article['user_group'] == 2 || $article['user_group'] == 5))
				{
					$templating->block('bio', 'articles_full');

					// sort out the avatar
					if ($article['avatar_gravatar'] == 1)
					{
						$avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $article['gravatar_email'] ) ) ) . "?d=" . urlencode($config['website_url'] . '/uploads/avatars/no_avatar.png') . '&size=125';
					}

					// either uploaded or linked an avatar
					else if (!empty($article['avatar']) && $article['avatar_gravatar'] == 0)
					{
						$avatar = $article['avatar'];
						if ($article['avatar_uploaded'] == 1)
						{
							$avatar = "/uploads/avatars/{$article['avatar']}";
						}
					}

					// else no avatar
					else if (empty($article['avatar']) && $article['avatar_gravatar'] == 0)
					{
						$avatar = "/uploads/avatars/no_avatar.png";
					}
					$templating->set('avatar', $avatar);

					$templating->set('username', $username);
					$templating->set('view_more', $view_more);

					$bio = bbcode($article['article_bio'], 0);

					$templating->set('article_bio', $bio);
				}

				// get the comments if we aren't in preview mode
				if ($article['active'] == 1)
				{
					// count how many there is in total
					$sql_count = "SELECT `comment_id` FROM `articles_comments` WHERE `article_id` = ?";
					$db->sqlquery($sql_count, array($_GET['aid']));
					$total_pages = $db->num_rows();

					// update their subscriptions if they are reading the last page
					if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
					{
						$db->sqlquery("SELECT `send_email` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['aid']));
						$check_sub = $db->fetch();

						$db->sqlquery("SELECT `email_options` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
						$check_user = $db->fetch();

						if ($check_user['email_options'] == 2 && $check_sub['send_email'] == 0)
						{
							//lastpage is = total pages / items per page, rounded up.
							if ($total_pages <= 10)
							{
								$lastpage = 1;
							}
							else
							{
								$lastpage = ceil($total_pages/10);
							}

							// they have read all new comments (or we think they have since they are on the last page)
							if ($page == $lastpage)
							{
								// send them an email on a new comment again
								$db->sqlquery("UPDATE `articles_subscriptions` SET `send_email` = 1 WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['aid']));
							}
						}
					}

					if ($_SESSION['user_group'] != 4)
					{
						// find out if this user has subscribed to the comments
						if ($_SESSION['user_id'] != 0)
						{
							$db->sqlquery("SELECT `user_id` FROM `articles_subscriptions` WHERE `article_id` = ? AND `user_id` = ?", array($_GET['aid'], $_SESSION['user_id']), 'articles_full.php');
							if ($db->num_rows() == 1)
							{
								$subscribe_link = "<a href=\"/index.php?module=articles_full&amp;go=unsubscribe&amp;article_id={$_GET['aid']}\" class=\"white-link\"><span class=\"link_button\">Unsubscribe from comments</span></a>";
							}

							else
							{
								$subscribe_link = "<a href=\"/index.php?module=articles_full&amp;go=subscribe&amp;article_id={$_GET['aid']}\" class=\"white-link\"><span class=\"link_button\">Subscribe to comments</span></a>";
							}
						}

						// if they are a guest don't show them a link
						else
						{
							$subscribe_link = '';
						}

						$templating->block('subscribe', 'articles_full');
						$templating->set('subscribe_link', $subscribe_link);

						$close_comments_link = '';
						if ($user->check_group(1,2) == true)
						{
							if ($article['comments_open'] == 1)
							{
								$close_comments_link = "<a href=\"/index.php?module=articles_full&go=close_comments&article_id={$article['article_id']}\" class=\"white-link\"><span class=\"link_button\">Close Comments</a></span>";
							}
							else if ($article['comments_open'] == 0)
							{
								$close_comments_link = "<a href=\"/index.php?module=articles_full&go=open_comments&article_id={$article['article_id']}\" class=\"white-link\"><span class=\"link_button\">Open Comments</a></span>";
							}
						}
						$templating->set('close_comments', $close_comments_link);
					}

					if ($article['comments_open'] == 0)
					{
						$templating->block('comments_closed', 'articles_full');
					}

					if (core::config('pretty_urls') == 1)
					{
						$pagination_linking = "/articles/$nice_title.{$_GET['aid']}/";
					}
					else
					{
						$pagination_linking = core::config('website_url') . 'index.php?module=articles_full&amp;aid=' . $_GET['aid'] . '&amp;';
					}

					// sort out the pagination link
					$pagination = $core->pagination_link(10, $total_pages, $pagination_linking, $page, '#comments');

					if (isset($_GET['message']))
					{
						if ($_GET['message'] == 'reported')
						{
							$core->message('Thanks, reported that comment! We appreciate the help!');
						}
					}

					//
					/* COMMENTS SECTION */
					//

					$templating->block('comments_top', 'articles_full');

					include('includes/profile_fields.php');

					$db_grab_fields = '';
					foreach ($profile_fields as $field)
					{
						$db_grab_fields .= "u.`{$field['db_field']}`,";
					}

					$db->sqlquery("SELECT a.author_id, a.guest_username, a.comment_text, a.comment_id, a.time_posted, a.last_edited, a.last_edited_time, u.username, u.user_group, u.secondary_user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, $db_grab_fields u.`avatar_uploaded`, ul.username as username_edited FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` ul ON ul.user_id = a.last_edited WHERE a.`article_id` = ? ORDER BY a.`comment_id` ASC LIMIT ?, 10", array($_GET['aid'], $core->start), 'articles_full.php comments');

					$comments_get = $db->fetch_all_rows();

					foreach ($comments_get as $comments)
					{
						$comment_date = $core->format_date($comments['time_posted']);

						if ($comments['author_id'] == 0 || empty($comments['username']))
						{
							if (empty($comments['username']))
							{
								if ($user->check_group(1,2) == true)
								{
									$username = "<a href=\"/admin.php?module=articles&view=comments&ip_id={$comments['comment_id']}\">Guest</a>";
								}
								else
								{
									$username = 'Guest';
								}

							}
							if (!empty($comments['guest_username']))
							{
								if ($user->check_group(1,2) == true)
								{
									$username = "<a href=\"/admin.php?module=articles&view=comments&ip_id={$comments['comment_id']}\">{$comments['guest_username']}</a>";
								}
								else
								{
									$username = $comments['guest_username'];
								}
							}
							$quote_username = preg_replace('/[^A-Za-z0-9 -]+/', "", $comments['guest_username']);
						}
						else
						{
							$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
							$quote_username = preg_replace('/[^A-Za-z0-9 -]+/', "", $comments['username']);
						}

						// sort out the avatar
						// either no avatar (gets no avatar from gravatars redirect) or gravatar set
						if ($comments['avatar_gravatar'] == 1)
						{
							$comment_avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=" . urlencode('//www.gamingonlinux.com/uploads/avatars/no_avatar.png') . '&size=125';
						}

						// either uploaded or linked an avatar
						if (!empty($comments['avatar']) && $comments['avatar_gravatar'] == 0)
						{
							$comment_avatar = $comments['avatar'];
							if ($comments['avatar_uploaded'] == 1)
							{
								$comment_avatar = "/uploads/avatars/{$comments['avatar']}";
							}
						}

						// else no avatar, then as a fallback use gravatar if they have an email left-over
						else if (empty($comments['avatar']) && $comments['avatar_gravatar'] == 0)
						{
							$comment_avatar = "/uploads/avatars/no_avatar.png";
						}

						$editor_bit = '';
						// check if editor or admin
						if ($comments['user_group'] == 1 || $comments['user_group'] == 2)
						{
							$editor_bit = "<li><span class=\"badge editor\">Editor</span></li>";
						}

						// check if accepted submitter
						if ($comments['user_group'] == 5)
						{
							$editor_bit = "<li><span class=\"badge editor\">Contributing Editor</span></li>";
						}

						$into_username = '';
						if (!empty($comments['distro']) && $comments['distro'] != 'Not Listed')
						{
							$into_username .= '<img title="' . $comments['distro'] . '" class="distro tooltip-top"  alt="" src="' . core::config('website_url') . 'templates/default/images/distros/' . $comments['distro'] . '.svg" />';
						}

						$templating->block('article_comments', 'articles_full');
						$templating->set('user_id', $comments['author_id']);
						$templating->set('username', $into_username . $username);
						$templating->set('plain_username', $quote_username);
						$templating->set('editor', $editor_bit);
						$templating->set('comment_avatar', $comment_avatar);
						$templating->set('date', $comment_date);
						$templating->set('tzdate', date('c',$comments['time_posted']) ); //piratelv timeago

						$last_edited = '';
						if ($comments['last_edited'] != 0)
						{
							$last_edited = "\r\n\r\n\r\n[i]Last edited by " . $comments['username_edited'] . ' at ' . $core->format_date($comments['last_edited_time']) . '[/i]';
						}

						$templating->set('text', bbcode($comments['comment_text'] . $last_edited, 0));
						$templating->set('text_plain', htmlspecialchars_decode($comments['comment_text'], ENT_QUOTES));
						$templating->set('article_id', $_GET['aid']);
						$templating->set('comment_id', $comments['comment_id']);

						// Total number of likes for the status message
						$qtotallikes = $db->sqlquery("SELECT COUNT(comment_id) as `total` FROM likes WHERE comment_id = ?", array($comments['comment_id']));
						$get_total = $db->fetch();
						$total_likes = $get_total['total'];

						$templating->set('total_likes', $total_likes);

						$like_text = "Like";
						$like_class = "like";
						if ($_SESSION['user_id'] != 0)
						{
							// Checks current login user liked this status or not
							$qnumlikes = $db->sqlquery("SELECT `like_id` FROM likes WHERE user_id = ? AND comment_id = ?", array($_SESSION['user_id'], $comments['comment_id']));
							$numlikes = $db->num_rows();

							if ($numlikes == 0)
							{
								$like_text = "Like";
								$like_class = "like";
							}
							else if ($numlikes >= 1)
							{
								$like_text = "Unlike";
								$like_class = "unlike";
							}
						}
						$templating->set('like_text', $like_text);
						$templating->set('like_class', $like_class);

						$donator_badge = '';

						if (($comments['secondary_user_group'] == 6 || $comments['secondary_user_group'] == 7) && $comments['user_group'] != 1 && $comments['user_group'] != 2)
						{
							$donator_badge = ' <li><span class="badge supporter">GOL Supporter</span></li>';
						}

						$profile_fields_output = '';

						foreach ($profile_fields as $field)
						{
							if (!empty($comments[$field['db_field']]))
							{

								if ( $comments[$field['db_field']] == $field['base_link'] ){
									//Skip if it's only the first part of the url
									continue;
								}

								if ($field['db_field'] == 'website')
								{
									if (substr($comments[$field['db_field']], 0, 7) != 'http://')
									{
										$comments[$field['db_field']] = 'http://' . $comments[$field['db_field']];
									}
								}

								$url = '';
								if ($field['base_link_required'] == 1 && strpos($comments[$field['db_field']], $field['base_link']) === false ) //base_link_required and not already in the database
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
									$into_output .= "<li><a href=\"$url{$comments[$field['db_field']]}\">$image$span</a></li>";
								}

								$profile_fields_output .= $into_output;
							}
						}

						$templating->set('profile_fields', $profile_fields_output);

						$templating->set('donator_badge', $donator_badge);

						$comment_edit_link = '';
						if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $comments['author_id'] || $user->check_group(1,2) == true && $_SESSION['user_id'] != 0)
						{
							$comment_edit_link = "<li><a class=\"tooltip-top\" title=\"Edit\" href=\"{$config['website_url']}index.php?module=articles_full&amp;view=Edit&amp;comment_id={$comments['comment_id']}&page=$page\"><span class=\"icon edit\">Edit</span></a></li>";
						}
						$templating->set('edit', $comment_edit_link);

						$comment_delete_link = '';
						if ($user->check_group(1,2) == true)
						{
							$comment_delete_link = "<li><a class=\"tooltip-top\" title=\"Delete\" href=\"{$config['website_url']}index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\"><span class=\"icon delete\"></span></a></li>";
						}
						$templating->set('delete', $comment_delete_link);

						$report_link = '';
						if ($_SESSION['user_id'] != 0)
						{
							$report_link = "<li><a class=\"tooltip-top\" href=\"{$config['website_url']}index.php?module=articles_full&amp;go=report_comment&amp;article_id={$_GET['aid']}&amp;comment_id={$comments['comment_id']}\" title=\"Report\"><span class=\"icon flag\">Flag</span></a></li>";
						}
						$templating->set('report_link', $report_link);
					}

					$templating->block('bottom', 'articles_full');
					$templating->set('pagination', $pagination);

					if (isset($_GET['error']))
					{
						if ($_GET['error'] == 'emptycomment')
						{
							$core->message('You cannot post an empty comment dummy!', NULL, 1);
						}

						if ($_GET['error'] == 'doublecomment')
						{
							$core->message('You cannot post the same comment twice dummy!', NULL, 1);
						}

						if ($_GET['error'] == 'locked')
						{
							$core->message('Sorry, the comments were locked while you were writing your reply!', NULL, 1);
						}

						if ($_GET['error'] == 'noid')
						{
							$core->message('Article id was not a number! Stop trying to do something naughty!');
						}
					}

					// only show comments box if the comments are turned on for this article

					if (($article['comments_open'] == 1) || ($article['comments_open'] == 0 && $user->check_group(1,2) == true))
					{
						if ($_SESSION['user_group'] == 4)
						{
							$templating->merge('login');
							$templating->block('small');
						}

						else
						{
							if (!isset($_SESSION['activated']))
							{
								$db->sqlquery("SELECT `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
								$get_active = $db->fetch();
								$_SESSION['activated'] = $get_active['activated'];
							}

							if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
							{
								// find if they have auto subscribe on
								$db->sqlquery("SELECT `auto_subscribe`,`auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']), 'articles_full.php');
								$subscribe_info = $db->fetch();

								$subscribe_check = '';
								if ($subscribe_info['auto_subscribe'] == 1)
								{
									$subscribe_check = 'checked';
								}

								$subscribe_email_check = '';
								if ($subscribe_info['auto_subscribe_email'] == 1)
								{
									$subscribe_email_check = 'checked';
								}

								$comment = '';
								if (isset($_SESSION['acomment']))
								{
									$comment = $_SESSION['acomment'];
								}
								$templating->block('comments_box_top', 'articles_full');
								$templating->set('path', core::config('path'));

								$core->editor('text', $comment);

								$templating->block('comment_buttons', 'articles_full');
								$templating->set('path', core::config('path'));
								$templating->set('subsribe_check', $subscribe_check);
								$templating->set('subscribe_email_check', $subscribe_email_check);
								$templating->set('aid', $_GET['aid']);
							}
						}
					}
				}
			}
		}
	}

	else if (isset($_GET['view']) && $_GET['view'] == 'Edit')
	{
		$templating->set_previous('meta_data', '', 1);

		$db->sqlquery("SELECT c.`author_id`, c.comment_id, c.`comment_text`, c.time_posted, a.`title`, a.article_id FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array($_GET['comment_id']), 'articles_full.php');
		$comment = $db->fetch();

		// check if author
		if ($_SESSION['user_id'] != $comment['author_id'] && $user->check_group(1,2) == false || $_SESSION['user_id'] == 0)
		{
			$nice_title = $core->nice_title($comment['title']);
			header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
			die();
		}

		$templating->set_previous('meta_description', 'Editing a comment on GamingOnLinux', 1);
		$templating->set_previous('title', 'Editing a comment', 1);

		if (isset($_GET['preview']))
		{
			$templating->block('preview_top', 'articles_full');
			$templating->block('preview', 'articles_full');

			$templating->set('username', $_SESSION['username']);

			$templating->set('editor', '');
			$templating->set('edit', '');
			$templating->set('delete', '');
			$templating->set('report_spam', '');
			$templating->set('comment_id', '');

			$date = $core->format_date($comment['time_posted']);

			$templating->set('date', $date);
			$templating->set('tzdate', date('c',$comment['time_posted']) ); //piratelv timeago

			$steam = "<img src=\"/templates/default/images/steam.png\" alt=\"steam\" />";
			$website = "<i class=\"icon-globe\"></i>";

			$templating->set('steam', $steam);
			$templating->set('website', $website);

			$templating->set('text_preview', bbcode($_POST['text']));

			// avatar
			if ($comment['author_id'] != 0)
			{
				$db->sqlquery("SELECT `avatar`, `avatar_gravatar`, `gravatar_email`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($comment['author_id']), 'articles_full.php');
				$comments = $db->fetch();

				// sort out the avatar
				// either no avatar (gets no avatar from gravatars redirect) or gravatar set
				if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
				{
					$comment_avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=//www.gamingonlinux.com/uploads/avatars/no_avatar.png&size=125";
				}

				// either uploaded or linked an avatar
				else
				{
					$comment_avatar = $comments['avatar'];
					if ($comments['avatar_uploaded'] == 1)
					{
						$comment_avatar = "/uploads/avatars/{$comments['avatar']}";
					}
				}
			}

			else
			{
				$comment_avatar = core::config('website_url') . '/uploads/avatars/no_avatar.png';
			}

			$templating->set('comment_avatar', $comment_avatar);
		}

		if (isset($_GET['preview']))
		{
			$comment_text = $_POST['text'];
		}
		else
		{
			$comment_text = $comment['comment_text'];
		}

		if (isset($_GET['error']))
		{
			$comment_text = $_SESSION['acomment'];
		}

		$page = 1;
		if (isset($_GET['page']))
		{
			$page = $_GET['page'];
		}

		$templating->block('edit_top', 'articles_full');

		$core->editor('text', $comment_text);

		$templating->block('edit_comment_buttons', 'articles_full');
		$templating->set('comment_id', $comment['comment_id']);
		$templating->set('path', core::config('path'));
		$templating->set('page', $page);
	}
}

else if (isset($_GET['go']))
{
	if ($_GET['go'] == 'comment')
	{
		// make sure news id is a number
		if (!isset($_POST['aid']) || !is_numeric($_POST['aid']))
		{
			if (core::config('pretty_urls') == 1)
			{
				header("Location: " . core::config('website_url') . "/articles/$title_nice.{$_POST['aid']}/error=noid#commentbox");
			}
			else {
				header("Location: " . core::config('website_url') . "/index.php?module=articles_full&aid={$_POST['aid']}&error=noid#commentbox");
			}

			die();
		}

		// had to put this in, as somehow a guest was able to comment even without showing a textarea to them (HIGHLY CONFUSED HOW)
		else if ($_SESSION['user_id'] == 0)
		{
			$core->message('You do not have permisions to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else if ($parray['comment_on_articles'] == 0)
		{
			$core->message('You do not have permisions to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else
		{
			// check to make sure their IP isn't banned
			$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array($core->ip));
			if ($db->num_rows() >= 1)
			{
				header("Location: /home/banned");
			}

			else
			{
				// get article name for the email and redirect
				$db->sqlquery("SELECT `title`, `comment_count`, `comments_open` FROM `articles` WHERE `article_id` = ?", array($_POST['aid']));
				$title = $db->fetch();
				$title_nice = $core->nice_title($title['title']);

				if ($title['comments_open'] == 0)
				{
					if (core::config('pretty_urls') == 1)
					{
						header("Location: " . core::config('website_url') . "articles/$title_nice.{$_POST['aid']}/error=locked#commentbox");
					}
					else {
						header("Location: " . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['aid']}&error=locked#commentbox");
					}

					die();
				}
				else
				{
					// sort out what page the new comment is on, if current is 9, the next comment is on page 2, otherwise round up for the correct page
					$comment_page = 1;
					if ($title['comment_count'] >= 10)
					{
						$new_total = $title['comment_count']+1;
						$comment_page = ceil($new_total/10);
					}

					// check empty
					$comment = trim($_POST['text']);

					// check for double comment
					$db->sqlquery("SELECT `comment_text` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `comment_id` DESC LIMIT 1", array($_POST['aid']));
					$check_comment = $db->fetch();

					if ($check_comment['comment_text'] == $comment)
					{
						if (core::config('pretty_urls') == 1)
						{
							header("Location: " . core::config('website_url') . "articles/$title_nice.{$_POST['aid']}/error=doublecomment#commentbox");
						}
						else {
							header("Location: " . core::config('website_url') . "/index.php?module=articles_full&aid={$_POST['aid']}&error=doublecomment#commentbox");
						}

						die();
					}

					if (empty($comment))
					{
						if (core::config('pretty_urls') == 1)
						{
							header("Location: " . core::config('website_url') . "articles/$title_nice.{$_POST['aid']}/error=emptycomment#commentbox");
						}
						else {
							header("Location: " . core::config('website_url') . "/index.php?module=articles_full&aid={$_POST['aid']}&error=emptycomment#commentbox");
						}

						die();
					}

					else
					{
						$comment = htmlspecialchars($comment, ENT_QUOTES);

						$article_id = $_POST['aid'];

						// add the comment
						$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($_POST['aid'], $_SESSION['user_id'], $core->date, $comment));

						$new_comment_id = $db->grab_id();

						// update the news items comment count
						$db->sqlquery("UPDATE `articles` SET `comment_count` = (comment_count + 1) WHERE `article_id` = ?", array($article_id));

						// update the posting users comment count
						$db->sqlquery("UPDATE `users` SET `comment_count` = (comment_count + 1) WHERE `user_id` = ?", array($_SESSION['user_id']));

						// check if they are subscribing
						if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
						{
							// make sure we don't make lots of doubles
							$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));

							$emails = 0;
							if (isset($_POST['emails']))
							{
								$emails = 1;
							}

							$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?, `send_email` = ?", array($_SESSION['user_id'], $article_id, $emails, $emails));
						}

						// email anyone subscribed which isn't you
						$db->sqlquery("SELECT s.`user_id`, s.emails, u.email, u.username FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.send_email = 1 AND s.emails = 1", array($article_id));
						$users_array = array();
						while ($users = $db->fetch())
						{
							if ($users['user_id'] != $_SESSION['user_id'] && $users['emails'] == 1)
							{
								$users_array[$users['user_id']]['user_id'] = $users['user_id'];
								$users_array[$users['user_id']]['email'] = $users['email'];
								$users_array[$users['user_id']]['username'] = $users['username'];
							}
						}

						// send the emails
						foreach ($users_array as $email_user)
						{
							$to = $email_user['email'];

							// set the title to upper case
							$title_upper = $title['title'];

							// subject
							$subject = "New reply to article {$title_upper} on GamingOnLinux.com";

							$username = $_SESSION['username'];

							$comment_email = email_bbcode($comment);

							$message = '';

							// message
							$html_message = "
							<html>
							<head>
							<title>New reply to an article you follow on GamingOnLinux.com</title>
							<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
							</head>
							<body>
							<img src=\"{$config['website_url']}templates/default/images/icon.png\" alt=\"Gaming On Linux\">
							<br />
							<p>Hello <strong>{$email_user['username']}</strong>,</p>
							<p><strong>{$username}</strong> has replied to an article you follow on titled \"<strong><a href=\"{$config['website_url']}articles/$title_nice.$article_id/page=$comment_page#r{$new_comment_id}\">{$title_upper}</a></strong>\". There may be more comments after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
							<div>
						 	<hr>
						 	{$comment_email}
						 	<hr>
						 	You can unsubscribe from this article by <a href=\"{$config['website_url']}unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"{$config['website_url']}usercp.php\">User Control Panel</a>.
						 	<hr>
						  	<p>If you haven&#39;t registered at <a href=\"{$config['website_url']}\" target=\"_blank\">{$config['website_url']}</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
						  	<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
						  	<p>-----------------------------------------------------------------------------------------------------------</p>
							</div>
							</body>
							</html>
							";

							$plain_message = PHP_EOL."Hello {$email_user['username']}, {$username} replied to an article on {$config['website_url']}articles/$title_nice.$article_id/page=$comment_page#r{$new_comment_id}\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: {$config['website_url']}unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}";

							$boundary = uniqid('np');

							// To send HTML mail, the Content-type header must be set
							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= "Content-Type: multipart/alternative;charset=utf-8;boundary=" . $boundary . "\r\n";
							$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: ".core::genReplyAddress($article_id,'comment')."\r\n";

							$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
							$message .= "Content-Type: text/plain;charset=utf-8".PHP_EOL;
							$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
							$message .= $plain_message;

							$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
							$message .= "Content-Type: text/html;charset=utf-8".PHP_EOL;
							$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
							$message .= "$html_message";

							$message .= "\r\n\r\n--" . $boundary . "--";

							// Mail it
							if ($config['send_emails'] == 1)
							{
								mail($to, $subject, $message, $headers);
							}

							// remove anyones send_emails subscription setting if they have it set to email once
							$db->sqlquery("SELECT `email_options` FROM `users` WHERE `user_id` = ?", array($email_user['user_id']));
							$update_sub = $db->fetch();

							if ($update_sub['email_options'] == 2)
							{
								$db->sqlquery("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($article_id, $email_user['user_id']));
							}
						}

						// try to stop double postings, clear text
						unset($_POST['text']);

						// clear any comment or name left from errors
						unset($_SESSION['acomment']);
						unset($_SESSION['bad']);

						if (core::config('pretty_urls') == 1)
						{
							header("Location: /articles/$title_nice.$article_id/page={$comment_page}#{$new_comment_id}");
						}
						else
						{
							header("Location: " . core::config('website_url') . "index.php?module=articles_full&aid=$article_id&page={$comment_page}#{$new_comment_id}");
						}
					}
				}
			}
		}
	}

	if ($_GET['go'] == 'preview')
	{
		if (empty($_POST['text']))
		{
			$db->sqlquery("SELECT a.`title` FROM `articles` a WHERE `article_id` = ?", array($_POST['aid']), 'articles_full.php');
			$comment = $db->fetch();

			$nice_title = $core->nice_title($comment['title']);
			if (core::config('pretty_urls') == 1)
			{
				header("Location: /articles/$nice_title.{$_POST['aid']}/error=emptycomment#commentbox");
			}
			else {
				header("Location: ".url."index.php?module=articles_full&aid={$_POST['aid']}&error=emptycomment#commentbox");
			}

		}

		else
		{
			$templating->block('preview', 'articles_full');
			$templating->set_previous('meta_description', 'Previewing a comment on GamingOnLinux.com', 1);
			$templating->set_previous('title', ' - Previewing comment', 1);

			if ($_SESSION['user_id'] == 0)
			{
				$username = $_POST['guest_name'];
			}

			else
			{
				$username = $_SESSION['username'];
			}

			$templating->set('username', $username);

			$templating->set('editor', '');
			$templating->set('edit', '');
			$templating->set('delete', '');
			$templating->set('report_spam', '');
			$templating->set('comment_id', '');

			$date = $core->format_date($core->date);

			$templating->set('date', $date);

			$steam = "<img src=\"/templates/default/images/steam.png\" alt=\"steam\" />";
			$website = "<i class=\"icon-globe\"></i>";
			$twitter = "<img src=\"/templates/default/images/twitter.gif\" alt=\"twitter\" />";

			$templating->set('steam', $steam);
			$templating->set('website', $website);
			$templating->set('twitter', $twitter);

			$text_preview = trim($_POST['text']);
			$text_preview = htmlspecialchars($text_preview);
			$templating->set('text_preview', bbcode($text_preview));

			// avatar
			$db->sqlquery("SELECT `avatar`, `avatar_gravatar`, `gravatar_email`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']), 'articles_full.php');
			$comments = $db->fetch();

			// sort out the avatar
			// either no avatar (gets no avatar from gravatars redirect) or gravatar set
			if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
			{
				$comment_avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=//www.gamingonlinux.com/uploads/avatars/no_avatar.png&size=125";
			}

			// either uploaded or linked an avatar
			else
			{
				$comment_avatar = $comments['avatar'];
				if ($comments['avatar_uploaded'] == 1)
				{
					$comment_avatar = "/uploads/avatars/{$comments['avatar']}";
				}
			}

			$templating->set('comment_avatar', $comment_avatar);

			// find if they have auto subscribe on
			$db->sqlquery("SELECT `auto_subscribe`,`auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']), 'articles_full.php');
			$subscribe_info = $db->fetch();

			$subscribe_check = '';
			if ($subscribe_info['auto_subscribe'] == 1)
			{
				$subscribe_check = 'checked';
			}

			$subscribe_email_check = '';
			if ($subscribe_info['auto_subscribe_email'] == 1)
			{
				$subscribe_email_check = 'checked';
			}

			$templating->block('comments_box_top', 'articles_full');

			$core->editor('text', $_POST['text']);

			$templating->block('comment_buttons', 'articles_full');
			$templating->set('path', core::config('path'));
			$templating->set('subsribe_check', $subscribe_check);
			$templating->set('subscribe_email_check', $subscribe_email_check);
			$templating->set('aid', $_POST['aid']);
		}
	}

	if ($_GET['go'] == 'editcomment')
	{
		$db->sqlquery("SELECT c.`author_id`, c.`comment_text`, a.`title`, a.`article_id` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array($_POST['comment_id']), 'articles_full.php');
		$comment = $db->fetch();

		// check if author or editor/admin
		if ($_SESSION['user_id'] != $comment['author_id'] && $user->check_group(1,2) == false || $_SESSION['user_id'] == 0)
		{
			$nice_title = $core->nice_title($comment['title']);
			header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		}

		// do the edit since we are allowed
		else
		{
			$comment_text = trim($_POST['text']);
			// check empty
			if (empty($comment_text))
			{
				$core->message('You cannot post an empty comment');
			}

			// update comment
			else
			{
				unset($_SESSION['bad']);
				$comment_text = htmlspecialchars($comment_text, ENT_QUOTES);

				$db->sqlquery("UPDATE `articles_comments` SET `comment_text` = ?, `last_edited` = ?, `last_edited_time` = ? WHERE `comment_id` = ?", array($comment_text, $_SESSION['user_id'], $core->date, $_POST['comment_id']));

				$nice_title = $core->nice_title($comment['title']);

				if ($config['pretty_urls'] == 1)
				{
					header("Location: /articles/$nice_title.{$comment['article_id']}/page={$_GET['page']}#comments");
				}
				else {
					header("Location: ".url."index.php?module=articles_full&aid={$comment['article_id']}&page={$_GET['page']}#comments");
				}

			}
		}
	}

	if ($_GET['go'] == 'deletecomment')
	{
		$db->sqlquery("SELECT c.`author_id`, c.`comment_text`, c.`spam`, a.`title`, a.`article_id` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array($_GET['comment_id']));
		$comment = $db->fetch();

		$nice_title = $core->nice_title($comment['title']);

		if ($user->check_group(1,2) == false)
		{
			if ($config['pretty_urls'] == 1)
			{
				header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
			}
			else {
				header("Location: ".url."index.php?module=articles_full&aid={$comment['article_id']}#comments");
			}

		}

		else
		{
			if ($comment['author_id'] == 1 && $_SESSION['user_id'] != 1)
			{
				if ($config['pretty_urls'] == 1)
				{
					header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
				}
				else {
					header("Location: ".url."index.php?module=articles_full&aid={$comment['article_id']}#comments");
				}
			}

			else
			{
				if (!isset($_POST['yes']) && !isset($_POST['no']))
				{
					$templating->set_previous('title', ' - Deleting comment', 1);
					$core->yes_no('Are you sure you want to delete that comment?', url."index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$_GET['comment_id']}");
				}

				else if (isset($_POST['no']))
				{
					if ($config['pretty_urls'] == 1)
					{
						header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
					}
					else {
						header("Location: ".url."index.php?module=articles_full&aid={$comment['article_id']}#comments");
					}

				}

				else if (isset($_POST['yes']))
				{
					// this comment was reported as spam but as its now deleted remove the notification
					if ($comment['spam'] == 1)
					{
						$db->sqlquery("DELETE FROM `admin_notifications` WHERE `comment_id` = ?", array($_GET['comment_id']));
					}

					$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `created` = ?, `action` = ?, `completed_date` = ?, `comment_id` = ?, `content` = ?", array($core->date, "{$_SESSION['username']} deleted a comment.", $core->date, $_GET['comment_id'], $comment['comment_text']));

					// delete comment and update comments counter
					$db->sqlquery("UPDATE `articles` SET `comment_count` = (comment_count - 1) WHERE `article_id` = ?", array($comment['article_id']));
					$db->sqlquery("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array($_GET['comment_id']));
					$db->sqlquery("DELETE FROM `likes` WHERE `comment_id` = ?", array($_GET['comment_id']));

					if ($config['pretty_urls'] == 1)
					{
						header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
					}
					else {
						header("Location: ".url."index.php?module=articles_full&aid={$comment['article_id']}#comments");
					}
				}
			}
		}
	}

	if ($_GET['go'] == 'subscribe')
	{
		// make sure we don't make lots of doubles
		$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['article_id']));

		// now subscribe
		$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?", array($_SESSION['user_id'], $_GET['article_id']));

		// get info for title
		$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
		$title = $db->fetch();
		$title = $core->nice_title($title['title']);

		header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
	}

	if ($_GET['go'] == 'unsubscribe')
	{
		$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['article_id']));

		// get info for title
		$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
		$title = $db->fetch();
		$title = $core->nice_title($title['title']);

		header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
	}

	if ($_GET['go'] == 'report_comment')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Reporting a comment', 1);

			// show the comment they are reporting
			$db->sqlquery("SELECT c.`comment_text`, u.avatar, u.avatar_gravatar, u.gravatar_email, u.avatar_uploaded FROM `articles_comments` c LEFT JOIN users u ON u.user_id = c.author_id WHERE c.`comment_id` = ?", array($_GET['comment_id']));
			$comment = $db->fetch();
			$templating->block('report', 'articles_full');
			$templating->set('text', bbcode($comment['comment_text']));

			// sort out the avatar
			// either no avatar (gets no avatar from gravatars redirect) or gravatar set
			if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
			{
				$comment_avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=//www.gamingonlinux.com/uploads/avatars/no_avatar.png&size=125";
			}

			// either uploaded or linked an avatar
			else
			{
				$comment_avatar = $comments['avatar'];
				if ($comments['avatar_uploaded'] == 1)
				{
					$comment_avatar = "/uploads/avatars/{$comments['avatar']}";
				}
			}

			$templating->set('comment_avatar', $comment_avatar);

			$core->yes_no('Are you sure you wish to report that comment?', url."index.php?module=articles_full&go=report_comment&article_id={$_GET['article_id']}&comment_id={$_GET['comment_id']}", "");
		}
		else if (isset($_POST['no']))
		{
			// get info for title
			$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
			$title = $db->fetch();
			$title = $core->nice_title($title['title']);

			header("Location: /articles/{$title}.{$_GET['article_id']}/#comments");
		}

		else
		{
			if ($_SESSION['user_group'] != 4)
			{
				// update admin notifications
				$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `comment_id` = ?, `reported_comment` = 1", array("{$_SESSION['username']} reported an article comment.", $core->date, $_GET['comment_id']));

				$db->sqlquery("UPDATE `articles_comments` SET `spam` = 1, `spam_report_by` = ? WHERE `comment_id` = ?", array($_SESSION['user_id'], $_GET['comment_id']));
			}

			// get info for title
			$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
			$title = $db->fetch();
			$title = $core->nice_title($title['title']);

			header("Location: /articles/{$title}.{$_GET['article_id']}/message=reported#comments");
		}
	}

	if ($_GET['go'] == 'open_comments')
	{
		if ($user->check_group(1,2) == true)
		{
			// get info for title
			$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
			$title = $db->fetch();
			$title_nice = $core->nice_title($title['title']);

			header("Location: /articles/{$title}.{$_GET['article_id']}#comments");

			if ($user->check_group(1,2) == false)
			{
				header("Location: /articles/$title_nice.{$comment['article_id']}#comments");
			}

			else
			{
				$db->sqlquery("UPDATE `articles` SET `comments_open` = 1 WHERE `article_id` = ?", array($_GET['article_id']));
			}

			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `article_id` = ?", array("{$_SESSION['username']} Opened the comments on {$title['title']}", $core->date, $_GET['article_id']));

			header("Location: /articles/{$title_nice}.{$_GET['article_id']}#comments");
		}

		else
		{
			header("Location: ".url);
		}
	}

	if ($_GET['go'] == 'close_comments')
	{
		if ($user->check_group(1,2) == true)
		{
			// get info for title
			$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
			$title = $db->fetch();
			$title_nice = $core->nice_title($title['title']);

			header("Location: /articles/{$title}.{$_GET['article_id']}#comments");

			if ($user->check_group(1,2) == false)
			{
				header("Location: /articles/$title_nice.{$comment['article_id']}#comments");
			}

			else
			{
				$db->sqlquery("UPDATE `articles` SET `comments_open` = 0 WHERE `article_id` = ?", array($_GET['article_id']));
			}

			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `article_id` = ?", array("{$_SESSION['username']} Closed the comments on {$title['title']}", $core->date, $_GET['article_id']));

			header("Location: /articles/{$title_nice}.{$_GET['article_id']}#comments");
		}

		else
		{
			header("Location: ".url);
		}
	}
}
