<?php
$templating->load('database');

$templating->block('main');

if (!isset($_GET['cat']))
{
  $db->sqlquery("SELECT `name` FROM `game_list` WHERE `accepted` = 1");
  while ($games = $db->fetch())
  {
    $templating->block('game-row');
    $templating->set('name', $games['name']);
  }
}
