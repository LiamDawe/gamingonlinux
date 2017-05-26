<?php
// steam game importer, DLC only ordered by release date (for already released stuff)

// http://simplehtmldom.sourceforge.net/
include('simple_html_dom.php');

define("APP_ROOT", dirname ( dirname( dirname( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$stop = 0;
$games_added_list = '';

$page = 1;
$url = "http://store.steampowered.com/search/?sort_by=Released_DESC&category1=21&os=linux&page=";

do
{
  echo 'Moving onto page ' . $page . '<br />';
  $html = file_get_html($url . $page);

  $get_games = $html->find('div.responsive_search_name_combined');

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
            $dont_use_array = array("Soundtrack", "soundtrack", "Soundtracks", "Sound Track", "Wallpapers", " OST", "Artbook", " Walkthrough", "Season Pass");
            foreach ($dont_use_array as $checker)
            {
              // don't give us this junk
              if (strpos($title, $checker) !== false)
              {
                $dont_use = 1;
              }
            }

            if ($dont_use == 0)
            {
              echo 'Title: ' . $element->find('span.title', 0)->plaintext . '<br />';

              echo 'Release date: ' . $parsed_release_date . ' original ('.$release_date->plaintext.')' . '<br />';

              $link = $element->parent()->href;
              echo  'Link: ' . $link . '<br /><br />';

              $grab_info = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($title))->fetch();

              // if it does exist, make sure it's not from Steam already
              if (!$grab_info)
              {
                $dbl->run("INSERT INTO `calendar` SET `name` = ?, `steam_link` = ?, `date` = ?, `approved` = 1", array($title, $link, $parsed_release_date));

                $game_id = $dbl->new_id();

                echo "\tAdded this game to the calendar DB with id: " . $game_id . "<br />\n";

                $games_added_list .= $title . ' - Date: ' . $parsed_release_date . '<br /><br />';
              }

              // if we already have it, just update it
              else if (!empty($grab_info) && $grab_info['steam_link'] == NULL)
              {
                $dbl->run("UPDATE `calendar` SET `steam_link` = ?, `is_dlc` = 1 WHERE id = ?", array($link, $grab_info['id']));

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
  if ($core->config('send_emails') == 1)
  {
		$mail = new mailer($core);
		$mail->sendMail($core->config('contact_email'), 'The Steam calendar importer has added new games', 'New games added to the <a href="https://www.gamingonlinux.com/index.php?module=calendar">calendar</a> from Steam!<br />' . $games_added_list, "New games added to the https://www.gamingonlinux.com/index.php?module=calendar calendar from Steam!\r\n" . $games_added_list);
  }
}
