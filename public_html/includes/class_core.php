<?php
define( 'TIMEBEFORE_NOW',         'less than a minute ago' );
define( 'TIMEBEFORE_MINUTE_ABOUT','about a minute ago' );
define( 'TIMEBEFORE_MINUTE',      '{num} minute ago' );
define( 'TIMEBEFORE_MINUTES',     '{num} minutes ago' );
define( 'TIMEBEFORE_HOUR',        '{num} hour ago' );
define( 'TIMEBEFORE_HOURS',       'about {num} hours ago' );
define( 'TIMEBEFORE_YESTERDAY',   'a day ago' );
define( 'TIMEBEFORE_DAYS',    '{num} days ago' );
define( 'TIMEBEFORE_FORMAT',      '%e %b' );
define( 'TIMEBEFORE_FORMAT_YEAR', '%e %b, %Y' );

class core
{	
	protected $dbl;	
	// the current date and time for the mysql
	public static $date;
	
	// the time and date right now in the MySQL timestamp format
	public static $sql_date_now;

	// the users ip address
	public static $ip;

	// how many pages their are in the pagination being done
	public $pages;

	// pagination number to start from for query
	public $start = 0;

	// any message for image uploader
	public $error_message;

	public static $user_chart_js = NULL;

	public static $config = [];

	public static $url_command;
	
	public static $allowed_modules = [];
	
	public static $current_module = [];
	
	public static $top_bar_links = [];

	public static $redis = NULL;

	public static $current_page = NULL;

	function __construct($dbl)
	{	
		header('X-Frame-Options: SAMEORIGIN');
		date_default_timezone_set('UTC');
		
		core::$date = strtotime(gmdate("d-n-Y H:i:s"));
		core::$sql_date_now = date('Y-m-d H:i:s');
		core::$ip = $this->get_client_ip();
		core::$current_page = $this->current_page();
		$this->dbl = $dbl;

		$this->check_dbcache();

		// determin if JS is being served from a static subdomain or not
		$js_location = '';
		if (!empty($this->config('javascript_static')) && $this->config('javascript_static') != '')
		{
			$js_location = $this->config('javascript_static');
		}
		else
		{
			$js_location = $this->config('website_url') . 'includes/jscripts';
		}
		define ('JSSTATIC', $js_location);
	}

	// check redis is installed and running
	public function check_dbcache()
	{
		if (class_exists('Redis')) 
		{
			try 
			{
				core::$redis = new Redis();
				core::$redis->connect('127.0.0.1', 6379);
			}
			catch (Exception $e) 
			{
				error_log($e->getMessage());
			}
		}
	}

	public function get_dbcache($key)
	{
		if (!isset(core::$redis))
		{
			return false;
		}
		else
		{
			return core::$redis->get($key);
		}
	}
	
	public function set_dbcache($key, $data, $expiry = NULL)
	{
		if (!isset(core::$redis))
		{
			return false;
		}
		else
		{
			return core::$redis->set($key, $data, $expiry);
		}	
	}

	public function delete_dbcache($key)
	{
		if (!isset(core::$redis))
		{
			return false;
		}
		else
		{
			return core::$redis->del($key);
		}		
	}

	// check in_array for a multidimensional array
	public static function in_array_r($needle, $haystack) 
	{
		foreach($haystack as $array)
		{
			if(in_array($needle, $array, true))
			{
				return true;
			}
		}
		return false;
	}

	public static function make_safe($text)
	{
		if (is_array($text))
		{
			foreach ($text as $k => $unsafe_text)
			{
				$text[$k] = htmlspecialchars($unsafe_text, ENT_QUOTES);
				$text[$k] = str_replace('{', '&#123;', $unsafe_text);
				$text[$k] = trim($unsafe_text);
			}
		}
		else
		{
			$text = htmlspecialchars($text, ENT_QUOTES);
			$text = str_replace('{', '&#123;', $text);
			$text = trim($text);
		}

		return $text;
	}

