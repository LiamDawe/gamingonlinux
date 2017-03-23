<?php
class golchart
{
	private $chart_info;
	private $labels_raw_data;
	private $labels = [];
	private $data_counts = [];
	private $chart_options = [];
	private $chart_height = 0;
	private $outlines_x = 0;
	private $axis_outline_y = 0;
	private $strokes_array = [];
	private $labels_output_array = [];
	private $bars_output_array = [];
	private $counter_array =[];
	private $label_splits_array = [];
	private $divisions = '';
	private $y_axis_label_y = 0;
	private $label_y_start = 0;
	private $label_y_increment = 38;
	private $scale = 0;
	private $bottom_axis_numbers_y = 0;
	private $total_bars = 0;
	private $bars_x_start = 0;
	private $chart_bar_start_x = 0;
	private $biggest_label = 0;
	private $actual_chart_space = 0;
	private $shown_labels = 0;
	private $max_data = 0;
	private $tick_spacing = 0;
	private $ticks_total = 0;
	private $value_per_tick = 0;
	private $data_series = [];
	private $biggest_series = 0;
	private $chart_end_x = 0;
	private $get_labels_old;
	private $chart_info_old;
	
	function setup($custom_options = NULL)
	{
		// set some defaults
		$this->chart_options['colours'] = [
		'#a6cee3',
		'#1f78b4',
		'#b2df8a',
		'#33a02c',
		'#fb9a99',
		'#e31a1c',
		'#fdbf6f',
		'#ff7f00',
		'#cab2d6',
		'#6a3d9a'];
		
		$this->chart_options['title_color'] = '';
		
		$this->chart_options['chart_width'] = 600;
		$this->chart_options['title_background_height'] = 25;
		$this->chart_options['bar_thickness'] = 30;
		$this->chart_options['padding_bottom'] = 10;
		$this->chart_options['padding_right'] = 50;
		$this->chart_options['bar_counter_left_padding'] = 5;
		$this->chart_options['counter_font_size'] = 15;
		$this->chart_options['tick_font_size'] = 15;
		$this->chart_options['label_right_padding'] = 3;
		$this->chart_options['label_left_padding'] = 5;
		$this->chart_options['label_font_size'] = 15;
		$this->chart_options['ticks_total'] = 5;
		$this->chart_options['show_top_10'] = 0;
		$this->chart_options['use_percentages'] = 0;
		$this->chart_options['order'] = 'DESC';
		
		// sort out any custom options passed to us
		if (isset($custom_options) && is_array($custom_options))
		{
			foreach ($custom_options as $option => $value)
			{
				$this->chart_options[$option] = $value;
			}
		}
		
		$this->label_y_start = $this->chart_options['title_background_height'] * 2;
	}
	
	function get_chart($chart_id, $type = 'normal')
	{
		global $db;
		
		if ($type == 'normal')
		{
			$db->sqlquery("SELECT `id`, `name`, `sub_title`, `h_label`, `grouped` FROM `charts` WHERE `id` = ?", array($chart_id));
		}
		if ($type == 'stat_chart')
		{
			$db->sqlquery("SELECT `name`, `sub_title`, `h_label`, `generated_date`, `total_answers`, `grouped` FROM `user_stats_charts` WHERE `id` = ?", array($chart_id));
		}
		$this->chart_info = $db->fetch();
	}
	
