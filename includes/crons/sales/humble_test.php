<?php
$url = "https://www.humblebundle.com/store/api?request=3&page_size=20&sort=discount&page=0&platform=linux";
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	die('Humble XML not available!');
}

$json = json_decode(file_get_contents($url));

$total_pages = $json->{'num_pages'};

echo $total_pages;

for ($i = 0; $i <= $total_pages; $i++) 
{
	$json = json_decode(file_get_contents("https://www.humblebundle.com/store/api?request=3&page_size=20&sort=discount&page=$i&platform=linux"), true);

	foreach ($json['results'] as $game)
	{
		if (in_array('linux', $game['platforms']))
		{
			//print_r($game);
			if ($game['current_price'][0] != $game['full_price'][0])
			{
				echo '<img src="https://www.humblebundle.com'. $game['storefront_featured_image_small'] .' " alt=""/><br />Link: https://www.humblebundle.com/store/p/' . $game['machine_name'] . '<br />' .  $game['human_name'] . ' Current Price: $' . $game['current_price'][0]  .  ', Full Price: $' . $game['full_price'][0] . '<br />';

				$drm_free = 0;
				$steam = 0;

				if (in_array('download', $game['delivery_methods']))
				{
					$drm_free = 1;
				}

				if (in_array('steam', $game['delivery_methods']))
				{
					$steam = 1;
				}

				echo 'DRM Free: ' . $drm_free . '<br />';
				echo 'Steam Key: ' . $steam . '<br />';
			}
		}
	}

	print_r($json);
}
