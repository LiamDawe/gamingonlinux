<?php
/*
HTML is sanatized in files directly, not here
*/
function do_charts($body)
{
	global $db;

	preg_match_all("/\[chart\](.+?)\[\/chart\]/is", $body, $matches);

	$chart_id = 0;

	$how_many = sizeof($matches[1]);

	require_once(core::config('path') . 'includes/SVGGraph/SVGGraph.php');

	foreach ($matches[1] as $id)
	{
		$db->sqlquery("SELECT `name`, `h_label` FROM `charts` WHERE `id` = ?", array($id));
		$chart_info = $db->fetch();

		// set the right labels to the right data
		$labels = array();
		$db->sqlquery("SELECT `label_id`, `name` FROM `charts_labels` WHERE `chart_id` = ?", array($id));
		$get_labels = $db->fetch_all_rows();

    foreach ($get_labels as $label_loop)
    {
        $db->sqlquery("SELECT `data`, `label_id` FROM `charts_data` WHERE `chart_id` = ?", array($id));
        while ($get_data = $db->fetch())
        {
            if ($label_loop['label_id'] == $get_data['label_id'])
            {
                $labels[$label_loop['name']] = $get_data['data'];
            }
        }
    }

		$settings = array('graph_title' => $chart_info['name'], 'auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside', 'show_grid_h' => false, 'label_h' => $chart_info['h_label'], 'minimum_grid_spacing_h' => 20);
		$graph = new SVGGraph(400, 300, $settings);
		$colours = array(array('rgb(151,187,205):0.90','rgb(113,140,153):'), array('rgb(152,125,113):0.90','rgb(114,93,84)'));
		$graph->colours = $colours;

    $graph->Values($labels);
    $get_graph = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';

		$body = preg_replace("/\[chart\]($id)\[\/chart\]/is", $get_graph, $body);
	}
	return $body;
}

function replace_giveaways($text, $giveaway_id)
{
	global $db;

	$key_claim = '';

	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$get_name = $db->sqlquery("SELECT `id`, `game_name`FROM `game_giveaways` WHERE `id` = ?", array($giveaway_id));
		$game_info = $get_name->fetch();

		$get_keys = $db->sqlquery("SELECT COUNT(id) as counter FROM `game_giveaways_keys` WHERE `claimed` = 0 AND `game_id` = ?", array($giveaway_id));
		$keys_left = $get_keys->fetch();

		$grab_your_key = $db->sqlquery("SELECT COUNT(game_key) as counter, `game_key` FROM `game_giveaways_keys` WHERE `claimed_by_id` = ? AND `game_id` = ? GROUP BY `game_key`", array($_SESSION['user_id'], $giveaway_id));
		$your_key = $grab_your_key->fetch();

		// they have a key already
		if ($your_key['counter'] == 1)
		{
			$key_claim = '[b]Grab a key[/b]<br />You already claimed one: ' . $your_key['game_key'];
		}
		// they do not have a key
		else if ($your_key['counter'] == 0)
		{
			if ($keys_left['counter'] == 0)
			{
				$key_claim = '[b]Grab a key[/b]<br />All keys are now gone, sorry!';
			}
			else if ($keys_left['counter'] > 0)
			{
				$key_claim = '[b]Grab a key[/b] (keys left: '.$keys_left['counter'].')<br /><div id="key-area"><a id="claim_key" data-game-id="'.$game_info['id'].'" href="#">click here to claim</a></div>';
			}
		}

		$text = preg_replace("/\[giveaway\]".$giveaway_id."\[\/giveaway\]/is", $key_claim, $text);
	}
	else
	{
		$text = preg_replace("/\[giveaway\]".$giveaway_id."\[\/giveaway\]/is", '[b]Grab a key[/b]<br />You must be logged in to grab a key!', $text);
	}

	return $text;
}

