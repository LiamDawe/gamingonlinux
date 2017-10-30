<?php
$templating->load('admin_blocks/admin_block_sales');
$templating->block('main');

// count submitted bundles
$bundle_count = $dbl->run("SELECT COUNT(*) FROM `sales_bundles` WHERE `approved` = 0")->fetchOne();
if ($bundle_count > 0)
{
	$templating->set('bundle_count', "<span class=\"badge badge-important\">$bundle_count</span>");
}
else if ($bundle_count == 0)
{
	$templating->set('bundle_count', "(0)");
}