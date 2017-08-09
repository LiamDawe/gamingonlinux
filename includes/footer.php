<?php
$templating->load('footer');
$templating->block('footer');
$templating->set('url', url);
$templating->set('year', date('Y'));

$article_rss = '';
if ($core->config('articles_rss') == 1)
{
	$article_rss = '<li><a href="'.$core->config('website_url').'article_rss.php" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/social/white/rss-website.svg" width="30" height="30" /></a></li>';
}
$templating->set('article_rss', $article_rss);

$forum_rss = '';
if ($core->config('forum_rss') == 1)
{
	$forum_rss = '<li><a href="'.$core->config('website_url').'forum_rss.php" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/social/white/rss-forum.svg" width="30" height="30" /></a></li>';
}
$templating->set('forum_rss', $forum_rss);

// social and sharing icons
$social_icons = [
'twitter_username' => ['config' => 'twitter_username', 'icon' => 'twitter.svg'],
'telegram_group' => ['config' => 'telegram_group', 'icon' => 'telegram.svg'],
'discord' => ['config' => 'discord', 'icon' => 'discord.svg'],
'steam_group' => ['config' => 'steam_group', 'icon' => 'steam.svg'],
'twitch_channel' => ['config' => 'twitch_channel', 'icon' => 'twitch.svg'],
'youtube_channel' => ['config' => 'youtube_channel', 'icon' => 'youtube.svg'],
'gplus_page' => ['config' => 'gplus_page', 'icon' => 'google-plus.svg'],
'facebook_page' => ['config' => 'facebook_page', 'icon' => 'facebook.svg']
];

$social_output = '';
foreach ($social_icons as $social)
{
	$extra_url = '';
	if (!empty($core->config($social['config'])))
	{
		if ($social['config'] == 'twitter_username')
		{
			$extra_url = 'https://www.twitter.com/';
		}
		$social_output .= '<li><a href="'.$extra_url.$core->config($social['config']).'" target="_blank"><img alt src="'.$core->config('website_url').'templates/'.$core->config('template').'/images/social/white/'.$social['icon'].'" width="30" height="30" /></a></li>';
	}
}
$templating->set('social_icons', $social_output);

// info for admins to see execution time and mysql queries per page
$debug = '';
if ($user->check_group(1) && $core->config('show_debug') == 1)
{
	$timer_end = microtime(true);
	$time = number_format($timer_end - $timer_start, 3);
	
	$total_queries = $db->counter + $dbl->counter;

	$debug = "<br />Page generated in {$time} seconds, MySQL queries: {$total_queries}<br />";
	$debug .= $db->queries;
	$debug .= $dbl->debug_queries;
	$debug .= print_r($_SESSION, true);
	$debug .= 'Stored user details: ' . print_r($user->user_details, true);
}
$templating->set('debug', $debug);

// user stat trending charts
$svg_js = '';
if (!empty(core::$user_graphs_js) || isset(core::$user_graphs_js))
{
	$svg_js = core::$user_graphs_js;
}
$templating->set('user_graph_js', $svg_js);

// editor js
$editor_js = '';
if (!empty(core::$editor_js) || isset(core::$editor_js))
{
	$editor_js = '<script type="text/javascript">' . implode("\n", core::$editor_js) . '</script>';
}
$templating->set('editor_js', $editor_js);

echo $templating->output();

// close the mysql connection
$db = null;
$dbl = NULL;
?>