function replace_timer($matches)
{
	if (preg_match("/\*time-only/is", $matches[0]))
	{
		$rest = substr($matches[1], 0, -10);
		return '<div id="'.$rest.'"></div><script type="text/javascript">var ' . $rest . ' = moment.tz("'.$matches[2].'", "UTC"); $("#'.$rest.'").countdown('.$rest.'.toDate(),function(event) {$(this).text(event.strftime(\'%H:%M:%S\'));});</script>';
	}
	else
	{
		return '<div id="'.$matches[1].'"></div><script type="text/javascript">var ' . $matches[1] . ' = moment.tz("'.$matches[2].'", "UTC"); $("#'.$matches[1].'").countdown('.$matches[1].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
	}
}

function do_timers($body)
{
	$body = preg_replace_callback("/\[timer=(.+?)](.+?)\[\/timer]/is", 'replace_timer', $body);

	return $body;
}

function remove_bbcode($string)
{
	$pattern = '/\[[^\]]+\]/si'; //More effecient striping regex thx to tadzik
	$replace = '';
	return preg_replace($pattern, $replace, $string);
}

// this is the replacement function the the article dump module in admin, it sorts the different sections and splits them
function article_dump($dump)
{
	$sections = array();

	// title
	$pattern = '/\=title\=(.+?)\=title\=/is';

	preg_match($pattern, $dump, $title);
	$sections['title'] = $title[1];

	// tags
	$pattern = '/\=tags\=(.+?)\=tags\=/is';

	preg_match($pattern, $dump, $tags);
	$sections['tags'] = $tags[1];

	// tagline
	$pattern = '/\=tagline\=(.+?)\=tagline\=/is';

	preg_match($pattern, $dump, $tagline);
	$sections['tagline'] = $tagline[1];

	// text
	$pattern = '/\=text\=(.+?)\=text\=/is';

	preg_match($pattern, $dump, $text);
	$sections['text'] = $text[1];

	return $sections;
}

// replace specific-user quotes, called by quotes()
function replace_quotes($matches)
{
	global $db;

	$find_quoted = $db->sqlquery("SELECT `username`, `user_id` FROM `users` WHERE `username` = ?", array($matches[1]));
	if ($db->num_rows() == 1)
	{
		$get_quoted = $find_quoted->fetch();
		if (core::config('pretty_urls') == 1)
		{
			$profile_link = '/profiles/' . $get_quoted['user_id'];
		}
		else
		{
			$profile_link = '/index.php?module=profile&user_id=' . $get_quoted['user_id'];
		}
		return '<blockquote><cite><a href="'.$profile_link.'">'.$matches[1].'</a></cite>'.$matches[2].'</blockquote>';
	}
	else
	{
		return '<blockquote><cite>'.$matches[1].'</cite>'.$matches[2].'</blockquote>';
	}
}

// find all quotes
function quotes($body)
{
	// Quote on its own, do these first so they don't get in the way
	$pattern = '/\[quote\](.+?)\[\/quote\]/is';
	$replace = "<blockquote><cite>Quote</cite>$1</blockquote>";

	while(preg_match($pattern, $body))
	{
		$body = preg_replace($pattern, $replace, $body);
	}

	// Quoting an actual person, book or whatever
	$pattern = '~\[quote=([^]]+)]([^[]*(?:\[(?!/?quote\b)[^[]*)*)\[/quote]~i';
	do
	{
		$body = preg_replace_callback($pattern, 'replace_quotes', $body, -1, $count);
	} while ($count);

	return $body;
}

// this is to only show to people who are logged in
function logged_in_code($body)
{
	// Quoting an actual person, book or whatever
	$pattern = '/\[users-only\](.+?)\[\/users-only\]/is';
	$replace = "$1";

	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
	{
		while(preg_match($pattern, $body))
		{
			$body = preg_replace($pattern, $replace, $body);
		}
	}
	else
	{
		$body = preg_replace($pattern, '<strong>You must be logged in to see this section!</strong>', $body);
	}

	return $body;
}

