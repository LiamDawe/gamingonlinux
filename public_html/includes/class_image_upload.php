<?php
use claviska\SimpleImage;

class image_upload
{
	protected $dbl;	
	public static $return_message;
	private $core;
	function __construct($dbl, $core)
	{
		$this->core = $core;
		$this->dbl = $dbl;
	}
	
	public function avatar($author_photo = 0)
	{
		if (is_uploaded_file($_FILES['new_image']['tmp_name']))
		{
			// this will make sure it is an image file, if it cant get an image size then its not an image
			if (!getimagesize($_FILES['new_image']['tmp_name']))
			{
				self::$return_message = 'not_image';
				return false;
			}

			// check the dimensions
			list($width, $height, $type, $attr) = getimagesize($_FILES['new_image']['tmp_name']);
			
			if ($width > $this->core->config('avatar_width') || $height > $this->core->config('avatar_height'))
			{
				$img = new SimpleImage();

				$img->fromFile($_FILES['new_image']['tmp_name'])->resize($this->core->config('avatar_width'), $this->core->config('avatar_height'))->toFile($_FILES['new_image']['tmp_name']);
			}

			// check if its too big
			if (filesize($_FILES['new_image']['tmp_name']) > 100000)
			{
				self::$return_message = 'too_big';
				return false;
			}

			// see if they currently have an avatar set
			$avatar = $this->dbl->run("SELECT `avatar`, `avatar_uploaded`, `author_picture` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

			$image_info = getimagesize($_FILES['new_image']['tmp_name']);
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

			$rand_name = rand(1,999);

			$imagename = $_SESSION['username'] . $rand_name . '_avatar.' . $file_ext;

			// the actual image
			$source = $_FILES['new_image']['tmp_name'];

			// where to upload to
			if ($author_photo == 0)
			{
				$target = $_SERVER['DOCUMENT_ROOT'] . "/uploads/avatars/" . $imagename;
			}
			else if ($author_photo == 1)
			{
				$target = $_SERVER['DOCUMENT_ROOT'] . "/uploads/avatars/author_pictures/" . $imagename;
			}

			if (move_uploaded_file($source, $target))
			{
				if ($author_photo == 0)
				{
					// remove old avatar
					if ($avatar['avatar_uploaded'] == 1)
					{
						unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $avatar['avatar']);
					}

					$this->dbl->run("UPDATE `users` SET `avatar` = ?, `avatar_uploaded` = 1, `avatar_gallery` = NULL WHERE `user_id` = ?", array($imagename, $_SESSION['user_id']));
					return true;
				}
				else if ($author_photo == 1)
				{
					// remove old avatar
					if (isset($avatar['author_picture']) && !empty($avatar['author_picture']))
					{
						unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/author_pictures/' . $avatar['author_picture']);
					}

					$this->dbl->run("UPDATE `users` SET `author_picture` = ? WHERE `user_id` = ?", array($imagename, $_SESSION['user_id']));
					return true;
				}
			}

			else
			{
				self::$return_message = 'cant_upload';
				return false;
			}
		}

		else
		{
			self::$return_message = 'no_file';
			return false;
		}
	}
	
		// $new has to be either 1 or 0
	// 1 = new article, 0 = editing the current image
	function featured_image($article_id, $new = NULL)
	{
		if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 4)
		{
			$_SESSION['message'] = 'nofile';
			return false;
		}

		$upload_path = $this->core->config('path') . "uploads/carousel/";

		$image_info = getimagesize($_FILES['new_image']['tmp_name']);
		$mime_type = $image_info['mime'];

		$allowed =  array('image/jpeg' , 'image/png', 'image/webp');
		if(!in_array($mime_type,$allowed))
		{
			$_SESSION['message'] = 'filetype';
    		return false;
		}

		// this will make sure it is an image file, if it cant get an image size then its not an image
		if (!getimagesize($_FILES['new_image']['tmp_name']))
		{
			$_SESSION['message'] = 'filetype';
    		return false;
		}

