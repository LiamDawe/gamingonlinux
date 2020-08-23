<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

require APP_ROOT . '/includes/aws/aws-autoloader.php';

$key = $core->config('do_space_key_uploads');
$secret = $core->config('do_space_key_private_uploads');

use Aws\S3\S3Client;

$client = new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => 'am3',
        'endpoint' => 'https://ams3.digitaloceanspaces.com',
        'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
]);

include_once(APP_ROOT . '/includes/image_class/SimpleImage.php');
use claviska\SimpleImage;
$img = new SimpleImage();

// only logged in accounts can do this
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	die('You shouldn\'t be here.');
}

header('Content-Type: application/json');

define ("MAX_SIZE",2*1024*1024); // 2MB

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
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$mime = mime_content_type($_FILES['media']['tmp_name'][$name]);
		
		if(in_array($ext,$valid_formats))
		{
			if ($size < (MAX_SIZE))
			{
				$new_media_name = rand().time().'gol'.$_SESSION['user_id'];
				if (isset($_GET['type']) && $_GET['type'] == 'featured')
				{
					$new_media_name .= '_featured';
				}
				$image_name = $new_media_name.'.'.$ext;
				$main_newname = $uploaddir.$image_name;

				$uploaded = 0;

				if (isset($_POST['local_upload']))
				{
					$main_url = $core->config('website_url') . 'uploads/gamesdb/big/' . $item_dir . $image_name;
					$location = NULL;

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
					}
				}
				else
				{
					$main_url = $core->config('external_media_upload_url') . 'uploads/gamesdb/big/' . $item_dir . $image_name;
					$location = $core->config('external_media_upload_url');

					if (isset($_GET['type']) && $_GET['type'] == 'featured')
					{
						$img->fromFile($_FILES['media']['tmp_name'][$name]);

						if ($img->getWidth() != 460 || $img->getHeight() != 215)
						{
							$img->fromFile($_FILES['media']['tmp_name'][$name])->resize(460, 215)->toFile($_FILES['media']['tmp_name'][$name]);
						}

						$to_upload = fopen($_FILES['media']['tmp_name'][$name],'rb');

						$upload_details = [
							'Bucket' => 'goluploads',
							'Key'    => 'uploads/gamesdb/big/' . $item_dir . $image_name,
							'Body'   => $to_upload,
							'ACL'    => 'public-read',
							'ContentType' => $mime
						];
						try {
							$result = $client->putObject($upload_details);
							$uploaded = 1;
						} 
						catch (S3Exception $e) 
						{
							echo 'there has been an exception<br>';
							print_r($e);
							error_log($e);
						}
					}
				}

				if ($uploaded == 1)
				{
					$preview_file = '<img src="' . $main_url . '" class="imgList"><br />';

					$featured = 0;
					if (isset($_GET['type']) && $_GET['type'] == 'featured')
					{
						$approved = 0; // set to zero for featured, to help with regular cleanup in housekeeping cron
						$featured = 1;
					}

					$new_image = $dbl->run("INSERT INTO `itemdb_images` SET `filename` = ?, `location` = ?, `uploader_id` = ?, `date_uploaded` = ?, `item_id` = ?, `filetype` = ?, `featured` = ?, `approved` = ?", [$image_name, $location, $_SESSION['user_id'], core::$sql_date_now, $item_id, $ext, $featured, $approved]);
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
?>
