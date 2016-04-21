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

	protected static $config = array();

	function __construct()
	{
		core::$date = strtotime(gmdate("d-n-Y H:i:s"));
		core::$ip = $this->get_client_ip();

		// stop magic quotes! they add extra slashes
		if (get_magic_quotes_gpc())
		{
			$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
			while (list($key, $val) = each($process))
			{
				foreach ($val as $k => $v)
				{
					unset($process[$key][$k]);
					if (is_array($v))
					{
						$process[$key][stripslashes($k)] = $v;
						$process[] = &$process[$key][stripslashes($k)];
					}

					else
					{
						$process[$key][stripslashes($k)] = stripslashes($v);
					}
				}
			}
			unset($process);
		}
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
		} else {
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
				$pagination.= "<li class=\"disabled previouspage\"><a href=\"#\">&laquo;</a></li>";
			}

			$pagination .= "<li class=\"disabled\"><a href=\"#\">$page</a></li>";

			$pagination .= "<li class=\"disabled\"><a href=\"#\">/</a></li>";

			// sort out last page link, no link if on last page
			if ($page == $lastpage)
			{
				$pagination .= "<li class=\"disabled\"><a href=\"#\">{$lastpage}</a></li>";
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
				$pagination .= "<li class=\"disabled nextpage\"><a href=\"#\">&raquo;</a></li>";
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
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", '-', $clean);

		return $clean;
	}

	// move previously uploaded tagline image to correct directory
	function move_temp_image($article_id, $file)
	{
		global $db, $config;

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

	// this should probably be removed, we never use it, we dont even show sales images anymore
	function sale_image($sale_id)
	{
		global $db, $config;

		if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0)
		{
			if (!@fopen($_FILES['new_image']['tmp_name'], 'r'))
			{
				$this->error_message = "Could not find image, did you select one to upload?";
				return false;
			}

			else
			{
				// check the dimensions
				list($width, $height, $type, $attr) = getimagesize($_FILES['new_image']['tmp_name']);

				if ($width > 110 || $height > 110)
				{
					// include the image class to resize it as its too big
					include('includes/class_image.php');
					$image = new SimpleImage();
					$image->load($_FILES['new_image']['tmp_name']);
					$image->resize(110,110);
					$image->save($_FILES['new_image']['tmp_name']);

					// just double check it's now the right size (just a failsafe)
					list($width, $height, $type, $attr) = getimagesize($_FILES['new_image']['tmp_name']);
					if ($width > 110 || $height > 110)
					{
						$this->error_message = 'Too big!';
						return false;
					}
				}

				// check if its too big
				if ($_FILES['new_image']['size'] > 20000)
				{
					$image_info = getimagesize($_FILES['new_image']['tmp_name']);
					$image_type = $image_info[2];
					if( $image_type == IMAGETYPE_JPEG )
					{
						$oldImage = imagecreatefromjpeg($_FILES['new_image']['tmp_name']);
					}
					else if( $image_type == IMAGETYPE_GIF )
					{
						$oldImage = imagecreatefromgif($_FILES['new_image']['tmp_name']);
					}

					else if( $image_type == IMAGETYPE_PNG )
					{
						$oldImage = imagecreatefrompng($_FILES['new_image']['tmp_name']);
					}

					imagejpeg($oldImage, $_FILES['new_image']['tmp_name'], 85);

					clearstatcache();

					// check again
					if (filesize($_FILES['new_image']['tmp_name']) > 35900)
					{
						$this->error_message = 'File size too big! The max is 35kb, try to use some more compression on it, or find another image.';
						return false;
					}
				}

				// this will make sure it is an image file, if it cant get an image size then its not an image
				if (!getimagesize($_FILES['new_image']['tmp_name']))
				{
					$this->error_message = 'Not an image!';
					return false;
				}
			}

			// see if they currently have an avatar set
			$db->sqlquery("SELECT `has_screenshot`, `screenshot_filename`  FROM `game_sales` WHERE `id` = ?", array($sale_id));
			$image = $db->fetch();

			// give the image a random file name
			$imagename = rand() . 'id' . $sale_id . 'gol.jpg';

			// the actual image
			$source = $_FILES['new_image']['tmp_name'];

			// where to upload to
			$target = $_SERVER['DOCUMENT_ROOT'] . url . "uploads/sales/" . $imagename;

			if (move_uploaded_file($source, $target))
			{
				// remove old avatar
				if ($image['has_screenshot'] == 1)
				{
					unlink($_SERVER['DOCUMENT_ROOT'] . url . 'uploads/sales/' . $image['screenshot_filename']);
				}

				$db->sqlquery("UPDATE `game_sales` SET `has_screenshot` = 1, `screenshot_filename` = ? WHERE `id` = ?", array($imagename, $sale_id));
				return true;
			}

			else
			{
				$this->error_message = 'Could not upload file!';
				return false;
			}

			return true;
		}
	}

	function carousel_image($article_id)
	{
		global $db, $config;

		if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0)
		{
			if (!@fopen($_FILES['new_image']['tmp_name'], 'r'))
			{
				$this->error_message = "Could not find image, did you select one to upload?";
				return false;
			}

			else
			{
				// check the dimensions
				$image_info = getimagesize($_FILES['new_image']['tmp_name']);
				$image_type = $image_info[2];

				list($width, $height, $type, $attr) = $image_info;

				if ($width < $config['carousel_image_width'] || $height < $config['carousel_image_height'])
				{
					// include the image class to resize it as its too big
					include('includes/class_image.php');
					$image = new SimpleImage();
					$image->load($_FILES['new_image']['tmp_name']);
					$image->resize($config['carousel_image_width'],$config['carousel_image_height']);
					$image->save($_FILES['new_image']['tmp_name'], $image_type);

					// just double check it's now the right size (just a failsafe, should never happen but no harm in double checking resizing worked)
					list($width, $height, $type, $attr) = getimagesize($_FILES['new_image']['tmp_name']);
					if ($width != $config['carousel_image_width'] || $height != $config['carousel_image_height'])
					{
						$this->error_message = 'It was not the correct size!';
						return false;
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
						$this->error_message = 'File size too big! The max is 300kb, try to use some more compression on it, or find another image.';
						return false;
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
								$this->error_message = 'File size too big! The max is 300kb, try to use some more compression on it, or find another image. The image you used is ' . filesize($_FILES['new_image']['tmp_name']);
								return false;
							}
						}

						// gif so can't reduce it
						else
						{
							$this->error_message = 'File size too big! The max is 300kb, try to use some more compression on it, or find another image. The image you used is ' . filesize($_FILES['new_image']['tmp_name']);
							return false;
						}
					}
				}

				// this will make sure it is an image file, if it cant get an image size then its not an image
				if (!getimagesize($_FILES['new_image']['tmp_name']))
				{
					$this->error_message = 'Not an image!';
					return false;
				}
			}

			// see if there is a current top image
			$db->sqlquery("SELECT `featured_image` FROM `articles` WHERE `article_id` = ?", array($article_id));
			$image = $db->fetch();

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
				// remove old avatar
				if (!empty($image['featured_image']))
				{
					unlink($this->config('path') . 'uploads/carousel/' . $image['image']);
				}

				$db->sqlquery("UPDATE `articles` SET `featured_image` = ? WHERE `article_id` = ?", array($imagename, $article_id));
				return true;
			}

			else
			{
				$this->error_message = 'Could not upload file!';
				return false;
			}

			return true;
		}
	}

	function getRemaining($now,$future)
	{
		global $config;

		if($future <= $now)
		{
			// Time has already elapsed
			return FALSE;
		}
		else
		{
			if ($config['summer_time'] == 1)
			{
				$offset=3600;

				$future = $future - $offset;
			}

			// Get difference between times
			$time = $future - $now;
			$minutesFloat = $time/60;
			$minutes = floor($minutesFloat);
			$hoursFloat = $minutes/60;
			$hours = floor($hoursFloat);
			$daysFloat = $hours/24;
			$days = floor($daysFloat);
			$weeks = floor($days/7);
			$months = floor($weeks/4);

			$time_left = array(
			'days' => $days,
			'hours' => round(($daysFloat-$days)*24),
			'minutes' => round(($hoursFloat-$hours)*60),
			'seconds' => round(($minutesFloat-$minutes)*60)
			);

			if ($weeks > 0)
			{
				if ($weeks > 12)
				{
					return '1+ Years';
				}

				if ($weeks == 12)
				{
					return '1 Year';
				}

				if ($weeks >= 8)
				{
					return $months . ' Months';
				}

				if ($weeks > 4 && $weeks < 8)
				{
					return $months . ' Month';
				}

				// one month, no s!
				if ($weeks == 4)
				{
					return $months . ' Month';
				}
			}

			// 2 or more weeks, needs weeks
			if ($days >= 14)
			{
				return $weeks . ' Weeks';
			}

			if ($days > 7 && $days < 14)
			{
				return "1 Week";
			}

			// if its 1 week, no s ;)
			if ($days == 7)
			{
				return "1 Week";
			}

			// more than one day but not a week
			if ($days > 0 && $days != 1 && $days < 7)
			{
				return "$days Days";
			}

			// more than one day but under 2
			if ($days == 1 && $time_left['hours'] <= 23)
			{
				return $days . ' Day' . '<br />' . $time_left['hours'] . ' Hours';
			}

			// 1 day
			if ($days == 1 && $time_left['hours'] == 0)
			{
				return $days . ' Day';
			}

			// under a day left
			if ($days == 0 && $time_left['hours'] <= 23 && $time_left['hours'] != 1)
			{
				return $time_left['hours'] . ' Hours<br />' . $time_left['minutes'] . ' Minutes';
			}

			if ($days == 0 && $time_left['hours'] == 1)
			{
				return $time_left['hours'] . ' Hour<br />' . $time_left['minutes'] . ' Minutes';
			}

			if ($days == 0 && $time_left['hours'] < 1 && $time_left['minutes'] > 1)
			{
				return $time_left['minutes'] . ' Minutes';
			}

			if ($days == 0 && $time_left['hours'] < 1 && $time_left['minutes'] <= 1)
			{
				return $time_left['minutes'] . ' Minute';
			}

			if ($weeks == 0 && $days == 0 && $time_left['minutes'] == 0)
			{
				return 'Expired';
			}
		}
	}

	// include this anywhere to show the bbcode editor
	function editor($name, $content, $article_editor = 0, $disabled = 0, $anchor_name = 'commentbox', $ays_ignore = 0)
	{
		global $templating, $config;

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
		if ($article_editor == 1)
		{
			$page_button = '<li data-snippet="<*PAGE*>">page</li>';
		}
		$templating->set('page_button', $page_button);

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
}
?>