		if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0)
		{
			if (!@fopen($_FILES['new_image']['tmp_name'], 'r'))
			{
				$_SESSION['message'] = 'nofile';
				return false;
			}

			else
			{
				// check the dimensions
				$image_info = getimagesize($_FILES['new_image']['tmp_name']);

				list($width, $height, $type, $attr) = $image_info;

				if ($this->core->config('carousel_image_width') != $width || $this->core->config('carousel_image_height') != $height)
				{					
					$img = new SimpleImage();

					$img->fromFile($_FILES['new_image']['tmp_name'])->resize($this->core->config('carousel_image_width'), $this->core->config('carousel_image_height'))->toFile($_FILES['new_image']['tmp_name'], $mime_type, 99);
					clearstatcache();
				}

				// check if its too big
				if (filesize($_FILES['new_image']['tmp_name']) > $this->core->config('max_featured_image_filesize'))
				{
					$image_info = getimagesize($_FILES['new_image']['tmp_name']);
					$image_type = $image_info[2];
					if( $image_type == IMAGETYPE_JPEG )
					{
						$oldImage = imagecreatefromjpeg($_FILES['new_image']['tmp_name']);
						imagejpeg($oldImage, $_FILES['new_image']['tmp_name'], 95);
					}
					else if( $image_type == IMAGETYPE_PNG )
					{
						$oldImage = imagecreatefrompng($_FILES['new_image']['tmp_name']);
						imagepng($oldImage, $_FILES['new_image']['tmp_name'], 7);
					}

					clearstatcache();

					// check again
					if (filesize($_FILES['new_image']['tmp_name']) > $this->core->config('max_featured_image_filesize'))
					{
						// try reducing it some more
						if( $image_type == IMAGETYPE_JPEG )
						{
							$oldImage = imagecreatefromjpeg($_FILES['new_image']['tmp_name']);
							imagejpeg($oldImage, $_FILES['new_image']['tmp_name'], 90);

							clearstatcache();

							// still too big
							if (filesize($_FILES['new_image']['tmp_name']) > $this->core->config('max_featured_image_filesize'))
							{
								$_SESSION['message'] = 'toobig';
								return false;
							}
						}

						// gif so can't reduce it
						else
						{
							$_SESSION['message'] = 'toobig';
							return false;
						}
					}
				}
			}

			$file_ext = '';
			if ($mime_type == 'image/jpeg')
			{
				$file_ext = 'jpg';
			}
			else if ($mime_type == 'image/png')
			{
				$file_ext = 'png';
			}

			// give the image a random file name
			$imagename = rand() . 'id' . $article_id . 'gol.' . $file_ext;

			// the actual image
			$source = $_FILES['new_image']['tmp_name'];

			// where to upload to
			$target = $upload_path . $imagename;

			if (move_uploaded_file($source, $target))
			{
				$main_file = NULL;
				$backup_file = NULL;
				// make opposite files so we always have a backup for older/crap browsers
				if ($file_ext != 'webp')
				{
					$img = new SimpleImage();

					$new_webp = str_replace($file_ext, '', $imagename) . 'webp';

					$img->fromFile($target)->toFile($upload_path.$new_webp, 'image/webp', '90');

					if (filesize($target) < filesize($upload_path.$new_webp))
					{
						$main_file = $imagename;
						$backup_file = $new_webp;
					}
					else
					{
						$main_file = $new_webp;
						$backup_file = $imagename;
					}
				}

				if ($file_ext == 'webp')
				{
					$img = new SimpleImage();

					$new_jpg = str_replace('webp', '', $target) . 'jpg';

					$img->fromFile($target)->toFile($upload_path.$new_jpg, 'image/jpeg', '90');	
					
					if (filesize($target) < filesize($upload_path.$new_jpg))
					{
						$main_file = $imagename;
						$backup_file = $new_jpg;
					}
					else
					{
						$main_file = $new_jpg;
						$backup_file = $imagename;
					}
				}

				// we are editing an existing featured image
				if ($new == 0)
				{
					// see if there is a current top image
					$image = $this->dbl->run("SELECT `featured_image`, `featured_image_backup` FROM `editor_picks` WHERE `article_id` = ?", array($article_id))->fetch();

					// remove old image
					if (!empty($image['featured_image']))
					{
						$featured_image = $this->core->config('path') . 'uploads/carousel/' . $image['featured_image'];
						if (file_exists($featured_image))
						{
							unlink($featured_image);
						}
						$featured_image_backup = $this->core->config('path') . 'uploads/carousel/' . $image['featured_image_backup'];
						if (file_exists($featured_image_backup))
						{
							unlink($featured_image_backup);
						}

						$this->dbl->run("UPDATE `editor_picks` SET `featured_image` = ?, `featured_image_backup` = ? WHERE `article_id` = ?", array($main_file, $backup_file, $article_id));
					}
				}

				// it's a brand new featured image
				if ($new == 1)
				{
					$this->dbl->run("UPDATE `articles` SET `show_in_menu` = 1 WHERE `article_id` = ?", array($article_id));

					$this->dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_featured'");

					$end_date = $_POST['end_date'] . ' ' . $_POST['end_time'] . ':00';

					$this->dbl->run("INSERT INTO `editor_picks` SET `article_id` = ?, `featured_image` = ?, `featured_image_backup` = ?, `end_date` = ?", array($article_id, $main_file, $backup_file, $end_date));
				}

				return true;
			}


			else
			{
				$_SESSION['message'] = 'cantmove';
				return false;
			}

			return true;
		}
	}
}