	// for validating numbers a bit more thoroughly, for things like ID numbers to use in the database
	// this will work on arrays as well as single digits
	public static function is_number($data)
	{
		if (isset($data))
		{
			if (is_array($data))
			{
				foreach ($data as $test_id)
				{
					if (!is_numeric($test_id))
					{
						return false;
					}
				}
			}
			else if (!is_numeric($data))
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		return true;
	}

	// check the given date and time is actually a valid date and time
	public static function validateDate($date, $format = 'Y-m-d H:i:s') 
	{
		$dateTime = DateTime::createFromFormat($format, $date);
	
		if ($dateTime instanceof DateTime && $dateTime->format($format) == $date) 
		{
			return $dateTime->getTimestamp();
		}
	
		return false;
	}
	// use this on top of javascript, to ensure dates don't flicker on first load
	// tried to match jquery timeago
    function time_ago( $time ) 
    {
        $out    = ''; // what we will print out
        $now    = time(); // current time
        $diff   = $now - $time; // difference between the current and the provided dates

		if( $diff < 45 ) // it happened now
		{ 
            return TIMEBEFORE_NOW;
		}
		else if ($diff >= 45 && $diff < 90)
		{
			return TIMEBEFORE_MINUTE_ABOUT;
		}
		else if( $diff < 3600 ) // it happened X minutes ago
		{
            return str_replace( '{num}', ( $out = round( $diff / 60 ) ), $out == 1 ? TIMEBEFORE_MINUTE : TIMEBEFORE_MINUTES );
		}
		else if( $diff < 3600 * 24 ) // it happened X hours ago
		{
            return str_replace( '{num}', ( $out = round( $diff / 3600 ) ), $out == 1 ? TIMEBEFORE_HOUR : TIMEBEFORE_HOURS );
		}
		else if( $diff < 3600 * 24 * 2 ) // it happened yesterday
		{
            return TIMEBEFORE_YESTERDAY;
		}
		elseif( $diff < 3600 * 24 * 7 )
		{
			return str_replace( '{num}', round( $diff / ( 3600 * 24 ) ), TIMEBEFORE_DAYS );
		}
		else // falling back on a usual date format as it happened later than yesterday
		{
			return strftime( date( 'Y', $time ) == date( 'Y' ) ? TIMEBEFORE_FORMAT : TIMEBEFORE_FORMAT_YEAR, $time );
		}
    }

	// simple helper function to make sure we always have a page number set
	public static function give_page()
	{
		$page = 1;
		if (!isset($_GET['page']) || $_GET['page'] <= 0)
		{
		  $page = 1;
		}

		else if (core::is_number($_GET['page']))
		{
		  $page = $_GET['page'];
		}

		if (self::$current_module['module_file_name'] == 'articles_full' && isset(core::$url_command[3]) && strpos(core::$url_command[3], 'page=') !== false)
		{
			$page = (int) str_replace('page=', '', core::$url_command[3]);
		}

		return $page;
	}

	public static function file_get_contents_curl($url, $type = NULL, $post_fields = NULL, $headers = NULL) 
	{
	    $ch = curl_init($url);
	    
	    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		if ($type == 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		}
		if ($headers != NULL)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		$data = curl_exec($ch);
		
		if (curl_getinfo ( $ch )['http_code'] != 200)
		{
			curl_close ( $ch );
			return false;
		}
		else 
		{
			curl_close ( $ch );
			return $data;
		}
	}

	/* 
	Quickly grab and save an image
	Currently used for steam/gog game importing
	*/
	function save_image($img,$fullpath)
	{
		$ch = curl_init ($img);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$rawdata=curl_exec($ch);
		curl_close ($ch);
		if(file_exists($fullpath)){
			unlink($fullpath);
		}
		$fp = fopen($fullpath,'c');
		if (false === $fp) {
			error_log('Cannot open file for writing.');
		}

		if (fwrite($fp, $rawdata) === FALSE) 
		{
			error_log('Cannot write to file');
		}
		fclose($fp); 
	}
	
	// grab a config key
	public function config($key)
	{
		$get_config = $this->get_dbcache('CONFIG_'.$key);

		if ($get_config === false) // there's no cache
		{
				$get_config = $this->dbl->run("SELECT `data_value` FROM config WHERE `data_key` = ?", array($key))->fetchOne();

				$this->set_dbcache('CONFIG_'.$key, $get_config); // no expiry as config hardly ever changes
		}

		// return the requested key with the value in place
		return $get_config;
	}

	// update a single config var
	function set_config($value, $key)
	{
		$this->dbl->run("UPDATE `config` SET `data_value` = ? WHERE `data_key` = ?", [$value, $key]);

		$this->set_dbcache('CONFIG_'.$key, $value); // no expiry as config hardly ever changes
	}

	function get_client_ip()
	{
		if (isset ($_SERVER ['HTTP_X_FORWARDED_FOR']))
		{
			$clientIP = $_SERVER ['HTTP_X_FORWARDED_FOR'];
		}

		elseif (isset ($_SERVER ['HTTP_X_REAL_IP']))
		{
			$clientIP = $_SERVER ['HTTP_X_REAL_IP'];
		}

		elseif (isset ($_SERVER['REMOTE_ADDR']))
		{
			$clientIP = $_SERVER['REMOTE_ADDR'];
		}

		else
		{
			$clientIP = "127.10.10.1"; //We have no IP, maybe running from CLI?
		}

		return $clientIP;
	}

	// find the current page we are on
	function current_page()
	{
		if (isset($_SERVER["SCRIPT_NAME"]) && !empty($_SERVER["SCRIPT_NAME"]))
		{
			return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
		}
		return false;
	}

	public static function current_page_url()
	{
		$page_url = 'http';
		if (isset($_SERVER["HTTPS"]))
		{
			$page_url .= "s";
		}
		$page_url .= '://' . $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		return $page_url;
	}

	// $date_to_format = a timestamp to make more human readable
	function human_date($timestamp, $format = "j F Y \a\\t g:i a")
	{
		$text = date($format, $timestamp);

		return $text . ' UTC';
	}

	// per page = how many rows to show per page
	// total = total number of rows
	// targetpage = the page to append the pagination target page onto
	// extra = anything extra to add like "#comments" to go to the comments
	function pagination_link($per_page, $total, $targetpage, $page, $extra = NULL)
	{
		// what row number for the query to start from
		if ($page != 1 && $page > 0)
		{
			$this->start = ($page - 1) * $per_page;
		}
		else
		{
			$this->start = 0;
		}

		// make sure it's an int not a string - have to use this as it kept turning into a string somehow when i only pass numbers to it?
		$this->start = intval($this->start);

		//previous page is page - 1
		$prev = $page - 1;

		//next page is page + 1
		$next = $page + 1;

		//lastpage is = total pages / items per page, rounded up.
		$lastpage = ceil($total/$per_page);

		// sort out the pagination links
		$pagination = "";
		if($lastpage > 1)
		{
			$pagination .= "<div class=\"fnone pagination\">";

			//previous button
			if ($page >= 3)
			{
				$pagination.= "<a title=\"First Page\" class=\"live\" data-page=\"{$prev}\" href=\"{$targetpage}page=1\">&laquo; 1</a>";
			}

			if ($page > 1)
			{
				$pagination.= "<a class=\"live\" data-page=\"{$prev}\" href=\"{$targetpage}page=$prev$extra\">&laquo;</a>";
			}

			// current page
			$pagination .= "<a class=\"active\" href=\"#\">$page</a>";

			// seperator
			$pagination .= "<a class=\"seperator\" href=\"#\">/</a>";

			// sort out last page link, no link if on last page
			if ($page == $lastpage)
			{
				$pagination .= "<a class=\"active\" href=\"#\">{$lastpage}</a>";
			}

			else
			{
				$pagination .= "<a class=\"live\" data-page=\"{$lastpage}\" href=\"{$targetpage}page={$lastpage}$extra\">{$lastpage}</a>";
			}

			// next button
			if ($page < $lastpage)
			{
				$pagination .= "<a class=\"live\" data-page=\"{$next}\" href=\"{$targetpage}page=$next$extra\">&raquo;</a>";
			}

			$pagination .= '</div>';
		}

		return $pagination;
	}

	// per page = how many rows to show per page
	// total = total number of rows
	// targetpage = the page to append the pagination target page onto
	// extra = anything extra to add like "#comments" to go to the comments
	function head_pagination($per_page, $total, $targetpage, $page, $extra = NULL)
	{
		// what row number for the query to start from
		if ($page != 1 && $page > 0)
		{
			$this->start = ($page - 1) * $per_page;
		}
		else
		{
			$this->start = 0;
		}

		// make sure it's an int not a string - have to use this as it kept turning into a string somehow when i only pass numbers to it?
		$this->start = intval($this->start);

		//previous page is page - 1
		$prev = $page - 1;

		//next page is page + 1
		$next = $page + 1;

		//lastpage is = total pages / items per page, rounded up.
		$lastpage = ceil($total/$per_page);

		// sort out the pagination links
		$pagination = "";
		if ($lastpage > 1)
		{
			$pagination .= "Page: ";
		}
		if($lastpage > 1)
		{
			//previous button
			if ($page > 1)
			{
				$pagination.= "<a data-page=\"{$prev}\" href=\"{$targetpage}page=$prev$extra\"><span class=\"previouspage\">&laquo;</span></a>";
			}

			// current page
			$pagination .= "<span class=\"pagination-disabled\">$page</span>";
			// seperator
			$pagination .= "<span class=\"pagination-disabled\">/</span>";

			// sort out last page link, no link if on last page
			if ($page == $lastpage)
			{
				$pagination .= "<a href=\"#\"><span class=\"pagination-disabled\">{$lastpage}</span></a>";
			}
			else
			{
				$pagination .= "<a data-page=\"{$lastpage}\" href=\"{$targetpage}page={$lastpage}$extra\"><span>{$lastpage}</span></a>";
			}

			// next button
			if ($page < $lastpage)
			{
				$pagination .= "<a data-page=\"{$next}\" href=\"{$targetpage}page=$next$extra\"><span class=\"nextpage\">&raquo;</span></a>";
			}

			$pagination .= "<form name=\"form2\" class=\"form-inline\">&nbsp; Go to: <select class=\"wrap ays-ignore\" name=\"jumpmenu\">";

			for ($i = 1; $i <= $lastpage; $i++)
			{
				$selected = '';
				if ($page == $i)
				{
					$selected = 'selected';
				}
				$pagination .= "<option data-page=\"{$i}\" value=\"{$targetpage}page={$i}{$extra}\" $selected>$i</option>";
			}

			$pagination .= '</select></form>';
		}

		return $pagination;
	}

	// $message = what to show them
	function message($message, $urgent = 0)
	{
		global $templating;

		if (!is_object($templating)) return; //your globals are fucked, bail

		$templating->load('messages');
		$templating->block('message');

		if ($urgent == 0)
		{
			$templating->set('type', '');
		}

		else if ($urgent == 1)
		{
			$templating->set('type', 'error');
		}

		else if ($urgent == 2)
		{
			$templating->set('type', 'warning');
		}

		$templating->set('message', $message);
	}

	// check for multiple things being empty and return the name of what was empty
	public static function mempty()
	{
		foreach(func_get_args()[0] as $key => $arg)
		{
			if(empty($arg))
			{
				return $key;
			}
			else
			{
				continue;
			}
		}
		return true;
	}

	// $message = what to ask them
	// $action_url = whatever the press it will go to this page
	// $act = a hidden $_POST box to define where to send them on the action url
	// $act2 = if we need a second stage act for any reason
	// $act2_custom_name = incase we aren't using "act2" (maybe i should go through and re-name all custom second bits like "moderator_options" to "act2" to just simplify it?)
	function yes_no($message, $action_url, $act = NULL, $act2 = NULL, $act2_custom_name = 'act2', $extra_content = NULL)
	{
		global $templating;

		$templating->load('messages');
		$templating->block('yes_no');
		$extra = '';
		if ($extra_content != NULL)
		{
			$extra = $extra_content;
		}
		$templating->set('extra_content', $extra);
		$templating->set('message', $message);
		$templating->set('action_url', $action_url);
		$templating->set('act', $act);

		if ($act2 == NULL)
		{
			$act2_text = '';
		}

		else
		{
			$act2_text = "<input type=\"hidden\" name=\"$act2_custom_name\" value=\"$act2\" />";
		}
		$templating->set('act2', $act2_text);
	}
	
	// a yes/no confirmation box
	// this better one is to eventually replace the older one above
	function confirmation($details)
	{
		global $templating;

		$templating->load('messages');
		$templating->block('confirmation');
		$templating->set('title', $details['title']);
		
		$text = '';
		if (isset($details['text']))
		{
			$text = $details['text'];
		}
		$templating->set('text', $text);
		
		$templating->set('action_url', $details['action_url']);
		$templating->set('act', $details['act']);

		if (!isset($details['act_2_name']))
		{
			$act2_text = '';
		}

		else
		{
			$act2_text = "<input type=\"hidden\" name=\"{$details['act_2_name']}\" value=\"{$details['act_2_value']}\" />";
		}
		$templating->set('act2', $act2_text);
	}
	
	public static function nice_title($title)
	{
		$clean = trim($title);
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(rtrim($clean));
		$clean = preg_replace("/[\/_|+ -]+/", '-', $clean);

		return $clean;
	}

	// move previously uploaded tagline image to correct directory
	function move_temp_image($article_id, $file, $text)
	{
		$types = array('jpg', 'png', 'gif');
		$full_file_big = $this->config('path') . "uploads/articles/tagline_images/temp/" . $file;
		$full_file_thumbnail = $this->config('path') . "uploads/articles/tagline_images/temp/thumbnails/" . $file;

		if (!file_exists($full_file_big))
		{
			$this->error_message = "Could not find temp image to load? $full_file_big";
			return false;
		}

		if (!file_exists($full_file_thumbnail))
		{
			$this->error_message = "Could not find temp thumbnail image to load? $full_file_thumbnail";
			return false;
		}

		else
		{
			$image_info = getimagesize($full_file_big);
			$image_type = $image_info[2];
			$file_ext = '';
			if( $image_type == IMAGETYPE_JPEG )
			{
				$file_ext = 'jpg';
			}

			else if( $image_type == IMAGETYPE_GIF )
			{
				$file_ext = 'gif';
			}

			else if( $image_type == IMAGETYPE_PNG )
			{
				$file_ext = 'png';
			}

			// give the image a random file name
			$imagename = rand() . 'id' . $article_id . 'gol.' . $file_ext;

			// set the main image directory
			$source = $full_file_big;
			$target = $this->config('path') . "uploads/articles/tagline_images/" . $imagename;

			// set thumbnail directory
			$source_thumbnail = $full_file_thumbnail;
			$target_thumbnail = $this->config('path') . "uploads/articles/tagline_images/thumbnails/" . $imagename;		

			if (rename($source, $target) && rename($source_thumbnail, $target_thumbnail))
			{
				$image = $this->dbl->run("SELECT `tagline_image` FROM `articles` WHERE `article_id` = ?", array($article_id))->fetch();

				// remove old image
				if (isset($image))
				{
					if (!empty($image['tagline_image']))
					{
						unlink($this->config('path') . 'uploads/articles/tagline_images/' . $image['tagline_image']);
						unlink($this->config('path') . 'uploads/articles/tagline_images/thumbnails/' . $image['tagline_image']);
					}
				}
				
				// replace the temp filename with the new filename
				$text = preg_replace('/(<img src=".+temp\/thumbnails\/.+" \/>)/', '<img src="'.$this->config('website_url').'uploads/articles/tagline_images/thumbnails/'.$imagename.'" />', $text);
				$text = preg_replace('/<img src=".+temp\/.+" \/>/', '<img src="'.$this->config('website_url').'uploads/articles/tagline_images/'.$imagename.'" />', $text);

				$this->dbl->run("UPDATE `articles` SET `tagline_image` = ?, `gallery_tagline` = 0, `text` = ? WHERE `article_id` = ?", array($imagename, $text, $article_id));
				return true;
			}

			else
			{
				$this->error_message = 'Could not move temp file to tagline images uploads folder!';
				return false;
			}
		}
	}
	
	/* For generating a bbcode editor form, options are:
	name - name of the textarea
	content
	article_editor
	disabled
	anchor_name
	ays_ignore
	editor_id
	*/
	// include this anywhere to show the bbcode editor
	function article_editor($custom_options)
	{
		global $templating;
		
		if (!is_array($custom_options))
		{
			die('CKEditor editor not setup correctly!');
		}
		
		// sort some defaults
		$editor['disabled'] = 0;
		$editor['ays_ignore'] = 0;
		$editor['content'] = '';
		$editor['anchor_name'] = 'commentbox';
		
		foreach ($custom_options as $option => $value)
		{
			$editor[$option] = $value;
		}
		
		$templating->load('ckeditor');
		$templating->block('editor');
		$templating->set('this_template', $this->config('website_url') . 'templates/' . $this->config('template'));
		$templating->set('url', $this->config('website_url'));
		$templating->set('content', $editor['content']);
		$templating->set('anchor_name', $editor['anchor_name']);
		
		$disabled = '';
		if ($editor['disabled'] == 1)
		{
			$disabled = 'disabled';
		}
		$templating->set('disabled', $disabled);

		$ays_check = '';
		if ($editor['ays_ignore'] == 1)
		{
			$ays_check = 'class="ays-ignore"';
		}
		$templating->set('ays_ignore', $ays_check);
	}

	// convert bytes to human readable stuffs, only up to MB as we will never be uploading more than MB files directly
	public static function readable_bytes($bytes, $decimals = 2)
	{
	  $kilobyte = 1024;
	  $megabyte = $kilobyte * 1024;

	  if (($bytes >= 0) && ($bytes < $kilobyte))
		{
	    return $bytes . ' B';
	  }
		else if (($bytes >= $kilobyte) && ($bytes < $megabyte))
		{
	    return round($bytes / $kilobyte, $decimals) . ' KB';
	  }
		else if (($bytes >= $megabyte))
		{
	    return round($bytes / $megabyte, $decimals) . ' MB';
	  }
		// not really needed, but in case i accidentally don't put something to even 1KB on upload limits
		else
		{
	    return $bytes . ' B';
	  }
	}

	public static function random_id($length = 10)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@-_';
		$characters_length = strlen($characters);
		$random_string = '';
		for ($i = 0; $i < $length; $i++)
		{
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}
		return $random_string;
	}

