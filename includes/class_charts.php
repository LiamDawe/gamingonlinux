<?php
class golchart
{
	private $chart_info;
	private $labels = [];
	private $data_counts = [];
	private $chart_options = [];
	
	function pass_options($custom_options)
	{		
		// set some defaults
		$this->chart_options['colours'] = array(
			'#a6cee3',
			'#1f78b4',
			'#b2df8a',
			'#33a02c',
			'#fb9a99',
			'#e31a1c',
			'#fdbf6f',
			'#ff7f00',
			'#cab2d6',
			'#6a3d9a');
		
		$this->chart_options['chart_width'] = 600;
		$this->chart_options['title_background_height'] = 25;
		$this->chart_options['bar_thickness'] = 30;
		$this->chart_options['padding_bottom'] = 10;
		$this->chart_options['bar_counter_left_padding'] = 5;
		$this->chart_options['counter_font_size'] = 15;
		$this->chart_options['division_font_size'] = 15;
		$this->chart_options['label_right_padding'] = 3;
		
		// sort out any custom options passed to us
		if (isset($custom_options) && is_array($custom_options))
		{
			foreach ($custom_options as $option => $value)
			{
				$this->chart_options[$option] = $value;
			}
		}
	}
	
	function get_chart($chart_id)
	{
		global $db;
		
		$db->sqlquery("SELECT `id`, `name`, `h_label` FROM `charts` WHERE `id` = ?", array($chart_id));
		$this->chart_info = $db->fetch();
	}
	
	function get_labels($chart_id)
	{
		global $db;
		
		// set the right labels to the right data
		$db->sqlquery("SELECT `label_id`, `name` FROM `charts_labels` WHERE `chart_id` = ?", array($chart_id));
		$get_labels = $db->fetch_all_rows();
		foreach ($get_labels as $label_loop)
		{
			$db->sqlquery("SELECT `data`, `label_id` FROM `charts_data` WHERE `chart_id` = ?", array($chart_id));
			while ($get_data = $db->fetch())
			{
				if ($label_loop['label_id'] == $get_data['label_id'])
				{
					$this->labels[$label_loop['name']] = $get_data['data'];
					$this->data_counts[] = $get_data['data'];
				}
			}	
		}
	}
	
