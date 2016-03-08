<?php
$templating->merge('blocks/block_twitter');
$templating->block('menu');

if (isset($_SESSION['theme']) && $_SESSION['theme'] == 'light' || !isset($_SESSION['theme']))
{
	$embed = "<a class=\"twitter-timeline\" href=\"https://twitter.com/gamingonlinux\" data-widget-id=\"381375312019218432\" style=\"width: 400px\">Tweets by @gamingonlinux</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\"://platform.twitter.com/widgets.js\";fjs.parentNode.insertBefore(js,fjs);}}(document,\"script\",\"twitter-wjs\");</script>";
}

else
{
	$embed = "<a class=\"twitter-timeline\" href=\"https://twitter.com/gamingonlinux\" data-widget-id=\"456133589034209280\" style=\"width: 400px\">Tweets by @gamingonlinux</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\"://platform.twitter.com/widgets.js\";fjs.parentNode.insertBefore(js,fjs);}}(document,\"script\",\"twitter-wjs\");</script>";
}

$templating->set('embed', $embed);
