<?php
plugins::register_hook('footer_code', 'piwik_footer');

function hook_piwik_footer()
{
	global $templating;
	
	$templating->merge_plugin('piwik_footer/template');
    $piwik_html = $templating->block_store('piwik', 'piwik_footer/template');
	
	return $piwik_html;
}
