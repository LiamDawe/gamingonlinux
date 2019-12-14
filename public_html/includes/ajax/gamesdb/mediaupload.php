<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

include_once(APP_ROOT . '/includes/image_class/SimpleImage.php');
use claviska\SimpleImage;
$img = new SimpleImage();

// some basic security, make sure the referring url is actually us - can be spoofed, but still a good idea
if (!isset($_SERVER['HTTP_REFERER']))
{
	die("You should not be here!");
}

// only logged in accounts can do this too, it's only for articles
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	die('You shouldn\'t be here.');
}

$parse_url = parse_url($_SERVER['HTTP_REFERER']);
if ($parse_url['scheme'].'://'.$parse_url['host'].'/' == $core->config('website_url'))
{
	header('Content-Type: application/json');

	define ("MAX_SIZE",2*1024*1024); // 2MB
	function getExtension($str)
	{
		$i = strrpos($str,".");
		if (!$i) { return ""; }
		$l = strlen($str) - $i;
		$ext = substr($str,$i+1,$l);
		return $ext;
	}

	$item_id = 0;
	$item_dir = 'tmp/';
	$approved = 0;
	if (isset($_POST['item_id']) && is_numeric($_POST['item_id']) && $_POST['item_id'] > 0)
	{
		$approved = 1;
		$item_id = $_POST['item_id'];
		$item_dir = $_POST['item_id'] . '/';
	}

	$uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/gamesdb/big/" . $item_dir;
	$thumbs_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/gamesdb/big/thumbs/" . $item_dir;

	if (!is_dir($uploaddir))
	{
		mkdir($uploaddir, 0777);
		chmod($uploaddir, 0777);
	}

	if (!is_dir($thumbs_dir))
	{
		mkdir($thumbs_dir, 0777);
		chmod($thumbs_dir, 0777);
	}

	$return_data = [];
	$valid_formats = array("jpg", "png", "jpeg");
	if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
	{
		foreach ($_FILES['media']['name'] as $name => $value)
		{
			$filename = stripslashes($_FILES['media']['name'][$name]);
			$size=filesize($_FILES['media']['tmp_name'][$name]);
			//get the extension of the file in a lower case format
			$ext = getExtension($filename);
			$ext = strtolower($ext);

			if(in_array($ext,$valid_formats))
			{
				if ($size < (MAX_SIZE))
				{
					$new_media_name = rand().time().'gol'.$_SESSION['user_id'];
					$image_name = $new_media_name.'.'.$ext;
					$main_newname = $uploaddir.$image_name;

					$main_url = $core->config('website_url') . 'uploads/gamesdb/big/' . $item_dir . $image_name;

					$uploaded = 0;

					if (isset($_GET['type']) && $_GET['type'] == 'bigmedia')
					{
						$thumb_url = $core->config('website_url') . 'uploads/gamesdb/big/thumbs/' . $item_dir . $image_name;

						// thumbs
						$img->fromFile($_FILES['media']['tmp_name'][$name]);

						$thumb_newname = $thumbs_dir.$image_name;

						// so we don't make a big thumbnail of a small image
						if ($img->getWidth() <= 450)
						{
							$img->fromFile($_FILES['media']['tmp_name'][$name])->toFile($thumb_newname);
						}
						else
						{
							$img->fromFile($_FILES['media']['tmp_name'][$name])->resize(450, null)->toFile($thumb_newname);
						}

						$preview_file = '<a data-fancybox="images" href="'.$main_url.'" target="_blank"><img src="' . $thumb_url . '" class="imgList"></a><br />';

						if (move_uploaded_file($_FILES['media']['tmp_name'][$name], $main_newname))
						{
							$uploaded = 1;
						}
					}
					else if (isset($_GET['type']) && $_GET['type'] == 'featured')
					{
						$img->fromFile($_FILES['media']['tmp_name'][$name]);

						if ($img->getWidth() != 460 || $img->getHeight() != 215)
						{
							if($img->fromFile($_FILES['media']['tmp_name'][$name])->resize(460, 215)->toFile($main_newname))
							{
								$uploaded = 1;
							}
						}
						else
						{
							if (move_uploaded_file($_FILES['media']['tmp_name'][$name], $main_newname))
							{
								$uploaded = 1;
							}
						}

						$preview_file = '<img src="' . $main_url . '" class="imgList"><br />';
					}

					$data_type = 'image';

					if ($uploaded == 1)
					{
						$featured = 0;
						if (isset($_GET['type']) && $_GET['type'] == 'featured')
						{
							$approved = 0; // set to zero for featured, to help with regular cleanup in housekeeping cron
							$featured = 1;
						}

						$new_image = $dbl->run("INSERT INTO `itemdb_images` SET `filename` = ?, `uploader_id` = ?, `date_uploaded` = ?, `item_id` = ?, `filetype` = ?, `featured` = ?, `approved` = ?", [$image_name, $_SESSION['user_id'], core::$sql_date_now, $item_id, $ext, $featured, $approved]);
						$media_db_id = $new_image->new_id();

						$html_output = '<div class="box">
						<div class="body group">
						<div id="'.$media_db_id.'">'.$preview_file.'
						<button id="' . $media_db_id . '" class="trash" data-type="itemdb">Delete Media</button>
						</div>
						</div>
						</div>';

						$return_data[] = array('output' => $html_output, "media_id" => $media_db_id);
					}

					else
					{
						echo '<span class="imgList">Couldn\'t upload image!</span>';
					}

				}
				else
				{
					echo '<span class="imgList">You have exceeded the size limit!</span>';
				}

			}
			else
			{
				echo '<span class="imgList">Unknown extension!</span>';
			}

		}
		echo json_encode(array("data" => $return_data));
		return;
	}
}
?>
