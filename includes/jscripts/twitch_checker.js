function twitch_check(twitch_key, channel)
{
	$.getJSON("https://api.twitch.tv/kraken/streams?client_id="+twitch_key+"&channel="+channel, function(t) 
	{
		if (t["streams"].length > 0)
		{
			//console.log(a["streams"][0].game);
			if (t["streams"][0].game.length > 0)
			{
				$( ".twitch_game" ).html('Playing: ' + t["streams"][0].game);
			}
			$( ".gol_twitch" ).show();
		}
	});
} 