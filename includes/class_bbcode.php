<?php
/*
HTML is sanatized in files directly, not here
*/
class bbcode
{
	// the required database connection
	private $database;
	// the requred core class
	private $core;
	
	function __construct($database, $core)
	{
		$this->database = $database;
		$this->core = $core;
	}
	
	function do_charts($body)
	{		
		preg_match_all("/\[chart\](.+?)\[\/chart\]/is", $body, $matches);

		foreach ($matches[1] as $id)
		{
			$charts = new charts($this->database);

			$body = preg_replace("/\[chart\]($id)\[\/chart\]/is", '<div style="text-align:center; width: 100%;">' . $charts->render(NULL, ['id' => $id]) . '</div>', $body);
		}
		return $body;
	}

	function replace_giveaways($text, $giveaway_id)
	{
		$key_claim = '';
		$nope_message = '[b]Grab a key[/b]<br />You must be logged in to grab a key, your account must also be older than one day!';
		
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$your_key = $this->database->run("SELECT COUNT(game_key) as counter, `game_key` FROM `game_giveaways_keys` WHERE `claimed_by_id` = ? AND `game_id` = ? GROUP BY `game_key`", [$_SESSION['user_id'], $giveaway_id])->fetch();

			// they have a key already
			if ($your_key['counter'] == 1)
			{
				$key_claim = '[b]Grab a key[/b]<br />You already claimed one: ' . $your_key['game_key'];
			}
			// they do not have a key
			else if ($your_key['counter'] == 0)
			{
				$keys_left = $this->database->run("SELECT COUNT(id) as counter FROM `game_giveaways_keys` WHERE `claimed` = 0 AND `game_id` = ?", [$giveaway_id])->fetch();

				if ($keys_left['counter'] == 0)
				{
					$key_claim = '[b]Grab a key[/b]<br />All keys are now gone, sorry!';
				}
				else
				{
					 // check their registration date is older than one day
					$reg_fetch = $this->database->run("SELECT `register_date` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

					$day_ago = time() - 24 * 3600;

					if ($day_ago > $reg_fetch['register_date'])
					{
						$game_info = $this->database->run("SELECT `id`, `game_name`FROM `game_giveaways` WHERE `id` = ?", array($giveaway_id))->fetch();

						$key_claim = '[b]Grab a key[/b] (keys left: '.$keys_left['counter'].')<br /><div id="key-area"><a id="claim_key" data-game-id="'.$game_info['id'].'" href="#">click here to claim</a></div>';
					}
					else
					{
						$key_claim = $nope_message;
					}
				}
			}
		}
		else
		{
			$key_claim = $nope_message;
		}

		$text = preg_replace("/\[giveaway\]".$giveaway_id."\[\/giveaway\]/is", $key_claim, $text);

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
		$body = preg_replace_callback("/\[timer=(.+?)](.+?)\[\/timer]/is", function ($matches){return $this->replace_timer($matches);}, $body);

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
		$sections['title'] = '';
		$pattern = '/\=title\=(.+?)\=title\=/is';
		if (preg_match($pattern, $dump, $title))
		{
			$sections['title'] = $title[1];
		}

		// tags
		$sections['tags'] = '';
		$pattern = '/\=tags\=(.+?)\=tags\=/is';
		if (preg_match($pattern, $dump, $tags))
		{
			$sections['tags'] = $tags[1];
		}
		
		// tagline
		$sections['tagline'] = '';
		$pattern = '/\=tagline\=(.+?)\=tagline\=/is';
		if (preg_match($pattern, $dump, $tagline))
		{
			$sections['tagline'] = $tagline[1];
		}
		
		// text
		$sections['text'] = '';
		$pattern = '/\=text\=(.+?)\=text\=/is';
		if (preg_match($pattern, $dump, $text))
		{
			$sections['text'] = $text[1];
		}
		
		return $sections;
	}

