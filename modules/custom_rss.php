<?php
$templating->set_previous('title', 'RSS feed customiser', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com RSS feed customiser', 1);

$templating->load('custom_rss');
$templating->block('main', 'custom_rss');