function bbcode($body, $article = 1, $parse_links = 1, $tagline_image = NULL, $gallery_tagline = NULL)
{
	//  get rid of empty BBCode, is there a point in having excess markup?
	$body = preg_replace("`\[(b|i|s|u|url|mail|spoiler|img|quote|code|color|youtube)\]\[/(b|i|s|u|url|spoiler|mail|img|quote|code|color|youtube)\]`",'',$body);

	$body = logged_in_code($body);

	if (preg_match_all("/\[giveaway\](.+?)\[\/giveaway\]/is", $body, $giveaway_matches))
	{
		foreach ($giveaway_matches[1] as $match)
		{
			$body = replace_giveaways($body, $match);
		}
	}

	$body = do_timers($body);

	// Array for tempory storing codeblock contents
	$codeBlocks = [];

	// make all bbcode brackets inside the code tags be the correct html entities to prevent the bbcode inside from parsing
	$body = preg_replace_callback("/\[code\](.+?)\[\/code\]/is",
		function($matches) use(&$codeBlocks)
		{
			$codeBlocks[] = str_replace(array('[', ']'), array('&#91;', '&#93;'), "<code class='bbcodeblock'>" . $matches[1] . '</code>');
			end($codeBlocks); //Move array pointer to the end
			$k = key($codeBlocks); //Get the last inserted number
			reset($codeBlocks); //Reset the array pointer to the start
			return "!!@codeblock".$k."!!";
		},
		$body);

	// replace charts bbcode with the pretty stuff
	$body = do_charts($body);

	$body = pc_info($body);

	if ($tagline_image != NULL)
	{
		$find = "[img]tagline-image[/img]";

		if ($gallery_tagline == NULL)
		{
			$file_path = "uploads/articles/tagline_images/";
		}
		else
		{
			$file_path = "uploads/tagline_gallery/";
		}
		$replace = "<img itemprop=\"image\" src=\"" . core::config('website_url') . $file_path . $tagline_image . "\" class=\"img-responsive\" alt=\"tagline-image\" />";

		$body = str_replace($find, $replace, $body);
	}

	if ($parse_links == 1)
	{
		$URLRegex = '/(?:(?<!(\[\/url\]|\[\/url=))(\s|^))'; // No [url]-tag in front and is start of string, or has whitespace in front
		$URLRegex.= '(';                                    // Start capturing URL
		$URLRegex.= '(https?|ftps?|ircs?):\/\/';            // Protocol
		$URLRegex.= '[\w\d\.\/#\_\-\?:=]+';                        // Any non-space character
		$URLRegex.= ')';                                    // Stop capturing URL
		$URLRegex.= '(?:(?<![.,;!?:\"\'()-])(\/|\[|\s|\.?$))/i';      // Doesn't end with punctuation and is end of string, or has whitespace after

		$body = preg_replace($URLRegex,"$2[url=$3]$3[/url]$5", $body);


		$find = array(
		'~\[url=([^]]+)]\[img]([^[]+)\[/img]\[/url]~i'
		);

		if ($article == 1)
		{
			$replace = array(
			'<a href="$1" target="_blank"><img src="$2" class="img-responsive" alt="image" /></a>'
			);
		}

		else if ($article == 0)
		{
			$replace = array(
			'<a href="$1" target="_blank"><img src="$2" class="img-responsive bbcodeimage-comment" alt="image" /></a>'
			);
		}

		$body = preg_replace($find, $replace, $body);

		$find = array(
		"/\[url\=(.+?)\](.+?)\[\/url\]/is",
		"/\[url\](.+?)\[\/url\]/is"
		);

		$replace = array(
		"<a href=\"$1\" target=\"_blank\">$2</a>",
		"<a href=\"$1\" target=\"_blank\">$1</a>"
		);

		$body = preg_replace($find, $replace, $body);
	}

	else if ($parse_links == 0)
	{
		$find = array(
		"/\[url\=(.+?)\](.+?)\[\/url\]/is",
		"/\[url\](.+?)\[\/url\]/is"
		);

		$replace = array(
		"$2",
		"$1"
		);

		$body = preg_replace($find, $replace, $body);
	}

	// remove extra new lines, caused by editors adding a new line after bbcode elements for easier reading when editing
	$find_lines = array(
		"/\[\/quote\]\r\n/is",
		"/\[ul\]\r\n/is",
		"/\[youtube\](.+?)\[\/youtube\]\r\n/is"
	);

	$replace_lines = array(
		"[/quote]",
		'[ul]',
		"[youtube]$1[/youtube]"
	);

	$body = preg_replace($find_lines, $replace_lines, $body);

	$body = quotes($body);

	// replace images and youtube to the correct size if its a comment or forum post (less space!)
	if ($article == 0)
	{
		$find = array(
		"/\[img\](.+?)\[\/img\]/is",
		"/\[img=([0-9]+)x([0-9]+)\](.+?)\[\/img\]/is",
		"/\[youtube\](.+?)\[\/youtube\]/is"
		);

		$replace = array(
		"<img src=\"$1\" class=\"img-responsive bbcodeimage-comment\" alt=\"image\" />",
		"<img width=\"$1\" height=\"$2\" src=\"$3\" class=\"img-responsive bbcodeimage-comment\" alt=\"image\" />",
		"<div class=\"video-container\"><iframe class=\"youtube-player\" width=\"550\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" data-youtube-id=\"$1\" frameborder=\"0\" allowfullscreen></iframe></div>"
		);

		$body = preg_replace($find, $replace, $body);
	}

	$find = array(
  "/\[url\=(.+?)\](.+?)\[\/url\]/is",
	"/\[url\](.+?)\[\/url\]/is",
  "/\[b\](.+?)\[\/b\]/is",
  "/\[i\](.+?)\[\/i\]/is",
  "/\[u\](.+?)\[\/u\]/is",
  "/\[s\](.+?)\[\/s\]/is",
  "/\[color\=(.+?)\](.+?)\[\/color\]/is",
  "/\[font\=(.+?)\](.+?)\[\/font\]/is",
  "/\[center\](.+?)\[\/center\]/is",
  "/\[right\](.+?)\[\/right\]/is",
  "/\[left\](.+?)\[\/left\]/is",
  "/\[img\](.+?)\[\/img\]/is",
  "/\[img=([0-9]+)x([0-9]+)\](.+?)\[\/img\]/is",
  "/\[email\](.+?)\[\/email\]/is",
	"/\[s\](.+?)\[\/s\]/is",
	"/\[youtube\](.+?)\[\/youtube\]/is",
	"/\[media=youtube\](.+?)\[\/media\]/is", // this one is for old articles, probably from xenforo, do not remove
	'/\[list\](.*?)\[\/list\]/is',
	'/\[\*\](.*?)(\n|\r\n?)/is',
	'/\[ul\]/is',
	'/\[\/ul\]/is',
	'/\[li\]/is',
	'/\[\/li\]/is',
	"/\[size\=(.+?)\](.+?)\[\/size\]/is",
	"/\[email\=(.+?)\](.+?)\[\/email\]/is",
	"/\[justify\](.+?)\[\/justify\]/is",
	"/\[code\](.+?)\[\/code\]/is",
	"/\[sup\](.+?)\[\/sup\]/is",
	"/\[spoiler](.+?)\[\/spoiler\]/is",
	"/\[mp3](.+?)\[\/mp3\]/is",
	"/\[ogg](.+?)\[\/ogg\]/is"
	);

	$replace = array(
  "<a href=\"$1\" target=\"_blank\">$2</a>",
	"<a href=\"$1\" target=\"_blank\">$1</a>",
  "<strong>$1</strong>",
  "<em>$1</em>",
  "<span style=\"text-decoration:underline;\">$1</span>",
  "<del>$1</del>",
  "$2",
  "$2",
  "<div style=\"text-align:center;\">$1</div>",
  "<div style=\"text-align:right;\">$1</div>",
  "<div style=\"text-align:left;\">$1</div>",
  "<a class=\"fancybox\" rel=\"group\" href=\"$1\"><img itemprop=\"image\" src=\"$1\" class=\"img-responsive\" alt=\"image\" /></a>",
  "<a class=\"fancybox\" rel=\"group\" href=\"$3\"><img itemprop=\"image\" width=\"$1\" height=\"$2\" src=\"$3\" class=\"img-responsive\" alt=\"image\" /></a>",
  "<a href=\"mailto:$1\" target=\"_blank\">$1</a>",
	"<span style=\"text-decoration: line-through\">$1</span>",
	"<div class=\"video-container\"><iframe class=\"youtube-player\" width=\"550\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" data-youtube-id=\"$1\" frameborder=\"0\" allowfullscreen></iframe></div>",
	"<iframe class=\"youtube-player\" width=\"550\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" data-youtube-id=\"$1\" frameborder=\"0\" allowfullscreen></iframe>",
	'<ul>$1</ul>',
	'</ul>',
	'<li>',
	'</li>',
	'$2',
	'<a href="mailto:$1">$2</a>',
	'$1',
	'Code:<br /><code>$1</code>',
	'<sup>$1</sup>',
	'<div class="collapse_container"><div class="collapse_header"><span>Spoiler, click me</span></div><div class="collapse_content"><div class="body group">$1</div></div></div>',
	'<audio controls><source src="$1" type="audio/mpeg">Your browser does not support the audio element.</audio>',
	'<audio controls><source src="$1" type="audio/ogg">Your browser does not support the audio element.</audio>'
	);

	$body = emoticons($body);

	$body = preg_replace($find, $replace, $body);

	$body = nl2br($body);

	// stop adding breaks to lists
	$body = str_replace('<ul><br />', '<ul>', $body);
	$body = str_replace('</ul><br />', '</ul>', $body);
	$body = str_replace('</li><br />', '</li>', $body);

	// stop adding line breaks to table html
	$body = str_replace('<tr><br />', '<tr>', $body);
	$body = str_replace('</th><br />', '</th>', $body);
	$body = str_replace('</td><br />', '</td>', $body);
	$body = str_replace('</tr><br />', '</tr>', $body);
	$body = preg_replace('/\<table (.+?)\>\<br \/\>/is', '<table $1>', $body);

	// stop big gaps after embedding a tweet from twitter
	$body = str_replace('</a></blockquote><br />', '</a></blockquote>', $body);
	$body = str_replace('</script><br />', '</script>', $body);


	// Put the code blocks back in
	foreach ($codeBlocks as $key => $codeblock)
	{
		$body = str_replace("!!@codeblock".$key."!!", $codeblock, $body);
	}

	 return $body;
}

