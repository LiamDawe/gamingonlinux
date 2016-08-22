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

$stop = 0;
$new_games = array();

$page = 1;
$url = "http://store.steampowered.com/search/?os=linux&filter=comingsoon&page=";

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

          // thanks gnarface from IRC, check if they only give us month and year
          preg_match('/^([[:alpha:]]+)?\W*(\d{1,2})?\W*(\d{4})$/', $remove_comma, $matches);

          if ($parsed_release_date != '1970-01-01' && $length != 4 && empty($matches))
          {
            foreach($element->find('span.title') as $span)
            {
              $title = trim($span->plaintext);
              echo 'Title: ' . $title . '<br />';
            }

            echo 'Release date: ' . $parsed_release_date . ' original ('.$release_date->plaintext.')' . '<br />';

            $link = $element->href;
            echo  'Link: ' . $link . '<br />';

            $db->sqlquery("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($title));

            // if it does exist, make sure it's not from Steam already
            if ($db->num_rows() == 0)
            {
              $db->sqlquery("INSERT INTO `calendar` SET `name` = ?, `steam_link` = ?, `date` = ?, `approved` = 1", array($title, $link, $parsed_release_date));

              $sale_id = $db->grab_id();

              echo "\tAdded this game to the calendar DB with id: " . $sale_id . "<br />\n";

              $games .= $title . '<br />';

              $email = 1;
            }

            // if we already have it, just update it
            else
            {
              $current_game = $db->fetch();
              $db->sqlquery("UPDATE `calendar` SET `steam_link` = ? WHERE id = ?", array($link, $current_game['id']));

              echo "Updated {$title} with the latest information<br />";
            }
          }
        }
        echo '<br />';
      }
      $page++;
    }
  } while ($stop == 0);

echo '<br />Last page hit: ' . $page . '<br /><br />';

echo "End of Steam Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
