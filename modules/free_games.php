<?php
$game_sales = new game_sales($dbl, $templating, $user, $core);

$templating->set_previous('title', 'Free Linux games', 1);
$templating->set_previous('meta_description', 'Free Linux games', 1);

$templating->load('free_games');

$total_free = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `free_game` = 1 AND `approved` = 1")->fetchOne();

$templating->block('head', 'free_games');
$templating->set('total', $total_free);

$game_sales->display_free();