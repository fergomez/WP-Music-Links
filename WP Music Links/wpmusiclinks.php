<?php 
/*
Plugin Name: WP Music Links
Plugin URI: http://github.com/fergomez/wp-music-links
Description: Adds links to social networks of artists and festivals easily in your posts.
						 Usage: [musiclinks artist="name"], [musiclinks festival="name"].
Version: 0.1
Author: Fernando Gómez Pose
Author URI: http://fergomez.es/
License: GPL2
*/

global $wpdb;
$wpdb->musiclinks = $wpdb->prefix . 'musiclinks';
$wpdb->musiclinksr = $wpdb->prefix . 'musiclinks_rel';
	
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
 */	
function wpmusiclinks_create_tables() {	
	global $wpdb;
	$charset_collate = '';
	if($wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if(!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}
	
	if (@is_file(ABSPATH.'/wp-admin/upgrade-functions.php')) {
		include_once(ABSPATH.'/wp-admin/upgrade-functions.php');
	} elseif (@is_file(ABSPATH.'/wp-admin/includes/upgrade.php')) {
		include_once(ABSPATH.'/wp-admin/includes/upgrade.php');
	} else {
		die('We had a problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'');
	}
	
	$create_table = array();
	$create_table['links'] = "CREATE TABLE $wpdb->musiclinks ( 
														  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, 
															`name` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
															`type` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
															`mb_id` varchar (20) NULL,
															PRIMARY KEY (`id`),
															CONSTRAINT uq_musiclink_name
																UNIQUE (name, type)) $charset_collate;";
	
	$create_table['linksr'] = "CREATE TABLE $wpdb->musiclinksr (
															`id` int(11) UNSIGNED NOT NULL,
															`link_type` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
															`link_value` varchar(150)  CHARACTER SET utf8 NOT NULL DEFAULT '',
															PRIMARY KEY (`id`, `link_type`),
														  CONSTRAINT fk_musiclinks_rel
														    FOREIGN KEY (id)
														    REFERENCES $wpdb->musiclinks (id)) $charset_collate;";

	maybe_create_table($wpdb->musiclinks, $create_table['links']);
	maybe_create_table($wpdb->musiclinksr, $create_table['linksr']);	
	
}


/**
 * Checks if an item already exists in our database or not
 * @param string $name name of item
 * @param string $type type of item ("artist", "festival"...)
 * @return boolean true if we already have the item in our database ($results != 0)
 */
function wpmusiclinks_cache_check($name, $type)  {
	global $wpdb;
	$query = "SELECT COUNT(*) FROM $wpdb->musiclinks 
						WHERE `name` = '$name' AND `type` = '$type';";
	$results = $wpdb->get_var($query);
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
function wpmusiclinks_add_menu() {
	if (function_exists('add_menu_page')) {
		add_menu_page('WP Music Links', "WP Music Links", 8, basename(__file__), '', plugins_url('wpmusiclogo.png', __FILE__));
	}
	if (function_exists('add_submenu_page')) {
		add_submenu_page(basename(__file__), 'WP Music Links Settings', "WP Music Links", 8, basename(__file__), "wpmusiclinks_menu");
		
	}
}


/**
 * 
 */
function wpmusiclinks_menu(){
	global $wpdb;
	echo "<p>Hi!</p>";
	$query = "INSERT INTO $wpdb->musiclinks (name, type) VALUES ('Metallica', 'artist')";
	$wpdb->query($query);
	if (wpmusiclinks_cache_check("Metallica", "artist")) echo "<p>We have Metallica.</p>";
	else echo "<p>No Metallica.</p>";
	if (wpmusiclinks_cache_check("Iron Maiden", "artist")) echo "<p>We have Iron Maiden.</p>";
	else echo "<p>No Iron Maiden.</p>";
	
}

add_action('activate_wpmusiclinks.php', 'wpmusiclinks_create_tables');
add_filter('the_content', 'wpmusiclinks_replace_shortcodes');
add_action('admin_menu', 'wpmusiclinks_add_menu');

// check permission
?>