<?php 
/*
Plugin Name: WP Music Links
Plugin URI: http://github.com/fergomez/wp-music-links
Description: Adds links to social networks of artists and festivals easily in your posts.
Version: 0.1
Author: Fernando Gómez Pose
Author URI: http://fergomez.es/
License: GPL2
*/


/**
 * File containing all the functions for the plugin.
 * Usage: [musiclinks artist="name"], [musiclinks festival="name"].
 * @author fergomez
 *
 */
	
/**
 * Creates (if they don't exist) the database tables for storing all the links retrieved from
 * MusicBrainz or manually. We create two tables: the main one for each stored item (mainly "artist"
 * or "festival", but eventually expanded to "label", "agency", etc.) and the other one for all the 
 * links for each item (Facebook, Twitter, Main website, etc.). 
 *  
 * Structure:
 * 	wp_musiclinks:
 * 		- id : item id
 * 		- name : item name
 * 		- type : "artist", "festival", "label", "agency"...
 * 		- mb_id : in case of "artist", music brainz id; otherwise, null
 *
 *	wp_musiclinks_rel: (relationships)
 *    - id : item id
 *    - type_link: "facebook", "twitter", "website"...
 *    - value : urls
 *    
 *  @access private
 *  @return boolean false in case of problems 
 */	
function wpmusiclinks_create_database() {
	global $wpdb;
	
	$query = "CREATE TABLE IF NOT EXISTS `wp_" . $wp_prefix . "_musiclinks` ( 
					  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, 
						`name` varchar(100),
						`type` varchar(50),
						`mb_id` varchar (20),
						PRIMARY KEY (`id`)		
					)";
	
	// mysqli_query
	
	$query = "CREATE TABLE IF NOT EXISTS `wp_" . $wp_prefix . "_musiclinks_rel` (
						`id` int(11) UNSIGNED NOT NULL,
						`link_type` varchar(50),
						`link_value` varchar(150)
						PRIMARY KEY (`id`, `link_type`)		
					)";
	
	// mysqli_query
	
	// check if they exist
	
	return true;
}


/**
 * Checks if an item already exists in our database or not
 * @param string $name name of item
 * @param string $type type of item ("artist", "festival"...)
 * @return boolean true if we already have the item in our database ($results != 0)
 */
function wpmusiclinks_cache_check($name, $type)  {
	$query = "SELECT COUNT(*) FROM `wp_" . $wp_prefix . "_musiclinks` 
						WHERE `name` = '$name' AND `type` = '$type';";
	$results = $wpdb->get_results($query);
	
	return ($results);
}


/**
 * Gets the information from MusicBrainz API and stores it into our database.
 * @param string $name name of artist
 */
function wpmusiclinks_get_info($name) {
	// parse info and blabla
}


/**
 * Generates the HTML links
 * @param string $name name of the item
 * @param string $type type of the item
 * @return string the HTML code for the links or an empty string in case of error
 */
function wpmusiclinks_get_links($name, $type) {
	// get item info from database
	// in case it's not found, we get it from MusicBrainz
	// in case we don't find anything in MusicBrainz, we return an empty string
}


/**
 * Replaces the shortcodes with its corresponding HTML links
 * @param string $post post content
 * @return string the post with all the shortcodes replaced for its corresponding HTML
 */
function wpmusiclinks_replace_shortcodes($post) {
	if (substr_count($post, '[musiclinks ') > 0) {
		// regex for getting all the shortcodes (type + name)
		// replaces each shortcode for their links using getLinks($id)
		$post = str_replace('[musiclinks ' . type . '="' . name . '"]');
	}
	return $post;
} 



/**
 * Parses old links from older posts and stores the information in our database
 * @param string $html the HTML code from the links from previous posts
 */
function wpmusiclinks_parse_existing_links($html) {
	// we get the website, facebook and twitter
}

/**
 * Form where we can add manually (inside WordPress) the information for an artist, festival...
 */
function wpmusiclinks_add_info_manually() {
	
}

/**
 * Form where we can edit manually (inside WordPress) the information for an artist, festival...
 */
function wpmusiclinks_edit_info() {
}

/**
 * Adds a menu for users.
 */
function wpmusiclinks_menu() {
	add_menu_page('WP Music Links', "WP Music Links", 8, basename(__file__), '', plugins_url('wpmusiclogo.png', __FILE__));
	add_submenu_page(basename(__file__), 'WP Music Links Settings', "WP Music Links", 8, basename(__file__), "wpmusiclinks_option_menu");
}

/**
 * 
 */
function wpmusiclinks_option_menu(){
	echo "<p>Hi!</p>";
	
}


add_filter('the_content', 'wpmusiclinks_replace_shortcodes');
add_action('admin_menu', 'wpmusiclinks_menu');

// check permission
?>