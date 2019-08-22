<?php
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
	$string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
	$string = trim($string);
	$string = strtolower($string);
	return preg_replace('/[^A-Za-z0-9]/', '', $string); // Removes special chars.
}

// this needs cleaning up
function steam_release_date($data)
{
	echo 'Raw release date: ' . $data . "\n";
	$trimmed_date = trim($data);	
	$remove_comma = str_replace(',', '', $trimmed_date);
	$parsed_release_date = strtotime($remove_comma);
	// so we can get rid of items that only have the year nice and simple
	$length = strlen($remove_comma);
	$parsed_release_date = date("Y-m-d", $parsed_release_date);
	$has_day = DateTime::createFromFormat('F Y', $remove_comma);
		
	if ($parsed_release_date != '1970-01-01' && $length != 4 && $has_day == FALSE)
	{
		return $clean_release_date = $parsed_release_date;
	}
	return null;
}
?>