<?php
class article
{
	private $database;
	private $core;
	private $user;
	private $templating;
	private $bbcode;
	
	function __construct($database, $core, $user, $templating, $bbcode)
	{
		$this->database = $database;
		$this->core = $core;
		$this->user = $user;
		$this->templating = $templating;
		$this->bbcode = $bbcode;
	}
	
	// clear out any left overs, since there's no error we don't need them, stop errors with them
	function reset_sessions()
	{
		unset($_SESSION['uploads']);
		unset($_SESSION['uploads_tagline']);
		unset($_SESSION['gallery_tagline_id']);
		unset($_SESSION['gallery_tagline_rand']);
		unset($_SESSION['gallery_tagline_filename']);
	}

	public function tagline_image($data)
	{
		$tagline_image = '';
		if (!empty($data['tagline_image']))
		{
			$tagline_image = "<img alt src=\"".$this->core->config('website_url')."uploads/articles/tagline_images/{$data['tagline_image']}\">";
		}
		if ($data['gallery_tagline'] > 0 && !empty($data['gallery_tagline_filename']))
		{
			$tagline_image = "<img alt src=\"".$this->core->config('website_url')."uploads/tagline_gallery/{$data['gallery_tagline_filename']}\">";
		}
		if (empty($data['tagline_image']) && $data['gallery_tagline'] == 0)
		{
			$tagline_image = "<img alt src=\"".$this->core->config('website_url')."uploads/articles/tagline_images/defaulttagline.png\">";
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
					unlink($this->core->config('path') . 'uploads/articles/tagline_images/' . $data['tagline_image']);
					unlink($this->core->config('path') . 'uploads/articles/tagline_images/thumbnails/' . $data['tagline_image']);
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
        $bbcode = "[img]" . $this->core->config('website_url') . "uploads/articles/article_images/{$value['filename']}[/img]";
        $bbcode_thumb = "[img-thumb]{$value['filename']}[/img-thumb]";
        
        // for old uploads where no thumbnail was made
        $show_thumb = '';
        $main_image = $this->core->config('website_url') . 'uploads/articles/article_images/'.$value['filename'];
        if (file_exists($this->core->config('path') . 'uploads/articles/article_images/thumbs/'.$value['filename']))
        {
			$main_image = $this->core->config('website_url') . 'uploads/articles/article_images/thumbs/'.$value['filename'];
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
            $bbcode = "[img]" . $this->core->config('website_url') . "uploads/articles/article_images/{$key['image_name']}[/img]";
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

	function delete_article($article)
	{
		global $db;

		$db->sqlquery("DELETE FROM `articles` WHERE `article_id` = ?", array($article['article_id']));
		$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($article['article_id']));
		$db->sqlquery("DELETE FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']));
		$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($article['article_id']));
		$db->sqlquery("DELETE FROM `article_history` WHERE `article_id` = ?", array($article['article_id']));
    
		$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` IN ('article_admin_queue', 'article_correction', 'article_submission_queue', 'submitted_article')  AND `completed` = 0", array(core::$date, $article['article_id']));
		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `data` = ?, `type` = ?, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $article['article_id'], 'deleted_article', core::$date, core::$date));

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
        $tagline_image = "<div class=\"test\" id=\"{$article['tagline_image']}\"><img src=\"" . $this->core->config('website_url') . "uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
        Full Image Url: <a class=\"tagline-image\" href=\"" . $this->core->config('website_url') . "uploads/articles/tagline_images/{$article['tagline_image']}\" target=\"_blank\">Click Me</a><br /><button type=\"button\" class=\"insert_tagline_image\">Insert into editor</button></div>";
      }
      if ($article['gallery_tagline'] > 0 && !empty($article['gallery_tagline_filename']))
      {
        $tagline_image = "<div class=\"test\" id=\"{$article['gallery_tagline']}\"><img src=\"" . $this->core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
        Full Image Url: <a class=\"tagline-image\" href=\"" . $this->core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" target=\"_blank\">Click Me</a><br /><button type=\"button\" class=\"insert_tagline_image\">Insert into editor</button></div>";

      }
    }

    if (isset(message_map::$error) && message_map::$error == 1)
    {
      if ($_GET['temp_tagline'] == 1)
      {
        if (isset($_SESSION['uploads_tagline']))
        {
          $file = $this->core->config('path') . 'uploads/articles/tagline_images/temp/' . $_SESSION['uploads_tagline']['image_name'];

          if (file_exists($file))
          {
            $tagline_image = "<div class=\"test\" id=\"{$_SESSION['uploads_tagline']['image_name']}\"><img src=\"".$this->core->config('website_url')."uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" class='imgList'><br />
            <input type=\"hidden\" name=\"image_name\" value=\"{$_SESSION['uploads_tagline']['image_name']}\" />
            <a href=\"#\" id=\"{$_SESSION['uploads_tagline']['image_name']}\" class=\"trash_tagline\">Delete Image</a></div>";
          }
        }

        if (isset($_SESSION['gallery_tagline_rand']) && $_SESSION['gallery_tagline_rand'] = $_SESSION['image_rand'])
        {
          $tagline_image = "<div class=\"test\" id=\"{$_SESSION['gallery_tagline_filename']}\"><img src=\"".$this->core->config('website_url')."uploads/tagline_gallery/{$_SESSION['gallery_tagline_filename']}\" class='imgList'><br />
          <input type=\"hidden\" name=\"image_name\" value=\"{$_SESSION['gallery_tagline_filename']}\" /></div>";
        }
      }
    }

    return $tagline_image;
  }

	// this function will check over everything necessary for an article to be correctly done
	public function check_article_inputs($return_page)
	{
		global $core;

		// if this is set to 1, we've come across an issue, so redirect
		$redirect = 0;

		$temp_tagline = 0;
		if ( (!empty($_SESSION['uploads_tagline']['image_name']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand']) || (!empty($_SESSION['gallery_tagline_rand']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand']))
		{
			$temp_tagline = 1;
		}

		if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
		{
			$check_article = $this->database->run("SELECT `tagline_image`, `gallery_tagline` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();
		}

		$title = strip_tags($_POST['title']);
		$tagline = trim($_POST['tagline']);
		$text = trim($_POST['text']);
		$categories = $_POST['categories'];

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
		$empty_check = core::mempty(compact('title', 'tagline', 'text', 'categories'));
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

		else if (strlen($tagline) > $core->config('tagline-max-length'))
		{
			$redirect = 1;

			$_SESSION['message'] = 'taglinetoolong';
			$_SESSION['message_extra'] = $core->config('tagline-max-length');
		}

		else if (strlen($title) < 10)
		{
			$redirect = 1;

			$_SESSION['message'] = 'shorttitle';
		}

		else if (isset($_POST['show_block']) && $core->config('total_featured') == $core->config('editor_picks_limit'))
		{
			$redirect = 1;
			
			$_SESSION['message'] = 'editor_picks_full';
			$_SESSION['message_extra'] = $core->config('editor_picks_limit');
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

	// subscribe to an article, or update subscription and generate any missing secret keys
	function subscribe($article_id, $emails = NULL)
	{
		if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
		{
			$sub_info = $this->database->run("SELECT `user_id`, `article_id`, `secret_key` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id))->fetch();
			// there's no sub, so make one now
			if (!$sub_info)
			{
				// have we been given an email option, if so use it
				if ($emails == NULL)
				{
					// find how they like to normally subscribe
					$get_email_type = $this->database->run("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
					
					$sql_emails = $get_email_type['auto_subscribe_email'];
				}
				else
				{
					$sql_emails = (int) $emails;
				}
        
				// for unsubscribe link in emails
				$secret_key = core::random_id(15);

				$this->database->run("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?, `send_email` = ?, `secret_key` = ?", array($_SESSION['user_id'], $article_id, $sql_emails, $sql_emails, $secret_key));
			}
			else
			{
				// for unsubscribe link in emails
				if (empty($sub_info['secret_key']))
				{
					$secret_key = core::random_id(15);
				}
				else
				{
					$secret_key = $sub_info['secret_key'];
				}
				
				// check over their email options on this new subscription
				if ($emails == NULL)
				{
					// find how they like to normally subscribe
					$get_email_type = $this->database->run("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
					
					$sql_emails = $get_email_type['auto_subscribe_email'];
				}
				else
				{
					$sql_emails = (int) $emails;
				}
				$this->database->run("UPDATE `articles_subscriptions` SET `secret_key` = ?, `emails` = ?, `send_email` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $sql_emails, $sql_emails, $_SESSION['user_id'], $article_id));
			}
		}
	}

	function unsubscribe($article_id)
	{
		if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
		{
			$check_exists = $this->database->run("SELECT `article_id` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id))->fetchOne();
			if ($check_exists)
			{
				$this->database->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
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
			$date = $core->human_date($grab_history['date']);
			$history .= '<li><a href="/profiles/'. $grab_history['user_id'] .'">' . $grab_history['username'] . '</a> '.$view_link.' - ' . $date . '</li>';
		}

		$templating->load('admin_modules/admin_module_articles');
		$templating->block('history', 'admin_modules/admin_module_articles');
		$templating->set('history', $history);
	}
  
	public function get_link($id, $title, $additional = NULL)
	{
		$link = '';
		$nice_title = core::nice_title($title);
		
		if ($this->core->config('pretty_urls') == 1)
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
		return $this->core->config('website_url') . $link;
	}
	
	public function tag_link($name)
	{
		$name = urlencode($name);
		if ($this->core->config('pretty_urls') == 1)
		{
			$link = 'articles/category/'.$name;
		}
		else
		{
			$link = 'index.php?module=articles&amp;view=cat&amp;catid='.$name;
		}
		return $this->core->config('website_url') . $link;
	}
	
	public function publish_article($options)
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
		$checked = $this->check_article_inputs($options['return_page']);
			
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

				$checked['text'] = $checked['text'] . "\r\n\r\n<em>Thanks to " . $submitted_by_user . ' for letting us know!</em>';
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
			
			$db->sqlquery("UPDATE `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `submitted_unapproved` = 0, `draft` = 0, `locked` = 0, `comment_count` = 0 WHERE `article_id` = ?", array($author_id, $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $editors_pick, core::$date, $_SESSION['user_id'], $_POST['article_id']));
			
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
		
		// move new uploaded tagline image, and save it to the article
		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name'], $checked['text']);
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
				if ($this->core->config('send_emails') == 1)
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

			if ($this->core->config('send_emails') == 1)
			{
				mail($email, $subject, $message, $headers);
			}
		}
		
		include($this->core->config('path') . 'includes/telegram_poster.php');
		include($this->core->config('path') . 'includes/mastodon/post_status.php');

		$article_link = self::get_link($article_id, $checked['slug']);
		
		telegram($checked['title'] . ' ' . $article_link);
		
		post_mastodon_status($checked['title'] . "\n\n" . $article_link);

		if (!isset($_POST['show_block']))
		{
			$redirect = $article_link;
		}
		else
		{
			$redirect = $this->core->config('website_url') . "admin.php?module=featured&view=add&article_id=".$article_id;
		}
		header("Location: " . $redirect);	
	}
	
	public function display_article_list($article_list, $get_categories)
	{
		global $db, $templating, $user;

		foreach ($article_list as $article)
		{
			// make date human readable
			$date = $this->core->human_date($article['date']);

			// get the article row template
			$templating->block('article_row', 'articles');

			if ($user->check_group([1,2,5]))
			{
				$templating->set('edit_link', "<p><a href=\"/admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"> <strong>Edit</strong></a>");
				if ($article['show_in_menu'] == 0)
				{
					if ($this->core->config('total_featured') < 5)
					{
						$editor_pick_expiry = $this->core->human_date($article['date'] + 1209600, 'd/m/y');
						$templating->set('editors_pick_link', " <a class=\"tooltip-top\" title=\"It would expire around now on $editor_pick_expiry\" href=\"".url."index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-heart-empty\"></span> <strong>Make Editors Pick</strong></a></p>");
					}
					else if ($this->core->config('total_featured') == 5)
					{
						$templating->set('editors_pick_link', "");
					}
				}
				else if ($article['show_in_menu'] == 1)
				{
					$templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=removeeditors&amp;article_id={$article['article_id']}\"><strong>Remove Editors Pick</strong></a></p>");
				}
			}

			else
			{
				$templating->set('edit_link', '');
				$templating->set('editors_pick_link', '');
			}

			$templating->set('title', $article['title']);
			$templating->set('user_id', $article['author_id']);

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
				$username = "<a href=\"/profiles/{$article['author_id']}\">" . $article['username'] . '</a>';
			}

			$templating->set('username', $username);

			$templating->set('date', $date);

			$editors_pick = '';
			if ($article['show_in_menu'] == 1)
			{
				$editors_pick = '<li><a href="#">Editors Pick</a></li>';
			}
			$categories_list = $editors_pick;

			foreach ($get_categories as $k => $category_list)
			{
				if ($article['article_id'] == $category_list['article_id'])
				{
					$category_link = $this->tag_link($category_list['category_name']);

					if ($category_list['category_id'] == 60)
					{
						$categories_list .= " <li class=\"ea\"><a href=\"$category_link\">{$category_list['category_name']}</a></li> ";
					}
					else
					{
						$categories_list .= " <li><a href=\"$category_link\">{$category_list['category_name']}</a></li> ";
					}
				}
			}

			$templating->set('categories_list', $categories_list);

			$tagline_image = $this->tagline_image($article);

			$templating->set('top_image', $tagline_image);

			// set last bit to 0 so we don't parse links in the tagline
			$templating->set('text', $article['tagline']);
				
			$templating->set('article_link', $this->get_link($article['article_id'], $article['slug']));
			$templating->set('comment_count', $article['comment_count']);
		}
	}
	
