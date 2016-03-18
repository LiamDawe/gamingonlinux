<?php
function telegram($link)
{
    $botToken = core::config('telegram_bot_key');
    $chat_id = "@linuxgaming";
    $message = $link;
    $bot_url    = "https://api.telegram.org/bot$botToken/";
    $url = $bot_url."sendMessage?chat_id=".$chat_id."&text=".urlencode($message);
    file_get_contents($url);
}
?>
