<?php
session_start();

include('config.php');

include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('class_core.php');
$core = new core();

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
				$image_name=time().$filename;
				$newname=$uploaddir.$image_name; //Check / delete file it exists

				if (move_uploaded_file($_FILES['photos']['tmp_name'][$name], $newname))
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

					echo "<div class=\"box\"><div class=\"body group\"><div id=\"{$image_id}\"><img src=\"" . core::config('website_url') . "uploads/articles/article_images/$image_name\" class='imgList'><br />";
					echo "BBCode: <input id=\"img{$image_id}\" type=\"text\" class=\"form-control\" value=\"{$bbcode}\" /> <button class=\"btn\" data-clipboard-target=\"#img{$image_id}\">Copy</button> <button data-bbcode=\"{$bbcode}\" class=\"add_button\">Add to editor</button> <button id=\"{$image_id}\" class=\"trash\">Delete image</button>";
					echo "</div></div></div>";
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