	 function get_labels($chart_id, $labels_table, $data_table)
	{
		global $db;
		
		// set the right labels to the right data
		$db->sqlquery("SELECT l.`label_id`, l.`name`, d.`data`, d.`data_series` FROM `".$labels_table."` l LEFT JOIN `".$data_table."` d ON d.label_id = l.label_id WHERE l.`chart_id` = ? ORDER BY d.`data` " . $this->chart_options['order'], array($chart_id));
		$this->labels_raw_data = $db->fetch_all_rows();
		
		// if we are requesting the top 10, cut it down
		$get_labels = $this->labels_raw_data;
		if ($this->chart_options['show_top_10'] == 1)
		{
			$get_labels = array_slice($this->labels_raw_data, -10);
		}
		
		// make up the data array of labels for this chart
		foreach ($get_labels as $label_loop)
		{
			if ($this->chart_info['grouped'] == 0)
			{
				$this->labels[]['name'] = $label_loop['name'];
				end($this->labels);
				$last_id=key($this->labels);
				$this->labels[$last_id]['total'] = $label_loop['data'];
			}
			else
			{
				$this->labels[$label_loop['name']][$label_loop['data_series']] = $label_loop['data'];
				if (!in_array($label_loop['data_series'], $this->data_series))
				{
					$this->data_series[] = $label_loop['data_series'];
				}
			}
			$this->data_counts[] = $label_loop['data'];
		}
		
		$this->max_data = $this->data_counts[0];
		
		// total number of answers for this chart
		$total_counter = 0;
		foreach ($this->labels_raw_data as $raw)
		{
			$total_counter = $total_counter + $raw['data'];
		}
		if ($this->chart_info['grouped'] == 0)
		{
			// work out the percentage value of each label based on total answers against each labels data
			foreach ($this->labels as $key => $label)
			{
				$this->labels[$key]['percent'] = round(($label['total'] / $total_counter) * 100, 2);
			}
		}
		
		// get the max number of labels to make the axis and bars, and the chart height
		if ($this->chart_info['grouped'] == 0)
		{
			$this->total_bars = count($this->labels);
			$this->shown_labels = count($this->labels);
		}
		else
		{
			// for grouped data we need extra space for each bar in the series (so this just counts all the series, the adjusts to remove the outer labels)
			$label_temp_count = count($this->labels);
			$data_series_count = count($this->labels, COUNT_RECURSIVE);
			
			$this->total_bars = $data_series_count - $label_temp_count;
			$this->shown_labels = $label_temp_count;
		}
		
		self::get_biggest_label($this->labels);
	}
	
	function text_size($text, $font_size, $font_adjust, $encoding)
	{
		$height = $font_size;
		
		$len = mb_strlen($text, $encoding);
    
		$width = $len * $font_size * $font_adjust;

		return [$width, $text];
	}
	
	// for getting the biggest label to see how much space we need
	function get_biggest_label($labels)
	{
		$label_lengths = [];
		foreach ($labels as $key => $label)
		{
			$text = $key;
			if ($this->chart_info['grouped'] == 0)
			{
				if (is_array($label))
				{
					$text = $label['name'];
				}
			}
			$get_label_length = $this->text_size($text, $this->chart_options['label_font_size'], 0.6, 'UTF-8');
			$label_lengths[] = $get_label_length;
		}
		rsort($label_lengths);
		
		$this->biggest_label = array_slice($label_lengths[0], 0, 1);
	}
	
	function biggest_series_label($series)
	{
		foreach ($series as $key => $text)
		{
			$get_series_length = $this->text_size($text, $this->chart_options['label_font_size'], 0.6, 'UTF-8');
			$series_lengths[] = $get_series_length;
		}
		rsort($series_lengths);
		
		$this->biggest_series = array_slice($series_lengths[0], 0, 1)[0];
	}
	
