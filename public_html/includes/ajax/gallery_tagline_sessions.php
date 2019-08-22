<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if (isset($_POST['id']))
{
	// see if there's a previous image uploaded, this is set by ajax_tagline_upload.php
	if (isset($_SESSION['uploads_tagline']))
	{
		// remove the image
		unlink($core->config('path') . "uploads/articles/tagline_images/temp/" . $_SESSION['uploads_tagline']['image_name']);
		unlink($core->config('path') . "uploads/articles/tagline_images/temp/thumbnails/" . $_SESSION['uploads_tagline']['image_name']);

		// destroy the session for any other images
		unset($_SESSION['uploads_tagline']);
	}

	// set the id of the image in the session to detect and use when finishing the article
	$_SESSION['gallery_tagline_id'] = $_POST['id'];
	$_SESSION['gallery_tagline_rand'] = $_SESSION['image_rand'];
	$_SESSION['gallery_tagline_filename'] = $_POST['filename'];

	echo json_encode(array("result" => 'done'));
	return;
}
echo json_encode(array("result" => 'error'));
return;
