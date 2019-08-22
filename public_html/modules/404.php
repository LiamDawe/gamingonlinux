<?php
http_response_code(404);
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('meta_data', '', 1);
$templating->set_previous('meta_description', '404 not found', 1);
$templating->set_previous('title', '404 not found', 1);

$templating->load('404');
$templating->block('main');
$templating->set('url', $core->config('website_url'));
?>
