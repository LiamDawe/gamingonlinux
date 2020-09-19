<?php
session_start();

error_reporting(-1);

define("APP_ROOT", dirname( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

use claviska\SimpleImage;
$img = new SimpleImage();

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
			if ($img->getWidth() < 120 || $img->getWidth() > 120)
			{
				if ( $img->getMimeType() == 'image/jpeg' || $img->getMimeType() == 'image/png' )
				{
					$img->resize(120, 45)->toFile($_FILES['photos2']['tmp_name']);
				}

				// don't mess with the gif man
				else if ( $img->getMimeType() == 'image/gif' )
				{
					echo '<span class="imgList">That gif is too small</span>';
					return;
				}

				clearstatcache();
			}

			/* RECHECK DIMENSIONS AFTER SCALING
			It will now be the correct width, but we need to be sure on the height
			*/

			if ($img->getHeight() < 45 || $img->getHeight() > 45)
			{
				echo '<span class="imgList">Image is just too short!</span>';
				return;
			}

			/* CHECK FILE SIZE
			So we now know for sure it has the correct minimum dimensions, but is the filesize okay?
			*/
			if (filesize($_FILES['photos2']['tmp_name']) > $core->config('max_tagline_image_filesize'))
			{
				// okay, so it's a rather big image you're trying to put up as a tagline image, let's make it no bigger than GOL's content area, as that would be utterly pointless
				if ($img->getWidth() > 120)
				{
					if( $img->getMimeType() == 'image/jpeg' || $img->getMimeType() == 'image/png' )
					{
						$img->resize(120, null)->toFile($_FILES['photos2']['tmp_name']);
					}

					// again, don't mess with the gif bro
					else if( $img->getMimeType() == 'image/gif' )
					{
						echo '<span class="imgList">File size too big!</span>';
						return;
					}
					
					clearstatcache();

					// if it's still too big, we should try compressing it
					if (filesize($_FILES['photos2']['tmp_name']) > $core->config('max_tagline_image_filesize'))
					{
						if( $img->getMimeType() == 'image/jpeg' || $img->getMimeType() == 'image/png' )
						{
							$img->toFile($_FILES['photos2']['tmp_name'], NULL, 80);
						}
						
						clearstatcache();
						
						// if it's still too big, we should try compressing it
						if (filesize($_FILES['photos2']['tmp_name']) > $core->config('max_tagline_image_filesize'))
						{
							echo '<span class="imgList">File size too big, tried compressing it, but still too big!</span>';
							return;
						}
					}
				}

				// okay, so width is good, let's try compressing directly instead then
				else if ($img->getWidth() <= 120)
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
		$imagename = rand() . 'golgdb.' . $file_ext;

		// the actual image
		$source = $_FILES['photos2']['tmp_name'];

		// where to upload to
		$target = $core->config('path') . "uploads/gamesdb/small/temp/" . $imagename;
		
		if (move_uploaded_file($source, $target))
		{
			// replace any existing just-uploaded image
			if (isset($_SESSION['gamesdb_smallpic']) && file_exists($core->config('path') . "uploads/gamesdb/small/temp/" . $_SESSION['gamesdb_smallpic']['image_name']))
			{
				unlink($core->config('path') . "uploads/gamesdb/small/temp/" . $_SESSION['gamesdb_smallpic']['image_name']);
			}

			$_SESSION['gamesdb_smallpic']['image_name'] = $imagename;
			$_SESSION['gamesdb_smallpic']['image_rand'] = $_SESSION['gamesdb_image_rand'];

			echo "<div class=\"test\" id=\"{$imagename}\"><img src=\"".$core->config('website_url')."uploads/gamesdb/small/temp/{$imagename}\"><br />";
			echo "<input type=\"hidden\" name=\"image_name\" value=\"{$imagename}\" />";
			echo 'Image is not saved until you hit edit! <a href=\"#\" id=\"{$imagename}\" class=\"trash_tagline\">Delete Image</a>';
		}

		else
		{
			echo '<span class="imgList">There was an error while trying to upload!</span>';
		}
	}
}
?>
