<?php
$templating->load('articles_full');

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
			$article = $dbl->run("SELECT
				a.`article_id`,
				a.`slug`,
				a.`preview_code`,
				a.`title`,
				a.`text`,
				a.`tagline`,
				a.`date`,
				a.`views`,
				a.`date_submitted`,
				a.`author_id`,
				a.`active`,
				a.`guest_username`,
				a.`tagline_image`,
				a.`gallery_tagline`,
				a.`comment_count`,
				a.`total_likes`,
				t.`filename` as `gallery_tagline_filename`,
				a.`comments_open`,
				u.`username`,
				u.`twitter_on_profile`
				FROM `articles` a
				LEFT JOIN
				`users` u on a.`author_id` = u.`user_id`
				LEFT JOIN
				`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
				WHERE
				a.`article_id` = ?", array((int) $_GET['aid']))->fetch();

			// FIND THE CORRECT PAGE IF THEY HAVE A LINKED COMMENT
			if (isset($_GET['comment_id']) && core::is_number($_GET['comment_id']))
			{
				// check comment still exists
				$check = $dbl->run("SELECT `comment_id` FROM `articles_comments` WHERE `comment_id` = ? AND `article_id` = ?", array((int) $_GET['comment_id'], (int) $_GET['aid']))->fetchOne();
				if ($check)
				{
					// get blocked id's
					$blocked_sql = '';
					$blocked_ids = [];
					if (count($user->blocked_users) > 0)
					{
						foreach ($user->blocked_users as $username => $blocked_id)
						{
							$blocked_ids[] = $blocked_id[0];
						}

						$in  = str_repeat('?,', count($blocked_ids) - 1) . '?';
						$blocked_sql = "AND c.`author_id` NOT IN ($in)";
					}

					// calculate the page this comment is on
					$prev_comments = $dbl->run("SELECT COUNT(comment_id) AS total FROM `articles_comments` WHERE `comment_id` <= ? AND `article_id` = ?", array($_GET['comment_id'], $article['article_id']))->fetchOne();

					$comments_per_page = $core->config('default-comments-per-page');
					if (isset($_SESSION['per-page']))
					{
						$comments_per_page = $_SESSION['per-page'];
					}

					$comment_page = 1;
					if ($article['comment_count'] > $comments_per_page)
					{
						$comment_page = ceil($prev_comments/$_SESSION['per-page']);
					}

					$article_link = $article_class->get_link($article['article_id'], $article['slug'], 'page=' . $comment_page . '#r' . $_GET['comment_id']);

					header("Location: " . $article_link);
					die();
				}
				else
				{
					$_SESSION['message'] = 'nocomment';
					$article_link = $article_class->get_link($article['article_id'], $article['slug']);

					header("Location: " . $article_link);
					die();
				}
			}

			if (!$article)
			{
				http_response_code(404);
				$templating->set_previous('meta_data', '', 1);
				$templating->set_previous('title', 'Couldn\'t find article', 1);
				$core->message('That is not a correct article id! We have loaded a search box for you if you\'re lost!');

				$templating->load('search');
				$templating->block('top');
				$templating->set('url', $core->config('website_url'));
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
				
				$html_title = htmlentities($article['title'], ENT_COMPAT);

				if (!isset($_GET['preview_code']))
				{
					$templating->set_previous('title', $html_title, 1);

					// update the view counter if it is not a preview
					$dbl->run("UPDATE `articles` SET `views` = (views + 1) WHERE `article_id` = ?", array($article['article_id']));
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
					$article_meta_image = $core->config('website_url') . "uploads/articles/tagline_images/{$article['tagline_image']}";
				}
				if (!empty($article['gallery_tagline_filename']))
				{
					$article_meta_image = $core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}";
				}

				$nice_title = core::nice_title($article['title']);

				// twitter info card
				$twitter_card = "<!-- twitter card -->\n";
				$twitter_card .= '<meta name="twitter:card" content="summary_large_image">';
				$twitter_card .= '<meta name="twitter:site" content="@'.$core->config('twitter_username').'">';
				if (!empty($article['twitter_on_profile']))
				{
					$twitter_card .= '<meta name="twitter:creator" content="@'.$article['twitter_on_profile'].'">';
				}

				$twitter_card .= '<meta name="twitter:title" content="'.$html_title.'">';
				$twitter_card .= '<meta name="twitter:description" content="'.$article['tagline'].'">';
				$twitter_card .= '<meta name="twitter:image" content="'.$article_meta_image.'">';
				$twitter_card .= '<meta name="twitter:image:src" content="'.$article_meta_image.'">';
				
				$published_date_meta = date("Y-m-d\TH:i:s", $article['date']) . 'Z';

				// meta tags for g+, facebook and twitter images
				$templating->set_previous('meta_data', "<meta property=\"og:image\" content=\"$article_meta_image\"/>\n<meta property=\"og:image_url\" content=\"$article_meta_image\"/>\n<meta property=\"og:type\" content=\"article\">\n<meta property=\"og:title\" content=\"" . $html_title . "\" />\n<meta property=\"og:description\" content=\"{$article['tagline']}\" />\n<meta property=\"og:url\" content=\"" . $article_class->get_link($article['article_id'], $nice_title) . "\" />\n<meta itemprop=\"image\" content=\"$article_meta_image\" />\n<meta itemprop=\"title\" content=\"" . $html_title . "\" />\n<meta itemprop=\"description\" content=\"{$article['tagline']}\" />\n<meta property=\"datePublished\" content=\"{$published_date_meta}\">\n$twitter_card", 1);

				// make date human readable
				$date = $core->human_date($article['date']);

				$templating->block('article', 'articles_full');
				$templating->set('url', $core->config('website_url'));
				
				$share_url = $article_class->get_link($_GET['aid'], $nice_title);
				
				$twitter_share = '<a class="button small fnone" href="https://twitter.com/intent/tweet?text='.urlencode($article['title']).'%20%23Linux&amp;url='.$share_url.'&amp;via=gamingonlinux" target="_blank"><img src="'.$core->config('website_url') . 'templates/' . $core->config('template') .'/images/network-icons/twitter.svg" alt="" /></a>';
				$templating->set('twitter_share', $twitter_share);

				$fb_onclick = "window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent('$share_url'), 'height=279, width=575'); return false;";
				
				$facebook_share = '<a class="button small fnone" href="#" onclick="'.$fb_onclick.'" target="_blank"><img src="'.$core->config('website_url') . 'templates/' . $core->config('template') .'/images/network-icons/facebook.svg" alt="" /></a>';
				$templating->set('facebook_share', $facebook_share);
				
				$gplus_share = '<a class="button small fnone" href="https://plusone.google.com/_/+1/confirm?hl=en&url='.$share_url.'" target="_blank"><img src="'.$core->config('website_url') . 'templates/' . $core->config('template') .'/images/network-icons/google-plus.svg" alt="" /></a>';
				$templating->set('gplus_share', $gplus_share);

				$article_link = $article_class->get_link($_GET['aid'], $nice_title);
				
				$templating->set('article_link', $article_link);

				$templating->set('rules', $core->config('rules'));

				if (($user->check_group([1,2,5]) == true) && !isset($_GET['preview']))
				{
					$templating->set('edit_link', " <a href=\"" . $core->config('website_url') . "admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\">Edit</a>");
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
					$templating->set('admin_button', "<form method=\"post\"><button type=\"submit\" class=\"btn btn-info\" formaction=\"" . $core->config('website_url') . "{$page_action}\">Back</button> <button type=\"submit\" formaction=\"" . $core->config('url') . "{$page_action}&aid={$_GET['aid']}\" class=\"btn btn-info\">Edit</button></form>");
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
				$templating->set('machine_time', $published_date_meta);
				$templating->set('article_views', number_format($article['views']));
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
				if ($user->user_details['single_article_page'] == 0)
				{
					$pages_array = explode('<*PAGE*>', $article['text']);
					$article_page_count = count($pages_array);
					$pages_array = array_combine(range(1, count($pages_array)), $pages_array);
					if ($article_page <= $article_page_count)
					{
						$article_body = $pages_array[$article_page];
					}
					else
					{
						$article_body = $pages_array[1];
						$article_page = 1;
					}
				}
				else
				{
					$article_body = str_replace('<*PAGE*>', '', $article['text']);
					$article_page_count = 1;
				}
				
				$templating->set('this_template', $core->config('website_url') . 'templates/' . $core->config('template'));

				$templating->set('text', $bbcode->article_bbcode($article_body));

				$article_link = "/articles/$nice_title.{$_GET['aid']}/";
				if (isset($_GET['preview']))
				{
					$article_link = "/index.php?module=articles_full&amp;aid={$_GET['aid']}&amp;preview&amp;";
				}

				$article_pagination = $article_class->article_pagination($article_page, $article_page_count, $article_link);

				$templating->set('paging', $article_pagination);

				$categories_list = '';
				// sort out the categories (tags)
				$fetch_cats = $dbl->run("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? ORDER BY r.`category_id` = 60 DESC, r.`category_id` ASC", array($article['article_id']))->fetch_all();
				foreach ($fetch_cats as $get_categories)
				{
					$category_link = $article_class->tag_link($get_categories['category_name']);
					
					$category_name = str_replace(' ', '-', $get_categories['category_name']);
					if ($get_categories['category_id'] == 60)
					{
						$categories_list .= '<li class="ea"><a href="'.$category_link.'">'. $get_categories['category_name'] . '</a></li>';
					}

					else
					{
						$categories_list .= '<li><a href="'.$category_link.'">' . $get_categories['category_name'] . '</a></li>';
					}
				}

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
					$bookmark_check = $dbl->run("SELECT `data_id` FROM `user_bookmarks` WHERE `data_id` = ? AND `user_id` = ? AND `type` = 'article'", array($article['article_id'], (int) $_SESSION['user_id']))->fetchOne();
					if ($bookmark_check)
					{
						$bookmark_link = '<a href="#" class="bookmark-content tooltip-top bookmark-saved" data-page="normal" data-type="article" data-id="'.$article['article_id'].'" data-method="remove" title="Remove Bookmark"><span class="icon bookmark"></span></a>';
					}
					else
					{
						$bookmark_link = '<a href="#" class="bookmark-content tooltip-top" data-page="normal" data-type="article" data-id="'.$article['article_id'].'" data-method="add" title="Bookmark"><span class="icon bookmark"></span></a>';
					}
				}
				$templating->set('bookmark_link', $bookmark_link);



				$templating->set('total_likes', $article['total_likes']);

				$who_likes_alink = '';
				if ($article['total_likes'] > 0)
				{
					$who_likes_alink = ', <a class="who_likes" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?article_id='.$article['article_id'].'">Who?</a>';
				}
				$templating->set('who_likes_alink', $who_likes_alink);

				$like_button = '';
				if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
				{
					$like_text = "Like";
					$like_class = "like";
					$they_liked = 0;
					// Checks current login user liked this status or not
					if ($article['total_likes'] > 0) // no point checking if they've liked it, if there's no likes
					{
						$numlikes = $dbl->run("SELECT 1 FROM `article_likes` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], $article['article_id']))->fetchOne();

						if ($numlikes)
						{
							$they_liked = 1;
						}
					}
					if ($they_liked == 1)
					{
						$like_text = "Unlike";
						$like_class = "unlike";
					}
					else
					{
						$like_text = "Like";
						$like_class = "like";
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
					$article_class->display_comments(['article' => $article, 'pagination_link' => '/articles/' . $nice_title . '.' . $_GET['aid'] . '/', 'type' => 'live_article', 'page' => core::give_page()]);

					// only show comments box if the comments are turned on for this article
					if ($core->config('comments_open') == 1)
					{
						if (($article['comments_open'] == 1) || ($article['comments_open'] == 0 && $user->check_group([1,2]) == true))
						{
							if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 || !isset($_SESSION['user_id']))
							{
								$templating->load('login');
								$templating->block('small');
								$templating->set('url', $core->config('website_url'));
								
								$twitter_button = '';
								if ($core->config('twitter_login') == 1)
								{	
									$twitter_button = '<a href="'.$core->config('website_url').'index.php?module=login&twitter" class="btn-auth btn-twitter"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/twitter.png" /> </span>Sign in with <b>Twitter</b></a>';
								}
								$templating->set('twitter_button', $twitter_button);
								
								$steam_button = '';
								if ($core->config('steam_login') == 1)
								{
									$steam_button = '<a href="'.$core->config('website_url').'index.php?module=login&steam" class="btn-auth btn-steam"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/steam.png" /> </span>Sign in with <b>Steam</b></a>';
								}
								$templating->set('steam_button', $steam_button);
								
								$google_button = '';
								if ($core->config('google_login') == 1)
								{
									$client_id = $core->config('google_login_public'); 
									$client_secret = $core->config('google_login_secret');
									$redirect_uri = $core->config('website_url') . 'includes/google/login.php';
									require_once ($core->config('path') . 'includes/google/libraries/Google/autoload.php');
									$client = new Google_Client();
									$client->setClientId($client_id);
									$client->setClientSecret($client_secret);
									$client->setRedirectUri($redirect_uri);
									$client->addScope("email");
									$client->addScope("profile");
									$service = new Google_Service_Oauth2($client);
									$authUrl = $client->createAuthUrl();
									
									$google_button = '<a href="'.$authUrl.'" class="btn-auth btn-google"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/google-plus.png" /> </span>Sign in with <b>Google</b></a>';
								}
								$templating->set('google_button', $google_button);
							}

							else
							{
								if (!isset($_SESSION['activated']))
								{
									$get_active = $dbl->run("SELECT `activated` FROM `users` WHERE `user_id` = ?", array((int) $_SESSION['user_id']))->fetch();
									$_SESSION['activated'] = $get_active['activated'];
								}

								if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
								{
									// check they don't already have a reply in the mod queue for this forum topic
									$check_queue = $dbl->run("SELECT COUNT(`comment_id`) FROM `articles_comments` WHERE `approved` = 0 AND `author_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['aid']))->fetchOne();
									if ($check_queue == 0)
									{
										$mod_queue = $user->user_details['in_mod_queue'];
										$forced_mod_queue = $user->can('forced_mod_queue');
							
										if ($forced_mod_queue == true || $mod_queue == 1)
										{
											$core->message('Some comments are held for moderation. Your post may not appear right away.', NULL, 2);
										}
										$subscribe_check = $user->check_subscription($_GET['aid'], 'article');

										$comment = '';
										if (isset($_SESSION['acomment']))
										{
											$comment = $_SESSION['acomment'];
										}
										$templating->block('comments_box_top', 'articles_full');
										$templating->set('url', $core->config('website_url'));
										$templating->set('article_id', $_GET['aid']);

										$core->editor(['name' => 'text', 'content' => $comment, 'editor_id' => 'comment']);

										$templating->block('comment_buttons', 'articles_full');
										$templating->set('url', $core->config('website_url'));
										$templating->set('subscribe_check', $subscribe_check['auto_subscribe']);
										$templating->set('subscribe_email_check', $subscribe_check['emails']);
										$templating->set('aid', $_GET['aid']);

										$templating->block('preview', 'articles_full');
									}
									else
									{
										$core->message('You currently have a comment in the moderation queue for this article, you must wait for that to be approved/denied before you can post another reply here.', NULL, 2);
									}
								}

								else
								{
									$core->message('To comment you need to activate your account! You were sent an email with instructions on how to activate. <a href="/index.php?module=activate_user&redo=1">Click here to re-send a new activation key</a>');
								}
							}
						}
					}
					else if ($core->config('comments_open') == 0)
					{
						$core->message('Commenting is currently down for maintenance.');
					}
				}
			}
		}
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
			header("Location: " . $core->config('website_url'));

			die();
		}

		else if (!isset($_SESSION['user_id']) || ( isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 ) )
		{
			$core->message('You do not have permission to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else if (!$user->can('comment_on_articles'))
		{
			$core->message('You do not have permission to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}
		else
		{
			// check empty
			$correction = trim($_POST['correction']);

			$correction = core::make_safe($correction, ENT_QUOTES);

			// get article name for the redirect
			$title = $dbl->run("SELECT `title`, `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_POST['article_id']))->fetch();
			
			$article_link = $article_class->get_link($_POST['article_id'], $title['slug']);

			if (empty($correction))
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'correction text';

				header("Location: " . $article_link);

				die();
			}

			$dbl->run("INSERT INTO `article_corrections` SET `article_id` = ?, `date` = ?, `user_id` = ?, `correction_comment` = ?", array((int) $_POST['article_id'], core::$date, $_SESSION['user_id'], $correction));

			$correction_id = $dbl->new_id();

			// note who did it
			$core->new_admin_note(array('completed' => 0, 'content' => ' sent a new article correction for: <a href="/admin.php?module=corrections">'.$title['title'].'</a>.', 'type' => 'article_correction', 'data' => $correction_id));

			$_SESSION['message'] = 'tip_sent';
			header("Location: " . $article_link);
			die();
		}

	}

	if ($_GET['go'] == 'comment')
	{
		// make sure news id is a number
		if (!isset($_POST['aid']) || !is_numeric($_POST['aid']))
		{
			header("Location: " . $core->config('website_url'));
			die();
		}

		else if (!isset($_SESSION['user_id']) || ( isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 ) )
		{
			$core->message('You do not have permission to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else if (!$user->can('comment_on_articles'))
		{
			$core->message('You do not have permission to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else
		{
			if ($core->config('comments_open') == 0)
			{
				$core->message('Commenting is currently down for maintenance.');
			}
			else
			{
				// get article name for the email and redirect
				$title = $dbl->run("SELECT `title`, `comment_count`, `comments_open`, `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_POST['aid']))->fetch();
				$title_nice = core::nice_title($title['title']);

				if ($title['comments_open'] == 0 && $user->check_group([1,2]) == false)
				{
					$_SESSION['message'] = 'locked';
					$_SESSION['message_extra'] = 'article comments';
					$article_link = $article_class->get_link($_POST['aid'], $title['slug']);

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
					$check_comment = $dbl->run("SELECT `comment_text` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `comment_id` DESC LIMIT 1", array((int) $_POST['aid']))->fetch();

					if ($check_comment['comment_text'] == $comment)
					{
						$_SESSION['message'] = 'double_comment';
						$article_link = $article_class->get_link($_POST['aid'], $title['slug']);
							
						header("Location: " . $article_link);

						die();
					}

					// check if it's an empty comment
					if (empty($comment))
					{
						$_SESSION['message'] = 'empty';
						$_SESSION['message_extra'] = 'text';
						$article_link = $article_class->get_link($_POST['aid'], $title['slug']);

						header("Location: " . $article_link);

						die();
					}

					else
					{
						$mod_queue = $user->user_details['in_mod_queue'];
						$forced_mod_queue = $user->can('forced_mod_queue');
							
						$approved = 1;
						if ($mod_queue == 1 || $forced_mod_queue == true)
						{
							$approved = 0;
						}
				
						$comment = core::make_safe($comment);

						$article_id = (int) $_POST['aid'];

						// add the comment
						$dbl->run("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?, `approved` = ?", array($article_id, (int) $_SESSION['user_id'], core::$date, $comment, $approved));
							
						$new_comment_id = $dbl->new_id();
							
						// if they aren't keeping a subscription
						if (!isset($_POST['subscribe']))
						{
							$dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], $article_id));
						}

						if ($approved == 1)
						{
							// deal with redis comment counter cache
							$article_class->article_comments_counter($article_id, 'add');

							// update the news items comment count
							$dbl->run("UPDATE `articles` SET `comment_count` = (comment_count + 1) WHERE `article_id` = ?", array($article_id));

							// update the posting users comment count
							$dbl->run("UPDATE `users` SET `comment_count` = (comment_count + 1) WHERE `user_id` = ?", array((int) $_SESSION['user_id']));

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
								
							$new_notification_id = $article_class->quote_notification($comment, $_SESSION['username'], $_SESSION['user_id'], $article_id, $new_comment_id);

							/* gather a list of subscriptions for this article (not including yourself!)
							- Make an array of anyone who needs an email now
							- Additionally, send a notification to anyone subscribed
							*/
							$users_to_email = $dbl->run("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`email_options` FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.user_id != ?", array($article_id, (int) $_SESSION['user_id']))->fetch_all();
							$users_array = array();
							foreach ($users_to_email as $email_user)
							{
								// gather list
								if ($email_user['emails'] == 1 && $email_user['send_email'] == 1)
								{
									// use existing key, or generate any missing keys
									if (empty($email_user['secret_key']))
									{
										$secret_key = core::random_id(15);
										$dbl->run("UPDATE `articles_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $email_user['user_id'], $article_id));
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
								if (!in_array($email_user['username'], $new_notification_id['quoted_usernames']))
								{
									$get_note_info = $dbl->run("SELECT `id`, `article_id`, `seen` FROM `user_notifications` WHERE `article_id` = ? AND `owner_id` = ? AND `type` != 'liked' AND `type` != 'quoted'", array($article_id, $email_user['user_id']))->fetch();

									if (!$get_note_info)
									{
										$dbl->run("INSERT INTO `user_notifications` SET `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `total` = 1, `type` = 'article_comment'", array($email_user['user_id'], (int) $_SESSION['user_id'], $article_id, $new_comment_id));
										$new_notification_id[$email_user['user_id']] = $dbl->new_id();
									}
									else if ($get_note_info)
									{
										if ($get_note_info['seen'] == 1)
										{
											// they already have one, refresh it as if it's literally brand new (don't waste the row id)
											$dbl->run("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `last_date` = ?, `total` = 1, `seen_date` = NULL, `comment_id` = ? WHERE `id` = ?", array($_SESSION['user_id'], core::$sql_date_now, $new_comment_id, $get_note_info['id']));
										}
										else if ($get_note_info['seen'] == 0)
										{
											// they haven't seen the last one yet, so only update the time and date
											$dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `total` = (total + 1) WHERE `id` = ?", array(core::$sql_date_now, $get_note_info['id']));
										}

										$new_notification_id[$email_user['user_id']] = $get_note_info['id'];
									}
								}
							}

							// send the emails
							foreach ($users_array as $email_user)
							{
								// subject
								$subject = "New reply to article {$title['title']} on GamingOnLinux.com";

								$comment_email = $bbcode->email_bbcode($comment);

								// message
								$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
								<p><strong>{$_SESSION['username']}</strong> has replied to an article you follow on titled \"<strong><a href=\"" . $core->config('website_url') . "index.php?module=articles_full&aid=$article_id&comment_id={$new_comment_id}&clear_note={$new_notification_id[$email_user['user_id']]}\">{$title['title']}</a></strong>\". There may be more comments after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
								<div>
								<hr>
								{$comment_email}
								<hr>
								<p>You can unsubscribe from this article by <a href=\"" . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $core->config('website_url') . "usercp.php\">User Control Panel</a>.</p>";

								$plain_message = PHP_EOL."Hello {$email_user['username']}, {$_SESSION['username']} replied to an article on " . $core->config('website_url') . "index.php?module=articles_full&aid=$article_id&comment_id={$new_comment_id}&clear_note={$new_notification_id[$email_user['user_id']]}\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: " . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}";

								// Mail it
								if ($core->config('send_emails') == 1)
								{
									$mail = new mailer($core);
									$mail->sendMail($email_user['email'], $subject, $html_message, $plain_message);
								}

								// remove anyones send_emails subscription setting if they have it set to email once
								if ($email_user['email_options'] == 2)
								{
									$dbl->run("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($article_id, $email_user['user_id']));
								}
							}

							// try to stop double postings, clear text
							unset($_POST['text']);

							// clear any comment or name left from errors
							unset($_SESSION['acomment']);
								
							$article_link = $article_class->get_link($_POST['aid'], $title['slug'], 'page=' . $comment_page . '#r' . $new_comment_id);

							header("Location: " . $article_link);
						}
						else if ($approved == 0)
						{
							// note who did it
							$core->new_admin_note(array('completed' => 0, 'content' => ' has a comment that <a href="/admin.php?module=mod_queue&view=manage">needs approval.</a>', 'type' => 'mod_queue_comment', 'data' => $new_comment_id));

							// try to stop double postings, clear text
							unset($_POST['text']);

							// clear any comment or name left from errors
							unset($_SESSION['acomment']);
								
							$_SESSION['message'] = 'mod_queue';
								
							$article_link = $article_class->get_link($_POST['aid'], $title['slug']);
					
							header("Location: " . $article_link);
						}
					}
				}
			}
		}
	}

	if ($_GET['go'] == 'deletecomment')
	{
		if (!isset($_GET['comment_id']) || !core::is_number($_GET['comment_id']))
		{
			$core->message('Looks like you took a wrong turn! The ID of the comment was not set, this may be a bug.');
			include('includes/footer.php');
			die();
		}

		$comment = $dbl->run("SELECT a.`slug`, c.`article_id` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array((int) $_GET['comment_id']))->fetch();
		$article_link = $article_class->get_link($comment['article_id'], $comment['slug'], '#comments');
		
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', ' - Deleting comment', 1);
			$core->yes_no('Are you sure you want to delete that comment?', url."index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$_GET['comment_id']}");
		}
		else if (isset($_POST['no']))
		{
			header("Location: ".$article_link);
			die();
		}
		else
		{
			$article_class->delete_comment($_GET['comment_id']);
			header("Location: ".$article_link);
			die();
		}
	}

	if ($_GET['go'] == 'subscribe')
	{
		$article_class->subscribe($_GET['article_id']);

		// get info for title
		$title = $dbl->run("SELECT `title` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']))->fetch();
		$title = core::nice_title($title['title']);

		header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
		die();
	}

	if ($_GET['go'] == 'unsubscribe')
	{
		$article_class->unsubscribe($_GET['article_id']);

		// get info for title
		$title = $dbl->run("SELECT `title` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']))->fetch();
		$title = core::nice_title($title['title']);

		header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
		die();
	}

	if ($_GET['go'] == 'report_comment')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Reporting a comment', 1);

			// show the comment they are reporting
			$comment = $dbl->run("SELECT c.`comment_text`, u.`user_id` FROM `articles_comments` c LEFT JOIN `users` u ON u.user_id = c.author_id WHERE c.`comment_id` = ?", array((int) $_GET['comment_id']))->fetch();
			$templating->block('report', 'articles_full');
			$templating->set('text', $bbcode->parse_bbcode($comment['comment_text']));

			// sort out the avatar
			$comment_avatar = $user->sort_avatar($comment['user_id']);

			$templating->set('comment_avatar', $comment_avatar);

			$core->yes_no('Are you sure you wish to report that comment?', url."index.php?module=articles_full&go=report_comment&article_id={$_GET['article_id']}&comment_id={$_GET['comment_id']}", "");
		}
		else if (isset($_POST['no']))
		{
			// get info for title
			$title = $dbl->run("SELECT `title`, `slug` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']))->fetch();
			
			$article_link = $article_class->get_link($_GET['article_id'], $title['slug'], '#comments');

			header("Location: ".$article_link);
		}

		else
		{
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
			{
				// note who did it
				$core->new_admin_note(array('completed' => 0, 'content' => ' has <a href="/admin.php?module=comment_reports">reported a comment.</a>', 'type' => 'reported_comment', 'data' => $_GET['comment_id']));

				$dbl->run("UPDATE `articles_comments` SET `spam` = 1, `spam_report_by` = ? WHERE `comment_id` = ?", array((int) $_SESSION['user_id'], (int) $_GET['comment_id']));
			}

			// get info for title
			$title = $dbl->run("SELECT `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']))->fetch();
			
			$article_link = $article_class->get_link($_GET['article_id'], $title['slug']);

			$_SESSION['message'] = 'reported';
			$_SESSION['message_extra'] = 'comment';

			header("Location: ".$article_link);
		}
	}

	if ($_GET['go'] == 'open_comments')
	{
		// get info for title
		$title = $dbl->run("SELECT `title`,`slug` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']))->fetch();
			
		$article_link = $article_class->get_link($_GET['article_id'], $title['slug']);

		if ($user->check_group([1,2]) == true)
		{
			$dbl->run("UPDATE `articles` SET `comments_open` = 1 WHERE `article_id` = ?", array((int) $_GET['article_id']));

			$core->new_admin_note(['complete' => 1, 'type' => 'opened_comments', 'content' => 'opened the comments on the article titled: <a href="'.$article_link.'">'.$title['title'] . '</a>', 'data' => $_GET['article_id']]);

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
		// get info for title
		$title = $dbl->run("SELECT `title`, `slug` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['article_id']))->fetch();
			
		$article_link = $article_class->get_link($_GET['article_id'], $title['slug']);

		if ($user->check_group([1,2]) == true)
		{
			$dbl->run("UPDATE `articles` SET `comments_open` = 0 WHERE `article_id` = ?", array((int) $_GET['article_id']));

			$core->new_admin_note(['complete' => 1, 'type' => 'closed_comments', 'content' => 'closed the comments on the article titled: <a href="'.$article_link.'">'.$title['title'] . '</a>', 'data' => $_GET['article_id']]);

			$_SESSION['message'] = 'comments_closed';
			header("Location: ".$article_link);
			die();
		}

		else
		{
			$_SESSION['message'] = 'no_permission';
			header("Location: ".$article_link);
			die();
		}
	}
}
