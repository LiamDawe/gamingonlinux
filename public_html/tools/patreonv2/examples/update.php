<?php

require_once('../src/API.php');
require_once('../src/OAuth.php');

use Patreon\API;
use Patreon\OAuth;

// This example shows you how to create a webhook at Patreon API to notify you when you have any member changes in your campaign

// Create a client first, using your creator's access token
$api_client = new API('_jYIhwvvGTijPg-1rhOoW59mXsPszMLzG9LjoLoF6HQ');

// If you dont know the campaign id you are targeting already, fetch your campaigns and get the id for the campaign you need. If you already know your campaign id, just skip this part

$campaigns_response = $api_client->fetch_campaigns();

//print_r($campaigns_response);

// Get the campaign id
$campaign_id = $campaigns_response['data'][0]['id'];

//$details = $api_client->fetch_campaign_details($campaign_id);

$url = "campaigns/{$campaign_id}/members?include=currently_entitled_tiers,address&fields".urlencode("[member]")."=full_name,is_follower,last_charge_date,last_charge_status,lifetime_support_cents,currently_entitled_amount_cents,patron_status&fields".urlencode("[tier]")."=amount_cents,created_at,description,discord_role_ids,edited_at,patron_count,published,published_at,requires_shipping,title";
		
$details = $api_client->get_data($url);

echo '<pre>';
print_r($details);
echo '</pre>';

do {
	$paging = '&page%5Bcursor%5D=' . $details['meta']['pagination']['cursors']['next'];

	$url = "campaigns/{$campaign_id}/members?include=currently_entitled_tiers,address&fields".urlencode("[member]")."=full_name,is_follower,last_charge_date,last_charge_status,lifetime_support_cents,currently_entitled_amount_cents,patron_status&fields".urlencode("[tier]")."=amount_cents,created_at,description,discord_role_ids,edited_at,patron_count,published,published_at,requires_shipping,title" . $paging;
		
	$details = $api_client->get_data($url);

	foreach ($details as $key => $info)
	{
		if ($info['patron_status'] == 'active_patron')
		{
			
		}
		echo $info['full_name'];
	}
	
	echo '<pre>';
	print_r($details);
	echo '</pre>';
} while (isset($details['meta']['pagination']['cursors']['next']) && !empty($details['meta']['pagination']['cursors']['next']));