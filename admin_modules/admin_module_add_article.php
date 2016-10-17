<?php
$templating->merge('admin_modules/admin_module_articles');
$templating->set('article_css', 'articleadmin');

$templating->set_previous('title', 'New article ' . $templating->get('title', 1)  , 1);

// only refresh the tagline image session identifier on a brand new page, if there's an error or we are publishing, we need it the same to compare
if (!isset($_GET['error']) && !isset($_POST['act']))
{
  $_SESSION['image_rand'] = rand();
}

if (isset ($_GET['error']))
{
  if ($_GET['error'] == 'empty')
  {
    $core->message('You have to fill in a title, tagline and text!', NULL, 1);
  }

  if ($_GET['error'] == 'categories')
  {
    $core->message('You have to give the article at least one category tag!', NULL, 1);
  }

  else if ($_GET['error'] == 'shorttagline')
  {
    $core->message('The tagline was too short, it needs to be at least 100 characters to be informative!', NULL, 1);
  }

  else if ($_GET['error'] == 'taglinetoolong')
  {
    $core->message('The tagline was too long, it needs to be 400 characters or less!', NULL, 1);
  }

  else if ($_GET['error'] == 'shorttitle')
  {
    $core->message('The title was too short, make it informative!', NULL, 1);
  }

  else if ($_GET['error'] == 'toomanypicks')
  {
    $core->message('There are already 3 articles set as editor picks!', NULL, 1);
  }

  else if ($_GET['error'] == 'noimageselected')
  {
    $core->message('You didn\'t select a tagline image to upload with the article, all articles must have one!', NULL, 1);
  }
}

$templating->block('add', 'admin_modules/admin_module_articles');

$templating->merge('admin_modules/article_form');
$templating->block('full_editor', 'admin_modules/article_form');
$templating->set('main_formaction', '<form method="post" name="article-form" action="'.core::config('website_url').'admin.php?module=articles" enctype="multipart/form-data">');
$templating->set('max_filesize', core::readable_bytes(core::config('max_tagline_image_filesize')));

// get categorys
$categorys_list = '';
$db->sqlquery("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC");
while ($categorys = $db->fetch())
{
  if (isset($_GET['error']))
  {
    if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
    {
      $categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
    }

    else
    {
      $categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
    }
  }

  else
  {
    $categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
  }
}

// get games list
$games_list = $article_class->sort_game_assoc();

// if they have done it before set text and tagline
$title = '';
$slug = '';
$text = '';
$tagline = '';
$tagline_image = '';
$previously_uploaded = '';

if (isset($_GET['error']))
{
  $title = $_SESSION['atitle'];
  $tagline = $_SESSION['atagline'];
  $text = $_SESSION['atext'];
  $slug = $_SESSION['aslug'];

  if ($_GET['temp_tagline'] == 1)
  {
    $file = core::config('path') . 'uploads/articles/tagline_images/temp/' . $_SESSION['atagline_image'];
    $image_load = false;

    if (file_exists($file))
    {
      $tagline_image = "<div class=\"test\" id=\"{$_SESSION['atagline_image']}\"><img src=\"".core::config('website_url')."uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['atagline_image']}\" class='imgList'><br />
      BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
      <input type=\"hidden\" name=\"image_name\" value=\"{$_SESSION['atagline_image']}\" />
      <a href=\"#\" id=\"{$_SESSION['atagline_image']}\" class=\"trash_tagline\">Delete Image</a></div>";
    }
  }
  // sort out previously uploaded images
  $previously_uploaded	= $article_class->previous_uploads();
}

$templating->set('tagline_image', $tagline_image);

$templating->set('title', $title);
$templating->set('slug', $slug);
$templating->set('tagline', $tagline);
$templating->set('text', $text);

$templating->set('categories_list', $categorys_list);
$templating->set('games_list', $games_list);
$templating->set('max_height', core::config('article_image_max_height'));
$templating->set('max_width', core::config('article_image_max_width'));

