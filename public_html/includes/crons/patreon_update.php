<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require_once(APP_ROOT . '/vendor/autoload.php');
require_once(APP_ROOT . '/includes/bootstrap.php');

use Patreon\API;
use Patreon\OAuth;

// Get your current "Creator's Access Token" from https://www.patreon.com/platform/documentation/clients
$access_token = "-L5XGtDEzg1rmVY-v3q9oY4tgzc_n_E3pJZvB8q1RdA";
// Get your "Creator's Refesh Token" from https://www.patreon.com/platform/documentation/clients
$refresh_token = "OtCQPcQATOZ0jh57tput049xFsnBJSnnL7Nv8tMZKaQ";
$api_client = new API($access_token);

// Get your campaign data
$campaign_response = $api_client->fetch_campaign();

// If the token doesn't work, get a newer one
if ($campaign_response->has('errors')) 
{
    echo "Got an error\n";
    print_r($campaign_response->get('errors.0')->asArray());

    echo "Refreshing tokens\n";
    // Make an OAuth client
    // Get your Client ID and Secret from https://www.patreon.com/platform/documentation/clients
    $client_id = null;
    $client_secret = null;
    $oauth_client = new OAuth($client_id, $client_secret);
    // Get a fresher access token
    $tokens = $oauth_client->refresh_token($refresh_token, null);
	if ($tokens['access_token']) 
	{
        $access_token = $tokens['access_token'];
        echo "Got a new access_token! Please overwrite the old one in this script with: " . $access_token . " and try again.";
	} 
	else 
	{
        echo "Can't fetch new tokens. Please debug, or write in to Patreon support.\n";
        print_r($tokens);
    }
    return;
}

if (!$campaign_response->has('data.0.id')) 
{
    echo "No campaign found. Please check you have an access token for a Patreon creator.\n";
}

// get page after page of pledge data
$campaign_id = $campaign_response->get('data.0.id');
$cursor = null;
while (true) 
{
    $pledges_response = $api_client->fetch_page_of_pledges($campaign_id, 25, $cursor);
    // loop over the pledges to get e.g. their amount and user name
	foreach ($pledges_response->get('data')->getKeys() as $pledge_data_key) 
	{
		$pledge_data = $pledges_response->get('data')->get($pledge_data_key);
		$pledge_amount = $pledge_data->attribute('amount_cents');
		$declined_since = $pledge_data->attribute('declined_since');
		$patron = $pledge_data->relationship('patron')->resolve($pledges_response);
		$patron_full_name = $patron->attribute('full_name');
		$patron_email = trim($patron->attribute('email'));
		$created_at = $pledge_data->attribute('created_at');
		echo '<p><strong>' . $patron_full_name . "</strong><br /> 
		Created on: ".$created_at."
		<br />Email: " . $patron_email . "<br />
		Pledging: " . $pledge_amount . " cents." . "<br />
		Declined? " . $declined_since . "</p>";
		
		if ($declined_since == NULL && $pledge_amount >= 400)
		{
			$user_info = $dbl->run("SELECT `username`, `user_id` FROM `users` WHERE `email` = ? OR `supporter_email` = ?", array($patron_email, $patron_email))->fetch();
			if ($user_info)
			{
				$their_groups = $user->post_group_list([$user_info['user_id']]);
				if (!in_array(6, $their_groups[$user_info['user_id']]))
				{
					echo 'Username: ' . $user_info['username'] . ' ' . $line[2] . ' | Pledge: '. $pledge .'<pre>';
					print_r($their_groups);
					echo '</pre>';

					if (!isset($_GET['testrun']))
					{					
						$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 6", [$user_info['user_id']]);
					}
					
					echo "\nGiven Supporter status\n\n";
				}

				if ($pledge_amount >= 700)
				{
					if (!in_array(9, $their_groups[$user_info['user_id']]))
					{
						if (!isset($_GET['test_run']))
						{
							$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 9", [$user_info['user_id']]);
						}
						
						echo "\nGiven Supporter Plus status\n\n";
					}
				}
			}
		}
    }
    // get the link to the next page of pledges
	if (!$pledges_response->has('links.next')) 
	{
        // if there's no next page, we're done!
        break;
    }
    $next_link = $pledges_response->get('links.next');
    // otherwise, parse out the cursor param
    $next_query_params = explode("?", $next_link)[1];
    parse_str($next_query_params, $parsed_next_query_params);
    $cursor = $parsed_next_query_params['page']['cursor'];
}

echo "Done!\n";

?>
