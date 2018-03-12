function twitch_check()
{
	$.getJSON("/includes/crons/goltwitchcheck.json", function(t) 
	{
		if (t["data"].length > 0 && t["data"][0].type == 'live')
		{
			if (t["game_name"].length > 0)
			{
				$( ".twitch_game" ).html('Title: ' + t["game_name"]);
			}
			else if (t["data"][0].title.length > 0)
			{
				$( ".twitch_game" ).html('Title: ' + t["data"][0].title);
			}
			
			$( ".gol_twitch" ).show();
		}
	});
} 