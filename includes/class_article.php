<?php
class article_class
{
  // clear out any left overs, since there's no error we don't need them, stop errors with them
  function reset_sessions()
  {
    unset($_SESSION['uploads']);
    unset($_SESSION['uploads_tagline']);
    unset($_SESSION['gallery_tagline_id']);
    unset($_SESSION['gallery_tagline_rand']);
    unset($_SESSION['gallery_tagline_filename']);
  }

  function tagline_image($data)
  {
    $tagline_image = '';
    if (!empty($data['tagline_image']))
    {
      $tagline_image = "<img alt src=\"".core::config('website_url')."uploads/articles/tagline_images/{$data['tagline_image']}\">";
    }
    if ($data['gallery_tagline'] > 0 && !empty($data['gallery_tagline_filename']))
    {
      $tagline_image = "<img alt src=\"".core::config('website_url')."uploads/tagline_gallery/{$data['gallery_tagline_filename']}\">";
    }
    if (empty($data['tagline_image']) && $data['gallery_tagline'] == 0)
    {
      $tagline_image = "<img alt src=\"".core::config('website_url')."uploads/articles/tagline_images/defaulttagline.png\">";
    }

    return $tagline_image;
  }

  // if they have set a tagline image from the gallery, remove any existing images
  public static function gallery_tagline($data = NULL)
  {
    global $db;

    $gallery_tagline_sql = '';

    if (isset($_SESSION['gallery_tagline_id']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand'])
    {
      if ($data != NULL && $data['article_id'] != NULL)
      {
        if (!empty($data['tagline_image']))
        {
          unlink(core::config('path') . 'uploads/articles/tagline_images/' . $data['tagline_image']);
          unlink(core::config('path') . 'uploads/articles/tagline_images/thumbnails/' . $data['tagline_image']);
        }

        $db->sqlquery("UPDATE `articles` SET `tagline_image` = '', `gallery_tagline` = {$_SESSION['gallery_tagline_id']} WHERE `article_id` = ?", array($data['article_id']));
      }
      else if ($data == NULL || $data['article_id'] == NULL)
      {
        $gallery_tagline_sql = ", `gallery_tagline` = {$_SESSION['gallery_tagline_id']}";
        return $gallery_tagline_sql;
      }
    }
  }

  function display_previous_uploads($article_id = NULL)
  {
    global $db;

    $previously_uploaded = '';
    if ($article_id != NULL)
    {
      // add in uploaded images from database
      $db->sqlquery("SELECT `filename`,`id` FROM `article_images` WHERE `article_id` = ? ORDER BY `id` ASC", array($article_id));
      $article_images = $db->fetch_all_rows();

      foreach($article_images as $value)
      {
        $bbcode = "[img]" . core::config('website_url') . "uploads/articles/article_images/{$value['filename']}[/img]";
        $bbcode_thumb = "[img-thumb]{$value['filename']}[/img-thumb]";
        
        // for old uploads where no thumbnail was made
        $show_thumb = '';
        $main_image = core::config('website_url') . 'uploads/articles/article_images/'.$value['filename'];
        if (file_exists(core::config('path') . 'uploads/articles/article_images/thumbs/'.$value['filename']))
        {
			$main_image = core::config('website_url') . 'uploads/articles/article_images/thumbs/'.$value['filename'];
			$show_thumb = 'BBCode (thumbnail): <input id="img'.$value['id'].'_thumb" type="text" class="form-control" value="'.$bbcode_thumb.'" /> <button class="btn" data-clipboard-target="#img'.$value['id'].'_thumb">Copy</button> <button data-bbcode="'.$bbcode_thumb.'" class="add_button">Add to editor</button>';
        }
        
        $previously_uploaded .= '<div class="box"><div class="body group"><div id="'.$value['id'].'"><img src="'.$main_image.'" class="imgList"><br />
        BBCode: <input id="img'.$value['id'].'" type="text" class="form-control" value="'.$bbcode.'" />
        <button class="btn" data-clipboard-target="#img'.$value['id'].'">Copy</button> <button data-bbcode="'.$bbcode.'" class="add_button">Add to editor</button> ' . $show_thumb .'
        <button id="'.$value['id'].'" class="trash">Delete image</button>
        </div>
        </div>
        </div>';
      }
    }
    else if ($article_id == NULL)
    {
      // sort out previously uploaded images
      if (isset($_SESSION['uploads']))
      {
        foreach($_SESSION['uploads'] as $key)
        {
          if ($key['image_rand'] == $_SESSION['image_rand'])
          {
            $bbcode = "[img]" . core::config('website_url') . "uploads/articles/article_images/{$key['image_name']}[/img]";
            $previously_uploaded .= "<div class=\"box\"><div class=\"body group\"><div id=\"{$key['image_id']}\"><img src=\"/uploads/articles/article_images/{$key['image_name']}\" class='imgList'><br />
            BBCode: <input id=\"img{$key['image_id']}\" type=\"text\" class=\"form-control\" value=\"{$bbcode}\" />
            <button class=\"btn\" data-clipboard-target=\"#img{$key['image_id']}\">Copy</button> <button data-bbcode=\"{$bbcode}\" class=\"add_button\">Add to editor</button> <button id=\"{$key['image_id']}\" class=\"trash\">Delete image</button>
            </div></div></div>";
          }
        }
      }
    }
    return $previously_uploaded;
  }

  function display_game_assoc($article_id = NULL)
  {
    global $db;

    if ($article_id != NULL)
    {
      // get games list
      $games_check_array = array();
      $db->sqlquery("SELECT `game_id` FROM `article_game_assoc` WHERE `article_id` = ?", array($article_id));
      while($games_check = $db->fetch())
      {
        $games_check_array[] = $games_check['game_id'];
      }
    }

    $games_list = '';
    $db->sqlquery("SELECT `id`, `name` FROM `calendar` ORDER BY `name` ASC");
    while ($games = $db->fetch())
    {
      // if there was some sort of error, we use the games set on the error
      if (isset($_GET['error']))
      {
        if (!empty($_SESSION['agames']) && in_array($games['id'], $_SESSION['agames']))
        {
          $games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
        }
      }

      // otherwise if we are submitting a form, like on a preview
      else if (!empty($_POST['games']) && !isset($_GET['error']))
      {
        if (in_array($games['id'], $_POST['games']))
        {
          $games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
        }
      }

      // lastly, if we are viewing an existing article
      else if (($article_id != NULL) && isset($games_check_array) && in_array($games['id'], $games_check_array))
      {
        $games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
      }
    }

    return $games_list;
  }

  public static function process_categories($article_id)
  {
    global $db;

    if (isset($article_id) && is_numeric($article_id))
    {
      // delete any existing categories that aren't in the final list for publishing
      $db->sqlquery("SELECT `ref_id`, `article_id`, `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article_id));
      $current_categories = $db->fetch_all_rows();

      if (!empty($current_categories))
      {
        foreach ($current_categories as $current_category)
        {
        	if (!in_array($current_category['category_id'], $_POST['categories']))
        	{
        		$db->sqlquery("DELETE FROM `article_category_reference` WHERE `ref_id` = ?", array($current_category['ref_id']));
        	}
        }
      }

      // get fresh list of categories, and insert any that don't exist
      $db->sqlquery("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article_id));
      $current_categories = $db->fetch_all_rows(PDO::FETCH_COLUMN, 0);

      if (isset($_POST['categories']) && !empty($_POST['categories']))
      {
        foreach($_POST['categories'] as $category)
        {
        	if (!in_array($category, $current_categories))
        	{
        		$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($article_id, $category));
        	}
        }
      }
    }
  }

  public static function process_game_assoc($article_id)
  {
    global $db;

    if (isset($article_id) && is_numeric($article_id))
    {
      // delete any existing games that aren't in the final list for publishing
      $db->sqlquery("SELECT `id`, `article_id`, `game_id` FROM `article_game_assoc` WHERE `article_id` = ?", array($article_id));
      $current_games = $db->fetch_all_rows();

      if (!empty($current_games))
      {
        foreach ($current_games as $current_game)
        {
          if (!in_array($current_game['game_id'], $_POST['games']))
          {
            $db->sqlquery("DELETE FROM `article_game_assoc` WHERE `id` = ?", array($current_game['id']));
          }
        }
      }

      // get fresh list of games, and insert any that don't exist
      $db->sqlquery("SELECT `game_id`, `id`, `article_id` FROM `article_game_assoc` WHERE `article_id` = ?", array($article_id));
      $current_games = $db->fetch_all_rows(PDO::FETCH_COLUMN, 0);

      if (isset($_POST['games']) && !empty($_POST['games']))
      {
        foreach($_POST['games'] as $game)
        {
          if (!in_array($game, $current_games))
          {
            $db->sqlquery("INSERT INTO `article_game_assoc` SET `article_id` = ?, `game_id` = ?", array($article_id, $game));
          }
        }
      }
    }
  }

  function delete_article($article)
  {
    global $db;

    $db->sqlquery("DELETE FROM `articles` WHERE `article_id` = ?", array($article['article_id']));
    $db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($article['article_id']));
    $db->sqlquery("DELETE FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']));
    $db->sqlquery("DELETE FROM `article_game_assoc` WHERE `article_id` = ?", array($article['article_id']));
    $db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($article['article_id']));
    $db->sqlquery("DELETE FROM `article_history` WHERE `article_id` = ?", array($article['article_id']));
    $db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` IN ('article_admin_queue', 'article_correction', 'article_submission_queue', 'submitted_article')  AND `completed` = 0", array(core::$date, $article['article_id']));
    $db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `data` = ?, `type` = ?, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $article_id, 'deleted_article', core::$date, core::$date));

    // if it wasn't posted by the bot, as the bot uses static images, can remove this when the bot uses gallery images
    if ($article['author_id'] != 1844)
    {
      if (!empty($article['tagline_image']))
      {
        unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']);
        unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']);
      }
    }

    // find any uploaded images, and remove them
    $db->sqlquery("SELECT * FROM `article_images` WHERE `article_id` = ?", array($article['article_id']));
    while ($image_search = $db->fetch())
    {
      unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/' . $image_search['filename']);
    }

    $db->sqlquery("DELETE FROM `article_images` WHERE `article_id` = ?", array($article['article_id']));
  }

  function display_tagline_image($article = NULL)
  {
    $tagline_image = '';

    // if it's an existing article, see if there's a tagline image to grab
    if ($article != NULL)
    {
      if (!empty($article['tagline_image']))
      {
        $tagline_image = "<div class=\"test\" id=\"{$article['tagline_image']}\"><img src=\"" . core::config('website_url') . "uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
        BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
        Full Image Url: <a href=\"" . core::config('website_url') . "uploads/articles/tagline_images/{$article['tagline_image']}\" target=\"_blank\">Click Me</a></div>";
      }
      if ($article['gallery_tagline'] > 0 && !empty($article['gallery_tagline_filename']))
      {
        $tagline_image = "<div class=\"test\" id=\"{$article['gallery_tagline']}\"><img src=\"" . core::config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
        BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
        Full Image Url: <a href=\"" . core::config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" target=\"_blank\">Click Me</a></div>";
      }
    }

    if (isset(message_map::$error) && message_map::$error == 1)
    {
      if ($_GET['temp_tagline'] == 1)
      {
        if (isset($_SESSION['uploads_tagline']))
        {
          $file = core::config('path') . 'uploads/articles/tagline_images/temp/' . $_SESSION['uploads_tagline']['image_name'];

          if (file_exists($file))
          {
            $tagline_image = "<div class=\"test\" id=\"{$_SESSION['uploads_tagline']['image_name']}\"><img src=\"".core::config('website_url')."uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" class='imgList'><br />
            BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
            <input type=\"hidden\" name=\"image_name\" value=\"{$_SESSION['uploads_tagline']['image_name']}\" />
            <a href=\"#\" id=\"{$_SESSION['uploads_tagline']['image_name']}\" class=\"trash_tagline\">Delete Image</a></div>";
          }
        }

        if (isset($_SESSION['gallery_tagline_rand']) && $_SESSION['gallery_tagline_rand'] = $_SESSION['image_rand'])
        {
          $tagline_image = "<div class=\"test\" id=\"{$_SESSION['gallery_tagline_filename']}\"><img src=\"".core::config('website_url')."uploads/tagline_gallery/{$_SESSION['gallery_tagline_filename']}\" class='imgList'><br />
          BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
          <input type=\"hidden\" name=\"image_name\" value=\"{$_SESSION['gallery_tagline_filename']}\" /></div>";
        }
      }
    }

    return $tagline_image;
  }

	// this function will check over everything necessary for an article to be correctly done
	public static function check_article_inputs($return_page)
	{
		global $db, $core;

		// if this is set to 1, we've come across an issue, so redirect
		$redirect = 0;

		// count how many editors picks we have
		$editor_picks = array();

		$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");
		while($editor_get = $db->fetch())
		{
			$editor_picks[] = $editor_get['article_id'];
		}

		$editor_pick_count = $db->num_rows();

		$temp_tagline = 0;
		if ( (!empty($_SESSION['uploads_tagline']['image_name']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand']) || (!empty($_SESSION['gallery_tagline_rand']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand']))
		{
			$temp_tagline = 1;
		}

		if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
		{
			$db->sqlquery("SELECT `tagline_image`, `gallery_tagline` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
			$check_article = $db->fetch();
		}

		$title = strip_tags($_POST['title']);
		$tagline = trim($_POST['tagline']);
		$text = trim($_POST['text']);

		// check its set, if not hard-set it based on the article title
		if (isset($_POST['slug']) && !empty($_POST['slug']))
		{
			$slug = core::nice_title($_POST['slug']);
		}
		else
		{
			$slug = core::nice_title($_POST['title']);
		}

		// make sure its not empty
		$empty_check = core::mempty(compact('title', 'tagline', 'text'));
		if ($empty_check !== true)
		{
			$redirect = 1;

			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
		}

		else if (strlen($tagline) < 100)
		{
			$redirect = 1;

			$_SESSION['message'] = 'shorttagline';
		}

		else if (strlen($tagline) > core::config('tagline-max-length'))
		{
			$redirect = 1;

			$_SESSION['message'] = 'taglinetoolong';
			$_SESSION['message_extra'] = core::config('tagline-max-length');
		}

		else if (strlen($title) < 10)
		{
			$redirect = 1;

			$_SESSION['message'] = 'shorttitle';
		}

		else if (isset($_POST['show_block']) && $editor_pick_count == core::config('editor_picks_limit'))
		{
			$redirect = 1;
			
			$_SESSION['message'] = 'editor_picks_full';
			$_SESSION['message_extra'] = core::config('editor_picks_limit');
		}

		// if it's an existing article, check tagline image
		// if database tagline_image is empty and there's no upload OR upload doesn't match (previous left over)
		else if (isset($_POST['article_id']) && !empty($_POST['article_id']))
		{
			$has_tagline_img = 0;
			if (!empty($check_article['tagline_image']) || $check_article['gallery_tagline'] > 0)
			{
				$has_tagline_img = 1;
			}
			
			if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
			{
				$has_tagline_img = 1;
			}
			
			if (isset($_SESSION['gallery_tagline_id']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand'])
			{
				$has_tagline_img = 1;
			}
			
			if ($has_tagline_img == 0)
			{
				$redirect = 1;
				
				$_SESSION['message'] = 'noimageselected';
			}
		}

		// if it's a new article, check for tagline image in a simpler way
		// if there's no upload, gallery or they don't match (one from a previous article that wasn't wiped)
		else if (!isset($_POST['article_id']))
		{
			$has_tagline_img = 0;
			if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
			{
				$has_tagline_img = 1;
			}
			
			if (isset($_SESSION['gallery_tagline_id']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand'])
			{
				$has_tagline_img = 1;
			}
			
			if ($has_tagline_img == 0)
			{
				$redirect = 1;
				
				$_SESSION['message'] = 'noimageselected';
			}
		}

		if ($redirect == 1)
		{
			$_SESSION['atitle'] = $title;
			$_SESSION['aslug'] = $slug;
			$_SESSION['atagline'] = $tagline;
			$_SESSION['atext'] = $text;

			if (isset($_POST['categories']) && !empty($_POST['categories']))
			{
				$_SESSION['acategories'] = $_POST['categories'];
			}

			if (isset($_POST['games']) && !empty($_POST['games']))
			{
				$_SESSION['agames'] = $_POST['games'];
			}

			if (isset($_POST['show_article']))
			{
				$_SESSION['aactive'] = 1;
			}
			else
			{
				$_SESSION['aactive'] = 0;
			}

			$self = 0;
			if (isset($_POST['submit_as_self']))
			{
				$self = 1;
			}

			header("Location: $return_page&self=$self&temp_tagline=$temp_tagline");
			die();
		}

		// only set them, if they actually exists
		$article_id = NULL;
		if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
		{
			$article_id = $_POST['article_id'];
		}

		$tagline_image = '';
		if (isset($check_article['tagline_image']))
		{
			$tagline_image = $check_article['tagline_image'];
		}

		$content_array = array('title' => $title, 'text' => $text, 'tagline' => $tagline, 'slug' => $slug, 'article_id' => $article_id, 'tagline_image' => $tagline_image);

		return $content_array;
	}

	// this will subscribe them to an article and generate any possible missing secret key for emails
	function subscribe($article_id, $emails = NULL)
	{
		global $db;

		if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
		{
			$db->sqlquery("SELECT `user_id`, `article_id`, `secret_key` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
			$count_subs = $db->num_rows();
			if ($count_subs == 0)
			{
				// have we been given an email option, if so use it
				if ($emails == NULL)
				{
					// find how they like to normally subscribe
					$db->sqlquery("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
					
					$get_email_type = $db->fetch();
					
					$sql_emails = $get_email_type['auto_subscribe_email'];
				}
				else
				{
					$sql_emails = (int) $emails;
				}
        
				// for unsubscribe link in emails
				$secret_key = core::random_id(15);

				$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?, `send_email` = ?, `secret_key` = ?", array($_SESSION['user_id'], $article_id, $sql_emails, $sql_emails, $secret_key));
			}
			else if ($count_subs == 1)
			{
				$get_key = $db->fetch();
				// for unsubscribe link in emails
				if (empty($get_key['secret_key']))
				{
					$secret_key = core::random_id(15);
				}
				else
				{
					$secret_key = $get_key['secret_key'];
				}
				
				// check over their email options on this new subscription
				if ($emails == NULL)
				{
					// find how they like to normally subscribe
					$db->sqlquery("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
					
					$get_email_type = $db->fetch();
					
					$sql_emails = $get_email_type['auto_subscribe_email'];
				}
				else
				{
					$sql_emails = (int) $emails;
				}
				$db->sqlquery("UPDATE `articles_subscriptions` SET `secret_key` = ?, `emails` = ?, `send_email` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $sql_emails, $sql_emails, $_SESSION['user_id'], $article_id));
			}
		}
	}

	function unsubscribe($article_id)
	{
		global $db;

		if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
		{
			$db->sqlquery("SELECT `user_id`, `article_id` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
			$count_subs = $db->num_rows();
			if ($count_subs == 1)
			{
				$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
			}
		}
	}

	function article_history($article_id)
	{
		global $db, $templating, $core;
		$db->sqlquery("SELECT u.`username`, u.`user_id`, a.`date`, a.id, a.text FROM `users` u INNER JOIN `article_history` a ON a.user_id = u.user_id WHERE a.article_id = ? ORDER BY a.id DESC LIMIT 10", array($article_id));
		$history = '';
		while ($grab_history = $db->fetch())
		{
			$view_link = '';
			if ($grab_history['text'] != NULL && !empty($grab_history['text']))
			{
				$view_link = '- <a href="/admin.php?module=article_history&id='.$grab_history['id'].'">View text</a>';
			}
			$date = $core->format_date($grab_history['date']);
			$history .= '<li><a href="/profiles/'. $grab_history['user_id'] .'">' . $grab_history['username'] . '</a> '.$view_link.' - ' . $date . '</li>';
		}

		$templating->merge('admin_modules/admin_module_articles');
		$templating->block('history', 'admin_modules/admin_module_articles');
		$templating->set('history', $history);
	}
  
	public static function get_link($id, $title, $additional = NULL)
	{
		$link = '';
		$nice_title = core::nice_title($title);
		
		if (core::config('pretty_urls') == 1)
		{
			$link = 'articles/'.$nice_title.'.'.$id;
			
			if ($additional != NULL)
			{
				$link = $link . '/' . $additional;
			}
		}
		else
		{
			$link = 'index.php?module=articles_full&aid='.$id.'&title='.$nice_title;
			
			if ($additional != NULL)
			{
				$link = $link . '&' . $additional;
			}
		}
		return core::config('website_url') . $link;
	}
	
	public static function tag_link($name)
	{
		$name = str_replace(' ', '-', $name);
		if (core::config('pretty_urls') == 1)
		{
			$link = 'articles/category/'.$name;
		}
		else
		{
			$link = 'index.php?module=articles&amp;view=cat&amp;catid='.$name;
		}
		return $link;
	}
	
	public static function publish_article($options)
	{
		global $db, $core;

		if (isset($_POST['article_id']))
		{
			// check it hasn't been accepted already
			$db->sqlquery("SELECT a.`active`, a.`author_id`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON u.`user_id` = a.`author_id` WHERE a.`article_id` = ?", array($_POST['article_id']));
			$check_article = $db->fetch();
			if ($check_article['active'] == 1)
			{
				$_SESSION['message'] = 'already_approved';
				header("Location: ".$options['return_page']);
			}
		}
			
		// check everything is set correctly
		$checked = self::check_article_inputs($options['return_page']);
			
		// check if it's an editors pick
		$editors_pick = 0;
		if (isset($_POST['show_block']))
		{
			$editors_pick = 1;
		}
		
		$gallery_tagline_sql = self::gallery_tagline($checked);
		
		// an existing article needs cleaning up and updating
		if (isset($_POST['article_id']) && !empty($_POST['article_id']))
		{
			// this is for user submissions, if we are submitting it as ourselves, to auto thank them for the submission
			if (isset($_POST['submit_as_self']))
			{
				$author_id = $_SESSION['user_id'];
				$submission_date = '';

				if (!empty($check_article['username']))
				{
					$submitted_by_user = $check_article['username'];
				}

				else if (!empty($check_article['guest_username']))
				{
					$submitted_by_user = $check_article['guest_username'];
				}

				else
				{
					$submitted_by_user = "a guest submitter";
				}

				$checked['text'] = $checked['text'] . "\r\n\r\n[i]Thanks to " . $submitted_by_user . ' for letting us know![/i]';
			}
			else
			{
				$author_id = $check_article['author_id'];
			}
		
			$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($_POST['article_id']));
			$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));
			
			if ($options['type'] != 'draft')
			{
				$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = ?", array(core::$date, $_POST['article_id'], $options['clear_notification_type']));
			}
			
			$db->sqlquery("UPDATE `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `submitted_unapproved` = 0, `draft` = 0, `locked` = 0 WHERE `article_id` = ?", array($author_id, $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $editors_pick, core::$date, $_SESSION['user_id'], $_POST['article_id']));
			
			// since they are approving and not neccisarily editing, check if the text matches, if it doesnt they have edited it
			if ($_SESSION['original_text'] != $checked['text'])
			{
				$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));
			}
			
			if ($_SESSION['user_id'] == $author_id)
			{
				if (isset($_POST['subscribe']))
				{
					$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = 1, `send_email` = 1", array($_SESSION['user_id'], $_POST['article_id']));
				}
			}
			
			$article_id = $_POST['article_id'];
		}
		// otherwise make the new article
		else
		{
			$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0 $gallery_tagline_sql", array($_SESSION['user_id'], $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $editors_pick, core::$date));
			
			$article_id = $db->grab_id();
			
			if (isset($_POST['subscribe']))
			{
				$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = 1, `send_email` = 1", array($_SESSION['user_id'], $article_id));
			}
		}
		