function email_bbcode($body)
{
	// turn any url into url bbcode that doesn't have it already - so we can auto link urls- thanks stackoverflow
	$URLRegex = '/(?:(?<!(\[\/url\]|\[\/url=))(\s|^))'; // No [url]-tag in front and is start of string, or has whitespace in front
	$URLRegex.= '(';                                    // Start capturing URL
	$URLRegex.= '(https?|ftps?|ircs?):\/\/';            // Protocol
	$URLRegex.= '\S+';                                  // Any non-space character
	$URLRegex.= ')';                                    // Stop capturing URL
	$URLRegex.= '(?:(?<![.,;!?:\"\'()-])(\/|\s|\.?$))/i';      // Doesn't end with punctuation and is end of string, or has whitespace after

	$body = preg_replace($URLRegex,"$2[url=$3]$3[/url]$5", $body);

	// remove the new line after quotes, stop massive spacing
	$body = str_replace("[/quote]\r\n", '[/quote]', $body);

	// stop lists having a big gap at the top
	$body = str_replace("[ul]\r\n", '[ul]', $body);

	// Quoting an actual person, book or whatever
	$pattern = '/\[quote\=(.+?)\](.+?)\[\/quote\]/is';

	$replace = "<div style=\"overflow: hidden;margin: 1em 0px 0px;padding: 0.2em 1.25em 0.2em 24px;background: rgb(243, 243, 240);color: rgb(85, 68, 51);\"><strong>$1</strong><br />$2</div>";

	while(preg_match($pattern, $body))
	{
		$body = preg_replace($pattern, $replace, $body);
	}

	// Quote on its own
	$pattern = '/\[quote\](.+?)\[\/quote\]/is';

	$replace = "<div style=\"overflow: hidden;margin: 1em 0px 0px;padding: 0.2em 1.25em 0.2em 24px;background: rgb(243, 243, 240);color: rgb(85, 68, 51);\"><strong>Quote</strong><br />$1</div>";

	while(preg_match($pattern, $body))
	{
		$body = preg_replace($pattern, $replace, $body);
	}

	$find = array(
    "/\[url\=(.+?)\](.+?)\[\/url\]/is",
	"/\[url\](.+?)\[\/url\]/is",
    "/\[b\](.+?)\[\/b\]/is",
    "/\[i\](.+?)\[\/i\]/is",
    "/\[u\](.+?)\[\/u\]/is",
    "/\[s\](.+?)\[\/s\]/is",
    "/\[color\=(.+?)\](.+?)\[\/color\]/is",
    "/\[font\=(.+?)\](.+?)\[\/font\]/is",
    "/\[center\](.+?)\[\/center\]/is",
    "/\[right\](.+?)\[\/right\]/is",
    "/\[left\](.+?)\[\/left\]/is",
	"/\[img\]http\:\/\/img\.youtube.com\/vi\/(.+?)\/0\.jpg\[\/img\]/is", //youtube videos done by the old tinymce plugin...
    "/\[img\](.+?)\[\/img\]/is",
    "/\[img=([0-9]+)x([0-9]+)\](.+?)\[\/img\]/is",
    "/\[email\](.+?)\[\/email\]/is",
	"/\[s\](.+?)\[\/s\]/is",
	"/\[media=youtube\](.+?)\[\/media\]/is", // This is for videos done by xenforo...
	"/\[youtube\](.+?)\[\/youtube\]/is",
	'/\[list\](.*?)\[\/list\]/is',
 	'/\[\*\](.*?)(\n|\r\n?)/is',
 	'/\[ul\]/is',
 	'/\[\/ul\]/is',
 	'/\[li\]/is',
 	'/\[\/li\]/is',
 	"/\[size\=(.+?)\](.+?)\[\/size\]/is",
 	"/\[email\=(.+?)\](.+?)\[\/email\]/is",
 	"/\[justify\](.+?)\[\/justify\]/is",
 	"/\[code\](.+?)\[\/code\]/is",
	"/\[spoiler\](.+?)\[\/spoiler\]/is"
	);

	$replace = array(
  "<a href=\"$1\" target=\"_blank\">$2</a>",
	"<a href=\"$1\" target=\"_blank\">$1</a>",
  "<strong>$1</strong>",
  "<em>$1</em>",
  "<span style=\"text-decoration:underline;\">$1</span>",
  "<del>$1</del>",
  "$2",
  "$2",
  "<div style=\"text-align:center;\">$1</div>",
  "<div style=\"text-align:right;\">$1</div>",
  "<div style=\"text-align:left;\">$1</div>",
	"<iframe class=\"youtube-player\" width=\"640\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allowfullscreen></iframe>",
  "<img src=\"$1\" class=\"bbcodeimage img-polaroid\" alt=\"[img]\" />",
  "<img width=\"$1\" height=\"$2\" src=\"$3\" class=\"bbcodeimage img-polaroid\" alt=\"[img]\" />",
  "<a href=\"mailto:$1\" target=\"_blank\">$1</a>",
	"<span style=\"text-decoration: line-through\">$1</span>",
	"<iframe class=\"youtube-player\" width=\"640\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allowfullscreen></iframe>", // for xenforo videos
	"<iframe class=\"youtube-player\" width=\"640\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allowfullscreen>
	</iframe>",
	'<ul>$1</ul>',
	'<li>$1</li>',
	'<ul>',
	'</ul>',
	'<li>',
	'</li>',
	'$2',
	'<a href="mailto:$1">$2</a>',
	'$1',
	'Code:<br /><code>$1</code>',
	'SPOILER: View the website if you wish to see it.'
	);

	$body = emoticons($body);

	$body = preg_replace($find, $replace, $body);

	$body = nl2br($body);

	$body = str_replace('</li><br />', '</li>', $body);

	// stop there being a big gap after a list is finished
	$body = str_replace('</ul><br />', '</ul>', $body);

	// stop big gaps after embedding a tweet from twitter
	$body = str_replace('</a></blockquote><br />', '</a></blockquote>', $body);
	$body = str_replace('</script><br />', '</script>', $body);

	return $body;
}

