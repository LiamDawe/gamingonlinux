<?php
session_start();

include('config.php');

include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('class_core.php');
$core = new core();

include_once('class_image.php');
$image_func = new SimpleImage();

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
			// check the dimensions
			$image_info = getimagesize($_FILES['photos2']['tmp_name']);
			$image_func->image_type = $image_info[2];

			// if the image is just too small in dimensions
			list($width, $height, $type, $attr) = $image_info;

			/* SCALING UP TO 550
			// has to be at least 550 to work on social media websites for the image to be auto-included in posts like on G+ and Facebook
			// Do this by itself first, so that we can try to preserve aspect ratio!
			*/
			if ($width < 550)
			{
				if( $image_func->image_type == IMAGETYPE_JPEG )
				{
					// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
					$image_func->load($_FILES['photos2']['tmp_name']);
					$image_func->scale(550);
					$image_func->save($_FILES['photos2']['tmp_name']);
				}

				// cannot compress gifs so it's just too big
				else if( $image_func->image_type == IMAGETYPE_GIF )
				{
					echo '<span class="imgList">File size too big!</span>';
					return;
				}

				else if( $image_func->image_type == IMAGETYPE_PNG )
				{
					// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
					$image_func->load($_FILES['photos2']['tmp_name']);
					$image_func->scale(550);
					$image_func->save($_FILES['photos2']['tmp_name']);

					// compress it a bit
					$oldImage = imagecreatefrompng($_FILES['photos2']['tmp_name']);
					imagepng($oldImage, $_FILES['photos2']['tmp_name'], 7);
				}

				clearstatcache();
			}

			/* RECHECK DIMENSIONS AFTER SCALING
			It will now be the correct width, but we need to be sure on the height
			*/
			$image_info = getimagesize($_FILES['photos2']['tmp_name']);

			// if the image is just too small in dimensions
			list($width, $height, $type, $attr) = $image_info;
			if ($height < 250)
			{
				echo '<span class="imgList">Image is just too small!</span>';
				return;
			}

			/* CHECK FILE SIZE
			So we know for sure it has the correct minimum dimensions
			*/
			if (filesize($_FILES['photos2']['tmp_name']) > core::config('max_tagline_image_filesize'))
			{
				$image_info = getimagesize($_FILES['photos2']['tmp_name']);

				// okay, so it's a rather big image you're trying to put up as a tagline image, let's make it no bigger than GOL's content area
				if ($width > 950)
				{
					if( $image_func->image_type == IMAGETYPE_JPEG )
					{
						$image_func->load($_FILES['photos2']['tmp_name']);
						$image_func->scale(950);
						$image_func->save($_FILES['photos2']['tmp_name']);
					}

					// cannot compress gifs so it's just too big
					else if( $image_func->image_type == IMAGETYPE_GIF )
					{
						echo '<span class="imgList">File size too big!</span>';
						return;
					}

					else if( $image_func->image_type == IMAGETYPE_PNG )
					{
						$image_func->load($_FILES['photos2']['tmp_name']);
						$image_func->scale(950);
						$image_func->save($_FILES['photos2']['tmp_name']);

						$oldImage = imagecreatefrompng($_FILES['photos2']['tmp_name']);
						imagepng($oldImage, $_FILES['photos2']['tmp_name'], 7);
					}
				}

				// okay, so width is good, let's try compressing directly instead then
				else if ($width < 950)
				{
					if( $image_func->image_type == IMAGETYPE_JPEG )
					{
						// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
						$image_func->load($_FILES['photos2']['tmp_name']);
						$image_func->save($_FILES['photos2']['tmp_name'], $image_func->image_type, 70);
					}

					// cannot compress gifs so it's just too big
					else if( $image_func->image_type == IMAGETYPE_GIF )
					{
						echo '<span class="imgList">File size too big!</span>';
						return;
					}

					else if( $image_func->image_type == IMAGETYPE_PNG )
					{
						// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
						$image_func->load($_FILES['photos2']['tmp_name']);
						$image_func->save($_FILES['photos2']['tmp_name']);

						$oldImage = imagecreatefrompng($_FILES['photos2']['tmp_name']);
						imagepng($oldImage, $_FILES['photos2']['tmp_name'], 8);
					}
				}

				clearstatcache();

				// check again after scaling and compressing a bit, try reducing it some more  as a last resort
				if (filesize($_FILES['photos2']['tmp_name']) > core::config('max_tagline_image_filesize'))
				{
					if( $image_func->image_type == IMAGETYPE_JPEG )
					{
						$image_func->load($_FILES['photos2']['tmp_name']);
						$image_func->save($_FILES['photos2']['tmp_name'], $image_func->image_type, 65);
					}

					else if( $image_func->image_type == IMAGETYPE_GIF )
					{
						echo '<span class="imgList">File size too big!</span>';
						return;
					}

					else if( $image_func->image_type == IMAGETYPE_PNG )
					{
						$image_func->load($_FILES['photos2']['tmp_name']);
						$image_func->save($_FILES['photos2']['tmp_name']);

						$oldImage = imagecreatefrompng($_FILES['photos2']['tmp_name']);
						imagepng($oldImage, $_FILES['photos2']['tmp_name'], 9);
					}

					clearstatcache();

					// still too big, must be a fecking huge image they're trying to upload wtf
					if (filesize($_FILES['photos2']['tmp_name']) > core::config('max_tagline_image_filesize'))
					{
						echo '<span class="imgList">File size too big!</span>';
						return;
					}
				}
			}

			// this will make finally sure it is an image file (cant hurt to check one last time in another way), if it cant get an image size then its not an image
			if (!getimagesize($_FILES['photos2']['tmp_name']))
			{
				echo '<span class="imgList">That was not an image!</span>';
				return;
			}
		}

		$image_info = getimagesize($_FILES['photos2']['tmp_name']);
		$file_ext = '';
		if( $image_func->image_type == IMAGETYPE_JPEG )
		{
			$file_ext = 'jpg';
		}

		else if( $image_func->image_type == IMAGETYPE_GIF )
		{
			$file_ext = 'gif';
		}

		else if( $image_func->image_type == IMAGETYPE_PNG )
		{
			$file_ext = 'png';
		}

		// give the image a random file name
		$imagename = rand() . 'idgol.' . $file_ext;

		// the actual image
		$source = $_FILES['photos2']['tmp_name'];

		// where to upload to
		$target = core::config('path') . "uploads/articles/tagline_images/temp/" . $imagename;

		// make the thumbnail, nice and small
		$image_func->load($_FILES['photos2']['tmp_name']);
		$image_func->scale(350);
		$image_func->save(core::config('path') . "uploads/articles/tagline_images/temp/thumbnails/" . $imagename);

		if (move_uploaded_file($source, $target))
		{
			// replace any existing just-uploaded image
			if (isset($_SESSION['uploads_tagline']))
			{
				unlink(core::config('path') . "uploads/articles/tagline_images/temp/" . $_SESSION['uploads_tagline']['image_name']);
				unlink(core::config('path') . "uploads/articles/tagline_images/temp/thumbnails/" . $_SESSION['uploads_tagline']['image_name']);
			}

			$_SESSION['uploads_tagline']['image_name'] = $imagename;
			$_SESSION['uploads_tagline']['image_rand'] = $_SESSION['image_rand'];

			echo "<div class=\"test\" id=\"{$imagename}\"><img src=\"".core::config('website_url')."uploads/articles/tagline_images/temp/thumbnails/{$imagename}\" class='imgList'><br />";
			echo "BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />";
			echo "<input type=\"hidden\" name=\"image_name\" value=\"{$imagename}\" />";
			echo "<a href=\"#\" id=\"{$imagename}\" class=\"trash_tagline\">Delete Image</a></div>";
		}

		else
		{
			echo '<span class="imgList">There was an error while trying to upload!</span>';
		}
	}
}
?>