$db->sqlquery("SELECT `auto_subscribe_new_article` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$grab_subscribe = $db->fetch();

$auto_subscribe = '';
if ($grab_subscribe['auto_subscribe_new_article'] == 1)
{
  $auto_subscribe = 'checked';
}

$core->editor('text', $text, 1);

$templating->block('add_bottom', 'admin_modules/admin_module_articles');
$templating->set('previously_uploaded', $previously_uploaded);
$templating->set('subscribe_check', $auto_subscribe);

if (isset($_POST['act']) && $_POST['act'] == 'publish_now')
{
  $temp_tagline = 0;
  if (!empty($_SESSION['uploads_tagline']['image_name']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
  {
  	$temp_tagline = 1;
  }

  // count how many editors picks we have
  $db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");
  $editor_pick_count = $db->num_rows();

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
  if (empty($_POST['title']) || empty($_POST['tagline']) || empty($_POST['text']) || empty($slug))
  {
  	$_SESSION['atitle'] = $_POST['title'];
  	$_SESSION['aslug'] = $slug;
  	$_SESSION['atagline'] = $_POST['tagline'];
  	$_SESSION['atext'] = $_POST['text'];
  	$_SESSION['acategories'] = $_POST['categories'];
  	$_SESSION['agames'] = $_POST['games'];
    $_SESSION['atagline_image'] = $_SESSION['uploads_tagline']['image_name'];

  	$url = "admin.php?module=add_article&error=empty&temp_tagline=$temp_tagline";

  	header("Location: $url");
  	die();
  }

  // make sure tagline isn't too short
  else if (strlen($_POST['tagline']) < 100)
  {
  	$_SESSION['atitle'] = $_POST['title'];
  	$_SESSION['aslug'] = $slug;
  	$_SESSION['atagline'] = $_POST['tagline'];
  	$_SESSION['atext'] = $_POST['text'];
  	$_SESSION['acategories'] = $_POST['categories'];
  	$_SESSION['agames'] = $_POST['games'];
    $_SESSION['atagline_image'] = $_SESSION['uploads_tagline']['image_name'];

  	$url = "admin.php?module=add_article&error=shorttagline&temp_tagline=$temp_tagline";

  	header("Location: $url");
  	die();
  }

  // if tagline is too long
  else if (strlen($_POST['tagline']) > 400)
  {
  	$_SESSION['atitle'] = $_POST['title'];
  	$_SESSION['aslug'] = $slug;
  	$_SESSION['atagline'] = $_POST['tagline'];
  	$_SESSION['atext'] = $_POST['text'];
  	$_SESSION['acategories'] = $_POST['categories'];
  	$_SESSION['agames'] = $_POST['games'];
    $_SESSION['atagline_image'] = $_SESSION['uploads_tagline']['image_name'];

  	$url = "admin.php?module=add_article&error=taglinetoolong&temp_tagline=$temp_tagline";

  	header("Location: $url");
  	die();
  }

  // if tagline is too long
  else if (empty($_POST['categories']))
  {
    $_SESSION['atitle'] = $_POST['title'];
    $_SESSION['aslug'] = $slug;
    $_SESSION['atagline'] = $_POST['tagline'];
    $_SESSION['atext'] = $_POST['text'];
    $_SESSION['acategories'] = $_POST['categories'];
    $_SESSION['agames'] = $_POST['games'];
    $_SESSION['atagline_image'] = $_SESSION['uploads_tagline']['image_name'];

    $url = "admin.php?module=add_article&error=categories&temp_tagline=$temp_tagline";

    header("Location: $url");
    die();
  }

  // if title is too short
  else if (strlen($_POST['title']) < 10)
  {
  	$_SESSION['atitle'] = $_POST['title'];
  	$_SESSION['aslug'] = $slug;
  	$_SESSION['atagline'] = $_POST['tagline'];
  	$_SESSION['atext'] = $_POST['text'];
  	$_SESSION['acategories'] = $_POST['categories'];
  	$_SESSION['agames'] = $_POST['games'];
    $_SESSION['atagline_image'] = $_SESSION['uploads_tagline']['image_name'];

  	$url = "admin.php?module=add_article&error=shorttile&temp_tagline=$temp_tagline";

  	header("Location: $url");
  	die();
  }

  // if they try to make it an editor pick, and there's too many already
  else if (isset($_POST['show_block']) && $editor_pick_count == core::config('editor_picks_limit'))
  {
  	$_SESSION['atitle'] = $_POST['title'];
  	$_SESSION['aslug'] = $slug;
  	$_SESSION['atagline'] = $_POST['tagline'];
  	$_SESSION['atext'] = $_POST['text'];
  	$_SESSION['acategories'] = $_POST['categories'];
  	$_SESSION['agames'] = $_POST['games'];
    $_SESSION['atagline_image'] = $_SESSION['uploads_tagline']['image_name'];

  	$url = "admin.php?module=add_article&error=toomanypicks&temp_tagline=$temp_tagline";

  	header("Location: $url");
  	die();
  }

  // if they aren't uploading a tagline image on a brand new article
  else if ((!isset($_SESSION['uploads_tagline'])) || (isset($_SESSION['uploads_tagline']['image_rand']) && $_SESSION['uploads_tagline']['image_rand'] != $_SESSION['image_rand']))
  {
  	$_SESSION['atitle'] = $_POST['title'];
  	$_SESSION['aslug'] = $slug;
  	$_SESSION['atagline'] = $_POST['tagline'];
  	$_SESSION['atext'] = $_POST['text'];
  	$_SESSION['acategories'] = $_POST['categories'];
  	$_SESSION['agames'] = $_POST['games'];
    $_SESSION['atagline_image'] = $_SESSION['uploads_tagline']['image_name'];

  	$url = "admin.php?module=add_article&error=noimageselected&temp_tagline=$temp_tagline";

  	header("Location: $url");
  	die();
  }

  else
  {
  	// show in the editors pick block section
  	$block = 0;
  	if (isset($_POST['show_block']))
  	{
  		$block = 1;
  	}

  	$text = trim($_POST['text']);
  	$tagline = trim($_POST['tagline']);

  	// since it's now up we need to add 1 to total article count, it now exists, yaay have a beer on me, just kidding get your wallet!
  	$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");

  	$title = strip_tags($_POST['title']);

  	$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0", array($_SESSION['user_id'], $title, $slug, $tagline, $text, $block, core::$date));

  	$article_id = $db->grab_id();

  	$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `created` = ?, `action` = ?, `completed_date` = ?, `article_id` = ?", array(core::$date, "{$_SESSION['username']} published a new article.", core::$date, $article_id));

  	// upload attached images
  	if (isset($_SESSION['uploads']))
  	{
  		foreach($_SESSION['uploads'] as $key)
  		{
  			$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($article_id, $key['image_name']));
  		}
  	}

  	$article_class->process_categories($article_id);

  	// process game associations
  	if (isset($_POST['games']) && !empty($_POST['games']))
  	{
  		foreach($_POST['games'] as $game)
  		{
  			$db->sqlquery("INSERT INTO `article_game_assoc` SET `article_id` = ?, `game_id` = ?", array($article_id, $game));
  		}
  	}

  	// move new uploaded tagline image, and save it to the article
  	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
  	{
  		$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name']);
  	}

  	// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
  	unset($_SESSION['atitle']);
  	unset($_SESSION['aslug']);
  	unset($_SESSION['atagline']);
  	unset($_SESSION['atext']);
  	unset($_SESSION['acategories']);
  	unset($_SESSION['agame']);
  	unset($_SESSION['tagerror']);
  	unset($_SESSION['uploads']);
  	unset($_SESSION['image_rand']);
  	unset($_SESSION['uploads_tagline']);

  	include(core::config('path') . 'includes/telegram_poster.php');

  	if (core::config('pretty_urls') == 1 && !isset($_POST['show_block']))
  	{
  		telegram($title . ' ' . core::config('website_url') . "articles/" . $slug . '.' . $article_id);
  		header("Location: /articles/" . $slug . '.' . $article_id);
  	}
  	else if (core::config('pretty_urls') == 1 && isset($_POST['show_block']))
  	{
  		telegram($title . ' ' . core::config('website_url') . "articles/" . $slug . '.' . $article_id);
  		header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$article_id}");
  	}
  	else
  	{
  		if (!isset($_POST['show_block']))
  		{
  			telegram($title . ' ' . core::config('website_url') . "index.php?module=articles_full&aid={$article_id}&title={$slug}");
  			header("Location: " . core::config('website_url') . "index.php?module=articles_full&aid={$article_id}&title={$slug}");
  		}
  		else
  		{
  			telegram($title . ' ' . core::config('website_url') . "index.php?module=articles_full&aid={$article_id}&title={$slug}");
  			header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$article_id}");
  		}
  	}
  }
}
?>
