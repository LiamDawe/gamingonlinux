<?php
// include the image class to resize it as its too big
include_once(APP_ROOT . '/includes/image_class/SimpleImage.php');
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
	
	public function avatar()
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
			$avatar = $this->dbl->run("SELECT `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

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
			$target = $_SERVER['DOCUMENT_ROOT'] . "/uploads/avatars/" . $imagename;

			if (move_uploaded_file($source, $target))
			{
				// remove old avatar
				if ($avatar['avatar_uploaded'] == 1)
				{
					unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $avatar['avatar']);
				}

				$this->dbl->run("UPDATE `users` SET `avatar` = ?, `avatar_uploaded` = 1, `avatar_gravatar` = 0, `gravatar_email` = '', `avatar_gallery` = NULL WHERE `user_id` = ?", array($imagename, $_SESSION['user_id']));
				return true;
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
			return 'nofile';
		}

		$allowed =  array('gif', 'png' ,'jpg');
		$filename = $_FILES['new_image']['name'];
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if(!in_array($ext,$allowed) )
		{
    	return 'filetype';
		}

		// this will make sure it is an image file, if it cant get an image size then its not an image
		if (!getimagesize($_FILES['new_image']['tmp_name']))
		{
			return 'filetype';
		}

		if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0)
		{
			if (!@fopen($_FILES['new_image']['tmp_name'], 'r'))
			{
				return 'nofile';
			}

			else
			{
				// check the dimensions
				$image_info = getimagesize($_FILES['new_image']['tmp_name']);
				$image_type = $image_info[2];

				list($width, $height, $type, $attr) = $image_info;

				if ($this->core->config('carousel_image_width') > $width || $this->core->config('carousel_image_height') > $height)
				{					
					$img = new SimpleImage();

					$img->fromFile($_FILES['new_image']['tmp_name'])->resize($this->core->config('carousel_image_width'), $this->core->config('carousel_image_height'))->toFile($_FILES['new_image']['tmp_name']);
				}

				// check if its too big
				if ($_FILES['new_image']['size'] > 305900)
				{
					$image_info = getimagesize($_FILES['new_image']['tmp_name']);
					$image_type = $image_info[2];
					if( $image_type == IMAGETYPE_JPEG )
					{
						$oldImage = imagecreatefromjpeg($_FILES['new_image']['tmp_name']);
						imagejpeg($oldImage, $_FILES['new_image']['tmp_name'], 90);
					}

					// cannot compress gifs so it's just too big
					else if( $image_type == IMAGETYPE_GIF )
					{
						return 'File size too big! The max is 300kb, try to use some more compression on it, or find another image.';
					}

					else if( $image_type == IMAGETYPE_PNG )
					{
						$oldImage = imagecreatefrompng($_FILES['new_image']['tmp_name']);
						imagepng($oldImage, $_FILES['new_image']['tmp_name'], 7);
					}

					clearstatcache();

					// check again
					if (filesize($_FILES['new_image']['tmp_name']) > 305900)
					{
						// try reducing it some more
						if( $image_type == IMAGETYPE_JPEG )
						{
							$oldImage = imagecreatefromjpeg($_FILES['new_image']['tmp_name']);
							imagejpeg($oldImage, $_FILES['new_image']['tmp_name'], 80);

							clearstatcache();

							// still too big
							if (filesize($_FILES['new_image']['tmp_name']) > 305900)
							{
								return 'toobig';
							}
						}

						// gif so can't reduce it
						else
						{
							return 'toobig';
						}
					}
				}
			}

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

			// give the image a random file name
			$imagename = rand() . 'id' . $article_id . 'gol.' . $file_ext;

			// the actual image
			$source = $_FILES['new_image']['tmp_name'];

			// where to upload to
			$target = $this->core->config('path') . "uploads/carousel/" . $imagename;

			if (move_uploaded_file($source, $target))
			{
				// we are editing an existing featured image
				if ($new == 0)
				{
					// see if there is a current top image
					$image = $this->dbl->run("SELECT `featured_image` FROM `editor_picks` WHERE `article_id` = ?", array($article_id))->fetch();

					// remove old image
					if (!empty($image['featured_image']))
					{
						unlink($this->core->config('path') . 'uploads/carousel/' . $image['featured_image']);
						$this->dbl->run("UPDATE `editor_picks` SET `featured_image` = ? WHERE `article_id` = ?", array($imagename, $article_id));
					}
				}

				// it's a brand new featured image
				if ($new == 1)
				{
					$this->dbl->run("UPDATE `articles` SET `show_in_menu` = 1 WHERE `article_id` = ?", array($article_id));

					$this->dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_featured'");

					$this->dbl->run("INSERT INTO `editor_picks` SET `article_id` = ?, `featured_image` = ?", array($article_id, $imagename));
				}

				return true;
			}


			else
			{
				return 'cantmove';
			}

			return true;
		}
	}
}
