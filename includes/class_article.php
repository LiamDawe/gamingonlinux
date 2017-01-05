<?php
class article_class
{
  function tagline_image($data)
  {
    $tagline_image = '';
    if ($data['article_top_image'] == 1)
    {
      $tagline_image = "<img alt src=\"".url."uploads/articles/topimages/{$data['article_top_image_filename']}\">";
    }
    if (!empty($data['tagline_image']))
    {
      $tagline_image = "<img alt src=\"".url."uploads/articles/tagline_images/{$data['tagline_image']}\">";
    }
    if ($data['article_top_image'] == 0 && empty($data['tagline_image']))
    {
      $tagline_image = "<img alt src=\"".url."uploads/articles/tagline_images/defaulttagline.png\">";
    }

    return $tagline_image;
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
        $previously_uploaded .= "<div class=\"box\"><div class=\"body group\"><div id=\"{$value['id']}\"><img src=\"/uploads/articles/article_images/{$value['filename']}\" class='imgList'><br />
        BBCode: <input id=\"img{$value['id']}\" type=\"text\" class=\"form-control\" value=\"{$bbcode}\" />
        <button class=\"btn\" data-clipboard-target=\"#img{$value['id']}\">Copy</button> <button data-bbcode=\"{$bbcode}\" class=\"add_button\">Add to editor</button> <button id=\"{$value['id']}\" class=\"trash\">Delete image</button>
        </div></div></div>";
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

  function process_categories($article_id)
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

  function process_game_assoc($article_id)
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

    // remove old article's image
    if ($article['article_top_image'] == 1)
    {
      unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/topimages/' . $article['article_top_image_filename']);
    }

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
    }

    if (isset($_GET['error']))
    {
      if ($_GET['temp_tagline'] == 1)
      {
        $file = core::config('path') . 'uploads/articles/tagline_images/temp/' . $_SESSION['uploads_tagline']['image_name'];
        $image_load = false;

        if (file_exists($file))
        {
          $tagline_image = "<div class=\"test\" id=\"{$_SESSION['uploads_tagline']['image_name']}\"><img src=\"".core::config('website_url')."uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" class='imgList'><br />
          BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
          <input type=\"hidden\" name=\"image_name\" value=\"{$_SESSION['uploads_tagline']['image_name']}\" />
          <a href=\"#\" id=\"{$_SESSION['uploads_tagline']['image_name']}\" class=\"trash_tagline\">Delete Image</a></div>";
        }
      }
    }

    return $tagline_image;
  }

  // this function will check over everything necessary for an article to be correctly done
  function check_article_inputs($return_page)
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
    if (!empty($_SESSION['uploads_tagline']['image_name']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
    {
      $temp_tagline = 1;
    }

    if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
    {
      $db->sqlquery("SELECT `tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
      $check_article = $db->fetch();
    }

    $title = strip_tags($_POST['title']);
    $tagline = trim($_POST['tagline']);
    $text = trim($_POST['text']);

    // check its set, if not hard-set it based on the article title
    if (isset($_POST['slug']) && !empty($_POST['slug']))
    {
      $slug = $core->nice_title($_POST['slug']);
    }
    else
    {
      $slug = $core->nice_title($_POST['title']);
    }

    // make sure its not empty
		if (empty($title) || empty($tagline) || empty($text))
		{
      $redirect = 1;

      $return_error = 'empty';
		}

		else if (strlen($tagline) < 100)
		{
      $redirect = 1;

      $return_error = 'shorttagline';
		}

		else if (strlen($tagline) > 400)
		{
      $redirect = 1;

      $return_error = 'taglinetoolong';
		}

		else if (strlen($title) < 10)
		{
      $redirect = 1;

      $return_error = 'shorttitle';
		}

		else if (isset($_POST['show_block']) && $editor_pick_count == core::config('editor_picks_limit'))
		{
      $redirect = 1;

      $return_error = 'toomanypicks';
    }

    // if it's an existing article, check tagline image
    // if database tagline_image is empty and there's no upload OR upload doesn't match (previous left over)
    else if ((isset($_POST['article_id'])) && (empty($check_article['tagline_image']) && !isset($_SESSION['uploads_tagline']) || isset($_SESSION['uploads_tagline']['image_rand']) && $_SESSION['uploads_tagline']['image_rand'] != $_SESSION['image_rand']))
    {
      $redirect = 1;

      $return_error = 'noimageselected';
    }

    // if it's a new article, check for tagline image in a simpler way
    // if there's no upload, or upload doesn't match
    else if ((!isset($_POST['article_id'])) && (!isset($_SESSION['uploads_tagline'])) || (isset($_SESSION['uploads_tagline']['image_rand']) && $_SESSION['uploads_tagline']['image_rand'] != $_SESSION['image_rand']))
    {
      $redirect = 1;

      $return_error = 'noimageselected';
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

      header("Location: $return_page&error=$return_error&self=$self&temp_tagline=$temp_tagline");
      die();
    }

    $content_array = array('title' => $title, 'text' => $text, 'tagline' => $tagline, 'slug' => $slug);

    return $content_array;
  }

  function subscribe($article_id)
  {
    global $db;

    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
    {
      $db->sqlquery("SELECT `user_id`, `article_id` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
      $count_subs = $db->num_rows();
      if ($count_subs == 0)
      {
        // find how they like to normally subscribe
        $db->sqlquery("SELECT `auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
        $get_email_type = $db->fetch();

        $db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?", array($_SESSION['user_id'], $article_id, $get_email_type['auto_subscribe_email']));
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
}
?>