	function chart_sizing()
	{
		if ($this->chart_info['grouped'] == 1)
		{
			self::biggest_series_label($this->data_series);
		}
		
		$this->actual_chart_space = $this->chart_options['chart_width'] - $this->biggest_label[0] - $this->chart_options['padding_right'];
		
		// the actual bars and everything else start after the label
		$this->chart_bar_start_x = $this->biggest_label[0];
		
		if ($this->chart_info['grouped'] == 0)
		{
			$this->chart_height = $this->label_y_start + $this->total_bars * $this->label_y_increment + $this->chart_options['padding_bottom'];
		}
		else
		{
			$this->chart_height = $this->label_y_start;
			$this->actual_chart_space = $this->actual_chart_space - $this->biggest_series * 2;
		}
		
		if (isset($this->chart_info['sub_title']) && $this->chart_info['sub_title'] != NULL)
		{
			$this->chart_height = $this->chart_height + 16;
			$this->label_y_start = $this->label_y_start + 18;
		}
		
		$this->outlines_x = $this->chart_bar_start_x + $this->chart_options['label_left_padding'] + $this->chart_options['label_right_padding'];
		$this->bars_x_start = $this->chart_bar_start_x + $this->chart_options['label_left_padding'] + $this->chart_options['label_right_padding'];
		
		$this->chart_end_x = $this->chart_bar_start_x + $this->actual_chart_space + $this->chart_options['label_left_padding'] + $this->chart_options['label_right_padding'];
	}
	
	function render($id, $pass_options = NULL, $labels_table = NULL, $data_table = NULL)
	{
		$this->setup($pass_options);
		$this->get_chart($id);
		$this->get_labels($this->chart_info['id'], $labels_table, $data_table);

		self::chart_sizing();
		
		// bottom axis data divisions
		self::ticks($this->max_data, $this->biggest_label[0]);
		
		$last_label_y = 0;
		$label_counter = 0;
		$last_bar_y = 0;
		
		$last_label_name = '';
		
		// sort labels, bars, bar counters and more
		foreach ($this->labels as $k => $label)
		{
			// setup label vertical positions
			if ($label_counter == 0)
			{
				$this_label_y = $this->label_y_start;
			}
			else
			{
				if ($this->chart_info['grouped'] == 0)
				{
					$this_label_y = $last_label_y + $this->label_y_increment;
				}
				
				// if it's a grouped chart with data series, make space 
				else
				{
					$this_label_y = $last_bar_y + $this->chart_options['bar_thickness'] + $this->label_y_increment;
				}
			}
			
			$label_x_position = $this->chart_bar_start_x + $this->chart_options['label_left_padding'];
			
			// labels
			if ($this->chart_info['grouped'] == 0)
			{
				$this->labels_output_array[] = '<text x="'.$label_x_position.'" y="'.$this_label_y.'" text-anchor="end"><title>'.$label['name'].' ' . $label['total'] . '</title>'.$label['name'].'</text>';
			}
			else
			{
				if ($last_label_name != $k)
				{
					$this->labels_output_array[] = '<text x="'.$label_x_position.'" y="'.$this_label_y.'" text-anchor="end">'.$k.'</text>';
				}
			}
			$last_label_y = $this_label_y;
			
			// label splitters
			if ($label_counter > 0)
			{
				$this_split_y = $this_label_y - 25;
				$this_split_y2 = $this_split_y + 1;
				$this_split_x = $this->chart_bar_start_x - 5 + $this->chart_options['label_left_padding'] + $this->chart_options['label_right_padding'];
				$this->label_splits_array[] = '<line x1="'.$this_split_x.'" y1="'.$this_split_y.'" x2="'.$this_split_x.'" y2="'.$this_split_y2.'" stroke="#757575" stroke-width="10" />';
			}
			
			// setup bar positions and array of items
			if ($this->chart_info['grouped'] == 0)
			{
				$this_bar_y = $this_label_y - 18;
				$bar_width = $label['total']*$this->scale;
				$this->bars_output_array[] = '<rect x="'.$this->bars_x_start.'" y="'.$this_bar_y.'" height="'.$this->chart_options['bar_thickness'].'" width="'.$bar_width.'" fill="'.$this->chart_options['colours'][$label_counter].'"><title>'.$label['name'].' ' . $label['total'] . '</title></rect>';
				
				// bar counters and their positions
				$this_counter_x = $bar_width + $this->chart_bar_start_x + $this->chart_options['bar_counter_left_padding'] + $this->chart_options['label_left_padding'];
				$this_counter_y = $this_bar_y + 21;
				
				$this->counter_array[] = '<text class="golsvg_counters" x="'.$this_counter_x.'" y="'.$this_counter_y.'" font-size="'.$this->chart_options['counter_font_size'].'">'.$label['total'].'</text>';
			}
			else
			{
				$data_series_counter = 0;
				
				foreach ($label as $k => $data)
				{
					// setup label vertical positions
					if ($data_series_counter == 0)
					{
						$this_bar_y = $this_label_y - 18;
					}
					else
					{
						$this_bar_y = $last_bar_y + $this->chart_options['bar_thickness'] + 2;
					}
					
					$bar_width = $data*$this->scale;
					$this->bars_output_array[] = '<rect x="'.$this->bars_x_start.'" y="'.$this_bar_y.'" height="'.$this->chart_options['bar_thickness'].'" width="'.$bar_width.'" fill="'.$this->chart_options['colours'][$data_series_counter].'"><title>'.$k.' ' . $data . '</title></rect>';
				
					// bar counters and their positions
					$this_counter_x = $bar_width + $this->chart_bar_start_x + $this->chart_options['bar_counter_left_padding'] + $this->chart_options['label_left_padding'];
					$this_counter_y = $this_bar_y + 21;
					
					$force_counter_colour = '';
					if (isset($this->chart_options['counter_colour']) && !empty($this->chart_options['counter_colour']))
					{
						$force_counter_colour = 'fill="'.$this->chart_options['counter_colour'].'"';
					}
					
					$this->counter_array[] = '<text class="golsvg_counters" '.$force_counter_colour.' x="'.$this_counter_x.'" y="'.$this_counter_y.'" font-size="'.$this->chart_options['counter_font_size'].'">'.$data.'</text>';

				
					$data_series_counter++;
					$last_bar_y = $this_bar_y;
				}
				
			}
			$label_counter++;
			$last_label_name = $k;
		}
		$this->chart_height = $this->chart_height + $last_bar_y;
		
		return $this->build_svg();
	}
	
