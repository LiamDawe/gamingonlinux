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
		if (!isset($_GET['aid']) || core::is_number($_GET['aid']) == false)
		{
			http_response_code(404);
			$templating->set_previous('meta_data', '', 1);
			$templating->set_previous('title', 'No id entered', 1);
			$core->message('That is not a correct article id!');
		}

		else
		{
			// get the article
			$db->sqlquery("SELECT
				a.`article_id`,
				a.`slug`,
				a.`preview_code`,
				a.`title`,
				a.`text`,
				a.`tagline`,
				a.`date`,
				a.`date_submitted`,
				a.`author_id`,
				a.`active`,
				a.`guest_username`,
				a.`views`,
				a.`tagline_image`,
				a.`gallery_tagline`,
				t.`filename` as `gallery_tagline_filename`,
				a.`comments_open`,
				u.`username`,
				u.`avatar`,
				u.`avatar_gravatar`,
				u.`gravatar_email`,
				u.`avatar_uploaded`,
				u.`avatar_gallery`,
				u.`article_bio`,
				u.`user_group`,
				u.`twitter_on_profile`
				FROM `articles` a
				LEFT JOIN
				`users` u on a.`author_id` = u.`user_id`
				LEFT JOIN
				`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
				WHERE
				a.`article_id` = ?", array((int) $_GET['aid']));
			$article = $db->fetch();

			// FIND THE CORRECT PAGE IF THEY HAVE A LINKED COMMENT
			if (isset($_GET['comment_id']) && core::is_number($_GET['comment_id']))
			{
				// have they come from a notification/alert box link?
				if (isset($_GET['clear_note']) && core::is_number($_GET['clear_note']))
				{
					// make sure they own it to clear it
					$db->sqlquery("SELECT `owner_id` FROM `user_notifications` WHERE `id` = ?", array((int) $_GET['clear_note']));
					$check_note = $db->fetch();
					if ($check_note['owner_id'] == $_SESSION['user_id'])
					{
						// they have seen it and when they saw it
						$db->sqlquery("UPDATE `user_notifications` SET `seen` = 1, `seen_date` = ? WHERE `id` = ?", array(core::$date, (int) $_GET['clear_note']));
					}
				}

				// check comment still exists
				$db->sqlquery("SELECT COUNT(`comment_id`) as `count` FROM `articles_comments` WHERE `comment_id` = ? AND `article_id` = ?", array((int) $_GET['comment_id'], (int) $_GET['aid']));
				$check = $db->fetch();
				if ($check['count'] == 1)
				{
					// see if we are above their set limit per-page
					$db->sqlquery("SELECT `comment_count` FROM `articles` WHERE `article_id` = ?", array($_GET['aid']));
					$count = $db->fetch();

					if ($count['comment_count'] > $_SESSION['per-page'])
					{
						// count how many are below and equal to this comment, to find how many comments that is
						$db->sqlquery("SELECT count(`comment_id`) as counter FROM `articles_comments` WHERE `article_id` = ? AND `comment_id` <= ?", array((int) $_GET['aid'], (int) $_GET['comment_id']));
						$current_number = $db->fetch();

						$last_page = ceil($current_number['counter']/$_SESSION['per-page']);
						
						$article_link = article_class::get_link($article['article_id'], $article['slug'], 'page=' . $last_page . '#r' . $_GET['comment_id']);

						header("Location: " . $article_link);

					}
					else
					{
						$article_link = article_class::get_link($article['article_id'], $article['slug'], '#r' . $_GET['comment_id']);

						header("Location: " . $article_link);
					}
				}
				else
				{
					$_SESSION['message'] = 'nocomment';
					$article_link = article_class::get_link($article['article_id'], $article['slug']);

					header("Location: " . $article_link);
				}
			}

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

			else if ($article['active'] == 0 && !isset($_GET['preview_code']))
			{
				$templating->set_previous('meta_description', 'Article error on GamingOnLinux', 1);
				$templating->set_previous('title', 'Article Inactive', 1);
				$templating->set_previous('meta_data', '', 1);
				$core->message('This article is currently inactive!');
			}

			else if ($article['active'] == 0 && isset($_GET['preview_code']) && $article['preview_code'] != $_GET['preview_code'])
			{
				$templating->set_previous('meta_description', 'Article error on GamingOnLinux', 1);
				$templating->set_previous('title', 'Article Inactive', 1);
				$templating->set_previous('meta_data', '', 1);
				$core->message('This article is currently inactive!');
			}

			else if ($article['active'] == 0 && $article['preview_code'] == $_GET['preview_code'] || $article['active'] == 1)
			{
				$templating->set_previous('meta_description', $article['tagline'], 1);

				if (!isset($_GET['preview_code']))
				{
					$templating->set_previous('title', $article['title'], 1);

					// update the view counter if it is not a preview
					$db->sqlquery("UPDATE `articles` SET `views` = (views + 1) WHERE `article_id` = ?", array($article['article_id']));
				}
				else
				{
					$templating->set_previous('title', 'PREVIEW: ' . $article['title'], 1);

					$core->message('Article currently inactive, you are seeing a private preview. Please do not share this unless you have been given permission.');
				}

				// set the article image meta
				$article_meta_image = '';
				if (!empty($article['tagline_image']))
				{
					$article_meta_image = core::config('website_url') . "uploads/articles/tagline_images/{$article['tagline_image']}";
				}
				if (!empty($article['gallery_tagline_filename']))
				{
					$article_meta_image = core::config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}";
				}

				$nice_title = core::nice_title($article['title']);

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
				$templating->set_previous('meta_data', "<meta property=\"og:image\" content=\"$article_meta_image\"/>\n<meta property=\"og:image_url\" content=\"$article_meta_image\"/>\n<meta property=\"og:type\" content=\"article\">\n<meta property=\"og:title\" content=\"" . $article['title'] . "\" />\n<meta property=\"og:description\" content=\"{$article['tagline']}\" />\n<meta property=\"og:url\" content=\"" . core::config('website_url') . "/articles/$nice_title.{$article['article_id']}\" />\n<meta itemprop=\"image\" content=\"$article_meta_image\" />\n<meta itemprop=\"title\" content=\"" . $article['title'] . "\" />\n<meta itemprop=\"description\" content=\"{$article['tagline']}\" />\n$twitter_card", 1);

				// make date human readable
				$date = $core->format_date($article['date']);

				$templating->block('article', 'articles_full');
				$templating->set('url', core::config('website_url'));
				
				$share_url = article_class::get_link($_GET['aid'], $nice_title);
				
				$twitter_share = '';
				if (!empty(core::config('twitter_username')))
				{
					$twitter_share = '<a class="button small fnone" href="https://twitter.com/intent/tweet?text='.$article['title'].'%20%23Linux&amp;url='.$share_url.'&amp;via='.core::config('twitter_username').'" target="_blank"><img src="'.core::config('website_url') . 'templates/' . core::config('template') .'/images/social/twitter.svg" alt="" /></a>';
				}
				$templating->set('twitter_share', $twitter_share);

				$fb_onclick = "window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent('$share_url'), 'height=279, width=575'); return false;";
				
				$facebook_share = '<a class="button small fnone" href="#" onclick="'.$fb_onclick.'" target="_blank"><img src="'.core::config('website_url') . 'templates/' . core::config('template') .'/images/social/facebook.svg" alt="" /></a>';
				$templating->set('facebook_share', $facebook_share);
				
				
				
				$gplus_share = '<a class="button small fnone" href="https://plusone.google.com/_/+1/confirm?hl=en&url='.$share_url.'" target="_blank"><img src="'.core::config('website_url') . 'templates/' . core::config('template') .'/images/social/google-plus.svg" alt="" /></a>';
				$templating->set('gplus_share', $gplus_share);

				if (core::config('pretty_urls') == 1)
				{
					$article_link = "https://www.gamingonlinux.com/articles/$nice_title.{$_GET['aid']}";
				}
				else
				{
					$article_link = "/index.php?module=articles_full&$nice_title&aid={$_GET['aid']}";
				}
				$templating->set('article_link', $article_link);

				$templating->set('rules', core::config('rules'));

				if (($user->check_group([1,2,5]) == true) && !isset($_GET['preview']))
				{
					$templating->set('edit_link', " <a href=\"" . core::config('website_url') . "admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\">Edit</a>");
					$templating->set('admin_button', '');
				}

				else if (($user->check_group([1,2,5]) == true) && isset($_GET['preview']))
				{
					$page_action = 'admin.php?module=adminreview';
					if (isset($_GET['submitted']) && $_GET['submitted'] == 1)
					{
						$page_action ='admin.php?module=articles&view=Submitted';
					}
					if (isset($_GET['draft']) && $_GET['draft'] == 1)
					{
						$page_action ='admin.php?module=articles&view=drafts';
					}
					$templating->set('edit_link', '');
					$templating->set('admin_button', "<form method=\"post\"><button type=\"submit\" class=\"btn btn-info\" formaction=\"" . core::config('website_url') . "{$page_action}\">Back</button> <button type=\"submit\" formaction=\"" . core::config('url') . "{$page_action}&aid={$_GET['aid']}\" class=\"btn btn-info\">Edit</button></form>");
				}

				if ($user->check_group([1,2,5]) == false)
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
					$view_more = " | <a href=\"/index.php?module=search&amp;author_id={$article['author_id']}\">View more articles from {$article['username']}</a>";
				}

				$templating->set('username', $username);

				$templating->set('date', $date);

				$templating->set('article_views', $article['views']);

				$article_bottom = '';
				if ($article['user_group'] != 1 && $article['user_group'] != 2 && $article['user_group'] != 5)
				{
					$article_bottom = "\n<br /><br /><p class=\"small muted\">This article was submitted by a guest, we encourage anyone to <a href=\"//www.gamingonlinux.com/submit-article/\">submit their own articles</a>.</p>";
				}

				//piratelv timeago: 12/11/14
				$templating->set('article_meta', "<meta itemprop=\"image\" content=\"$article_meta_image\" /> <script>var postdate=new Date('".date('c', $article['date'])."')</script>");

				$tagline_bbcode = '';
				$bbcode_tagline_gallery = NULL;
				if (!empty($article['tagline_image']))
				{
					$tagline_bbcode  = $article['tagline_image'];
				}
				if (!empty($article['gallery_tagline']))
				{
					$tagline_bbcode = $article['gallery_tagline_filename'];
					$bbcode_tagline_gallery = 1;
				}

				$article_page = 1;
				if (isset($_GET['article_page']) && is_numeric($_GET['article_page']))
				{
					$article_page = $_GET['article_page'];
				}

				// sort out the pages and pagination and only return the page requested
				if ($_SESSION['single_article_page'] == 0)
				{
					$pages_array = explode('<*PAGE*>', $article['text']);
					$article_page_count = count($pages_array);
					$pages_array = array_combine(range(1, count($pages_array)), $pages_array);
					$article_body = $pages_array[$article_page];
				}
				else
				{
					$article_body = str_replace('<*PAGE*>', '', $article['text']);
					$article_page_count = 1;
				}
				
				$templating->set('this_template', core::config('website_url') . 'templates/' . core::config('template'));

				$templating->set('text', bbcode($article_body, 1, 1, $tagline_bbcode, $bbcode_tagline_gallery) . $article_bottom);

				$article_link = "/articles/$nice_title.{$_GET['aid']}/";
				if (isset($_GET['preview']))
				{
					$article_link = "/index.php?module=articles_full&amp;aid={$_GET['aid']}&amp;preview&amp;";
				}

				$article_pagination = $core->article_pagination($article_page, $article_page_count, $article_link);

				$templating->set('paging', $article_pagination);

				$categories_list = '';
				// sort out the categories (tags)
				$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? ORDER BY r.`category_id` = 60 DESC, r.`category_id` ASC", array($article['article_id']));
				while ($get_categories = $db->fetch())
				{
					$category_name = str_replace(' ', '-', $get_categories['category_name']);
					if ($get_categories['category_id'] == 60)
					{
						$categories_list .= " <li class=\"ea\"><a href=\"/articles/category/$category_name\">{$get_categories['category_name']}</a></li> ";
					}

					else
					{
						$categories_list .= " <li><a href=\"/articles/category/$category_name\">{$get_categories['category_name']}</a></li> ";
					}
				}
				
				$categories_list .= plugins::do_hooks('display_article_tags_list', $article['article_id']);

				if (!empty($categories_list))
				{
					$templating->block('tags', 'articles_full');
					$templating->set('categories_list', $categories_list);
				}

				// article meta for bookmarking, likes etc
				$templating->block('article_meta', 'articles_full');

				$bookmark_link = '';
				if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
				{
					$db->sqlquery("SELECT `data_id` FROM `user_bookmarks` WHERE `data_id` = ? AND `user_id` = ? AND `type` = 'article'", array($article['article_id'], (int) $_SESSION['user_id']));
					if ($db->num_rows() == 1)
					{
						$bookmark_link = '<a href="#" class="bookmark-content tooltip-top bookmark-saved" data-page="normal" data-type="article" data-id="'.$article['article_id'].'" data-method="remove" title="Remove Bookmark"><span class="icon bookmark"></span></a>';
					}
					else
					{
						$bookmark_link = '<a href="#" class="bookmark-content tooltip-top" data-page="normal" data-type="article" data-id="'.$article['article_id'].'" data-method="add" title="Bookmark"><span class="icon bookmark"></span></a>';
					}
				}
				$templating->set('bookmark_link', $bookmark_link);

				// Total number of likes for the status message
				$db->sqlquery("SELECT COUNT(article_id) as `total` FROM `article_likes` WHERE `article_id` = ?", array($article['article_id']));
				$get_article_likes = $db->fetch();
				$total_alikes = $get_article_likes['total'];

				$templating->set('total_likes', $total_alikes);

				$who_likes_alink = '';
				if ($total_alikes > 0)
				{
					$who_likes_alink = ', <a class="who_likes fancybox.ajax" data-fancybox-type="ajax" href="/includes/ajax/who_likes.php?article_id='.$article['article_id'].'">Who?</a>';
				}
				$templating->set('who_likes_alink', $who_likes_alink);

				$like_button = '';
				if ($_SESSION['user_group'] != 4)
				{
					$like_text = "Like";
					$like_class = "like";
					if ($_SESSION['user_id'] != 0)
					{
						// Checks current login user liked this status or not
						$qnumlikes = $db->sqlquery("SELECT `like_id` FROM `article_likes` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], $article['article_id']));
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

					// don't let them like their own post
					if ($article['author_id'] == $_SESSION['user_id'])
					{
						$like_button = '';
					}
					else
					{
						$like_button = '<a class="likearticle tooltip-top" data-type="article" data-id="'.$article['article_id'].'" title="Like"><span class="icon '.$like_class.'">'.$like_text.'</span></a>';
					}
				}
				$templating->set('like_button', $like_button);

				$templating->block('article_bottom', 'articles_full');

				if ($_SESSION['user_id'] > 0)
				{
					$templating->block('corrections', 'articles_full');
					$templating->set('article_id', $article['article_id']);
				}

				// get the comments if we aren't in preview mode
				if ($article['active'] == 1)
				{
					// count how many there is in total
					$sql_count = "SELECT `comment_id` FROM `articles_comments` WHERE `article_id` = ?";
					$db->sqlquery($sql_count, array($_GET['aid']));
					$total_comments = $db->num_rows();

					//lastpage is = total comments / items per page, rounded up.
					if ($total_comments <= 10)
					{
						$lastpage = 1;
					}
					else
					{
						$lastpage = ceil($total_comments/$_SESSION['per-page']);
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

					if ($page > $lastpage)
					{
						$page = $lastpage;
					}

					// update their subscriptions if they are reading the last page
					if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
					{
						$db->sqlquery("SELECT `send_email` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], (int) $_GET['aid']));
						$count_rows = $db->num_rows();
						if ($count_rows == 1)
						{
							$check_sub = $db->fetch();

							if ($_SESSION['email_options'] == 2 && $check_sub['send_email'] == 0)
							{
								// they have read all new comments (or we think they have since they are on the last page)
								if ($page == $lastpage)
								{
									// send them an email on a new comment again
									$db->sqlquery("UPDATE `articles_subscriptions` SET `send_email` = 1 WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], (int) $_GET['aid']));
								}
							}
						}
					}

					$subscribe_link = '';
					$close_comments_link = '';
					if ($_SESSION['user_group'] != 4)
					{
						// find out if this user has subscribed to the comments
						if ($_SESSION['user_id'] != 0)
						{
							$db->sqlquery("SELECT `user_id` FROM `articles_subscriptions` WHERE `article_id` = ? AND `user_id` = ?", array((int) $_GET['aid'], (int) $_SESSION['user_id']));
							if ($db->num_rows() == 1)
							{
								$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"unsubscribe\" data-article-id=\"{$_GET['aid']}\" href=\"/index.php?module=articles_full&amp;go=unsubscribe&amp;article_id={$_GET['aid']}\" class=\"white-link\"><span class=\"link_button\">Unsubscribe</span></a>";
							}

							else
							{
								$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"subscribe\" data-article-id=\"{$_GET['aid']}\" href=\"/index.php?module=articles_full&amp;go=subscribe&amp;article_id={$_GET['aid']}\" class=\"white-link\"><span class=\"link_button\">Subscribe</span></a>";
							}
						}

						if ($user->check_group([1,2]) == true)
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
					$pagination = $core->pagination_link($_SESSION['per-page'], $total_comments, $pagination_linking, $page, '#comments');
					$pagination_head = $core->head_pagination($_SESSION['per-page'], $total_comments, $pagination_linking, $page, '#comments');

					//
					/* COMMENTS SECTION */
					//

					if ($total_comments > 0 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
					{
						// first grab a list of their bookmarks
						$db->sqlquery("SELECT `data_id` FROM `user_bookmarks` WHERE `type` = 'comment' AND `parent_id` = ? AND `user_id` = ?", array((int) $_GET['aid'], (int) $_SESSION['user_id']));
						$bookmarks_array = $db->fetch_all_rows(PDO::FETCH_COLUMN);
					}

					$templating->block('comments_top', 'articles_full');
					$templating->set('subscribe_link', $subscribe_link);
					$templating->set('close_comments', $close_comments_link);
					$templating->set('pagination_head', $pagination_head);
					$templating->set('pagination', $pagination);

					$profile_fields = include 'includes/profile_fields.php';

					$db_grab_fields = '';
					foreach ($profile_fields as $field)
					{
						$db_grab_fields .= "u.`{$field['db_field']}`,";
					}

					$per_page = 15;
					if (isset($_SESSION['per-page']) && is_numeric($_SESSION['per-page']) && $_SESSION['per-page'] > 0)
					{
						$per_page = $_SESSION['per-page'];
					}

					$db->sqlquery("SELECT a.author_id, a.guest_username, a.comment_text, a.comment_id, u.pc_info_public, u.distro, a.time_posted, a.last_edited, a.last_edited_time, a.`edit_counter`, u.username, u.user_group, u.secondary_user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, $db_grab_fields u.`avatar_uploaded`, u.`avatar_gallery`, u.pc_info_filled, u.game_developer, u.register_date, ul.username as username_edited FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` ul ON ul.user_id = a.last_edited WHERE a.`article_id` = ? ORDER BY a.`comment_id` ASC LIMIT ?, ?", array((int) $_GET['aid'], $core->start, $per_page));
					$comments_get = $db->fetch_all_rows();
					
					// make an array of all comment ids to search for likes (instead of one query per comment for likes)
					$like_array = [];
					$sql_replacers = [];
					foreach ($comments_get as $id_loop)
					{
						$like_array[] = (int) $id_loop['comment_id'];
						$sql_replacers[] = '?';
					}
					
					if (!empty($sql_replacers))
					{
						$to_replace = implode(',', $sql_replacers);
						
						// Total number of likes for the comments
						$grab_total_likes = $db->sqlquery("SELECT data_id, COUNT(*) FROM `likes` WHERE `data_id` IN ( $to_replace ) AND `type` = 'comment' GROUP BY data_id", $like_array);
						$get_likes = $db->fetch_all_rows(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
						
						// this users likes
						if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
						{
							$replace = [$_SESSION['user_id']];
							foreach ($like_array as $comment_id)
							{
								$replace[] = $comment_id;
							}

							$grab_user_likes = $db->sqlquery("SELECT `data_id` FROM `likes` WHERE `user_id` = ? AND `data_id` IN ( $to_replace ) AND `type` = 'comment'", $replace);
							$get_user_likes = $db->fetch_all_rows(PDO::FETCH_COLUMN);
						}
					}

					foreach ($comments_get as $comments)
					{
						$comment_date = $core->format_date($comments['time_posted']);

						if ($comments['author_id'] == 0 || empty($comments['username']))
						{
							if (empty($comments['username']))
							{
								$username = 'Guest';
							}
							if (!empty($comments['guest_username']))
							{
								if ($user->check_group([1,2]) == true)
								{
									$username = "<a href=\"/admin.php?module=articles&view=comments&ip_id={$comments['comment_id']}\">{$comments['guest_username']}</a>";
								}
								else
								{
									$username = $comments['guest_username'];
								}
							}
							$quote_username = $comments['guest_username'];
						}
						else
						{
							$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
							$quote_username = $comments['username'];
						}

						// sort out the avatar
						$comment_avatar = user::sort_avatar($comments);
						
						$into_username = '';
						$into_username = plugins::do_hooks('into_post_username', $comments);
						
						$user_info_extra = '';
						$user_info_extra = plugins::do_hooks('into_post_user_info', $comments);

						$templating->block('article_comments', 'articles_full');
						$permalink = article_class::get_link($article['article_id'], $article['slug'], 'comment_id=' . $comments['comment_id']);
						$templating->set('comment_permalink', $permalink);
						$templating->set('user_id', $comments['author_id']);
						$templating->set('username', $into_username . $username);
						$templating->set('comment_avatar', $comment_avatar);
						$templating->set('date', $comment_date);
						$templating->set('tzdate', date('c',$comments['time_posted']) );
						$templating->set('user_info_extra', $user_info_extra);

						$cake_bit = '';
						if ($username != 'Guest')
						{
							$cake_bit = $user->cake_day($comments['register_date'], $comments['username']);
						}
						$templating->set('cake_icon', $cake_bit);

						$last_edited = '';
						$edit_counter = '';
						if ($comments['last_edited'] != 0)
						{
							if ($comments['edit_counter'] > 1)
							{
								$edit_counter = '. Edited ' . $comments['edit_counter'] . ' times.';
							}
							
							$last_edited = "\r\n\r\n\r\n[i]Last edited by " . $comments['username_edited'] . ' at ' . $core->format_date($comments['last_edited_time']) . $edit_counter . '[/i]';
						}

						$templating->set('article_id', $_GET['aid']);
						$templating->set('comment_id', $comments['comment_id']);
 						
 						$total_likes = 0;
 						if (isset($get_likes[$comments['comment_id']][0]))
 						{
							$total_likes = $get_likes[$comments['comment_id']][0];
 						}

						$templating->set('total_likes', $total_likes);

						$who_likes_link = '';
						if ($total_likes > 0)
						{
							$who_likes_link = ', <a class="who_likes fancybox.ajax" data-fancybox-type="ajax" href="/includes/ajax/who_likes.php?comment_id='.$comments['comment_id'].'">Who?</a>';
						}
						$templating->set('who_likes_link', $who_likes_link);

						$logged_in_options = '';
						$bookmark_comment = '';
						if ($_SESSION['user_group'] != 4)
						{
							// sort bookmark icon out
							if (in_array($comments['comment_id'], $bookmarks_array))
							{
								$bookmark_comment = '<li><a href="#" class="bookmark-content tooltip-top bookmark-saved" data-page="normal" data-type="comment" data-id="'.$comments['comment_id'].'" data-parent-id="'.$article['article_id'].'" data-method="remove" title="Remove Bookmark"><span class="icon bookmark"></span></a></li>';
							}
							else
							{
								$bookmark_comment = '<li><a href="#" class="bookmark-content tooltip-top" data-page="normal" data-type="comment" data-id="'.$comments['comment_id'].'" data-parent-id="'.$article['article_id'].'" data-method="add" title="Bookmark"><span class="icon bookmark"></span></a></li>';
							}

							$like_text = "Like";
							$like_class = "like";
							if ($_SESSION['user_id'] != 0)
							{								
								if (in_array($comments['comment_id'], $get_user_likes))
								{
									$like_text = "Unlike";
									$like_class = "unlike";									
								}
								else
								{
									$like_text = "Like";
									$like_class = "like";
								}
							}
							$logged_in_options = $templating->block_store('logged_in_options', 'articles_full');

							// don't let them like their own post
							if ($comments['author_id'] == $_SESSION['user_id'])
							{
								$like_button = '';
							}
							else
							{
								$like_button = '<li class="like-button" style="display:none !important"><a class="likebutton tooltip-top" data-type="comment" data-id="'.$comments['comment_id'].'" data-article-id="'.$article['article_id'].'" data-author-id="'.$comments['author_id'].'" title="Like"><span class="icon '.$like_class.'">'.$like_text.'</span></a></li>';
							}

							$logged_in_options = $templating->store_replace($logged_in_options, array('plain_username'=> $quote_username,'text_plain'=>htmlspecialchars($comments['comment_text'], ENT_QUOTES), 'like_button'=>$like_button));
						}
						$templating->set('logged_in_options', $logged_in_options);
						$templating->set('bookmark', $bookmark_comment);

						$badges = user::user_badges($comments, 1);
						$templating->set('badges', implode(' ', $badges));
						
						$profile_fields_output = user::user_profile_icons($profile_fields, $comments);

						$templating->set('profile_fields', $profile_fields_output);

						$comment_edit_link = '';
						if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $comments['author_id'] || $user->check_group([1,2]) == true && $_SESSION['user_id'] != 0)
						{
							$comment_edit_link = "<li><a class=\"tooltip-top\" title=\"Edit\" href=\"" . core::config('website_url') . "index.php?module=articles_full&amp;view=Edit&amp;comment_id={$comments['comment_id']}&page=$page\"><span class=\"icon edit\">Edit</span></a></li>";
						}
						$templating->set('edit', $comment_edit_link);

						$comment_delete_link = '';
						if ($user->check_group([1,2]) == true)
						{
							$comment_delete_link = "<li><a class=\"tooltip-top\" title=\"Delete\" href=\"" . core::config('website_url') . "index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\"><span class=\"icon delete\"></span></a></li>";
						}
						$templating->set('delete', $comment_delete_link);

						$report_link = '';
						if ($_SESSION['user_id'] != 0)
						{
							$report_link = "<li><a class=\"tooltip-top\" href=\"" . core::config('website_url') . "index.php?module=articles_full&amp;go=report_comment&amp;article_id={$_GET['aid']}&amp;comment_id={$comments['comment_id']}\" title=\"Report\"><span class=\"icon flag\">Flag</span></a></li>";
						}
						$templating->set('report_link', $report_link);

						// do this last, to help stop templating tags getting parsed in user text
						$templating->set('text', bbcode($comments['comment_text'] . $last_edited, 0));
					}

					$templating->block('bottom', 'articles_full');
					$templating->set('pagination', $pagination);

					// only show comments box if the comments are turned on for this article
					if (core::config('comments_open') == 1)
					{
						if (($article['comments_open'] == 1) || ($article['comments_open'] == 0 && $user->check_group([1,2]) == true))
						{
							if ($_SESSION['user_group'] == 4)
							{
								$templating->merge('login');
								$templating->block('small');
								$templating->set('url', core::config('website_url'));
								
								$twitter_button = '';
								if (core::config('twitter_login') == 1)
								{
									$twitter_button = '<button type="submit" name="action" value="twitter" class="fright"><svg xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="18" style="margin-bottom: -3px;"><image xlink:href="'.core::config('website_url'). 'templates/' . core::config('template') .'/images/social/twitter.svg" src="'.core::config('website_url'). 'templates/' . core::config('template') .'/images/social/twitter.png" width="18" height="18" /></svg> Twitter</button>';
								}
								$templating->set('twitter_button', $twitter_button);
			
								$steam_button = '';
								if (core::config('steam_login') == 1)
								{
									$steam_button = '<button type="submit" name="action" value="steam" class="fleft"><svg xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="18" style="margin-bottom: -4px;"><image xlink:href="'.core::config('website_url'). 'templates/' . core::config('template') .'/images/social/steam.svg" src="'.core::config('website_url'). 'templates/' . core::config('template') .'/images/social/steam.png" width="18" height="18" /></svg> Steam</button>';
								}
								$templating->set('steam_button', $steam_button);
							}

							else
							{
								if (!isset($_SESSION['activated']))
								{
									$db->sqlquery("SELECT `activated` FROM `users` WHERE `user_id` = ?", array((int) $_SESSION['user_id']));
									$get_active = $db->fetch();
									$_SESSION['activated'] = $get_active['activated'];
								}

								if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
								{
									$subscribe_check = $user->check_subscription($_GET['aid'], 'article');

									$comment = '';
									if (isset($_SESSION['acomment']))
									{
										$comment = $_SESSION['acomment'];
									}
									$templating->block('comments_box_top', 'articles_full');
									$templating->set('url', core::config('website_url'));
									$templating->set('article_id', $_GET['aid']);

									$core->editor(['name' => 'text', 'content' => $comment, 'editor_id' => 'comment_text']);

									$templating->block('comment_buttons', 'articles_full');
									$templating->set('url', core::config('website_url'));
									$templating->set('subscribe_check', $subscribe_check['auto_subscribe']);
									$templating->set('subscribe_email_check', $subscribe_check['emails']);
									$templating->set('aid', $_GET['aid']);

									$templating->block('preview', 'articles_full');
								}

								else
								{
									$core->message('To comment you need to activate your account! You were sent an email with instructions on how to activate. <a href="/index.php?module=activate_user&redo=1">Click here to re-send a new activation key</a>');
								}
							}
						}
					}
					else if (core::config('comments_open') == 0)
					{
						$core->message('Commenting is currently down for maintenance.');
					}
				}
			}
		}
	}

	else if (isset($_GET['view']) && $_GET['view'] == 'Edit')
	{
		$templating->set_previous('meta_data', '', 1);

		$db->sqlquery("SELECT c.`author_id`, c.comment_id, c.`comment_text`, c.time_posted, a.`title`, a.article_id FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_GET['comment_id']));
		$comment = $db->fetch();

		$nice_title = core::nice_title($comment['title']);

		// check if author
		if ($_SESSION['user_id'] != $comment['author_id'] && $user->check_group([1,2]) == false || $_SESSION['user_id'] == 0)
		{
			header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
			die();
		}

		$templating->set_previous('meta_description', 'Editing a comment on GamingOnLinux', 1);
		$templating->set_previous('title', 'Editing a comment', 1);

		$comment_text = $comment['comment_text'];

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

		$core->editor(['name' => 'text', 'content' => $comment_text, 'editor_id' => 'comment_text']);

		$templating->block('edit_comment_buttons', 'articles_full');
		$templating->set('comment_id', $comment['comment_id']);
		$templating->set('url', core::config('website_url'));
		$templating->set('page', $page);
		
		$cancel_action = article_class::get_link($comment['article_id'], $nice_title);

		$templating->set('cancel_action', $cancel_action);
		$templating->block('preview', 'articles_full');
	}
}

