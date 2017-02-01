<?php
$file_dir = dirname( dirname(__FILE__) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir. '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_mail.php');

$csv = array_map('str_getcsv', file('patreon.csv'));

array_splice($csv, 0, 2);
foreach ($csv as $line)
{
  // make it a proper decimal number to compare against
  $pledge = (float) $line[3];

  // if they pledge at least 5 dollars a month
  if ($pledge >= 5)
  {
    $db->sqlquery("SELECT `username`, `secondary_user_group` FROM `users` WHERE `email` = ?", array($line[2]));
    $count = $db->num_rows();
    // it didn't find an account, email them
    if ($count != 1)
    {
      if (core::config('send_emails') == 1)
      {
        $html_message = "Hello from Liam at <a href=\"https://www.gamingonlinux.com\">GamingOnLinux.com</a>! Thank you for supporting me on Patreon.<br />
        <br />
        I have tried to match your email up to a username, but I didn't find anything. <br />
        <br />
        <strong>Don't worry</strong>, if you already have your GOL Supporter badge you can ignore this email! <br />
        <br />
        If you haven't, please reply with your username or email attached to a GOL account. You're likely using a different email address on Patreon to what you use on GOL.<br />
        <br />
        Thank you.<br />
        <hr />
        Ps. Don't worry if you have never seen this before, this email was generated from a new script I wrote to help me automate Patreon stuff! It will only be sent to you once a month, just to confirm and so I don't miss anyone.";


        $mail = new mail($line[2], 'Thank you for supporting GamingOnLinux, more info may be needed', $html_message, '', 'Reply-To: ' . core::config('contact_email'));
        $mail->send();

        echo "Email sent to " . $line[2] . '<br />';
      }
    }
    // it found an account, give them their badge
    else if ($count == 1)
    {
      $result = $db->fetch();
      if ($result['secondary_user_group'] != 6)
      {
        $db->sqlquery("UPDATE `users` SET `secondary_user_group` = 6 WHERE `email` = ?", array($line[2]));
        echo 'User ' . $result['username'] . ' ' . $line[2] . ' given GOL Supporter status.<br />';
      }
    }
  }
}
?>
