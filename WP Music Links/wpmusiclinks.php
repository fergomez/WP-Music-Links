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
	
	$query = "INSERT INTO $wpdb->musiclinks (name, type) VALUES ('Metallica', 'artist')";
	$wpdb->query($query);
	$query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_value) VALUES
						(1, 'facebook', 'https://www.facebook.com/metallica')";
	$wpdb->query($query);
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
 * 
 * @param string $name name of the item
 * @param string $type type of the item (festival, artist...)
 */
function wpmusiclinks_get_value($name, $type, $val_type) {
	global $wpdb;
	if (wpmusiclinks_cache_check($name, $type)) {
		$query = "SELECT link_value as val
							FROM $wpdb->musiclinks m
							JOIN $wpdb->musiclinksr r
								ON m.id = r.id
							WHERE m.name = '$name' AND
										m.type = '$type' AND
										r.link_type = '$val_type'
							LIMIT 1;";
		$results = $wpdb->get_results($query);
		
		if ($results) {
			foreach ($results as $result) {
				return $result->val;
			}
		} else return false;
	} else return false;
}


/**
 * Returns the facebook of one certain artist
 * @param string $name name of the artist
 * @return string return facebook link
 */
function wpmusiclinks_get_artist_facebook($name) {
	return wpmusiclinks_get_value($name, "artist", "facebook");
}


/**
 * Returns the twitter of one certain artist
 * @param string $name name of the artist
 * @return string return twitter link
 */
function wpmusiclinks_get_artist_twitter($name) {
	return wpmusiclinks_get_value($name, "artist", "twitter");
}


/**
 * Returns the official website of one certain artist
 * @param string $name name of the artist
 * @return string return website link
 */
function wpmusiclinks_get_artist_website($name) {
	return wpmusiclinks_get_value($name, "artist", "website");
}


/**
 * Returns the Last.fm profile of one certain artist
 * @param string $name name of the artist
 * @return string return Last.fm link
 */
function wpmusiclinks_get_artist_lastfm($name) {
	return "http://last.fm/music/" . $name;
}


/**
 * Returns the facebook of one certain festival
 * @param string $name name of the festival
 * @return string return facebook link
 */
function wpmusiclinks_get_festival_facebook($name) {
	return wpmusiclinks_get_value($name, "festival", "facebook");
}


/**
 * Returns the twitter of one certain festival
 * @param string $name name of the festival
 * @return string return twitter link
 */
function wpmusiclinks_get_festival_twitter($name) {
	return wpmusiclinks_get_value($name, "festival", "twitter");
}


/**
 * Returns the official website of one certain festival
 * @param string $name name of the festival
 * @return string return website link
 */
function wpmusiclinks_get_festival_website($name) {
	return wpmusiclinks_get_value($name, "festival", "website");
}


/**
 * Returns the Last.fm profile of one certain festival
 * @param string $name name of the festival
 * @return string return Last.fm link
 */
function wpmusiclinks_get_festival_lastfm($name) {
	return wpmusiclinks_get_value($name, "festival", "lastfm");
}


/**
 * Generates the HTML links
 * @param string $name name of the item
 * @param string $type type of the item
 * @return string the HTML code for the links or an empty string in case of error
 */
function wpmusiclinks_get_links($name, $type) {
	global $wpdb;
	if (wpmusiclinks_cache_check($name, $type)) {
		$query = "SELECT link_type as type, link_value as val
							FROM $wpdb->musiclinks m
							JOIN $wpdb->musiclinksr r
								ON m.id = r.id
							WHERE m.name = '$name' AND
										m.type = '$type';";
		$results = $wpdb->get_results($query);
		
		if ($results) {
			$links = "<strong>$name</strong>: ";
			// sort links as desired
			foreach ($results as $result) {
				$type = ucfirst($result->type); 
				$links	.= '<a href="' . $result->val . '" title="' . $type . '">' . $type . '</a> ';
			}
			return $links;
		} 
	}
	// if not found
	if ($type == "artist") {
		wpmusiclinks_get_info($name, $type);
		return wpmusiclinks_get_links($name, $type);
	} else return "";
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
 * Adds a menu in our dashboard for allowed users.
 */
function wpmusiclinks_add_menu() {
	if (function_exists('add_menu_page')) {
		add_menu_page('WP Music Links', "WP Music Links", 8, basename(__file__), '', plugins_url('wpmusiclogo.png', __FILE__));
	}
	if (function_exists('add_submenu_page')) {
		add_submenu_page(basename(__file__), 'WP Music Links Settings', "WP Music Links", 8, basename(__file__), "wpmusiclinks_desktop");
		
	}
}


/**
 * Shows our info in our plugin section.
 */
function wpmusiclinks_desktop(){
	global $wpdb;
	echo "<p>Hi!</p>";
	if (wpmusiclinks_cache_check("Metallica", "artist")) echo '<p>We have Metallica. Facebook: <a href="' . wpmusiclinks_get_artist_facebook("Metallica") . '">Facebook</a></p>';
	else echo "<p>No Metallica.</p>";
	if (wpmusiclinks_cache_check("Iron Maiden", "artist")) echo "<p>We have Iron Maiden.</p>";
	else echo "<p>No Iron Maiden.</p>";
	echo "<p>Facebook: " . wpmusiclinks_get_artist_facebook("Iron Maiden") . "</p>";
	echo wpmusiclinks_get_links("Metallica", "artist");
	
}

// creates tables when activating the plugin
add_action('activate_wpmusiclinks.php', 'wpmusiclinks_create_tables');
// replaces the shortcodes in our content
add_filter('the_content', 'wpmusiclinks_replace_shortcodes');
// adds the plugin menu to the admin menu
add_action('admin_menu', 'wpmusiclinks_add_menu');

// check permissions?
?>