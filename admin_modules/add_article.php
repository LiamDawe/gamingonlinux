<?php
$templating->merge('admin_modules/admin_module_articles');
$templating->set('article_css', 'articleadmin');

$templating->set_previous('title', 'New article ' . $templating->get('title', 1)  , 1);

// only refresh the tagline image session identifier on a brand new page, if there's an error or we are publishing, we need it the same to compare
if ((isset($message_map::$error) && $message_map::$error == 0) && !isset($_POST['act']))
{
	$_SESSION['image_rand'] = rand();
	$article_class->reset_sessions();
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
	if (isset($_SESSION['error']) || isset($_GET['dump']))
	{
		if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
		{
			$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
		}
	}
}

$article_form_top = plugins::do_hooks('article_form_top');
$templating->set('article_form_top', $article_form_top);

// if they have done it before set text and tagline
$title = '';
$slug = '';
$text = '';
$tagline = '';
$tagline_image = '';
$previously_uploaded = '';

if ((isset($message_map::$error) && $message_map::$error == 1) || isset($_GET['dump']))
{
	$title = $_SESSION['atitle'];
	$tagline = $_SESSION['atagline'];
	$text = $_SESSION['atext'];
	$slug = $_SESSION['aslug'];

	// sort out previously uploaded images
	$previously_uploaded = $article_class->display_previous_uploads();
}

$tagline_image = $article_class->display_tagline_image();

$templating->set('tagline_image', $tagline_image);

$templating->set('title', $title);
$templating->set('slug', $slug);
$templating->set('tagline', $tagline);
$templating->set('text', $text);

$templating->set('categories_list', $categorys_list);
$templating->set('max_height', core::config('article_image_max_height'));
$templating->set('max_width', core::config('article_image_max_width'));

$db->sqlquery("SELECT `auto_subscribe_new_article` FROM `".$dbl->table_prefix."users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$grab_subscribe = $db->fetch();

$auto_subscribe = '';
if ($grab_subscribe['auto_subscribe_new_article'] == 1)
{
	$auto_subscribe = 'checked';
}

$core->editor(['name'=> 'text', 'content' => $text, 'article_editor' => 1, 'editor_id' => 'article_text']);

$templating->block('add_bottom', 'admin_modules/admin_module_articles');
$templating->set('website_url', core::config('website_url'));
$templating->set('previously_uploaded', $previously_uploaded);
$templating->set('subscribe_check', $auto_subscribe);

if (isset($_POST['act']) && $_POST['act'] == 'publish_now')
{
	$return_page = "/admin.php?module=add_article";
	article_class::publish_article(['return_page' => $return_page, 'type' => 'new_article', 'new_notification_type' => 'new_article_published']);
}
?>
