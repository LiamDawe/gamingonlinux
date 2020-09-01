<?php
/*
HTML is sanatized in files directly, not here
*/
class bbcode
{
	// the required database connection
	private $dbl;
	// the requred core class
	private $core;

	private $allowed_link_protocols = array('http:\/\/', 'https:\/\/', 'ftp:\/\/', 'ftps:\/\/', 'mailto:', 'irc:\/\/', 'ircs:\/\/');
	private $protocols = '';
	public $emoji = array();

	function __construct($dbl, $core, $user)
	{
		$this->dbl = $dbl;
		$this->core = $core;
		$this->user = $user;

		$this->protocols = implode('|',$this->allowed_link_protocols);
		$this->prepare_emoji();
	}
	
	function do_charts($body)
	{		
		preg_match_all("/\[chart\](.+?)\[\/chart\]/is", $body, $matches);

		foreach ($matches[1] as $id)
		{
			$charts = new charts($this->dbl);

			$body = preg_replace("/\[chart\]($id)\[\/chart\]/is", '<div style="text-align:center; width: 100%;">' . $charts->render(NULL, ['id' => $id]) . '</div>', $body);
		}
		return $body;
	}

	function replace_giveaways($text, $giveaway_id, $external_page = 0)
	{
		$key_claim = '';
		$nope_message = '<strong>Grab a key</strong><br />You must be logged in to grab a key, your account must also be older than one day!';
		
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$game_info = $this->dbl->run("SELECT `id`, `giveaway_name`, `supporters_only`, `display_all` FROM `game_giveaways` WHERE `id` = ?", array($giveaway_id))->fetch();

			$your_key = $this->dbl->run("SELECT COUNT(game_key) as counter, `game_key` FROM `game_giveaways_keys` WHERE `claimed_by_id` = ? AND `game_id` = ? GROUP BY `game_key`", [$_SESSION['user_id'], $giveaway_id])->fetch();

			if ($external_page == 1)
			{
				$key_claim .= '<p>This is a giveaway for: <strong>' . $game_info['giveaway_name'] . '</strong></p>';
			}

			// they have a key already
			if (isset($your_key['game_key']))
			{
				$key_claim .= '<strong>Grab a key</strong><br />You already claimed one: ' . $your_key['game_key'];
			}
			// they do not have a key
			else
			{
				$can_claim = 1; // start off by allowing them and removing as needed

				// doing this here, in case they redeemed a key while they were a supporter and not now - still allow them to view their previously redeemed key
				if ($game_info['supporters_only'] == 1)
				{
					if (!$this->user->check_group([1,9]))
					{
						$can_claim = 0;
						$nope_message .= ' You also need to support GamingOnLinux to qualify for this giveaway.';
					}
				}

				$keys_left = $this->dbl->run("SELECT COUNT(id) as counter FROM `game_giveaways_keys` WHERE `claimed` = 0 AND `game_id` = ?", [$giveaway_id])->fetch();

				// there are keys left
				if ($keys_left['counter'] == 0)
				{
					$key_claim .= '<strong>Grab a key</strong><br />All keys are now gone, sorry!';
				}
				else
				{
					// check their registration date is older than one day
					$reg_fetch = $this->dbl->run("SELECT `register_date` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
			
					$day_ago = time() - 24 * 3600;

					// either they're too new or they can't claim (not a supporter?)
					if ($day_ago < $reg_fetch['register_date'] || $can_claim == 0)
					{
						$key_claim .= $nope_message;
					}
					// all good, let them claim
					else if ($day_ago > $reg_fetch['register_date'] && $can_claim == 1)
					{
						// standard single-title giveaway
						if ($game_info['display_all'] == 0)
						{
							$key_claim .= '<strong>Grab a key</strong> (keys left: '.$keys_left['counter'].')<br /><div id="key-area"><a class="claim_key" data-game-id="'.$game_info['id'].'" href="#">click here to claim</a></div>';
						}
						// giving away multiple items, display them and let users pick
						else
						{
							$key_claim .= '<strong>Grab a key</strong> (keys left: '.$keys_left['counter'].')';

							$all_keys = $this->dbl->run("SELECT `id`, `name` FROM `game_giveaways_keys` WHERE `game_id` = ? AND `claimed` = 0", [$giveaway_id])->fetch_all();
							foreach ($all_keys as $pick)
							{
								$key_claim .= '<p>'.$pick['name'].' - <a class="claim_key" data-game-id="'.$game_info['id'].'" data-key-id="'.$pick['id'].'" href="#">click here to claim</a></p>';
							}
						}
					}
				}
			}
		}
		else
		{
			$key_claim .= $nope_message;
		}

		$text = preg_replace("/\[giveaway\]".$giveaway_id."\[\/giveaway\]/is", $key_claim, $text);

		return $text;
	}

