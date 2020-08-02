<?php
$templating->set_previous('title', 'Patreon Account Linking' . $templating->get('title', 1)  , 1);

$templating->load('usercp_modules/patreon');
$templating->block('main');

// let's get their current status first
$get_groups = $user->get_user_groups();

if (in_array(6, $get_groups))
{
	$templating->block('existing_supporter');
}

require_once(APP_ROOT . '/includes/patreon/API.php');
require_once(APP_ROOT . '/includes/patreon/OAuth.php');
 
use Patreon\API;
use Patreon\OAuth;

$client_id = $core->config('patreon_client_id');
$client_secret = $core->config('patreon_client_secret');

// Set the redirect url where the user will land after oAuth. That url is where the access code will be sent as a _GET parameter. This may be any url in your app that you can accept and process the access code and login

// In this case, say, /patreon_login request uri

$redirect_uri = "https://www.gamingonlinux.com/usercp.php?module=patreon";

// Generate the oAuth url

$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' 
. $client_id . '&redirect_uri=' . urlencode($redirect_uri);

// You can send an array of vars to Patreon and receive them back as they are. Ie, state vars to set the user state, app state or any other info which should be sent back and forth. 

// for example lets set final page which the user needs to land at - this may be a content the user is unlocking via oauth, or a welcome/thank you page

// Lets make it a thank you page

$state = array();

$state['final_page'] = 'http://mydomain.com/thank_you';

// Add any number of vars you need to this array by $state['key'] = variable value

// Prepare state var. It must be json_encoded, base64_encoded and url encoded to be safe in regard to any odd chars
$state_parameters = '&state=' . urlencode( base64_encode( json_encode( $state ) ) );

// Append it to the url 

$href .= $state_parameters;

// Now place the url into a login link. Below is a very simple login link with just text. in assets/images folder, there is a button image made with official Patreon assets (login_with_patreon.php). You can also use this image as the inner html of the <a> tag instead of the text provided here

// Scopes! You must request the scopes you need to have the access token.
// In this case, we are requesting the user's identity (basic user info), user's email
// For example, if you do not request email scope while logging the user in, later you wont be able to get user's email via /identity endpoint when fetching the user details
// You can only have access to data identified with the scopes you asked. Read more at https://docs.patreon.com/#scopes

// Lets request identity of the user, and email.

$scope_parameters = '&scope=identity%20identity'.urlencode('[email]');

$href .= $scope_parameters;

// Simply echoing it here. You can present the login link/button in any other way.

$templating->block("login");
$templating->set('url', $href);

// The below code snippet needs to be active wherever the the user is landing in $redirect_uri parameter above. It will grab the auth code from Patreon and get the tokens via the oAuth client