	function process_livestream_users($livestream_id, $user_ids)
	{
		if (isset($livestream_id) && is_numeric($livestream_id))
		{
			// find existing users, if any
			$current_users = $this->dbl->run("SELECT `user_id` FROM `livestream_presenters` WHERE `livestream_id` = ?", array($livestream_id))->fetch_all(PDO::FETCH_COLUMN);

			// if the existing users aren't in the new list, remove them
			if ($current_users)
			{
				foreach ($current_users as $current_user)
				{
					if (!in_array($current_user, $user_ids))
					{
						$this->dbl->run("DELETE FROM `livestream_presenters` WHERE `livestream_id` = ? AND `user_id` = ?", array($livestream_id, $current_user));
					}
				}
			}
			
			// we have a list of user ids
			if (!empty($user_ids) && is_array($user_ids))
			{
				foreach($user_ids as $streamer_id)
				{
					// if this user_id isn't in the current list, add them
					if (!in_array($streamer_id, $current_users))
					{
						$this->dbl->run("INSERT INTO `livestream_presenters` SET `livestream_id` = ?, `user_id` = ?", array($livestream_id, $streamer_id));
					}
				}				
			}
		}
	}

	function check_ip_from_stopforumspam($ip)
	{
		$url = "https://api.stopforumspam.org/api?f=json&ip=" . $ip;
		$json = self::file_get_contents_curl($url);
		$json = json_decode($json, true);
		if ( $json["ip"]["appears"] == 1 )
		{
			$_SESSION['message'] = 'spam';
			header('Location: /index.php?module=home');
			die();
		}
	}