	function replace_timer($matches)
	{
		if (preg_match("/\*time-only/is", $matches[0]))
		{
			$rest = substr($matches[1], 0, -10);
			return '<div class="countdown" id="'.$rest.'">'.$matches[2].'</div>';
		}
		else
		{
			return '<div class="countdown" id="'.$matches[1].'">'.$matches[2].'</div>';
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

	// find all quotes
	function quotes($body)
	{
		// Quote on its own, do these first so they don't get in the way
		$pattern = '/\[quote\](?:\s)*(.+?)\[\/quote\]/is';
		$replace = "<blockquote class=\"comment_quote\"><cite>Quote</cite>$1</blockquote>";
		while(preg_match($pattern, $body))
		{
			$body = preg_replace($pattern, $replace, $body);
		}

		// Quoting an actual person, book or whatever
		$pattern = '~\[quote=([^]]+)](?:\s)*([^[]*(?:\[(?!/?quote\b)[^[]*)*)\[/quote]~i';
		$replace = '<blockquote class="comment_quote"><cite>Quoting: <span class="username">$1</span></cite>$2</blockquote>';
		while(preg_match($pattern, $body))
		{
			$body = preg_replace($pattern, $replace, $body);
		}
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

	function parse_images($text)
	{
		// cocoen image compare bbcode support
		$text = preg_replace_callback("~\[compare]\[img]([^[]+)\[/img]\[img]([^[]+)\[/img]\[/compare]~i",
		function ($matches)
		{
			return "<div class=\"cocoen\"><img alt=\"\" src=\"".$matches[1]."\" /> <img alt=\"\" src=\"".$matches[2]."\" /></div>";
		},
		$text);

		// markdown images as links
		$text = preg_replace_callback("/\[\!\[([^]]+?)?\]\((($this->protocols)([^ |)]+))\)\]\(($this->protocols)([^ |)]+)\)/is",
		function($matches)
		{
			return "<a href=\"".$matches[5].$matches[6]."\" rel=\"nofollow noopener noreferrer\"><img itemprop=\"image\" src=\"".$matches[2]."\" class=\"img-responsive\" alt=\"".$matches[1]."\" /></a>";
		},
		$text);

		// markdown images
		$text = preg_replace_callback("/\!\[([^]]+?)?\]\((($this->protocols)([^ |)]+))\)/is",
		function($matches)
		{
			return "<a data-fancybox rel=\"group\" href=\"".$matches[2]."\" rel=\"nofollow noopener noreferrer\"><img itemprop=\"image\" src=\"".$matches[2]."\" class=\"img-responsive\" alt=\"".$matches[1]."\" /></a>";
		},
		$text);
		
		return $text;
	}
	
	function parse_links($text)
	{
		// general link matching
		$text = preg_replace("/(\s|^)(($this->protocols)[\w\d\.\/#\_\-\?:=&;@()\%+,~]+)(?:(?<![.,;!?:\"\'-])(\s|.|$))/iu", '$1<a href="$2" target="_blank" rel="nofollow noopener noreferrer">$2</a>$4', $text);
		//<link>
		$text = preg_replace("/(?:&lt;)(($this->protocols)([\S]+))(?:&gt;)/is", "<a href=\"$1\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">$1</a>", $text); 
		// [title](link)
		$text = preg_replace("/\[([^]]+?)\]\((($this->protocols)[\w\d\.\/#\_\-\?:=&;@()\%+,~]+)(?:(?<![.,;!?:\"\'-])\)(\s|.|$))/is", "<a href=\"$2\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">$1</a>$4", $text);

		return $text;
	}

	function parse_lists($text)
	{
		$list_types = array('-', '*');

		$text = preg_replace();

		return $text;
	}

	function parse_bbcode($body, $article = 1)
	{
		//  get rid of empty BBCode, is there a point in having excess markup?
		$body = preg_replace("`\[(b|i|s|u|mail|spoiler|img|quote|color|youtube)\]\[/(b|i|s|u|spoiler|mail|img|quote|color|youtube)\]`",'',$body);

		// Array for tempory storing codeblock contents
		$codeBlocks = [];

		// make all bbcode brackets inside the code tags be the correct html entities to prevent the bbcode inside from parsing
		$body = preg_replace_callback("/`{3}\n?([a-z]*[\s\S]*?\n?)`{3}/",
			function($matches) use(&$codeBlocks)
			{
				$matches[1] = str_replace(' ', '&nbsp;', $matches[1]); // preserve whitespace no matter what
				$codeBlocks[] = str_replace(array('[', ']'), array('&#91;', '&#93;'), "<code class='bbcodeblock'>" . trim($matches[1]) . '</code>');
				end($codeBlocks); //Move array pointer to the end
				$k = key($codeBlocks); //Get the last inserted number
				reset($codeBlocks); //Reset the array pointer to the start

				return "!!@codeblock".$k."!!";
			},
			$body);

		$body = $this->parse_images($body);

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

		$body = $this->quotes($body);		

		$find_replace = array(
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
		"/\[sup\](.+?)\[\/sup\]/is" 
			=> '<sup>$1</sup>',
		"/\[spoiler](.+?)\[\/spoiler\](\r?\n)?/is" 
			=> '<div class="collapse_container"><div class="collapse_header"><span>Spoiler, click me</span></div><div class="collapse_content"><div class="body group">$1</div></div></div>',
		"/(\[split\])(\s)*/is"
			=> '<hr class="content_split">'
		);

		$body = $this->emoticons($body);

		foreach ($find_replace as $find => $replace)
		{
			$body = preg_replace($find, $replace, $body);
		}

		// stop adding breaks to lists
		$body = str_replace('<ul><br />', '<ul>', $body);
		$body = str_replace('</ul><br />', '</ul>', $body);
		$body = str_replace('</li><br />', '</li>', $body);

		// Put the code blocks back in
		foreach ($codeBlocks as $key => $codeblock)
		{
			$body = str_replace("!!@codeblock".$key."!!", $codeblock, $body);
		}

		$body = nl2br($body);

		return $body;
	}

	function email_bbcode($body)
	{
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
		"/\[email\](.+?)\[\/email\]/is",
		"/\[s\](.+?)\[\/s\]/is",
		'/\[list\](.*?)\[\/list\]/is',
		'/\[\*\](.*?)(\n|\r\n?)/is',
		'/\[ul\]/is',
		'/\[\/ul\]/is',
		'/\[li\]/is',
		'/\[\/li\]/is',
		"/\[size\=(.+?)\](.+?)\[\/size\]/is",
		"/\[email\=(.+?)\](.+?)\[\/email\]/is",
		"/\[justify\](.+?)\[\/justify\]/is",
		"/\[spoiler\](.+?)\[\/spoiler\]/is"
		);

		$replace = array(
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
		'<ul>$1</ul>',
		'<li>$1</li>',
		'<ul>',
		'</ul>',
		'<li>',
		'</li>',
		'$2',
		'<a href="mailto:$1">$2</a>',
		'$1',
		'SPOILER: View the website if you wish to see it.'
		);

		$body = preg_replace($find, $replace, $body);

		$body = nl2br($body);

		$body = str_replace('</li><br />', '</li>', $body);

		// stop there being a big gap after a list is finished
		$body = str_replace('</ul><br />', '</ul>', $body);

		return $body;
	}

	function prepare_emoji()
	{
		if (!isset($this->emoji['raw'])) // ensure we only do this once
		{
			$this->emoji['raw'] = $this->dbl->run("SELECT `text_replace`, `filename` FROM `emoji`")->fetch_all(PDO::FETCH_KEY_PAIR);
		}

		$emoji_location = $this->core->config('website_url') . 'templates/' . $this->core->config('template');

		foreach ($this->emoji['raw'] as $key => $value)
		{
			$this->emoji['replaced'][$key] = '<img src="'. $emoji_location . '/images/emoticons/' . $value . '" alt="" />';
		}
	}

	function emoticons($text)
	{
        if ($this->emoji['raw'])
        {
		    $text = str_replace( array_keys( $this->emoji['replaced'] ), array_values( $this->emoji['replaced'] ), $text );
        }
        return $text;
	}

	function youtube_callback($matches)
	{
		if (isset($matches[2])) // custom set preview image
		{
			$load_image = $matches[2];
		}
		else // otherwise see if we have an image stored from YT or use a default image
		{
			// this needs to be improved to be a loop like the image proxy
			$local_cache_maxresdefault = APP_ROOT.'/cache/youtube_thumbs/' . md5("https://img.youtube.com/vi/".$matches[1]."/maxresdefault.jpg") . '.jpg';
			$local_cache_hqdefault = APP_ROOT.'/cache/youtube_thumbs/' . md5("https://img.youtube.com/vi/".$matches[1]."/hqdefault.jpg") . '.jpg';

			// check for highest res local cache
			if (file_exists($local_cache_maxresdefault))
			{
				$load_image = $this->core->config('website_url') . 'cache/youtube_thumbs/' . md5("https://img.youtube.com/vi/".$matches[1]."/maxresdefault.jpg") . '.jpg';
			}
			// check for high quality local cache
			else if (file_exists($local_cache_hqdefault))
			{
				$load_image = $this->core->config('website_url') . 'cache/youtube_thumbs/' . md5("https://img.youtube.com/vi/".$matches[1]."/hqdefault.jpg") . '.jpg';
			}
			else
			{
				$load_image = $this->core->config('website_url') . "templates/default/images/youtube_cache_default.png";
			}
		}

		return "<div class=\"hidden_video\" data-video-id=\"{$matches[1]}\"><img alt=\"YouTube Thumbnail\" src=\"$load_image\"><div class=\"hidden_video_content\">YouTube videos require cookies, you must accept their cookies to view. <a href=\"/index.php?module=cookie_prefs\">View cookie preferences</a>.<br /><span class=\"video_accept_button badge blue\"><a class=\"accept_video\" data-video-id=\"{$matches[1]}\" href=\"#\">Accept Cookies &amp; Show</a> </span> &nbsp; <span class=\"badge blue\"><a href=\"https://www.youtube.com/watch?v={$matches[1]}\" target=\"_blank\">Direct Link</a></span></div></div>";
	}

	// required by GDPR since YouTube don't warn about cookies and tracking before playing
	function youtube_privacy($text)
	{
		if (!isset($_COOKIE['gol_youtube_consent']) || isset($_COOKIE['gol_youtube_consent']) && $_COOKIE['gol_youtube_consent'] == 'nope')
		{
			$text = preg_replace_callback("/\<div class=\"youtube-embed-wrapper\" style=\"(?:.+?)\"\>\<iframe allowfullscreen=\"\" frameborder=\"0\" height=\"360\" src=\"https:\/\/www.youtube-nocookie.com\/embed\/(.+?)(?:\?rel=[0|1])?(?:&amp;start=[0-9]*)?\" style=\"(?:.+?)\" width=\"640\"\>\<\/iframe\>(?:.*?)\<\/div\>/is","bbcode::youtube_callback",$text); // this is for older videos, one day we should convert them all to save a little code here...

			$text = preg_replace_callback("/\<div class=\"youtube-embed-wrapper\" data-video-url=\"https:\/\/www.youtube-nocookie.com\/embed\/(.+?)(?:\?rel=[0|1])?(?:&amp;start=[0-9]*)?\"(?: data-video-urlpreview=\"([^\"]+?)\")? style=\"(?:.+?)\"\>(?:.+?)\<\/div\>/is","bbcode::youtube_callback",$text);
		}
		return $text;
	}

	// remove bits to make sure RSS validates, and to make sure hidden bits don't become available to all
	function rss_stripping($text, $tagline_image = NULL, $gallery_tagline = NULL)
	{
		$text = str_replace('<*PAGE*>', '', $text);

		$text = str_replace('[pcinfo]', '', $text);

		$text = preg_replace("/\[timer=(.+?)](.+?)\[\/timer]/is", ' Visit <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a> to see the timer ', $text);

		$find_replace = [
		'/\[users-only\](.+?)\[\/users-only\]/is'
			=> ' Visit <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a> to see this bit, this is for logged in users only ',
		"/\<div class=\"youtube-embed-wrapper\"(?:\s)?(?:data-video-url=\"https:\/\/www.youtube-nocookie.com\/embed\/(?:.+?)?(?:\?rel=0)?\")?(?:\s)?style=\"(?:.+?)\"\>(?:.*)\<iframe allowfullscreen=\"\" frameborder=\"0\" height=\"360\" src=\"https:\/\/www.youtube-nocookie.com\/embed\/(.+?)(?:\?rel=0)?\" style=\"(?:.+?)\" width=\"640\"\>\<\/iframe\>\<\/div\>/is"
			=> "<a href=\"https://www.youtube.com/watch?v=$1\" rel=\"noopener noreferrer\"><img src=\"https://img.youtube.com/vi/$1/0.jpg\" alt=\"youtube video thumbnail\"><br />Watch video on YouTube.com</a>",
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
				$included_note = '';
				
				$pc_info = $this->user->display_pc_info($_SESSION['user_id']);

				if ($pc_info['counter'] > 0)
				{
					if ($pc_info['empty'] > 0)
					{
						$fields_output .= '<div class="info_box">Info: you currently have <u><strong>' . $pc_info['empty'] . '</strong></u> fields <strong>not</strong> filled out.</div>';
					}

					$fields_output .= '<p>Here\'s what we have for you at the moment:</p>';

					$fields_output .= '<ul style="list-style-type: none; margin: 0; padding: 0;">';
					foreach ($pc_info as $k => $info)
					{
						if ($k != 'counter' && $k != 'date_updated' && $k != 'include_in_survey' && $k != 'empty')
						{
							$fields_output .= '<li>' . $info . '</li>';
						}
					}
					$fields_output .= '</ul>';
		
					if ($pc_info['include_in_survey'] == 0)
					{
						$included_note = '<strong>Note:</strong> You currently have your profile set to <u>not</u> be included. You can <a href="/usercp.php?module=pcinfo">change this option here</a>.';
					}
					else
					{
						$included_note = '<strong>Note:</strong> Your PC info is currently included. You can <a href="/usercp.php?module=pcinfo">change this option here</a>.';
					}

					$included_note .= ' It\'s opt-in and you can uncheck it at any time to not be included again.';
				}
				else
				{
					$fields_output = "<em>You haven't filled yours in! You can <a href=\"/usercp.php?module=pcinfo\">do so here any time</a>.</em>";
				}

				if (!isset($pc_info['date_updated']))
				{
					$date_updated = '<strong>Never</strong>!';
				}
				else
				{
					$date_updated = '<strong>' . date('d M, Y', strtotime($pc_info['date_updated'])) . '</strong>';
				}

				$body = str_replace("[pcinfo]", 'You last updated yours: ' . $date_updated . '. <br /><br />'.$included_note.'<br />' . $fields_output . '<br />', $body);
			}
			else
			{
				$body = str_replace("[pcinfo]", '<em>You need to be logged in to see when you last updated your PC info!</em>', $body);
			}
		}

		return $body;
	}

	// NOT FINISHED
	function display_polls($text, $poll_id)
	{
		// check poll exists
		$this->dbl->run();
		$text = preg_replace("/\[poll\]".$poll_id."\[\/giveaway\]/is", $key_claim, $text);

		return $text;
	}
	
	// convert helpers into the html for displaying on the site
	function article_bbcode($text)
	{
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

		/*
		if (preg_match_all("/\[poll\](.+?)\[\/poll\]/is", $text, $poll_matches))
		{
			foreach ($poll_matches[1] as $match)
			{
				$text = $this->display_polls($text, $match);
			}
		}*/

		$text = $this->youtube_privacy($text);

		$text = $this->do_timers($text);
		
		return $text;
	}
}
?>