	function ticks($max_data, $biggest_label)
	{
		// bottom axis data divisions
		$this->ticks_total = $this->chart_options['ticks_total'];

		// as we want integer values for ticks, make sure ticks_total is not too large for max_data
		if ($max_data < $this->ticks_total)
		{
			$this->ticks_total = ceil($max_data);
		}

		// get the smallest integer divisible by ticks_total that is larger than $max_data
		$max_tick = ceil($max_data);
		if ($max_tick % $this->ticks_total !== 0)
		{
			while ($max_tick % $this->ticks_total !== 0)
			{
				$max_tick += 1;
			}
		}
		
		// scale is the space between the starting axis line and the last tick
		$this->scale = $this->actual_chart_space / $max_tick;

		$this->value_per_tick = $max_tick / $this->ticks_total;

		$this->tick_spacing = $this->value_per_tick * $this->scale;
	}
	
	// this is a start to extract the extra needed for stat charts, so they can eventually use the same functions as normal charts
	function stat_chart_old($last_id)
	{
		global $db;
		
		$db->sqlquery("SELECT `name`, `h_label`, `generated_date`, `total_answers` FROM `user_stats_charts` WHERE `id` = ?", array($last_id));
		$this->chart_info_old = $db->fetch();

		// set the right labels to the right data (OLD DATA)
		$labels_old = [];
		$db->sqlquery("SELECT l.`label_id`, l.`name`, d.`data` FROM `user_stats_charts_labels` l LEFT JOIN `user_stats_charts_data` d ON d.label_id = l.label_id WHERE l.`chart_id` = ? ORDER BY d.`data` " . $this->chart_options['order'], array($last_id));
		$this->get_labels_old = $db->fetch_all_rows();
	}
	
