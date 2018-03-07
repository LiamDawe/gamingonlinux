<?php
$templating->set_previous('title', 'Linux game servers', 1);
$templating->set_previous('meta_description', 'Linux game servers', 1);

$templating->load('game_servers');

$templating->block('head', 'game_servers');

// setup a cache
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);

$query = "SELECT s.`id`, g.`name`, s.`connection_info`, s.`official` FROM `game_servers` s INNER JOIN `calendar` g ON g.id = s.game_id ORDER BY s.`id`, s.`official`";
$querykey = "KEY" . md5($query);

$get_servers = $mem->get($querykey); // check cache

if (!$get_servers) // there's no cache
{
	$get_servers = $dbl->run($query)->fetch_all();
	$mem->set($querykey, $get_servers, 3600); // cache for one hour
}

if ($get_servers)
{
	foreach ($get_servers as $servers)
	{
		$templating->block('server', 'game_servers');
		$templating->set('name', $servers['name']);
		
		$badge = '';
		if ($servers['official'] == 0)
		{
		$badge = '<span class="badge blue">Community Server</span>';
		}
		else if ($servers['official'] == 1)
		{
		$badge = '<span class="badge editor">Official GOL Server</span>';
		}
		$templating->set('badge', $badge);
		
		$templating->set('connection_info', $bbcode->parse_bbcode($servers['connection_info']));
	}
}
else
{
	$core->message("We aren't listing any servers at the moment, come back soon!");
}
