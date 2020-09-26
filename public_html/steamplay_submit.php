<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

if (!isset($_GET['steam_id']) || (isset($_GET['steam_id']) && !is_numeric($_GET['steam_id'])))
{
    $_SESSION['message'] = 'no_id';
    $_SESSION['message_extra'] = 'steam app';
    header("Location: /steamplay/");
    die();    
}

$steam_id = (int) $_GET['steam_id'];

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0))
{
    $_SESSION['message'] = 'notloggedin';
    header("Location: /steamplay/reports/".$steam_id);
    die();
}

// TWITCH ONLINE INDICATOR
if (!isset($_COOKIE['gol_announce_gol_twitch'])) // if they haven't dissmissed it
{
	$templating->load('twitch_bar');
	$templating->block('main', 'twitch_bar');
}

$app_info = $dbl->run("SELECT `name` FROM `calendar` WHERE `steam_id` = ?", array($steam_id))->fetch();

if (!$app_info['name'])
{
	$_SESSION['message'] = 'none_found';
	$_SESSION['message_extra'] = 'GOL APPs with that Steam ID';
	header('Location: /steamplay/');
	die();
}

$templating->set_previous('title', 'Submitting a Steam Play Proton report for ' . $app_info['name'], 1);
$templating->set_previous('meta_description', 'Submitting a Steam Play Proton report for ' . $app_info['name'], 1);

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message('steamplay', $_SESSION['message'], $extra);
}

$templating->load('steamplay_submit');
$templating->block('top');
$templating->set('name', $app_info['name']);
$templating->set('steam_id', $steam_id);

// there was an error on submission, populate fields with previous data
$error = 0;
if (!isset($message_map::$error) || (isset($message_map::$error) && $message_map::$error == 0))
{
    unset($_SESSION['item_post_data']); 
}
else if (isset($message_map::$error) && $message_map::$error >= 1)
{
    $error = 1;
}

$proton_versions = $dbl->run("SELECT `id`, `version` FROM `proton_versions` ORDER BY `release_date` DESC")->fetch_all();
$proton_versions_plain = array(); // for checking output to DB
$versions_output = '';
foreach ($proton_versions as $version)
{
	$proton_versions_plain[] = $version['id'];
    $selected = '';
    if ($error == 1 && isset($_SESSION['item_post_data']['proton_version']) && $_SESSION['item_post_data']['proton_version'] == $version['id'])
    {
        $selected = 'selected';
    }
    $versions_output .= '<option value="'.$version['id'].'" '.$selected.'>'.$version['version'].'</option>';
}
$templating->set('proton_versions', $versions_output);

$time_played_array = array('< 1 hour', '1-3 hours', '3-5 hours', '5+ hours');
$time_options = '';
foreach ($time_played_array as $time_played)
{
	$selected = '';
    if ($error == 1 && isset($_SESSION['item_post_data']['time_played']) && $_SESSION['item_post_data']['time_played'] == $time_played)
    {
        $selected = 'selected';
    }
	$time_options .= '<option value="'.$time_played.'" '.$selected.'>'.$time_played.'</option>';
}
$templating->set('time_options', $time_options);

$comment = '';
if (isset($_SESSION['item_post_data']['comment']))
{
    $comment = $_SESSION['item_post_data']['comment'];
}

// PC Info
$additional_sql = "SELECT u.`distro`, p.`date_updated`, p.`desktop_environment`, p.`dual_boot`, p.`cpu_vendor`, p.`cpu_model`, p.`gpu_vendor`, g.`id` AS `gpu_id`, g.`name` AS `gpu_model` FROM `users` u LEFT JOIN `user_profile_info` p ON p.user_id = u.user_id LEFT JOIN `gpu_models` g ON g.id = p.gpu_model WHERE p.`user_id` = ?";
$additional = $dbl->run($additional_sql, array($_SESSION['user_id']))->fetch();

// if for some reason they don't have a profile info row, give them one
if (!$additional)
{
	$dbl->run("INSERT INTO `user_profile_info` SET `user_id` = ?", array($_SESSION['user_id']));
}