else if (isset($_GET['go']))
{
	if ($_GET['go'] == 'correction')
	{
		// make sure news id is a number
		if (!isset($_POST['article_id']) || !is_numeric($_POST['article_id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'Article ID';
			header("Location: " . core::config('website_url'));

			die();
		}

		else if (!isset($_SESSION['user_id']) || ( isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 ) )
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
			$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
			if ($db->num_rows() >= 1)
			{
				header("Location: /home/banned");
				die();
			}

			// check empty
			$correction = trim($_POST['correction']);

			$correction = core::make_safe($correction, ENT_QUOTES);

			// get article name for the email and redirect
			$db->sqlquery("SELECT `title`, `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_POST['article_id']));
			$title = $db->fetch();
			
			$article_link = article_class::get_link($_POST['article_id'], $title['slug']);

			if (empty($correction))
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'correction text';

				header("Location: " . $article_link);

				die();
			}

			$db->sqlquery("INSERT INTO `article_corrections` SET `article_id` = ?, `date` = ?, `user_id` = ?, `correction_comment` = ?", array((int) $_POST['article_id'], core::$date, $_SESSION['user_id'], $correction));

			$correction_id = $db->grab_id();

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `created_date` = ?, `type` = ?, `data` = ?, `completed` = 0", array((int) $_SESSION['user_id'], core::$date, 'article_correction', $correction_id));

			$_SESSION['message'] = 'tip_sent';
			header("Location: " . $article_link);
		}

	}

	if ($_GET['go'] == 'comment')
	{
		// make sure news id is a number
		if (!isset($_POST['aid']) || !is_numeric($_POST['aid']))
		{
			header("Location: " . core::config('website_url'));
			die();
		}

		else if (!isset($_SESSION['user_id']) || ( isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 ) )
		{
			$core->message('You do not have permisions to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else if ($parray['comment_on_articles'] == 0)
		{
			$core->message('You do not have permisions to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else
		{
			if (core::config('comments_open') == 0)
			{
				$core->message('Commenting is currently down for maintenance.');
			}
			else
			{
				// check to make sure their IP isn't banned
				$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
				if ($db->num_rows() >= 1)
				{
					header("Location: /home/banned");
				}

				else
				{
					// get article name for the email and redirect
					$db->sqlquery("SELECT `title`, `comment_count`, `comments_open`, `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_POST['aid']));
					$title = $db->fetch();
					$title_nice = core::nice_title($title['title']);

					if ($title['comments_open'] == 0 && $user->check_group([1,2]) == false)
					{
						$_SESSION['message'] = 'locked';
						$_SESSION['message_extra'] = 'article comments';
						$article_link = article_class::get_link($_POST['aid'], $title['slug']);

						header("Location: " . $article_link);

						die();
					}
					else
					{
						// sort out what page the new comment is on, if current is 9, the next comment is on page 2, otherwise round up for the correct page
						$comment_page = 1;
						if ($title['comment_count'] >= $_SESSION['per-page'])
						{
							$new_total = $title['comment_count']+1;

							$comment_page = ceil($new_total/$_SESSION['per-page']);
						}

						// remove extra pointless whitespace
						$comment = trim($_POST['text']);

						// check for double comment
						$db->sqlquery("SELECT `comment_text` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `comment_id` DESC LIMIT 1", array((int) $_POST['aid']));
						$check_comment = $db->fetch();

						if ($check_comment['comment_text'] == $comment)
						{
							$_SESSION['message'] = 'double_comment';
							$article_link = article_class::get_link($_POST['aid'], $title['slug']);
							
							header("Location: " . $article_link);

							die();
						}

						// check if it's an empty comment
						if (empty($comment))
						{
							$_SESSION['message'] = 'empty';
							$_SESSION['message_extra'] = 'text';
							$article_link = article_class::get_link($_POST['aid'], $title['slug']);

							header("Location: " . $article_link);

							die();
						}

						else
						{
							$comment = core::make_safe($comment);

							$article_id = (int) $_POST['aid'];

							// add the comment
							$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($article_id, (int) $_SESSION['user_id'], core::$date, $comment));

							$new_comment_id = $db->grab_id();

							// update the news items comment count
							$db->sqlquery("UPDATE `articles` SET `comment_count` = (comment_count + 1) WHERE `article_id` = ?", array($article_id));

							// update the posting users comment count
							$db->sqlquery("UPDATE `users` SET `comment_count` = (comment_count + 1) WHERE `user_id` = ?", array((int) $_SESSION['user_id']));

							// see if they are subscribed right now, if they are and they untick the subscribe box, remove their subscription as they are unsubscribing
							$db->sqlquery("SELECT `article_id`, `emails`, `send_email` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], $article_id));
							if ($db->num_rows() == 1)
							{
								if (!isset($_POST['subscribe']))
								{
									$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], $article_id));
								}
							}

							// check if they are subscribing
							if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
							{
								$emails = 0;
								if ($_POST['subscribe-type'] == 'sub-emails')
								{
									$emails = 1;
								}
								$article_class->subscribe($article_id, $emails);
							}

							/* gather a list of people quoted and let them know
							 do this first, so we can check if they have been notified already and not send another */
							$pattern = '/\[quote\=(.+?)\](.+?)\[\/quote\]/is';
							preg_match_all($pattern, $comment, $matches);

							// we only want to notify them once on being quoted, so make sure each quote has a unique name
							$quoted_usernames = array_values(array_unique($matches[1]));

							if (!empty($quoted_usernames))
							{
								foreach($quoted_usernames as $match)
								{
									if ($match != $_SESSION['username'])
									{
										$db->sqlquery("SELECT `user_id` FROM `users` WHERE `username` = ?", array($match));
										if ($db->num_rows() == 1)
										{
											$quoted_user = $db->fetch();
											$db->sqlquery("INSERT INTO `user_notifications` SET `date` = ?, `seen` = 0, `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `is_quote` = 1", array(core::$date, $quoted_user['user_id'], (int) $_SESSION['user_id'], $article_id, $new_comment_id));
											$new_notification_id = $db->grab_id();
										}
									}
								}
							}

							/* gather a list of subscriptions for this article (not including yourself!)
							- Make an array of anyone who needs an email now
							- Additionally, send a notification to anyone subscribed
							*/
							$db->sqlquery("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`email_options` FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.user_id != ?", array($article_id, (int) $_SESSION['user_id']));
							$users_array = array();
							$users_to_email = $db->fetch_all_rows();
							foreach ($users_to_email as $email_user)
							{
								// gather list
								if ($email_user['emails'] == 1 && $email_user['send_email'] == 1)
								{
									// use existing key, or generate any missing keys
									if (empty($email_user['secret_key']))
									{
										$secret_key = core::random_id(15);
										$db->sqlquery("UPDATE `articles_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $email_user['user_id'], $article_id));

									}
									else
									{
										$secret_key = $email_user['secret_key'];
									}
									
									$users_array[$email_user['user_id']]['user_id'] = $email_user['user_id'];
									$users_array[$email_user['user_id']]['email'] = $email_user['email'];
									$users_array[$email_user['user_id']]['username'] = $email_user['username'];
									$users_array[$email_user['user_id']]['email_options'] = $email_user['email_options'];
									$users_array[$email_user['user_id']]['secret_key'] = $secret_key;
								}

								// notify them, if they haven't been quoted and already given one
								if (!in_array($email_user['username'], $quoted_usernames))
								{
									$db->sqlquery("SELECT `id`, `article_id`, `seen` FROM `user_notifications` WHERE `article_id` = ? AND `owner_id` = ? AND `is_like` = 0 AND `is_quote` = 0", array($article_id, $email_user['user_id']));
									$check_exists = $db->num_rows();
									$get_note_info = $db->fetch();
									if ($check_exists == 0)
									{
										$db->sqlquery("INSERT INTO `user_notifications` SET `date` = ?, `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `total` = 1", array(core::$date, $email_user['user_id'], (int) $_SESSION['user_id'], $article_id, $new_comment_id));
										$new_notification_id = $db->grab_id();
									}
									else if ($check_exists == 1)
									{
										// they have seen this one before, but kept it, so refresh it as if it's literally brand new (don't waste the row id)
										if ($get_note_info['seen'] == 1)
										{
											$db->sqlquery("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `date` = ?, `total` = 1, `seen_date` = NULL, `comment_id` = ? WHERE `id` = ?", array($_SESSION['user_id'], core::$date, $new_comment_id, $get_note_info['id']));
										}
										// they haven't seen this note before, so add one to the counter and update the date
										else if ($get_note_info['seen'] == 0)
										{
											$db->sqlquery("UPDATE `user_notifications` SET `date` = ?, `total` = (total + 1) WHERE `id` = ?", array(core::$date, $get_note_info['id']));
										}
										$new_notification_id = $get_note_info['id'];
									}
								}
							}

							// send the emails
							foreach ($users_array as $email_user)
							{
								// subject
								$subject = "New reply to article {$title['title']} on GamingOnLinux.com";

								$comment_email = email_bbcode($comment);

								// message
								$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
								<p><strong>{$_SESSION['username']}</strong> has replied to an article you follow on titled \"<strong><a href=\"" . core::config('website_url') . "index.php?module=articles_full&aid=$article_id&comment_id={$new_comment_id}&clear_note=$new_notification_id\">{$title['title']}</a></strong>\". There may be more comments after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
								<div>
							 	<hr>
							 	{$comment_email}
							 	<hr>
							 	<p>You can unsubscribe from this article by <a href=\"" . core::config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . core::config('website_url') . "usercp.php\">User Control Panel</a>.</p>";

								$plain_message = PHP_EOL."Hello {$email_user['username']}, {$_SESSION['username']} replied to an article on " . core::config('website_url') . "index.php?module=articles_full&aid=$article_id&comment_id={$new_comment_id}&clear_note=$new_notification_id\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: " . core::config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}";

								// Mail it
								if (core::config('send_emails') == 1)
								{
									$mail = new mail($email_user['email'], $subject, $html_message, $plain_message);
									$mail->send();
								}

								// remove anyones send_emails subscription setting if they have it set to email once
								if ($email_user['email_options'] == 2)
								{
									$db->sqlquery("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($article_id, $email_user['user_id']));
								}
							}

							// try to stop double postings, clear text
							unset($_POST['text']);

							// clear any comment or name left from errors
							unset($_SESSION['acomment']);
							
							$article_link = article_class::get_link($_POST['aid'], $title['slug'], 'page=' . $comment_page . '#r' . $new_comment_id);

							header("Location: " . $article_link);
						}
					}
				}
			}
		}
	}

	if ($_GET['go'] == 'editcomment')
	{
		$db->sqlquery("SELECT c.`author_id`, c.`comment_text`, a.`title`, a.`article_id`, a.`slug` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_POST['comment_id']));
		$comment = $db->fetch();

		// check if author or editor/admin
		if ($_SESSION['user_id'] != $comment['author_id'] && $user->check_group([1,2]) == false || $_SESSION['user_id'] == 0)
		{
			$nice_title = core::nice_title($comment['title']);
			header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		}

		// do the edit since we are allowed
		else
		{
			$comment_text = trim($_POST['text']);
			// check empty
			if (empty($comment_text))
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'text';
				$article_link = article_class::get_link($comment['article_id'], $comment['slug']);

				header("Location: " . $article_link);

				die();
			}

			// update comment
			else
			{
				$comment_text = core::make_safe($comment_text);

				$db->sqlquery("UPDATE `articles_comments` SET `comment_text` = ?, `last_edited` = ?, `last_edited_time` = ?, `edit_counter` = (edit_counter + 1) WHERE `comment_id` = ?", array($comment_text, (int) $_SESSION['user_id'], core::$date, (int) $_POST['comment_id']));
				
				$edit_redirect = article_class::get_link($comment['article_id'], $comment['slug'], 'comment_id=' . $_POST['comment_id']);

				header("Location: ".$edit_redirect);
			}
		}
	}

	if ($_GET['go'] == 'deletecomment')
	{
		if (!isset($_GET['comment_id']) || !core::is_number($_GET['comment_id']))
		{
			$core->message('Looks like you took a wrong turn!');
			include('includes/footer.php');
			die();
		}
		
		$db->sqlquery("SELECT c.`author_id`, c.`comment_text`, c.`spam`, a.`title`, a.`article_id`, a.`slug` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_GET['comment_id']));
		$comment = $db->fetch();

		$article_link = article_class::get_link($comment['article_id'], $comment['slug'], '#comments');

		if ($user->check_group([1,2]) == false)
		{
			header("Location: ".$article_link);
		}

		else
		{
			if ($comment['author_id'] == 1 && $_SESSION['user_id'] != 1)
			{
				header("Location: ".$article_link);
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
					header("Location: ".$article_link);
				}

				else if (isset($_POST['yes']))
				{
					// this comment was reported as spam but as its now deleted remove the notification
					if ($comment['spam'] == 1)
					{
						$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'reported_comment'", array(core::$date, (int) $_GET['comment_id']));
					}

					$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `type` = ?, `completed_date` = ?, `data` = ?, `content` = ?", array($_SESSION['user_id'], core::$date, 'comment_deleted', core::$date, (int) $_GET['comment_id'], $comment['comment_text']));

					$db->sqlquery("UPDATE `articles` SET `comment_count` = (comment_count - 1) WHERE `article_id` = ?", array($comment['article_id']));
					$db->sqlquery("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array((int) $_GET['comment_id']));
					$db->sqlquery("DELETE FROM `likes` WHERE `data_id` = ?", array((int) $_GET['comment_id']));

					// update notifications

					// find any notifications caused by the deleted comment
					$db->sqlquery("SELECT `owner_id`, `id`, `total`, `seen`, `seen_date`, `article_id`, `comment_id` FROM `user_notifications` WHERE `is_like` = 0 AND `article_id` = ?", array($comment['article_id']));
					$current_notes = $db->fetch_all_rows();
					foreach ($current_notes as $this_note)
					{
						// if this wasn't the only comment made for that notification
						if ($this_note['total'] >= 2)
						{
							// if the one deleted is the original comment we were notified about
							if ($this_note['comment_id'] == $_GET['comment_id'])
							{
								// find the last available comment
								$db->sqlquery("SELECT `author_id`, `comment_id`, `time_posted` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `time_posted` DESC LIMIT 1", array($this_note['article_id']));
								$last_comment = $db->fetch();

								$seen = '';

								// if the last time they saw this notification was before the date of the new last like, they haven't seen it
								if ($last_comment['time_posted'] > $this_note['seen_date'])
								{
									$seen = 0;
								}
								else
								{
									$seen = 1;
								}

								$db->sqlquery("UPDATE `user_notifications` SET `date` = ?, `notifier_id` = ?, `seen` = ?, `comment_id` = ? WHERE `id` = ?", array($last_comment['time_posted'], $last_comment['author_id'], $seen, $last_comment['comment_id'], $this_note['id']));
							}
							// no matter what we need to adjust the counter
							$db->sqlquery("UPDATE `user_notifications` SET `total` = (total - 1) WHERE `id` = ?", array($this_note['id']));
						}
						// it's the only comment they were notified about, so just delete the notification to completely remove it
						else if ($this_note['total'] == 1)
						{
							$db->sqlquery("DELETE FROM `user_notifications` WHERE `id` = ?", array($this_note['id']));
						}
					}

					header("Location: ".$article_link);
				}
			}
		}
	}

	if ($_GET['go'] == 'subscribe')
	{
		$article_class->subscribe($_GET['article_id']);

		// get info for title
		$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']));
		$title = $db->fetch();
		$title = core::nice_title($title['title']);

		header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
	}

	if ($_GET['go'] == 'unsubscribe')
	{
		$article_class->unsubscribe($_GET['article_id']);

		// get info for title
		$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']));
		$title = $db->fetch();
		$title = core::nice_title($title['title']);

		header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
	}

	if ($_GET['go'] == 'report_comment')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Reporting a comment', 1);

			// show the comment they are reporting
			$db->sqlquery("SELECT c.`comment_text`, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u.`avatar_gallery` FROM `articles_comments` c LEFT JOIN users u ON u.user_id = c.author_id WHERE c.`comment_id` = ?", array((int) $_GET['comment_id']));
			$comment = $db->fetch();
			$templating->block('report', 'articles_full');
			$templating->set('text', bbcode($comment['comment_text']));

			// sort out the avatar
			$comment_avatar = $user->sort_avatar($comment);

			$templating->set('comment_avatar', $comment_avatar);

			$core->yes_no('Are you sure you wish to report that comment?', url."index.php?module=articles_full&go=report_comment&article_id={$_GET['article_id']}&comment_id={$_GET['comment_id']}", "");
		}
		else if (isset($_POST['no']))
		{
			// get info for title
			$db->sqlquery("SELECT `title`, `slug` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
			$title = $db->fetch();
			
			$article_link = article_class::get_link($_GET['article_id'], $title['slug'], '#comments');

			header("Location: ".$article_link);
		}

		else
		{
			if ($_SESSION['user_group'] != 4)
			{
				// update admin notifications
				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array((int) $_SESSION['user_id'], 'reported_comment', core::$date, (int) $_GET['comment_id']));

				$db->sqlquery("UPDATE `articles_comments` SET `spam` = 1, `spam_report_by` = ? WHERE `comment_id` = ?", array((int) $_SESSION['user_id'], (int) $_GET['comment_id']));
			}

			// get info for title
			$db->sqlquery("SELECT `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']));
			$title = $db->fetch();
			
			$article_link = article_class::get_link($_GET['article_id'], $title['slug']);

			$_SESSION['message'] = 'reported';
			$_SESSION['message_extra'] = 'comment';

			header("Location: ".$article_link);
		}
	}

	if ($_GET['go'] == 'open_comments')
	{
		if ($user->check_group([1,2]) == true)
		{
			// get info for title
			$db->sqlquery("SELECT `title`,`slug` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']));
			$title = $db->fetch();
			
			$article_link = article_class::get_link($_GET['article_id'], $title['slug']);

			header("Location: ".$article_link);

			if ($user->check_group([1,2]) == false)
			{
				header("Location: ".$article_link);
			}

			else
			{
				$db->sqlquery("UPDATE `articles` SET `comments_open` = 1 WHERE `article_id` = ?", array((int) $_GET['article_id']));
			}

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `created_date` = ?, `completed` = 1, `type` = ?, `completed_date` = ?, `data` = ?", array((int) $_SESSION['user_id'], core::$date, 'opened_comments', core::$date, (int) $_GET['article_id']));

			$_SESSION['message'] = 'comments_opened';
			header("Location: ".$article_link);
		}

		else
		{
			$_SESSION['message'] = 'no_permission';
			header("Location: ".$article_link);
		}
	}

	if ($_GET['go'] == 'close_comments')
	{
		if ($user->check_group([1,2]) == true)
		{
			// get info for title
			$db->sqlquery("SELECT `title`, `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']));
			$title = $db->fetch();
			
			$article_link = article_class::get_link($_GET['article_id'], $title['slug']);

			header("Location: ".$article_link);

			if ($user->check_group([1,2]) == false)
			{
				header("Location: ".$article_link);
			}

			else
			{
				$db->sqlquery("UPDATE `articles` SET `comments_open` = 0 WHERE `article_id` = ?", array((int) $_GET['article_id']));
			}

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `created_date` = ?, `completed` = 1, `type` = ?, `completed_date` = ?, `data` = ?", array((int) $_SESSION['user_id'], core::$date, 'closed_comments', core::$date, (int) $_GET['article_id']));

			$_SESSION['message'] = 'comments_closed';
			header("Location: ".$article_link);
		}

		else
		{
			$_SESSION['message'] = 'no_permission';
			header("Location: ".$article_link);
		}
	}
}
