<?php
include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

if (isset($_GET['id']) && is_numeric($_GET['id']))
{
  $db->sqlquery("SELECT `pc_info_public` FROM `users` WHERE `user_id` = ?", array($_GET['id']));
  $public = $db->fetch();

  if ($public['pc_info_public'] == 1)
  {
    $db->sqlquery("SELECT u.`distro`, u.`username`, i.`desktop_environment`, i.`gpu_vendor`, i.`gpu_model`, i.`cpu_vendor`, i.`cpu_model` FROM `users` u LEFT JOIN `user_profile_info` i ON u.user_id = i.user_id WHERE u.`user_id` = ?", array($_GET['id']));
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
      // if it didn't work, they might have somehow picked a distro image we don't have, so force the standard Linux "tux" image
      $distro = @imagecreatefrompng('templates/default/images/distros/linux_icon.png');
    }

    // only do this if it actually worked
    if ($distro)
    {
      imagecopy($base_image, $distro, (imagesx($base_image)/2)-(imagesx($distro)/2), (imagesy($base_image)/2)-(imagesy($distro)/2), 0, 0, imagesx($distro), imagesy($distro));
    }

    $text_colour = imagecolorallocate($base_image, 0, 0, 0);
    $width = imagesx($base_image);
    $height = imagesy($base_image);
    $font = 4;

    $username = $user_info['username'];

    $desktop_text = '';
    if (!empty($user_info['distro']))
    {
      $desktop_text = $user_info['distro'];
    }

    $environment = '';
    if (!empty($user_info['desktop_environment']))
    {
      $environment .= $user_info['desktop_environment'];
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

    $cpu = '';
    if (!empty($user_info['cpu_vendor']))
    {
      $cpu = $user_info['cpu_vendor'];
      if (!empty($user_info['cpu_model']))
      {
        $cpu .= ':' . $user_info['cpu_model'];
      }
    }

    // calculate the left position of the text:
    $leftTextPos = ( $width - imagefontwidth($font)*strlen("Linux Gamer!") );
    imagestring($base_image, $font, $leftTextPos, $height-70, "Linux Gamer!", $text_colour);
    imagestring($base_image, $font, 2, $height-70, $username, $text_colour);
    if (!empty($desktop_text))
    {
      imagestring($base_image, $font, 2, $height-50, $desktop_text, $text_colour);
    }
    if (!empty($environment))
    {
      imagestring($base_image, $font, 2, $height-35, $environment, $text_colour);
    }
    if (!empty($gpu))
    {
      $leftTextPos = ( $width - imagefontwidth($font)*strlen($gpu) );
      imagestring($base_image, $font, $leftTextPos, $height-35, $gpu, $text_colour);
    }
    if (!empty($cpu))
    {
      $leftTextPos = ( $width - imagefontwidth($font)*strlen($cpu) );
      imagestring($base_image, $font, $leftTextPos, $height-50, $cpu, $text_colour);
    }
    $leftTextPos = ( $width - imagefontwidth($font)*strlen("GamingOnLinux.com") );
    imagestring($base_image, $font, $leftTextPos-130, $height-18, "GamingOnLinux.com", $text_colour);

    header('Content-Type: image/png');
    imagepng($base_image);
  }
  else
  {
    $base_image = imagecreatefrompng('templates/default/images/signature.png');
    $distro = @imagecreatefrompng('templates/default/images/distros/linux_icon.png');
    imagecopy($base_image, $distro, (imagesx($base_image)/2)-(imagesx($distro)/2), (imagesy($base_image)/2)-(imagesy($distro)/2), 0, 0, imagesx($distro), imagesy($distro));
    $text_colour = imagecolorallocate($base_image, 0, 0, 0);
    $height = imagesy($base_image);
    $font = 4;
    imagestring($base_image, $font, 1, $height-70, "ERROR: That users PC info is not public!", $text_colour);
    imagestring($base_image, $font, 260, $height-20, "GamingOnLinux.com", $text_colour);
    header('Content-Type: image/png');
    imagepng($base_image);
  }
}
else
{
  $base_image = imagecreatefrompng('templates/default/images/signature.png');
  $distro = @imagecreatefrompng('templates/default/images/distros/linux_icon.png');
  imagecopy($base_image, $distro, (imagesx($base_image)/2)-(imagesx($distro)/2), (imagesy($base_image)/2)-(imagesy($distro)/2), 0, 0, imagesx($distro), imagesy($distro));
  $text_colour = imagecolorallocate($base_image, 0, 0, 0);
  $height = imagesy($base_image);
  $font = 4;
  imagestring($base_image, $font, 1, $height-70, "ERROR: No User ID set", $text_colour);
  imagestring($base_image, $font, 260, $height-20, "GamingOnLinux.com", $text_colour);
  header('Content-Type: image/png');
  imagepng($base_image);
}
?>
