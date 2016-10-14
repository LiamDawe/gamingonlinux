<?php
echo "Steam Store importer started on " .date('d-m-Y H:m:s'). "<br />\n";

// http://simplehtmldom.sourceforge.net/
include('simple_html_dom.php');

define('path', '/home/gamingonlinux/public_html/');
//define('path', '/mnt/storage/public_html/');
include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

include(path . 'includes/class_mail.php');

$stop = 0;
$games_added_list = '';

$page = 1;
$url = "http://store.steampowered.com/search/?os=linux&filter=comingsoon&category1=21%2C998&page=";

do
{
  echo 'Moving onto page ' . $page . '<br />';
  $html = file_get_html($url . $page);

  $get_games = $html->find('a.search_result_row');

  if (empty($get_games))
  {
    $stop = 1;
  }
  else
  {
    foreach($get_games as $element)
    {
        foreach ($element->find('div.search_released') as $release_date)
        {
          $trimmed_date = trim($release_date->plaintext);

          $remove_comma = str_replace(',', '', $trimmed_date);

          $parsed_release_date = strtotime($remove_comma);

          // so we can get rid of items that only have the year nice and simple
          $length = strlen($remove_comma);

          $parsed_release_date = date("Y-m-d", $parsed_release_date);

          $has_day = DateTime::createFromFormat('F Y', $remove_comma);

          if ($parsed_release_date != '1970-01-01' && $length != 4 && $has_day == FALSE)
          {
            $title = $element->find('span.title', 0)->plaintext;

            $title = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $title);

            $dont_use = 0;
            // don't give us soundtracks, they are DLC but we don't want them!
            if (strpos($title, 'Soundtrack') !== false)
            {
              $dont_use = 1;
            }
            if (strpos($title, 'Soundtracks') !== false)
            {
              $dont_use = 1;
            }
            if (strpos($title, 'Sound Track') !== false)
            {
              $dont_use = 1;
            }
            //include space to not end up finding games with "OST" in the name
            if (strpos($title, ' OST') !== false)
            {
              $dont_use = 1;
            }
            if ($dont_use == 0)
            {
              echo 'Title: ' . $element->find('span.title', 0)->plaintext . '<br />';

              echo 'Release date: ' . $parsed_release_date . ' original ('.$release_date->plaintext.')' . '<br />';

              $link = $element->href;
              echo  'Link: ' . $link . '<br /><br />';

              $db->sqlquery("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($title));

              $grab_info = $db->fetch();

              $check_rows = $db->num_rows();

              // if it does exist, make sure it's not from Steam already
              if ($check_rows == 0)
              {
                $db->sqlquery("INSERT INTO `calendar` SET `name` = ?, `steam_link` = ?, `date` = ?, `approved` = 1", array($title, $link, $parsed_release_date));

                $game_id = $db->grab_id();

                echo "\tAdded this game to the calendar DB with id: " . $game_id . "<br />\n";

                $games_added_list .= $title . ' - Date: ' . $parsed_release_date . '<br />';
              }

              // if we already have it, just update it
              else if ($check_rows == 1 && $grab_info['steam_link'] == NULL)
              {
                $db->sqlquery("UPDATE `calendar` SET `steam_link` = ? WHERE id = ?", array($link, $grab_info['id']));

                echo "Updated {$title} with the latest information<br />";
              }
            }
          }
        }
      }
      $page++;
    }
  } while ($stop == 0);

echo '<br />Last page hit: ' . $page . '<br /><br />';

if (!empty($games_added_list))
{
  if (core::config('send_emails') == 1)
  {
    $mail = new mail('liamdawe@gmail.com', 'The Steam calendar importer has added new games', 'New games added to the <a href="https://www.gamingonlinux.com/index.php?module=calendar">calendar</a> from Steam!<br />' . $games_added_list, '');
    $mail->send();
  }
}

echo "End of Steam Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
