<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

function update_pubsubhubbub($url)
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL,"https://pubsubhubbub.appspot.com/");

	//Use the CURLOPT_HTTPHEADER option to set the Content-Type
	//for the request.
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/x-www-form-urlencoded'
	));

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,'hub.mode=publish&hub.url='.$url);

	// Receive server response ...
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$server_output = curl_exec($ch);
	
	curl_close ($ch);
}
?>
