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

// only logged in accounts can do this too, it's only for articles
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	die('You shouldn\'t be here.');
}

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest' )
{
	die('You shouldn\'t be here. Not an AJAX request.');
}

header('Content-Type: application/json');

define ("MAX_SIZE",50*1024*1024); // 50MB
function getExtension($str)
{
	$i = strrpos($str,".");
	if (!$i) { return ""; }
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return $ext;
}

$return_data = [];
$valid_formats = array("jpg", "png", "gif", "jpeg", "svg", 'mp4', 'webm', 'ogg', 'mp3', 'webp');
if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
{
	$uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/articles/article_media/";
	$thumbs_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/articles/article_media/thumbs/";
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

				$mime = mime_content_type($_FILES['media']['tmp_name'][$name]);

				if (isset($_POST['local_upload']))
				{
					$main_url = $core->config('website_url') . 'uploads/articles/article_media/' . $image_name;
					$location = NULL;
				}
				else
				{
					$main_url = $core->config('external_media_upload_url') . 'uploads/articles/article_media/' . $image_name;
					$location = $core->config('external_media_upload_url');
				}
				$gif_static_button = '';
				$thumbnail_button = '';
				$data_type = '';

				// only for images
				if ($ext != 'mp4' && $ext != 'webm' && $ext != 'ogg' && $ext != 'mp3')
				{
					// thumbs
					$img->fromFile($_FILES['media']['tmp_name'][$name]);

					$thumb_newname = $thumbs_dir.$image_name;

					/* LOCAL FILESYSTEM UPLOADS */
					if (isset($_POST['local_upload']))
					{
						$thumb_url = $core->config('website_url') . 'uploads/articles/article_media/thumbs/' . $image_name;

						// so we don't make a big thumbnail of a small image
						if ($img->getWidth() <= 450)
						{
							$img->fromFile($_FILES['media']['tmp_name'][$name])->toFile($thumb_newname);
						}
						else
						{
							$img->fromFile($_FILES['media']['tmp_name'][$name])->resize(450, null)->toFile($thumb_newname);
						}

						// if it's a gif, we need a static version to switch to a gif
						if ($ext == 'gif')
						{
							$static_pic = $uploaddir.$new_media_name.'_static.jpg';
							$img->fromFile($_FILES['media']['tmp_name'][$name])->overlay($_SERVER['DOCUMENT_ROOT'].'/templates/default/images/playbutton.png')->toFile($static_pic, 'image/jpeg');

							$static_url = $core->config('website_url') . 'uploads/articles/article_media/'.$new_media_name.'_static.jpg';
							$gif_static_button = '<button data-url-gif="'.$main_url.'" data-url-static="'.$static_url.'" class="add_static_button">Insert Static</button>';
						}
					}
					/* EXTERNAL FILE UPLOADS 
					This is for uploading to DO Spaces, AWS etc
					*/
					else
					{
						$thumb_url = $core->config('external_media_upload_url') . 'uploads/articles/article_media/thumbs/' . $image_name;

						// so we don't make a big thumbnail of a small image
						if ($img->getWidth() <= 450)
						{
							$thumb_file = $img->fromFile($_FILES['media']['tmp_name'][$name])->toString();
						}
						else
						{
							$thumb_file = $img->fromFile($_FILES['media']['tmp_name'][$name])->resize(450, null)->toString();
						}

						// if it's a gif, we need a static version to switch to a gif
						if ($ext == 'gif')
						{
							$static_pic = $new_media_name.'_static.jpg';
							$static_file = $img->fromFile($_FILES['media']['tmp_name'][$name])->overlay($_SERVER['DOCUMENT_ROOT'].'/templates/default/images/playbutton.png')->toString('image/jpeg');

							$static_url = $core->config('external_media_upload_url') . 'uploads/articles/article_media/'.$new_media_name.'_static.jpg';
							$gif_static_button = '<button data-url-gif="'.$main_url.'" data-url-static="'.$static_url.'" class="add_static_button">Insert Static</button>';

							$upload_details = [
								'Bucket' => 'goluploads',
								'Key'    => 'uploads/articles/article_media/' . $static_pic,
								'Body'   => $static_file,
								'ACL'    => 'public-read',
								'ContentType' => $mime
							];
							try {
								$result = $client->putObject($upload_details);
							} 
							catch (S3Exception $e) 
							{
								echo 'there has been an exception<br>';
								print_r($e);
							}
						}		

						$upload_details = [
							'Bucket' => 'goluploads',
							'Key'    => 'uploads/articles/article_media/thumbs/' . $image_name,
							'Body'   => $thumb_file,
							'ACL'    => 'public-read',
							'ContentType' => $mime
						];
						try {
							$result = $client->putObject($upload_details);
						} 
						catch (S3Exception $e) 
						{
							echo 'there has been an exception<br>';
							print_r($e);
						}
					}

					$thumbnail_button = '<button data-url="'.$thumb_url.'" data-main-url="'.$main_url.'" class="add_thumbnail_button">Insert thumbnail</button>';

					$preview_file = '<img src="' . $thumb_url . '" class="imgList"><br />';
					$data_type = 'image';
				}
				else if ($ext == 'mp4' || $ext == 'webm')
				{
					$preview_file = '<video width="100%" src="'.$main_url.'" controls></video>';
					$data_type = 'video';
				}
				else if ($ext == 'mp3' || $ext == 'ogg')
				{
					$preview_file = '<div class="ckeditor-html5-audio" style="text-align: center;"><audio controls="controls" src="'.$main_url.'">&nbsp;</audio></div>';
					$data_type = 'audio';
				}

				// upload the main file

				if (isset($_POST['local_upload']))
				{
					if (move_uploaded_file($_FILES['media']['tmp_name'][$name], $main_newname))
					{
						$finished = 1;
					}
				}
				else
				{
					$to_upload = fopen($_FILES['media']['tmp_name'][$name],'rb');

					$upload_details = [
						'Bucket' => 'goluploads',
						'Key'    => 'uploads/articles/article_media/' . $image_name,
						'Body'   => $to_upload,
						'ACL'    => 'public-read',
						'ContentType' => $mime
					];
					try {
						$result = $client->putObject($upload_details);
						$finished = 1;
					} 
					catch (S3Exception $e) 
					{
						echo 'there has been an exception<br>';
						print_r($e);
					}
				}
				
					
				if ($finished == 1)
				{
					$article_id = 0;
					if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
					{
						$article_id = $_POST['article_id'];
					}

					$new_image = $dbl->run("INSERT INTO `article_images` SET `filename` = ?, `location` = ?, `uploader_id` = ?, `date_uploaded` = ?, `article_id` = ?, `filetype` = ?", [$image_name, $location, $_SESSION['user_id'], core::$date, $article_id, $ext]);
					$media_db_id = $new_image->new_id();

					$html_output = '<div class="box">
					<div class="body group">
					<div id="'.$media_db_id.'">'.$preview_file.'
					URL: <input id="img' . $media_db_id . '" type="text" value="' . $main_url . '" /> <button class="btn" data-clipboard-target="#img' . $media_db_id . '">Copy</button> '.$gif_static_button.' <button data-url="'.$main_url.'" data-type="'.$data_type.'" class="add_button">Insert</button> '.$thumbnail_button.' <button id="' . $media_db_id . '" class="trash" data-type="article">Delete Media</button>
					</div>
					</div>
					</div>';

					$return_data[] = array('output' => $html_output, "media_id" => $media_db_id);
				}

				else
				{
					echo '<span class="imgList">You have exceeded the size limit! so moving unsuccessful! </span>';
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
