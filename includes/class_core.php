<?php
class core
{
	// the current date and time for the mysql
	public static $date;

	// the users ip address
	public static $ip;

	// how many pages their are in the pagination being done
	public $pages;

	// pagination number to start from for query
	public $start = 0;

	// any message for image uploader
	public $error_message;

	public static $user_graphs_js = '';

	protected static $config = array();

	function __construct()
	{
		core::$date = strtotime(gmdate("d-n-Y H:i:s"));
		core::$ip = $this->get_client_ip();
	}

	public static function genEmailCode($id)
	{
		include_once dirname(__FILE__).'/hashids/HashGenerator.php';
		include_once dirname(__FILE__).'/hashids/Hashids.php';

		$hashids = new Hashids\Hashids('GoL sends email');
		return $hashids->encode($id);
	}

	public static function genReplyAddress($id, $type)
	{
		if (!in_array($type, ['comment', 'forum', 'editor', 'admin'])) return false;
		return static::genEmailCode($id)."-".$type."@mail.gamingonlinux.com";
	}

	function file_get_contents_curl($url) {
	    $ch = curl_init();

	    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

	    $data = curl_exec($ch);
	    curl_close($ch);

	    return $data;
	}

	// grab a config key
	public static function config($key)
	{
		global $db;

		if (empty(self::$config))
		{
			// get config
			$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
			$fetch_config = $db->fetch_all_rows();

			foreach ($fetch_config as $config_set)
			{
				self::$config[$config_set['data_key']] = $config_set['data_value'];
			}
		}

		// return the requested key with the value in place
		return self::$config[$key];
	}

	// update a single config var
	function set_config($value, $key)
	{
		global $db;
		$db->sqlquery("UPDATE `config` SET `data_value` = ? WHERE `data_key` = ?", array($value, $key));

		// invalidate the cache
		self::$config = array();
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
		return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
	}

	// find the current page we are on with path for sql errors handling
	function current_page_path()
	{
		return $_SERVER["SCRIPT_FILENAME"];
	}

	// $date_to_format = a timestamp to make more human readable
	function format_date($timestamp, $format = "j F Y \a\\t g:i a")
	{
		$text = date($format, $timestamp);

		return $text . ' UTC';
	}

	function normal_date($timestamp, $format = "F j, Y \a\\t g:i a")
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
			$pagination .= "<div class=\"fnone\"><ul class=\"pagination\">";

			//previous button
			if ($page > 1)
			{
				$pagination.= "<li class=\"previouspage\"><a href=\"{$targetpage}page=$prev$extra\">&laquo;</a></li>";
			}

			else
			{
				$pagination.= "<li class=\"pagination-disabled previouspage\"><a href=\"#\">&laquo;</a></li>";
			}

			// current page
			$pagination .= "<li class=\"pagination-disabled active\"><a href=\"#\">$page</a></li>";

			// seperator
			$pagination .= "<li class=\"pagination-disabled\"><a href=\"#\">/</a></li>";

			// sort out last page link, no link if on last page
			if ($page == $lastpage)
			{
				$pagination .= "<li class=\"pagination-disabled\"><a href=\"#\">{$lastpage}</a></li>";
			}

			else
			{
				$pagination .= "<li><a href=\"{$targetpage}page={$lastpage}$extra\">{$lastpage}</a></li>";
			}

			// next button
			if ($page < $lastpage)
			{
				$pagination .= "<li class=\"nextpage\"><a href=\"{$targetpage}page=$next$extra\">&raquo;</a></li>";
			}

			else
			{
				$pagination .= "<li class=\"pagination-disabled nextpage\"><a href=\"#\">&raquo;</a></li>";
			}

			$pagination .= "</ul>";

			$pagination .= "</div> <div class=\"fnone\">
			<form name=\"form2\" class=\"form-inline\">
			 &nbsp; Go to: <select class=\"wrap ays-ignore\" name=\"jumpmenu\" onchange=\"window.open(this.options[this.selectedIndex].value, '_self')\">";

