<?php
//$path = "/mnt/storage/public_html/";
$path = "/home/gamingonlinux/public_html/";

if ($handle = opendir($path.'uploads/articles/tagline_images/temp/')) {
    echo "Entries:<br />";

    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle)))
		{
			$full_filename = $path . 'uploads/articles/tagline_images/temp/' . $entry;
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
					echo 'Deleted: ' . $entry . '</br>';
					unlink($full_filename);
				}
			}
    }
    closedir($handle);
}

if ($handle = opendir($path.'uploads/articles/tagline_images/temp/thumbnails/')) {
    echo "Entries:<br />";

    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle)))
		{
			$full_filename = $path . 'uploads/articles/tagline_images/temp/thumbnails/' . $entry;
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
					echo 'Deleted: ' . $entry . '</br>';
					unlink($full_filename);
				}
			}
    }
    closedir($handle);
}
