<?php
plugins::register_hook('top_of_home_hook', 'latest_rss_item');

function hook_latest_rss_item($database, $core, $article_id)
{
	global $templating;
	
	$templating->merge_plugin('latest_lifeonlinux_article/template');
	$rss_block = $templating->block_store('main', 'latest_lifeonlinux_article/template');
	
	$content = $core->file_get_contents_curl('https://www.lifeonlinux.com/article_rss.php');
	
	if ($content !== false)
	{
		$x = new SimpleXmlElement($content);
		
		$count_items = 0;
		foreach($x->channel->item as $entry) 
		{
			if($count_items == 1 ) 
			{
				break;
			} 
			$rss_block = $templating->store_replace($rss_block, ['link' => $entry->link, 'title' => $entry->title]);
			$count_items++;
		}

		return $rss_block;
    }
}