			for ($i = 1; $i <= $lastpage; $i++)
			{
				$selected = '';
				if ($page == $i)
				{
					$selected = 'selected';
				}
				$pagination .= "<option value=\"{$targetpage}page={$i}{$extra}\" $selected>$i</option>";
			}

			$pagination .= '</select></form></div>';
		}

		return $pagination;
	}

	// per page = how many rows to show per page
	// total = total number of rows
	// targetpage = the page to append the pagination target page onto
	// extra = anything extra to add like "#comments" to go to the comments
	function article_pagination($page, $lastpage, $targetpage)
	{
		//previous page is page - 1
		$prev = $page - 1;

		//next page is page + 1
		$next = $page + 1;

		// sort out the pagination links
		$article_pagination = "";
		if($lastpage > 1)
		{
			$article_pagination .= "<div class=\"pagination group\"><ul class=\"pagination fleft\">";

			//previous button
			if ($page > 1)
			{
				$article_pagination.= "<li class=\"previouspage\"><a href=\"{$targetpage}article_page=$prev\">&laquo;</a></li>";
			}

			else
			{
				$article_pagination.= "<li><span>&laquo;</span></li>";
			}

			$article_pagination .= "<li><a href=\"#\">$page</a></li>";

			$article_pagination .= "<li><span>/</span></li>";

			// sort out last page link, no link if on last page
			if ($page == $lastpage)
			{
				$article_pagination .= "<li><span>{$lastpage}</span></li>";
			}

			else
			{
				$article_pagination.= "<li><a href=\"{$targetpage}article_page={$lastpage}\">{$lastpage}</a></li>";
			}

			// next button
			if ($page < $lastpage)
			{
				$article_pagination .= "<li><a href=\"{$targetpage}article_page=$next\">&raquo;</a></li>";
			}

			else
			{
				$article_pagination .= "<li><span>&raquo;</span></li>";
			}

			$article_pagination .= "</ul>";


			$article_pagination .= "<form name=\"form2\" class=\"form-inline\">
			&nbsp; Go to: <select class=\"dropdown\" style=\"width: auto;\" name=\"jumpmenu\" onchange=\"window.open(this.options[this.selectedIndex].value, '_self')\">";

			for ($i = 1; $i <= $lastpage; $i++)
			{
				$selected = '';
				if ($i == $page)
				{
					$selected = 'selected';
				}
				$article_pagination .= "<option value=\"{$targetpage}article_page={$i}\" $selected>$i</option>";
			}

			$article_pagination .= '</select></form></div>';
		}

		return $article_pagination;
	}

	// $message = what to show them
	function message($message, $redirect = NULL, $urgent = 0)
	{
		global $templating;

		if (!is_object($templating)) return; //your globals are fucked, bail

		$templating->merge('messages');

		if ($urgent == 0)
		{
			$templating->block('message');

		}

		else if ($urgent == 1)
		{
			$templating->block('errormessage');
		}

		else if ($urgent == 2)
		{
			$templating->block('warningmessage');
		}

		$templating->set('message', $message);

		if ($redirect != NULL)
		{
			$templating->block('redirect');
			$templating->set('redirect', $redirect);
		}
	}

	// $message = what to ask them
	// $action_url = whatever the press it will go to this page
	// $act = a hidden $_POST box to define where to send them on the action url
	// $act2 = if we need a second stage act for any reason
	// $act2_custom_name = incase we aren't using "act2" (maybe i should go through and re-name all custom second bits like "moderator_options" to "act2" to just simplify it?)
	function yes_no($message, $action_url, $act = NULL, $act2 = NULL, $act2_custom_name = 'act2')
	{
		global $templating;

		$templating->merge('messages');
		$templating->block('yes_no');
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

	// check permissions, done from primary user group as thats where your main permissions come from, secondary user group should only be used for site extras anyway
	function forum_permissions($id)
	{
		global $db, $parray;

		$forum_id = $id;
		$group_id = $_SESSION['user_group'];

		$sql_permissions = "
		SELECT
			`can_view`,
			`can_topic`,
			`can_reply`,
			`can_lock`,
			`can_sticky`,
			`can_delete`,
			`can_delete_own`,
			`can_avoid_floods`,
			`can_move`
		FROM
			`forum_permissions`
		WHERE
			`forum_id` = ? AND `group_id` = ?
		";

		$db->sqlquery($sql_permissions, array($forum_id, $group_id));

		$permission = $db->fetch();

		$parray = array();

		// set the permissions
		$parray['view'] = 0;
		if ($permission['can_view'] == 1)
		{
			$parray['view'] = 1;
		}

		$parray['topic'] = 0;
		if ($permission['can_topic'] == 1)
		{
			$parray['topic'] = 1;
		}

		$parray['reply'] = 0;
		if ($permission['can_reply'] == 1)
		{
			$parray['reply'] = 1;
		}

		$parray['lock'] = 0;
		if ($permission['can_lock'] == 1)
		{
			$parray['lock'] = 1;
		}

		$parray['sticky'] = 0;
		if ($permission['can_sticky'] == 1)
		{
			$parray['sticky'] = 1;
		}

		$parray['delete'] = 0;
		if ($permission['can_delete'] == 1)
		{
			$parray['delete'] = 1;
		}

		$parray['delete_own'] = 0;
		if ($permission['can_delete_own'] == 1)
		{
			$parray['delete_own'] = 1;
		}

		$parray['avoid_floods'] = 0;
		if ($permission['can_avoid_floods'] == 1)
		{
			$parray['avoid_floods'] = 1;
		}

		$parray['can_move'] = 0;
		if ($permission['can_move'] == 1)
		{
			$parray['can_move'] = 1;
		}
	}

	function nice_title($title)
	{
		$clean = trim($title);
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", '-', $clean);

		return $clean;
	}

	// move previously uploaded tagline image to correct directory
	function move_temp_image($article_id, $file)
	{
		global $db;

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

			// the actual image
			$source = $full_file_big;

			// where to upload to
			$target = $this->config('path') . "uploads/articles/tagline_images/" . $imagename;

			// the actual image
			$source_thumbnail = $full_file_thumbnail;

			// where to upload to
			$target_thumbnail = $this->config('path') . "uploads/articles/tagline_images/thumbnails/" . $imagename;

			if (rename($source, $target) && rename($source_thumbnail, $target_thumbnail))
			{
				$db->sqlquery("SELECT `article_top_image`, `article_top_image_filename`,`tagline_image`  FROM `articles` WHERE `article_id` = ?", array($article_id));
				$image = $db->fetch();

				// remove old image
				if (isset($image))
				{
					if ($image['article_top_image'] == 1)
					{
						unlink($this->config('path') . 'uploads/articles/topimages/' . $image['article_top_image_filename']);
					}
					if (!empty($image['tagline_image']))
					{
						unlink($this->config('path') . 'uploads/articles/tagline_images/' . $image['tagline_image']);
						unlink($this->config('path') . 'uploads/articles/tagline_images/thumbnails/' . $image['tagline_image']);
					}
				}

				$db->sqlquery("UPDATE `articles` SET `tagline_image` = ?, `article_top_image_filename` = '', `article_top_image` = 0 WHERE `article_id` = ?", array($imagename, $article_id));
				return true;
			}

			else
			{
				$this->error_message = 'Could not move temp file to tagline images uploads folder!';
				return false;
			}
		}
	}

	// $new has to be either 1 or 0
	// 1 = new article, 0 = editing the current image
	function carousel_image($article_id, $new = NULL)
	{
		global $db;

		if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 4)
		{
			return 'nofile';
		}

		$allowed =  array('gif', 'png' ,'jpg');
		$filename = $_FILES['new_image']['name'];
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if(!in_array($ext,$allowed) )
		{
    	return 'filetype';
		}

		// this will make sure it is an image file, if it cant get an image size then its not an image
		if (!getimagesize($_FILES['new_image']['tmp_name']))
		{
			return 'filetype';
		}

		if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0)
		{
			if (!@fopen($_FILES['new_image']['tmp_name'], 'r'))
			{
				return 'nofile';
			}

			else
			{
				// check the dimensions
				$image_info = getimagesize($_FILES['new_image']['tmp_name']);
				$image_type = $image_info[2];

				list($width, $height, $type, $attr) = $image_info;

				if ($width < $this->config('carousel_image_width') || $height < $this->config('carousel_image_height'))
				{
					// include the image class to resize it as its too big
					include('includes/class_image.php');
					$image = new SimpleImage();
					$image->load($_FILES['new_image']['tmp_name']);
					$image->resize(core::config('carousel_image_width'),core::config('carousel_image_height'));
					$image->save($_FILES['new_image']['tmp_name'], $image_type);

					// just double check it's now the right size (just a failsafe, should never happen but no harm in double checking resizing worked)
					list($width, $height, $type, $attr) = getimagesize($_FILES['new_image']['tmp_name']);
					if ($width != $this->config('carousel_image_width') || $height != $this->config('carousel_image_height'))
					{
						return 'dimensions';
					}
				}

				// check if its too big
				if ($_FILES['new_image']['size'] > 305900)
				{
					$image_info = getimagesize($_FILES['new_image']['tmp_name']);
					$image_type = $image_info[2];
					if( $image_type == IMAGETYPE_JPEG )
					{
						$oldImage = imagecreatefromjpeg($_FILES['new_image']['tmp_name']);
						imagejpeg($oldImage, $_FILES['new_image']['tmp_name'], 90);
					}

					// cannot compress gifs so it's just too big
					else if( $image_type == IMAGETYPE_GIF )
					{
						return 'File size too big! The max is 300kb, try to use some more compression on it, or find another image.';
					}

					else if( $image_type == IMAGETYPE_PNG )
					{
						$oldImage = imagecreatefrompng($_FILES['new_image']['tmp_name']);
						imagepng($oldImage, $_FILES['new_image']['tmp_name'], 7);
					}

					clearstatcache();

					// check again
					if (filesize($_FILES['new_image']['tmp_name']) > 305900)
					{
						// try reducing it some more
						if( $image_type == IMAGETYPE_JPEG )
						{
							$oldImage = imagecreatefromjpeg($_FILES['new_image']['tmp_name']);
							imagejpeg($oldImage, $_FILES['new_image']['tmp_name'], 80);

							clearstatcache();

							// still too big
							if (filesize($_FILES['new_image']['tmp_name']) > 305900)
							{
								return 'toobig';
							}
						}

						// gif so can't reduce it
						else
						{
							return 'toobig';
						}
					}
				}
			}

			$image_info = getimagesize($_FILES['new_image']['tmp_name']);
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

			// the actual image
			$source = $_FILES['new_image']['tmp_name'];

			// where to upload to
			$target = $this->config('path') . "uploads/carousel/" . $imagename;

			if (move_uploaded_file($source, $target))
			{
				// we are editing an existing featured image
				if ($new == 0)
				{
					// see if there is a current top image
					$db->sqlquery("SELECT `featured_image` FROM `editor_picks` WHERE `article_id` = ?", array($article_id));
					$image = $db->fetch();

					// remove old image
					if (!empty($image['featured_image']))
					{
						unlink($this->config('path') . 'uploads/carousel/' . $image['featured_image']);
						$db->sqlquery("UPDATE `editor_picks` SET `featured_image` = ? WHERE `article_id` = ?", array($imagename, $article_id));
					}
				}

				// it's a brand new featured image
				if ($new == 1)
				{
					$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 1 WHERE `article_id` = ?", array($article_id));

					$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_featured'");

					$db->sqlquery("INSERT INTO `editor_picks` SET `article_id` = ?, `featured_image` = ?", array($article_id, $imagename));
				}

				return true;
			}


			else
			{
				return 'cantmove';
			}

			return true;
		}
	}

	// include this anywhere to show the bbcode editor
	function editor($name, $content, $article_editor = 0, $disabled = 0, $anchor_name = 'commentbox', $ays_ignore = 0)
	{
		global $templating;

		$templating->merge('editor');
		$templating->block('editor');
		$templating->set('url', $this->config('website_url'));
		$templating->set('name', $name);
		$templating->set('content', $content);
		$templating->set('anchor_name', $anchor_name);
		if ($disabled == 0)
		{
			$disabled = '';
		}
		else
		{
			$disabled = 'disabled';
		}
		$templating->set('disabled', $disabled);

		$page_button = '';
		$timer_button = '';
		if ($article_editor == 1)
		{
			$page_button = '<li data-snippet="<*PAGE*>">page</li>';
			//$timer_button = '<li data-snippet="[timer=timer1]'.date('Y/m/d H:m:s').'[/timer]">timer</li>';
			$timer_button = '<ul><li class="dropdown">Timer<ul class="timer"><li data-snippet="[timer=timer1*time-only]'.date('Y-m-d H:m:s').'[/timer]">time only</li><li data-snippet="[timer=timer1]'.date('Y-m-d H:m:s').'[/timer]">time and date</li></ul></li></ul>';
		}
		$templating->set('page_button', $page_button);
		$templating->set('timer_button', $timer_button);

		if ($ays_ignore == 0)
		{
			$ays_check = '';
		}
		else if ($ays_ignore == 1)
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

	function random_id($length = 10)
	{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_string = '';
		for ($i = 0; $i < $length; $i++)
		{
    	$random_string .= $characters[rand(0, $characters_length - 1)];
		}
    return $random_string;
	}

	function stat_chart($id, $order = '', $last_id = '')
	{
		global $db;

		require_once(core::config('path') . 'includes/SVGGraph/SVGGraph.php');

		$db->sqlquery("SELECT `name`, `h_label`, `generated_date`, `total_answers` FROM `user_stats_charts` WHERE `id` = ?", array($last_id));
		$chart_info_old = $db->fetch();

		$res_sort = '';
		$order_sql = 'd.`data` ASC';

		// set the right labels to the right data (OLD DATA)
		$labels_old = array();
		$db->sqlquery("SELECT $res_sort l.`label_id`, l.`name`, d.`data` FROM `user_stats_charts_labels` l LEFT JOIN `user_stats_charts_data` d ON d.label_id = l.label_id WHERE l.`chart_id` = ? ORDER BY $order_sql", array($last_id));
		$get_labels_old = $db->fetch_all_rows();

		if ($db->num_rows() > 0)
		{
			$top_10_labels = array_slice($get_labels_old, -10);

			if ($chart_info_old['name'] == 'RAM' || $chart_info_old['name'] == 'Resolution')
			{
				uasort($top_10_labels, function($a, $b) { return strnatcmp($a["name"], $b["name"]); });
			}
			foreach ($top_10_labels as $label_loop_old)
			{
					$label_add = '';
					if ($chart_info_old['name'] == 'RAM')
					{
						$label_add = 'GB';
					}
					$labels_old[]['name'] = $label_loop_old['name'] . $label_add;
					end($labels_old);
					$last_id_old=key($labels_old);
					$labels_old[$last_id_old]['total'] = $label_loop_old['data'];

					$labels_old[$last_id_old]['percent'] = round(($label_loop_old['data'] / $chart_info_old['total_answers']) * 100, 2) . '%';
			}
		}

		$db->sqlquery("SELECT `name`, `h_label`, `generated_date`, `total_answers` FROM `user_stats_charts` WHERE `id` = ?", array($id));
		$chart_info = $db->fetch();

		// set the right labels to the right data (This months data)
		$labels = array();
		$db->sqlquery("SELECT $res_sort l.`label_id`, l.`name`, d.`data` FROM `user_stats_charts_labels` l LEFT JOIN `user_stats_charts_data` d ON d.label_id = l.label_id WHERE l.`chart_id` = ? ORDER BY $order_sql", array($id));
		$get_labels = $db->fetch_all_rows();

		if ($db->num_rows() > 0)
		{
			$top_10_labels = array_slice($get_labels, -10);

			if ($chart_info['name'] == 'RAM' || $chart_info['name'] == 'Resolution')
			{
				uasort($top_10_labels, function($a, $b) { return strnatcmp($a["name"], $b["name"]); });
			}
		  foreach ($top_10_labels as $label_loop)
		  {
					$label_add = '';
					if ($chart_info['name'] == 'RAM')
					{
						$label_add = 'GB';
					}
		      $labels[]['name'] = $label_loop['name'] . $label_add;
					end($labels);
					$last_id=key($labels);
					$labels[$last_id]['total'] = $label_loop['data'];
					if ($label_loop['name'] == 'Intel')
					{
						$labels[$last_id]['colour'] = "#a6cee3";
					}
					if ($label_loop['name'] == 'AMD' || $label_loop['name'] == 'Proprietary')
					{
						$labels[$last_id]['colour'] = "#e31a1c";
					}
					if ($label_loop['name'] == 'Nvidia' || $label_loop['name'] == 'Open Source')
					{
						$labels[$last_id]['colour'] = "#33a02c";
					}
					$labels[$last_id]['percent'] = round(($label_loop['data'] / $chart_info['total_answers']) * 100, 2);
		  }

			// this is for the full info expand box, as charts only show 10 items, this expands to show them all
			$full_info = '<div class="collapse_container"><div class="collapse_header"><span>Click for full statistics</span></div><div class="collapse_content">';

			// sort them from highest to lowest
			usort($get_labels, function($b, $a)
			{
				return $a['data'] - $b['data'];
			});
			foreach ($get_labels as $k => $all_labels)
			{
				$icon = '';
				if ($chart_info['name'] == "Linux Distributions (Split)")
				{
					$icon = '<img src="/templates/default/images/distros/'.$all_labels['name'].'.svg" alt="distro-icon" width="20" height="20" /> ';
				}
				if ($chart_info['name'] == "Linux Distributions (Combined)")
				{
					if ($all_labels['name'] == 'Ubuntu-based')
					{
						$icon_name = 'Ubuntu';
					}
					else if ($all_labels['name'] == 'Arch-based')
					{
						$icon_name = 'Arch';
					}
					else
					{
						$icon_name = $all_labels['name'];
					}
					$icon = '<img src="/templates/default/images/distros/'.$icon_name.'.svg" alt="distro-icon" width="20" height="20" /> ';
				}
				$percent = round(($all_labels['data'] / $chart_info['total_answers']) * 100, 2);

				$old_info = '';
				foreach ($get_labels_old as $all_old)
				{
					if ($all_old['name'] == $all_labels['name'])
					{
						$percent_old = round(($all_old['data'] / $chart_info_old['total_answers']) * 100, 2);
						$difference_percentage = round($percent - $percent_old, 2);

						$difference_people = $all_labels['data'] - $all_old['data'];

						if (strpos($difference_percentage, '-') === FALSE)
						{
							$difference_percentage = '+' . $difference_percentage;
						}

						if ($difference_people > 0)
						{
							$difference_people = '+' . $difference_people;
						}
						$old_info = ' Difference: (' . $difference_percentage . '% overall, ' . $difference_people .' people)';
					}
				}

				$full_info .= $icon . '<strong>' . $all_labels['name'] . $label_add . '</strong>: ' . $all_labels['data'] . ' (' . $percent . '%)' . $old_info . '<br />';
			}
			$full_info .= '</div></div>';

			$settings = array('units_label' => '%', 'grid_division_h' => 10, 'show_tooltips' => true, 'show_data_labels' => true, 'data_label_position' => 'outside right', 'data_label_shadow_opacity' => 0, 'pad_right' => 35, 'data_label_padding' => 2, 'data_label_type' => 'box', 'minimum_grid_spacing_h'=> 20, 'graph_title' => $chart_info['name'], 'auto_fit'=>true, 'svg_class' => 'svggraph', 'minimum_units_y' => 0, 'units_y' => "%", 'show_grid_h' => false, 'label_h' => $chart_info['h_label'], 'minimum_grid_spacing_h' => 20);
			$settings['structured_data'] = true;
			$settings['structure'] = array(
			'key' => 'name',
			'value' => 'percent',
			'colour' => 'colour',
			'tooltip' => 'total'
			);

			$graph = new SVGGraph(400, 300, $settings);

		  $graph->Values($labels);

			$colours = array(
	    '#a6cee3',
	    '#1f78b4',
	    '#b2df8a',
	    '#33a02c',
	    '#fb9a99',
	    '#e31a1c',
	    '#fdbf6f',
	    '#ff7f00',
			'#cab2d6',
			'#6a3d9a'
	 		);
	 		$graph->Colours($colours);

		  $get_graph['graph'] = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';
			$get_graph['full_info'] = $full_info;
			$get_graph['date'] = $chart_info['generated_date'];

			$total_difference = '';
			if (isset($chart_info_old['total_answers']))
			{
				$total_difference = $chart_info['total_answers'] - $chart_info_old['total_answers'];
				if ($total_difference > 0)
				{
					$total_difference = '+' . $total_difference;
				}
				$total_difference = ' (' . $total_difference . ')';
			}

			$get_graph['total_users_answered'] = $chart_info['total_answers'] . $total_difference;

			core::$user_graphs_js = $graph->FetchJavascript();

			return $get_graph;
		}
		else
		{
			$get_graph['graph'] = "Graph not generated yet, please stand by!";
			return $get_graph;
		}
	}

	function process_livestream_users($livestream_id)
	{
		global $db;

		if (isset($livestream_id) && is_numeric($livestream_id))
		{
			// delete any existing categories that aren't in the final list for publishing
			$db->sqlquery("SELECT `id`, `livestream_id`, `user_id` FROM `livestream_presenters` WHERE `livestream_id` = ?", array($livestream_id));
			$current_users = $db->fetch_all_rows();

			if (!empty($current_users))
			{
				foreach ($current_users as $current_user)
				{
					if (!in_array($current_user['user_id'], $_POST['user_ids']))
					{
						$db->sqlquery("DELETE FROM `livestream_presenters` WHERE `id` = ?", array($current_user['id']));
					}
				}
			}

			// get fresh list of categories, and insert any that don't exist
			$db->sqlquery("SELECT `user_id` FROM `livestream_presenters` WHERE `livestream_id` = ?", array($livestream_id));
			$current_streamers = $db->fetch_all_rows(PDO::FETCH_COLUMN, 0);

			if (isset($_POST['user_ids']) && !empty($_POST['user_ids']))
			{
				foreach($_POST['user_ids'] as $streamer_id)
				{
					if (!in_array($streamer_id, $current_streamers))
					{
						$db->sqlquery("INSERT INTO `livestream_presenters` SET `livestream_id` = ?, `user_id` = ?", array($livestream_id, $streamer_id));
					}
				}
			}
		}
	}

	function check_old_pc_info($user_id)
	{
		global $db, $templating;

		if (isset($user_id) && $user_id != 0)
		{
			$db->sqlquery("SELECT `date_updated` FROM `user_profile_info` WHERE `user_id` = ?", array($user_id));
			$checker = $db->fetch();

			if ($checker['date_updated'] != NULL)
			{
				$minus_4months = strtotime('-4 months');

				if (strtotime($checker['date_updated']) < $minus_4months)
				{
					$templating->merge('announcements');
					$templating->block('announcement_top', 'announcements');
					$templating->block('announcement', 'announcements');
					$templating->set('text', 'You haven\'t updated your PC information in over 4 months! <a href="/usercp.php?module=pcinfo">Click here to go and check</a>. You can simply update if nothing has changed to be included in our statistics!');
					$templating->block('announcement_bottom', 'announcements');
				}
			}
		}
	}
}
?>