	function render($id, $pass_options = NULL)
	{
		$this->pass_options($pass_options);
		$this->get_chart($id);
		$this->get_labels($this->chart_info['id']);
		
		// get the max number to make the axis and bars
		$total_labels = count($this->labels);
		$max_data = $this->data_counts[0];

		/* TESTING LABEL LENGTHS */
		$label_lengths = [];
		foreach ($this->labels as $key => $label)
		{
			$label_lengths[] = strlen($key);
		}
		rsort($label_lengths);
		// //
		
		$chart_bar_start_x = 120.5;
		
		// chart sizing
		$label_y_start = 60;
		$label_y_increment = 45;
		$bottom_padding = $this->chart_options['padding_bottom'];
		$chart_height = $total_labels * $label_y_increment + $label_y_start + $bottom_padding;
		
		$bottom_axis_numbers_y = $chart_height - 20;
		$axis_outline_y = $bottom_axis_numbers_y - 14;
		$y_axis_label_y = $bottom_axis_numbers_y + 15;
		$strokes_height = $chart_height - 35;
		
		// bottom axis data divisions
		$subdivisions = 5;
		$value_per_division = $max_data / $subdivisions;
		
		$division_x_start = $chart_bar_start_x; // they start where the axis outline for labels ends
		$current_value = $value_per_division;
		$division_x_position = $division_x_start;
		$divisions = '<text x="'.$division_x_start.'" y="'.$bottom_axis_numbers_y.'" font-size="'.$this->chart_options['division_font_size'].'">0</text>';
		for ($i = 1; $i <= $subdivisions; $i++)
		{
			$division_x_position = $division_x_position + 78;
			$divisions .= '<text x="'.$division_x_position.'" y="'.$bottom_axis_numbers_y.'" font-size="'.$this->chart_options['division_font_size'].'">'.round($current_value).'</text>';
			$current_value = $current_value + $value_per_division;
		}
		
		// scale is the space between the starting axis line and the last data stroke
		$scale = ($division_x_position - $chart_bar_start_x)/$max_data;
		
		$last_label_y = 0;
		$label_counter = 0;
		
		$labels_output_array = [];
		$bars_output_array = [];
		$label_splits_array = [];
		$counter_array = [];
		
		// sort labels, bars, bar counters and more
		foreach ($this->labels as $key => $data)
		{
			// setup label vertical positions
			if ($label_counter == 0)
			{
				$this_label_y = $label_y_start;
			}
			else
			{
				$this_label_y = $last_label_y + $label_y_increment;
			}
			
			$label_x_position = $chart_bar_start_x - $this->chart_options['label_right_padding']; 
			
			// sort long labels into smaller sections to wrap the text nicely
			$label_title = '';
			if (strlen($key) > 15)
			{
				$wrap_label = wordwrap($key, 15, ";;", true);
				$split_label = explode(";;", $wrap_label);
				
				// fix position for multi-line text
				if (count($split_label) == 2)
				{
					$label_y_adjust = 5;
				}
				else if (count($split_label) > 2)
				{
					$label_y_adjust = 12;
				}
				
				$text_initial_y = $this_label_y - $label_y_adjust;
				$label_text_spacing = 15;
				foreach ($split_label as $split)
				{
					$label_title .= '<tspan x="'.$label_x_position.'" y="'.$text_initial_y.'">'.$split.'</tspan>';
					$text_initial_y = $text_initial_y + $label_text_spacing;
				}
			}
			else
			{
				$label_title = $key;
			}
			
			// labels
			$labels_output_array[] = '<text x="'.$label_x_position.'" y="'.$this_label_y.'" text-anchor="end"><title>'.$key.' ' . $data . '</title>'.$label_title.'</text>';
			$last_label_y = $this_label_y;
			
			// label splitters
			if ($label_counter > 0)
			{
				$this_split_y = $this_label_y - 25;
				$this_split_y2 = $this_split_y + 1;
				$this_split_x = $chart_bar_start_x - 5;
				$label_splits_array[] = '<line x1="'.$this_split_x.'" y1="'.$this_split_y.'" x2="'.$this_split_x.'" y2="'.$this_split_y2.'" stroke="#757575" stroke-width="10" />';
			}
			
			// setup bar positions and array of items
			$this_bar_y = $this_label_y - 18;
			$bar_width = $data*$scale;
			$bars_output_array[] = '<rect x="'.$chart_bar_start_x.'" y="'.$this_bar_y.'" height="'.$this->chart_options['bar_thickness'].'" width="'.$bar_width.'" fill="'.$this->chart_options['colours'][$label_counter].'"><title>'.$key.' ' . $data . '</title></rect>';
			
			// bar counters and their positions
			$this_counter_x = $bar_width + $chart_bar_start_x + $this->chart_options['bar_counter_left_padding'];
			$this_counter_y = $this_bar_y + 21;
			
			$counter_array[] = '<text class="golsvg_counters" x="'.$this_counter_x.'" y="'.$this_counter_y.'" font-size="'.$this->chart_options['counter_font_size'].'">'.$data.'</text>';
			
			$label_counter++;
		}
		
		$get_graph = '<svg class="golgraph" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" baseProfile="tiny" version="1.2" viewbox="0 0 '.$this->chart_options['chart_width'].' '.$chart_height.'" style="max-height: '.$chart_height.'px">
		<!-- outer box -->
		<rect class="golsvg_background" x="0" y="0" width="'.$this->chart_options['chart_width'].'" height="'.$chart_height.'" fill="#F2F2F2" stroke="#696969" stroke-width="1" />
		<!-- x/y axis outlines -->
		<g stroke="#757575" stroke-width="1">
			<line x1="'.$chart_bar_start_x.'" y1="64" x2="'.$chart_bar_start_x.'" y2="'.$axis_outline_y.'" />
			<line x1="'.$chart_bar_start_x.'" y1="'.$axis_outline_y.'" x2="586" y2="'.$axis_outline_y.'" />
		</g>
		<rect class="golsvg_header" x="0" y="0" width="'.$this->chart_options['chart_width'].'" height="'.$this->chart_options['title_background_height'].'" fill="#222222"/>
		<text class="golsvg_title" x="300" y="19" font-size="17" text-anchor="middle">'.$this->chart_info['name'].'</text>
		<!-- strokes -->
		<g stroke="#ccc" stroke-width="1" stroke-opacity="0.6">
			<line x1="200.5" y1="45" x2="200.5" y2="'.$strokes_height.'"/>
			<line x1="278.5" y1="45" x2="278.5" y2="'.$strokes_height.'" />
			<line x1="356" y1="45" x2="356" y2="'.$strokes_height.'" />
			<line x1="434" y1="45" x2="434" y2="'.$strokes_height.'" />
			<line x1="512" y1="45" x2="512" y2="'.$strokes_height.'"/>
		</g>
		<!-- labels -->
		<g font-size="15" fill="#000000">';

		$get_graph .= implode('', $labels_output_array);

		$get_graph .= '</g>
		<!-- bars -->
		<g stroke="#949494" stroke-width="1">';

		$get_graph .= implode('', $bars_output_array);

		$get_graph .= '</g>
		<g font-size="10" fill="#FFFFFF">';
			
		$get_graph .= implode('', $counter_array);
			
		$get_graph .= '</g>
		<!-- bar splitters -->';

		$get_graph .= implode('', $label_splits_array);

		$get_graph .= '<!-- bottom axis numbers -->
		<g font-size="10" fill="#000000" text-anchor="middle">'.$divisions.'</g>
		<!-- bottom axis label -->
		<text x="285" y="'.$y_axis_label_y.'" font-size="15" fill="#000000" text-anchor="start">Total</text>
		</svg>';
		
		return $get_graph;		
	}
}
?>
