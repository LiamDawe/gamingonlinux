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

		$nodlc_checked = '';
		$less5_selected = '';
		$less10_selected = '';
		if (isset($filters_sort) && is_array($filters_sort))
		{
			$options_array = [];
			$options_link = [];
			foreach ($filters_sort['option'] as $option)
			{
				if ($option == '5less')
				{
					$options_array[] = ' s.`sale_dollars` <= 5 ';
					$less5_selected = 'selected';
					$options_link[] = 'option[]=5less';
				}
				if ($option == '10less')
				{
					$options_array[] = ' s.`sale_dollars` <= 10 ';
					$less10_selected = 'selected';
					$options_link[] = 'option[]=10less';
				}
				if ($option == 'nodlc')
				{
					$options_array[] = ' c.`is_dlc` = 0 ';
					$nodlc_checked = 'checked';
					$options_link[] = 'option[]=nodlc';
				}
			}
		}
		$this->templating->set('less5_selected', $less5_selected);
		$this->templating->set('less10_selected', $less10_selected);
		$this->templating->set('nodlc_checked', $nodlc_checked);

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
			$sales_res = $this->dbl->run("SELECT c.id as game_id, c.name, c.is_dlc, c.small_picture, s.`sale_dollars`, s.original_dollars, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id WHERE c.`name` LIKE ? $options_sql ORDER BY s.`sale_dollars` ASC", [$where])->fetch_all();
		}
		else
		{
			$options_sql = '';
			if (!empty($options_array))
			{
				$options_sql = ' WHERE ' . implode(' AND ', $options_array);
			}
			$sales_res = $this->dbl->run("SELECT c.id as game_id, c.name, c.is_dlc, c.small_picture, s.`sale_dollars`, s.original_dollars, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id $options_sql ORDER BY s.`sale_dollars` ASC")->fetch_all();
		}

		$sales_merged = [];
		foreach ($sales_res as $sale)
		{
			$sales_merged[$sale['name']][] = ['game_id' => $sale['game_id'], 'store' => $sale['store_name'], 'sale_dollars' => $sale['sale_dollars'], 'original_dollars' => $sale['original_dollars'], 'link' => $sale['link'], 'is_dlc' => $sale['is_dlc'], 'picture' => $sale['small_picture']];
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
				$small_pic = $this->core->config('website_url') . 'uploads/sales/' . $sales[0]['game_id'] . '.jpg';
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
				if ($store['original_dollars'] != 0)
				{
					$savings = 1 - ($store['sale_dollars'] / $store['original_dollars']);
					$savings_dollars = round($savings * 100) . '% off';
				}

				$dlc = '';
				if ($store['is_dlc'] == 1)
				{
					$dlc = '<span class="badge yellow">DLC</span>';
				}

				$stores_output .= ' <span class="badge"><a href="'.$store['link'].'" target="_blank">'.$store['store'].' - $'.$store['sale_dollars'] . ' | ' . $savings_dollars . '</a></span> ';
			}
			$this->templating->set('stores', $dlc . $stores_output);

			$this->templating->set('lowest_price', $sales[0]['sale_dollars']);
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