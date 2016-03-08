<?php
$templating->merge('articles_full');

require_once("includes/ayah/ayah.php");
$ayah = new AYAH();

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
			$core->message('That is not a correct article id!');
		}

		else
		{
			// get the article
			$db->sqlquery("SELECT a.article_id, a.title, a.text, a.page2, a.page3, a.tagline, a.date, a.date_submitted, a.author_id, a.active, a.guest_username, a.views, a.article_top_image, a.article_top_image_filename, a.comments_open, u.username, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u.article_bio, u.user_group, u.twitter_on_profile FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.article_id = ?", array($_GET['aid']), 'articles_full.php');
			$article = $db->fetch();
			
			if ($db->num_rows() == 0)
			{
				$templating->set_previous('meta_description', 'Article error on GamingOnLinux', 1);
				$templating->set_previous('title', ' - Article Error', 1);
				$core->message('Sorry but that article doesn\'t exist, if you have followed an old it link it may need updating for the new website.');
			}
		
			else if ($article['active'] == 0 && !isset($_GET['preview']))
			{
				$templating->set_previous('meta_description', 'Article error on GamingOnLinux', 1);
				$templating->set_previous('title', ' - Article Inactive', 1);
				$core->message('This article is currently inactive!');
			}
		
			else
			{
				// update the view counter
				$db->sqlquery("UPDATE `articles` SET `views` = (views + 1) WHERE `article_id` = ?", array($article['article_id']), 'articles_full.php');

				if ($article['active'] == 0 && isset($_GET['preview']))
				{
					$templating->set_previous('meta_description', $article['tagline'] . ' - On GamingOnLinux.com PREVIEW', 1);
					$templating->set_previous('title', ' - ' . ucwords($article['title']) . ' - PREVIEW', 1);
					
					$core->message('This article is currently inactive, you are seeing a Preview.');
				}
				else
				{
					$templating->set_previous('meta_description', $article['tagline'] . ' - On GamingOnLinux.com', 1);
					$templating->set_previous('title', ' - ' . ucwords($article['title']), 1);
				}

				// set the article image meta
				$article_meta_image = '';
				if ($article['article_top_image'] == 1)
				{
					$article_meta_image = "http://www.gamingonlinux.com/uploads/articles/topimages/{$article['article_top_image_filename']}";
				}
				
				$nice_title = $core->nice_title($article['title']);
				
				// twitter info card
				$twitter_card = "<!-- twitter card -->\n";
				$twitter_card .= '<meta name="twitter:card" content="summary">';
				$twitter_card .= '<meta name="twitter:site" content="@gamingonlinux">';
				if (!empty($article['twitter_on_profile']) && $article['twitter_on_profile'] !== 'gamingonlinux' )
				{
					$twitter_card .= '<meta name="twitter:creator" content="@'.$article['twitter_on_profile'].'">';
				}
                    
				$twitter_card .= '<meta name="twitter:title" content="'.$article['title'].'">';
				$twitter_card .= '<meta name="twitter:description" content="'.bbcode($article['tagline']).'">';
				$twitter_card .= '<meta name="twitter:image" content="'.$article_meta_image.'">';

				// meta tags for g+, facebook and twitter images
				$templating->set_previous('article_image', "<meta property=\"og:image\" content=\"$article_meta_image\"/>$twitter_card", 1);

				// make date human readable
				$date = $core->format_date($article['date']);

				$templating->block('article', 'articles_full');
		
				if ($user->check_group(1,2))
				{
					$templating->set('edit_link', " <a href=\"/admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"><i class=\"icon-pencil\"></i><strong>Edit</strong></a>");
				}

				else
				{
					$templating->set('edit_link', '');
				}
		
				$templating->set('title', ucwords($article['title']));
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
					$view_more = "<a href=\"/index.php?module=search&author_id={$article['author_id']}\"><span class=\"glyphicon glyphicon-search\"></span> View more articles from {$article['username']}</a>";
				}
				
				$templating->set('username', $username);
			
				$templating->set('date', $date);

				$submitted_date = '';
				if (!empty($article['date_submitted']))
				{
					$submitted_date = $core->format_date($article['date_submitted']);
					$submitted_date = "(Article originally submitted $submitted_date)";
				}

				$templating->set('submitted_date', $submitted_date);
				
				$templating->set('article_views', $article['views']);
				
				$categories_list = '';
				// sort out the categories (tags)
				$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ?", array($article['article_id']), 'articles_full.php');
				while ($get_categories = $db->fetch())
				{
					$categories_list .= " <a href=\"/articles/category/{$get_categories['category_id']}\"><span class=\"label label-info\">{$get_categories['category_name']}</span></a> ";
				}
				
				if (!empty($categories_list))
				{
					$categories_list = 'Tags: ' . $categories_list;
				}
				
				$templating->set('categories_list', $categories_list);

				$templating->set('share_url', "http://www.gamingonlinux.com/articles/$nice_title.{$_GET['aid']}/");
				
				$article_bottom = '';
				if ($article['user_group'] != 1 && $article['user_group'] != 2 && $article['user_group'] != 5)
				{
					$article_bottom = "\n<br /><br /><p class=\"small muted\">This article was submitted by a guest and may not reflect the views of GamingOnLinux.com, we encourage anyone to <a href=\"//www.gamingonlinux.com/submit-article/\">submit their own articles</a>.</p>";
				}
				
				$article_page = 1;
				if (isset($_GET['article_page']) && is_numeric($_GET['article_page']))
				{
					$article_page = $_GET['article_page'];
				}
				
				if ($article_page == 1)
				{
					$templating->set('text', bbcode($article['text']) . $article_bottom);
				}
				
				else
				{
					$templating->set('text', bbcode($article['page'.$article_page]) . $article_bottom);
				}
				
				$pages = 1;
				if (!empty($article['page2']))
				{
					$pages = 2;
				}
				if (!empty($article['page3']))
				{
					$pages = 3;
				}

				$article_link = "/articles/$nice_title.{$_GET['aid']}/";
				if (isset($_GET['preview']))
				{
					$article_link = "/index.php?module=articles_full&aid={$_GET['aid']}&preview&";
				}
				
				$article_pagination = $core->article_pagination($article_page, $pages, $article_link);
				
				$templating->set('paging', $article_pagination);

				if (!empty($article['article_bio']) && ($article['user_group'] == 1 || $article['user_group'] == 2 || $article['user_group'] == 5))
				{
					$templating->block('bio', 'articles_full');

					// sort out the avatar
					// either no avatar (gets no avatar from gravatars redirect) or gravatar set
					if (empty($article['avatar']) || $article['avatar_gravatar'] == 1)
					{
						$avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $article['gravatar_email'] ) ) ) . "?d={$config['website_url']}/uploads/avatars/no_avatar_small.gif";
					}
		
					// either uploaded or linked an avatar
					else 
					{
						$avatar = $article['avatar'];
						if ($article['avatar_uploaded'] == 1)
						{
							$avatar = "/uploads/avatars/{$article['avatar']}";
						}
					}

					$templating->set('avatar', $avatar);

					$templating->set('username', $username);
					$templating->set('view_more', $view_more);
					
					$bio = bbcode($article['article_bio']);
					
					$templating->set('article_bio', $bio);
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

				// get the comments if we aren't in preview mode
				if ($article['active'] == 1)
				{
					// count how many there is in total
					$sql_count = "SELECT `comment_id` FROM `articles_comments` WHERE `article_id` = ?";
					$db->sqlquery($sql_count, array($_GET['aid']));
					$total_pages = $db->num_rows();

					if ($_SESSION['user_group'] != 4)
					{
						// find out if this user has subscribed to the comments
						if ($_SESSION['user_id'] != 0)
						{
							$db->sqlquery("SELECT `user_id` FROM `articles_subscriptions` WHERE `article_id` = ? AND `user_id` = ?", array($_GET['aid'], $_SESSION['user_id']), 'articles_full.php');
							if ($db->num_rows() == 1)
							{
								$subscribe_link = "<a href=\"/index.php?module=articles_full&amp;go=unsubscribe&amp;article_id={$_GET['aid']}\"><span class=\"label label-default\">Unsubscribe from comments</span></a>";
							}

							else
							{
								$subscribe_link = "<a href=\"/index.php?module=articles_full&amp;go=subscribe&amp;article_id={$_GET['aid']}\"><span class=\"label label-default\">Subscribe to comments</span></a>";
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
								$close_comments_link = "<span class=\"label label-warning\"><a href=\"/index.php?module=articles_full&go=close_comments&article_id={$article['article_id']}\" class=\"white-link\">Close Comments</a></span>";
							}
							else if ($article['comments_open'] == 0)
							{
								$close_comments_link = "<span class=\"label label-success\"><a href=\"/index.php?module=articles_full&go=open_comments&article_id={$article['article_id']}\" class=\"white-link\">Open Comments</a></span>";
							}
						}
						$templating->set('close_comments', $close_comments_link);
					}
					
					if ($article['comments_open'] == 0)
					{
						$templating->block('comments_closed', 'articles_full');
					}
					
					if ($user->check_group(6) == false)
					{
						$templating->block('comments_advert', 'articles_full');
					}

					// sort out the pagination link
					$pagination = $core->pagination_link(9, $total_pages, "/articles/$nice_title.{$_GET['aid']}/", $page, '#comments');
					
					if (isset($_GET['message']))
					{
						if ($_GET['message'] == 'reported')
						{
							$core->message('Thanks, reported that comment as spam! We appreciate the help!');
						}
					}

					//
					/* COMMENTS SECTION */
					//
					
					include('includes/profile_fields.php');
		
					$db_grab_fields = '';
					foreach ($profile_fields as $field)
					{
						$db_grab_fields .= "u.`{$field['db_field']}`,";
					}
					
					$db->sqlquery("SELECT a.author_id, a.guest_username, a.comment_text, a.comment_id, a.time_posted, u.username, u.user_group, u.secondary_user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, $db_grab_fields u.`avatar_uploaded` FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE a.`article_id` = ? ORDER BY a.`comment_id` ASC LIMIT ?, 9", array($_GET['aid'], $core->start), 'articles_full.php comments');

					$comments_get = $db->fetch_all_rows();

					foreach ($comments_get as $comments)
					{
						// make date human readable
						$date = $core->format_date($comments['time_posted']);

						if ($comments['author_id'] == 0)
						{
							$username = $comments['guest_username'];
							$quote_username = preg_replace('/[^A-Za-z0-9 -]+/', "", $comments['guest_username']);
						}
						else
						{
							$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
							$quote_username = preg_replace('/[^A-Za-z0-9 -]+/', "", $comments['username']);
						}

						// sort out the avatar
						// either no avatar (gets no avatar from gravatars redirect) or gravatar set
						if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
						{
							$comment_avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=//www.gamingonlinux.com/uploads/avatars/no_avatar_small.gif";
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

						$editor_bit = '';
						// check if editor or admin 
						if ($comments['user_group'] == 1 || $comments['user_group'] == 2)
						{
							$editor_bit = "<span class=\"label label-success\">Editor</span><br />";
						}
						
						// check if accepted submitter
						if ($comments['user_group'] == 5)
						{
							$editor_bit = "<span class=\"label label-success\">Contributing Editor</span><br />";
						}

						$templating->block('article_comments', 'articles_full');
						$templating->set('user_id', $comments['author_id']);
						$templating->set('username', $username);
						$templating->set('plain_username', $quote_username);
						$templating->set('editor', $editor_bit);
						$templating->set('comment_avatar', $comment_avatar);
						$templating->set('date', $date);
						$templating->set('text', bbcode($comments['comment_text'], 0));
						$templating->set('article_id', $_GET['aid']);
						$templating->set('comment_id', $comments['comment_id']);

						// Total number of likes for the status message
						$qtotallikes = $db->sqlquery("SELECT * FROM likes WHERE comment_id = ?", array($comments['comment_id']));  
						$total_likes = $db->num_rows($qtotallikes);

						$templating->set('total_likes', $total_likes);

						$like_link = '&nbsp; Likes';
						if ($_SESSION['user_id'] != 0)
						{
							// Checks current login user liked this status or not
							$qnumlikes = $db->sqlquery("SELECT `like_id` FROM likes WHERE user_id = ? AND comment_id = ?", array($_SESSION['user_id'], $comments['comment_id'])); 
							$numlikes = $db->num_rows();

							if ($numlikes == 0)
							{
								$like_text = "Like";
							}
							else if ($numlikes >= 1)
							{
								$like_text = "Unlike";
							}

							$like_link = "&nbsp;<a class=\"likes\" id=\"{$total_likes}\" href=\"#\">{$like_text}</a>";
						}
						$templating->set('like_link', $like_link);

						$donator_badge = '';
						
						if (($comments['secondary_user_group'] == 6 || $comments['secondary_user_group'] == 7) && $comments['user_group'] != 1 && $comments['user_group'] != 2)
						{
							$donator_badge = '<br />
							<span class="label label-warning">GOL Supporter!</span><br /> ';
						}
						
						$profile_fields_output = '';
						
						foreach ($profile_fields as $field)
						{
							if (!empty($comments[$field['db_field']]))
							{
								$url = '';
								if ($field['base_link_required'] == 1)
								{
									$url = $field['base_link'];
								}
								
								$image = '';
								if ($field['image'] != NULL)
								{
									$image = "<img src=\"{$field['image']}\" alt=\"{$field['name']}\" />";
								}
								
								$span = '';
								if (isset($field['span']))
								{
									$span = $field['span'];
								}
								
								$profile_fields_output .= " <a href=\"$url{$comments[$field['db_field']]}\">$image$span</a> ";
							}
						}
						
						$templating->set('profile_fields', $profile_fields_output);
						
						$templating->set('donator_badge', $donator_badge);

						$comment_edit_link = '';
						if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $comments['author_id'] || $user->check_group(1,2) == true && $_SESSION['user_id'] != 0)
						{
							$comment_edit_link = "<a href=\"/index.php?module=articles_full&amp;view=Edit&amp;comment_id={$comments['comment_id']}&page=$page\"><i class=\"icon-edit\"></i> Edit</a>";
						}
						$templating->set('edit', $comment_edit_link);

						$comment_delete_link = '';
						if ($user->check_group(1,2) == true)
						{
							$comment_delete_link = "<a href=\"/index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\"><i class=\"icon-remove\"></i> Delete</a>";
						}
						$templating->set('delete', $comment_delete_link);
						
						$report_link = '';
						if ($_SESSION['user_id'] != 0)
						{
							$report_link = "<a href=\"/index.php?module=articles_full&amp;go=spam&amp;article_id={$_GET['aid']}&amp;comment_id={$comments['comment_id']}\"><i class=\"icon-flag\"></i> Report Spam</a>";
						}					
						$templating->set('report_spam', $report_link);
					}
					
					$templating->block('bottom', 'articles_full');
					$templating->set('pagination', $pagination);
					
					$captcha = '';
					if ($parray['article_comments_captcha'] == 1)
					{
						$captcha = $ayah->getPublisherHTML();
					}
					
					if (isset($_GET['error']))
					{
						if ($_GET['error'] == 'emptycomment')
						{
							$core->message('You cannot post an empty comment dummy!', NULL, 1);
						}
						
						if ($_GET['error'] == 'failedcaptcha')
						{
							$core->message("You need to complete the captcha to prove you are human and not a bot!", NULL, 1);
						}
					}
					
					// only show comments box if the comments are turned on for this article
					
					if (($article['comments_open'] == 1) || ($article['comments_open'] == 0 && $user->check_group(1,2) == true))
					{
						if ($_SESSION['user_group'] == 4)
						{
							if ($config['guest_article_comments'] == 1)
							{
								$templating->block('comments_box_guest', 'articles_full');
								$templating->set('aid', $_GET['aid']);
								$templating->set('captcha', $captcha);
						
								$guest_name = '';
								if (isset($_SESSION['guest_name']))
								{
									$guest_name = $_SESSION['guest_name'];
								}
								$templating->set('guest_name', $guest_name);
						
								$comment = '';
								if (isset($_SESSION['acomment']))
								{
									$comment = $_SESSION['acomment'];
								}
								$templating->set('comment', $comment);
							}
							else if ($config['guest_article_comments'] == 0)
							{
								$core->message($config['guest_article_comments_message']);
							}
						}

						else
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
						
							$templating->block('comments_box', 'articles_full');
							$templating->set('aid', $_GET['aid']);
							$templating->set('captcha', $captcha);
							$templating->set('subscribe_check', $subscribe_check);
							$templating->set('subscribe_email_check', $subscribe_email_check);
							$templating->set('comment', '');
						}
					}
				}
			}
		}
	}

	else if (isset($_GET['view']) && $_GET['view'] == 'Edit')
	{
		$db->sqlquery("SELECT c.`author_id`, c.comment_id, c.`comment_text`, c.time_posted, a.`title`, a.article_id FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array($_GET['comment_id']), 'articles_full.php');
		$comment = $db->fetch();
		
		// check if author
		if ($_SESSION['user_id'] != $comment['author_id'] && $user->check_group(1,2) == false || $_SESSION['user_id'] == 0)
		{
			$nice_title = $core->nice_title($comment['title']);
			header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		}
		
		$templating->set_previous('meta_description', 'Editing a comment on GamingOnLinux', 1);
		$templating->set_previous('title', ' - Editing a comment', 1);
		
		if (isset($_GET['preview']))
		{
			$templating->block('preview');
		
			$templating->set('username', $_SESSION['username']);
		
			$templating->set('editor', '');
			$templating->set('edit', '');
			$templating->set('delete', '');
			$templating->set('report_spam', '');
			$templating->set('comment_id', '');
		
			$date = $core->format_date($comment['time_posted']);
		
			$templating->set('date', $date);
		
			$desura = "<img src=\"/templates/default/images/desura.png\" alt=\"desura\" />";
			$steam = "<img src=\"/templates/default/images/steam.png\" alt=\"steam\" />";
			$website = "<i class=\"icon-globe\"></i>";

			$templating->set('desura', $desura);
			$templating->set('steam', $steam);
			$templating->set('website', $website);
		
			$templating->set('text', bbcode($_POST['text']));
		
			// avatar
			if ($comment['author_id'] != 0)
			{
				$db->sqlquery("SELECT `avatar`, `avatar_gravatar`, `gravatar_email`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($comment['author_id']), 'articles_full.php');
				$comments = $db->fetch();
				
				// sort out the avatar
				// either no avatar (gets no avatar from gravatars redirect) or gravatar set
				if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
				{
					$comment_avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=//www.gamingonlinux.com/uploads/avatars/no_avatar_small.gif";
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
				$comment_avatar = "//www.gamingonlinux.com/uploads/avatars/no_avatar_small.gif";
			}
		
			$templating->set('comment_avatar', $comment_avatar);
		}

		// display the edit block
		$templating->block('edit_comment');
		$templating->set('title', $comment['title']);
		if (isset($_GET['preview']))
		{
			$comment_text = $_POST['text'];
		}
		else
		{
			$comment_text = $comment['comment_text'];
		}
		$templating->set('text', $comment_text);
		$templating->set('comment_id', $comment['comment_id']);
		$templating->set('page', $_GET['page']);
	}
}

else if (isset($_GET['go']))
{
	if ($_GET['go'] == 'comment')
	{
		// make sure news id is a number
		if (!is_numeric($_POST['aid']))
		{
			$core->message('Article id was not a number! Stop trying to do something naughty!');
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
				$db->sqlquery("SELECT `title`, `comment_count` FROM `articles` WHERE `article_id` = ?", array($_POST['aid']), 'articles_full.php');
				$title = $db->fetch();
				$title_nice = $core->nice_title($title['title']);
				
				$page = 1;
				if ($title['comment_count'] > 9)
				{
					$page = ceil($title['comment_count']/9);
				}

				// check empty
				$comment = trim($_POST['text']);

				// filter naughties
				$comment = $core->bad_words($comment);
				if (empty($comment))
				{
					header("Location: /articles/$title_nice.{$_POST['aid']}/error=emptycomment#commentbox");
				}

				else
				{
					if ($parray['article_comments_captcha'] == 1)
					{
						// Use the AYAH object to get the score.
						$score = $ayah->scoreResult();
					}
		
					if ($parray['article_comments_captcha'] == 1 && !$score)
					{
						$_SESSION['acomment'] = $_POST['text'];
						$_SESSION['guest_name'] = $_POST['guest_name'];
					
						header("Location: /articles/$title_nice.{$_POST['aid']}/error=failedcaptcha#commentbox");	
					}

					else if (($parray['article_comments_captcha'] == 1 && $score) || $parray['article_comments_captcha'] == 0)
					{
						$comment = htmlspecialchars($comment, ENT_QUOTES);
					
						$article_id = $_POST['aid'];
					
						// add the comment
						if ($_SESSION['user_group'] != 4)
						{
							$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($_POST['aid'], $_SESSION['user_id'], $core->date, $comment), 'articles_full.php');
						}
						else if ($_SESSION['user_group'] == 4)
						{
							if (empty($_POST['guest_name']))
							{
								$_POST['guest_name'] = 'Anonymous';
							}
							$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `guest_username` = ?, `guest_ip` = ?, `time_posted` = ?, `comment_text` = ?", array($_POST['aid'], $_SESSION['user_id'], $_POST['guest_name'], $core->ip, $core->date, $comment), 'articles_full.php');
						}
				
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
					
							$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?", array($_SESSION['user_id'], $article_id, $emails));
						}

						// email anyone subscribed which isn't you
						$db->sqlquery("SELECT s.`user_id`, s.emails, u.email, u.username FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE `article_id` = ?", array($article_id));
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
							$title_upper = ucwords($title['title']);

							// subject
							$subject = "New reply to article {$title_upper} on GamingOnLinux.com";
					
							// sort out username
							if (isset($_SESSION['username']))
							{
								$username = $_SESSION['username'];
							}
					
							else
							{
								$username = $_POST['guest_name'];
							}
					
							$comment_email = email_bbcode($comment);

							// message
							$html_message = "
							<html>
							<head>
							<title>New reply to an article you follow on GamingOnLinux.com</title>
							<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
							</head>
							<body>
							<img src=\"{$config['website_url']}/templates/default/images/logo.png\" alt=\"Gaming On Linux\">
							<br />
							<p>Hello <strong>{$email_user['username']}</strong>,</p>
							<p><strong>{$username}</strong> has replied to an article you follow on titled \"<strong><a href=\"{$config['website_url']}/articles/$title_nice.$article_id#comments\">{$title_upper}</a></strong>\".</p>
							<div>
					 		<hr>
					 		{$comment_email}
					 		<hr>
					 		You can unsubscribe from this article by <a href=\"{$config['website_url']}/unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"{$config['website_url']}/usercp.php\">User Control Panel</a>.
					 		<hr>
					  		<p>If you haven&#39;t registered at <a href=\"{$config['website_url']}\" target=\"_blank\">{$config['website_url']}</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
					  		<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
							</div>
							</body>
							</html>
							";

							$plain_message = PHP_EOL."Hello {$email_user['username']}, {$username} replied to an article on {$config['website_url']}/articles/$title_nice.$article_id#comments\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: {$config['website_url']}/unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}";

							$boundary = uniqid('np');

							// To send HTML mail, the Content-type header must be set
							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= "Content-Type: multipart/alternative;charset=utf-8;boundary=" . $boundary . "\r\n";
							$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

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
							mail($to, $subject, $message, $headers);					
						}
				
						// try to stop double postings, clear text
						unset($_POST['text']);
					
						// clear any comment or name left from errors
						unset($_SESSION['acomment']);
						unset($_SESSION['guest_name']);

						header("Location: /articles/$title_nice.$article_id/page={$page}#{$new_comment_id}");
					}
				}
			}
		}
	}
	
	if ($_GET['go'] == 'preview')
	{
		$templating->block('preview');
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
		
		$desura = "<img src=\"/templates/default/images/desura.png\" alt=\"desura\" />";
		$steam = "<img src=\"/templates/default/images/steam.png\" alt=\"steam\" />";
		$website = "<i class=\"icon-globe\"></i>";
		$twitter = "<img src=\"/templates/default/images/twitter.gif\" alt=\"twitter\" />";

		$templating->set('desura', $desura);
		$templating->set('steam', $steam);
		$templating->set('website', $website);
		$templating->set('twitter', $twitter);
		
		$comment = $core->bad_words($_POST['text']);
		$templating->set('text', bbcode($comment));
		
		// avatar
		$db->sqlquery("SELECT `avatar`, `avatar_gravatar`, `gravatar_email`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']), 'articles_full.php');
		$comments = $db->fetch();
		
		// sort out the avatar
		// either no avatar (gets no avatar from gravatars redirect) or gravatar set
		if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
		{
			$comment_avatar = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=//www.gamingonlinux.com/uploads/avatars/no_avatar_small.gif";
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
		
		$captcha = '';
		if ($parray['article_comments_captcha'] == 1)
		{
			$captcha = $ayah->getPublisherHTML();
		}

		if ($_SESSION['user_group'] == 4)
		{
			$templating->block('comments_box_guest');
			$templating->set('aid', $_POST['aid']);
			$templating->set('captcha', $captcha);
			$templating->set('guest_name', $_POST['guest_name']);
		}

		else
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
					
			$templating->block('comments_box');
			$templating->set('aid', $_POST['aid']);
			$templating->set('captcha', $captcha);
			$templating->set('subscribe_check', $subscribe_check);
			$templating->set('subscribe_email_check', $subscribe_email_check);
			
		}
		$templating->set('comment', $core->bad_words($_POST['text']));
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
			$comment = $core->bad_words($comment);
			// check empty
			if (empty($comment_text))
			{
				$core->message('You cannot post an empty comment');
			}

			// update comment
			else
			{
				$comment_text = htmlspecialchars($comment_text, ENT_QUOTES);
				
				$db->sqlquery("UPDATE `articles_comments` SET `comment_text` = ? WHERE `comment_id` = ?", array($comment_text, $_POST['comment_id']));

				$nice_title = $core->nice_title($comment['title']);
				header("Location: /articles/$nice_title.{$comment['article_id']}/page={$_GET['page']}#comments");
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
			header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		}

		else
		{
			if ($comment['author_id'] == 1 && $_SESSION['user_id'] != 1)
			{
				header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
			}
			
			else
			{
				if (!isset($_POST['yes']) && !isset($_POST['no']))
				{
					$templating->set_previous('title', ' - Deleting comment', 1);
					$core->yes_no('Are you sure you want to delete that comment?', "index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$_GET['comment_id']}");
				}

				else if (isset($_POST['no']))
				{
					header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
				}

				else if (isset($_POST['yes']))
				{
					// this comment was reported as spam but as its now deleted remove the notification
					if ($comment['spam'] == 1)
					{
						$db->sqlquery("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'admin_notifications'");	
					}
					
					// delete comment and update comments counter
					$db->sqlquery("UPDATE `articles` SET `comment_count` = (comment_count - 1) WHERE `article_id` = ?", array($comment['article_id']));
					$db->sqlquery("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array($_GET['comment_id']));
					$db->sqlquery("DELETE FROM `likes` WHERE `comment_id` = ?", array($_GET['comment_id']));
					
					// add to editor tracking
					$db->sqlquery("INSERT INTO `editor_tracking` SET `action` = ?, `time` = ?", array("{$_SESSION['username']} deleted a comment from an article page directly.", $core->date));

					header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
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
	
	if ($_GET['go'] == 'spam')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you wish to report that comment as spam?', "/index.php?module=articles_full&go=spam&article_id={$_GET['article_id']}&comment_id={$_GET['comment_id']}", "");
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
				$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'admin_notifications'");
					
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
			$title = $core->nice_title($title['title']);

			header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
			
			if ($user->check_group(1,2) == false)
			{
				header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
			}
			
			else
			{
				$db->sqlquery("UPDATE `articles` SET `comments_open` = 1 WHERE `article_id` = ?", array($_GET['article_id']));
			}
			header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
		}
		
		else
		{
			header("Location: //www.gamingonlinux.com");
		}
	}
	
	if ($_GET['go'] == 'close_comments')
	{
		if ($user->check_group(1,2) == true)
		{
			// get info for title
			$db->sqlquery("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
			$title = $db->fetch();
			$title = $core->nice_title($title['title']);

			header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
			
			if ($user->check_group(1,2) == false)
			{
				header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
			}
			
			else
			{
				$db->sqlquery("UPDATE `articles` SET `comments_open` = 0 WHERE `article_id` = ?", array($_GET['article_id']));
			}
			header("Location: /articles/{$title}.{$_GET['article_id']}#comments");
		}
		
		else
		{
			header("Location: //www.gamingonlinux.com");
		}
	}
}
