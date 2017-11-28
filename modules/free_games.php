<?php
$game_sales = new game_sales($dbl, $templating, $user, $core);

$templating->set_previous('title', 'Free Linux games', 1);
$templating->set_previous('meta_description', 'Free Linux games', 1);

$templating->load('free_games');

$templating->block('head', 'free_games');

$game_sales->display_free();