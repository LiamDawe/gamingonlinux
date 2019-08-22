<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

$counter = 0;

if ($handle = opendir(APP_ROOT.'/uploads/articles/tagline_images/temp/')) 
{
    while (false !== ($entry = readdir($handle)))
		{
			$full_filename = APP_ROOT . '/uploads/articles/tagline_images/temp/' . $entry;
			// if the file is older than 24 hours
			if (time()-filemtime($full_filename) > 24 * 3600)
			{
				$types = array('jpg', 'png', 'gif');
				$image_info = @getimagesize($full_filename); // supress errors, as we don't get if it cant read folders
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
				// if we managed to read it, it's an image, if the file extension matches (to be 100% certain it's an image) we can delete it
				if (in_array($file_ext, $types))
				{
					unlink($full_filename);
					echo 'Deleted' . $full_filename . PHP_EOL;
					$counter++;
				}
			}
    }
    closedir($handle);
}

if ($handle = opendir(APP_ROOT.'/uploads/articles/tagline_images/temp/thumbnails/')) 
{
    while (false !== ($entry = readdir($handle)))
		{
			$full_filename = APP_ROOT . '/uploads/articles/tagline_images/temp/thumbnails/' . $entry;
			// if the file is older than 24 hours
			if (time()-filemtime($full_filename) > 24 * 3600)
			{
				$types = array('jpg', 'png', 'gif');
				$image_info = @getimagesize($full_filename); // supress errors, as we don't get if it cant read folders
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
				// if we managed to read it, it's an image, if the file extension matches (to be 100% certain it's an image) we can delete it
				if (in_array($file_ext, $types))
				{
					unlink($full_filename);
					echo 'Deleted' . $full_filename . PHP_EOL;
					$counter++;
				}
			}
    }
    closedir($handle);
}

if ($counter == 0)
{
	echo 'Nothing to delete.';
}