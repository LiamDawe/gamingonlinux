<?php
session_start();

error_reporting(-1);

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

use claviska\SimpleImage;
$img = new SimpleImage();

// only logged in accounts can do this too, it's only for articles
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	die('You shouldn\'t be here.');
}

if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
{
	if (isset($_FILES['photos2']) && $_FILES['photos2']['error'] == 0)
	{
		if (!@fopen($_FILES['photos2']['tmp_name'], 'r'))
		{
			echo '<span class="imgList">Did you select an image? Couldn\'t find one.</span>';
		}

		else
		{
			// make sure it's actually an image, for sure
			if (!getimagesize($_FILES['photos2']['tmp_name']))
			{
				echo '<span class="imgList">That was not an image!</span>';
				return;
			}
			
			// load the file
			$img->fromFile($_FILES['photos2']['tmp_name']);
			
			/* SCALING UP TO 550
			// has to be at least 550 to work on social media websites for the image to be auto-included in posts like on G+ and Facebook
			// Do this by itself first, so that we can try to preserve aspect ratio!
			*/
			if ($img->getWidth() != 740 || $img->getHeight() != 420)
			{
				if ( $img->getMimeType() == 'image/jpeg' || $img->getMimeType() == 'image/png' )
				{
					$img->resize(740, 420)->toFile($_FILES['photos2']['tmp_name']);
				}

				// don't mess with the gif man
				else if ( $img->getMimeType() == 'image/gif' )
				{
					echo '<span class="imgList">That gif is too small</span>';
					return;
				}

				clearstatcache();
			}

			/* CHECK FILE SIZE
			So we now know for sure it has the correct minimum dimensions, but is the filesize okay?
			*/
			if (filesize($_FILES['photos2']['tmp_name']) > $core->config('max_tagline_image_filesize'))
			{
				// okay, so width is good, let's try compressing directly instead then
				if ($img->getWidth() == 740)
				{
					if( $img->getMimeType() == 'image/jpeg' || $img->getMimeType() == 'image/png' )
					{
						$img->toFile($_FILES['photos2']['tmp_name'], NULL, 80);
					}

					// cannot compress gifs so it's just too big
					else if( $img->getMimeType() == 'image/gif' )
					{
						echo '<span class="imgList">File size too big!</span>';
						return;
					}
					
					clearstatcache();

					// if it's still too big, we don't want to compress any further or it will look bad
					if (filesize($_FILES['photos2']['tmp_name']) > $core->config('max_tagline_image_filesize'))
					{
						echo '<span class="imgList">File size too big, tried compressing it, but still too big!</span>';
						return;
					}
				}
			}
		}

		$file_ext = '';
		if( $img->getMimeType() == 'image/jpeg' )
		{
			$file_ext = 'jpg';
		}

		else if( $img->getMimeType() == 'image/gif' )
		{
			$file_ext = 'gif';
		}

		else if( $img->getMimeType() == 'image/png' )
		{
			$file_ext = 'png';
		}

		// give the image a random file name
		$imagename = rand() . 'idgol.' . $file_ext;

		// the actual image
		$source = $_FILES['photos2']['tmp_name'];

		// where to upload to
		$target = $core->config('path') . "uploads/articles/tagline_images/temp/" . $imagename;

		// make the thumbnail, nice and small
		$img->fromFile($_FILES['photos2']['tmp_name'])->resize(350, null)->toFile($core->config('path') . "uploads/articles/tagline_images/temp/thumbnails/" . $imagename);
		
		if (move_uploaded_file($source, $target))
		{
			// replace any existing just-uploaded image
			if (isset($_SESSION['uploads_tagline']))
			{
				unlink($core->config('path') . "uploads/articles/tagline_images/temp/" . $_SESSION['uploads_tagline']['image_name']);
				unlink($core->config('path') . "uploads/articles/tagline_images/temp/thumbnails/" . $_SESSION['uploads_tagline']['image_name']);
			}

			$_SESSION['uploads_tagline']['image_name'] = $imagename;
			$_SESSION['uploads_tagline']['image_rand'] = $_SESSION['image_rand'];

			// this will replace any previously selected gallery image
			unset($_SESSION['gallery_tagline_id']);
			unset($_SESSION['gallery_tagline_rand']);
			unset($_SESSION['gallery_tagline_filename']);

			echo "<div class=\"test\" id=\"{$imagename}\"><img src=\"".$core->config('website_url')."uploads/articles/tagline_images/temp/thumbnails/{$imagename}\"><br />";
			echo "<input type=\"hidden\" name=\"image_name\" value=\"{$imagename}\" />";
			echo 'Full Image Url: <a class="tagline-image" href="' . $core->config('website_url') . 'uploads/articles/tagline_images/temp/'.$imagename.'" target="_blank">Click Me</a> - <a href="#" id="'.$imagename.'" class="trash_tagline">Delete Image</a>';
			echo '<br /><button type="button" class="insert_tagline_image">Insert into editor</button></div>';
		}

		else
		{
			echo '<span class="imgList">There was an error while trying to upload!</span>';
		}
	}
}
?>
