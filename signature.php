<?php
include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

$db->sqlquery("SELECT `pc_info_public` FROM `users` WHERE `user_id` = ?", array($_GET['id']));
$public = $db->fetch();

if ($public['pc_info_public'] == 1)
{
  $db->sqlquery("SELECT u.`distro`, u.`username`, i.`desktop_environment`, i.`what_bits`, i.`gpu_vendor`, i.`gpu_model` FROM `users` u LEFT JOIN `user_profile_info` i ON u.user_id = i.user_id WHERE u.`user_id` = ?", array($_GET['id']));
  $user_info = $db->fetch();

  $base_image = imagecreatefrompng('templates/default/images/signature.png');

  $distro_image_picker = 'linux_icon';
  if (!empty($user_info['distro']))
  {
    $distro_image_picker = $user_info['distro'];
  }

  $distro = @imagecreatefrompng('templates/default/images/distros/' . $distro_image_picker . '.png');

  if (!$distro)
  {
    die('Couldn\'t find your distro image');
  }

  imagecopy($base_image, $distro, (imagesx($base_image)/2)-(imagesx($distro)/2), (imagesy($base_image)/2)-(imagesy($distro)/2), 0, 0, imagesx($distro), imagesy($distro));

  $text_colour = imagecolorallocate($base_image, 0, 0, 0);
  $width = imagesx($base_image);
  $height = imagesy($base_image);
  $font = 4;

  $username = $user_info['username'];

  $desktop_text = '';
  if (!empty($user_info['distro']))
  {
    $desktop_text = $user_info['distro'];
    if (!empty($user_info['desktop_environment']))
    {
      $desktop_text .= ':' . $user_info['desktop_environment'];
    }

    if (!empty($user_info['what_bits']))
    {
      $desktop_text .= ':' . $user_info['what_bits'];
    }
  }

  $gpu = '';
  if (!empty($user_info['gpu_vendor']))
  {
    $gpu = $user_info['gpu_vendor'];
    if (!empty($user_info['gpu_model']))
    {
      $gpu .= ':' . $user_info['gpu_model'];
    }
  }

  // calculate the left position of the text:
  //$leftTextPos = ( $width - imagefontwidth($font)*strlen($text) )/2;
  // finally, write the string:
  imagestring($base_image, $font, 1, $height-70, $username, $text_colour);
  imagestring($base_image, $font, 1, $height-45, $desktop_text, $text_colour);
  imagestring($base_image, $font, 1, $height-20, $gpu, $text_colour);
  imagestring($base_image, $font, 260, $height-20, "GamingOnLinux.com", $text_colour);

  header('Content-Type: image/png');
  imagepng($base_image);
}
?>
