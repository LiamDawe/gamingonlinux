<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

define('TIMESTAMP', date("c", strtotime("now")));

function post_to_discord($data)
{
	//=======================================================================================================
	// Compose message. You can use Markdown
	// Message Formatting -- https://discordapp.com/developers/docs/reference#message-formatting
	//========================================================================================================

	$json_data = json_encode([
		// Message
		"content" => $data['title'] . ' -> ' . $data['link'],
		
		// Username
		"username" => "GOL News",

		// Avatar URL.
		"avatar_url" => "https://www.gamingonlinux.com/templates/default/images/icon.png",

		// Text-to-speech
		"tts" => false,

		// File upload
		// "file" => "",

		// Embeds Array
		"embeds" => [
			[
				// Embed Title
				"title" => $data['title'],

				// Embed Type
				"type" => "rich",

				// Embed Description
				"description" => $data['tagline'],

				// URL of title link
				"url" => $data['link'],

				// Timestamp of embed must be formatted as ISO8601
				"timestamp" => TIMESTAMP,

				// Embed left border color in HEX
				"color" => hexdec( "3366ff" ),

				// Image to send
				"image" => [
					"url" => $data['image']
				],

				// Thumbnail
				//"thumbnail" => [
				//    "url" => "https://ru.gravatar.com/userimage/28503754/1168e2bddca84fec2a63addb348c571d.jpg?size=400"
				//],

				// Author
				"author" => [
					"name" => "GamingOnLinux.com",
					"url" => "https://www.gamingonlinux.com"
				],

				// Additional Fields array
				/*"fields" => [
					// Field 1
					[
						"name" => "Field #1 Name",
						"value" => "Field #1 Value",
						"inline" => false
					],
					// Field 2
					[
						"name" => "Field #2 Name",
						"value" => "Field #2 Value",
						"inline" => true
					]
					// Etc..
				]*/
			]
		]

	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


	$ch = curl_init( WEBHOOK_URL );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	// If you need to debug, or find out why you can't send message uncomment line below, and execute script.
	// echo $response;
	curl_close( $ch );
}
?>
