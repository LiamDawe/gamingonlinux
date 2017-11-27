<?php
class game_sales
{
	protected $templating;
	protected $dbl;
	protected $user;
	protected $core;

	function __construct($dbl, $templating, $user, $core)
	{
		$this->dbl = $dbl;
		$this->templating = $templating;
		$this->user = $user;
		$this->core = $core;
	}

	function clean_title($title)
	{
		$title = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $title); // remove junk
		$title = trim($title); // some stores give a random space
		return $title;
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

		// paging for pagination
		$page = isset($_GET['page'])?intval($_GET['page']-1):0;

		$total_rows = $this->dbl->run("SELECT COUNT(id) FROM `calendar` WHERE `free_game` = 1 ORDER BY `name` ASC")->fetchOne();

		$link_extra = '';
		$pagination = $this->core->pagination_link(50, $total_rows, '/index.php?module=free_games&', $page + 1, $link_extra);

		$games_res = $this->dbl->run("SELECT `id`, `name`, `link`, `gog_link`, `steam_link`, `itch_link`, `license`, `small_picture`, `trailer` FROM `calendar` WHERE `free_game` = 1 ORDER BY `name` ASC LIMIT {$this->core->start}, 50")->fetch_all();

		if ($games_res)
		{
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
			}
		}
		else
		{
			$this->core->message("We aren't listing any free games at the moment, come back soon!");
		}

		$this->templating->block('bottom', 'free_games');
		if ($pagination != '')
		{
			$pagination = '<div class="free-games-pagination">'.$pagination.'</div>';
		}
		$this->templating->set('pagination', $pagination);
	}

	function display_normal($filters = NULL)
	{
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

		$where = '';
		if (isset($_GET['q']))
		{
			$options_sql = '';
			if (!empty($options_array))
			{
				$options_sql = implode(' AND ', $options_array);
			}

			$search_query = str_replace('+', ' ', $_GET['q']);
			$where = '%'.$search_query.'%';
			$sales_res = $this->dbl->run("SELECT c.id as game_id, c.`name`, c.`is_dlc`, c.`small_picture`, s.`$sale_price_field`, s.$original_price_field, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id WHERE c.`free_game` = 0 AND c.`name` LIKE ? AND s.`$sale_price_field` IS NOT NULL $options_sql ORDER BY s.`$sale_price_field` ASC", [$where])->fetch_all();
		}
		else
		{
			$options_sql = '';
			if (!empty($options_array))
			{
				$options_sql = ' AND ' . implode(' AND ', $options_array);
			}
			$sales_sql = "SELECT c.id as game_id, c.`name`, c.`is_dlc`, c.`small_picture`, s.`$sale_price_field`, s.$original_price_field, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id WHERE c.`free_game` = 0 AND s.`$sale_price_field` IS NOT NULL $options_sql $stores_sql ORDER BY s.`$sale_price_field` ASC";
			$sales_res = $this->dbl->run($sales_sql, $store_ids)->fetch_all();
		}

		$sales_merged = [];
		foreach ($sales_res as $sale)
		{
			$sales_merged[$sale['name']][] = ['game_id' => $sale['game_id'], 'store' => $sale['store_name'], 'sale_price' => $sale[$sale_price_field], 'original_price' => $sale[$original_price_field], 'link' => $sale['link'], 'is_dlc' => $sale['is_dlc'], 'picture' => $sale['small_picture']];
		}

		// paging for pagination
		$page = isset($_GET['page'])?intval($_GET['page']-1):0;

		$total_rows = count($sales_merged);

		$this->templating->set('total', $total_rows);

		//foreach ($sales_merged as $name => $sales)
		foreach (array_slice($sales_merged, $page*50, 50) as $name => $sales)
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

		$pagination = $this->core->pagination_link(50, $total_rows, 'sales.php?', $page + 1, $link_extra);

		if ($pagination != '')
		{
			$pagination = '<div class="sales-pagination">'.$pagination.'</div>';
		}
		$this->templating->set('pagination', $pagination);
	}
}