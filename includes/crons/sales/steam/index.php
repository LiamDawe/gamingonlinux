<?php
// TODO
// ?cc=us for dollars, lets get pounds working first eh boyos!

// With thanks to http://steamsales.rhekua.com/, they explain directly on their website how they do it

echo "Steam Store importer started on " .date('d-m-Y H:m:s'). "\n";

// http://simplehtmldom.sourceforge.net/
include('simple_html_dom.php');

//define('path', '/home/gamingonlinux/public_html/');
define('path', '/mnt/storage/public_html/');
include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

$page = 1;
$stop = 0;
$new_games = array();

$url = "http://store.steampowered.com/search/?specials=1&os=linux&cc=GB&page=";

do
{
  $html = file_get_html($url . $page);

  $get_sales = $html->find('a.search_result_row');

  if (empty($get_sales))
  {
    $stop = 1;
  }
  else
  {
    foreach($get_sales as $element)
    {
      $link = $element->href;
      echo $element->href . '<br />';

      foreach($element->find('span.title') as $span)
      {
        $title = trim($span->plaintext);
        echo $span->plaintext . '<br />';
      }

      foreach ($element->find('div.discounted') as $price)
      {
        $prices = trim($price->plaintext);
        $prices = explode(' ', $prices);

        // wtf?
        $original_price = substr($prices[0], 2);
        $price_now = substr($prices[1], 4);

        echo 'Original price: ' . $original_price . '<br />';
        echo 'Price now: ' . $price_now  . '<br />';

        // ADD IT TO THE GAMES DATABASE
        $db->sqlquery("SELECT `local_id`, `name` FROM `game_list` WHERE `name` = ?", array($title));
        if ($db->num_rows() == 0)
        {
          $db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($title));

          // okay now get the info for it to use later
          $db->sqlquery("SELECT `local_id`, `name` FROM `game_list` WHERE `name` = ?", array($title));
          $game_list = $db->fetch();
        }
        // it exists, so get the info for it to use later
        else
        {
          $game_list = $db->fetch();
        }
        $new_games[] = $game_list['local_id'];

        // need to check if we already have it to insert it
        // search if that title exists
        $db->sqlquery("SELECT `id` FROM `game_sales` WHERE `list_id` = ? AND `provider_id` = 1", array($game_list['local_id']));

        // if it does exist, make sure it's not from Steam already
        if ($db->num_rows() == 0)
        {
          $db->sqlquery("INSERT INTO `game_sales` SET `list_id` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 1, `pounds` = ?, `pounds_original` = ?, `steam` = 1", array($game_list['local_id'], $link, core::$date, $price_now, $original_price));

          $sale_id = $db->grab_id();

          echo "\tAdded this game to the sales DB with id: " . $sale_id . "<br />.\n";

          $games .= $title . '<br />';

          $email = 1;
        }

        // if we already have it, just update it
        else
        {
          $current_sale = $db->fetch();
          $db->sqlquery("UPDATE `game_sales` SET `pounds` = ?, `pounds_original` = ? WHERE `provider_id` = 1 AND id = ?", array($price_now, $original_price, $current_sale['id']));

          echo "Updated {$title} with the latest information<br />";
        }
      }
    }
    $page++;
  }
} while ($stop == 0);

$db->sqlquery("SELECT s.`list_id`, l.`name` FROM `game_sales` s INNER JOIN `game_list` l ON l.local_id = s.list_id WHERE s.`provider_id` = 1");
$in_db = $db->fetch_all_rows();

foreach ($in_db as $key => $in_database)
{
  if (!in_array($in_database['list_id'], $new_games))
  {
    // delete old sales as we don't have expiry times (yet, maybe one day eh)
    $db->sqlquery("DELETE FROM `game_sales` WHERE provider_id = 1 AND `list_id` = ?", array($in_database['list_id']));

    echo 'Deleted: ' . $in_database['name'] . '<br />';
  }
}

echo "End of Steam Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