	function stat_chart($id, $last_id = '', $custom_options)
	{
		global $db;
		
		$this->setup($custom_options);

		if (core::is_number($last_id))
		{
			self::stat_chart_old($last_id);
		}
		
		self::get_chart($id, 'stat_chart');
		
		$label_add = '';
		if ($this->chart_info['name'] == 'RAM')
		{
			$label_add = 'GB';
		}

		// set the right labels to the right data (This months data)
		$this->get_labels($id, 'user_stats_charts_labels', 'user_stats_charts_data');
		
		// this is for the full info expand box, as charts only show 10 items, this expands to show them all
		$full_info = '<div class="collapse_container"><div class="collapse_header"><span>Click for full statistics</span></div><div class="collapse_content">';

		// sort them from highest to lowest
		usort($this->labels_raw_data, function($b, $a)
		{
			return $a['data'] - $b['data'];
		});
		foreach ($this->labels_raw_data as $k => $all_labels)
		{
			$icon = '';
			if ($this->chart_info['name'] == "Linux Distributions (Split)")
			{
				$icon = '<img class="distro" src="/templates/default/images/distros/'.$all_labels['name'].'.svg" alt="distro-icon" width="20" height="20" /> ';
			}
			if ($this->chart_info['name'] == "Linux Distributions (Combined)")
			{
				if ($all_labels['name'] == 'Ubuntu-based')
				{
					$icon_name = 'Ubuntu';
				}
				else if ($all_labels['name'] == 'Arch-based')
				{
					$icon_name = 'Arch';
				}
				else
				{
					$icon_name = $all_labels['name'];
				}
				$icon = '<img class="distro" src="/templates/default/images/distros/'.$icon_name.'.svg" alt="distro-icon" width="20" height="20" /> ';
			}
			
			$percent = round(($all_labels['data'] / $this->chart_info['total_answers']) * 100, 2);
			if (core::is_number($last_id))
			{
				$old_info = '';
				foreach ($this->get_labels_old as $all_old)
				{
					if ($all_old['name'] == $all_labels['name'])
					{
						$percent_old = round(($all_old['data'] / $this->chart_info_old['total_answers']) * 100, 2);
						$difference_percentage = round($percent - $percent_old, 2);

						$difference_people = $all_labels['data'] - $all_old['data'];

						if (strpos($difference_percentage, '-') === FALSE)
						{
							$difference_percentage = '+' . $difference_percentage;
						}

						if ($difference_people > 0)
						{
							$difference_people = '+' . $difference_people;
						}
						$old_info = ' Difference: (' . $difference_percentage . '% overall, ' . $difference_people .' people)';
					}
				}

				$full_info .= $icon . '<strong>' . $all_labels['name'] . $label_add . '</strong>: ' . $all_labels['data'] . ' (' . $percent . '%)' . $old_info . '<br />';
			}
		}
		$full_info .= '</div></div>';
			
		// get the max number to make the axis and bars
		$this->total_labels = count($this->labels);
			
		usort($this->labels, function($a, $b) 
		{
			return $b['total'] - $a['total'];
		});
		
		$max_data = $this->labels[0]['percent'];
			
		self::chart_sizing();
		
		self::ticks($max_data, $this->biggest_label[0]);
		
		$last_label_y = 0;
		$label_counter = 0;
			
		// sort labels, bars, bar counters and more
		foreach ($this->labels as $key => $data)
		{
			// setup label vertical positions
			if ($label_counter == 0)
			{
				$this_label_y = $this->label_y_start;
			}
			else
			{
				$this_label_y = $last_label_y + $this->label_y_increment;
			}
				
			$label_x_position = $this->chart_bar_start_x + $this->chart_options['label_left_padding']; 
				
			// labels
			$this->labels_output_array[] = '<text x="'.$label_x_position.'" y="'.$this_label_y.'" text-anchor="end"><title>'.$data['name'] . $label_add .' (' . $data['total'] . ' total votes)</title>'.$data['name'].'</text>';
			$last_label_y = $this_label_y;
				
			// label splitters
			if ($label_counter > 0)
			{
				$this_split_y = $this_label_y - 25;
				$this_split_y2 = $this_split_y + 1;
				$this_split_x = $this->chart_bar_start_x - 5 + $this->chart_options['label_left_padding'] + $this->chart_options['label_right_padding'];
				$this->label_splits_array[] = '<line x1="'.$this_split_x.'" y1="'.$this_split_y.'" x2="'.$this_split_x.'" y2="'.$this_split_y2.'" stroke="#757575" stroke-width="10" />';
			}
				
			// setup bar positions and array of items
			$this_bar_y = $this_label_y - 18;
			$bar_width = $data['percent']*$this->scale;
			$this->bars_output_array[] = '<rect x="'.$this->bars_x_start.'" y="'.$this_bar_y.'" height="'.$this->chart_options['bar_thickness'].'" width="'.$bar_width.'" fill="'.$this->chart_options['colours'][$label_counter].'"><title>'.$data['name'].$label_add.' (' . $data['total'] . ' total votes)</title></rect>';
				
			// bar counters and their positions
			$this_counter_x = $bar_width + $this->chart_bar_start_x + $this->chart_options['bar_counter_left_padding'] + $this->chart_options['label_left_padding'];
			$this_counter_y = $this_bar_y + 21;
			
			$force_counter_colour = '';
			if (isset($this->chart_options['counter_colour']) && !empty($this->chart_options['counter_colour']))
			{
				$force_counter_colour = 'fill="'.$this->chart_options['counter_colour'].'"';
			}
			
			$this->counter_array[] = '<text class="golsvg_counters" '.$force_counter_colour.' x="'.$this_counter_x.'" y="'.$this_counter_y.'" font-size="'.$this->chart_options['counter_font_size'].'">'.$data['percent'].'%</text>';
				
			$label_counter++;
		}
		
		$get_graph['graph'] = $this->build_svg();
		  
		$get_graph['full_info'] = $full_info;
		$get_graph['date'] = $this->chart_info['generated_date'];

		$total_difference = '';
		if (isset($chart_info_old['total_answers']))
		{
			$total_difference = $this->chart_info['total_answers'] - $chart_info_old['total_answers'];
			if ($total_difference > 0)
			{
				$total_difference = '+' . $total_difference;
			}
			$total_difference = ' (' . $total_difference . ')';
		}

		$get_graph['total_users_answered'] = $this->chart_info['total_answers'] . $total_difference;

		return $get_graph;
	}
	
