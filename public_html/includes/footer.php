<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: footer.');
}
$templating->load('footer');

// random hot articles
if (!isset(core::$current_module) || isset(core::$current_module) && (core::$current_module['module_file_name'] == 'home') && $core->current_page() == 'index.php')
{
	/*
	// top articles this month but not from the most recent 2 days to prevent showing what they've just seen on the home page
	*/
	$blocked_tags  = str_repeat('?,', count($user->blocked_tags) - 1) . '?';
	$top_article_query = "SELECT a.`article_id`, a.`title`, a.`slug`, a.`date`, a.`tagline_image`, a.`gallery_tagline`, a.comment_count, a.author_id, a.guest_username, t.`filename` as gallery_tagline_filename, u.`username`, u.`profile_address` FROM `articles` a LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` LEFT JOIN `users` u ON a.author_id = u.user_id WHERE a.`date` > UNIX_TIMESTAMP(NOW() - INTERVAL 1 MONTH) AND a.`date` < UNIX_TIMESTAMP(NOW() - INTERVAL 2 DAY) AND a.`views` > ? AND a.`show_in_menu` = 0 AND NOT EXISTS (SELECT 1 FROM article_category_reference c  WHERE a.article_id = c.article_id AND c.`category_id` IN ( $blocked_tags )) ORDER BY RAND() DESC LIMIT 4";

	$fetch_top = $dbl->run($top_article_query, array_merge([$core->config('hot-article-viewcount')], $user->blocked_tags))->fetch_all();

	$used_article_ids = array();

	if (is_array($fetch_top))
	{
		$templating->block('random_top_articles', 'footer');
		$random_list = '';
		$counter = 0;
		foreach ($fetch_top as $top_articles)
		{
			$counter++;
			$used_article_ids[] = $top_articles['article_id'];
			$tagline_image = $article_class->tagline_image($top_articles, 142, 250);

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

			$random_list .= '
			<article class="footer-article-flex-col-item">
					<div class="footer-article-image">
						<a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'])).'">'.$tagline_image.'</a>
					</div>
						<div class="footer-article-title"><a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'])).'">'.$top_articles['title'].'</a><div class="footer-article-meta">by '.$username.' <span class="comments-pip"><a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'], 'additional' => '#comments')).'">'.$article['comment_count'].'</a></span></div> 
					</div>
			</article>
			';
			
			if ($counter == 1)
			{
				$random_list .= '<div class="footer-grid-seperator"></div>';
			}
		}
		$templating->set('random_list', $random_list);
	}
}

// recent open source and linux distro news
if (!isset(core::$current_module) || isset(core::$current_module) && (core::$current_module['module_file_name'] == 'home') && $core->current_page() == 'index.php')
{
	$blocked_tags  = str_repeat('?,', count($user->blocked_tags) - 1) . '?';
	$used_ids_sql = str_repeat('?,', count($used_article_ids) - 1) . '?';

	$top_article_query = "SELECT DISTINCT a.`article_id`, a.`title`, a.`slug`, a.`date`, a.`tagline_image`, a.`gallery_tagline`, a.comment_count, a.author_id, a.guest_username, t.`filename` as gallery_tagline_filename, u.`username`, u.`profile_address` FROM `article_category_reference` r JOIN `articles` a ON a.`article_id` = r.`article_id` LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` LEFT JOIN `users` u ON a.author_id = u.user_id WHERE a.`date` > UNIX_TIMESTAMP(NOW() - INTERVAL 1 MONTH) AND NOT EXISTS (SELECT 1 FROM article_category_reference c  WHERE a.article_id = c.article_id AND c.`category_id` IN ( $blocked_tags )) AND r.category_id IN (11,192,170) AND a.`article_id` NOT IN ( $used_ids_sql ) ORDER BY RAND() DESC LIMIT 4";

	$fetch_top_os = $dbl->run($top_article_query, array_merge($user->blocked_tags, $used_article_ids))->fetch_all();

	if (is_array($fetch_top_os) && count($fetch_top_os) >= 3)
	{
		$templating->block('recent_open_source', 'footer');
		$random_list = '';
		$counter = 0;
		foreach ($fetch_top_os as $top_articles)
		{
			$counter++;
			$tagline_image = $article_class->tagline_image($top_articles, 142, 250);

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

			$random_list .= '
			<article class="footer-article-flex-col-item">
					<div class="footer-article-image">
						<a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'])).'">'.$tagline_image.'</a>
					</div>
						<div class="footer-article-title"><a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'])).'">'.$top_articles['title'].'</a><div class="footer-article-meta">by '.$username.' <span class="comments-pip"><a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'], 'additional' => '#comments')).'">'.$article['comment_count'].'</a></span></div> 
					</div>
			</article>
			';
			
			if ($counter == 1)
			{
				$random_list .= '<div class="footer-grid-seperator"></div>';
			}
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
