<?php
require_once APP_ROOT . '/includes/aws/aws-autoloader.php';
use claviska\SimpleImage;
use Aws\S3\S3Client;

class game_sales
{
	protected $templating;
	protected $dbl;
	protected $user;
    protected $core;
    protected $bbcode;

	public $main_links;

	function __construct($dbl, $templating, $user, $core, $bbcode)
	{
		$this->dbl = $dbl;
		$this->templating = $templating;
		$this->user = $user;
        $this->core = $core;
        $this->bbcode = $bbcode;

		// key/db field => nice name
		$this->main_links = array('link' => 'Official Site', 'gog_link' => 'GOG', 'steam_link' => 'Steam', 'stadia_link' => 'Stadia', 'itch_link' => 'itch.io', 'crowdfund_link' => 'Crowdfunded');
	}

	// this will remove needless junk for the proper display title of a game
	function clean_title($title)
	{
		$title = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $title); // remove junk
		$title = trim($title); // some stores give a random space
		return $title;
	}

	/* return a basic string, with no special characters and no spaces
	gives us an absolute bare-bones name to compare different stores sales like "This: This" and "This - This"
	*/
	function stripped_title($string)
	{
		$string = str_replace(' ', '', $string);
		$string = trim($string);
		$string = strtolower($string);
		return preg_replace('/[^A-Za-z0-9]/', '', $string); // Removes special chars.
	}

	// this needs cleaning up
	function steam_release_date($data)
	{
		$clean_release_date = NULL;
		$release_date_raw = $data;
		echo 'Raw release date: ' . $release_date_raw . "\n";
		$trimmed_date = trim($release_date_raw);	
		$remove_comma = str_replace(',', '', $trimmed_date);

		// ensure there's a numeric year in it, otherwise it's not an exact proper date
		if (preg_match("/[0-9]{4}/", $remove_comma) == 0)
		{
			return NULL;
		}

		$parsed_release_date = strtotime($remove_comma);
		$length = strlen($remove_comma); // so we can get rid of items that only have the year nice and simple
		$parsed_release_date = date("Y-m-d", $parsed_release_date);
		$has_day = DateTime::createFromFormat('F Y', $remove_comma);
			
		if ($parsed_release_date != '1970-01-01' && $length != 4 && $has_day == FALSE)
		{
			return $clean_release_date = $parsed_release_date;
		}
		return null;
	}

	function get_correct_info($title, $steam_id = NULL, $update_naming = 0)
	{
		// first check using basic name, binary to ensure correct against accent letters and so on
		$check_name = $this->dbl->run("SELECT `id` FROM `calendar` WHERE BINARY `name` = ?", array($title))->fetchOne();

		// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
		$duplicate = $this->dbl->run("SELECT `real_id` FROM `item_dupes` WHERE BINARY `name` = ?", array($title))->fetchOne();

		// for if steam importing, check over steam id
		if ($steam_id)
		{
			// check for name change, insert different name into dupes table and keep original name
			$name_change = $this->dbl->run("SELECT `id` FROM `calendar` WHERE `steam_id` = ? AND BINARY `name` != ?", array($steam_id, $title))->fetchOne();
			if ($name_change)
			{
				echo PHP_EOL . 'Detected name changed from what we store in the DB.'; 

				// only enter a dupe, if there's not another item going by this name (because stores let games be the exact same name <_<)
				$check_conflict = $this->dbl->run("SELECT `id` FROM `calendar` WHERE `steam_id` != ? AND BINARY `name` = ?", array($steam_id, $title))->fetchOne();
				if (!$check_conflict)
				{
					if ($update_naming == 0)
					{
						echo PHP_EOL . 'Leaving original name in place.' . PHP_EOL;
						$exists = $this->dbl->run("SELECT 1 FROM `item_dupes` WHERE `real_id` = ? AND BINARY `name` = ?", array($name_change, $title))->fetchOne();
						if (!$exists)
						{
							echo PHP_EOL . 'Adding new duplicate name.' . PHP_EOL;
							$this->dbl->run("INSERT IGNORE INTO `item_dupes` SET `real_id` = ?, `name` = ?", array($name_change, $title));
						}
					}
					else if ($update_naming == 1)
					{
						echo PHP_EOL . 'Name changed, not a new item.' . PHP_EOL;
						$this->dbl->run("UPDATE `calendar` SET `name` = ? WHERE `id` = ?", array($title, $name_change));
					}
				}
			}
		}

		if ($check_name)
		{
			echo PHP_EOL . 'Now using the main ID.' . PHP_EOL;
			$game_id = $check_name;
		}
		if ($duplicate)
		{
			echo PHP_EOL . 'Now using real ID from the dupe.' . PHP_EOL;
			$game_id = $duplicate;
		}
		if ($name_change)
		{
			echo PHP_EOL . 'Now using real ID from the name change.' . PHP_EOL;
			$game_id = $name_change;
		}

		if (isset($game_id))
		{
			$details = $this->dbl->run("SELECT `id`, `small_picture`, `steam_id`, `bundle`, `date`, `stripped_name` FROM `calendar` WHERE `id` = ?", array($game_id))->fetch();

			return $details;
		}
		else
		{
			return false;
		}
	}

	// move previously uploaded tagline image to correct directory
	function move_small($game_id, $file)
	{
		$types = array('jpg', 'png', 'gif');
		$full_file_big = $this->core->config('path') . "uploads/gamesdb/small/temp/" . $file;

		if (!file_exists($full_file_big))
		{
			$this->error_message = "Could not find temp image to load? $full_file_big";
			return false;
		}

		else
		{
			$image_info = getimagesize($full_file_big);
			$image_type = $image_info[2];
			$file_ext = '';
			if( $image_type == IMAGETYPE_JPEG )
			{
				$file_ext = 'jpg';
			}

			else if( $image_type == IMAGETYPE_GIF )
			{
				$file_ext = 'gif';
			}

			else if( $image_type == IMAGETYPE_PNG )
			{
				$file_ext = 'png';
			}

			// give the image a random file name
			$imagename = rand() . 'id' . $game_id . 'gol.' . $file_ext;

			// the actual image
			$source = $full_file_big;

			// where to upload to
			$target = $this->core->config('path') . "uploads/gamesdb/small/" . $imagename;

			if (rename($source, $target))
			{
				$image = $this->dbl->run("SELECT `small_picture` FROM `calendar` WHERE `id` = ?", array($game_id))->fetch();

				// remove old image
				if (isset($image))
				{
					if (!empty($image['small_picture']))
					{
						unlink($this->core->config('path') . 'uploads/gamesdb/small/' . $image['small_picture']);
					}
				}

				$this->dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$imagename, $game_id]);
				
				return true;
			}
		}
	}

	function display_free($filters = NULL)
	{
		$this->templating->load('free_games');

		$this->templating->block('list_top', 'free_games');

		// for non-ajax requests
		if (isset($_GET['option']) && is_array($_GET['option']) && $filters == NULL)
		{
			$filters_sort = ['option' => $_GET['option']];
		}
		else if ($filters)
		{
			$filters_sort = array();
			parse_str($filters, $filters_sort);
		}

		$genre_ids = [];
		$licenses = [];
		$options_sql = '';
		$genre_join = '';
		if (isset($filters_sort) && is_array($filters_sort))
		{
			if (isset($filters_sort['genres']))
			{
				foreach ($filters_sort['genres'] as $genre)
				{
					$genre_ids[] = $genre;
					$options_link[] = 'genres[]=' . $genre;
				}
				$in  = str_repeat('?,', count($genre_ids) - 1) . '?';
				$options_sql .= ' AND r.genre_id IN ('.$in.') ';
				$genre_join = ' INNER JOIN `game_genres_reference` r ON r.game_id = c.`id` ';
			}

			if (isset($filters_sort['licenses']))
			{
				foreach ($filters_sort['licenses'] as $license)
				{
					$licenses[] = $license;
					$options_link[] = 'licenses[]=' . $license;
				}
				$in  = str_repeat('?,', count($licenses) - 1) . '?';
				$options_sql .= ' AND c.license IN ('.$in.') ';
			}
		}

		// paging for pagination
		$page = core::give_page();

		$where = NULL;
		$sql_where = '';
		$link_extra = '';
		if (isset($_GET['q']))
		{
			$search_query = str_replace('+', ' ', $_GET['q']);
			$where = '%'.$search_query.'%';
			$sql_where = ' c.`name` LIKE ? AND ';

			$merged_arrays = array_merge([$where], $genre_ids, $licenses);

			$total_rows = $this->dbl->run("SELECT COUNT(Distinct id) FROM `calendar` c WHERE $sql_where c.`free_game` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 AND `supports_linux` = 1 ORDER BY c.`name` ASC", [$where])->fetchOne();
			$pagination = $this->core->pagination_link(50, $total_rows, '/free_games.php?', $page, $link_extra);	

			$games_res = $this->dbl->run("SELECT c.`id`, c.`name`, c.`license`, c.`small_picture` FROM `calendar` c $genre_join WHERE $sql_where c.`free_game` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 AND `supports_linux` = 1 $options_sql GROUP BY c.`id` ORDER BY c.`name` ASC LIMIT {$this->core->start}, 50", $merged_arrays)->fetch_all();
		}
		else
		{
			$merged_arrays = array_merge($genre_ids, $licenses);
			$total_rows = $this->dbl->run("SELECT COUNT(Distinct c.id) FROM `calendar` c $genre_join WHERE c.`free_game` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 AND `supports_linux` = 1 $options_sql  ORDER BY c.`name` ASC", $merged_arrays)->fetchOne();
			$pagination = $this->core->pagination_link(50, $total_rows, '/free_games.php?', $page, $link_extra);

			$games_res = $this->dbl->run("SELECT c.`id`, c.`name`, c.`license`, c.`small_picture` FROM `calendar` c $genre_join WHERE c.`free_game` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 AND `supports_linux` = 1 $options_sql GROUP BY c.`id` ORDER BY c.`name` ASC LIMIT {$this->core->start}, 50", $merged_arrays)->fetch_all();	
		}

		$this->templating->set('filter_total', $total_rows);

		if ($games_res)
		{
			// first grab a list of all the genres for each game, so we only do one query instead of one for each
			$genre_ids = [];
			foreach ($games_res as $set)
			{
				$genre_ids[] = $set['id'];
			}
			$in  = str_repeat('?,', count($genre_ids) - 1) . '?';
			$genre_tag_sql = "SELECT r.`game_id`, c.`category_name` AS `name` FROM `game_genres_reference` r INNER JOIN `articles_categorys` c ON c.`category_id` = r.genre_id WHERE r.`game_id` IN ($in) GROUP BY r.`game_id`, `name`";
			$genre_res = $this->dbl->run($genre_tag_sql, $genre_ids)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

			foreach ($games_res as $game)
			{
				$this->templating->block('row', 'free_games');

				if ($game['small_picture'] != NULL && $game['small_picture'] != '')
				{
					$small_pic = '<img src="' . $this->core->config('website_url') . 'uploads/gamesdb/small/' . $game['small_picture'] . '" alt="" />';
				}
				else
				{
					$small_pic = '<img src="' . $this->core->config('website_url') . 'templates/default/images/gamesdb/default_smallpic.jpg" alt="" />';
				}

				$this->templating->set('small_pic', $small_pic);

				$this->templating->set('name', $game['name']);
				$this->templating->set('item_id', $game['id']);

				$license = '';
				if (isset($game['license']) && $game['license'] != '')
				{
					$license = '<div class="itemdb-list-licensetext"><small>License: ' . $game['license'] . '</small></div>';
				}
				$this->templating->set('license', $license);
			}
		}
		else
		{
			$this->core->message("We aren't finding any free games at the moment, try a different filtering option? Or come back soon!");
		}

		$this->templating->block('bottom', 'free_games');
		if ($pagination != '')
		{
			$pagination = '<div class="games-pagination">'.$pagination.'</div>';
		}
		$this->templating->set('pagination', $pagination);
	}

	// free games list needs merging with this, to have free games auto ticked - less code dupe, only place for all
	function display_all_games($filters = NULL)
	{
		$this->templating->load('itemdb');

		$this->templating->block('list_top', 'itemdb');

		// for non-ajax requests
		if (isset($_GET['filters']) && $filters == NULL)
		{
			$filters_sort = array();
			parse_str($_GET['filters'], $filters_sort);
		}
		else if ($filters)
		{
			$filters_sort = array();
			parse_str($filters, $filters_sort);
		}

		$genre_ids = [];
		$licenses = [];
		$engines = [];
		$options_sql = '';
		$genre_join = '';
		if (isset($filters_sort) && is_array($filters_sort))
		{
			if (isset($filters_sort['genres']))
			{
				foreach ($filters_sort['genres'] as $genre)
				{
					$genre_ids[] = $genre;
					$options_link[] = 'genres[]=' . $genre;
				}
				$in  = str_repeat('?,', count($genre_ids) - 1) . '?';
				$options_sql .= ' AND r.genre_id IN ('.$in.') ';
				$genre_join = ' INNER JOIN `game_genres_reference` r ON r.game_id = c.`id` ';
			}

			if (isset($filters_sort['licenses']))
			{
				foreach ($filters_sort['licenses'] as $license)
				{
					$licenses[] = $license;
					$options_link[] = 'licenses[]=' . $license;
				}
				$in  = str_repeat('?,', count($licenses) - 1) . '?';
				$options_sql .= ' AND c.license IN ('.$in.') ';
			}

			if (isset($filters_sort['engines']))
			{
				foreach ($filters_sort['engines'] as $engine)
				{
					$engines[] = $engine;
					$options_link[] = 'engines[]=' . $engine;
				}
				$in  = str_repeat('?,', count($engines) - 1) . '?';
				$options_sql .= ' AND c.game_engine_id IN ('.$in.') ';
			}

			if (isset($filters_sort['misc']))
			{
				foreach ($filters_sort['misc'] as $misc)
				{
					if ($misc == 'free_only')
					{
						$options_sql .= ' AND c.free_game = 1 ';
					}
					if ($misc == 'no_dlc')
					{
						$options_sql .= ' AND c.is_dlc = 0 ';
					}
				}
			}
			if (isset($filters['initial']))
			{
				$options_sql .= ' AND c.`name` LIKE ';
			}
		}

		// paging for pagination
		$page = core::give_page();

		$where = NULL;
		$sql_where = '';
		$link_extra = '';
		if (isset($_GET['q']))
		{
			$search_query = str_replace('+', ' ', $_GET['q']);
			$where = '%'.$search_query.'%';
			$sql_where = ' c.`name` LIKE ? AND ';

			$merged_arrays = array_merge([$where], $genre_ids, $licenses, $engines);

			$total_rows = $this->dbl->run("SELECT COUNT(Distinct c.id) FROM `calendar` c WHERE $sql_where c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND c.`bundle` = 0 AND c.`supports_linux` = 1 ORDER BY c.`name` ASC", [$where])->fetchOne();
			$pagination = $this->core->pagination_link(50, $total_rows, '/itemdb.php?view=mainlist&', $page, $link_extra);	

			$games_res = $this->dbl->run("SELECT c.`id`, c.`name`, c.`link`, c.`gog_link`, c.`steam_link`, c.`itch_link`, c.`license`, c.`small_picture`, c.`trailer`, c.`is_dlc` FROM `calendar` c $genre_join WHERE $sql_where c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND c.bundle = 0 AND c.`supports_linux` = 1 $options_sql GROUP BY c.`id` ORDER BY c.`name` ASC LIMIT {$this->core->start}, 50", $merged_arrays)->fetch_all();
		}
		else
		{
			$merged_arrays = array_merge($genre_ids, $licenses, $engines);
			$total_rows = $this->dbl->run("SELECT COUNT(Distinct c.id) FROM `calendar` c $genre_join WHERE c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND c.bundle = 0 AND c.`supports_linux` = 1 $options_sql ORDER BY c.`name` ASC", $merged_arrays)->fetchOne();
			$pagination = $this->core->pagination_link(50, $total_rows, '/itemdb.php?view=mainlist&', $page, $link_extra);

			$games_res = $this->dbl->run("SELECT c.`id`, c.`name`, c.`link`, c.`gog_link`, c.`steam_link`, c.`itch_link`, c.`license`, c.`small_picture`, c.`trailer`, c.`is_dlc` FROM `calendar` c $genre_join WHERE c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND c.`bundle` = 0 AND c.`supports_linux` = 1 $options_sql GROUP BY c.`id` ORDER BY c.`name` ASC LIMIT {$this->core->start}, 50", $merged_arrays)->fetch_all();	
		}

		$this->templating->set('filter_total', number_format($total_rows));

		if ($games_res)
		{
			// first grab a list of all the genres for each game, so we only do one query instead of one for each
			$genre_ids = [];
			foreach ($games_res as $set)
			{
				$genre_ids[] = $set['id'];
			}
			$in  = str_repeat('?,', count($genre_ids) - 1) . '?';
			$genre_tag_sql = "SELECT r.`game_id`, c.`category_name` AS `name` FROM `game_genres_reference` r INNER JOIN `articles_categorys` c ON c.`category_id` = r.genre_id WHERE r.`game_id` IN ($in) GROUP BY r.`game_id`, `name`";
			$genre_res = $this->dbl->run($genre_tag_sql, $genre_ids)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

			foreach ($games_res as $game)
			{
				$this->templating->block('row');

				$small_pic = '';
				if ($game['small_picture'] != NULL && $game['small_picture'] != '')
				{
					$small_pic = '<img src="' . $this->core->config('website_url') . 'uploads/gamesdb/small/' . $game['small_picture'] . '" alt="" />';
				}

				if ($game['trailer'] != NULL && $game['trailer'] != '')
				{
					$small_pic = '<a data-fancybox href="'.$game['trailer'].'">' . $small_pic . '</a>';
				}

				$this->templating->set('small_pic', $small_pic);

				$edit = '';
				if ($this->user->check_group([1,2,5]))
				{
					$edit = '<a href="/admin.php?module=games&view=edit&id='.$game['id'].'"><span class="icon edit edit-sale-icon"></span></a> ';
				}
				$this->templating->set('edit', $edit);

				$this->templating->set('name', htmlentities($game['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
				$this->templating->set('id', $game['id']);

				$links = [];
				$stores = ['link' => 'Official Site', 'gog_link' => 'GOG', 'steam_link' => 'Steam', 'itch_link' => 'itch.io'];
				foreach ($stores as $type => $name)
				{
					if (isset($game[$type]) && !empty($game[$type]))
					{
						$links[] = '<a href="'.$game[$type].'">'.$name.'</a>';
					}
				}
				$this->templating->set('links', implode(', ', $links));

				$license = '';
				if (isset($game['license']) && $game['license'] != '')
				{
					$license = $game['license'];
				}
				$this->templating->set('license', $license);
				
				$genre_list = [];
				if ($game['is_dlc'] == 1)
				{
					$genre_list[] = '<span class="badge yellow">DLC</span>';
				}
				
				if (isset($genre_res[$game['id']]))
				{	
					foreach ($genre_res[$game['id']] as $k => $name)
					{
						$genre_list[] = "<span class=\"badge\">{$name}</span>";
					}
				}

				$suggest_link = '';
				if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
				{
					$suggest_link .= '<a href="/index.php?module=items_database&view=suggest_tags&id='.$game['id'].'">Suggest Tags</a>';
				}

				$genre_output = 'None';
				if (!empty($genre_list))
				{
					$genre_output = implode(' ', $genre_list);
				}

				$this->templating->set('genre_list', $genre_output);
				$this->templating->set('suggest_link', $suggest_link);
			}
		}
		else
		{
			$this->core->message("We aren't finding any games at the moment, try a different filtering option? Or come back soon!");
		}

		$this->templating->block('bottom', 'itemdb');
		if ($pagination != '')
		{
			$pagination = '<div class="games-pagination">'.$pagination.'</div>';
		}
		$this->templating->set('pagination', $pagination);
	}

	function notify_wishlists($game_id)
	{
		$id_list = $this->dbl->run("SELECT `user_id` FROM `user_wishlist` WHERE `game_id` = ?", array($game_id))->fetch_all(PDO::FETCH_COLUMN);
		if ($id_list)
		{
			$rows = array();
			foreach ($id_list as $user_id)
			{
				$rows[] = [$user_id, $game_id, 'wishlist_sale'];
			}

			$sql = "insert into `user_notifications` (owner_id, sale_game_id, type) values ";

			$this->dbl->insert_multi($sql, $rows);
		}
	}

	function display_normal($filters = NULL)
	{
		$per_page = 50;

		// for non-ajax requests
		if (isset($_GET['option']) && is_array($_GET['option']) && $filters == NULL)
		{
			$filters_sort = ['option' => $_GET['option']];
		}
		else if ($filters)
		{
			$filters_sort = array();
			parse_str($filters, $filters_sort);
		}

		// grab their wishlist items
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$wishlist_items = $this->dbl->run("SELECT `game_id` FROM `user_wishlist` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN);
		}

		$this->templating->load('sales');

		// get normal sales
		$this->templating->block('sales_top', 'sales');

		$currency = ['uk' => ['sign' => '&pound;', 'sign_position' => 'front', 'field' => 'pounds'], 'us' => ['sign' => '&dollar;', 'sign_position' => 'front', 'field' => 'dollars'], 'eu' => ['sign' => '&euro;', 'sign_position' => 'back', 'field' => 'euro']];

		$store_ids = [];
		$stores_sql = '';
		$picked_currency = $currency['us'];
		if (isset($filters_sort) && is_array($filters_sort))
		{
			$options_array = [];
			$options_link = [];
			foreach ($filters_sort['option'] as $option)
			{
				if ($option == '5less')
				{
					$options_array[] = ' s.`sale_dollars` <= 5 ';
					$options_link[] = 'option[]=5less';
				}
				if ($option == '10less')
				{
					$options_array[] = ' s.`sale_dollars` <= 10 ';
					$options_link[] = 'option[]=10less';
				}
				if ($option == 'nodlc')
				{
					$options_array[] = ' c.`is_dlc` = 0 ';
					$options_link[] = 'option[]=nodlc';
				}
			}
			
			if (isset($filters_sort['stores']))
			{
				foreach ($filters_sort['stores'] as $store)
				{
					$store_ids[] = $store;
					$options_link[] = 'stores[]=' . $store;
				}
				$in  = str_repeat('?,', count($store_ids) - 1) . '?';
				$stores_sql = ' AND store_id in ('.$in.') ';
			}
			
			if (isset($filters_sort['currency']) && array_key_exists($filters_sort['currency'], $currency))
			{
				$picked_currency = $currency[$filters_sort['currency']];
			}
		}

		$sale_price_field = 'sale_' . $picked_currency['field'];
		$original_price_field = 'original_' . $picked_currency['field'];

		$options_sql = '';
		if (!empty($options_array))
		{
			$options_sql = ' AND ' . implode(' AND ', $options_array);
		}

		$where = '';
		if (isset($_GET['q']) && !empty($_GET['q']))
		{
			$search_query = str_replace('+', ' ', $_GET['q']);
			$where = ['%'.$search_query.'%'];
			$sales_res = $this->dbl->run("SELECT c.id as game_id, c.`name`, c.`is_dlc`, c.`small_picture`, s.`$sale_price_field`, s.$original_price_field, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id WHERE c.`free_game` = 0 AND c.`name` LIKE ? AND s.`$sale_price_field` IS NOT NULL $options_sql $stores_sql ORDER BY s.`$sale_price_field` ASC", array_merge($where, $store_ids))->fetch_all();

			$search_q = $_GET['q'];
		}
		else
		{
			$game_id_sql = NULL;
			$game_id_value = [];
			if (isset($_GET['game_id']) && is_numeric($_GET['game_id']))
			{
				$game_id_sql = " AND `game_id` = ? ";
				$game_id_value = [$_GET['game_id']];				
			}
			$sales_sql = "SELECT c.id as game_id, c.`name`, c.`is_dlc`, c.`small_picture`, s.`$sale_price_field`, s.$original_price_field, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id WHERE c.`free_game` = 0 AND s.`$sale_price_field` IS NOT NULL $game_id_sql $options_sql $stores_sql ORDER BY s.`$sale_price_field` ASC";
			$sales_res = $this->dbl->run($sales_sql, array_merge($store_ids, $game_id_value))->fetch_all();

			$search_q = '';
		}

		$this->templating->set('search_q', htmlspecialchars($search_q));

		$sales_merged = [];
		foreach ($sales_res as $sale)
		{
			$sales_merged[$sale['name']][] = ['game_id' => $sale['game_id'], 'store' => $sale['store_name'], 'sale_price' => $sale[$sale_price_field], 'original_price' => $sale[$original_price_field], 'link' => $sale['link'], 'is_dlc' => $sale['is_dlc'], 'picture' => $sale['small_picture']];
		}

		// paging for pagination
		$page = core::give_page();
		if ($page >= 1) // slicing an array, starts from 0 not 1
		{
			$page = $page - 1;
		}

		$total_rows = count($sales_merged);
		$lastpage = ceil($total_rows/$per_page) - 1;
		if ($page > $lastpage)
		{
			$page = $lastpage;
		}

		$this->templating->set('total', $total_rows);

		//foreach ($sales_merged as $name => $sales)
		foreach (array_slice($sales_merged, $page*$per_page, $per_page) as $name => $sales)
		{
			$this->templating->block('sale_row', 'sales');
			$this->templating->set('name', $name);

			$small_pic = '';
			if ($sales[0]['picture'] != NULL && $sales[0]['picture'] != '')
			{
				$small_pic = $this->core->config('website_url') . 'uploads/gamesdb/small/' . $sales[0]['picture'];
			}
			$this->templating->set('small_pic', $small_pic);

			$stores_output = '';
			foreach ($sales as $store)
			{
				$star = '';
				if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
				{
					if (in_array($store['game_id'], $wishlist_items))
					{
						$star = '<a class="change_wishlist tooltip-top" data-type="remove" data-game-id="'.$store['game_id'].'" title="On Wishlist" href="">&#9733;</a>';
					}
					else
					{
						$star = '<a class="change_wishlist tooltip-top" data-type="add" data-game-id="'.$store['game_id'].'" title="Not On Wishlist" href="">&#9734;</a>';
					}
				}
				$this->templating->set('star', $star);

				$edit = '';
				if ($this->user->check_group([1,2,5]))
				{
					$edit = '<a href="/admin.php?module=games&view=edit&id='.$store['game_id'].'"><span class="icon edit edit-sale-icon"></span></a> ';
				}
				$this->templating->set('edit', $edit);
				$savings_dollars = '';
				if ($store['original_price'] != 0)
				{
					$savings = 1 - ($store['sale_price'] / $store['original_price']);
					$savings_dollars = round($savings * 100) . '% off';
				}

				$dlc = '';
				if ($store['is_dlc'] == 1)
				{
					$dlc = '<span class="badge yellow">DLC</span>';
				}

				$front_sign = '';
				$back_sign = '';
				if ($picked_currency['sign_position'] == 'front')
				{
					$front_sign = $picked_currency['sign'];
				}
				else if ($picked_currency['sign_position'] == 'back')
				{
					$back_sign = $picked_currency['sign'];
				}

				$stores_output .= ' <span class="badge"><a href="'.$store['link'].'" target="_blank">'.$store['store'].' - ' . $front_sign .$store['sale_price'] . $back_sign . ' | ' . $savings_dollars . '</a></span> ';
			}
			$this->templating->set('stores', $dlc . $stores_output);

			$this->templating->set('lowest_price', $sales[0]['sale_price']);
			$this->templating->set('name_sort', trim(strtolower($name)));
		}

		$this->templating->block('sales_bottom', 'sales');

		$link_extra = '';
		if (!empty($options_link) && is_array($options_link))
		{
			$link_extra = '&' . implode('&', $options_link);
		}

		$pagination = $this->core->pagination_link($per_page, $total_rows, '/sales.php?', $page + 1, $link_extra);

		if ($pagination != '')
		{
			$pagination = '<div class="sales-pagination">'.$pagination.'</div>';
		}
		$this->templating->set('pagination', $pagination);
	}

	function display_hidden_steam($filters = NULL)
	{
		$this->templating->load('hidden_steam_games');

		$this->templating->block('list_top', 'hidden_steam_games');

		// for non-ajax requests
		if (isset($_GET['option']) && is_array($_GET['option']) && $filters == NULL)
		{
			$filters_sort = ['option' => $_GET['option']];
		}
		else if ($filters)
		{
			$filters_sort = array();
			parse_str($filters, $filters_sort);
		}

		$genre_ids = [];
		$licenses = [];
		$options_sql = '';
		$genre_join = '';
		if (isset($filters_sort) && is_array($filters_sort))
		{
			if (isset($filters_sort['genres']))
			{
				foreach ($filters_sort['genres'] as $genre)
				{
					$genre_ids[] = $genre;
					$options_link[] = 'genres[]=' . $genre;
				}
				$in  = str_repeat('?,', count($genre_ids) - 1) . '?';
				$options_sql .= ' AND r.genre_id IN ('.$in.') ';
				$genre_join = ' INNER JOIN `game_genres_reference` r ON r.game_id = c.`id` ';
			}

			if (isset($filters_sort['licenses']))
			{
				foreach ($filters_sort['licenses'] as $license)
				{
					$licenses[] = $license;
					$options_link[] = 'licenses[]=' . $license;
				}
				$in  = str_repeat('?,', count($licenses) - 1) . '?';
				$options_sql .= ' AND c.license IN ('.$in.') ';
			}
		}

		// paging for pagination
		$page = isset($_GET['page'])?intval($_GET['page']-1):0;

		$where = NULL;
		$sql_where = '';
		$link_extra = '';
		if (isset($_GET['q']))
		{
			$search_query = str_replace('+', ' ', $_GET['q']);
			$where = '%'.$search_query.'%';
			$sql_where = ' c.`name` LIKE ? AND ';

			$merged_arrays = array_merge([$where], $genre_ids, $licenses);

			$total_rows = $this->dbl->run("SELECT COUNT(Distinct id) FROM `calendar` c WHERE $sql_where c.`is_hidden_steam` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 ORDER BY c.`name` ASC", [$where])->fetchOne();
			$pagination = $this->core->pagination_link(50, $total_rows, '/hidden_steam_games.php?', $page + 1, $link_extra);	

			$games_res = $this->dbl->run("SELECT c.`id`, c.`name`, c.`link`, c.`gog_link`, c.`steam_link`, c.`itch_link`, c.`license`, c.`small_picture`, c.`trailer` FROM `calendar` c $genre_join WHERE $sql_where c.`is_hidden_steam` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 $options_sql GROUP BY c.`id` ORDER BY c.`name` ASC LIMIT {$this->core->start}, 50", $merged_arrays)->fetch_all();
		}
		else
		{
			$merged_arrays = array_merge($genre_ids, $licenses);
			$total_rows = $this->dbl->run("SELECT COUNT(Distinct c.id) FROM `calendar` c $genre_join WHERE c.`is_hidden_steam` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 $options_sql  ORDER BY c.`name` ASC", $merged_arrays)->fetchOne();
			$pagination = $this->core->pagination_link(50, $total_rows, '/hidden_steam_games.php?', $page + 1, $link_extra);

			$games_res = $this->dbl->run("SELECT c.`id`, c.`name`, c.`link`, c.`gog_link`, c.`steam_link`, c.`itch_link`, c.`license`, c.`small_picture`, c.`trailer` FROM `calendar` c $genre_join WHERE c.`is_hidden_steam` = 1 AND c.`also_known_as` IS NULL AND c.`is_application` = 0 AND c.`approved` = 1 AND `is_emulator` = 0 AND `is_dlc` = 0 $options_sql GROUP BY c.`id` ORDER BY c.`name` ASC LIMIT {$this->core->start}, 50", $merged_arrays)->fetch_all();	
		}

		$this->templating->set('filter_total', $total_rows);

		if ($games_res)
		{
			// first grab a list of all the genres for each game, so we only do one query instead of one for each
			$genre_ids = [];
			foreach ($games_res as $set)
			{
				$genre_ids[] = $set['id'];
			}
			$in  = str_repeat('?,', count($genre_ids) - 1) . '?';
			$genre_tag_sql = "SELECT r.`game_id`, c.`category_name` AS `name` FROM `game_genres_reference` r INNER JOIN `articles_categorys` c ON c.`category_id` = r.genre_id WHERE r.`game_id` IN ($in) GROUP BY r.`game_id`, `name`";
			$genre_res = $this->dbl->run($genre_tag_sql, $genre_ids)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

			foreach ($games_res as $game)
			{
				$this->templating->block('row', 'free_games');

				$small_pic = '';
				if ($game['small_picture'] != NULL && $game['small_picture'] != '')
				{
					$small_pic = '<img src="' . $this->core->config('website_url') . 'uploads/gamesdb/small/' . $game['small_picture'] . '" alt="" />';
				}

				if ($game['trailer'] != NULL && $game['trailer'] != '')
				{
					$small_pic = '<a data-fancybox href="'.$game['trailer'].'">' . $small_pic . '</a>';
				}

				$this->templating->set('small_pic', $small_pic);

				$edit = '';
				if ($this->user->check_group([1,2,5]))
				{
					$edit = '<a href="/admin.php?module=games&view=edit&id='.$game['id'].'"><span class="icon edit edit-sale-icon"></span></a> ';
				}
				$this->templating->set('edit', $edit);

				$this->templating->set('name', $game['name']);
				$this->templating->set('id', $game['id']);

				$links = [];
				$stores = ['link' => 'Official Site', 'steam_link' => 'Steam'];
				foreach ($stores as $type => $name)
				{
					if (isset($game[$type]) && !empty($game[$type]))
					{
						$links[] = '<a href="'.$game[$type].'">'.$name.'</a>';
					}
				}
				$this->templating->set('links', implode(', ', $links));

				$license = '';
				if (isset($game['license']) && $game['license'] != '')
				{
					$license = $game['license'];
				}
				$this->templating->set('license', $license);
				
				$genre_list = [];
				if (isset($genre_res[$game['id']]))
				{	
					foreach ($genre_res[$game['id']] as $k => $name)
					{
						$genre_list[] = "<span class=\"badge\">{$name}</span>";
					}
				}
				$suggest_link = '';
				if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
				{
					$suggest_link .= '<a href="/index.php?module=items_database&view=suggest_tags&id='.$game['id'].'">Suggest Tags</a>';
				}

				$genre_output = 'None';
				if (!empty($genre_list))
				{
					$genre_output = implode(' ', $genre_list);
				}

				$this->templating->set('genre_list', $genre_output);
				$this->templating->set('suggest_link', $suggest_link);
			}
		}
		else
		{
			$this->core->message("We aren't finding any free games at the moment, try a different filtering option? Or come back soon!");
		}

		$this->templating->block('bottom', 'free_games');
		if ($pagination != '')
		{
			$pagination = '<div class="free-games-pagination">'.$pagination.'</div>';
		}
		$this->templating->set('pagination', $pagination);
	}

	// for editing a game in the database, adjust what genre's it's linked with
	function process_developers($game_id)
	{
		if (isset($game_id) && is_numeric($game_id))
		{
			// delete any existing genres that aren't in the final list for publishing
			$current_devs = $this->dbl->run("SELECT `ref_id`, `game_id`, `developer_id` FROM `game_developer_reference` WHERE `game_id` = ?", array($game_id))->fetch_all();
			if (!empty($current_devs))
			{
				foreach ($current_devs as $current_dev)
				{
					if (!in_array($current_dev['developer_id'], $_POST['developers']))
					{
						$this->dbl->run("DELETE FROM `game_developer_reference` WHERE `developer_id` = ? AND `game_id` = ?", array($current_dev['developer_id'], $game_id));
					}
				}
			}

			// get fresh list of genres, and insert any that don't exist
			$current_devs = $this->dbl->run("SELECT `developer_id` FROM `game_developer_reference` WHERE `game_id` = ?", array($game_id))->fetch_all(PDO::FETCH_COLUMN, 0);

			if (isset($_POST['developers']) && !empty($_POST['developers']) && core::is_number($_POST['developers']))
			{
				foreach($_POST['developers'] as $developer_id)
				{
					if (!in_array($developer_id, $current_devs))
					{
						$this->dbl->run("INSERT INTO `game_developer_reference` SET `game_id` = ?, `developer_id` = ?", array($game_id, $developer_id));
					}
				}
			}
		}
	}

	function display_previous_uploads($item_id = NULL)
	{
		$previously_uploaded['output'] = '';
		$previously_uploaded['hidden'] = '';
		$item_images = NULL;
		if ($item_id != NULL)
		{
			// add in uploaded images from database
			$item_images = $this->dbl->run("SELECT `filename`,`id`,`filetype`,`item_id` FROM `itemdb_images` WHERE `item_id` = ? AND `featured` = 0 ORDER BY `id` ASC", array($item_id))->fetch_all();
		}
		else
		{
			if (isset($_SESSION['itemdb']['uploads']))
			{
				$image_ids = [];
				foreach ($_SESSION['itemdb']['uploads'] as $id)
				{
					$image_ids[] = $id;
				}
				unset($_SESSION['itemdb']['uploads']);
				$in  = str_repeat('?,', count($image_ids) - 1) . '?';
				$item_images = $this->dbl->run("SELECT `filename`,`id`,`filetype`,`featured` FROM `itemdb_images` WHERE `id` IN ($in) ORDER BY `id` ASC", $image_ids)->fetch_all();
			}
		}
		if ($item_images)
		{
			foreach($item_images as $value)
			{
				if ($item_id == NULL)
				{
					$previously_uploaded['hidden'] .= '<input class="uploads-'.$value['id'].'" type="hidden" name="uploads[]" value="'.$value['id'].'" />';
					$item_dir = 'tmp/';
				}
				else
				{
					$item_dir = $item_id . '/';
				}

				if ($value['featured'] == 0)
				{
					$thumbs_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/gamesdb/big/thumbs/" . $item_dir;

					$main_url = $this->core->config('website_url') . 'uploads/gamesdb/big/' . $item_dir . $value['filename'];
					$thumb_url = $this->core->config('website_url') . 'uploads/gamesdb/big/thumbs/' . $item_dir . $value['filename'];

					$preview_file = '<a data-fancybox="images" href="'.$main_url.'" target="_blank"><img src="' . $thumb_url . '" class="imgList"></a><br />';

					$previously_uploaded['output'] .= '<div class="box">
					<div class="body group">
					<div id="'.$value['id'].'">'.$preview_file.'
					<button id="' . $value['id'] . '" class="trash" data-type="itemdb">Delete Media</button>
					</div>
					</div>
					</div>';
				}
			}
		}
		return $previously_uploaded;
	}

	function move_tmp_media($uploads, $item_id)
	{
		$key = $this->core->config('do_space_key_uploads');
		$secret = $this->core->config('do_space_key_private_uploads');

		$client = new Aws\S3\S3Client([
				'version' => 'latest',
				'region'  => 'am3',
				'endpoint' => 'https://ams3.digitaloceanspaces.com',
				'credentials' => [
						'key'    => $key,
						'secret' => $secret,
					],
		]);

		foreach($uploads as $key)
		{
			$this->dbl->run("UPDATE `itemdb_images` SET `item_id` = ?, `approved` = 1 WHERE `id` = ?", array($item_id, $key));

			$file = $this->dbl->run("SELECT `filename`, `location`, `featured`, `filetype` FROM `itemdb_images` WHERE `id` = ?", array($key))->fetch();

			// feature item, check if there's an older one and remove it
			if ($file['featured'] == 1)
			{
				$check_featured = $this->dbl->run("SELECT `id`, `filename`, `location` FROM `itemdb_images` WHERE `id` != ? AND `featured` = 1 AND `item_id` = ?", array($key, $item_id))->fetch_all();
				if ($check_featured)
				{
					foreach ($check_featured as $old_featured)
					{
						if ($old_featured['location'] == NULL)
						{
							unlink(APP_ROOT . "/uploads/gamesdb/big/tmp/" . $old_featured['filename']);
						}
						else
						{
							$result = $client->deleteObject([
								'Bucket' => 'goluploads',
								'Key'    => 'uploads/gamesdb/big/tmp/' . $old_featured['filename']
							]);	
						}
					}
					$this->dbl->run("DELETE FROM `itemdb_images` WHERE `featured` = 1 AND `item_id` = ? AND `id` != ?", array($item_id, $key));
				}
				if ($file['location'] != NULL)
				{
					try
					{
						$mime_types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');

						$mime = $mime_types[$file['filetype']];

						$result = $client->copyObject([
							'Bucket' => 'goluploads',
							'CopySource' =>  'goluploads/uploads/gamesdb/big/tmp/' . $file['filename'],
							'Key' => 'uploads/gamesdb/big/' . $item_id . '/' . $file['filename'],
							'MetadataDirective' => 'REPLACE',
							'ACL'    => 'public-read',
							'ContentType' => $mime
						]);	

						$result = $client->deleteObject([
							'Bucket' => 'goluploads',
							'Key'    => 'uploads/gamesdb/big/tmp/' . $file['filename']
						]);							
					}
					catch (Exception $e)
					{
						error_log($e->getMessage());
					}
				}
			}
			else
			{
				$uploaddir = APP_ROOT . "/uploads/gamesdb/big/" . $item_id;
				$thumbs_dir = APP_ROOT . "/uploads/gamesdb/big/thumbs/" . $item_id;

				if (!is_dir($uploaddir))
				{
					mkdir($uploaddir, 0777);
				}
			
				if (!is_dir($thumbs_dir))
				{
					mkdir($thumbs_dir, 0777);
				}

				$tmp_full_file_big = APP_ROOT . "/uploads/gamesdb/big/tmp/" . $file['filename'];
				$full_file_big = $uploaddir . '/' . $file['filename'];

				$tmp_full_file_thumbnail = APP_ROOT . "/uploads/gamesdb/big/thumbs/tmp/" . $file['filename'];
				$full_file_thumbnail = $thumbs_dir . '/' . $file['filename'];

				rename($tmp_full_file_big, $full_file_big);
				rename($tmp_full_file_thumbnail, $full_file_thumbnail);
			}
		}
	}

	// when updating an item, this will remove older featured images left when a new one is put up as we only keep one
	function update_featured($item_id)
	{
		$check_featured = $this->dbl->run("SELECT `id`, `filename`, `location` FROM `itemdb_images` WHERE `featured` = 1 AND `item_id` = ? ORDER BY `id` ASC", array($item_id))->fetch_all();
		$count_featured = count($check_featured);
		if ($check_featured && $count_featured > 1)
		{
			$key = $this->core->config('do_space_key_uploads');
			$secret = $this->core->config('do_space_key_private_uploads');

			$client = new Aws\S3\S3Client([
					'version' => 'latest',
					'region'  => 'am3',
					'endpoint' => 'https://ams3.digitaloceanspaces.com',
					'credentials' => [
							'key'    => $key,
							'secret' => $secret,
						],
			]);

			$current = 0;
			$picture_ids = [];
			foreach ($check_featured as $old_featured)
			{
				$current++;

				if ($current != $count_featured)
				{
					if ($old_featured['location'] == NULL)
					{
						unlink(APP_ROOT . "/uploads/gamesdb/big/" . $item_id . '/' . $old_featured['filename']);
					}
					else
					{
						$result = $client->deleteObject([
							'Bucket' => 'goluploads',
							'Key'    => 'uploads/gamesdb/big/' . $item_id . '/' .  $old_featured['filename']
						]);
					}
					$picture_ids[] = $old_featured['id'];
				}
			}

			$in  = str_repeat('?,', count($picture_ids) - 1) . '?';
			$this->dbl->run("DELETE FROM `itemdb_images` WHERE `featured` = 1 AND `item_id` = ? AND `id` IN ($in)", array_merge([$item_id], $picture_ids));
		}
		$this->dbl->run("UPDATE `itemdb_images` SET `approved` = 1 WHERE `featured` = 1 AND `item_id` = ?", array($item_id));
	}

	function sort_yt_thumb($filename, $item_id)
	{
		include_once(APP_ROOT . '/includes/image_class/SimpleImage.php');
		$img = new SimpleImage();

		if (strpos($filename, 'youtube_cache_default') !== false) // we don't want to touch the standard fallback image
		{
			return false;
		}

		if (!is_dir(APP_ROOT.'/uploads/gamesdb/big/thumbs/'.$item_id))
		{
			mkdir(APP_ROOT.'/uploads/gamesdb/big/thumbs/'.$item_id, 0777);
			chmod(APP_ROOT.'/uploads/gamesdb/big/thumbs/'.$item_id, 0777);
		}

		$save_as = '/uploads/gamesdb/big/thumbs/'.$item_id.'/trailer_thumb.jpg';

		$filename = '/'.str_replace($this->core->config('website_url'),'',$filename);

		$img->fromFile($_SERVER['DOCUMENT_ROOT'].$filename)->resize(450, null)->overlay($_SERVER['DOCUMENT_ROOT'].'/templates/default/images/playbutton.png')->toFile($_SERVER['DOCUMENT_ROOT'].$save_as, 'image/jpeg');

		$this->dbl->run("UPDATE `calendar` SET `trailer_thumb` = ? WHERE `id` = ?", array($this->core->config('website_url') . $save_as, $item_id));
	}

	public function display_comments($data)
	{
		// get blocked id's
		$blocked_ids = [];
		$blocked_usernames = [];
		if (count($this->user->blocked_users) > 0)
		{
			foreach ($this->user->blocked_users as $username => $blocked_id)
			{
				$blocked_ids[] = $blocked_id[0];
				$blocked_usernames[] = $username;
			}
		}

		$total_comments = $data['item']['total_comments'];

		$per_page = 15;
		if (isset($_SESSION['per-page']) && is_numeric($_SESSION['per-page']) && $_SESSION['per-page'] > 0)
		{
			$per_page = $_SESSION['per-page'];
		}

		//lastpage is = total comments / items per page, rounded up.
		if ($total_comments <= 10)
		{
			$lastpage = 1;
		}
		else
		{
			$lastpage = ceil($total_comments/$per_page);
		}

		// paging for pagination
		if (!isset($data['page']) || $data['page'] == 0)
		{
			$page = 1;
		}

		else if (is_numeric($data['page']))
		{
			$page = $data['page'];
		}

		if ($page > $lastpage)
		{
			$page = $lastpage;
		}

		// sort out the pagination link
		$pagination = $this->core->pagination_link($per_page, $total_comments, $data['pagination_link'], $page, '#comments');
		$pagination_head = $this->core->head_pagination($per_page, $total_comments, $data['pagination_link'], $page, '#comments');

		$this->templating->block('comments_top', 'items_database');
		$this->templating->set('pagination_head', $pagination_head);
		$this->templating->set('pagination', $pagination);

		$subscribe_link = '';
		$close_comments_link = '';

		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
		{
			// they're logged in, so let's see if they're subscribed to the article
			$check_sub = $this->dbl->run("SELECT `send_email` FROM `itemdb_subscriptions` WHERE `user_id` = ? AND `item_id` = ?", array((int) $_SESSION['user_id'], (int) $data['item']['id']))->fetch();
			if ($check_sub)
			{
				// update their subscriptions if they are reading the last page
				if ($_SESSION['email_options'] == 2 && $check_sub['send_email'] == 0)
				{
					// they have read all new comments (or we think they have since they are on the last page)
					if ($page == $lastpage)
					{
						// send them an email on a new comment again
						$this->dbl->run("UPDATE `itemdb_subscriptions` SET `send_email` = 1 WHERE `user_id` = ? AND `item_id` = ?", array((int) $_SESSION['user_id'], (int) $data['item']['id']));
					}
				}
				// they're subscribed, so set the quick link to unsubscribe
				$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"unsubscribe\" data-article-id=\"{$data['item']['id']}\" href=\"/index.php?module=articles_full&amp;go=unsubscribe&amp;article_id={$data['item']['id']}\" class=\"white-link\"><span class=\"link_button\">Unsubscribe</span></a>";
			}
			// they're not subscribed, so set the quick link to subscribe
			else
			{
				$subscribe_link = "<a id=\"subscribe-link\" data-sub=\"subscribe\" data-article-id=\"{$data['item']['id']}\" href=\"/index.php?module=articles_full&amp;go=subscribe&amp;article_id={$data['item']['id']}\" class=\"white-link\"><span class=\"link_button\">Subscribe</span></a>";
			}
		}
		$this->templating->set('subscribe_link', $subscribe_link);

		//
		/* DISPLAY THE COMMENTS */
		//

		// first grab a list of their bookmarks
		if ($total_comments > 0 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$bookmarks_array = $this->dbl->run("SELECT `data_id` FROM `user_bookmarks` WHERE `type` = 'itemdb_comment' AND `parent_id` = ? AND `user_id` = ?", array((int) $data['item']['id'], (int) $_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN);
		}

		$profile_fields = include dirname ( dirname ( __FILE__ ) ) . '/includes/profile_fields.php';

		$db_grab_fields = '';
		foreach ($profile_fields as $field)
		{
			$db_grab_fields .= "u.`{$field['db_field']}`,";
		}

		$params = array_merge([(int) $data['item']['id']], [$this->core->start], [$per_page]);

		$comments_get = $this->dbl->run("SELECT ic.author_id, ic.comment_text, ic.comment_id, u.pc_info_public, u.distro, ic.time_posted, ic.last_edited, ic.last_edited_time, ic.`total_likes`, u.username, u.`avatar`,  $db_grab_fields u.`avatar_uploaded`, u.`avatar_gallery`, u.pc_info_filled, u.game_developer, u.register_date, ul.username as username_edited FROM `itemdb_comments` ic LEFT JOIN `users` u ON ic.author_id = u.user_id LEFT JOIN `users` ul ON ul.user_id = ic.last_edited WHERE ic.`item_id` = ? AND ic.approved = 1 ORDER BY ic.`time_posted` ASC LIMIT ?, ?", $params)->fetch_all();

		// make an array of all comment ids and user ids to search for likes (instead of one query per comment for likes) and user groups for badge displaying
		$like_array = [];
		$sql_replacers = [];
		$user_ids = [];

		foreach ($comments_get as $id_loop)
		{
			// no point checking for if they've liked a comment, that has no likes
			if ($id_loop['total_likes'] > 0)
			{
				$like_array[] = (int) $id_loop['comment_id'];
				$sql_replacers[] = '?';
			}
			$user_ids[] = (int) $id_loop['author_id'];
		}

		/* total comments indicator */
		$total_hidden = 0;
		if (!empty($user_ids) && !empty($blocked_ids))
		{
			foreach ($user_ids as $user_id)
			{
				if (in_array($user_id, $blocked_ids))
				{
					$total_hidden++;
				}
			}
		}

		$comments_top_text = '';
		if ($total_comments > 0)
		{
			$comments_top_text = number_format($total_comments) . ' comment';
			if ($total_comments > 1)
			{
				$comments_top_text .= 's';
			}

			if ($total_hidden > 0)
			{
				$comments_top_text .= ' ('.$total_hidden.' hidden)';
			}
		}
		else
		{
			$comments_top_text = 'No comments yet!';
		}
		$this->templating->set('comments_top_text', $comments_top_text);

		if (!empty($like_array))
		{
			$to_replace = implode(',', $sql_replacers);

			// get this users likes
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
			{
				$replace = [$_SESSION['user_id']];
				foreach ($like_array as $comment_id)
				{
					$replace[] = $comment_id;
				}

				$get_user_likes = $this->dbl->run("SELECT `data_id` FROM `likes` WHERE `user_id` = ? AND `data_id` IN ( $to_replace ) AND `type` = 'comment'", $replace)->fetch_all(PDO::FETCH_COLUMN);
			}
		}

		// get a list of each users user groups, so we can display their badges
		if (!empty($user_ids))
		{
			$comment_user_groups = $this->user->post_group_list($user_ids);
		}

		// check over their permissions now
		$permission_check = $this->user->can(array('mod_delete_comments', 'mod_edit_comments'));

		$can_delete = 0;
		if ($permission_check['mod_delete_comments'] == 1)
		{
			$can_delete = 1;
		}
		$can_edit = 0;
		if ($permission_check['mod_edit_comments'] == 1)
		{
			$can_edit = 1;
		}

		foreach ($comments_get as $comments)
		{
			$comment_date = $this->core->time_ago($comments['time_posted']);

			if (in_array($comments['author_id'], $blocked_ids))
			{
				$this->templating->block('blocked_comment', 'articles_full');
			}
			else
			{
				$this->templating->block('article_comments', 'articles_full');
			}
			// remove blocked users quotes
			if (count($blocked_usernames) > 0)
			{
				foreach($blocked_usernames as $username)
				{

					$capture_quotes = preg_split('~(\[/?quote[^]]*\])~', $comments['comment_text'], NULL, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

					// need to count the amount of [quote=?
					// each time we hit [/quote] take one away from counter
					// snip the comment between the start and the final index number?
					foreach ($capture_quotes as $index => $quote)
					{
						if(!isset($start) && $quote == "[quote={$username}]")
						{
							$start = $index;
							$opens = 1;
						}
						else if(isset($start))
						{
							if(strpos($quote,'[quote=') !== false || strpos($quote,'[quote]') !== false)
							{
								++$opens;
							}
							else if(strpos($quote,'[/quote]')!==false)
							{
								--$opens;
								if($opens == 0)
								{
									$capture_quotes = array_diff_key($capture_quotes,array_flip(range($start,$index)));
									$comments['comment_text'] = trim(implode($capture_quotes));
									unset($start);
								}
							}
						}
					}
				}
			}

			if ($comments['author_id'] == 0 || empty($comments['username']))
			{
				if (empty($comments['username']))
				{
					$username = 'Guest';
				}
				if (!empty($comments['guest_username']))
				{
					if ($this->user->check_group([1,2]) == true)
					{
						$username = "<a href=\"/admin.php?module=articles&view=comments&ip_id={$comments['comment_id']}\">{$comments['guest_username']}</a>";
					}
					else
					{
						$username = $comments['guest_username'];
					}
				}
				$quote_username = $comments['guest_username'];
			}
			else
			{
				$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
				$quote_username = $comments['username'];
			}

			// sort out the avatar
			$comment_avatar = $this->user->sort_avatar($comments);

			$into_username = '';
			if (!empty($comments['distro']) && $comments['distro'] != 'Not Listed')
			{
				$into_username .= '<img title="' . $comments['distro'] . '" class="distro tooltip-top"  alt="" src="' . $this->core->config('website_url') . 'templates/'.$this->core->config('template').'/images/distros/' . $comments['distro'] . '.svg" />';
			}

			$pc_info = '';
			if (isset($comments['pc_info_public']) && $comments['pc_info_public'] == 1)
			{
				if ($comments['pc_info_filled'] == 1)
				{
					$pc_info = '<a class="computer_deets" data-fancybox data-type="ajax" href="javascript:;" data-src="'.$this->core->config('website_url').'includes/ajax/call_profile.php?user_id='.$comments['author_id'].'">View PC info</a>';
				}
			}


			$this->templating->set('user_id', $comments['author_id']);
			$this->templating->set('username', $into_username . $username);
			$this->templating->set('comment_avatar', $comment_avatar);
			$this->templating->set('user_info_extra', $pc_info);

			$cake_bit = '';
			if ($username != 'Guest')
			{
				$cake_bit = $this->user->cake_day($comments['register_date'], $comments['username']);
			}
			$this->templating->set('cake_icon', $cake_bit);

			$last_edited = '';
			if ($comments['last_edited'] != 0)
			{
				$last_edited = "\r\n\r\n\r\n[i]Last edited by " . $comments['username_edited'] . ' on ' . $this->core->human_date($comments['last_edited_time']) . '[/i]';
			}

			$this->templating->set('article_id', $article_info['article']['article_id']);
			$this->templating->set('comment_id', $comments['comment_id']);

			$this->templating->set('total_likes', $comments['total_likes']);

			$who_likes_link = '';
			if ($comments['total_likes'] > 0)
			{
				$who_likes_link = ', <a class="who_likes" href="/index.php?module=who_likes&amp;comment_id='.$comments['comment_id'].'" data-fancybox data-type="ajax" href="javascript:;" data-src="/includes/ajax/who_likes.php?comment_id='.$comments['comment_id'].'">Who?</a>';
			}
			$this->templating->set('who_likes_link', $who_likes_link);

			$likes_hidden = '';
			if ($comments['total_likes'] == 0)
			{
				$likes_hidden = ' likes_hidden ';
			}
			$this->templating->set('hidden_likes_class', $likes_hidden);

			$logged_in_options = '';
			$bookmark_comment = '';
			$report_link = '';
			$comment_edit_link = '';
			$like_button = '';
			$comment_delete_link = '';
			$link_to_comment = '';
			$permalink = $this->article_link(array('date' => $article_info['article']['date'], 'slug' => $article_info['article']['slug'], 'additional' => 'comment_id=' . $comments['comment_id']));
			if (isset($article_info['type']) && $article_info['type'] != 'admin')
			{
				$link_to_comment = '<li><a class="post_link tooltip-top" data-fancybox data-type="ajax" href="'.$permalink.'" data-src="/includes/ajax/call_post_link.php?post_id=' . $comments['comment_id'] . '&type=comment" title="Link to this comment"><span class="icon link">Link</span></a></li>';
			}
			$this->templating->set('link_to_comment', $link_to_comment);
			$block_icon = '';
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
			{
				$logged_in_options = $this->templating->block_store('logged_in_options', 'articles_full');

				if (isset($article_info['type']) && $article_info['type'] != 'admin')
				{
					// sort bookmark icon out
					if (in_array($comments['comment_id'], $bookmarks_array))
					{
						$bookmark_comment = '<li><a href="#" class="bookmark-content tooltip-top bookmark-saved" data-page="normal" data-type="comment" data-id="'.$comments['comment_id'].'" data-parent-id="'.$article_info['article']['article_id'].'" data-method="remove" title="Remove Bookmark"><span class="icon bookmark"></span></a></li>';
					}
					else
					{
						$bookmark_comment = '<li><a href="#" class="bookmark-content tooltip-top" data-page="normal" data-type="comment" data-id="'.$comments['comment_id'].'" data-parent-id="'.$article_info['article']['article_id'].'" data-method="add" title="Bookmark"><span class="icon bookmark"></span></a></li>';
					}

					// block icon
					$block_icon = '';
					if ($_SESSION['user_id'] != $comments['author_id'])
					{
						$block_icon = '<li><a class="tooltip-top" href="/index.php?module=block_user&block='.$comments['author_id'].'" title="Block User"><span class="icon block"></span></a></li>';
					}

					$like_text = "Like";
					$like_class = "like";
					if ($_SESSION['user_id'] != 0)
					{
						if (isset($get_user_likes) && in_array($comments['comment_id'], $get_user_likes))
						{
							$like_text = "Unlike";
							$like_class = "unlike";
						}
						else
						{
							$like_text = "Like";
							$like_class = "like";
						}
					}

					// don't let them like their own post
					if ($comments['author_id'] != $_SESSION['user_id'])
					{
						$like_button = '<li class="lb-container" style="display:none !important"><a class="plusone tooltip-top" data-type="comment" data-id="'.$comments['comment_id'].'" data-article-id="'.$article_info['article']['article_id'].'" data-author-id="'.$comments['author_id'].'" title="Like"><span class="icon '.$like_class.'">'.$like_text.'</span></a></li>';
					}

					$report_link = "<li><a class=\"tooltip-top\" href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&amp;go=report_comment&amp;article_id={$article_info['article']['article_id']}&amp;comment_id={$comments['comment_id']}\" title=\"Report\"><span class=\"icon flag\">Flag</span></a></li>";

					if ($_SESSION['user_id'] == $comments['author_id'] || $can_edit == 1)
					{
						$comment_edit_link = "<li><a class=\"tooltip-top edit_comment_link\" data-comment-id=\"{$comments['comment_id']}\" title=\"Edit\" href=\"" . $this->core->config('website_url') . "index.php?module=edit_comment&amp;view=Edit&amp;comment_id={$comments['comment_id']}\"><span class=\"icon edit\">Edit</span></a></li>";
					}

					if ($can_delete == 1 || $_SESSION['user_id'] == $comments['author_id'])
					{
						$comment_delete_link = "<li><a class=\"tooltip-top delete_comment\" title=\"Delete\" href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\" data-comment-id=\"{$comments['comment_id']}\"><span class=\"icon delete\"></span></a></li>";
					}
				}

				$logged_in_options = $this->templating->store_replace($logged_in_options, array('post_id' => $comments['comment_id'], 'like_button' => $like_button, 'article_id' => $article_info['article']['article_id']));
			}
			$this->templating->set('logged_in_options', $logged_in_options);
			$this->templating->set('bookmark', $bookmark_comment);
			$this->templating->set('edit', $comment_edit_link);
			$this->templating->set('delete', $comment_delete_link);
			$this->templating->set('report_link', $report_link);
			$this->templating->set('block', $block_icon);

			// if we have some user groups for that user
			$comments['user_groups'] = $comment_user_groups[$comments['author_id']];
			$badges = user::user_badges($comments, 1);
			$this->templating->set('badges', implode(' ', $badges));

			$profile_fields_output = user::user_profile_icons($profile_fields, $comments);

			$this->templating->set('profile_fields', $profile_fields_output);

			// do this last, to help stop templating tags getting parsed in user text
			$this->templating->set('text', $this->bbcode->parse_bbcode($comments['comment_text'] . $last_edited, 0));

			$this->templating->set('date', $comment_date);
			$this->templating->set('tzdate', date('c',$comments['time_posted']) );
		}

		$this->templating->block('bottom', 'articles_full');
		$this->templating->set('pagination', $pagination);

		if (isset($article_info['type']) && $article_info['type'] != 'admin' && $this->user->check_group([6,9]) === false)
		{
			$this->templating->block('patreon_comments', 'articles_full');
		}
    }
    
    function render_proton_comments($steam_id, $total_comments)
	{
        $per_page = 15;
		if (isset($_SESSION['per-page']) && is_numeric($_SESSION['per-page']) && $_SESSION['per-page'] > 0)
		{
			$per_page = $_SESSION['per-page'];
		}

		//lastpage is = total comments / items per page, rounded up.
		if ($total_comments <= 10)
		{
			$lastpage = 1;
		}
		else
		{
			$lastpage = ceil($total_comments/$per_page);
		}
        
		// paging for pagination
		if (!isset($article_info['page']) || $article_info['page'] == 0)
		{
			$page = 1;
		}

		else if (is_numeric($article_info['page']))
		{
			$page = $article_info['page'];
		}

		if ($page > $lastpage)
		{
			$page = $lastpage;
        }
        
        $profile_fields = include dirname ( dirname ( __FILE__ ) ) . '/includes/profile_fields.php';

		$db_grab_fields = '';
		foreach ($profile_fields as $field)
		{
			$db_grab_fields .= "u.`{$field['db_field']}`,";
		}

        $params = array_merge([(int) $steam_id], [$this->core->start], [$per_page]);
        
        $comments_get = $this->dbl->run("SELECT r.`author_id`, r.`comment`, r.`report_id`, r.`single_works`, r.`multi_works`, u.`pc_info_public`, u.`distro`, r.`report_date`, r.`last_edited_by`, r.`last_edited_time`, v.version, u.`username`, u.`profile_address`, u.`avatar`, $db_grab_fields u.`avatar_uploaded`, u.`avatar_gallery`, u.`pc_info_filled`, u.`game_developer`, u.`register_date`, ul.`username` as `username_edited` FROM `proton_reports` r INNER JOIN `proton_versions` v ON r.proton_id = v.id LEFT JOIN `users` u ON r.`author_id` = u.`user_id` LEFT JOIN `users` ul ON ul.`user_id` = r.`last_edited_by` WHERE r.`steam_appid` = ? ORDER BY r.`report_date` DESC LIMIT ?,?", $params)->fetch_all();

		// check over their permissions now
		$permission_check = $this->user->can(array('mod_delete_comments', 'mod_edit_comments'));

        // get blocked id's
		$blocked_ids = [];
		$blocked_usernames = [];
		if (count($this->user->blocked_users) > 0)
		{
			foreach ($this->user->blocked_users as $username => $blocked_id)
			{
				$blocked_ids[] = $blocked_id[0];
				$blocked_usernames[] = $username;
			}
        }

		$can_delete = 0;
		if ($permission_check['mod_delete_comments'] == 1)
		{
			$can_delete = 1;
		}
		$can_edit = 0;
		if ($permission_check['mod_edit_comments'] == 1)
		{
			$can_edit = 1;
		}

		foreach ($comments_get as $comments)
		{
			$comment_date = $this->core->time_ago(strtotime($comments['report_date']));

			if (in_array($comments['author_id'], $this->user->blocked_user_ids))
			{
				$this->templating->block('blocked_comment', 'steamplay_reports');
			}
			else
			{
				$this->templating->block('comment', 'steamplay_reports');
			}
			// remove blocked users quotes
			if (count($this->user->blocked_usernames) > 0)
			{
				foreach($this->user->blocked_usernames as $username)
				{

					$capture_quotes = preg_split('~(\[/?quote[^]]*\])~', $comments['comment'], NULL, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

					// need to count the amount of [quote=?
					// each time we hit [/quote] take one away from counter
					// snip the comment between the start and the final index number?
					foreach ($capture_quotes as $index => $quote)
					{
						if(!isset($start) && $quote == "[quote={$username}]")
						{
							$start = $index;
							$opens = 1;
						}
						else if(isset($start))
						{
							if(strpos($quote,'[quote=') !== false || strpos($quote,'[quote]') !== false)
							{
								++$opens;
							}
							else if(strpos($quote,'[/quote]')!==false)
							{
								--$opens;
								if($opens == 0)
								{
									$capture_quotes = array_diff_key($capture_quotes,array_flip(range($start,$index)));
									$comments['comment'] = trim(implode($capture_quotes));
									unset($start);
								}
							}
						}
					}
				}
			}

			if ($comments['author_id'] == 0 || empty($comments['username']))
			{
				if (empty($comments['username']))
				{
					$username = 'Guest';
				}
				if (!empty($comments['guest_username']))
				{
					if ($this->user->check_group([1,2]) == true)
					{
						$username = "<a href=\"/admin.php?module=articles&view=comments&ip_id={$comments['comment_id']}\">{$comments['guest_username']}</a>";
					}
					else
					{
						$username = $comments['guest_username'];
					}
				}
				$quote_username = $comments['guest_username'];
			}
			else
			{
                if (isset($comments['profile_address']) && !empty($comments['profile_address']))
                {
                    $profile_address = '/profiles/' . $comments['profile_address'];
                }
                else
                {
                    $profile_address = '/profiles/' . $comments['author_id'];
                }
				$username = "<a href=\"".$profile_address."\">{$comments['username']}</a>";
				$quote_username = $comments['username'];
			}

			// sort out the avatar
			$comment_avatar = $this->user->sort_avatar($comments);

			$into_username = '';
			if (!empty($comments['distro']) && $comments['distro'] != 'Not Listed')
			{
				$into_username .= '<img title="' . $comments['distro'] . '" class="distro tooltip-top"  alt="" src="' . $this->core->config('website_url') . 'templates/'.$this->core->config('template').'/images/distros/' . $comments['distro'] . '.svg" />';
			}

			$pc_info = '';
			if (isset($comments['pc_info_public']) && $comments['pc_info_public'] == 1)
			{
				if ($comments['pc_info_filled'] == 1)
				{
					$pc_info = '<a class="computer_deets" data-fancybox data-type="ajax" href="javascript:;" data-src="'.$this->core->config('website_url').'includes/ajax/call_profile.php?user_id='.$comments['author_id'].'">View PC info</a>';
				}
			}

			$this->templating->set('user_id', $comments['author_id']);
			$this->templating->set('username', $into_username . $username);
			$this->templating->set('comment_avatar', $comment_avatar);
			$this->templating->set('user_info_extra', $pc_info);

			$cake_bit = '';
			if ($username != 'Guest')
			{
				$cake_bit = $this->user->cake_day($comments['register_date'], $comments['username']);
			}
			$this->templating->set('cake_icon', $cake_bit);

			$last_edited = '';
			if ($comments['last_edited_by'] != 0)
			{
				$last_edited = "\r\n\r\n\r\n[i]Last edited by " . $comments['username_edited'] . ' on ' . $this->core->human_date($comments['last_edited_time']) . '[/i]';
			}

			$this->templating->set('comment_id', $comments['report_id']);

			$logged_in_options = '';
			$bookmark_comment = '';
			$report_link = '';
			$comment_edit_link = '';
			$comment_delete_link = '';
			$link_to_comment = '';
            $permalink = '/steamplay/reports/' . $steam_id . '/report_id=' . $comments['report_id'];
            			
			$link_to_comment = '<li><a class="post_link tooltip-top" data-fancybox data-type="ajax" href="'.$permalink.'" data-src="/includes/ajax/call_post_link.php?post_id=' . $comments['report_id'] . '&type=protonreport" title="Link to this comment"><span class="icon link">Link</span></a></li>';
			
			$this->templating->set('link_to_comment', $link_to_comment);
			$block_icon = '';
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
			{
                // block icon
                $block_icon = '';
                if ($_SESSION['user_id'] != $comments['author_id'])
                {
                    $block_icon = '<li><a class="tooltip-top" href="/index.php?module=block_user&block='.$comments['author_id'].'" title="Block User"><span class="icon block"></span></a></li>';
                }
                
                $report_link = "<li><a class=\"tooltip-top\" href=\"" . $this->core->config('website_url') . "steamplay_reports.php&amp;go=report_comment&amp;steam_id={$steam_id}&amp;comment_id={$comments['report_id']}\" title=\"Report\"><span class=\"icon flag\">Flag</span></a></li>";

                if ($_SESSION['user_id'] == $comments['author_id'] || $can_edit == 1)
                {
                    $comment_edit_link = "<li><a class=\"tooltip-top edit_comment_link\" data-comment-id=\"{$comments['report_id']}\" title=\"Edit\" href=\"" . $this->core->config('website_url') . "index.php?module=edit_comment&amp;view=Edit&amp;comment_id={$comments['report_id']}\"><span class=\"icon edit\">Edit</span></a></li>";
                }

                if ($can_delete == 1 || $_SESSION['user_id'] == $comments['author_id'])
                {
                    $comment_delete_link = "<li><a class=\"tooltip-top delete_comment\" title=\"Delete\" href=\"" . $this->core->config('website_url') . "steamplay_reports.php?&amp;go=deletereport&amp;report_id={$comments['report_id']}\" data-comment-id=\"{$comments['report_id']}\"><span class=\"icon delete\"></span></a></li>";
                }
			}
			$this->templating->set('edit', $comment_edit_link);
			$this->templating->set('delete', $comment_delete_link);
			$this->templating->set('report_link', $report_link);
			$this->templating->set('block', $block_icon);

            // if we have some user groups for that user
            if (isset($comment_user_groups[$comments['author_id']]))
            {
                $comments['user_groups'] = $comment_user_groups[$comments['author_id']];
                $badges = user::user_badges($comments, 1);
                $this->templating->set('badges', implode(' ', $badges));
            }
            else
            {
                $this->templating->set('badges', '');
            }

			$profile_fields_output = user::user_profile_icons($profile_fields, $comments);

            $this->templating->set('profile_fields', $profile_fields_output);
            
            $report_info = '<br /><br /><blockquote><p><strong>Report info</strong></p><ul>';
            $report_info .= '<li>Proton version: ' . $comments['version'] . '</li>';
            if (isset($comments['single_works']))
            {
                $report_info .= '<li>Singleplayer</li>';
                if ($comments['single_works'] == 1)
                {
                    $report_info .= '<ul><li>Works without issues</li></ul>';
                }
                if ($comments['single_works'] == 0)
                {
                    $report_info .= '<ul><li>Has issues</li></ul>';
                }
            }
            if (isset($comments['multi_works']))
            {
                $report_info .= '<li>Multiplayer</li>';
                if ($comments['multi_works'] == 1)
                {
                    $report_info .= '<ul><li>Works without issues</li></ul>';
                }
                if ($comments['multi_works'] == 0)
                {
                    $report_info .= '<ul><li>Has issues</li></ul>';
                }               
            }
        
            $report_info .= '</ul></blockquote>';

            $this->templating->set('report_info', $report_info);

			// do this last, to help stop templating tags getting parsed in user text
			$this->templating->set('text', $this->bbcode->parse_bbcode($comments['comment'] . $last_edited, 0));

			$this->templating->set('date', $comment_date);
			$this->templating->set('tzdate', date('c', strtotime($comments['report_date'])) );
		}
	}
}