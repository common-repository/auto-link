<?php
/*
Plugin Name: AutoLink
Plugin URI: http://cjbonline.org
Description: This plugin takes delimiters and replaces them with links using the GoogleAPI
Author: Chris Black
Version: 0.5
Author URI: http://www.cjbonline.org

*/

require_once 'GoogleSearch.php';

add_filter('content_save_pre','autolink_content');
add_action('admin_menu', 'add_auto_link_options_page');
add_filter('comment_save_pre','autolink_content');

function add_auto_link_options_page() {
    if (function_exists('add_options_page')) {
		add_options_page('AutoLink', 'AutoLink', 8, basename(__FILE__), 'auto_link_options_panel');    
	}
 }

function autolink_content($content) {
	global $wpdb;
	
/*	$sql = "SELECT post_content FROM " . $wpdb->posts . " WHERE id = " . $id . " LIMIT 1" ;
	$result = $wpdb->get_results($sql);
	if ($result) {
		foreach($result as $post) {
			$content = $post->post_content;
		}	
	}
*/	
	$NumDelim = get_option('CjB-NumDelim');
	$GoogleKey = get_option('CjB-GoogleAPIKey');
	for ($i = 1; $i <= $NumDelim; $i++) {
		$OptionName = 'CjB-DelimStart_' . $i;
		$DelimStart = get_option($OptionName );
		$OptionName = 'CjB-DelimEnd_' . $i;
		$DelimEnd = get_option($OptionName );
		$OptionName = 'CjB-DelimSite_' . $i;
		$DelimSite = get_option($OptionName );
//		print ("<br> " . $DelimStart . "<br>" . $DelimEnd . "<br>");
		$pos1 = strpos($content, $DelimStart);
		$pos2 = strpos($content, $DelimEnd);

		while ($pos1 && $pos2) {
			$DelimLength = strlen($DelimStart);
			$TitleString = substr($content, $pos1 + $DelimLength, ($pos2 - $pos1) - $DelimLength);
			$SearchString = $TitleString . ' site:' . $DelimSite;
			//print($TitleString . "<br><br>");
			$gs = new GoogleSearch();
	
			//set Google licensing key
			$gs->setKey($GoogleKey);
			
			$gs->setQueryString($SearchString);	//set query string to search.
			
			//set few other parameters (optional)
			$gs->setMaxResults(1);	//set max. number of results to be returned.
			$gs->setSafeSearch(true);	//set Google "SafeSearch" feature.
			
			//call search method on GoogleSearch object
			$search_result = $gs->doSearch();
			
			//check for errors
			if(!$search_result)
			{
				if($err = $gs->getError())
				{
					echo "<br>Error: " . $err;
					return $content;
				}
			}
			$re = $search_result->getResultElements();
			//print_r($search_result->getResultElements());
			if ($re) {
				foreach($re as $element)
				{
					//echo "<br>Title: " . $element->getTitle();
					//echo " URL: " . $element->getURL();
					$NewString = "<a href=\"" . $element->getURL() . "\">" . $TitleString . "</a>";
				}
				//print ("<br>" . $NewString);
			} else {
				$NewString =  $TitleString;
			}
			$StringReplace = $DelimStart . $TitleString . $DelimEnd;
			$content = str_replace($StringReplace, $NewString, $content);
			$pos1 = strpos($content, $DelimStart);
			$pos2 = strpos($content, $DelimEnd);
//			print("<br> TEST TEST TEST");
		}
	}
//	$sql = "UPDATE  " . $wpdb->posts . " SET post_content = '" . $content . "' WHERE id = " . $id ;
//	$result = $wpdb->get_results($sql);
	return $content;
}

function auto_link_options_panel() {
	global $wpdb;
	add_option('CjB-GoogleAPIKey', '', 'GoogleKey', 'yes');
	add_option('CjB-DelimStart_1', '[movie]', 'Delimiter Start 1', 'yes');
	add_option('CjB-DelimEnd_1', '[/movie]', 'Delimiter End 1', 'yes');
	add_option('CjB-DelimSite_1', 'imdb.com', 'Delimiter Site 1', 'yes');
	add_option('CjB-NumDelim', '1', 'Number of Delimiters', 'yes');
	
	if (isset($_POST['add_options'])) {
		update_option('CjB-NumDelim',$_POST['NumDelim']);
		update_option('CjB-GoogleAPIKey',$_POST['GoogleKey']);
		update_option('PrivateRSSFeedLocation',$_POST['NewPrivateRSSFeedLocation']);
		//print_r($_POST);
		for ($i = 1; $i <= $_POST['NumDelim']; $i++) {
			$OptionName = 'CjB-DelimStart_' . $i;
			//print ("<br> POST: " . $_POST[$OptionName] . " <br>");
			update_option($OptionName,$_POST[$OptionName]);
			$OptionName = 'CjB-DelimEnd_' . $i;
			update_option($OptionName,$_POST[$OptionName]);
			$OptionName = 'CjB-DelimSite_' . $i;
			update_option($OptionName,$_POST[$OptionName]);
		}
		print("<div class=\"updated\"><p><strong>Updated</strong></p></div>");
	}
	
	$NumDelim = get_option('CjB-NumDelim');
	$GoogleKey = get_option('CjB-GoogleAPIKey');
	for ($i = 1; $i <= $NumDelim; $i++) {
		$OptionName = 'CjB-DelimStart_' . $i;
		$DelimStart[$i] = get_option($OptionName);
		$OptionName = 'CjB-DelimEnd_' . $i;
		$DelimEnd[$i] = get_option($OptionName);
		$OptionName = 'CjB-DelimSite_' . $i;
		$DelimSite[$i] = get_option($OptionName);
	}
	
	print ("<div class=wrap>
	  <form method=\"post\"><h2>AutoLink Options</h2><table width=\"80%\">");
	print("<tr><td>Number of Delimiters:</td><td><input name=\"NumDelim\" type=\"text\" value=\"$NumDelim\" size=\"20\"></td></tr>
	<tr><td>Google Key:</td><td><input name=\"GoogleKey\" type=\"text\" value=\"$GoogleKey\" size=\"20\"></td></tr>");
	for ($i = 1; $i <= $NumDelim; $i++) {
		print("<tr><td>DelimStart #" . $i . "</td><td><input name=\"CjB-DelimStart_" . $i . "\" type=\"text\" value=\"" . $DelimStart[$i] . "\" size=\"15\"></td></tr>");
		print("<tr><td>DelimEnd #" . $i . "</td><td><input name=\"CjB-DelimEnd_" . $i . "\" type=\"text\" value=\"" . $DelimEnd[$i] . "\" size=\"15\"></td></tr>");
		print("<tr><td>DelimSite #" . $i . "</td><td><input name=\"CjB-DelimSite_" . $i . "\" type=\"text\" value=\"" . $DelimSite[$i] . "\" size=\"15\"></td></tr>
		<tr><td colspan=\"2\">&nbsp;</td></tr>");
	}
	
	print("</table><div class=\"submit\"><input type=\"submit\" name=\"add_options\" value=\"Update Options\" /></div>
  	</form>
	</div>");
}

?>