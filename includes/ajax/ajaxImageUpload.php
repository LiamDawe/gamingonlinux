<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_user.php');
$user = new user();
$user->check_session();

include_once($file_dir . '/includes/class_image.php');
$image_func = new SimpleImage();

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
				$image_name = time().$filename;
				$main_newname = $uploaddir.$image_name; //Check / delete file it exists

				// thumbs
				$thumb_newname = $thumbs_dir.$image_name;
				$image_func->load($_FILES['photos']['tmp_name'][$name]);
				$image_func->scale(300);
				$image_func->save($thumb_newname);
				
				if (move_uploaded_file($_FILES['photos']['tmp_name'][$name], $main_newname))
				{
					$article_id = 0;
					if (isset($_POST['article_id']) && is_numeric($_POST['article_id']))
					{
						$article_id = $_POST['article_id'];
					}
					$db->sqlquery("INSERT INTO `article_images` SET `filename` = ?, `uploader_id` = ?, `date_uploaded` = ?, `article_id` = ?", array($image_name, $_SESSION['user_id'], core::$date, $article_id));
					$image_id = $db->grab_id();

					// if they aren't adding the image to an existing article, store it in the session
					if (!isset($_POST['article_id']) || $_POST['article_id'] == 0)
					{
						$_SESSION['uploads'][$image_id]['image_name'] = $image_name;
						$_SESSION['uploads'][$image_id]['image_id'] = $image_id;
						$_SESSION['uploads'][$image_id]['image_rand'] = $_SESSION['image_rand'];
					}

					$bbcode = "[img]" . core::config('website_url') . "uploads/articles/article_images/{$image_name}[/img]";
					$bbcode_thumb = "[img]" . core::config('website_url') . "uploads/articles/article_images/thumbs/{$image_name}[/img]";

					echo "<div class=\"box\">
					<div class=\"body group\">
					<div id=\"{$image_id}\"><img src=\"" . core::config('website_url') . "uploads/articles/article_images/thumbs/$image_name\" class='imgList'><br />
					BBCode: <input id=\"img{$image_id}\" type=\"text\" class=\"form-control\" value=\"{$bbcode}\" /> <button class=\"btn\" data-clipboard-target=\"#img{$image_id}\">Copy</button> <button data-bbcode=\"{$bbcode}\" class=\"add_button\">Add to editor</button>
					BBCode (thumbnail): <input id=\"img{$image_id}_thumb\" type=\"text\" class=\"form-control\" value=\"{$bbcode_thumb}\" /> <button class=\"btn\" data-clipboard-target=\"#img{$image_id}_thumb\">Copy</button> <button data-bbcode=\"{$bbcode_thumb}\" class=\"add_button\">Add to editor</button> <button id=\"{$image_id}\" class=\"trash\">Delete image</button>
					</div>
					</div>
					</div>";
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
