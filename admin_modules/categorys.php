<?php
$templating->load('admin_modules/admin_module_categorys');

if (!isset($_POST['act']))
{
	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'added')
		{
			$core->message("You have added that category!");
		}
	}
	
	$templating->block('add_category', 'admin_modules/admin_module_categorys');

	// get the current categorys
	$category_get = $dbl->run("SELECT `category_name`, `category_id`, `is_genre` FROM `articles_categorys` ORDER BY `category_name` ASC")->fetch_all();
	foreach ($category_get as $category)
	{
		$templating->block('category_row', 'admin_modules/admin_module_categorys');
		$templating->set('category_name', $category['category_name']);
		$templating->set('category_id', $category['category_id']);

		$genre_check = '';
		if ($category['is_genre'] == 1)
		{
			$genre_check = 'checked';
		}
		$templating->set('genre_check', $genre_check);
	}
}

else if (isset($_POST['act']) && !isset($_GET['view']))
{
	if ($_POST['act'] == 'Add')
	{
		if (empty($_POST['category_name']))
		{
			$core->message('You have to fill in a category name!');
		}
		else
		{
			$checker = $dbl->run("SELECT `category_name` FROM `articles_categorys` WHERE `category_name` = ?", [$_POST['category_name']])->fetch();
			if (!$checker)
			{
				$genre_check = 0;
				if (isset($_POST['is_genre']))
				{
					$genre_check = 1;
				}

				$dbl->run("INSERT INTO `articles_categorys` SET `category_name` = ?, `is_genre` = ?", [$_POST['category_name'], $genre_check]);

				// note who did it
				$core->new_admin_note(array('completed' => 1, 'content' => ' added a new content category named: '.$_POST['category_name'].'.'));

				header("Location: admin.php?module=categorys&message=added");
				die();
			}
			else
			{
				$_SESSION['message'] = 'category_exists';
				header("Location: admin.php?module=categorys");
				die();
			}
		}
	}
	

	if ($_POST['act'] == 'Edit')
	{	
		// make sure its not empty
		if (empty($_POST['category_name']))
		{
			$core->message('You have to fill in a category name it cannot be empty!');
		}
		else
		{
			$genre_check = 0;
			if (isset($_POST['is_genre']))
			{
				$genre_check = 1;
			}

			$dbl->run("UPDATE `articles_categorys` SET `category_name` = ?, `is_genre` = ? WHERE `category_id` = ?", array($_POST['category_name'], $genre_check, $_POST['category_id']));

			// note who did it
			$core->new_admin_note(array('completed' => 1, 'content' => ' updated a content category named: '.$_POST['category_name'].'.'));

			$_SESSION['message'] = 'edited';
			$_SESSION['message_extra'] = 'category';
			header("Location: /admin.php?module=categorys");
		}
	}

	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that category?', "admin.php?module=categorys&amp;category_id={$_POST['category_id']}", "Delete");
		}
		else if (isset($_POST['no']))
		{
			header("Location: admin.php?module=categorys");
		}
		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_GET['category_id']))
			{
				$core->message('That is not a correct id!');
			}
			else
			{
				// check category exists
				$cat_id = $dbl->run("SELECT `category_id`, `category_name` FROM `articles_categorys` WHERE `category_id` = ?", array($_GET['category_id']))->fetch();
				if (!$cat_id)
				{
					$core->message('That is not a correct id!');
				}
				// Delete now
				else
				{
					$dbl->run("DELETE FROM `articles_categorys` WHERE `category_id` = ?", array($_GET['category_id']));

					// note who did it
					$core->new_admin_note(array('completed' => 1, 'content' => ' deleted a content category named: '.$cat_id['category_name'].'.'));

					$_SESSION['message'] = 'deleted';
					$_SESSION['message_extra'] = 'category';
					header("Location: /admin.php?module=categorys");
				}
			}
		}
	}
}
