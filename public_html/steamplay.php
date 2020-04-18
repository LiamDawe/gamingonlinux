<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

// TWITCH ONLINE INDICATOR
if (!isset($_COOKIE['gol_announce_gol_twitch'])) // if they haven't dissmissed it
{
	$templating->load('twitch_bar');
	$templating->block('main', 'twitch_bar');
}

$templating->set_previous('title', 'Steam Play', 1);
$templating->set_previous('meta_description', 'Steam Play gaming', 1);
$templating->load('steamplay');
$templating->block('top', 'steamplay');

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message('goty', $_SESSION['message'], $extra);
}

$articles_res = $dbl->run("SELECT a.`author_id`, a.`article_id`, a.`title`, a.`slug`, a.`date`, a.`guest_username`, u.`username` FROM `article_category_reference` r JOIN `articles` a ON a.`article_id` = r.`article_id` LEFT JOIN `users` u ON u.user_id = a.author_id WHERE r.`category_id` = 158 AND a.active = 1 ORDER BY a.article_id DESC LIMIT 5")->fetch_all();
if ($articles_res)
{
	$article_list = '';
	
	foreach ($articles_res as $articles)
	{
		$article_link = $article_class->get_link($articles['article_id'], $articles['slug']);

		if ($articles['author_id'] == 0)
		{
			$username = $articles['guest_username'];
		}

		else
		{
			$username = "<a href=\"/profiles/{$articles['author_id']}\">" . $articles['username'] . '</a>';
		}

		$article_list .= '<li><a href="' . $article_link . '">'.$articles['title'].'</a> by '.$username.'<br />
		<small>'.$core->human_date($articles['date']).'</small></li>';
	}
	$templating->block('articles', 'steamplay');
	$templating->set('article_list', $article_list);
}

$templating->block('bottom', 'steamplay');

include(APP_ROOT . '/includes/footer.php');
