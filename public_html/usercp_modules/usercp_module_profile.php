<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Personal Profile Details' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/profile_details');

$templating->block('main', 'usercp_modules/profile_details');

$current_details = $dbl->run("SELECT `profile_address`, `article_bio`, `about_me` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

$profile_address = '';
if (!empty($current_details['profile_address']))
{
    $profile_address = $current_details['profile_address'];
}
$templating->set('profile_address', $profile_address);

$about_me = '';
if (!empty($current_details['about_me']))
{
    $about_me = $current_details['about_me'];
}
$templating->set('about_me', $current_details['about_me']);

$article_bio = '';
if (!empty($current_details['article_bio']))
{
    $article_bio = $current_details['article_bio'];
}
$templating->set('article_bio', $current_details['article_bio']);

if (isset($_POST['act']))
{
    if ($_POST['act'] == 'update')
    {
        $_POST['profile_address'] = trim($_POST['profile_address']);

		if (!empty($_POST['profile_address']))
		{
			// check for naughtyness
			$not_allowed = array('admin','gamingonlinux','moderator','owner');
			foreach($not_allowed as $string)
			{
				if(strpos($_POST['profile_address'], $string) !== false) 
				{
					$_SESSION['message'] = 'naughty';
					header("Location: /usercp.php?module=profile");
					die();    
				}
			}

			// check it's a correct value
			$aValid = array('-', '_');
			if(!ctype_alnum(str_replace($aValid, '', $_POST['profile_address'])))
			{
				$_SESSION['message'] = 'url-characters';
				header("Location: /usercp.php?module=profile");
				die();
			}
			
			if (strlen($_POST['profile_address']) < 4)
			{
				$_SESSION['message'] = 'url-too-short';
				header("Location: /usercp.php?module=profile");
				die();
			}
		}
		else
		{
			$_POST['profile_address'] = NULL;
		}
		
		$_POST['about_me'] = core::make_safe($_POST['about_me'], ENT_QUOTES);
		$_POST['article_bio'] = core::make_safe($_POST['article_bio'], ENT_QUOTES);

        if ($_POST['profile_address'] != $current_details['profile_address'])
        {
            // check for duplicates
            $checker = $dbl->run("SELECT `user_id` FROM `users` WHERE `profile_address` = ?", array($_POST['profile_address']))->fetch();
            if ($checker)
            {
                $_SESSION['message'] = 'exists';
                header("Location: /usercp.php?module=profile");
                die();
            }
		}
		
		// save
		$dbl->run("UPDATE `users` SET `profile_address` = ?, `article_bio` = ?, `about_me` = ? WHERE `user_id` = ?", array($_POST['profile_address'], $_POST['article_bio'], $_POST['about_me'], $_SESSION['user_id']));

        $_SESSION['message'] = 'saved';
        header("Location: /usercp.php?module=profile");
        die();
    }
}