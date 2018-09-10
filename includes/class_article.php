<?php
use claviska\SimpleImage;

class article
{
	private $dbl;
	private $core;
	private $user;
	private $templating;
	private $bbcode;
	
	function __construct($dbl, $core, $user, $templating, $bbcode)
	{
		$this->dbl = $dbl;
		$this->core = $core;
		$this->user = $user;
		$this->templating = $templating;
		$this->bbcode = $bbcode;
	}
	
	// clear out any left overs, since there's no error we don't need them, stop errors with them
	// TO DO: Use an array of $_SESSION['article']['blah'] and just remove that, would much much cleaer
	function reset_sessions()
	{
		$_SESSION['image_rand'] = rand();
		$_SESSION['article_timer'] = core::$date;
		unset($_SESSION['uploads']);
		unset($_SESSION['uploads_tagline']);
		unset($_SESSION['gallery_tagline_id']);
		unset($_SESSION['gallery_tagline_rand']);
		unset($_SESSION['gallery_tagline_filename']);
		// clear the conflict checker, since this is a fresh load
		if (isset($_SESSION['conflict_checked']))
		{
			unset($_SESSION['conflict_checked']);
		}
		
		unset($_SESSION['atitle']);
		unset($_SESSION['aslug']);
		unset($_SESSION['atagline']);
		unset($_SESSION['atext']);
		unset($_SESSION['acategories']);
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

	/* Find tags for an article or multiple articles
	OPTIONS
	article_ids - multiple ids to pull when showing a list of articles
	limit - How many to limit
	*/
	public function find_article_tags($options)
	{
		// this is required to properly count up the rank for the tags
		$this->dbl->run("SET @rank=null, @val=null;");

		// setting a limit on the amount of tags per article
		if (isset($options['limit']))
		{
			$category_tag_sql = "SELECT * FROM 
			(
				SELECT a.article_id, a.`category_name`, a.`category_id`, 
				@rank1 := IF( @val = a.article_id, @rank1 +1, 1 ) AS rank1, 
				@val := a.article_id 
				FROM 
				(
					SELECT r.article_id, c.`category_name`, c.`category_id`
					FROM  `article_category_reference` r
					INNER JOIN  `articles_categorys` c ON c.category_id = r.category_id
					WHERE r.article_id IN ("  . $options['article_ids'] . ")
					ORDER BY r.`article_id`,CASE WHEN (c.`show_first` = 1) THEN 0 ELSE 1 END ASC, c.category_name ASC
				) AS a
			)as Z
			WHERE Z.rank1 < " . $options['limit'];
		}
		// no limit, display all tags
		else
		{
			$category_tag_sql = "SELECT r.article_id, c.`category_name`, c.`category_id`
			FROM  `article_category_reference` r
			INNER JOIN  `articles_categorys` c ON c.category_id = r.category_id
			WHERE r.article_id IN ("  . $options['article_ids'] . ")
			ORDER BY r.`article_id`,CASE WHEN (c.`show_first` = 1) THEN 0 ELSE 1 END ASC, c.category_name ASC";		
		}

		$get_categories = $this->dbl->run($category_tag_sql)->fetch_all(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		return $get_categories;
	}

	public function display_article_tags($categories_list)
	{
		$categories_output = '';
		if (!empty($categories_list))
		{
			foreach ($categories_list as $tag)
			{
				$category_link = $this->tag_link($tag['category_name']);

				if ($tag['category_id'] == 60)
				{
					$categories_output .= " <li class=\"ea\"><a href=\"$category_link\">{$tag['category_name']}</a></li> ";
				}
				else if ($tag['category_name'] == 'Steam Play')
				{
					$categories_output .= " <li class=\"steamplay\"><a href=\"$category_link\">{$tag['category_name']}</a></li> ";
				}
				else
				{
					$categories_output .= " <li><a href=\"$category_link\">{$tag['category_name']}</a></li> ";
				}
			}
		}
		return $categories_output;
	}

	// if they have set a tagline image from the gallery, remove any existing images
	public function gallery_tagline($data = NULL)
	{
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

				$this->dbl->run("UPDATE `articles` SET `tagline_image` = '', `gallery_tagline` = {$_SESSION['gallery_tagline_id']} WHERE `article_id` = ?", array($data['article_id']));
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
		$previously_uploaded['output'] = '';
		$previously_uploaded['hidden'] = '';
		$article_images = NULL;
		if ($article_id != NULL)
		{
			// add in uploaded images from database
			$article_images = $this->dbl->run("SELECT `filename`,`id`,`filetype` FROM `article_images` WHERE `article_id` = ? ORDER BY `id` ASC", array($article_id))->fetch_all();
		}
		else
		{
			if (isset($_SESSION['uploads']['article_media']))
			{
				$image_ids = [];
				foreach ($_SESSION['uploads']['article_media'] as $id)
				{
					$image_ids[] = $id;
				}
				unset($_SESSION['uploads']['article_media']);
				$in  = str_repeat('?,', count($image_ids) - 1) . '?';
				$article_images = $this->dbl->run("SELECT `filename`,`id`,`filetype` FROM `article_images` WHERE `id` IN ($in) ORDER BY `id` ASC", $image_ids)->fetch_all();
			}
		}
		if ($article_images)
		{
			foreach($article_images as $value)
			{
				if ($article_id == NULL)
				{
					$previously_uploaded['hidden'] .= '<input class="uploads-'.$value['id'].'" type="hidden" name="uploads[]" value="'.$value['id'].'" />';
				}
				$main_url = $this->core->config('website_url') . 'uploads/articles/article_media/' . $value['filename'];
				$main_path = APP_ROOT . '/uploads/articles/article_media/' . $value['filename'];
				$gif_static_button = '';
				$thumbnail_button = '';
				$data_type = '';

				if ($value['filetype'] != 'mp4' && $value['filetype'] != 'webm' && $value['filetype'] != 'mp3' && $value['filetype'] != 'ogg')
				{
					$thumb_url = $this->core->config('website_url') . 'uploads/articles/article_media/thumbs/' . $value['filename'];
					$thumb_path = APP_ROOT . '/uploads/articles/article_media/thumbs/' . $value['filename'];
		
					if ($value['filetype'] == 'gif')
					{
						$static_filename = str_replace('.gif', '_static.jpg', $value['filename']);
						$static_url = $this->core->config('website_url') . 'uploads/articles/article_media/' . $static_filename;
						$gif_static_button = '<button data-url-gif="'.$main_url.'" data-url-static="'.$static_url.'" class="add_static_button">Insert Static</button>';
					}
												
					// for old uploads where no thumbnail was made, make one
					if (!file_exists($thumb_path) && file_exists($main_path))
					{
						include_once(APP_ROOT . '/includes/image_class/SimpleImage.php');
							
						$img = new SimpleImage();
						$img->fromFile($main_path)->resize(350, null)->toFile($thumb_path);					
					}
					$thumbnail_button = '<button data-url="'.$thumb_url.'" data-main-url="'.$main_url.'" class="add_thumbnail_button">Insert thumbnail</button>';

					$preview_file = '<img src="' . $thumb_url . '" class="imgList"><br />';
					$data_type = 'image';
				}
				else
				{
					$preview_file = '<video width="100%" src="'.$main_url.'" controls></video>';
					$data_type = 'video';
				}
			
				$previously_uploaded['output'] .= '<div class="box">
				<div class="body group">
				<div id="'.$value['id'].'">'.$preview_file.'
				URL: <input id="img' . $value['id'] . '" type="text" value="' . $main_url . '" /> <button class="btn" data-clipboard-target="#img' . $value['id'] . '">Copy</button> '.$gif_static_button.' <button data-url="'.$main_url.'" data-type="'.$data_type.'" class="add_button">Insert</button> '.$thumbnail_button.' <button id="' . $value['id'] . '" class="trash">Delete Media</button>
				</div>
				</div></div>';
			}
		}
		return $previously_uploaded;
	}

	public function process_categories($article_id)
	{
		if (isset($article_id) && is_numeric($article_id))
		{
			// delete any existing categories that aren't in the final list for publishing
			$current_categories = $this->dbl->run("SELECT `ref_id`, `article_id`, `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article_id))->fetch_all();

			if (!empty($current_categories))
			{
				foreach ($current_categories as $current_category)
				{
					if (!in_array($current_category['category_id'], $_POST['categories']))
					{
						$this->dbl->run("DELETE FROM `article_category_reference` WHERE `ref_id` = ?", array($current_category['ref_id']));
					}
				}
			}
			// get fresh list of categories, and insert any that don't exist
			$current_categories = $this->dbl->run("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article_id))->fetch_all(PDO::FETCH_COLUMN, 0);

			if (isset($_POST['categories']) && !empty($_POST['categories']))
			{
				foreach($_POST['categories'] as $category)
				{
					if (!in_array($category, $current_categories))
					{
						$this->dbl->run("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($article_id, $category));
					}
				}
			}
		}
	}

	function delete_article($article)
	{
		// get some details on the article first
		$article_info = $this->dbl->run("SELECT `title`, `active`, `draft`, `submitted_article` FROM `articles` WHERE `article_id` = ?", array($article['article_id']))->fetch();

		$this->dbl->run("DELETE FROM `articles` WHERE `article_id` = ?", array($article['article_id']));
		$this->dbl->run("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($article['article_id']));
		$this->dbl->run("DELETE FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']));
		$this->dbl->run("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($article['article_id']));
		$this->dbl->run("DELETE FROM `article_history` WHERE `article_id` = ?", array($article['article_id']));

		$additional_text = '';
		if ($article_info['active'] == 1)
		{
			$additional_text = ' This was a live article.';
		}
		if ($article_info['draft'] == 1)
		{
			$additional_text = ' This was a draft article.';
		}
		if ($article_info['submitted_article'] == 1)
		{
			$additional_text = ' This was a submitted article.';
		}
		
		// update any existing notification
		$this->core->update_admin_note(array(
			'type_search' => 'IN', 
			'type' => array('article_admin_queue', 'article_correction', 'article_submission_queue', 'submitted_article'), 
			'data' => $article['article_id']));

		// note who did it
		$this->core->new_admin_note(array('completed' => 1, 'content' => ' deleted an article titled: ' . $article_info['title'] . '.' . $additional_text));

		// if it wasn't posted by the bot, as the bot uses static images, can remove this when the bot uses gallery images
		if ($article['author_id'] != 1844)
		{
			if (isset($article['tagline_image']))
			{
				$tagline_image = trim($article['tagline_image']); // ensure we don't pick up random spaces
				if (!empty($tagline_image) && $tagline_image != '')
				{
					$main = $_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image'];
					$thumb = $_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image'];
					if (file_exists($main))
					{
						unlink($main);
					}
					if (file_exists($thumb))
					{
						unlink($thumb);
					}
				}
			}
		}

		// find any uploaded images, and remove them
		$res = $this->dbl->run("SELECT * FROM `article_images` WHERE `article_id` = ?", array($article['article_id']))->fetch_all();
		foreach ($res as $image_search)
		{
			$main = $_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_media/' . $image_search['filename'];
			$thumb = $_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_media/thumbs/' . $image_search['filename'];
			if (file_exists($main))
			{
				unlink($main);
			}
			if (file_exists($thumb))
			{
				unlink($thumb);
			}
		}

		$this->dbl->run("DELETE FROM `article_images` WHERE `article_id` = ?", array($article['article_id']));
	}

	function display_tagline_image($article = NULL)
	{
		$tagline_image = '';

		// if it's an existing article, see if there's a tagline image to grab
		if ($article != NULL)
		{
			if (!empty($article['tagline_image']))
			{
				$tagline_image = "<div class=\"test\" id=\"{$article['tagline_image']}\"><img src=\"" . $this->core->config('website_url') . "uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\"><br />Full Image Url: <a class=\"tagline-image\" href=\"" . $this->core->config('website_url') . "uploads/articles/tagline_images/{$article['tagline_image']}\" target=\"_blank\">Click Me</a><br /><button type=\"button\" class=\"insert_tagline_image\">Insert into editor</button></div>";
			}
			if ($article['gallery_tagline'] > 0 && !empty($article['gallery_tagline_filename']))
			{
				$tagline_image = "<div class=\"test\" id=\"{$article['gallery_tagline']}\"><img src=\"" . $this->core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" alt=\"[articleimage]\" class=\"imgList\"><br />Full Image Url: <a class=\"tagline-image\" href=\"" . $this->core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" target=\"_blank\">Click Me</a><br /><button type=\"button\" class=\"insert_tagline_image\">Insert into editor</button></div>";
			}
		}

    if (isset(message_map::$error) && message_map::$error == 1 || message_map::$error == 2)
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

	// this function will check over everything necessary for an article to be correctly done, including an article crossover checker
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
			$check_article = $this->dbl->run("SELECT `tagline_image`, `gallery_tagline` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();
		}

		$title = strip_tags($_POST['title']);
		$title = mb_convert_encoding($title, 'UTF-8');
		$tagline = trim($_POST['tagline']);
		$text = trim($_POST['text']);
		$categories = [];
		if (!empty($_POST['categories']))
		{
			$categories = $_POST['categories'];
		}

		// check its set, if not hard-set it based on the article title
		if (isset($_POST['slug']) && !empty($_POST['slug']))
		{
			$slug = core::nice_title($_POST['slug']);
		}
		else
		{
			$slug = core::nice_title($_POST['title']);
		}
		
		// check for newer articles, to prevent crossover
		if (isset($_SESSION['conflict_checked']) && is_array($_SESSION['conflict_checked']))
		{
			$in  = str_repeat('?,', count($_SESSION['conflict_checked']) - 1) . '?';
			$article_res = $this->dbl->run("SELECT `article_id`, `title` FROM `articles` WHERE `date` > ? AND `article_id` NOT IN ($in)", array_merge([$_SESSION['article_timer']], $_SESSION['conflict_checked']))->fetch_all();
		}
		else
		{
			if (!isset($_SESSION['article_timer']))
			{
				error_log('Article timer not set: ' . $_SERVER['REQUEST_URI']);
			}
			$article_res = $this->dbl->run("SELECT `article_id`, `title` FROM `articles` WHERE `date` > ?", array($_SESSION['article_timer']))->fetch_all();
		}
		if ($article_res)
		{
			$article_list = '<form><ul>';
			foreach($article_res as $res)
			{
				$article_link = $this->get_link($res['article_id'], $res['title']);
				$article_list .= '<li><a href="'.$article_link.'" target="_blank">'.$res['title'].'</a><input type="hidden" name="article_ids[]" value="'.$res['article_id'].'" /></li>';
			}
			
			$article_list .= '</ul><button type="button" class="conflict_confirmed">Confirmed</button></form>';
			
			$redirect = 1;
			$_SESSION['message'] = 'article_conflicts';
			$_SESSION['message_extra'] = $article_list;
		}
		else
		{
			// make sure its not empty
			$empty_check = core::mempty(compact('title', 'tagline', 'text', 'categories'));
			if ($empty_check !== true)
			{
				$redirect = 1;

				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = $empty_check;
			}
			// prevent ckeditor just giving us a blank article (this is the default for an empty editor)
			// this way if there's an issue and it gets wiped, we still don't get a blank article published
			else if ($text == '<p>&nbsp;</p>')
			{
				$redirect = 1;

				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'text';		
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
		}

		if ($redirect == 1)
		{
			$_SESSION['atitle'] = $title;
			$_SESSION['aslug'] = $slug;
			$_SESSION['atagline'] = $tagline;
			$_SESSION['atext'] = $text;
			$_SESSION['acategories'] = $categories;

			if (isset($_POST['uploads']))
			{
				$_SESSION['uploads']['article_media'] = $_POST['uploads'];
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
			$sub_info = $this->dbl->run("SELECT `user_id`, `article_id`, `secret_key` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id))->fetch();
			// there's no sub, so make one now
			if (!$sub_info)
			{
				// have we been given an email option, if so use it
				if ($emails === NULL)
				{
					// find how they like to normally subscribe
					$get_email_type = $this->dbl->run("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
					
					$sql_emails = $get_email_type['auto_subscribe_email'];
				}
				else
				{
					$sql_emails = (int) $emails;
				}
        
				// for unsubscribe link in emails
				$secret_key = core::random_id(15);

				$this->dbl->run("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?, `send_email` = ?, `secret_key` = ?", array($_SESSION['user_id'], $article_id, $sql_emails, $sql_emails, $secret_key));
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
					$get_email_type = $this->dbl->run("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
					
					$sql_emails = $get_email_type['auto_subscribe_email'];
				}
				else
				{
					$sql_emails = (int) $emails;
				}
				$this->dbl->run("UPDATE `articles_subscriptions` SET `secret_key` = ?, `emails` = ?, `send_email` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $sql_emails, $sql_emails, $_SESSION['user_id'], $article_id));
			}
		}
	}

	function unsubscribe($article_id)
	{
		if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
		{
			$this->dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
		}
	}

	function article_history($article_id)
	{
		global $templating, $core;

		$res = $this->dbl->run("SELECT u.`username`, u.`user_id`, a.`date`, a.id, a.text FROM `users` u INNER JOIN `article_history` a ON a.user_id = u.user_id WHERE a.article_id = ? ORDER BY a.id DESC LIMIT 10", array($article_id))->fetch_all();
		$history = '';
		foreach ($res as $grab_history)
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
		
		$link = 'articles/'.$nice_title.'.'.$id;
			
		if ($additional != NULL)
		{
			$link = $link . '/' . $additional;
		}

		return $this->core->config('website_url') . $link;
	}
	
	public function tag_link($name)
	{
		$name = urlencode($name);
		$link = 'articles/category/'.$name;
		return $this->core->config('website_url') . $link;
	}
	
	public function publish_article($options)
	{
		global $core;

		if (isset($_POST['article_id']))
		{
			// check it hasn't been accepted already
			$check_article = $this->dbl->run("SELECT a.`active`, a.`author_id`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON u.`user_id` = a.`author_id` WHERE a.`article_id` = ?", array($_POST['article_id']))->fetch();
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
			
		$gallery_tagline_sql = $this->gallery_tagline($checked);
			
		// an existing article (submitted/review/draft)
		if (isset($_POST['article_id']) && !empty($_POST['article_id']))
		{
			// this is for user submissions, if we are submitting it as ourselves, to auto thank them for the submission
			if (isset($_POST['submit_as_self']))
			{
				$author_id = $_SESSION['user_id'];
			}
			else
			{
				$author_id = $check_article['author_id'];
			}
			
			$this->dbl->run("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($_POST['article_id']));
			$this->dbl->run("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));
				
			if (isset($options['type']) && $options['type'] != 'draft')
			{
				$this->core->update_admin_note(array('type' => $options['clear_notification_type'], 'data' => $_POST['article_id']));
			}
				
			$this->dbl->run("UPDATE `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `submitted_unapproved` = 0, `draft` = 0, `locked` = 0, `comment_count` = 0, `guest_email` = '', `guest_ip` = '' WHERE `article_id` = ?", array($author_id, $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $editors_pick, core::$date, $_SESSION['user_id'], $_POST['article_id']));
				
			// since they are approving and not neccisarily editing, check if the text matches, if it doesnt they have edited it
			if ($_SESSION['original_text'] != $checked['text'])
			{
				$this->dbl->run("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));
			}

			$article_id = $_POST['article_id'];
				
			if ($_SESSION['user_id'] == $author_id)
			{
				if (isset($_POST['subscribe']))
				{
					$subscribe_them = 1;
				}
			}
		}
		// otherwise make the new article
		else
		{
			$this->dbl->run("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0 $gallery_tagline_sql", array($_SESSION['user_id'], $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $editors_pick, core::$date));
				
			$article_id = $this->dbl->new_id();
				
			if (isset($_POST['subscribe']))
			{
				$subscribe_them = 1;
			}
		}

		if (isset($subscribe_them) && $subscribe_them == 1)
		{
			// for unsubscribe link in emails
			$secret_key = core::random_id(15);

			$this->dbl->run("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?, `send_email` = ?, `secret_key` = ?", array($_SESSION['user_id'], $article_id, 1, 1, $secret_key));
		}
			
		// attach uploaded media to this article id
		if (isset($_POST['uploads']))
		{
			foreach($_POST['uploads'] as $key)
			{
				$this->dbl->run("UPDATE `article_images` SET `article_id` = ? WHERE `id` = ?", array($article_id, $key));
			}
		}
			
		$this->process_categories($article_id);
			
		// move new uploaded tagline image, and save it to the article
		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name'], $checked['text']);
		}
			
		$this->dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");
			
		unset($_SESSION['original_text']);

		$this->reset_sessions();
			
		// if the person publishing it is not the author then email them
		if ($options['type'] == 'admin_review')
		{
			if ($_POST['author_id'] != $_SESSION['user_id'])
			{
				// find the authors email
				$author_email = $this->dbl->run("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']))->fetch();

				// subject
				$subject = 'Your article was reviewed and published on GamingOnLinux.com!';

				// message
				$message = "
				<html>
				<head>
				<title>Your article was reviewed and approved GamingOnLinux.com!</title>
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

		$article_link = self::get_link($article_id, $checked['slug']);
			
		telegram($checked['title'] . ' ' . $article_link);

		if (!isset($_POST['show_block']))
		{
			$redirect = $article_link;
		}
		else
		{
			$redirect = $this->core->config('website_url') . "admin.php?module=featured&view=add&article_id=".$article_id;
		}

		// note who did it
		$core->new_admin_note(array('completed' => 1, 'content' => ' published a new article titled: <a href="/articles/'.$checked['slug'].'.'.$article_id.'">'.$checked['title'].'</a>.'));

		header("Location: " . $redirect);
		die();
	}
	
	public function display_article_list($article_list, $categories_list)
	{
		global $user;

		foreach ($article_list as $article)
		{
			// make date human readable
			$date = $this->core->human_date($article['date'], 'j F Y');

			$machine_time = date("Y-m-d\TH:i:s", $article['date']) . 'Z';

			$article_date = "<time class=\"timeago\" datetime=\"$machine_time\">{$date}</time>";

			// get the article row template
			$this->templating->block('article_row', 'articles');

			if ($user->check_group([1,2,5]))
			{
				$this->templating->set('edit_link', "<p><a href=\"/admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"> <strong>Edit</strong></a>");
				if ($article['show_in_menu'] == 0)
				{
					if ($this->core->config('total_featured') < 5)
					{
						$this->templating->set('editors_pick_link', " <a href=\"".url."index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><strong>Make Editors Pick</strong></a></p>");
					}
					else if ($this->core->config('total_featured') == 5)
					{
						$this->templating->set('editors_pick_link', "");
					}
				}
				else if ($article['show_in_menu'] == 1)
				{
					$this->templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=removeeditors&amp;article_id={$article['article_id']}\"><strong>Remove Editors Pick</strong></a></p>");
				}
			}

			else
			{
				$this->templating->set('edit_link', '');
				$this->templating->set('editors_pick_link', '');
			}

			$this->templating->set('title', $article['title']);
			$this->templating->set('user_id', $article['author_id']);

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

			$this->templating->set('username', $username);

			$this->templating->set('date', $article_date);

			$categories_display = '';
			if ($article['show_in_menu'] == 1)
			{
				$categories_display = '<li><a href="#">Editors Pick</a></li>';
			}

			if (isset($categories_list[$article['article_id']]))
			{
				$categories_display .= $this->display_article_tags($categories_list[$article['article_id']]);
			}

			$this->templating->set('categories_list', $categories_display);

			$tagline_image = $this->tagline_image($article);

			$this->templating->set('top_image', $tagline_image);

			// set last bit to 0 so we don't parse links in the tagline
			$this->templating->set('text', $article['tagline']);
				
			$this->templating->set('article_link', $this->get_link($article['article_id'], $article['slug']));
			$this->templating->set('comment_count', $article['comment_count']);
		}
	}
	
	// placeholder, so we can merge admin comments, plain article comments and the ajax updater into one function
	// article_info = required article details
	// pagination_link = destination link for pagination
	public function display_comments($article_info)
	{
		// get blocked id's
		$blocked_ids = [];
		$blocked_usernames = [];
		if (count($this->user->blocked_users) > 0)
		{
			foreach ($this->user->blocked_users as $username => $blocked_id)
			{
				$blocked_ids[] = $blocked_id[0];
				$blocked_usernames[] = $username;
			}
		}

		// count how many there is in total, from cache if it exists
		$total_comments = $this->article_comments_counter($article_info['article']['article_id']);
		
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

		if (isset($article_info['article']['comments_open']) && $article_info['article']['comments_open'] == 0) // wont be set for admin areas
		{
			$this->core->message('The comments on this article are closed.');
		}
		
		$this->templating->block('comments_top', 'articles_full');
		$this->templating->set('pagination_head', $pagination_head);
		$this->templating->set('pagination', $pagination);
		
		if (isset($article_info['type']) && $article_info['type'] != 'admin')
		{
			$subscribe_link = '';
			$close_comments_link = '';

			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
			{
				// they're logged in, so let's see if they're subscribed to the article
				$check_sub = $this->dbl->run("SELECT `send_email` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], (int) $article_info['article']['article_id']))->fetch();
				if ($check_sub)
				{
					// update their subscriptions if they are reading the last page
					if ($_SESSION['email_options'] == 2 && $check_sub['send_email'] == 0)
					{
						// they have read all new comments (or we think they have since they are on the last page)
						if ($page == $lastpage)
						{
							// send them an email on a new comment again
							$this->dbl->run("UPDATE `articles_subscriptions` SET `send_email` = 1 WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], (int) $article_info['article']['article_id']));
						}
					}
					// they're subscribed, so set the quick link to unsubscribe
					$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"unsubscribe\" data-article-id=\"{$article_info['article']['article_id']}\" href=\"/index.php?module=articles_full&amp;go=unsubscribe&amp;article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Unsubscribe</span></a>";
				}
				// they're not subscribed, so set the quick link to subscribe
				else
				{
					$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"subscribe\" data-article-id=\"{$article_info['article']['article_id']}\" href=\"/index.php?module=articles_full&amp;go=subscribe&amp;article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Subscribe</span></a>";
				}

				if ($this->user->check_group([1,2]) == true)
				{
					if ($article_info['article']['comments_open'] == 1)
					{
						$close_comments_link = "<a href=\"/index.php?module=articles_full&go=close_comments&article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Close Comments</a></span>";
					}
					else if ($article_info['article']['comments_open'] == 0)
					{
						$close_comments_link = "<a href=\"/index.php?module=articles_full&go=open_comments&article_id={$article_info['article']['article_id']}\" class=\"white-link\"><span class=\"link_button\">Open Comments</a></span>";
					}
				}
			}
			
			$this->templating->set('subscribe_link', $subscribe_link);
			$this->templating->set('close_comments', $close_comments_link);
		}
		else
		{
			$this->templating->set('subscribe_link', '');
			$this->templating->set('close_comments', '');		
		}
		
		//
		/* DISPLAY THE COMMENTS */
		//

		// first grab a list of their bookmarks
		if ($total_comments > 0 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$bookmarks_array = $this->dbl->run("SELECT `data_id` FROM `user_bookmarks` WHERE `type` = 'comment' AND `parent_id` = ? AND `user_id` = ?", array((int) $article_info['article']['article_id'], (int) $_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN);
		}

		$profile_fields = include dirname ( dirname ( __FILE__ ) ) . '/includes/profile_fields.php';

		$db_grab_fields = '';
		foreach ($profile_fields as $field)
		{
			$db_grab_fields .= "u.`{$field['db_field']}`,";
		}
		
		$params = array_merge([(int) $article_info['article']['article_id']], [$this->core->start], [$per_page]);
		
		$comments_get = $this->dbl->run("SELECT a.author_id, a.guest_username, a.comment_text, a.comment_id, u.pc_info_public, u.distro, a.time_posted, a.last_edited, a.last_edited_time, a.`edit_counter`, a.`total_likes`, u.username, u.`avatar`,  $db_grab_fields u.`avatar_uploaded`, u.`avatar_gallery`, u.pc_info_filled, u.game_developer, u.register_date, ul.username as username_edited FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` ul ON ul.user_id = a.last_edited WHERE a.`article_id` = ? AND a.approved = 1 ORDER BY a.`comment_id` ASC LIMIT ?, ?", $params)->fetch_all();
		
		// make an array of all comment ids and user ids to search for likes (instead of one query per comment for likes) and user groups for badge displaying
		$like_array = [];
		$sql_replacers = [];
		$user_ids = [];
		
		foreach ($comments_get as $id_loop)
		{
			// no point checking for if they've liked a comment, that has no likes
			if ($id_loop['total_likes'] > 0) 
			{
				$like_array[] = (int) $id_loop['comment_id'];
				$sql_replacers[] = '?';
			}	
			$user_ids[] = (int) $id_loop['author_id'];
		}

		/* total comments indicator */
		$total_hidden = 0;
		if (!empty($user_ids) && !empty($blocked_ids))
		{
			foreach ($user_ids as $user_id)
			{
				if (in_array($user_id, $blocked_ids))
				{
					$total_hidden++;
				}
			}
		}

		$comments_top_text = '';
		if ($total_comments > 0)
		{
			$comments_top_text = number_format($total_comments) . ' comment';
			if ($total_comments > 1)
			{
				$comments_top_text .= 's';
			}

			if ($total_hidden > 0)
			{
				$comments_top_text .= ' ('.$total_hidden.' hidden)';
			}
		}
		else
		{
			$comments_top_text = 'No comments yet!';
		}
		$this->templating->set('comments_top_text', $comments_top_text);
					
		if (!empty($like_array))
		{
			$to_replace = implode(',', $sql_replacers);
						
			// get this users likes
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
			{
				$replace = [$_SESSION['user_id']];
				foreach ($like_array as $comment_id)
				{
					$replace[] = $comment_id;
				}

				$get_user_likes = $this->dbl->run("SELECT `data_id` FROM `likes` WHERE `user_id` = ? AND `data_id` IN ( $to_replace ) AND `type` = 'comment'", $replace)->fetch_all(PDO::FETCH_COLUMN);
			}
		}

		// get a list of each users user groups, so we can display their badges
		if (!empty($user_ids))
		{
			$comment_user_groups = $this->user->post_group_list($user_ids);
		}
		
		// check over their permissions now
		$permission_check = $this->user->can(array('mod_delete_comments', 'mod_edit_comments'));

		$can_delete = 0;
		if ($permission_check['mod_delete_comments'] == 1)
		{
			$can_delete = 1;
		}
		$can_edit = 0;
		if ($permission_check['mod_edit_comments'] == 1)
		{
			$can_edit = 1;
		}
		
		foreach ($comments_get as $comments)
		{
			$comment_date = $this->core->human_date($comments['time_posted']);

			if (in_array($comments['author_id'], $blocked_ids))
			{
				$this->templating->block('blocked_comment');
			}
			else
			{
				$this->templating->block('article_comments', 'articles_full');
			}
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
			$comment_avatar = $this->user->sort_avatar($comments);
							
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

				
			$this->templating->set('user_id', $comments['author_id']);
			$this->templating->set('username', $into_username . $username);
			$this->templating->set('comment_avatar', $comment_avatar);
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

			$this->templating->set('total_likes', $comments['total_likes']);

			$who_likes_link = '';
			if ($comments['total_likes'] > 0)
			{
				$who_likes_link = ', <a class="who_likes" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?comment_id='.$comments['comment_id'].'">Who?</a>';
			}
			$this->templating->set('who_likes_link', $who_likes_link);
				
			$likes_hidden = '';
			if ($comments['total_likes'] == 0)
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
			$block_icon = '';
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

					// block icon
					$block_icon = '';
					if ($_SESSION['user_id'] != $comments['author_id'])
					{
						$block_icon = '<li><a class="tooltip-top" href="/index.php?module=block_user&block='.$comments['author_id'].'" title="Block User"><span class="icon block"></span></a></li>';
					}

					$like_text = "Like";
					$like_class = "like";
					if ($_SESSION['user_id'] != 0)
					{								
						if (isset($get_user_likes) && in_array($comments['comment_id'], $get_user_likes))
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
						$comment_edit_link = "<li><a class=\"tooltip-top edit_comment_link\" data-comment-id=\"{$comments['comment_id']}\" title=\"Edit\" href=\"" . $this->core->config('website_url') . "index.php?module=edit_comment&amp;view=Edit&amp;comment_id={$comments['comment_id']}\"><span class=\"icon edit\">Edit</span></a></li>";
					}
						
					if ($can_delete == 1 || $_SESSION['user_id'] == $comments['author_id'])
					{
						$comment_delete_link = "<li><a class=\"tooltip-top delete_comment\" title=\"Delete\" href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\" data-comment-id=\"{$comments['comment_id']}\"><span class=\"icon delete\"></span></a></li>";
					}
				}
					
				$logged_in_options = $this->templating->store_replace($logged_in_options, array('post_id' => $comments['comment_id'], 'like_button' => $like_button));
			}
			$this->templating->set('logged_in_options', $logged_in_options);
			$this->templating->set('bookmark', $bookmark_comment);
			$this->templating->set('edit', $comment_edit_link);
			$this->templating->set('delete', $comment_delete_link);
			$this->templating->set('report_link', $report_link);
			$this->templating->set('block', $block_icon);

			// if we have some user groups for that user
			$comments['user_groups'] = $comment_user_groups[$comments['author_id']];
			$badges = user::user_badges($comments, 1);
			$this->templating->set('badges', implode(' ', $badges));
							
			$profile_fields_output = user::user_profile_icons($profile_fields, $comments);

			$this->templating->set('profile_fields', $profile_fields_output);

			// do this last, to help stop templating tags getting parsed in user text
			$this->templating->set('text', $this->bbcode->parse_bbcode($comments['comment_text'] . $last_edited, 0));
			
			$this->templating->set('date', $comment_date);
			$this->templating->set('tzdate', date('c',$comments['time_posted']) );
		}

		$this->templating->block('bottom', 'articles_full');
		$this->templating->set('pagination', $pagination);
		
		if (isset($article_info['type']) && $article_info['type'] != 'admin' && $this->user->check_group([6,9]) === false)
		{
			$this->templating->block('patreon_comments', 'articles_full');
		}
	}
	
	public function display_category_picker($categorys_ids = NULL)
	{
		global $templating;
		
		// show the category selection box
		$templating->block('articles_top', 'articles');
		$options = '';
		$res = $this->dbl->run("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC")->fetch_all();
		foreach ($res as $get_cats)
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
					$quoted_user = $this->dbl->run("SELECT `user_id` FROM `users` WHERE `username` = ?", array($match))->fetchOne();
					if ($quoted_user)
					{
						$this->dbl->run("INSERT INTO `user_notifications` SET `seen` = 0, `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `type` = 'quoted'", array($quoted_user, $author_id, $article_id, $comment_id));
						$new_notification_id[$quoted_user] = $this->dbl->new_id();
					}
				}
			}
		}
		
		$new_notification_id['quoted_usernames'] = $quoted_usernames;
		
		return $new_notification_id;
	}

	function delete_comment($comment_id)
	{		
		$comment = $this->dbl->run("SELECT c.`author_id`, c.`comment_text`, c.`spam`, c.`article_id`, u.`username`, a.`title`, a.`slug` FROM `articles_comments` c LEFT JOIN `users` u ON u.`user_id` = c.`author_id` LEFT JOIN `articles` a ON a.`article_id` = c.`article_id` WHERE c.`comment_id` = ?", array((int) $comment_id))->fetch();

		if ($comment['author_id'] != $_SESSION['user_id'] && $this->user->can('mod_delete_comments') == false)
		{
			return false;
		}

		else
		{
			if ($comment['author_id'] == 1 && $_SESSION['user_id'] != 1)
			{
				return false;
			}

			else
			{
				// this comment was reported as spam but as its now deleted remove the notification
				if ($comment['spam'] == 1)
				{
					$this->core->update_admin_note(array('type' => 'reported_comment', 'data' => $comment_id));
				}

				if (isset($comment['username']) && !empty($comment['username']))
				{
					$username = $comment['username'];
				}
				else
				{
					$username = 'Guest';
				}

				$this->core->new_admin_note(array('completed' => 1, 'type' => 'comment_deleted', 'data' => $comment_id, 'content' => ' deleted a comment from ' . $username . ' in the article titled <a href="/articles/'.$comment['slug'].'.'.$comment['article_id'].'">'.$comment['title'].'</a>.'));

				$this->dbl->run("UPDATE `articles` SET `comment_count` = (comment_count - 1) WHERE `article_id` = ?", array($comment['article_id']));
				$this->dbl->run("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array((int) $comment_id));
				$this->dbl->run("DELETE FROM `likes` WHERE `data_id` = ?", array((int) $comment_id));

				// deal with redis comment counter cache
				$this->article_comments_counter($comment['article_id'], 'remove');

				// update notifications

				// find any notifications caused by the deleted comment
				$current_notes = $this->dbl->run("SELECT `owner_id`, `id`, `total`, `seen`, `seen_date`, `article_id`, `comment_id` FROM `user_notifications` WHERE `type` != 'liked' AND `article_id` = ?", array($comment['article_id']))->fetch_all();

				foreach ($current_notes as $this_note)
				{
					// if this wasn't the only comment made for that notification
					if ($this_note['total'] >= 2)
					{
						// if the one deleted is the original comment we were notified about
						if ($this_note['comment_id'] == $comment_id)
						{
							// find the last available comment
							$last_comment = $this->dbl->run("SELECT `author_id`, `comment_id`, `time_posted` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `time_posted` DESC LIMIT 1", array($this_note['article_id']))->fetch();

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

							$new_date = date('Y-m-d H:i:s', $last_comment['time_posted']); // comments use a plain int time format

							$this->dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `notifier_id` = ?, `seen` = ?, `comment_id` = ? WHERE `id` = ?", array($new_date, $last_comment['author_id'], $seen, $last_comment['comment_id'], $this_note['id']));
						}
						// no matter what we need to adjust the counter
						$this->dbl->run("UPDATE `user_notifications` SET `total` = (total - 1) WHERE `id` = ?", array($this_note['id']));
					}
					// it's the only comment they were notified about, so just delete the notification to completely remove it
					else if ($this_note['total'] == 1)
					{
						$this->dbl->run("DELETE FROM `user_notifications` WHERE `id` = ?", array($this_note['id']));
					}
				}

				return true;
			}
		}		
	}

	// grab total comments or add/remove 1 from the counter using redis cache if it exists
	function article_comments_counter($article_id, $type = NULL)
	{
		// deal with redis comment counter cache
		$sql_count = "SELECT COUNT(`comment_id`) FROM `articles_comments` a WHERE a.`article_id` = ? AND a.`approved` = 1";

		// setup a cache
		$querykey = "KEY" . md5($sql_count . serialize($article_id));
		$total_comments = unserialize(core::$redis->get($querykey)); // check cache
							
		if ($total_comments === false || $total_comments === null) // there's no cache
		{
			$total_comments = $this->dbl->run($sql_count, array($article_id))->fetchOne();
			core::$redis->set($querykey, serialize($total_comments), 21600); // cache for six hours
			return $total_comments;
		}
		else
		{
			if ($type == 'add')
			{
				core::$redis->set($querykey, serialize($total_comments + 1), 21600);
				$total_comments = $total_comments + 1;
			}
			else if ($type == 'remove')
			{
				core::$redis->set($querykey, serialize($total_comments - 1), 21600);
				$total_comments = $total_comments - 1;
			}
			return $total_comments;
		}	
	}

	function remove_editor_pick($article_id)
	{
		if ($this->user->check_group([1,2,5]))
		{
			$featured = $this->dbl->run("SELECT `featured_image` FROM `editor_picks` WHERE `article_id` = ?", array($article_id))->fetch();
			if ($featured)
			{
				$this->dbl->run("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($article_id));
				$featured_image = $this->core->config('path') . 'uploads/carousel/' . $featured['featured_image'];
				if (file_exists($featured_image))
				{
					unlink($featured_image);
				}

				$this->dbl->run("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'total_featured'");

				$this->dbl->run("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($article_id));

				// update cache
				$new_featured_total = $this->core->config('total_featured') - 1;
				core::$redis->set('CONFIG_total_featured', $new_featured_total); // no expiry as config hardly ever changes

				$_SESSION['message'] = 'featured_unpicked';
			}
		}
	}
}
?>
