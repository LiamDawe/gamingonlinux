<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');
define("THIS_ROOT", dirname( dirname(__FILE__) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/cron_bootstrap.php';

/* driver support info */

$save_file = dirname(__FILE__) . '/nvidialist.txt';

if (!file_exists($save_file))
{
	$myfile = fopen($save_file, "w") or die("Unable to open file!");
	fwrite($myfile, $new_info);
	fclose($myfile);
}

$current_info = file_get_contents($save_file);

$url = "https://nvidia.custhelp.com/app/answers/detail/a_id/3142/";
$html = file_get_html($url . $page);
$page_info = $html->find('div[id=container] div.rn_Container_Main',0);

$new_info = md5($page_info->outertext);

if ($current_info != $new_info)
{
	echo 'CHANGE DETECTED';

	$myfile = fopen($save_file, "w") or die("Unable to open file!");
	fwrite($myfile, $new_info);
	fclose($myfile);

	$to = $core->config('contact_email');
	$subject = 'GOL NVIDIA Scraper New';

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mailer($core);
		$mail->sendMail($to, $subject, "Detected change in NVIDIA driver support info: <a href=\"https://nvidia.custhelp.com/app/answers/detail/a_id/3142/\">https://nvidia.custhelp.com/app/answers/detail/a_id/3142/</a>");
	}
}

/* vulkan beta driver */

$vksave_file = dirname(__FILE__) . '/nvidiavulkanbeta.txt';

if (!file_exists($vksave_file))
{
	$myfile = fopen($vksave_file, "w") or die("Unable to open file!");
	fwrite($myfile, $new_info);
	fclose($myfile);
}

$vkcurrent_info = file_get_contents($vksave_file);

$url = "https://developer.nvidia.com/vulkan-driver";
$html = file_get_html($url . $page);
$vkpage_info = $html->find('div[id=wrapper] div[id=content-background] div[id=content]',0);

$vknew_info = md5($vkpage_info->outertext);

if ($vkcurrent_info != $vknew_info)
{
	echo 'Vulkan Beta CHANGE DETECTED';

	$myfile = fopen($vksave_file, "w") or die("Unable to open file!");
	fwrite($myfile, $vknew_info);
	fclose($myfile);

	$to = $core->config('contact_email');
	$subject = 'GOL NVIDIA Vulkan Beta Scraper New';

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mailer($core);
		$mail->sendMail($to, $subject, "Detected change in NVIDIA Vulkan beta driver page: <a href=\"https://developer.nvidia.com/vulkan-driver\">https://developer.nvidia.com/vulkan-driver</a>");
	}
}