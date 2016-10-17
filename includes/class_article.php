<?php
class article_class
{
  function previous_uploads($article_id = NULL)
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

  function sort_game_assoc($article_id = NULL)
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
    $db->sqlquery("SELECT * FROM `calendar` ORDER BY `name` ASC");
    while ($games = $db->fetch())
    {
      if (isset($_GET['error']))
      {
        if (!empty($_SESSION['agames']) && in_array($games['id'], $_SESSION['agames']))
        {
          $games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
        }

        else
        {
          $games_list .= "<option value=\"{$games['id']}\">{$games['name']}</option>";
        }
      }

      else
      {

        if (($article_id != NULL) && isset($games_check_array) && in_array($games['id'], $games_check_array))
        {
          $games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
        }

        else
        {
          $games_list .= "<option value=\"{$games['id']}\">{$games['name']}</option>";
        }
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

  function article_game_assoc($article_id)
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
    $db->sqlquery("DELETE FROM `admin_notifications` WHERE `article_id` = ?", array($article['article_id']));
    $db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `article_id` = ?, `action` = ?, `created` = ?, `completed_date` = ?", array($article_id, "{$_SESSION['username']} deleted the article: {$article['title']}", core::$date, core::$date));

    // remove old article's image
    if ($check['article_top_image'] == 1)
    {
      unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/topimages/' . $article['article_top_image_filename']);
    }

    if (!empty($check['tagline_image']))
    {
      unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']);
      unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']);
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
}
?>
