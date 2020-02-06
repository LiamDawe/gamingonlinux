<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: profile fields.');
}
$profile_fields = array();

$profile_fields['steam']['name'] = 'Steam ID:';
$profile_fields['steam']['description'] = 'You need to have set a custom id/url on your Steam profile for this to work!';
$profile_fields['steam']['db_field'] = 'steam';
$profile_fields['steam']['span'] = '<span class="inline icon steam">Steam</span>';
$profile_fields['steam']['base_link'] = 'https://steamcommunity.com/id/';
$profile_fields['steam']['base_link_required'] = 1;
$profile_fields['steam']['link'] = 1;
$profile_fields['steam']['preinput'] = 'https://steamcommunity.com/id/';

$profile_fields['twitter']['name'] = 'Twitter:';
$profile_fields['twitter']['description'] = 'Just your Twitter username!';
$profile_fields['twitter']['db_field'] = 'twitter_on_profile';
$profile_fields['twitter']['base_link'] = 'https://www.twitter.com/';
$profile_fields['twitter']['base_link_required'] = 1;
$profile_fields['twitter']['span'] = '<span class="inline icon twitter">Twitter</span>';
$profile_fields['twitter']['link'] = 1;
$profile_fields['twitter']['preinput'] = 'https://twitter.com/';

$profile_fields['website']['name'] = 'Website:';
$profile_fields['website']['db_field'] = 'website';
$profile_fields['website']['span'] = '<span class="inline icon website">Website</span>';
$profile_fields['website']['base_link'] = NULL;
$profile_fields['website']['base_link_required'] = 0;
$profile_fields['website']['link'] = 1;

$profile_fields['youtube']['name'] = 'Youtube:';
$profile_fields['youtube']['description'] = 'Enter your <strong>full</strong> Youtube channel, like this: https://www.youtube.com/gamingonlinux';
$profile_fields['youtube']['db_field'] = 'youtube';
$profile_fields['youtube']['span'] = '<span class="inline icon youtube">YouTube</span>';
$profile_fields['youtube']['base_link'] = NULL;
$profile_fields['youtube']['base_link_required'] = 0;
$profile_fields['youtube']['link'] = 1;

$profile_fields['facebook']['name'] = 'Facebook:';
$profile_fields['facebook']['db_field'] = 'facebook';
$profile_fields['facebook']['span'] = '<span class="inline icon facebook">facebook</span>';
$profile_fields['facebook']['base_link'] = NULL;
$profile_fields['facebook']['base_link_required'] = 0;
$profile_fields['facebook']['link'] = 1;

$profile_fields['twitch']['name'] = 'Twitch:';
$profile_fields['twitch']['description'] = 'Enter your <strong>full</strong> twitch channel, like this: https://www.twitch.tv/gamingonlinux (not your profile or anything else)';
$profile_fields['twitch']['db_field'] = 'twitch';
$profile_fields['twitch']['span'] = '<span class="inline icon twitch">twitch</span>';
$profile_fields['twitch']['base_link'] = NULL;
$profile_fields['twitch']['base_link_required'] = 0;
$profile_fields['twitch']['link'] = 1;

$profile_fields['mastodon']['name'] = 'Mastodon:';
$profile_fields['mastodon']['description'] = 'Enter your Mastodon profile, like this: https://mastodon.social/@gamingonlinux';
$profile_fields['mastodon']['db_field'] = 'mastodon';
$profile_fields['mastodon']['span'] = '<span class="inline icon mastodon">mastodon</span>';
$profile_fields['mastodon']['base_link'] = NULL;
$profile_fields['mastodon']['base_link_required'] = 0;
$profile_fields['mastodon']['link'] = 1;

$profile_fields['gog']['name'] = 'GOG:';
$profile_fields['gog']['description'] = 'Enter your GOG profile, like this: https://www.gog.com/u/username';
$profile_fields['gog']['db_field'] = 'gogprofile';
$profile_fields['gog']['span'] = '<span class="inline icon gog">gogprofile</span>';
$profile_fields['gog']['base_link'] = 'https://www.gog.com/u/';
$profile_fields['gog']['base_link_required'] = 1;
$profile_fields['gog']['link'] = 1;
$profile_fields['gog']['preinput'] = 'https://www.gog.com/u/';

return $profile_fields;
?>
