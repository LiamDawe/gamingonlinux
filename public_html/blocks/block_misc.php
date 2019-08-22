<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
// Article categorys block
$templating->load('blocks/block_bottom_info');
$templating->block('list');

$templating->set('url', $core->config('website_url'));

$templating->set('year', date("Y"));