	// this makes an auto-generated list of all timezones
	public static function timezone_list($current_timezone = NULL)
	{
		$timezone_list = '<select name="timezone">';
		$tz_list = DateTimeZone::listIdentifiers( DateTimeZone::ALL );

		$timezone_offsets = array();
		foreach( $tz_list as $timezone )
		{
			$tz = new DateTimeZone($timezone);
			$timezone_offsets[$timezone] = $tz->getOffset(new DateTime);
		}
		
		foreach( $timezone_offsets as $timezone => $offset )
		{
			$offset_prefix = $offset < 0 ? '-' : '+';
			$offset_formatted = gmdate( 'H:i', abs($offset) );

			$pretty_offset = "UTC${offset_prefix}${offset_formatted}";
			
			$selected = '';
			if ($current_timezone != NULL && !empty($current_timezone) && $current_timezone == $timezone)
			{
				$selected = 'selected';
			}
			else if ($current_timezone == NULL || empty($current_timezone) && $timezone == 'UTC')
			{
				$selected = 'selected';
			}
			$timezone_list .= '<option value="'.$timezone.'" '.$selected.'>'.$timezone.' ('.$pretty_offset.')</option>';
		}
		
		$timezone_list .= '</select>';

		return $timezone_list;
	}
	
	public static function adjust_time($date, $from = 'UTC', $to = 'UTC', $show_zone = 1)
	{
		if (empty($from) || $from == NULL)
		{
			$from = 'UTC';
		}
		if (empty($to) || $to == NULL)
		{
			$to = 'UTC';
		}
		$given = new DateTime($date, new DateTimeZone($from));
		$given->setTimezone(new DateTimeZone($to));
		
		$output = $given->format("Y-m-d H:i:s"); 
		if ($show_zone == 1)
		{
			$output .= ' (' . $to . ')';
		}
		
		return $output;
	}
	