	// replace specific-user quotes, called by quotes()
	function replace_quotes($matches)
	{
		$find_quoted = $this->database->run("SELECT `username`, `user_id` FROM `users` WHERE `username` = ?", [$matches[1]])->fetch();
		if ($find_quoted)
		{
			if ($this->core->config('pretty_urls') == 1)
			{
				$profile_link = '/profiles/' . $find_quoted['user_id'];
			}
			else
			{
				$profile_link = '/index.php?module=profile&user_id=' . $find_quoted['user_id'];
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
		$pattern = '/\[quote\](?:\s)*(.+?)\[\/quote\]/is';
		$replace = "<blockquote><cite>Quote</cite>$1</blockquote>";

		while(preg_match($pattern, $body))
		{
			$body = preg_replace($pattern, $replace, $body);
		}

		// Quoting an actual person, book or whatever
		$pattern = '~\[quote=([^]]+)](?:\s)*([^[]*(?:\[(?!/?quote\b)[^[]*)*)\[/quote]~i';
		do
		{
			$body = preg_replace_callback($pattern, function ($matches){return $this->replace_quotes($matches);}, $body, -1, $count);
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
	
	function parse_links($text)
	{
		$URLRegex = '/(?:(?<!(\[\/url\]|\[\/url=))(\s|^))'; // No [url]-tag in front and is start of string, or has whitespace in front
		$URLRegex.= '(';                                    // Start capturing URL
		$URLRegex.= '(https?|ftps?|ircs?):\/\/';            // Protocol
		$URLRegex.= '[\w\d\.\/#\_\-\?:=]+';                        // Any non-space character
		$URLRegex.= ')';                                    // Stop capturing URL
		$URLRegex.= '(?:(?<![.,;!?:\"\'()-])(\/|\[|\s|\.?$))/i';      // Doesn't end with punctuation and is end of string, or has whitespace after

		$text = preg_replace($URLRegex,"$2[url=$3]$3[/url]$5", $text);

		$find = '~\[url=([^]]+)]\[img]([^[]+)\[/img]\[/url]~i'; // don't even remember what this one was checking for exactly?

		$replace = '<a href="$1" target="_blank"><img src="$2" alt="image" /></a>';

		$text = preg_replace($find, $replace, $text);

		$find = array(
		"/\[url\=(.+?)\](.+?)\[\/url\]/is",
		"/\[url\](.+?)\[\/url\]/is"
		);

		$replace = array(
		"<a href=\"$1\" target=\"_blank\">$2</a>",
		"<a href=\"$1\" target=\"_blank\">$1</a>"
		);

		$text = preg_replace($find, $replace, $text);
		
		return $text;
	}

	function parse_bbcode($body, $article = 1)
	{
		//  get rid of empty BBCode, is there a point in having excess markup?
		$body = preg_replace("`\[(b|i|s|u|url|mail|spoiler|img|quote|code|color|youtube)\]\[/(b|i|s|u|url|spoiler|mail|img|quote|code|color|youtube)\]`",'',$body);
		
		$body = $this->logged_in_code($body);

		if (preg_match_all("/\[giveaway\](.+?)\[\/giveaway\]/is", $body, $giveaway_matches))
		{
			foreach ($giveaway_matches[1] as $match)
			{
				$body = $this->replace_giveaways($body, $match);
			}
		}

		$body = $this->do_timers($body);

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

		$body = $this->pc_info($body);

		$body = $this->parse_links($body);

		// remove extra new lines, caused by editors adding a new line after bbcode elements for easier reading when editing
		$find_lines = array(
			"/\[\/quote\]\r\n/is",
			"/\[ul\]\r\n/is",
		);

		$replace_lines = array(
			"[/quote]",
			'[ul]',
		);

		$body = preg_replace($find_lines, $replace_lines, $body);
		
		// limit youtube replacing to the config limit
		$find_replace = [
		"/\[youtube\](.+?)\[\/youtube\](\r?\n)?/is" => ["<div class=\"video-container\"><iframe class=\"youtube-player\" width=\"550\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" data-youtube-id=\"$1\" frameborder=\"0\" allowfullscreen></iframe></div>", $this->core->config('limit_youtube')]
		];

		foreach ($find_replace as $find => $replace)
		{
			$body = preg_replace($find, $replace[0], $body, intval($replace[1]));
		}

		$body = $this->quotes($body);

		$find_replace = array(
		"/\[youtube\](.+?)\[\/youtube\]/is" 
			=> " <a href=\"https://www.youtube.com/watch?v=$1\" target=\"_blank\">View video on youtube.com</a> ",
		"/\[url\=(.+?)\](.+?)\[\/url\]/is" 
			=> "<a href=\"$1\" target=\"_blank\">$2</a>",
		"/\[url\](.+?)\[\/url\]/is" 
			=> "<a href=\"$1\" target=\"_blank\">$1</a>",
		"/\[b\](.+?)\[\/b\]/is" 
			=> "<strong>$1</strong>",
		"/\[i\](.+?)\[\/i\]/is" 
			=> "<em>$1</em>",
		"/\[u\](.+?)\[\/u\]/is" 
			=> "<span style=\"text-decoration:underline;\">$1</span>",
		"/\[s\](.+?)\[\/s\]/is" 
			=> "<del>$1</del>",
		"/\[color\=(.+?)\](.+?)\[\/color\]/is" 
			=> "$2",
		"/\[font\=(.+?)\](.+?)\[\/font\]/is" 
			=> "$2",
		"/\[center\](.+?)\[\/center\](\r?\n)?/is" 
			=> "<div style=\"text-align:center;\">$1</div>",
		"/\[right\](.+?)\[\/right\]/is" 
			=> "<div style=\"text-align:right;\">$1</div>",
		"/\[left\](.+?)\[\/left\]/is" 
			=> "<div style=\"text-align:left;\">$1</div>",
		"/\[img\](.+?)\[\/img\]/is" 
			=> "<a data-fancybox=\"images\" rel=\"group\" href=\"$1\"><img itemprop=\"image\" src=\"$1\" class=\"img-responsive\" alt=\"image\" /></a>",
		"/\[img=([0-9]+)x([0-9]+)\](.+?)\[\/img\]/is" 
			=> "<a class=\"fancybox\" rel=\"group\" href=\"$3\"><img itemprop=\"image\" width=\"$1\" height=\"$2\" src=\"$3\" class=\"img-responsive\" alt=\"image\" /></a>",
		"/\[email\](.+?)\[\/email\]/is" 
			=> "<a href=\"mailto:$1\" target=\"_blank\">$1</a>",
		'/\[list\](.*?)\[\/list\]/is' 
			=> '<ul>$1</ul>',
		'/\[\*\](.*?)(\n|\r\n?)/is' 
			=> '<ul>$1</ul>',
		'/\[ul\]/is' 
			=> '<ul>',
		'/\[\/ul\]/is' 
			=> '</ul>',
		'/\[li\]/is' 
			=> '<li>',
		'/\[\/li\]/is' 
			=> '</li>',
		"/\[size\=(.+?)\](.+?)\[\/size\]/is" 
			=> '$2', // disallow size
		"/\[email\=(.+?)\](.+?)\[\/email\]/is" 
			=> '<a href="mailto:$1">$2</a>',
		"/\[justify\](.+?)\[\/justify\]/is" 
			=> '$1',
		"/\[code\](.+?)\[\/code\]/is" 
			=> '<code>$1</code>',
		"/\[sup\](.+?)\[\/sup\]/is" 
			=> '<sup>$1</sup>',
		"/\[spoiler](.+?)\[\/spoiler\](\r?\n)?/is" 
			=> '<div class="collapse_container"><div class="collapse_header"><span>Spoiler, click me</span></div><div class="collapse_content"><div class="body group">$1</div></div></div>',
		"/\[mp3](.+?)\[\/mp3\]/is" 
			=> '<audio controls><source src="$1" type="audio/mpeg">Your browser does not support the audio element.</audio>',
		"/\[ogg](.+?)\[\/ogg\]/is" 
			=> '<audio controls><source src="$1" type="audio/ogg">Your browser does not support the audio element.</audio>',
		"/(\[split\])(\s)*/is"
			=> '<hr class="content_split">'
		);

		$body = $this->emoticons($body);

		foreach ($find_replace as $find => $replace)
		{
			$body = preg_replace($find, $replace, $body);
		}

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

		// Put the code blocks back in
		foreach ($codeBlocks as $key => $codeblock)
		{
			$body = str_replace("!!@codeblock".$key."!!", $codeblock, $body);
		}
		
		// replace charts bbcode with the pretty stuff
		$body = $this->do_charts($body);

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
		"/\[img\](.+?)\[\/img\]/is",
		"/\[img=([0-9]+)x([0-9]+)\](.+?)\[\/img\]/is",
		"/\[email\](.+?)\[\/email\]/is",
		"/\[s\](.+?)\[\/s\]/is",
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
		"<img src=\"$1\" alt=\"[img]\" />",
		"<img width=\"$1\" height=\"$2\" src=\"$3\" alt=\"[img]\" />",
		"<a href=\"mailto:$1\" target=\"_blank\">$1</a>",
		"<span style=\"text-decoration: line-through\">$1</span>",
		"<iframe width=\"640\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allowfullscreen>
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

		$body = $this->emoticons($body);

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
		$smilie_location = $this->core->config('website_url') . 'templates/' . $this->core->config('template');
		
		$smilies = array(
		":><:" => '<img src="'.$smilie_location.'/images/emoticons/angry.png" alt="" />',
		":&gt;&lt;:" => '<img src="'.$smilie_location.'/images/emoticons/angry.png" alt="" />', // for comments as they are made html-safe
		":'(" => '<img src="'.$smilie_location.'/images/emoticons/cry.png" alt="" />', // for comments as they are made html-safe
		":&#039;(" => '<img src="'.$smilie_location.'/images/emoticons/cry.png" alt="" />',
		":dizzy:" => '<img src="'.$smilie_location.'/images/emoticons/dizzy.png" alt="" />',
		":D" => '<img src="'.$smilie_location.'/images/emoticons/grin.png" alt="" />',
		"^_^" => '<img src="'.$smilie_location.'/images/emoticons/happy.png" alt="" />',
		"<3" => '<img src="'.$smilie_location.'/images/emoticons/heart.png" alt="" />',
		"&lt;3" => '<img src="'.$smilie_location.'/images/emoticons/heart.png" alt="" />', // for comments as they are made html-safe
		":huh:" => '<img src="'.$smilie_location.'/images/emoticons/huh.png" alt="" />',
		":|" => '<img src="'.$smilie_location.'/images/emoticons/pouty.png" alt="" />',
		":(" => '<img src="'.$smilie_location.'/images/emoticons/sad.png" alt=""/>',
		":O" => '<img src="'.$smilie_location.'/images/emoticons/shocked.png" alt="" />',
		":sick:" => '<img src="'.$smilie_location.'/images/emoticons/sick.png" alt="" />',
		":)" => '<img src="'.$smilie_location.'/images/emoticons/smile.png" alt="" />',
		":P" => '<img src="'.$smilie_location.'/images/emoticons/tongue.png" alt="" />',
		":S:" => '<img src="'.$smilie_location.'/images/emoticons/unsure.png" alt="" />',
		":woot:" => '<img src="'.$smilie_location.'/images/emoticons/w00t.png" alt="" />',
		":whistle:" => '<img src="'.$smilie_location.'/images/emoticons/whistle.png" alt="" />',
		";)" => '<img src="'.$smilie_location.'/images/emoticons/wink.png" alt="" />',
		":wub:" => '<img src="'.$smilie_location.'/images/emoticons/wub.png" alt="" />'
		);

		$text = str_replace( array_keys( $smilies ), array_values( $smilies ), $text );

		return $text;
	}

	// remove bits to make sure RSS validates, and to make sure hidden bits don't become available to all
	function rss_stripping($text, $tagline_image = NULL, $gallery_tagline = NULL)
	{
		$text = str_replace('<*PAGE*>', '', $text);

		$text = str_replace('[pcinfo]', '', $text);

		$text = preg_replace('/\[quote\](.+?)\[\/quote\]/is', "<blockquote><cite>Quote</cite><br />$1</blockquote>", $text);
		$text = preg_replace('/\[quote\=(.+?)\](.+?)\[\/quote\]/is', "<blockquote><cite>Quote</cite><br />$2</blockquote>", $text);

		$text = preg_replace("/\[timer=(.+?)](.+?)\[\/timer]/is", ' Visit <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a> to see the timer ', $text);

		$find_replace = [
		'/\[users-only\](.+?)\[\/users-only\]/is'
			=> ' Visit <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a> to see this bit, this is for logged in users only ',
		"/\[img\](.+?)\[\/img\]/is"
			=> "<img src=\"$1\" alt=\"image\" />",
		"/\[img=([0-9]+)x([0-9]+)\](.+?)\[\/img\]/is"
			=> "<img width=\"$1\" height=\"$2\" src=\"$3\" alt=\"image\" />",
		"/\[youtube\](.+?)\[\/youtube\]/is"
			=> "<iframe width=\"640\" height=\"385\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allowfullscreen></iframe>",
		"/\[spoiler\](.+?)\[\/spoiler\]/is"
			=> 'SPOILER: View the website if you wish to see it.'
		];
		
		foreach ($find_replace as $find => $replace)
		{
			$text = preg_replace($find, $replace, $text);
		}
		
		return $text;
	}

	// for showing them when they last updated their PC info
	function pc_info($body)
	{
		if(preg_match('/\[pcinfo\]/', $body))
		{
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && is_numeric($_SESSION['user_id']))
			{
				$fields_output = '';
				
				$pc_info = [];

				$counter = 0;

				$additionaldb = $this->database->run("SELECT
					p.`desktop_environment`,
					p.`what_bits`,
					p.`cpu_vendor`,
					p.`cpu_model`,
					p.`gpu_vendor`,
					p.`gpu_driver`,
					p.`ram_count`,
					p.`monitor_count`,
					p.`gaming_machine_type`,
					p.`resolution`,
					p.`dual_boot`,
					p.`gamepad`,
					p.`date_updated`,
					u.`distro`,
					g.`id` AS `gpu_id`, 
					g.`name` AS `gpu_model`
					FROM
					`user_profile_info` p
					INNER JOIN
					`users` u ON u.user_id = p.user_id
					LEFT JOIN
					`gpu_models` g ON g.id = p.gpu_model 
					WHERE
					p.`user_id` = ?", array($_SESSION['user_id']))->fetch();


				if (!empty($additionaldb['distro']) && $additionaldb['distro'] != 'Not Listed')
				{
					$counter++;
					$pc_info['distro'] = "<strong>Distribution:</strong> <img class=\"distro\" height=\"20px\" width=\"20px\" src=\"/templates/default/images/distros/{$additionaldb['distro']}.svg\" alt=\"{$additionaldb['distro']}\" /> {$additionaldb['distro']}";
				}
				if (!empty($additionaldb['desktop_environment']))
				{
					$counter++;
					$pc_info['desktop'] = '<strong>Desktop Environment:</strong> ' . $additionaldb['desktop_environment'];
				}

				if ($additionaldb['what_bits'] != NULL && !empty($additionaldb['what_bits']))
				{
					$counter++;
					$pc_info['what_bits'] = '<strong>Distribution Architecture:</strong> '.$additionaldb['what_bits'];
				}

				if ($additionaldb['dual_boot'] != NULL && !empty($additionaldb['dual_boot']))
				{
					$counter++;
					$pc_info['dual_boot'] = '<strong>Do you dual-boot with a different operating system?</strong> '.$additionaldb['dual_boot'];
				}
				
				if ($additionaldb['ram_count'] != NULL && !empty($additionaldb['ram_count']))
				{
					$counter++;
					$pc_info['ram_count'] = '<strong>RAM:</strong> '.$additionaldb['ram_count'].'GB';
				}

				if ($additionaldb['cpu_vendor'] != NULL && !empty($additionaldb['cpu_vendor']))
				{
					$counter++;
					$pc_info['cpu_vendor'] = '<strong>CPU Vendor:</strong> '.$additionaldb['cpu_vendor'];
				}

				if ($additionaldb['cpu_model'] != NULL && !empty($additionaldb['cpu_model']))
				{
					$counter++;
					$pc_info['cpu_model'] = '<strong>CPU Model:</strong> ' . $additionaldb['cpu_model'];
				}

				if ($additionaldb['gpu_vendor'] != NULL && !empty($additionaldb['gpu_vendor']))
				{
					$counter++;
					$pc_info['gpu_vendor'] = '<strong>GPU Vendor:</strong> ' . $additionaldb['gpu_vendor'];
				}

				if ($additionaldb['gpu_model'] != NULL && !empty($additionaldb['gpu_model']))
				{
					$counter++;
					$pc_info['gpu_model'] = '<strong>GPU Model:</strong> ' . $additionaldb['gpu_model'];
				}

				if ($additionaldb['gpu_driver'] != NULL && !empty($additionaldb['gpu_driver']))
				{
					$counter++;
					$pc_info['gpu_driver'] = '<strong>GPU Driver:</strong> ' . $additionaldb['gpu_driver'];
				}

				if ($additionaldb['monitor_count'] != NULL && !empty($additionaldb['monitor_count']))
				{
					$counter++;
					$pc_info['monitor_count'] = '<strong>Monitors:</strong> '.$additionaldb['monitor_count'];
				}

				if ($additionaldb['resolution'] != NULL && !empty($additionaldb['resolution']))
				{
					$counter++;
					$pc_info['resolution'] = '<strong>Resolution:</strong> '.$additionaldb['resolution'];
				}

				if ($additionaldb['gaming_machine_type'] != NULL && !empty($additionaldb['gaming_machine_type']))
				{
					$counter++;
					$pc_info['gaming_machine_type'] = '<strong>Main gaming machine:</strong> '.$additionaldb['gaming_machine_type'];
				}

				if ($additionaldb['gamepad'] != NULL && !empty($additionaldb['gamepad']))
				{
					$counter++;
					$pc_info['gamepad'] = '<strong>Gamepad:</strong> '.$additionaldb['gamepad'];
				}
				
				$pc_info['counter'] = $counter;
				if ($pc_info['counter'] > 0)
				{
					$fields_output = '<ul style="list-style-type: none; margin: 0; padding: 0;">';
					foreach ($pc_info as $k => $info)
					{
						if ($k != 'counter')
						{
							$fields_output .= '<li>' . $info . '</li>';
						}
					}
					$fields_output .= '</ul>';
				}
				else
				{
					$fields_output = "<em>You haven't filled yours in!</em>";
				}

				$update_info = $this->database->run("SELECT `date_updated` FROM `user_profile_info` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

				if (!isset($update_info['date_updated']))
				{
					$date_updated = '<strong>Never</strong>!';
				}
				else
				{
					$date_updated = '<strong>' . date('d M, Y', strtotime($update_info['date_updated'])) . '</strong>';
				}
				$body = str_replace("[pcinfo]", 'You last updated yours: ' . $date_updated . '. <br /><br />Here\'s what we have for you at the moment:' . $fields_output . '<br />If this is correct, <a href="#" id="pc_info_update">click here to continue</a> to be included. If this isn\'t correct, <a href="/usercp.php?module=pcinfo">click here to go to your User Control Panel to update it!</a><span class="all-ok" id="pc_info_done"></span>', $body);
			}
			else
			{
				$body = str_replace("[pcinfo]", '<em>You need to be logged in to see when you last updated your PC info!</em>', $body);
			}
		}

		return $body;
	}
	
	// convert helpers into the html for displaying on the site
	function article_bbcode($text)
	{
		$text = $this->parse_links($text);
		$text = $this->pc_info($text);
		
		$text = $this->do_charts($text);
		
		$text = $this->logged_in_code($text);

		if (preg_match_all("/\[giveaway\](.+?)\[\/giveaway\]/is", $text, $giveaway_matches))
		{
			foreach ($giveaway_matches[1] as $match)
			{
				$text = $this->replace_giveaways($text, $match);
			}
		}

		$text = $this->do_timers($text);
		
		return $text;
	}
}
?>