function emoticons($text)
{
	$smilies = array(
	":><:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/angry.png" alt="" />',
	":&gt;&lt;:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/angry.png" alt="" />', // for comments as they are made html-safe
	":'(" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/cry.png" alt="" />', // for comments as they are made html-safe
	":&#039;(" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/cry.png" alt="" />',
	":dizzy:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/dizzy.png" alt="" />',
	":D" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/grin.png" alt="" />',
	"^_^" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/happy.png" alt="" />',
	"<3" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/heart.png" alt="" />',
	"&lt;3" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/heart.png" alt="" />', // for comments as they are made html-safe
	":huh:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/huh.png" alt="" />',
	":|" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/pouty.png" alt="" />',
	":(" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/sad.png" alt=""/>',
	":O" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/shocked.png" alt="" />',
	":sick:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/sick.png" alt="" />',
	":)" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/smile.png" alt="" />',
	":P" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/tongue.png" alt="" />',
	":S:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/unsure.png" alt="" />',
	":woot:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/w00t.png" alt="" />',
	":whistle:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/whistle.png" alt="" />',
	";)" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/wink.png" alt="" />',
	":wub:" => '<img src="'.core::config('website_url').'templates/default/images/emoticons/wub.png" alt="" />'
	);

	$text = str_replace( array_keys( $smilies ), array_values( $smilies ), $text );

	return $text;
}