	public function load_modules($options)
	{
		$module_links = '';

		if (($fetch_modules = unserialize($this->get_dbcache('active_main_modules'))) === false) // there's no cache
		{
			$fetch_modules = $this->dbl->run('SELECT `module_id`, `module_file_name`, `nice_title`, `nice_link`, `sections_link` FROM `'.$options['db_table'].'` WHERE `activated` = 1 ORDER BY `nice_title` ASC')->fetch_all();
			$this->set_dbcache('active_main_modules', serialize($fetch_modules)); // no expiry as shown blocks hardly ever changes
		}
		
		foreach ($fetch_modules as $modules)
		{
			// modules allowed for loading
			self::$allowed_modules[$modules['module_file_name']] = $modules;
			
			if ($modules['sections_link'] == 1)
			{
				// sort out links to be placed in the navbar
				$section_link = self::config('website_url') . 'index.php?module=' . $modules['module_file_name'];
				if (!empty($modules['nice_link']) && $modules['nice_link'] != NULL)
				{
					$section_link = self::config('website_url') . $modules['nice_link'];
				}
				self::$top_bar_links[] = '<li><a href="'.$section_link.'">'.$modules['nice_title'].'</a></li>';
			}
		}

		// modules loading with basic normal PHP ?module= URL, first are we asked to load a module, if not use the default
		if (isset($_GET['module']))
		{
			if (array_key_exists($_GET['module'], self::$allowed_modules))
			{
				self::$current_module = self::$allowed_modules[$_GET['module']];
			}
			else
			{
				self::$current_module = self::$allowed_modules['404'];
			}
		}
		// "friendly" URL support
		else if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '/')
		{
			$get_url = parse_url($_SERVER['REQUEST_URI']);

			// deal with extra silly slashes included somehow, send to the correct URL
			if (preg_match('~(\/{2,})~', $_SERVER['REQUEST_URI']))
			{
				$_SERVER['REQUEST_URI'] = preg_replace('~/+~', '/', $_SERVER['REQUEST_URI']);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: " . substr($this->config('website_url'), 0, -1) . $_SERVER['REQUEST_URI']); // substr to remove the extra slash we end up with
				die();
			}
			
			if (isset($get_url['path']))
			{
				$module = NULL;
				self::$url_command = explode('/', trim($get_url['path'], '/'));

				if (isset(self::$url_command[0]))
				{
					// an article
					if (strlen(self::$url_command[0]) == 4 && is_numeric(self::$url_command[0]))
					{
						if (isset(self::$url_command[1]) && strlen(self::$url_command[1]) == 2 && is_numeric(self::$url_command[1]))
						{
							if (isset(self::$url_command[2]))
							{
								$module = 'articles_full';
							}
						}
					}
					// forum
					if (self::$url_command[0] == 'forum')
					{
						if (!isset(self::$url_command[1]) || isset(self::$url_command[1]) && empty(self::$url_command[1]))
						{
							$module = 'forum';
						}
						else if (isset(self::$url_command[1]) && self::$url_command[1] != 'topic')
						{
							$module = 'viewforum';
						}
                    }
                    
					// user profiles
					if (self::$url_command[0] == 'profiles')
					{
						if (isset(self::$url_command[1]) && !empty(self::$url_command[1]))
						{
							$module = 'profile';
						}
					}
				}

				// now see if we can go to it
				if (array_key_exists($module, self::$allowed_modules))
				{
					self::$current_module = self::$allowed_modules[$module];
				}
				else
				{
					self::$current_module = self::$allowed_modules['404'];
				}
			}
			else
			{
				self::$current_module = self::$allowed_modules[$this->config('default_module')];
			}	
		}

