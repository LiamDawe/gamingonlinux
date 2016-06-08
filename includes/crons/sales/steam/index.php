<?php
// TODO
// ?cc=us for dollars, lets get pounds working first eh boyos!

echo "Steam Store importer started on " .date('d-m-Y H:m:s'). "\n";

// http://simplehtmldom.sourceforge.net/
include('simple_html_dom.php');

define('path', '/home/gamingonlinux/public_html/');
//define('path', '/mnt/storage/public_html/');
include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

$page = 1;
$stop = 0;
$titles = array();

$url = "http://store.steampowered.com/search/?specials=1&os=linux&page=";

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
        $title = $span->plaintext;
        $titles[] = $title;
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

        // need to check if we already have it to insert it
        // search if that title exists
        $db->sqlquery("SELECT `info` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 1", array($title));

        // if it does exist, make sure it's not from Steam already
        if ($db->num_rows() == 0)
        {
          $db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 1, `pounds` = ?, `pounds_original` = ?, `steam` = 1", array($title, $link, core::$date, $price_now, $original_price));

          $sale_id = $db->grab_id();

          echo "\tAdded this game to the sales DB with id: " . $sale_id . "<br />.\n";

          $games .= $title . '<br />';

          $email = 1;
        }

        // if we already have it, just update it
        else
        {
          $db->sqlquery("UPDATE `game_sales` SET `pounds` = ?, `pounds_original` = ? WHERE `provider_id` = 1 AND info = ?", array($price_now, $original_price, $title));

          echo "Updated {$title} with the latest information<br />";
        }
      }
    }
    $page++;
  }
} while ($stop == 0);

$db->sqlquery("SELECT `info` FROM `game_sales` WHERE `provider_id` = 1");
$in_db = $db->fetch_all_rows();

foreach ($in_db as $key => $in_database)
{
  if (!in_array($in_database['info'], $titles))
  {
    // delete old sales as we don't have expiry times (yet, maybe one day eh)
    $db->sqlquery("DELETE FROM `game_sales` WHERE provider_id = 1 AND `info` = ?", array($in_database['info']));

    echo 'Deleted: ' . $in_database['info'] . '<br />';
  }
}


echo "End of Steam Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
