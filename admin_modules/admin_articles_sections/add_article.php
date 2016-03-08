<?php
$temp_tagline = 0;
if (!empty($_POST['temp_tagline_image']))
{
	$temp_tagline = 1;
}

// count how many editors picks we have
$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");
$editor_pick_count = $db->num_rows();

$draft_tagline['tagline_image'] = '';
if (isset($_POST['check']) && $_POST['check'] == 'Draft')
{
	$db->sqlquery("SELECT `tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
	$draft_tagline = $db->fetch();
}

$slug = trim($_POST['slug']);

// make sure its not empty
if (empty($_POST['title']) || empty($_POST['tagline']) || empty($_POST['text']) || empty($slug))
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $slug;
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	if (isset($_POST['check']) && $_POST['check'] == 'Draft')
	{
		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=empty&temp_tagline=$temp_tagline";
	}

	else
	{
		$url = "admin.php?module=articles&view=add&aid={$_POST['article_id']}&error=empty&temp_tagline=$temp_tagline";
	}

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

	if (isset($_POST['check']) && $_POST['check'] == 'Draft')
	{
		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=shorttagline&temp_tagline=$temp_tagline";
	}

	else
	{
		$url = "admin.php?module=articles&view=add&aid={$_POST['article_id']}&error=shorttaline&temp_tagline=$temp_tagline";
	}

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

	if (isset($_POST['check']) && $_POST['check'] == 'Draft')
	{
		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=taglinetoolong&temp_tagline=$temp_tagline";
	}

	else
	{
		$url = "admin.php?module=articles&view=add&aid={$_POST['article_id']}&error=taglinetoolong&temp_tagline=$temp_tagline";
	}

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

	if (isset($_POST['check']) && $_POST['check'] == 'Draft')
	{
		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=shorttile&temp_tagline=$temp_tagline";
	}

	else
	{
		$url = "admin.php?module=articles&view=add&aid={$_POST['article_id']}&error=shorttile&temp_tagline=$temp_tagline";
	}

	header("Location: $url");
	die();
}

// if they try to make it an editor pick, and there's too many already
else if (isset($_POST['show_block']) && $editor_pick_count == 3)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $slug;
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	if (isset($_POST['check']) && $_POST['check'] == 'Draft')
	{
		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=toomanypicks&temp_tagline=$temp_tagline";
	}

	else
	{
		$url = "admin.php?module=articles&view=add&aid={$_POST['article_id']}&error=toomanypicks&temp_tagline=$temp_tagline";
	}

	header("Location: $url");
	die();
}

// if they aren't uploading a tagline image on a brand new article
else if (!isset($_SESSION['uploads_tagline']) && !isset($_POST['draft']))
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $slug;
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	if (isset($_POST['check']) && $_POST['check'] == 'Draft')
	{
		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=noimageselected&temp_tagline=$temp_tagline";
	}

	else
	{
		$url = "admin.php?module=articles&view=add&aid={$_POST['article_id']}&error=noimageselected&temp_tagline=$temp_tagline";
	}

	header("Location: $url");
	die();
}

// if it's a draft and there's no uploaded tagline image, and no stored image already
else if (empty($draft_tagline['tagline_image']) && !isset($_SESSION['uploads_tagline']) && isset($_POST['draft']) && $_POST['draft'] == 1)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $slug;
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	header("Location: admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=noimageselected&temp_tagline=$temp_tagline");
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

	// doubly make sure it's nice
	$slug = core::nice_title($_POST['slug']);

	$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `tagline_image` = ?", array($_SESSION['user_id'], $title, $slug, $tagline, $text, $block, $core->date, $draft_tagline['tagline_image']));

	$article_id = $db->grab_id();

	$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `created` = ?, `action` = ?, `completed_date` = ?, `article_id` = ?", array($core->date, "{$_SESSION['username']} published a new article.", $core->date, $article_id));

	// upload attached images
	if (isset($_SESSION['uploads']))
	{
		foreach($_SESSION['uploads'] as $key)
		{
			$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($article_id, $key['image_name']));
		}
	}

	// process category tags
	if (isset($_POST['categories']))
	{
		foreach($_POST['categories'] as $category)
		{
			$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($article_id, $category));
		}
	}

	// move new uploaded tagline image, and save it to the article
	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name']);
	}

	// remove the draft article as we have published it from a draft and we created a new article
	if (isset($_POST['draft']) && $_POST['draft'] == 1)
	{
		$db->sqlquery("DELETE FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
	}

	// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
	unset($_SESSION['atitle']);
	unset($_SESSION['aslug']);
	unset($_SESSION['atagline']);
	unset($_SESSION['atext']);
	unset($_SESSION['acategories']);
	unset($_SESSION['tagerror']);
	unset($_SESSION['uploads']);
	unset($_SESSION['image_rand']);
	unset($_SESSION['uploads_tagline']);

	if (core::config('pretty_urls') == 1 && !isset($_POST['show_block']))
	{
		header("Location: /articles/" . $_POST['slug'] . '.' . $article_id);
	}
	else if (core::config('pretty_urls') == 1 && isset($_POST['show_block']))
	{
		header("Location: " . core::config('path') . "admin.php?module=featured&view=add&article_id={$article_id}");
	}
	else
	{
		if (!isset($_POST['show_block']))
		{
			header("Location: " . core::config('path') . "index.php?module=articles_full&aid={$article_id}&title={$_POST['slug']}");
		}
		else
		{
			header("Location: " . core::config('path') . "admin.php?module=featured&view=add&article_id={$article_id}");
		}
	}
}