		// upload attached images
		if (isset($_SESSION['uploads']))
		{
			foreach($_SESSION['uploads'] as $key)
			{
				$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($article_id, $key['image_name']));
			}
		}
		
		self::process_categories($article_id);

		self::process_game_assoc($article_id);
		
		// move new uploaded tagline image, and save it to the article
		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name']);
		}
		
		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], $options['new_notification_type'], core::$date, core::$date, $article_id));
		
		$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");
		
		unset($_SESSION['atitle']);
		unset($_SESSION['aslug']);
		unset($_SESSION['atagline']);
		unset($_SESSION['atext']);
		unset($_SESSION['acategories']);
		unset($_SESSION['agame']);
		unset($_SESSION['uploads']);
		unset($_SESSION['image_rand']);
		unset($_SESSION['uploads_tagline']);
		unset($_SESSION['original_text']);
		unset($_SESSION['gallery_tagline_id']);
		unset($_SESSION['gallery_tagline_rand']);
		unset($_SESSION['gallery_tagline_filename']);
		
		// if the person publishing it is not the author then email them
		if ($options['type'] == 'admin_review')
		{
			if ($_POST['author_id'] != $_SESSION['user_id'])
			{
				// find the authors email
				$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']));
				$author_email = $db->fetch();

				// subject
				$subject = 'Your article was reviewed and published on GamingOnLinux.com!';

				// message
				$message = "
				<html>
				<head>
				<title>Your article was review and approved GamingOnLinux.com!</title>
				</head>
				<body>
				<img src=\"http://www.gamingonlinux.com/templates/default/images/logo.png\" alt=\"Gaming On Linux\">
				<br />
				<p><strong>{$_SESSION['username']}</strong> has reviewed and published your article \"<a href=\"http://www.gamingonlinux.com/articles/{$checked['slug']}.{$_POST['article_id']}/\">{$checked['title']}</a>\" on <a href=\"https://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>.</p>
				</body>
				</html>";

				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

				// Mail it
				if (core::config('send_emails') == 1)
				{
					mail($author_email['email'], $subject, $message, $headers);
				}
			}
		}
		
		if ($options['type'] == 'submitted_article')
		{
			// pick the email to use
			$email = '';
			if (!empty($check_article['guest_email']))
			{
				$email = $check_article['guest_email'];
			}

			else if (!empty($check_article['email']))
			{
				$email = $check_article['email'];
			}

			// subject
			$subject = 'Your article was approved on GamingOnLinux.com!';

			// message
			$message = "
			<html>
			<head>
			<title>Your article was approved GamingOnLinux.com!</title>
			</head>
			<body>
			<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
			<br />
			<p>We have accepted your article \"<a href=\"http://www.gamingonlinux.com/articles/{$checked['slug']}.{$_POST['article_id']}/\">{$checked['title']}</a>\" on <a href=\"http://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>. Thank you for taking the time to send us news we really appreciate the help, you are awesome.</p>
			</body>
			</html>";

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

			if (core::config('send_emails') == 1)
			{
				mail($email, $subject, $message, $headers);
			}
		}
		
		include(core::config('path') . 'includes/telegram_poster.php');

		$article_link = self::get_link($article_id, $checked['slug']);

		if (!isset($_POST['show_block']))
		{
			telegram($checked['title'] . ' ' . $article_link);
			header("Location: ".$article_link);			
		}
		else
		{
			telegram($checked['title'] . ' ' $article_link);
			header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id=".$article_id);
		}
	}
}
?>