// display distros
$distributions = $dbl->run("SELECT `name` FROM `distributions` ORDER BY `name` = 'Not Listed' DESC, `name` ASC")->fetch_all(PDO::FETCH_COLUMN);
$distro_list = '';
foreach ($distributions as $distros)
{
	$selected = '';
	if (isset($additional['distro']) && $additional['distro'] == $distros)
	{
		$selected = 'selected';
	}
	$distro_list .= "<option value=\"{$distros}\" $selected>{$distros}</option>";
}
$templating->set('distro_list', $distro_list);

$cpu = '';
if (isset($additional['cpu_vendor']) && !empty($additional['cpu_vendor']))
{
	$cpu .= $additional['cpu_vendor'];
}
if (isset($additional['cpu_model']) && !empty($additional['cpu_model']))
{
	$cpu .= ' ' . $additional['cpu_model'];
}
$templating->set('cpu', $cpu);

$gpu = '';
if (isset($additional['gpu_vendor']) && !empty($additional['gpu_vendor']))
{
	$gpu .= $additional['gpu_vendor'];
}
if (isset($additional['gpu_model']) && !empty($additional['gpu_model']))
{
	$gpu .= ' ' . $additional['gpu_model'];
}
$templating->set('gpu', $gpu);

$comment_editor = new editor($core, $templating, $bbcode);
$comment_editor->editor(['name' => 'text', 'content' => $comment, 'editor_id' => 'comment']);

$templating->block('bottom', 'steamplay_submit');
$templating->set('steam_id', $steam_id);

if (isset($_POST['act']) && $_POST['act'] == 'submit')
{
    // check all answered
    foreach ($_POST as $key => $post)
    {
        if (empty($post) && $key != 'text')
        {
            $_SESSION['item_post_data'] = $_POST;
            $_SESSION['message'] = 'empty';
            $_SESSION['message_extra'] = $key;
            header("Location: /steamplay_submit.php?steam_id=".$steam_id);
            die();
        }
    }

    switch ($_POST['singleplayer']) 
    {
        case 'N/A':
            $singleplayer = NULL;
            break;
        case 'Yes':
            $singleplayer = 1;
            break;
        case 'Has Issues':
            $singleplayer = 2;
            break;
        case 'Broken':
            $singleplayer = 0;
            break;
    }

    switch ($_POST['multiplayer']) 
    {
        case 'N/A':
            $multiplayer = NULL;
            break;
        case 'Yes':
            $multiplayer = 1;
            break;
        case 'Has Issues':
            $multiplayer = 2;
            break;
        case 'Broken':
            $multiplayer = 0;
            break;
    }
	
	if (!in_array($_POST['proton_version'], $proton_versions_plain))
	{
		$_SESSION['item_post_data'] = $_POST;
		$_SESSION['message'] = 'wrong_proton_version';
		header("Location: /steamplay_submit.php?steam_id=".$steam_id);
		die();	
	}

	if (!in_array($_POST['time_played'], $time_played_array))
	{
		$_SESSION['item_post_data'] = $_POST;
		$_SESSION['message'] = 'wrong_time_played';
		header("Location: /steamplay_submit.php?steam_id=".$steam_id);
		die();	
	}

	$comment = core::make_safe($_POST['text']);

    // insert
    $dbl->run("INSERT INTO `proton_reports` SET `author_id` = ?, `report_date` = ?, `steam_appid` = ?, `proton_id` = ?, `single_works` = ?, `multi_works` = ?, `time_played` = ?, `comment` = ?", array($_SESSION['user_id'], core::$sql_date_now, $steam_id, $_POST['proton_version'], $singleplayer, $multiplayer, $_POST['time_played'], $comment));

    // redirect
    $_SESSION['message'] = 'saved';
    $_SESSION['messafe'] = 'report';
    header("Location: /steamplay/reports/".$steam_id);
    die();
}

include(APP_ROOT . '/includes/footer.php');
?>