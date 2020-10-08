<?php
class article
{
	private $dbl;
	private $core;
	private $user;
	private $templating;
	private $bbcode;
	private $notifications;

	function __construct($dbl, $core, $user, $templating, $bbcode, $notifications)
	{
		$this->dbl = $dbl;
		$this->core = $core;
		$this->user = $user;
		$this->templating = $templating;
		$this->bbcode = $bbcode;
		$this->notifications = $notifications;
	}

	// clear out any left overs, since there's no error we don't need them, stop errors with them
	// TO DO: Use an array of $_SESSION['article']['blah'] and just remove that, would be much clearer and allow easy expansion/edits
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
		unset($_SESSION['agames']);
	}

	public function tagline_image($data, $height = 420, $width = 740)
	{
		$tagline_image = '';

		if (!empty($data['tagline_image']))
		{
			$tagline_image = "<img class=\"tagline_image\" alt height=\"$height\" width=\"$width\" loading=\"lazy\" src=\"".$this->core->config('website_url')."uploads/articles/tagline_images/{$data['tagline_image']}\">";
		}
		if ($data['gallery_tagline'] > 0 && !empty($data['gallery_tagline_filename']))
		{
			$tagline_image = "<img alt loading=\"lazy\" height=\"$height\" width=\"$width\" src=\"".$this->core->config('website_url')."uploads/tagline_gallery/{$data['gallery_tagline_filename']}\">";
		}
		if (empty($data['tagline_image']) && $data['gallery_tagline'] == 0)
		{
			$tagline_image = "<img alt loading=\"lazy\" height=\"$height\" width=\"$width\" src=\"".$this->core->config('website_url')."uploads/articles/tagline_images/defaulttagline.png\">";
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
		// setting a limit on the amount of tags per article
		if (isset($options['limit']))
		{
			$category_tag_sql = "WITH CTE AS (
				SELECT r.article_id, c.`category_name`, c.`category_id`,
					   ROW_NUMBER() OVER (PARTITION BY r.`article_id` ORDER BY c.show_first DESC, c.category_name) AS rn
				FROM  `article_category_reference` r
				INNER JOIN  `articles_categorys` c ON c.category_id = r.category_id
				WHERE r.article_id IN ("  . $options['article_ids'] . ")
			)
			SELECT article_id, category_name, category_id
			FROM CTE
			WHERE rn < " . $options['limit'];
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

	public function display_article_tags($categories_list, $style = 'list')
	{
		if ($style == 'list')
		{
			$categories_output = '';
		}
		if ($style == 'array_plain')
		{
			$categories_output = array();
		}
		
		if (!empty($categories_list))
		{
			foreach ($categories_list as $tag)
			{
				$category_link = $this->tag_link($tag['category_name']);

				$class = '';
				if ($tag['category_id'] == 60)
				{
					$class = 'class="ea"';
				}
				else if ($tag['category_name'] == 'Steam Play')
				{
					$class = 'class="steamplay"';
				}
				else if ($tag['category_name'] == 'Stadia')
				{
					$class = 'class="stadia"';
				}

				if ($style == 'list')
				{
					$categories_output .= " <li ".$class."><a href=\"$category_link\">{$tag['category_name']}</a></li> ";
				}
				if ($style == 'array_plain')
				{
					$categories_output[] = "<a ".$class." href=\"$category_link\">{$tag['category_name']}</a>";
				}
			}
		}
		return $categories_output;
	}

	public function display_game_tags($games_list, $style = 'list')
	{
		if ($style == 'list')
		{
			$categories_output = '';
		}
		if ($style == 'array_plain')
		{
			$categories_output = array();
		}

		if (!empty($games_list))
		{
			foreach ($games_list as $tag)
			{
				if ($style == 'list')
				{
					$categories_output .= " <li><a href=\"/itemdb/".$tag['game_id']."\">{$tag['name']}</a></li> ";
				}
				if ($style == 'array_plain')
				{
					$categories_output[] = "<a href=\"/itemdb/".$tag['game_id']."\">{$tag['name']}</a>";
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
		$image_formats = array("jpg", "png", "gif", "jpeg", "svg");

		$previously_uploaded['output'] = '';
		$previously_uploaded['hidden'] = '';
		$article_images = NULL;
		if ($article_id != NULL)
		{
			// add in uploaded images from database
			$article_images = $this->dbl->run("SELECT `filename`, `location`, `id`,`filetype`,`youtube_cache` FROM `article_images` WHERE `article_id` = ? ORDER BY `id` ASC", array($article_id))->fetch_all();
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
				$article_images = $this->dbl->run("SELECT `filename`,`id`,`filetype`,`youtube_cache`,`location` FROM `article_images` WHERE `id` IN ($in) ORDER BY `id` ASC", $image_ids)->fetch_all();
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
				$youtube_thumb = '';

				$core_url = $this->core->config('website_url');

				if ($value['location'] != NULL)
				{
					$core_url = $this->core->config('external_media_upload_url');
				}

				if ($value['youtube_cache'] == 1)
				{
					$youtube_thumb = 'YouTube Thumbnail Image: <br />';

					$main_url = $this->core->config('website_url') . 'cache/youtube_thumbs/' . $value['filename'];
					$main_path = APP_ROOT . '/cache/youtube_thumbs/' . $value['filename'];
				}
				else
				{
					$main_url = $core_url . 'uploads/articles/article_media/' . $value['filename'];
					$main_path = APP_ROOT . '/uploads/articles/article_media/' . $value['filename'];
				}
				$gif_static_button = '';
				$thumbnail_button = '';
				$data_type = '';

				if (in_array($value['filetype'], $image_formats))
				{
					if ($value['youtube_cache'] == 1)
					{
						$thumb_url = $this->core->config('website_url') . 'cache/youtube_thumbs/' . $value['filename'];
						$thumb_path = APP_ROOT . '/cache/youtube_thumbs/' . $value['filename'];						
					}
					else
					{
						$thumb_url = $core_url . 'uploads/articles/article_media/thumbs/' . $value['filename'];
						$thumb_path = APP_ROOT . '/uploads/articles/article_media/thumbs/' . $value['filename'];
						$thumbnail_button = '<button data-url="'.$thumb_url.'" data-main-url="'.$main_url.'" class="add_thumbnail_button">Insert thumbnail</button>'; // we don't make an extra thumbnail for the youtube cache images
					}

					if ($value['filetype'] == 'gif')
					{
						$static_filename = str_replace('.gif', '_static.jpg', $value['filename']);
						$static_url = $core_url . 'uploads/articles/article_media/' . $static_filename;
						$gif_static_button = '<button data-url-gif="'.$main_url.'" data-url-static="'.$static_url.'" class="add_static_button">Insert Static</button>';
					}

					$preview_file = '<img src="' . $thumb_url . '" class="imgList"><br />';
					$data_type = 'image';
				}
				else if ($value['filetype'] == 'mp4' || $value['filetype'] == 'webm')
				{
					$preview_file = '<video width="100%" src="'.$main_url.'" controls></video>';
					$data_type = 'video';
				}
				else if ($value['filetype'] == 'mp3' || $value['filetype'] == 'ogg')
				{
					$preview_file = '<div class="ckeditor-html5-audio" style="text-align: center;"><audio controls="controls" src="'.$main_url.'">&nbsp;</audio></div>';
					$data_type = 'audio';
				}

				$previously_uploaded['output'] .= '<div class="box">
				<div class="body group">
				<div id="'.$value['id'].'">'.$youtube_thumb.$preview_file.'
				URL: <input id="img' . $value['id'] . '" type="text" value="' . $main_url . '" /> <button class="btn" data-clipboard-target="#img' . $value['id'] . '">Copy</button> '.$gif_static_button.' <button data-url="'.$main_url.'" data-type="'.$data_type.'" class="add_button">Insert</button> '.$thumbnail_button.' <button id="' . $value['id'] . '" class="trash" data-type="article">Delete Media</button>
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

	public function process_games($article_id)
	{
		if (isset($article_id) && is_numeric($article_id))
		{
			// delete any existing categories that aren't in the final list for publishing
			$current_linked_games = $this->dbl->run("SELECT `id`, `article_id`, `game_id` FROM `article_item_assoc` WHERE `article_id` = ?", array($article_id))->fetch_all();

			if (!empty($current_linked_games))
			{
				foreach ($current_linked_games as $current_game)
				{
					if (!empty($_POST['games']))
					{
						if (!in_array($current_game['game_id'], $_POST['games']))
						{
							$this->dbl->run("DELETE FROM `article_item_assoc` WHERE `id` = ?", array($current_game['id']));
						}
					}
					else // totally empty, no tags = delete any stored
					{
						$this->dbl->run("DELETE FROM `article_item_assoc` WHERE `article_id` = ?", array($article_id));
					}
				}
			}
			// get fresh list of categories, and insert any that don't exist
			$current_linked_games = $this->dbl->run("SELECT `game_id` FROM `article_item_assoc` WHERE `article_id` = ?", array($article_id))->fetch_all(PDO::FETCH_COLUMN, 0);

			if (isset($_POST['games']) && !empty($_POST['games']))
			{
				foreach($_POST['games'] as $game)
				{
					if (!in_array($game, $current_linked_games))
					{
						$this->dbl->run("INSERT INTO `article_item_assoc` SET `article_id` = ?, `game_id` = ?", array($article_id, $game));
					}
				}
			}
		}
	}

	function display_previous_games($article_id = NULL)
	{
		$game_tag_list = '';

		if (isset($_SESSION['agames']) && !empty($_SESSION['agames']) && is_array($_SESSION['agames']))
		{
			$in  = str_repeat('?,', count($_SESSION['agames']) - 1) . '?';

			$current_linked_games = $this->dbl->run("SELECT `name`,`id` FROM `calendar` WHERE `id` IN ($in)", $_SESSION['agames'])->fetch_all();

			foreach ($current_linked_games as $game)
			{
				$game_tag_list .= "<option value=\"{$game['id']}\" selected>{$game['name']}</option>";
			}
			
		}

		else if (isset($article_id))
		{
			$current_linked_games = $this->dbl->run("SELECT a.`game_id`, g.`name` FROM `article_item_assoc` a INNER JOIN `calendar` g ON g.id = a.game_id WHERE a.`article_id` = ?", array($article_id))->fetch_all();
			foreach ($current_linked_games as $game)
			{
				$game_tag_list .= "<option value=\"{$game['game_id']}\" selected>{$game['name']}</option>";
			}
		}

		return $game_tag_list;
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

		// update cache for total articles live just in case
		$total = $this->dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE `active` = 1")->fetchOne();
		$this->core->set_dbcache('total_articles_active', $total);
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
		// if this is set to 1, we've come across an issue, so redirect
		$redirect = 0;

		$temp_tagline = 0;
		if ( (!empty($_SESSION['uploads_tagline']['image_name']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand']) || (!empty($_SESSION['gallery_tagline_rand']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand']))
		{
			$temp_tagline = 1;
		}

		$article_id = 0;
		if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
		{
			$article_id = $_POST['article_id'];
			$check_article = $this->dbl->run("SELECT `tagline_image`, `gallery_tagline` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();
		}

		$title = strip_tags($_POST['title']);
		$title = mb_convert_encoding($title, 'UTF-8');
		$tagline = trim($_POST['tagline']);
		$text = html_entity_decode(trim($_POST['text']), ENT_QUOTES);
		$categories = [];
		if (!empty($_POST['categories']))
		{
			$categories = $_POST['categories'];
		}
		$games = [];
		if (!empty($_POST['games']))
		{
			$games = $_POST['games'];
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
			$article_res = $this->dbl->run("SELECT `article_id`, `title`, `date`,`slug` FROM `articles` WHERE `date` > ? AND `article_id` NOT IN ($in)", array_merge([$_SESSION['article_timer']], $_SESSION['conflict_checked']))->fetch_all();
		}
		else
		{
			if (!isset($_SESSION['article_timer']))
			{
				error_log('Article timer not set: ' . $_SERVER['REQUEST_URI']);
			}
			$article_res = $this->dbl->run("SELECT `article_id`, `title`, `date`,`slug` FROM `articles` WHERE `date` > ?", array($_SESSION['article_timer']))->fetch_all();
		}
		if ($article_res)
		{
			$article_list = '<form><ul>';
			foreach($article_res as $res)
			{
				$article_link = $this->article_link(array('date' => $res['date'], 'slug' => $res['slug']));
				$article_list .= '<li><a href="'.$article_link.'" target="_blank">'.$res['title'].'</a><input type="hidden" name="article_ids[]" value="'.$res['article_id'].'" /></li>';
			}

			$article_list .= '</ul><button type="button" class="conflict_confirmed">Confirmed</button></form>';

			$redirect = 1;
			$_SESSION['message'] = 'article_conflicts';
			$_SESSION['message_extra'] = $article_list;
		}
		else
		{
			// get current list of featured article ids
			$featured_ids = $this->dbl->run("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1")->fetch_all(PDO::FETCH_COLUMN);

			// ensure unique slugs
			$slug_check = $this->dbl->run("SELECT 1 FROM `articles` WHERE `slug` = ? AND `article_id` != ?", array($slug, $article_id))->fetchOne();

			// make sure its not empty
			$empty_check = core::mempty(compact('title', 'tagline', 'text', 'categories'));
			if ($empty_check !== true)
			{
				$redirect = 1;

				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = $empty_check;
			}
			else if ($slug_check)
			{
				$redirect = 1;

				$_SESSION['message'] = 'slug_dupe';
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

			else if (strlen($tagline) > $this->core->config('tagline-max-length'))
			{
				$redirect = 1;

				$_SESSION['message'] = 'taglinetoolong';
				$_SESSION['message_extra'] = $this->core->config('tagline-max-length');
			}

			else if (strlen($title) < 10)
			{
				$redirect = 1;

				$_SESSION['message'] = 'shorttitle';
			}

			else if (isset($_POST['show_block']) && $this->core->config('total_featured') == $this->core->config('editor_picks_limit') && !in_array($_POST['article_id'], $featured_ids))
			{
				$redirect = 1;

				$_SESSION['message'] = 'editor_picks_full';
				$_SESSION['message_extra'] = $this->core->config('editor_picks_limit');
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
			$_SESSION['agames'] = $games;

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
			$date = $this->core->human_date($grab_history['date']);
			$history .= '<li><a href="/profiles/'. $grab_history['user_id'] .'">' . $grab_history['username'] . '</a> '.$view_link.' - ' . $date . '</li>';
		}

		$templating->load('admin_modules/admin_module_articles');
		$templating->block('history', 'admin_modules/admin_module_articles');
		$templating->set('history', $history);
	}

	/* generate a link for articles based on what's given */
	public function article_link($data)
	{
		$year = date('Y', $data['date']);
		$month = date('m', $data['date']);

		$link = $year . '/' . $month . '/' . $data['slug'];

		if (isset($data['additional']))
		{
			$link = $link . '/' . $data['additional'];
		}

		return $this->core->config('website_url') . $link;
	}

	public function tag_link($name)
	{
		$name = str_replace(' ', '_', $name);
		$name = rawurlencode($name);
		$link = 'articles/category/'.$name;
		return $this->core->config('website_url') . $link;
	}

	public function publish_article($options)
	{
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

		$date_now = core::$date;

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

			$this->dbl->run("UPDATE `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `submitted_unapproved` = 0, `draft` = 0, `locked` = 0, `comment_count` = 0, `guest_email` = '', `guest_ip` = '' WHERE `article_id` = ?", array($author_id, $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $editors_pick, $date_now, $_SESSION['user_id'], $_POST['article_id']));

			// since they are approving and not neccisarily editing, check if the text matches, if it doesnt they have edited it
			if ($_SESSION['original_text'] != $checked['text'])
			{
				$this->dbl->run("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], $date_now, $_SESSION['original_text']));
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
			$this->dbl->run("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0 $gallery_tagline_sql", array($_SESSION['user_id'], $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $editors_pick, $date_now));

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

		$this->process_games($article_id);

		// move new uploaded tagline image, and save it to the article
		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$this->core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name'], $checked['text']);
		}

		$this->dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");

		unset($_SESSION['original_text']);

		$this->reset_sessions();

		$article_link = self::article_link(array('date' => $date_now, 'slug' => $checked['slug']));
		$comments_link = $article_link . '/#comments';

		// if the person publishing it is not the author then email them
		if ($options['type'] == 'admin_review')
		{
			if ($_POST['author_id'] != $_SESSION['user_id'])
			{
				// find the authors email
				$author_email = $this->dbl->run("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']))->fetchOne();

				// subject
				$subject = 'Your article "'.$checked['title'].'" was reviewed and published on GamingOnLinux.com!';

				// message
				$html_message = "<p><strong>{$_SESSION['username']}</strong> has reviewed and published your article \"<a href=\"$article_link\">{$checked['title']}</a>\" on <a href=\"https://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>.</p>";

				$plain_message = "{$_SESSION['username']} has reviewed and published your article \"{$checked['title']}\", here's the live link: $article_link";

				// Mail it
				if ($this->core->config('send_emails') == 1)
				{
					$mail = new mailer($this->core);
					$mail->sendMail($author_email, $subject, $html_message, $plain_message);
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
			$subject = 'Your article "'.$checked['title'].'" was approved on GamingOnLinux.com!';

			// message
			$html_message = "<p>We have accepted your article \"<a href=\"$article_link\">{$checked['title']}</a>\" on <a href=\"http://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>. Thank you for taking the time to send us news we really appreciate the help, you are awesome.</p>";

			$plain_message = "We have accepted your article \"{$checked['title']}\" on GamingOnLinux.com, here's the live link: $article_link";

			if ($this->core->config('send_emails') == 1)
			{
				$mail = new mailer($this->core);
				$mail->sendMail($email, $subject, $html_message, $plain_message);
			}
		}

		include($this->core->config('path') . 'includes/telegram_poster.php');

		define('CHAT_ID', $this->core->config('telegram_news_channel'));
		define('BOT_TOKEN', $this->core->config('telegram_bot_key'));
		define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

		telegram($checked['title'] . "\n\r\n\rLink: " . $article_link . "\n\rComments: " . $comments_link, $article_link);

		define('WEBHOOK_URL', $this->core->config('discord_news_webhook'));

		include($this->core->config('path') . 'includes/discord_poster.php');

		$tagline_image_sql = $this->dbl->run("SELECT a.`tagline_image`,a.`gallery_tagline`, t.`filename` as `gallery_tagline_filename` FROM `articles` a LEFT JOIN
		`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` WHERE a.`article_id` = ?", array($article_id))->fetch();
		$tagline_image = '';
		if (!empty($tagline_image_sql['tagline_image']))
		{
			$tagline_image = $this->core->config('website_url')."uploads/articles/tagline_images/".$tagline_image_sql['tagline_image'];
		}
		if ($tagline_image_sql['gallery_tagline'] > 0 && !empty($tagline_image_sql['gallery_tagline_filename']))
		{
			$tagline_image = $this->core->config('website_url')."uploads/tagline_gallery/".$tagline_image_sql['gallery_tagline_filename'];
		}
		if (empty($tagline_image_sql['tagline_image']) && $tagline_image_sql['gallery_tagline'] == 0)
		{
			$tagline_image = $this->core->config('website_url')."uploads/articles/tagline_images/defaulttagline.png";
		}

		post_to_discord(array('title' => $checked['title'], 'link' => $article_link, 'tagline' => $checked['tagline'], 'image' => $tagline_image));

		if (!isset($_POST['show_block']))
		{
			$redirect = $article_link;
		}
		else
		{
			$redirect = $this->core->config('website_url') . "admin.php?module=featured&view=add&article_id=".$article_id;
		}

		// note who did it
		$this->core->new_admin_note(array('completed' => 1, 'content' => ' published a new article titled: <a href="/articles/'.$article_id.'">'.$checked['title'].'</a>.'));

		// update total active article cache
		$total = $this->dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE `active` = 1")->fetchOne();
		$this->core->set_dbcache('total_articles_active', $total);

		header("Location: " . $redirect);
		die();
	}

	public function display_article_list($article_list, $categories_list)
	{
		foreach ($article_list as $article)
		{
			// make date human readable
			$date = $this->core->time_ago($article['date']);

			$machine_time = date('c',$article['date']);

			$article_date = "<time datetime=\"$machine_time\">{$date}</time>";

			// get the article row template
			$this->templating->block('article_row', 'articles');

			$special_links = '';
			if ($this->user->check_group([1,2,5]))
			{
				$special_links .= '<span class="article-list-special-links">';
				$special_links .= " <a href=\"/admin.php?module=articles&amp;view=Edit&amp;aid={$article['article_id']}\"><strong>Edit</strong></a> ";
				if ($article['show_in_menu'] == 0)
				{
					if ($this->core->config('total_featured') < 5)
					{
						$special_links .= " <a href=\"".url."index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><strong>Make Editors Pick</strong></a> ";
					}
				}
				else if ($article['show_in_menu'] == 1)
				{
					$special_links .= " <a href=\"/index.php?module=home&amp;view=removeeditors&amp;article_id={$article['article_id']}\"><strong>Remove Editors Pick</strong></a> ";
				}
				$special_links .= '</span>';
			}
			$this->templating->set('special_links', $special_links);

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
                if (isset($article['profile_address']) && !empty($article['profile_address']))
                {
                    $profile_address = '/profiles/' . $article['profile_address'];
                }
                else
                {
                    $profile_address = '/profiles/' . $article['author_id'];
                }
				$username = "<a href=\"".$profile_address."\">" . $article['username'] . '</a>';
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

			$this->templating->set('article_link', $this->article_link(array('date' => $article['date'], 'slug' => $article['slug'])));
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

		$total_comments = $article_info['article']['comment_count'];

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
		$this->templating->set('article_id', $article_info['article']['article_id']);
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

		//
		/* DISPLAY THE COMMENTS */
		//

		// first grab a list of their bookmarks
		$bookmarks_array = NULL;
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

		/* NORMAL COMMENTS */
		$comments_get = $this->dbl->run("SELECT a.`author_id`, a.`guest_username`, a.`promoted`, a.`comment_text`, a.`comment_id`, u.`pc_info_public`, u.`distro`, a.`time_posted`, a.`last_edited`, a.`last_edited_time`, a.`total_likes`, u.`username`, u.`profile_address`, u.`avatar`,  $db_grab_fields u.`avatar_uploaded`, u.`avatar_gallery`, u.`pc_info_filled`, u.`game_developer`, u.`register_date`, ul.`username` as `username_edited` FROM `articles_comments` a LEFT JOIN `users` u ON a.`author_id` = u.`user_id` LEFT JOIN `users` ul ON ul.`user_id` = a.`last_edited` WHERE a.`article_id` = ? AND a.`approved` = 1 ORDER BY a.`time_posted` ASC LIMIT ?, ?", $params)->fetch_all();

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

		$get_user_likes = NULL;
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

		/* PROMOTED COMMENTS */
		if ($page == 1)
		{
			$promoted_comments = $this->dbl->run("SELECT a.`author_id`, a.`guest_username`, a.`promoted`, a.comment_text, a.comment_id, u.pc_info_public, u.distro, a.time_posted, a.last_edited, a.last_edited_time, a.`total_likes`, u.username, u.`profile_address`, u.`avatar`,  $db_grab_fields u.`avatar_uploaded`, u.`avatar_gallery`, u.pc_info_filled, u.game_developer, u.register_date, ul.username as username_edited FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` ul ON ul.user_id = a.last_edited WHERE a.`article_id` = ? AND a.`approved` = 1 AND a.`promoted` = 1 ORDER BY a.`time_posted` ASC LIMIT ?, ?", $params)->fetch_all();

			if (isset($promoted_comments) && !empty($promoted_comments))
			{
				$this->templating->block('promoted_top', 'articles_full');

				$this->render_comments($promoted_comments, $article_info, $bookmarks_array, $permission_check, $comment_user_groups, $profile_fields, $get_user_likes, 'promoted_comments');
			}
		}

		if(isset($promoted_comments) && !empty($promoted_comments))
		{
			$this->templating->block('normal_comments_top', 'articles_full');
		}

		if ($comments_get)
		{
			$this->render_comments($comments_get, $article_info, $bookmarks_array, $permission_check, $comment_user_groups, $profile_fields, $get_user_likes, 'normal_comments');
		}

		$this->templating->block('bottom', 'articles_full');
		$this->templating->set('article_id', $article_info['article']['article_id']);
		$this->templating->set('pagination', $pagination);
	}

	function render_comments($comments_get, $article_info, $bookmarks_array, $permission_check, $comment_user_groups, $profile_fields, $get_user_likes, $type)
	{
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
			$comment_date = $this->core->time_ago($comments['time_posted']);

			if (in_array($comments['author_id'], $this->user->blocked_user_ids))
			{
				$this->templating->block('blocked_comment', 'articles_full');
			}
			else
			{
				$promoted_style = '';
				if ($comments['promoted'] == 1)
				{
					$promoted_style = 'promoted-comment';
				}
				$this->templating->block('article_comments', 'articles_full');
				$this->templating->set('promoted_style', $promoted_style);
			}
			// remove blocked users quotes
			if (count($this->user->blocked_usernames) > 0)
			{
				foreach($this->user->blocked_usernames as $username)
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
                if (isset($comments['profile_address']) && !empty($comments['profile_address']))
                {
                    $profile_address = '/profiles/' . $comments['profile_address'];
                }
                else
                {
                    $profile_address = '/profiles/' . $comments['author_id'];
                }
				$username = "<a href=\"".$profile_address."\">{$comments['username']}</a>";
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
			if ($comments['last_edited'] != 0)
			{
				$last_edited = "\r\n\r\n\r\n[i]Last edited by " . $comments['username_edited'] . ' on ' . $this->core->human_date($comments['last_edited_time']) . '[/i]';
			}

			$promote_option = '';
			if ($this->user->check_group([1,2,5]))
			{
				if ($comments['promoted'] == 1)
				{
					$promote_option = ' <a href="/index.php?module=articles_full&aid='.$article_info['article']['article_id'].'&go=demote&demote='.$comments['comment_id'].'"><span class="link_button">Demote</span></a> ';
				}
				else
				{
					$promote_option = ' <a href="/index.php?module=articles_full&aid='.$article_info['article']['article_id'].'&go=promote&promote='.$comments['comment_id'].'"><span class="link_button">Promote</span></a> ';				
				}
			}
			$this->templating->set('promote_option', $promote_option);

			$this->templating->set('article_id', $article_info['article']['article_id']);
			$this->templating->set('comment_id', $comments['comment_id']);

			$this->templating->set('total_likes', $comments['total_likes']);

			$who_likes_link = '';
			if ($comments['total_likes'] > 0)
			{
				$who_likes_link = ', <a class="who_likes" href="/index.php?module=who_likes&amp;comment_id='.$comments['comment_id'].'" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?comment_id='.$comments['comment_id'].'">Who?</a>';
			}
			$this->templating->set('who_likes_link', $who_likes_link);

			$likes_hidden = '';
			if ($comments['total_likes'] == 0 || $type == 'promoted_comments')
			{
				$likes_hidden = ' likes_hidden ';
			}
			$this->templating->set('hidden_likes_class', $likes_hidden);

			$logged_in_options = '';
			$bookmark_comment = '';
			$report_link = '';
			$comment_edit_link = '';
			$like_button = '';
			$comment_delete_link = '';
			$link_to_comment = '';
			$permalink = $this->article_link(array('date' => $article_info['article']['date'], 'slug' => $article_info['article']['slug'], 'additional' => 'comment_id=' . $comments['comment_id']));
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

					if ($type == 'normal_comments')
					{
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
							$like_button = '<li class="lb-container" style="display:none !important"><a class="plusone tooltip-top" data-type="comment" data-id="'.$comments['comment_id'].'" data-article-id="'.$article_info['article']['article_id'].'" data-author-id="'.$comments['author_id'].'" title="Like"><span class="icon '.$like_class.'">'.$like_text.'</span></a></li>';
						}
					
						$report_link = "<li><a class=\"tooltip-top\" href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&amp;go=report_comment&amp;article_id={$article_info['article']['article_id']}&amp;comment_id={$comments['comment_id']}\" title=\"Report\"><span class=\"icon flag\">Flag</span></a></li>";
					}

					if ($_SESSION['user_id'] == $comments['author_id'] || $can_edit == 1)
					{
						$comment_edit_link = "<li><a class=\"tooltip-top edit_comment_link\" data-comment-id=\"{$comments['comment_id']}\" title=\"Edit\" href=\"" . $this->core->config('website_url') . "index.php?module=edit_comment&amp;view=Edit&amp;comment_id={$comments['comment_id']}\"><span class=\"icon edit\">Edit</span></a></li>";
					}

					if ($can_delete == 1 || $_SESSION['user_id'] == $comments['author_id'])
					{
						$comment_delete_link = "<li><a class=\"tooltip-top delete_comment\" title=\"Delete\" href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\" data-comment-id=\"{$comments['comment_id']}\"><span class=\"icon delete\"></span></a></li>";
					}
				}

				$logged_in_options = $this->templating->store_replace($logged_in_options, array('post_id' => $comments['comment_id'], 'like_button' => $like_button, 'article_id' => $article_info['article']['article_id']));
			}
			$this->templating->set('logged_in_options', $logged_in_options);
			$this->templating->set('bookmark', $bookmark_comment);
			$this->templating->set('edit', $comment_edit_link);
			$this->templating->set('delete', $comment_delete_link);
			$this->templating->set('report_link', $report_link);
			$this->templating->set('block', $block_icon);

            // if we have some user groups for that user
            if (isset($comment_user_groups[$comments['author_id']]))
            {
                $comments['user_groups'] = $comment_user_groups[$comments['author_id']];
                $badges = user::user_badges($comments, 1);
                $this->templating->set('badges', implode(' ', $badges));
            }
            else
            {
                $this->templating->set('badges', '');
            }

			$profile_fields_output = user::user_profile_icons($profile_fields, $comments);

			$this->templating->set('profile_fields', $profile_fields_output);

			// do this last, to help stop templating tags getting parsed in user text
			$this->templating->set('text', $this->bbcode->parse_bbcode($comments['comment_text'] . $last_edited, 0));

			$this->templating->set('date', $comment_date);
			$this->templating->set('tzdate', date('c',$comments['time_posted']) );
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

	function remove_editor_pick($article_id)
	{
		if ($this->user->check_group([1,2,5]))
		{
			$featured = $this->dbl->run("SELECT `featured_image`, `featured_image_backup` FROM `editor_picks` WHERE `article_id` = ?", array($article_id))->fetch();
			if ($featured)
			{
				$this->dbl->run("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($article_id));
				$featured_image = $this->core->config('path') . 'uploads/carousel/' . $featured['featured_image'];
				if (file_exists($featured_image))
				{
					unlink($featured_image);
				}
				$featured_image_backup = $this->core->config('path') . 'uploads/carousel/' . $featured['featured_image_backup'];
				if (file_exists($featured_image_backup))
				{
					unlink($featured_image_backup);
				}
				$this->dbl->run("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'total_featured'");

				$this->dbl->run("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($article_id));

				// update cache
				$new_featured_total = $this->core->config('total_featured') - 1;
				$this->core->set_dbcache('CONFIG_total_featured', $new_featured_total); // no expiry as config hardly ever changes

				$_SESSION['message'] = 'featured_unpicked';
			}
		}
	}

	public function add_comment()
	{
		if (!isset($_POST['aid']) || !is_numeric($_POST['aid']))
		{
			die();
		}

		if (!isset($_SESSION['user_id']) || ( isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 ) )
		{
			return array("error" => 1, "message" => 'not_logged_in');
		}

		if (!$this->user->can('comment_on_articles'))
		{
			return array("error" => 1, "message" => 'no_permission');
		}

		if ($this->core->config('comments_open') == 0)
		{
			return array("error" => 1, "message" => 'comments_offline');
		}
		else
		{
			// get article name for the email and redirect
			$title = $this->dbl->run("SELECT `title`, `comment_count`, `comments_open`, `slug`, `date` FROM `articles` WHERE `article_id` = ?", array((int) $_POST['aid']))->fetch();
			$title_nice = core::nice_title($title['title']);

			$article_link = $this->article_link(array('date' => $title['date'], 'slug' => $title['slug']));

			if ($title['comments_open'] == 0 && $this->user->check_group([1,2]) == false)
			{
				return array("error" => 1, "message" => 'locked', "message_extra" => 'article comments', 'redirect' => $article_link);

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
				$check_comment = $this->dbl->run("SELECT `comment_text` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `comment_id` DESC LIMIT 1", array((int) $_POST['aid']))->fetch();

				if ($check_comment && $check_comment['comment_text'] == $comment)
				{
					return array("error" => 1, "message" => 'double_comment', 'redirect' => $article_link);

					die();
				}

				// check if it's an empty comment
				if (empty($comment))
				{
					return array("error" => 1, "message" => 'empty', "message_extra" => 'text', 'redirect' => $article_link);

					die();
				}

				else
				{
					$mod_queue = $this->user->user_details['in_mod_queue'];
					$forced_mod_queue = $this->user->can('forced_mod_queue');
						
					$approved = 1;
					if ($mod_queue == 1 || $forced_mod_queue == true)
					{
						$approved = 0;
					}
			
					$comment = core::make_safe($comment);

					$article_id = (int) $_POST['aid'];

					// add the comment
					$this->dbl->run("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?, `approved` = ?", array($article_id, (int) $_SESSION['user_id'], core::$date, $comment, $approved));
						
					$new_comment_id = $this->dbl->new_id();
						
					// if they aren't keeping a subscription
					if (!isset($_POST['subscribe']))
					{
						$this->dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array((int) $_SESSION['user_id'], $article_id));
					}

					if ($approved == 1)
					{
						// update the news items comment count
						$this->dbl->run("UPDATE `articles` SET `comment_count` = (comment_count + 1) WHERE `article_id` = ?", array($article_id));

						// update the posting users comment count
						$this->dbl->run("UPDATE `users` SET `comment_count` = (comment_count + 1) WHERE `user_id` = ?", array((int) $_SESSION['user_id']));

						// check if they are subscribing
						if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
						{
							$emails = 0;
							if ($_POST['subscribe-type'] == 'sub-emails')
							{
								$emails = 1;
							}
							$this->subscribe($article_id, $emails);
						}
							
						$new_notification_id = $this->notifications->quote_notification($comment, $_SESSION['username'], $_SESSION['user_id'], array('type' => 'article_comment', 'thread_id' => $article_id, 'post_id' => $new_comment_id));

						/* gather a list of subscriptions for this article (not including yourself!)
						- Make an array of anyone who needs an email now
						- Additionally, send a notification to anyone subscribed
						*/
						$users_to_email = $this->dbl->run("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`email_options`, u.`display_comment_alerts` FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.user_id != ? AND NOT EXISTS (SELECT `user_id` FROM `user_block_list` WHERE `blocked_id` = ? AND `user_id` = s.user_id)", array($article_id, (int) $_SESSION['user_id'], (int) $_SESSION['user_id']))->fetch_all();
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
									$this->dbl->run("UPDATE `articles_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $email_user['user_id'], $article_id));
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

							// notify them, if they haven't been quoted and already given one and they have comment notifications turned on
							if ($email_user['display_comment_alerts'] == 1)
							{
								if (isset($new_notification_id['quoted_usernames']) && !in_array($email_user['username'], $new_notification_id['quoted_usernames']) || !isset($new_notification_id['quoted_usernames']))
								{
									$get_note_info = $this->dbl->run("SELECT `id`, `article_id`, `seen` FROM `user_notifications` WHERE `article_id` = ? AND `owner_id` = ? AND `type` != 'liked' AND `type` != 'quoted'", array($article_id, $email_user['user_id']))->fetch();

									if (!$get_note_info)
									{
										$this->dbl->run("INSERT INTO `user_notifications` SET `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `total` = 1, `type` = 'article_comment'", array($email_user['user_id'], (int) $_SESSION['user_id'], $article_id, $new_comment_id));
										$new_notification_id[$email_user['user_id']] = $this->dbl->new_id();
									}
									else if ($get_note_info)
									{
										if ($get_note_info['seen'] == 1)
										{
											// they already have one, refresh it as if it's literally brand new (don't waste the row id)
											$this->dbl->run("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `last_date` = ?, `total` = 1, `seen_date` = NULL, `comment_id` = ? WHERE `id` = ?", array($_SESSION['user_id'], core::$sql_date_now, $new_comment_id, $get_note_info['id']));
										}
										else if ($get_note_info['seen'] == 0)
										{
											// they haven't seen the last one yet, so only update the time and date
											$this->dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `total` = (total + 1) WHERE `id` = ?", array(core::$sql_date_now, $get_note_info['id']));
										}

										$new_notification_id[$email_user['user_id']] = $get_note_info['id'];
									}
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
								$this->dbl->run("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($article_id, $email_user['user_id']));
							}
						}

						// try to stop double postings, clear text
						unset($_POST['text']);

						// clear any comment or name left from errors
						unset($_SESSION['acomment']);

						$article_link = $this->article_link(array('date' => $title['date'], 'slug' => $title['slug'], 'additional' => 'page=' . $comment_page . '#r' . $new_comment_id));

						return array("error" => 0, "result" => 'done', 'redirect' => $article_link, 'article_id' => $_POST['aid'], 'page' => $comment_page, 'comment_id' => $new_comment_id);

						die();
					}
					else if ($approved == 0)
					{
						// note who did it
						$this->core->new_admin_note(array('completed' => 0, 'content' => ' has a comment that <a href="/admin.php?module=mod_queue&view=manage">needs approval.</a>', 'type' => 'mod_queue_comment', 'data' => $new_comment_id));

						// try to stop double postings, clear text
						unset($_POST['text']);

						// clear any comment or name left from errors
						unset($_SESSION['acomment']);
				
						return array("error" => 0, "result" => 'approvals', 'redirect' => $article_link, 'article_id' => $_POST['aid'], 'page' => 1);
						die();
					}
				}
			}
		}
	}
}
?>
