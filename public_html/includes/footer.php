<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: footer.');
}
$templating->load('footer');

// random hot articles
if (!isset(core::$current_module) || isset(core::$current_module) && (core::$current_module['module_file_name'] == 'home'))
{
	/*
	// top articles this month but not from the most recent 2 days to prevent showing what they've just seen on the home page
	*/
	$blocked_tags  = str_repeat('?,', count($user->blocked_tags) - 1) . '?';
	$top_article_query = "SELECT a.`article_id`, a.`title`, a.`slug`, a.`date`, a.`tagline_image`, a.`gallery_tagline`, t.`filename` as gallery_tagline_filename FROM `articles` a LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` WHERE a.`date` > UNIX_TIMESTAMP(NOW() - INTERVAL 1 MONTH) AND a.`date` < UNIX_TIMESTAMP(NOW() - INTERVAL 2 DAY) AND a.`views` > ? AND a.`show_in_menu` = 0 AND NOT EXISTS (SELECT 1 FROM article_category_reference c  WHERE a.article_id = c.article_id AND c.`category_id` IN ( $blocked_tags )) ORDER BY RAND() DESC LIMIT 6";

	$fetch_top3 = $dbl->run($top_article_query, array_merge([$core->config('hot-article-viewcount')], $user->blocked_tags))->fetch_all();

	if (is_array($fetch_top3))
	{
		$templating->block('random_top_articles', 'footer');
		$random_list = '';
		foreach ($fetch_top3 as $top_articles)
		{
			$tagline_image = $article_class->tagline_image($top_articles, 142, 250);

			$random_list .= '<div class="footer-article-flex-col-item"><div>'.$tagline_image.'</div><div><a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'])).'">'.$top_articles['title'].'</a></div></div>';
		}

		$templating->set('random_list', $random_list);
	}
}

$templating->block('footer');
$templating->set('jsstatic', JSSTATIC);
$templating->set('url', url);
$templating->set('year', date('Y'));

$article_rss = '';
if ($core->config('articles_rss') == 1)
{
	$article_rss = '<li><a title="Full article RSS" class="tooltip-top" href="'.$core->config('website_url').'article_rss.php" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/network-icons/white/rss-website.svg" width="30" height="30" /></a></li>
	<li><a title="Article title RSS" class="tooltip-top" href="'.$core->config('website_url').'article_rss.php?mini" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/network-icons/white/rss-website.svg" width="30" height="30" /></a></li>';
}
$templating->set('article_rss', $article_rss);

// don't set the rel tag for GOL's Mastodon on user profiles
$masto_rel = '';
if (!isset($_GET['module']) || isset($_GET['module']) && $_GET['module'] == 'home')
{
	$masto_rel = 'rel="me"';
}
$templating->set('masto_rel', $masto_rel);

$forum_rss = '';
if ($core->config('forum_rss') == 1)
{
	$forum_rss = '<li><a title="Forum RSS" class="tooltip-top" href="'.$core->config('website_url').'forum_rss.php" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/network-icons/white/rss-forum.svg" width="30" height="30" /></a></li>';
}
$templating->set('forum_rss', $forum_rss);

$ckeditor_js = '';
if ($core->current_page() == 'admin.php' || (isset($_GET['module']) && $_GET['module'] == 'submit_article'))
{
	$ckeditor_js = $templating->block_store('ckeditor', 'footer');
	$ckeditor_js = $templating->store_replace($ckeditor_js, array('jsstatic' => JSSTATIC));
}
$templating->set('ckeditor_js', $ckeditor_js);

// info for admins to see execution time and mysql queries per page
$debug = '';
if ($user->check_group(1) && $core->config('show_debug') == 1)
{
	$timer_end = microtime(true);
	$time = number_format($timer_end - $timer_start, 3);
	
	$total_queries = $dbl->counter;

	$debug = "<div class=\"box\"><div class=\"head\">Debug</div>
	<div class=\"body group\">Page generated in {$time} seconds</div>
	<div class=\"head\">MySQL queries: {$total_queries}</div>";
	foreach ($dbl->debug_queries as $key => $debug_query)
	{
		$debug .= '<div class="body group">(' . $key . ') ' . htmlentities($debug_query['query'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . "<br />" . $debug_query['time'] . '</div>';
	}
	//$debug .= print_r($dbl::$debug_queries, true);
	$debug .= print_r($_SESSION, true);
	$debug .= 'Stored user details: ' . print_r($user->user_details, true);
	$debug .= '</div>';
}
$templating->set('debug', $debug);

// user stat trending charts
$svg_js = '';
if (!empty(core::$user_chart_js) || isset(core::$user_chart_js))
{
	$svg_js = '<script src="'.JSSTATIC.'/Chart.min.js?v=2.9.3"></script>
	<script src="'.JSSTATIC.'/chartjs-plugin-trendline.min.js"></script>
	<script>var style = getComputedStyle(document.body);
		var textcolor = style.getPropertyValue("--svg-text-color");
		Chart.defaults.global.defaultFontColor = textcolor;
		' . core::$user_chart_js . '</script>';
}
$templating->set('user_chart_js', $svg_js);
echo $templating->output();

// close the mysql connection
$dbl = NULL;
?>