if ( isset($_GET['code']) && $_GET['code'] != '' ) 
{
	$oauth_client = new OAuth($client_id, $client_secret);	
		
	$tokens = $oauth_client->get_tokens($_GET['code'], $redirect_uri);
	$access_token = $tokens['access_token'];
	$refresh_token = $tokens['refresh_token'];
	
	// Here, you should save the access and refresh tokens for this user somewhere. Conceptually this is the point either you link an existing user of your app with his/her Patreon account, or, if the user is a new user, create an account for him or her in your app, log him/her in, and then link this new account with the Patreon account. More or less a social login logic applies here. 

	// After linking an existing account or a new account with Patreon by saving and matching the tokens for a given user, you can then read the access token (from the database or whatever resource), and then just check if the user is logged into Patreon by using below code. Code from down below can be placed wherever in your app, it doesnt need to be in the redirect_uri at which the Patreon user ends after oAuth. You just need the $access_token for the current user and thats it.

	// Lets say you read $access_token for current user via db resource, or you just acquired it through oAuth earlier like the above - create a new API client

	$api_client = new API($access_token);

	// Return from the API can be received in either array, object or JSON formats by setting the return format. It defaults to array if not specifically set. Specifically setting return format is not necessary. Below is shown as an example of having the return parsed as an object. If there is anyone using Art4 JSON parser lib or any other parser, they can just set the API return to JSON and then have the return parsed by that parser

	// Now get the current user:
	$patron_response = $api_client->fetch_user();

	$templating->block('response');
	if (isset($patron_response['included']))
	{
		$last_charge_month = date('m', strtotime($patron_response['included'][0]['attributes']['last_charge_date']));
		$joined_date = date('m-Y', strtotime($patron_response['included'][0]['attributes']['pledge_relationship_start']));
		if ($patron_response['data']['attributes']['last_charge_status'] = 'Paid' && $last_charge_month == date('m'))
		{
			$their_groups = $user->post_group_list([$_SESSION['user_id']]);
				
			// now check how much they've paid and sort the correct user groups
			$pledge = $patron_response['included'][0]['attributes']['currently_entitled_amount_cents'];

			$return_text = NULL;

			if ($pledge < 400)
			{
				$return_text .= "Sorry but to be given Supporter status you need to have paid the correct amount (at least $4 on Patreon).";
			}

			if ($pledge >= 400)
			{
				// check if we need to update their profile information
				$check_profile = $dbl->run("SELECT `supporter_email`, `supporter_type` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

				$sql_text = array();
				$sql_data = array(); 
				if ($check_profile['supporter_email'] == NULL || $check_profile['supporter_email'] == '')
				{
					// email is verified, let's act on it
					if ($patron_response['data']['attributes']['is_email_verified'] == 1)
					{
						$sql_text[] = "`supporter_email` = ?";
						$sql_data[] = $patron_response['data']['attributes']['email'];
					}
					else
					{
						$return_text .= 'Note: It seems your Patreon email is not verified. We suggest you <a href="https://www.patreon.com/">head on over to Patreon</a> to take a look.<br />';
					}
				}

				if ($check_profile['supporter_type'] == NULL || $check_profile['supporter_type'] != 'patreon')
				{
					$sql_text[] = "`supporter_type` = 'patreon'";
				}

				if (!empty($sql_text) || !empty($sql_data))
				{
					// set their status
					$dbl->run("UPDATE `users` SET ".implode(",", $sql_text)." WHERE `user_id` = ?", array_merge($sql_data, [$_SESSION['user_id']]));
				}

				// they're not currently set as a supporter, give them the status
				if (!in_array(6, $their_groups[$_SESSION['user_id']]))
				{
					$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 6", [$_SESSION['user_id']]);

					$return_text .= "Given Supporter status!";
				}
				if ($pledge <= 600)
				{
					// they don't pledge enough for supporter plus, if they're currently in it then remove them
					if (in_array(9, $their_groups[$_SESSION['user_id']]))
					{
						$dbl->run("DELETE FROM `user_group_membership` WHERE `user_id` = ? AND `group_id` = 9", [$_SESSION['user_id']]);
					}
				}
			}
			// they pledge enough to be given the Supporter Plus group
			if ($pledge >= 700)
			{
				if (!in_array(9, $their_groups[$_SESSION['user_id']]))
				{
					$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 9", [$_SESSION['user_id']]);

					$return_text .= " Also given Supporter Plus status!";
				}
			}

			if ($return_text == NULL || $return_text == '')
			{
				// generic message when we didnt need to do anything but it still all worked
				$return_text .= "You're all up to date!";
			}

			$templating->set('text', $return_text);
		}
		else if ($patron_response['data']['attributes']['last_charge_status'] != 'Paid')
		{
			$new_patron = '';
			if ($joined_date == date('m-Y'))
			{
				$new_patron = '<p>Our records show you started pledging on <u>' . $joined_date . '</u> - Payments are taken <strong>once a month</strong>, at the start of each month so your pledge is due next month.</p>';
			}
			$templating->set('text', 'Sorry but it seems your last payment has not been paid. We suggest you <a href="https://www.patreon.com/">head on over to Patreon</a> to take a look. We don\'t handle the payments directly, so you need to fix it there. Thank you!' . $new_patron);
		}
	}
	else
	{
		$templating->set('text', 'Sorry but we were unable to get your data from Patreon. This might be a temporary issue and you can try later. Also, please check your most recent payment went though. We suggest you <a href="https://www.patreon.com/">head on over to Patreon</a> to take a look. We don\'t handle the payments directly, so you need to fix it there. Thank you! If you have paid and it\'s still not working, let us know in <a href="https://www.gamingonlinux.com/forum/14">our Forum</a>.' . $new_patron);
	}
}