	function build_svg()
	{
		$this->bottom_axis_numbers_y = $this->chart_height - 20;
		$this->axis_outline_y = $this->bottom_axis_numbers_y - 14;
		$this->y_axis_label_y = $this->bottom_axis_numbers_y + 15;
		
		$get_graph = '<svg class="golgraph" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" baseProfile="tiny" version="1.2" viewbox="0 0 '.$this->chart_options['chart_width'].' '.$this->chart_height.'" style="max-height: '.$this->chart_height.'px">
		<!-- outer box -->
		<rect class="golsvg_background" x="0" y="0" width="'.$this->chart_options['chart_width'].'" height="'.$this->chart_height.'" fill="#F2F2F2" stroke="#696969" stroke-width="1" />
		<!-- x/y axis outlines -->
		<g stroke="#757575" stroke-width="1">
			<line x1="'.$this->outlines_x.'" y1="64" x2="'.$this->outlines_x.'" y2="'.$this->axis_outline_y.'" />
			<line x1="'.$this->outlines_x.'" y1="'.$this->axis_outline_y.'" x2="'.$this->chart_end_x.'" y2="'.$this->axis_outline_y.'" />
		</g>
		<rect class="golsvg_header" x="0" y="0" width="'.$this->chart_options['chart_width'].'" height="'.$this->chart_options['title_background_height'].'" fill="#222222"/>';
		
		if ($this->chart_info['grouped'] == 1)
		{
			$get_graph .= '<!-- legend -->';
			$legend_start_x = $this->chart_end_x + 50;
			$legend_start_y = $this->label_y_start;
			foreach ($this->data_series as $key => $series)
			{
				// setup label vertical positions
				if ($key == 0)
				{
					$this_legend_y = $legend_start_y;
				}
				else
				{
					$this_legend_y = $legend_last_y + $this->chart_options['label_font_size'];
				}
				$this_text_y = $this_legend_y + 10;
				$this_legend_text_x = $legend_start_x + 13;
				
				$get_graph .= '<rect x="'.$legend_start_x.'" y="'.$this_legend_y.'" width="10" height="10" rx="3" ry="3" fill="'.$this->chart_options['colours'][$key].'" /> <text x="'.$this_legend_text_x.'" y="'.$this_text_y.'">'.$series.'</text>';
				
				$legend_last_y = $this_legend_y;
			}

		}
		
		$get_graph .= '<!-- strokes -->
		<g stroke="#ccc" stroke-width="1" stroke-opacity="0.6">';
		
		// render tick numbers and graph division tick lines
		$tick_x_start = $this->outlines_x;
		$this->strokes_height = $this->chart_height - 35;
		$current_value = $this->value_per_tick;
		for ($i = 0; $i <= $this->ticks_total; $i++)
		{
			if ($i == 0)
			{
				$tick_x_position = $tick_x_start;
				$current_value = 0;
			}
			else
			{
				$tick_x_position = $tick_x_position + $this->tick_spacing;
				$current_value = $current_value + $this->value_per_tick;
			}
			$this->divisions .= '<text x="'.$tick_x_position.'" y="'.$this->bottom_axis_numbers_y.'" font-size="'.$this->chart_options['tick_font_size'].'">'.$current_value.'</text>';
				
			// graph counter strokes, don't make the zero stroke as it covers up the graph line
			if ($i > 0)
			{
				$get_graph .= '<line x1="'.$tick_x_position.'" y1="60" x2="'.$tick_x_position.'" y2="'.$this->strokes_height.'"/>';
			}
		}
		
		// only really used for chart exports
		$title_fill = '';
		if (isset($this->chart_options['title_colour']) && !empty($this->chart_options['title_colour']))
		{
			$title_fill = 'fill="'.$this->chart_options['title_colour'].'"';
		}
		
		$get_graph .= '<text class="golsvg_title" '.$title_fill.' x="300" y="19" font-size="17" text-anchor="middle">'.$this->chart_info['name'].'</text>';
		
		if (isset($this->chart_info['sub_title']) && $this->chart_info['sub_title'] != NULL)
		{
			$get_graph .= '<text class="golsvg_subtitle" x="300" y="45" font-size="16" text-anchor="middle">'.$this->chart_info['sub_title'].'</text>';
		}
		
		$get_graph .= '</g>
		<!-- labels -->
		<g font-size="'.$this->chart_options['label_font_size'].'" font-family="monospace" fill="#000000">';

		$get_graph .= implode('', $this->labels_output_array);

		$get_graph .= '</g>
		<!-- bars -->
		<g stroke="#949494" stroke-width="1">';

		$get_graph .= implode('', $this->bars_output_array);

		$get_graph .= '</g>
		<g font-size="10" fill="#FFFFFF">';
			
		$get_graph .= implode('', $this->counter_array);
			
		$get_graph .= '</g>
		<!-- bar splitters -->';

		$get_graph .= implode('', $this->label_splits_array);

		$get_graph .= '<!-- bottom axis numbers -->
		<g font-size="10" fill="#000000" text-anchor="middle">'.$this->divisions.'</g>
		<!-- bottom axis label -->
		<text x="285" y="'.$this->y_axis_label_y.'" font-size="15" fill="#000000" text-anchor="middle">'.$this->chart_info['h_label'].'</text>
		</svg>';
		
		return $get_graph;	
	}
}
?>
