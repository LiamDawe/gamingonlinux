<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('admin_blocks/admin_block_modules');
$templating->block('main');
