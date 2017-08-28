<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user->check_session();

include_once(APP_ROOT . '/includes/image_class/SimpleImage.php');
use claviska\SimpleImage;
$img = new SimpleImage();

if ($user->check_group([1,2,5]) == false)
{
	die('You should not be here.');
}

define ("MAX_SIZE",9*1024*1024); // 9MB
function getExtension($str)
{
         $i = strrpos($str,".");
         if (!$i) { return ""; }
         $l = strlen($str) - $i;
         $ext = substr($str,$i+1,$l);
         return $ext;
}

$valid_formats = array("jpg", "png", "gif", "jpeg", "svg");
if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
{
	$uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/articles/article_images/";
	$thumbs_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/articles/article_images/thumbs/";
	foreach ($_FILES['photos']['name'] as $name => $value)
	{
		$filename = stripslashes($_FILES['photos']['name'][$name]);
		$size=filesize($_FILES['photos']['tmp_name'][$name]);
		//get the extension of the file in a lower case format
		$ext = getExtension($filename);
		$ext = strtolower($ext);

		if(in_array($ext,$valid_formats))
		{
			if ($size < (MAX_SIZE))
			{
				// main image
				$image_name = rand().time().'gol'.$_SESSION['user_id'].'.'.$ext;
				$main_newname = $uploaddir.$image_name; //Check / delete file it exists

				// thumbs
				$thumb_newname = $thumbs_dir.$image_name;
				$img->fromFile($_FILES['photos']['tmp_name'][$name])->resize(350, null)->toFile($thumb_newname);
				
				if (move_uploaded_file($_FILES['photos']['tmp_name'][$name], $main_newname))
				{
					$article_id = 0;
					if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
					{
						$article_id = $_POST['article_id'];
					}
					$new_image = $dbl->run("INSERT INTO `article_images` SET `filename` = ?, `uploader_id` = ?, `date_uploaded` = ?, `article_id` = ?", [$image_name, $_SESSION['user_id'], core::$date, $article_id]);
					$image_id = $new_image->new_id();

					// if they aren't adding the image to an existing article, store it in the session
					if (!isset($_POST['article_id']) || $_POST['article_id'] == 0)
					{
						$_SESSION['uploads'][$image_id]['image_name'] = $image_name;
						$_SESSION['uploads'][$image_id]['image_id'] = $image_id;
						$_SESSION['uploads'][$image_id]['image_rand'] = $_SESSION['image_rand'];
					}

					$main_url = $core->config('website_url') . 'uploads/articles/article_images/' . $image_name;
					$thumb_url = $core->config('website_url') . 'uploads/articles/article_images/thumbs/' . $image_name;

					echo '<div class="box">
					<div class="body group">
					<div id="'.$image_id.'"><img src="' . $thumb_url . '" class="imgList"><br />
					URL: <input id="img' . $image_id . '" type="text" value="' . $main_url . '" /> <button class="btn" data-clipboard-target="#img' . $image_id . '">Copy</button> <button data-url="'.$main_url.'" class="add_button">Insert</button> <button data-url="'.$thumb_url.'" class="add_thumbnail_button">Insert thumbnail</button> <button id="' . $image_id . '" class="trash">Delete image</button>
					</div>
					</div>
					</div>';
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
}

?>
