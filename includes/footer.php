<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('footer');
$templating->block('footer');
$templating->set('url', url);
$templating->set('year', date('Y'));

$article_rss = '';
if ($core->config('articles_rss') == 1)
{
	$article_rss = '<li><a title="Full article RSS" class="tooltip-top" href="'.$core->config('website_url').'article_rss.php" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/network-icons/white/rss-website.svg" width="30" height="30" /></a></li>
	<li><a title="Article title RSS" class="tooltip-top" href="'.$core->config('website_url').'article_rss.php?mini" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/network-icons/white/rss-website.svg" width="30" height="30" /></a></li>';
}
$templating->set('article_rss', $article_rss);

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
		$debug .= '<div class="body group">(' . $key . ') ' . $debug_query . '</div>';
	}
	//$debug .= print_r($dbl::$debug_queries, true);
	$debug .= print_r($_SESSION, true);
	$debug .= 'Stored user details: ' . print_r($user->user_details, true);
	$debug .= '</div>';
}
$templating->set('debug', $debug);

// editor js
$editor_js = '';
if (!empty(core::$editor_js) || isset(core::$editor_js))
{
	$editor_js = '<script type="text/javascript">' . implode("\n", core::$editor_js) . '</script>';
}
$templating->set('editor_js', $editor_js);

// user stat trending charts
$svg_js = '';
if (!empty(core::$user_chart_js) || isset(core::$user_chart_js))
{
	$svg_js = core::$user_chart_js;
}
$templating->set('user_chart_js', $svg_js);


echo $templating->output();

// close the mysql connection
$dbl = NULL;
?>
