<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');
define("THIS_ROOT", dirname( dirname(__FILE__) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/cron_bootstrap.php';

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