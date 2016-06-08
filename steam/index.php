<?php
include('simple_html_dom.php');

$page = 1;
$stop = 0;

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
      echo $element->href . '<br />';

      foreach($element->find('span.title') as $span)
      {
        echo $span->plaintext . '<br />';
      }

      foreach ($element->find('div.discounted') as $price)
      {
        $prices = trim($price->plaintext);
        $prices = explode(' ', $prices);

        echo 'Original price: ' . $prices[0] . '<br />';
        echo 'Price now: ' . $prices[1]  . '<br />';
      }
    }
    $page++;
  }
} while ($stop == 0);
