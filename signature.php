<?php
header('Content-Type: image/png');

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

  $base = imagecreatefrompng('templates/default/images/signature.png');

  $distro_image = 'linux_icon';
  if (!empty($user_info['distro']))
  {
    $distro_image = $user_info['distro'];
  }
  $distro = imagecreatefrompng('templates/default/images/distros/' . $user_info['distro'] . '.png');

  imagecopy($base, $distro, (imagesx($base)/2)-(imagesx($distro)/2), (imagesy($base)/2)-(imagesy($distro)/2), 0, 0, imagesx($distro), imagesy($distro));

  $white_colour = imagecolorallocate($base, 255, 255, 255);
  $width = imagesx($base);
  $height = imagesy($base);
  $font = 4;

  $username = $user_info['username'];

  $distro = '';
  if (!empty($user_info['distro']))
  {
    $distro = $user_info['distro'];
    if (!empty($user_info['desktop_environment']))
    {
      $distro .= ':' . $user_info['desktop_environment'];
    }

    if (!empty($user_info['what_bits']))
    {
      $distro .= ':' . $user_info['what_bits'];
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
  imagestring($base, $font, 1, $height-60, $username, $white_colour);
  imagestring($base, $font, 1, $height-45, $distro, $white_colour);
  imagestring($base, $font, 1, $height-30, $gpu, $white_colour);

  imagepng($base);

/*
  $img = imagecreatefrompng('templates/default/images/distros/' . $user_info['distro'] . '.png');

  $white_colour = imagecolorallocate($img, 255, 255, 255);
  $black = imagecolorallocate($im, 0, 0, 0);

  $width = imagesx($img);
  $height = imagesy($img);

  // now we want to write in the centre of the rectangle:
  $font = 4; // store the int ID of the system font we're using in $font
  $text = $user_info['username']; // store the text we're going to write in $text
  // calculate the left position of the text:
  $leftTextPos = ( $width - imagefontwidth($font)*strlen($text) )/2;
  // finally, write the string:
  imagestring($img, $font, $leftTextPos, $height-18, $text, $white_colour);

  // draw a black rectangle across the bottom, say, 20 pixels of the image:
  imagefilledrectangle($img, 0, ($height+200) , $width, $height, $black);

  imagepng($img);*/
}
?>