		else
		{
			self::$current_module = self::$allowed_modules[$this->config('default_module')];
		}
	}

	/* games database */
	// list the genres for the current game
	function display_game_genres($game_id = NULL, $in_list = TRUE)
	{
		// for a specific game
		if (isset($game_id) && self::is_number($game_id))
		{
			// sort out genre tags
			$genre_list = NULL;
			$grab_genres = $this->dbl->run("SELECT g.`category_id`, g.category_name FROM `game_genres_reference` r INNER JOIN `articles_categorys` g ON r.genre_id = g.category_id WHERE r.`game_id` = ?", array($game_id))->fetch_all();
			foreach ($grab_genres as $genres)
			{
				if ($in_list == TRUE)
				{
					$genre_list .= '<option value="'.$genres['category_id'].'" selected>'.$genres['category_name'].'</option>';
				}
				else
				{
					$genre_list[] = $genres['category_name'];
				}
			}
		}
		return $genre_list;
	}
	
	// for editing a game in the database, adjust what genre's it's linked with
	function process_game_genres($game_id)
	{
		if (isset($game_id) && is_numeric($game_id))
		{
			// delete any existing genres that aren't in the final list for publishing
			$current_genres = $this->dbl->run("SELECT `id`, `game_id`, `genre_id` FROM `game_genres_reference` WHERE `game_id` = ?", array($game_id))->fetch_all();
			if (!empty($current_genres))
			{
				foreach ($current_genres as $current_genre)
				{
					if (!in_array($current_genre['genre_id'], $_POST['genre_ids']))
					{
						$this->dbl->run("DELETE FROM `game_genres_reference` WHERE `genre_id` = ? AND `game_id` = ?", array($current_genre['genre_id'], $game_id));
					}
				}
			}

			// get fresh list of genres, and insert any that don't exist
			$current_genres = $this->dbl->run("SELECT `genre_id` FROM `game_genres_reference` WHERE `game_id` = ?", array($game_id))->fetch_all(PDO::FETCH_COLUMN, 0);

			if (isset($_POST['genre_ids']) && !empty($_POST['genre_ids']) && core::is_number($_POST['genre_ids']))
			{
				foreach($_POST['genre_ids'] as $genre_id)
				{
					if (!in_array($genre_id, $current_genres))
					{
						$this->dbl->run("INSERT INTO `game_genres_reference` SET `game_id` = ?, `genre_id` = ?", array($game_id, $genre_id));
					}
				}
			}
		}
	}

	// for inserting a new admin notification
	function new_admin_note($options)
	{
		$completed = 0;
		$completed_date = NULL;
		if (isset($options['completed']) && $options['completed'] == 1)
		{
			$completed = 1;
			$completed_date = core::$date;
		}

		$type = NULL;
		if (isset($options['type']))
		{
			$type = $options['type'];
		}

		$data = NULL;
		if (isset($options['data']))
		{
			$data = $options['data'];
		}		

		$this->dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = ?, `created_date` = ?, `completed_date` = ?, `content` = ?, `type` = ?, `data` = ?", array($_SESSION['user_id'], $completed, core::$date, $completed_date, $options['content'], $type, $data));
	}

	// setting an admin notification as completed
	function update_admin_note($options)
	{
		$extra_sql_where = '';
		$extra_values = [];
		if (isset($options['sql_where']))
		{
			$extra_sql_where = ' AND ' . $options['sql_where']['fields'];
			$extra_values[] = $options['sql_where']['values'];
		}

		/* either a single type of notification (=) or when looking for multiple (IN)
		Some updates may need to wipe multiple types of notifications
		*/
		$type_search = '=';
		$type_value = '?';
		if (isset($options['type_search']) && $options['type_search'] == 'IN')
		{
			$type_search = 'IN';
			$type_value = '(' . str_repeat('?,', count($options['type']) - 1) . '?)';
		}
		else
		{
			$options['type'] = [$options['type']];
		}
		$this->dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` $type_search $type_value AND `data` = ? $extra_sql_where", array_merge([core::$date], $options['type'], [$options['data']], $extra_values));
	}

    function delete_folder($dir)
    { 
		if (empty($dir) || $dir == NULL || $dir == '/' || !is_dir($dir))
		{
			return false;
		}
		
        $files = array_diff(scandir($dir), array('.', '..')); 

        foreach ($files as $file) { 
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
        }

        return rmdir($dir); 
    } 
}
?>
