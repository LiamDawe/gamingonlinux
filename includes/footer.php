<?php
$templating->merge('footer');
$templating->block('footer');
$templating->set('url', url);
$templating->set('year', date('Y'));

// info for admins to see execution time and mysql queries per page
$debug = '';
if ($user->check_group(1) == true && core::config('show_debug') == 1)
{
	$timer_end = microtime(true);
	$time = number_format($timer_end - $timer_start, 3);

	$debug = "<br />Page generated in {$time} seconds, MySQL queries: {$db->counter}<br />";
	$debug .= $db->queries;
}
$templating->set('debug', $debug);

if (!empty(core::$user_graphs_js) || isset(core::$user_graphs_js))
{
	$svg_js = core::$user_graphs_js;
}
else {
	$svg_js = '';
}
$templating->set('svggraph_js', $svg_js);

echo $templating->output();

// close the mysql connection
$db = null;
?>
