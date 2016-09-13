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
}
?>