	// placeholder, so we can merge admin comments, plain article comments and the ajax updater into one function
	// article_info = required article details
	// pagination_link = destination link for pagination
	public function display_comments($article_info)
	{
		// get blocked id's
		$blocked_sql = '';
		$blocked_ids = [];
		$blocked_usernames = [];
		if (count($this->user->blocked_users) > 0)
		{
			foreach ($this->user->blocked_users as $username => $blocked_id)
			{
				$blocked_ids[] = $blocked_id[0];
				$blocked_usernames[] = $username;
			}

			$in  = str_repeat('?,', count($blocked_ids) - 1) . '?';
			$blocked_sql = "AND a.`author_id` NOT IN ($in)";
		}
		
		// count how many there is in total
		$sql_count = "SELECT COUNT(`comment_id`) FROM `articles_comments` a WHERE a.`article_id` = ? AND a.`approved` = 1 $blocked_sql";
		$total_comments = $this->database->run($sql_count, array_merge([$article_info['article']['article_id']], $blocked_ids))->fetchOne();
		
		$per_page = 15;
		if (isset($_SESSION['per-page']) && is_numeric($_SESSION['per-page']) && $_SESSION['per-page'] > 0)
		{
			$per_page = $_SESSION['per-page'];
		}

		//lastpage is = total comments / items per page, rounded up.
		if ($total_comments <= 10)
		{
			$lastpage = 1;
		}
		else
		{
			$lastpage = ceil($total_comments/$per_page);
		}

		// paging for pagination
		if (!isset($article_info['page']) || $article_info['page'] == 0)
		{
			$page = 1;
		}

		else if (is_numeric($article_info['page']))
		{
			$page = $article_info['page'];
		}

		if ($page > $lastpage)
		{
			$page = $lastpage;
		}
		
		// sort out the pagination link
		$pagination = $this->core->pagination_link($per_page, $total_comments, $article_info['pagination_link'], $page, '#comments');
		$pagination_head = $this->core->head_pagination($per_page, $total_comments, $article_info['pagination_link'], $page, '#comments');
		
		$this->templating->block('comments_top', 'articles_full');
		$this->templating->set('pagination_head', $pagination_head);
		$this->templating->set('pagination', $pagination);
		
		if (isset($article_info['type']) && $article_info['type'] != 'admin')
		{
			// update their subscriptions if they are reading the last page
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
			{
				$check_sub = $this->database->run("SELECT `send_email` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], (int) $article_info['article']['article_id']))->fetch();
				if ($check_sub)
				{
					if ($_SESSION['email_options'] == 2 && $check_sub['send_email'] == 0)
					{
						// they have read all new comments (or we think they have since they are on the last page)
						if ($page == $lastpage)
						{
							// send them an email on a new comment again
							$this->database->run("UPDATE `articles_subscriptions` SET `send_email` = 1 WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], (int) $article_info['article']['article_id']));
						}
					}
				}
			}

			$subscribe_link = '';
			$close_comments_link = '';
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
			{
				// find out if this user has subscribed to the comments
				if ($_SESSION['user_id'] != 0)
				{
					$book_test = $this->database->run("SELECT `user_id` FROM `articles_subscriptions` WHERE `article_id` = ? AND `user_id` = ?", array((int) $article_info['article']['article_id'], (int) $_SESSION['user_id']))->fetchOne();
					if ($book_test)
					{
						$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"unsubscribe\" data-article-id=\"{$article_info['article']['article_id']}\" href=\"/index.php?module=articles_full&amp;go=unsubscribe&amp;article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Unsubscribe</span></a>";
					}

					else
					{
						$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"subscribe\" data-article-id=\"{$article_info['article']['article_id']}\" href=\"/index.php?module=articles_full&amp;go=subscribe&amp;article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Subscribe</span></a>";
					}
				}

				if ($this->user->check_group([1,2]) == true)
				{
					if ($article_info['article']['comments_open'] == 1)
					{
						$close_comments_link = "<a href=\"/index.php?module=articles_full&go=close_comments&article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Close Comments</a></span>";
					}
					else if ($article_info['comments_open'] == 0)
					{
						$close_comments_link = "<a href=\"/index.php?module=articles_full&go=open_comments&article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Open Comments</a></span>";
					}
				}
			}
			
			$this->templating->set('subscribe_link', $subscribe_link);
			$this->templating->set('close_comments', $close_comments_link);
			
			if ($article_info['article']['comments_open'] == 0)
			{
				$this->templating->block('comments_closed', 'articles_full');
			}
		}
		else
		{
			$this->templating->set('subscribe_link', '');
			$this->templating->set('close_comments', '');		
		}
		
		//
		/* DISPLAY THE COMMENTS */
		//

		if ($total_comments > 0 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			// first grab a list of their bookmarks
			$bookmarks_array = $this->database->run("SELECT `data_id` FROM `user_bookmarks` WHERE `type` = 'comment' AND `parent_id` = ? AND `user_id` = ?", array((int) $article_info['article']['article_id'], (int) $_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN);
		}

		$profile_fields = include dirname ( dirname ( __FILE__ ) ) . '/includes/profile_fields.php';

		$db_grab_fields = '';
		foreach ($profile_fields as $field)
		{
			$db_grab_fields .= "u.`{$field['db_field']}`,";
		}
		
		$params = array_merge([(int) $article_info['article']['article_id']], $blocked_ids, [$this->core->start], [$per_page]);
		
		$comments_get = $this->database->run("SELECT a.author_id, a.guest_username, a.comment_text, a.comment_id, u.pc_info_public, u.distro, a.time_posted, a.last_edited, a.last_edited_time, a.`edit_counter`, u.username, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, $db_grab_fields u.`avatar_uploaded`, u.`avatar_gallery`, u.pc_info_filled, u.game_developer, u.register_date, ul.username as username_edited FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` ul ON ul.user_id = a.last_edited WHERE a.`article_id` = ? AND a.approved = 1 $blocked_sql ORDER BY a.`comment_id` ASC LIMIT ?, ?", $params)->fetch_all();
		
		// make an array of all comment ids and user ids to search for likes (instead of one query per comment for likes) and user groups for badge displaying
		$like_array = [];
		$sql_replacers = [];
		
		foreach ($comments_get as $id_loop)
		{
			$like_array[] = (int) $id_loop['comment_id'];
			$user_ids[] = (int) $id_loop['author_id'];
			$sql_replacers[] = '?';
		}
					
		if (!empty($sql_replacers))
		{
			$to_replace = implode(',', $sql_replacers);
						
			// Total number of likes for the comments
			$get_likes = $this->database->run("SELECT data_id, COUNT(*) FROM `likes` WHERE `data_id` IN ( $to_replace ) AND `type` = 'comment' GROUP BY data_id", $like_array)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
						
			// this users likes
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
			{
				$replace = [$_SESSION['user_id']];
				foreach ($like_array as $comment_id)
				{
					$replace[] = $comment_id;
				}

				$get_user_likes = $this->database->run("SELECT `data_id` FROM `likes` WHERE `user_id` = ? AND `data_id` IN ( $to_replace ) AND `type` = 'comment'", $replace)->fetch_all(PDO::FETCH_COLUMN);
			}
						
			// get a list of each users user groups, so we can display their badges
			$comment_user_groups = $this->user->post_group_list($user_ids);
		}
		
		// check over their permissions now
		$can_delete = 0;
		if ($this->user->can('mod_delete_comments'))
		{
			$can_delete = 1;
		}
		$can_edit = 0;
		if ($this->user->can('mod_edit_comments'))
		{
			$can_edit = 1;
		}
		
		foreach ($comments_get as $comments)
		{
			// remove blocked users quotes
			if (count($blocked_usernames) > 0)
			{
				foreach($blocked_usernames as $username)
				{
					
					$capture_quotes = preg_split('~(\[/?quote[^]]*\])~', $comments['comment_text'], NULL, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
					
					// need to count the amount of [quote=?
					// each time we hit [/quote] take one away from counter
					// snip the comment between the start and the final index number?
					foreach ($capture_quotes as $index => $quote)
					{
						if(!isset($start) && $quote == "[quote={$username}]")
						{
							$start = $index;
							$opens = 1;
						}
						else if(isset($start))
						{
							if(strpos($quote,'[quote=') !== false || strpos($quote,'[quote]') !== false)
							{
								++$opens;
							}	
							else if(strpos($quote,'[/quote]')!==false)
							{
								--$opens;
								if($opens == 0)
								{
									$capture_quotes = array_diff_key($capture_quotes,array_flip(range($start,$index)));
									$comments['comment_text'] = trim(implode($capture_quotes));
									unset($start);
								}
							}
						}
					}
				}
			}
			
			$comment_date = $this->core->human_date($comments['time_posted']);

			if ($comments['author_id'] == 0 || empty($comments['username']))
			{
				if (empty($comments['username']))
				{
					$username = 'Guest';
				}
				if (!empty($comments['guest_username']))
				{
					if ($this->user->check_group([1,2]) == true)
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
			$comment_avatar = $this->user->sort_avatar($comments['author_id']);
						
			$into_username = '';
			if (!empty($comments['distro']) && $comments['distro'] != 'Not Listed')
			{
				$into_username .= '<img title="' . $comments['distro'] . '" class="distro tooltip-top"  alt="" src="' . $this->core->config('website_url') . 'templates/'.$this->core->config('template').'/images/distros/' . $comments['distro'] . '.svg" />';
			}
						
			$pc_info = '';
			if (isset($comments['pc_info_public']) && $comments['pc_info_public'] == 1)
			{
				if ($comments['pc_info_filled'] == 1)
				{
					$pc_info = '<a class="computer_deets" data-fancybox data-type="ajax" href="javascript:;" data-src="'.$this->core->config('website_url').'includes/ajax/call_profile.php?user_id='.$comments['author_id'].'">View PC info</a>';
				}
			}

			$this->templating->block('article_comments', 'articles_full');
			$this->templating->set('user_id', $comments['author_id']);
			$this->templating->set('username', $into_username . $username);
			$this->templating->set('comment_avatar', $comment_avatar);
			$this->templating->set('date', $comment_date);
			$this->templating->set('tzdate', date('c',$comments['time_posted']) );
			$this->templating->set('user_info_extra', $pc_info);

			$cake_bit = '';
			if ($username != 'Guest')
			{
				$cake_bit = $this->user->cake_day($comments['register_date'], $comments['username']);
			}
			$this->templating->set('cake_icon', $cake_bit);

			$last_edited = '';
			$edit_counter = '';
			if ($comments['last_edited'] != 0)
			{
				if ($comments['edit_counter'] > 1)
				{
					$edit_counter = '. Edited ' . $comments['edit_counter'] . ' times.';
				}
							
				$last_edited = "\r\n\r\n\r\n[i]Last edited by " . $comments['username_edited'] . ' at ' . $this->core->human_date($comments['last_edited_time']) . $edit_counter . '[/i]';
			}

			$this->templating->set('article_id', $article_info['article']['article_id']);
			$this->templating->set('comment_id', $comments['comment_id']);
 						
			$total_likes = 0;
			if (isset($get_likes[$comments['comment_id']][0]))
			{
				$total_likes = $get_likes[$comments['comment_id']][0];
			}

			$this->templating->set('total_likes', $total_likes);

			$who_likes_link = '';
			if ($total_likes > 0)
			{
				$who_likes_link = ', <a class="who_likes" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?comment_id='.$comments['comment_id'].'">Who?</a>';
			}
			$this->templating->set('who_likes_link', $who_likes_link);
			
			$likes_hidden = '';
			if ($total_likes == 0)
			{
				$likes_hidden = 'likes_hidden';
			}
			$this->templating->set('hidden_likes_class', $likes_hidden);

			$logged_in_options = '';
			$bookmark_comment = '';
			$report_link = '';
			$comment_edit_link = '';
			$like_button = '';
			$comment_delete_link = '';
			$link_to_comment = '';
			$permalink = $this->get_link($article_info['article']['article_id'], $article_info['article']['slug'], 'comment_id=' . $comments['comment_id']);
			if (isset($article_info['type']) && $article_info['type'] != 'admin')
			{
				$link_to_comment = '<li><a class="post_link tooltip-top" data-fancybox data-type="ajax" href="'.$permalink.'" data-src="/includes/ajax/call_post_link.php?post_id=' . $comments['comment_id'] . '&type=comment" title="Link to this comment"><span class="icon link">Link</span></a></li>';
			}
			$this->templating->set('link_to_comment', $link_to_comment);
			
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
			{
				$logged_in_options = $this->templating->block_store('logged_in_options', 'articles_full');
				
				if (isset($article_info['type']) && $article_info['type'] != 'admin')
				{
					// sort bookmark icon out
					if (in_array($comments['comment_id'], $bookmarks_array))
					{
						$bookmark_comment = '<li><a href="#" class="bookmark-content tooltip-top bookmark-saved" data-page="normal" data-type="comment" data-id="'.$comments['comment_id'].'" data-parent-id="'.$article_info['article']['article_id'].'" data-method="remove" title="Remove Bookmark"><span class="icon bookmark"></span></a></li>';
					}
					else
					{
						$bookmark_comment = '<li><a href="#" class="bookmark-content tooltip-top" data-page="normal" data-type="comment" data-id="'.$comments['comment_id'].'" data-parent-id="'.$article_info['article']['article_id'].'" data-method="add" title="Bookmark"><span class="icon bookmark"></span></a></li>';
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

					// don't let them like their own post
					if ($comments['author_id'] != $_SESSION['user_id'])
					{
						$like_button = '<li class="like-button" style="display:none !important"><a class="likebutton tooltip-top" data-type="comment" data-id="'.$comments['comment_id'].'" data-article-id="'.$article_info['article']['article_id'].'" data-author-id="'.$comments['author_id'].'" title="Like"><span class="icon '.$like_class.'">'.$like_text.'</span></a></li>';
					}
					
					$report_link = "<li><a class=\"tooltip-top\" href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&amp;go=report_comment&amp;article_id={$article_info['article']['article_id']}&amp;comment_id={$comments['comment_id']}\" title=\"Report\"><span class=\"icon flag\">Flag</span></a></li>";
					
					if ($_SESSION['user_id'] == $comments['author_id'] || $can_edit == 1)
					{
						$comment_edit_link = "<li><a class=\"tooltip-top\" title=\"Edit\" href=\"" . $this->core->config('website_url') . "index.php?module=edit_comment&amp;view=Edit&amp;comment_id={$comments['comment_id']}\"><span class=\"icon edit\">Edit</span></a></li>";
					}
					
					if ($can_delete == 1)
					{
						$comment_delete_link = "<li><a class=\"tooltip-top\" title=\"Delete\" href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\"><span class=\"icon delete\"></span></a></li>";
					}
				}
				
				$logged_in_options = $this->templating->store_replace($logged_in_options, array('post_id' => $comments['comment_id'], 'like_button' => $like_button));
			}
			$this->templating->set('logged_in_options', $logged_in_options);
			$this->templating->set('bookmark', $bookmark_comment);
			$this->templating->set('edit', $comment_edit_link);
			$this->templating->set('delete', $comment_delete_link);
			$this->templating->set('report_link', $report_link);

			// if we have some user groups for that user
			if (array_key_exists($comments['author_id'], $comment_user_groups))
			{
				$comments['user_groups'] = $comment_user_groups[$comments['author_id']];
				$badges = user::user_badges($comments, 1);
				$this->templating->set('badges', implode(' ', $badges));
			}
			// otherwise guest account or their account was removed, as we didn't get any groups for it
			else
			{
				$this->templating->set('badges', '');
			}
						
			$profile_fields_output = user::user_profile_icons($profile_fields, $comments);

			$this->templating->set('profile_fields', $profile_fields_output);

			// do this last, to help stop templating tags getting parsed in user text
			$this->templating->set('text', $this->bbcode->parse_bbcode($comments['comment_text'] . $last_edited, 0));
		}

		$this->templating->block('bottom', 'articles_full');
		$this->templating->set('pagination', $pagination);
		
		if (isset($article_info['type']) && $article_info['type'] != 'admin')
		{
			$this->templating->block('patreon_comments', 'articles_full');
		}
	}
	
	public static function display_category_picker($categorys_ids = NULL)
	{
		global $templating, $db;
		
		// show the category selection box
		$templating->block('articles_top', 'articles');
		$options = '';
		$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
		while ($get_cats = $db->fetch())
		{
			$selected = '';
			if (isset($categorys_ids) && in_array($get_cats['category_id'], $categorys_ids))
			{
				$selected = 'selected';
			}
			if (isset($_GET['catid']) && !is_array($_GET['catid']) && $_GET['catid'] == $get_cats['category_name'])
			{
				$selected = 'selected';
			}
			$options .= '<option value="'.$get_cats['category_id'].'" ' . $selected . '>'.$get_cats['category_name'].'</option>';
		}
		$templating->set('options', $options);
		
		$all_check = '';
		$any_check = 'checked';
		if (isset($_GET['type']))
		{
			if ($_GET['type'] == 'any')
			{
				$any_check = 'checked';
				$all_check = '';
			}
			if ($_GET['type'] == 'all')
			{
				$all_check = 'checked';
				$any_check = '';
			}
		}
		$templating->set('any_check', $any_check);
		$templating->set('all_check', $all_check);
	}

	// per page = how many rows to show per page
	// total = total number of rows
	// targetpage = the page to append the pagination target page onto
	// extra = anything extra to add like "#comments" to go to the comments
	function article_pagination($page, $lastpage, $targetpage)
	{
		//previous page is page - 1
		$prev = $page - 1;

		//next page is page + 1
		$next = $page + 1;

		// sort out the pagination links
		$article_pagination = "";
		if($lastpage > 1)
		{
			$article_pagination .= "<div class=\"pagination group\"><ul class=\"pagination fleft\">";

			//previous button
			if ($page > 1)
			{
				$article_pagination.= "<li class=\"previouspage\"><a href=\"{$targetpage}article_page=$prev\">&laquo;</a></li>";
			}

			else
			{
				$article_pagination.= "<li><span>&laquo;</span></li>";
			}

			$article_pagination .= "<li><a href=\"#\">$page</a></li>";

			$article_pagination .= "<li><span>/</span></li>";

			// sort out last page link, no link if on last page
			if ($page == $lastpage)
			{
				$article_pagination .= "<li><span>{$lastpage}</span></li>";
			}

			else
			{
				$article_pagination.= "<li><a href=\"{$targetpage}article_page={$lastpage}\">{$lastpage}</a></li>";
			}

			// next button
			if ($page < $lastpage)
			{
				$article_pagination .= "<li><a href=\"{$targetpage}article_page=$next\">&raquo;</a></li>";
			}

			else
			{
				$article_pagination .= "<li><span>&raquo;</span></li>";
			}

			$article_pagination .= "</ul>";


			$article_pagination .= "<form name=\"form2\" class=\"form-inline\">
			&nbsp; Go to: <select class=\"dropdown\" style=\"width: auto;\" name=\"jumpmenu\" onchange=\"window.open(this.options[this.selectedIndex].value, '_self')\">";

			for ($i = 1; $i <= $lastpage; $i++)
			{
				$selected = '';
				if ($i == $page)
				{
					$selected = 'selected';
				}
				$article_pagination .= "<option value=\"{$targetpage}article_page={$i}\" $selected>$i</option>";
			}

			$article_pagination .= '</select></form></div>';
		}

		return $article_pagination;
	}
	
	// give a user a notification if their name was quoted in a comment
	function quote_notification($text, $username, $author_id, $article_id, $comment_id)
	{
		/* gather a list of people quoted and let them know
		do this first, so we can check if they have been notified already and not send another */
		$pattern = '/\[quote\=(.+?)\](.+?)\[\/quote\]/is';
		preg_match_all($pattern, $text, $matches);

		// we only want to notify them once on being quoted, so make sure each quote has a unique name
		$quoted_usernames = array_values(array_unique($matches[1]));
		
		$new_notification_id = [];

		if (!empty($quoted_usernames))
		{
			foreach($quoted_usernames as $match)
			{
				// don't notify the person making this post, if a quote has their own name in it
				if ($match != $username)
				{
					$quoted_user = $this->database->run("SELECT `user_id` FROM `users` WHERE `username` = ?", array($match))->fetchOne();
					if ($quoted_user)
					{
						$this->database->run("INSERT INTO `user_notifications` SET `date` = ?, `seen` = 0, `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `is_quote` = 1", array(core::$date, $quoted_user, $author_id, $article_id, $comment_id));
						$new_notification_id[$quoted_user] = $this->database->new_id();
					}
				}
			}
		}
		
		$new_notification_id['quoted_usernames'] = $quoted_usernames;
		
		return $new_notification_id;
	}
}
?>
