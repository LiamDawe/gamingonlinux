<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_POST))
{
    $text = $_POST['text'];
    $text = htmlspecialchars($text, ENT_QUOTES);
    $text = $bbcode->parse_bbcode($text);

    echo $text;
}
