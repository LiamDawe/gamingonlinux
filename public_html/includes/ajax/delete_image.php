<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

require APP_ROOT . '/includes/aws/aws-autoloader.php';

$key = $core->config('do_space_key_uploads');
$secret = $core->config('do_space_key_private_uploads');

$space_name = "goluploads";

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

// only logged in accounts can do this too, it's only for articles
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	die('You shouldn\'t be here. You need to be logged in.');
}

if (isset($_POST['image_id']) && is_numeric($_POST['image_id']) && isset($_POST['type']))
{
	switch($_POST['type'])
	{
		case 'itemdb':
		case 'itemdb_featured':
			$qry1 = "SELECT `filename`, `location`, `item_id` FROM `itemdb_images` WHERE `id` = ?";
			$qry2 = "DELETE FROM `itemdb_images` WHERE `id` = ?";				
		break;
		case 'article':
			$qry1 = "SELECT `id`, `filename`, `location`, `filetype`, `youtube_cache`, `uploader_id` FROM `article_images` WHERE `id` = ?";
			$qry2 = "DELETE FROM `article_images` WHERE `id` = ?";
		break;
	}

	$grabber = $dbl->run($qry1, array($_POST['image_id']))->fetch();
	$result = $dbl->run($qry2, array($_POST['image_id']));
	if(isset($result))
	{
		if (isset($_POST['type']) && ($_POST['type'] == 'itemdb' || $_POST['type'] == 'itemdb_featured'))
		{
			if ($grabber['item_id'] > 0)
			{
				$item_id = $grabber['item_id'];
			}
			else
			{
				$item_id = 'tmp';
			}

			$main = APP_ROOT . '/uploads/gamesdb/big/'. $item_id .'/' . $grabber['filename'];
			$thumb = APP_ROOT . '/uploads/gamesdb/big/thumbs/'. $item_id .'/' . $grabber['filename'];	

			if ($grabber['location'] == NULL)
			{
				if (file_exists($main))
				{
					unlink($main);
				}
				if (file_exists($thumb))
				{
					unlink($thumb);
				}
			}
			else
			{
				$result = $client->deleteObject([
					'Bucket' => 'goluploads',
					'Key'    => 'uploads/gamesdb/big/' . $item_id . '/' . $grabber['filename']
				]);				
			}
		}
		else
		{
			if ($grabber['youtube_cache'] == 0)
			{
				if ($grabber['location'] == NULL)
				{
					$main = APP_ROOT . '/uploads/articles/article_media/' . $grabber['filename'];

					if (file_exists($main))
					{
						unlink($main);
					}

					if ($grabber['filetype'] == 'gif')
					{
						$static_filename = str_replace('.gif', '_static.jpg', $grabber['filename']);

						$static = APP_ROOT . '/uploads/articles/article_media/' . $static_filename;

						if (file_exists($static))
						{
							unlink($static);
						}
						
					}

					$thumbnail = APP_ROOT . '/uploads/articles/article_media/thumbs/' . $grabber['filename'];

					if (file_exists($thumbnail))
					{
						unlink($thumbnail);
					}
				}
				/* EXTERNAL FILE UPLOADS 
				This is for deleting from DO Spaces, AWS etc
				*/				
				else
				{
					$result = $client->deleteObject([
						'Bucket' => 'goluploads',
						'Key'    => 'uploads/articles/article_media/' . $grabber['filename']
					]);
					
					if ($grabber['filetype'] == 'gif')
					{
						$static_filename = str_replace('.gif', '_static.jpg', $grabber['filename']);
						
						$result = $client->deleteObject([
							'Bucket' => 'goluploads',
							'Key'    => 'uploads/articles/article_media/' . $static_filename
						]);
					}

					$result = $client->deleteObject([
						'Bucket' => 'goluploads',
						'Key'    => 'uploads/articles/article_media/thumbs/' . $grabber['filename']
					]);
				}
			}
			else
			{
				if (file_exists(APP_ROOT . '/cache/youtube_thumbs/' . $grabber['filename']))
				{
					unlink(APP_ROOT . '/cache/youtube_thumbs/' . $grabber['filename']);
				}				
			}
		}
		echo "YES";
	}

	else
	{
		echo "NO";
	}
}
else
{
	echo "NO";
}
?>
