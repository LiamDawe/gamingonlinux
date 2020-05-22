<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

function exec_curl_request($handle) 
{
	$response = curl_exec($handle);

	if ($response === false) 
	{
		$errno = curl_errno($handle);
		$error = curl_error($handle);
		error_log("Curl returned error $errno: $error\n");
		curl_close($handle);
		return false;
	}

	$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
	curl_close($handle);

	if ($http_code >= 500) 
	{
		// do not wat to DDOS server if something goes wrong
		sleep(10);
		return false;
	} 
	else if ($http_code != 200) 
	{
		$response = json_decode($response, true);
		error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
		if ($http_code == 401) 
		{
			throw new Exception('Invalid access token provided');
		}
		return false;
	} 
	else 
	{
		$response = json_decode($response, true);
		if (isset($response['description'])) 
		{
			error_log("Request was successfull: {$response['description']}\n");
		}
		$response = $response['result'];
	}

	return $response;
}

function apiRequest($method, $parameters) 
{
	if (!is_string($method)) 
	{
		error_log("Method name must be a string\n");
		return false;
	}

	if (!$parameters) 
	{
		$parameters = array();
	} 
	else if (!is_array($parameters)) 
	{
		error_log("Parameters must be an array\n");
		return false;
	}

	foreach ($parameters as $key => &$val) 
	{
		// encoding to JSON array parameters, for example reply_markup
		if (!is_numeric($val) && !is_string($val)) 
		{
			$val = json_encode($val);
		}
	}
	$url = API_URL.$method.'?'.http_build_query($parameters);

	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($handle, CURLOPT_TIMEOUT, 60);

	return exec_curl_request($handle);
}

function telegram($message, $article_link)
{
	if (!empty(BOT_TOKEN))
	{
		// process incoming message
		$chat_id = "@" . CHAT_ID;

		$keyboard = array(
			"inline_keyboard" => array(array(array("text" => "Read on GOL", "url" => $article_link)))
		);

		if (isset($message))
		{
			apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "HTML", "text" => $message, 'reply_markup' => $keyboard));
		}
	}
}
?>
