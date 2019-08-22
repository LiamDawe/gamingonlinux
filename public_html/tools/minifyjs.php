<?php
// setup the URL, the JavaScript and the form data
$url = 'https://javascript-minifier.com/raw';
$save_filename = dirname(dirname(__FILE__)) . '/includes/jscripts/GOL/header.min.js';
$js = file_get_contents(dirname(dirname(__FILE__)) . '/includes/jscripts/GOL/header.js');
    
// init the request, set some info, send it and finally close it
$ch = curl_init($url);
    
curl_setopt_array($ch, [
     CURLOPT_URL => $url,
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_POST => true,
     CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"],
     CURLOPT_POSTFIELDS => http_build_query([ "input" => $js ])
 ]);

$minified = curl_exec($ch);

curl_close($ch);
    
// output the $minified js
if (file_put_contents($save_filename, $minified ) )
{
	chmod($save_filename, 0777);
	echo 'Done';
 }
?>

