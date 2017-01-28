<?php
$file_dir = dirname(__FILE__);

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir. '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

if (isset($_GET['id']) && is_numeric($_GET['id']))
{
  $db->sqlquery("SELECT `pc_info_public` FROM `users` WHERE `user_id` = ?", array($_GET['id']));
  $public = $db->fetch();

  if ($public['pc_info_public'] == 1)
  {
    $db->sqlquery("SELECT u.`distro`, u.`username`, i.`desktop_environment`, i.`gpu_vendor`, i.`gpu_model`, i.`cpu_vendor`, i.`cpu_model` FROM `users` u LEFT JOIN `user_profile_info` i ON u.user_id = i.user_id WHERE u.`user_id` = ?", array($_GET['id']));
    $user_info = $db->fetch();

    $username = $user_info['username'];

    $desktop_text = '';
    if (!empty($user_info['distro']))
    {
      $desktop_text = $user_info['distro'];
      if (!empty($user_info['desktop_environment']))
      {
        $desktop_text .= ' : ' . $user_info['desktop_environment'];
      }
    }

    $gpu = '';
    if (!empty($user_info['gpu_vendor']))
    {
      $gpu = $user_info['gpu_vendor'];
      if (!empty($user_info['gpu_model']))
      {
        $gpu .= ' : ' . $user_info['gpu_model'];
      }
    }

    $cpu = '';
    if (!empty($user_info['cpu_vendor']))
    {
      $cpu = $user_info['cpu_vendor'];
      if (!empty($user_info['cpu_model']))
      {
        $cpu .= ' : ' . $user_info['cpu_model'];
      }
    }

    $base_image = imagecreatefrompng($file_dir . '/templates/default/images/signature.png');

    $distro_image_picker = 'linux_icon';
    if (!empty($user_info['distro']))
    {
      $distro_image_picker = $user_info['distro'];
    }

    $distro = @imagecreatefrompng($file_dir . '/templates/default/images/distros/' . $distro_image_picker . '.png');

    if (!$distro)
    {
      // if it didn't work, they might have somehow picked a distro image we don't have, so force the standard Linux "tux" image
      $distro = @imagecreatefrompng($file_dir . '/templates/default/images/distros/linux_icon.png');
    }

    // only do this if it actually worked
    if ($distro)
    {
      imagecopy($base_image, $distro, 5, (imagesy($base_image)/2)-(imagesy($distro)/2)+5, 0, 0, imagesx($distro), imagesy($distro));
    }

    $text_colour = imagecolorallocate($base_image, 0, 0, 0);
    $width = imagesx($base_image);
    $height = imagesy($base_image);
    putenv('GDFONTPATH=' . realpath('.'));
    $font = 'Ubuntu-L.ttf';

    imagettftext($base_image, 11, 0, 257, 14, $text_colour, $font, "GamingOnLinux.com");

    imagettftext($base_image, 11, 0, 3, 14, $text_colour, $font, $username);
    if (!empty($desktop_text))
    {
      imagettftext($base_image, 11, 0, 45, 30, $text_colour, $font, $desktop_text);
    }
    if (!empty($gpu))
    {
      imagettftext($base_image, 11, 0, 45, 45, $text_colour, $font, $gpu);
    }
    if (!empty($cpu))
    {
      imagettftext($base_image, 11, 0, 45, 60, $text_colour, $font, $cpu);
    }

    header('Content-Type: image/png');
    imagepng($base_image);
  }
  else
  {
    $base_image = imagecreatefrompng($file_dir . '/templates/default/images/signature.png');
    $distro = @imagecreatefrompng($file_dir . '/templates/default/images/distros/linux_icon.png');
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
  $base_image = imagecreatefrompng($file_dir . '/templates/default/images/signature.png');
  $distro = @imagecreatefrompng($file_dir . '/templates/default/images/distros/linux_icon.png');
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
