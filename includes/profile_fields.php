<?php
global $profile_fields;
$profile_fields = array();

$profile_fields['steam']['name'] = 'Steam ID:';
$profile_fields['steam']['description'] = 'You need to have set a custom id/url on your Steam profile for this to work!';
$profile_fields['steam']['db_field'] = 'steam';
$profile_fields['steam']['span'] = '<span class="inline icon steam">Steam</span>';
$profile_fields['steam']['base_link'] = 'http://steamcommunity.com/id/';
$profile_fields['steam']['base_link_required'] = 1;
$profile_fields['steam']['link'] = 1;
$profile_fields['steam']['image'] = NULL;

$profile_fields['twitter']['name'] = 'Twitter:';
$profile_fields['twitter']['db_field'] = 'twitter_on_profile';
$profile_fields['twitter']['base_link'] = 'http://www.twitter.com/';
$profile_fields['twitter']['base_link_required'] = 1;
$profile_fields['twitter']['span'] = '<span class="inline icon twitter">Twitter</span>';
$profile_fields['twitter']['link'] = 1;
$profile_fields['twitter']['image'] = NULL;

$profile_fields['google']['name'] = 'G+';
$profile_fields['google']['db_field'] = 'google_plus';
$profile_fields['google']['base_link'] = 'https://plus.google.com/u/0/';
$profile_fields['google']['base_link_required'] = 1;
$profile_fields['google']['span'] = '<span class="inline icon google-plus">G+</span>';
$profile_fields['google']['link'] = 1;
$profile_fields['google']['image'] = NULL;

$profile_fields['website']['name'] = 'Website:';
$profile_fields['website']['db_field'] = 'website';
$profile_fields['website']['span'] = '<span class="inline icon website">Website</span>';
$profile_fields['website']['base_link'] = NULL;
$profile_fields['website']['base_link_required'] = 0;
$profile_fields['website']['link'] = 1;
$profile_fields['website']['image'] = NULL;

$profile_fields['youtube']['name'] = 'Youtube:';
$profile_fields['youtube']['db_field'] = 'youtube';
$profile_fields['youtube']['span'] = '<span class="inline icon youtube">YouTube</span>';
$profile_fields['youtube']['base_link'] = NULL;
$profile_fields['youtube']['base_link_required'] = 0;
$profile_fields['youtube']['link'] = 1;
$profile_fields['youtube']['image'] = NULL;

$profile_fields['facebook']['name'] = 'Facebook:';
$profile_fields['facebook']['db_field'] = 'facebook';
$profile_fields['facebook']['span'] = '<span class="inline icon facebook">facebook</span>';
$profile_fields['facebook']['base_link'] = NULL;
$profile_fields['facebook']['base_link_required'] = 0;
$profile_fields['facebook']['link'] = 1;
$profile_fields['facebook']['image'] = NULL;

$profile_fields['twitch']['name'] = 'Twitch:';
$profile_fields['twitch']['description'] = 'Enter your <strong>full</strong> twitch url!';
$profile_fields['twitch']['db_field'] = 'twitch';
$profile_fields['twitch']['span'] = '<span class="inline icon twitch">twitch</span>';
$profile_fields['twitch']['base_link'] = NULL;
$profile_fields['twitch']['base_link_required'] = 0;
$profile_fields['twitch']['link'] = 1;
$profile_fields['twitch']['image'] = NULL;

$profile_fields['distro']['name'] = 'Distro';
$profile_fields['distro']['db_field'] = 'distro';
$profile_fields['distro']['image'] = '/templates/default/images/distros/linux_icon.png';
$profile_fields['distro']['dropdown'] = 1;
$profile_fields['distro']['option'] = array();
$profile_fields['distro']['option'][] = 'Not Listed';
$profile_fields['distro']['option'][] = 'Arch';
$profile_fields['distro']['option'][] = 'Chakra';
$profile_fields['distro']['option'][] = 'Debian';
$profile_fields['distro']['option'][] = 'Elementary';
$profile_fields['distro']['option'][] = 'Fedora';
$profile_fields['distro']['option'][] = 'Gentoo';
$profile_fields['distro']['option'][] = 'Kubuntu';
$profile_fields['distro']['option'][] = 'Lubuntu';
$profile_fields['distro']['option'][] = 'Ubuntu';
$profile_fields['distro']['option'][] = 'Ubuntu-GNOME';
$profile_fields['distro']['option'][] = 'Ubuntu-MATE';
$profile_fields['distro']['option'][] = 'Mint';
$profile_fields['distro']['option'][] = 'OpenSUSE';
$profile_fields['distro']['option'][] = 'Manjaro';
$profile_fields['distro']['option'][] = 'Mageia';
$profile_fields['distro']['option'][] = 'Sabayon';
$profile_fields['distro']['option'][] = 'Slackware';
$profile_fields['distro']['option'][] = 'Solus';
$profile_fields['distro']['option'][] = 'Xubuntu';
$profile_fields['distro']['span'] = NULL;
$profile_fields['distro']['link'] = 0;
$profile_fields['distro']['base_link'] = '/templates/default/images/distros/';
$profile_fields['distro']['base_link_required'] = 1;
?>
