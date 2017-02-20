<?php
$templating->merge('admin_modules/admin_module_articles');
$templating->set('article_css', 'articleadmin');

$templating->set_previous('title', 'New article ' . $templating->get('title', 1)  , 1);

// only refresh the tagline image session identifier on a brand new page, if there's an error or we are publishing, we need it the same to compare
if (!isset($_GET['error']) && !isset($_POST['act']))
{
	$_SESSION['image_rand'] = rand();
	$article_class->reset_sessions();
}

if (isset ($_GET['error']))
{
	$extra = NULL;
	if (isset($_GET['extra']))
	{
		$extra = $_GET['extra'];
	}
	$message = $message_map->get_message($_GET['error'], $extra);
	$core->message($message['message'], NULL, $message['error']);
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
	if (isset($_GET['error']) || isset($_GET['dump']))
	{
		if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
		{
			$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
		}
	}
}

// get games list
$games_list = $article_class->display_game_assoc();

// if they have done it before set text and tagline
$title = '';
$slug = '';
$text = '';
$tagline = '';
$tagline_image = '';
$previously_uploaded = '';

if (isset($_GET['error']) || isset($_GET['dump']))
{
	$title = $_SESSION['atitle'];
	$tagline = $_SESSION['atagline'];
	$text = $_SESSION['atext'];
	$slug = $_SESSION['aslug'];

	// sort out previously uploaded images
	$previously_uploaded	= $article_class->display_previous_uploads();
}

$tagline_image = $article_class->display_tagline_image();

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
  $return_page = "admin.php?module=add_article&error=empty";
  if ($checked = $article_class->check_article_inputs($return_page))
  {
  	// show in the editors pick block section
  	$block = 0;
  	if (isset($_POST['show_block']))
  	{
  		$block = 1;
  	}

  	// since it's now up we need to add 1 to total article count, it now exists, yaay have a beer on me, just kidding get your wallet!
  	$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");

    $gallery_tagline_sql = $article_class->gallery_tagline();

  	$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0 $gallery_tagline_sql", array($_SESSION['user_id'], $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, core::$date));

  	$article_id = $db->grab_id();

  	$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `type` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, 'new_article_published', core::$date, $article_id));

  	// upload attached images
  	if (isset($_SESSION['uploads']))
  	{
  		foreach($_SESSION['uploads'] as $key)
  		{
  			$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($article_id, $key['image_name']));
  		}
  	}

  	$article_class->process_categories($article_id);

  	$article_class->process_game_assoc($article_id);

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
  	unset($_SESSION['uploads']);
  	unset($_SESSION['image_rand']);
  	unset($_SESSION['uploads_tagline']);
    unset($_SESSION['gallery_tagline_id']);
    unset($_SESSION['gallery_tagline_rand']);
    unset($_SESSION['gallery_tagline_filename']);

  	include(core::config('path') . 'includes/telegram_poster.php');

  	$article_link = article_class::get_link($article_id, $checked['slug']);

  	if (!isset($_POST['show_block']))
  	{
  		telegram($checked['title'] . ' ' . core::config('website_url') . $article_link);
  		header("Location: ".$article_link);
  	}
  	else if (isset($_POST['show_block']))
  	{
  		telegram($checked['title'] . ' ' . core::config('website_url') . $article_link);
  		header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id=".$article_id);
  	}
  }
}
?>
