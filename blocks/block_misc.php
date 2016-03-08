<?php
// Article categorys block
$templating->merge('blocks/block_bottom_info');
$templating->block('list');

if ($config['pretty_urls'] == 1)
{
	$about_link = '/about-us/';
}
else
{
	$about_link = $config['path'] . 'index.php?module=about_us';
}
$templating->set('about_link', $about_link);

$templating->set('year', date("Y"));
