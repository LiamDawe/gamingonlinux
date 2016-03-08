<?php
$templating->set_previous('title', ' - Friend Links', 1);
$templating->set_previous('meta_description', 'Friends links for gamingonlinux.com', 1);

require_once("includes/ayah/ayah.php");
$ayah = new AYAH();

$templating->merge('friend_links');
$templating->block('top');
?>
