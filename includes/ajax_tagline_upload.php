<?php
session_start();

include('config.php');

include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('class_core.php');
$core = new core();

include_once('class_image.php');
$image_func = new SimpleImage();

// get config
$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
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
			// check the dimensions
			$image_info = getimagesize($_FILES['photos2']['tmp_name']);
			$image_type = $image_info[2];

			// if the image is just too small in dimensions
			list($width, $height, $type, $attr) = $image_info;

			// has to be at least 550 to work on social media websites for the image to be auto-included in posts like on G+ and Facebook
			if ($width < 550 || $height < 250)
			{
				$image_info = getimagesize($_FILES['photos2']['tmp_name']);
				$image_type = $image_info[2];
				if( $image_type == IMAGETYPE_JPEG )
				{
					// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
					$image_func->load($_FILES['photos2']['tmp_name']);
					$image_func->scale(550);
					$image_func->save($_FILES['photos2']['tmp_name']);

					// compress it a lil bit
					$oldImage = imagecreatefromjpeg($_FILES['photos2']['tmp_name']);
					imagejpeg($oldImage, $_FILES['photos2']['tmp_name'], 90);
				}

				// cannot compress gifs so it's just too big
				else if( $image_type == IMAGETYPE_GIF )
				{
					echo '<span class="imgList">File size too big!</span>';
					return;
				}

				else if( $image_type == IMAGETYPE_PNG )
				{
					// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
					$image_func->load($_FILES['photos2']['tmp_name']);
					$image_func->scale(550);
					$image_func->save($_FILES['photos2']['tmp_name']);

					$oldImage = imagecreatefrompng($_FILES['photos2']['tmp_name']);
					imagepng($oldImage, $_FILES['photos2']['tmp_name'], 7);
				}

				clearstatcache();
			}

			// check if its too big
			if ($_FILES['photos2']['size'] > core::config('max_tagline_image_filesize'))
			{
				$image_info = getimagesize($_FILES['photos2']['tmp_name']);
				$image_type = $image_info[2];
				if( $image_type == IMAGETYPE_JPEG )
				{
					// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
					$image_func->load($_FILES['photos2']['tmp_name']);
					$image_func->scale(950);
					$image_func->save($_FILES['photos2']['tmp_name']);

					// compress it a lil bit
					$oldImage = imagecreatefromjpeg($_FILES['photos2']['tmp_name']);
					imagejpeg($oldImage, $_FILES['photos2']['tmp_name'], 90);
				}

				// cannot compress gifs so it's just too big
				else if( $image_type == IMAGETYPE_GIF )
				{
					echo '<span class="imgList">File size too big!</span>';
					return;
				}

				else if( $image_type == IMAGETYPE_PNG )
				{
					// so it's too big in filesize, let's make sure the image isn't bigger than our content section can fit to see if we can reduce filesize a bit
					$image_func->load($_FILES['photos2']['tmp_name']);
					$image_func->scale(950);
					$image_func->save($_FILES['photos2']['tmp_name']);

					$oldImage = imagecreatefrompng($_FILES['photos2']['tmp_name']);
					imagepng($oldImage, $_FILES['photos2']['tmp_name'], 7);
				}

				clearstatcache();

				// check again after scaling and downsmapling a bit
				if (filesize($_FILES['photos2']['tmp_name']) > core::config('max_tagline_image_filesize'))
				{
					// try reducing it some more
					if( $image_type == IMAGETYPE_JPEG )
					{
						$oldImage = imagecreatefromjpeg($_FILES['photos2']['tmp_name']);
						imagejpeg($oldImage, $_FILES['photos2']['tmp_name'], 85);

						clearstatcache();

						// still too big
						if (filesize($_FILES['photos2']['tmp_name']) > core::config('max_tagline_image_filesize'))
						{
							echo '<span class="imgList">File size too big!</span>';
							return;
						}
					}

					// gif so can't reduce it
					else
					{
						echo '<span class="imgList">File size too big!</span>';
						return;
					}
				}
			}

			// this will make sure it is an image file, if it cant get an image size then its not an image
			if (!getimagesize($_FILES['photos2']['tmp_name']))
			{
				echo '<span class="imgList">That was not an image!</span>';
				return;
			}
		}

		$image_info = getimagesize($_FILES['photos2']['tmp_name']);
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
		$imagename = rand() . 'idgol.' . $file_ext;

		// the actual image
		$source = $_FILES['photos2']['tmp_name'];

		// where to upload to
		$target = core::config('path') . "uploads/articles/tagline_images/temp/" . $imagename;

		$image_func->load($_FILES['photos2']['tmp_name']);
		$image_func->scale(350);
		$image_func->save(core::config('path') . "uploads/articles/tagline_images/temp/thumbnails/" . $imagename, $image_type);

		if (move_uploaded_file($source, $target))
		{
			// replace any existing just-uploaded image
			if (isset($_SESSION['uploads_tagline']))
			{
				unlink(core::config('path') . "uploads/articles/tagline_images/temp/" . $_SESSION['uploads_tagline']['image_name']);
				unlink(core::config('path') . "uploads/articles/tagline_images/thumbnails/temp/" . $_SESSION['uploads_tagline']['image_name']);
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
