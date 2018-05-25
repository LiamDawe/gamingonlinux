<?php
/* TODO
Email people the link once finished
Have a database table of:
- user id
- date requested
- download link
Housekeeping cron should delete files and database entry older than 1 week
*/
ini_set("memory_limit", "-1");

define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user_id_requested = $_GET['user_id'];

// make sure user exists first
$check_exists = $dbl->run("SELECT `username`,`avatar_uploaded`,`avatar` FROM `users` WHERE `user_id` = ?", array($user_id_requested))->fetch();

if (!$check_exists)
{
	die('User does not exist');
}

$data_folder = APP_ROOT . '/uploads/user_data_request/' . $user_id_requested . '/';

if (!file_exists($data_folder)) 
{
	mkdir($data_folder, 0777, true);
	@chmod($data_folder, 0777);
}

$zip = new ZipArchive();

$zip_name = $user_id_requested.time().core::random_id().'.zip';

$comment_text = '<p><a href="index.html">Back to index file</a></p>';
$comment_index_text = 'Here is a list of files showing your comments on <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a>. Any problems with this archive please email contact@gamingonlinux.com.';

$comments = $dbl->run("SELECT c.comment_text, c.time_posted, a.title FROM `articles_comments` c LEFT JOIN `articles` a ON c.article_id = a.article_id WHERE c.`author_id` = ?", array($user_id_requested))->fetch_all();

$total_comments = count($comments);

$comments_files = [];

$counter = 0;
$file_number = 0;
if ($total_comments > 0)
{
	$comment_index_text .= '<ul>';
	while($comment_loop = array_splice($comments, 0, 50)) 
	{
		$file_number++;
		foreach($comment_loop as $comment)
		{
			$counter++;
			$comment_date = date('y-m-d H:i:s', $comment['time_posted']);

			$title = '';
			if (isset($comment['title']))
			{
				$title = $comment['title'];
			}
			else
			{
				$title = 'Couldn\'t get article title, possibly a deleted article';
			}

			$comment_text .= '<div><strong>Article title: '.$title.'</strong></div>
			<div><em>Comment posted on:  '.$comment_date.'</em></div>
			<div>'.$comment['comment_text'].'</div>';

			if ($counter < $total_comments)
			{
				$comment_text .= '<hr />';
			}
		}

		$comments_file = $data_folder . $user_id_requested . 'comments_'.$file_number.'.html';
		file_put_contents($comments_file, $comment_text);
		$comments_files[$comments_file] = $user_id_requested . 'comments_'.$file_number.'.html';
		$comment_text = '';

		$comment_index_text .= '<li><a href="' . $user_id_requested . 'comments_'.$file_number.'.html">File: '.$file_number.'</a></li>';
	}
	$comment_index_text .= '</ul>';

	// make an index file with links to the other files, zip it, remove the basic file
	$comment_index_filename = $data_folder . $user_id_requested . 'comments_index.html';

	file_put_contents($comment_index_filename, $comment_index_text);

	if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
	{
		$zip->addFile($comment_index_filename, 'article_comments/index.html');
		$zip->close();
		echo 'ok';
	} 
	else 
	{
		error_log ( 'Failed making zip file for user data request' );
	}

	unlink($comment_index_filename);

	// now add each comments file to the zip, then remove the basic file
	foreach ($comments_files as $full_file_path => $file_name)
	{
		if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
		{
			$zip->addFile($full_file_path, 'article_comments/'.$file_name);
			$zip->close();
			echo 'ok';
		} 
		else 
		{
			error_log ( 'Failed making zip file for user data request' );
		}
		unlink($full_file_path);
	}
}

// grab their avatar and put it in the archive
if ($check_exists['avatar_uploaded'] == 1)
{
	$avatar_path = APP_ROOT . '/uploads/avatars/' . $check_exists['avatar'];
	if (file_exists($avatar_path))
	{
		if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
		{
			$zip->addFile($avatar_path, '/avatar/'.$check_exists['avatar']);
			$zip->close();
			echo 'ok';
		} 
		else 
		{
			error_log ( 'Failed making adding user avatar to zip file for user data request' );
		}
	}
}

echo 'Download: <a href="https://www.gamingonlinux.com/uploads/user_data_request/'.$user_id_requested.'/'.$zip_name.'">https://www.gamingonlinux.com/uploads/user_data_request/'.$user_id_requested.'/'.$zip_name.'</a>';

// check if they have admin notes?

// check for any admin notifications?