// remove bits to make sure RSS validates, and to make sure hidden bits don't become available to all
function rss_stripping($text, $tagline_image = NULL, $gallery_tagline = NULL)
{
	if ($tagline_image != NULL)
	{
		$find = "[img]tagline-image[/img]";

		if ($gallery_tagline == NULL)
		{
			$file_path = "uploads/articles/tagline_images/";
		}
		else
		{
			$file_path = "uploads/tagline_gallery/";
		}
		$replace = "<img src=\"" . core::config('website_url') . $file_path . $tagline_image . "\" alt=\"tagline-image\" />";

		$text = str_replace($find, $replace, $text);
	}

	$text = str_replace('<*PAGE*>', '', $text);

	$text = str_replace('[pcinfo]', '', $text);

	$text = preg_replace('/\[quote\](.+?)\[\/quote\]/is', "<blockquote><cite>Quote</cite><br />$1</blockquote>", $text);
	$text = preg_replace('/\[quote\=(.+?)\](.+?)\[\/quote\]/is', "<blockquote><cite>Quote</cite><br />$2</blockquote>", $text);

	$text = preg_replace("/\[youtube\](.+?)\[\/youtube\]/is", '', $text);

	$text = preg_replace("/\[timer=(.+?)](.+?)\[\/timer]/is", ' Visit <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a> to see the timer ', $text);

	$text = preg_replace('/\[users-only\](.+?)\[\/users-only\]/is', ' Visit <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a> to see this bit, this is for logged in users only ', $text);

	return $text;
}

// for showing them when they last updated their PC info
function pc_info($body)
{
	global $db;

	if(preg_match('/\[pcinfo\]/', $body))
	{
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$grab_date = $db->sqlquery("SELECT `date_updated` FROM `user_profile_info` WHERE `user_id` = ?", array($_SESSION['user_id']));
			$update_info = $grab_date->fetch();

			if (!isset($update_info['date_updated']))
			{
				$date_updated = '<strong>Never</strong>!';
			}
			else
			{
				$date_updated = '<strong>' . date('d M, Y', strtotime($update_info['date_updated'])) . '</strong>.';
			}
			$body = str_replace("[pcinfo]", 'You last updated yours: ' . $date_updated . ' <a href="/usercp.php?module=pcinfo">Click here to check and update yours now!</a>', $body);
		}
		else
		{
			$body = str_replace("[pcinfo]", '<em>You need to be logged in to see when you last updated your PC info!</em>', $body);
		}
	}

	return $body;
}
?>
