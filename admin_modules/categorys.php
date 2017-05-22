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
	$category_get = $db->sqlquery("SELECT `category_name`, `category_id` FROM `articles_categorys` ORDER BY `category_name` ASC");
	while ($category = $db->fetch($category_get))
	{
		$templating->block('category_row', 'admin_modules/admin_module_categorys');
		$templating->set('category_name', $category['category_name']);
		$templating->set('category_id', $category['category_id']);
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
			$db->sqlquery("INSERT INTO `articles_categorys` SET `category_name` = ?", array($_POST['category_name']));

			header("Location: admin.php?module=categorys&message=added");
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
			$db->sqlquery("UPDATE `articles_categorys` SET `category_name` = ? WHERE `category_id` = ?", array($_POST['category_name'], $_POST['category_id']));

			$core->message("Category {$_POST['category_name']} has been updated! <a href=\"admin.php?module=categorys\">Click here to edit more</a> or <a href=\"index.php\">click here to go to the site home</a>.");
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
				$db->sqlquery("SELECT `category_id` FROM `articles_categorys` WHERE `category_id` = ?", array($_GET['category_id']));
				if ($db->num_rows() != 1)
				{
					$core->message('That is not a correct id!');
				}

				// Delete now
				else
				{
					$db->sqlquery("DELETE FROM `articles_categorys` WHERE `category_id` = ?", array($_GET['category_id']));

					$core->message('That category has now been deleted');
				}
			}
		}
	}
}
