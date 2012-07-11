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

$mainfile_path = 'wpmusiclinks.php';
$addfile_path = 'wpmusiclinks-add.php';
$editfile_path = 'wpmusiclinks-edit.php';

require_once($addfile_path);
require_once($editfile_path);


/**
 * Creates (if they don't exist) the database tables for storing all the links retrieved from
 * MusicBrainz or manually. We create two tables: the main one for each stored item (mainly "artist"
 * or "festival", but eventually expanded to "label", "agency", etc.) and the other one for all the 
 * links for each item (Facebook, Twitter, Main website, etc.). 
 *  
 * Structure:
 *    wp_musiclinks:
 *       - id : item id
 *       - name : item name
 *       - type : "artist", "festival", "label", "agency"...
 *       - mbid : in case of "artist", music brainz id; otherwise, null
 *
 *   wp_musiclinks_rel: (relationships)
 *    - id : item id
 *    - type_link: "facebook", "twitter", "website"...
 *    - value : urls
 *    
 *  @access private
 */   
function wpmusiclinks_create_tables() {   
   global $wpdb;
   
   // collation
   $charset_collate = '';
   if($wpdb->supports_collation()) {
      if(!empty($wpdb->charset)) {
         $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
      }
      if(!empty($wpdb->collate)) {
         $charset_collate .= " COLLATE $wpdb->collate";
      }
   }
   
   // functions for creating the tables (maybe_create_table)
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
                              `mbid` varchar (20) NULL,
                              PRIMARY KEY (`id`),
                              CONSTRAINT uq_musiclink_name
                                 UNIQUE (name, type)) $charset_collate;";
   
   $create_table['linksr'] = "CREATE TABLE $wpdb->musiclinksr (
                              `id` int(11) UNSIGNED NOT NULL,
                              `link_type` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
                              `link_type_name` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
                              `link_value` varchar(150)  CHARACTER SET utf8 NOT NULL DEFAULT '',
                              `link_order` int(11) UNSIGNED,
                              PRIMARY KEY (`id`, `link_type`),
                             CONSTRAINT fk_musiclinks_rel
                               FOREIGN KEY (id)
                               REFERENCES $wpdb->musiclinks (id)) $charset_collate;";

   maybe_create_table($wpdb->musiclinks, $create_table['links']);
   maybe_create_table($wpdb->musiclinksr, $create_table['linksr']);   
   
   // we add one hardcoded example to our database
   $name = 'Metallica';
   $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';
   $website = 'http://www.metallica.com';
   $facebook = 'https://www.facebook.com/metallica';
   $twitter = 'https://www.twitter.com/metallica';
   $lastfm = 'http://last.fm/music/metallica';
   
   wpmusiclinks_add_artist($name, $mbid, $website, $facebook, $twitter, $lastfm);
   
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
   return ($results!=0);
}


/**
 * Returns the asked value of a given item
 * @param string $name name of the item
 * @param string $type type of the item (festival, artist...)
 */
function wpmusiclinks_get_value($name, $type, $val_type) {
   global $wpdb;
   if (wpmusiclinks_cache_check($name, $type)) {
      if ($val_type == 'id') {
         $query = "SELECT id as val
                   FROM $wpdb->musiclinks
                   WHERE name = '$name' AND
                         type = '$type'
                   LIMIT 1;";
      } elseif ($val_type == "mbid") {
         $query = "SELECT mbid as val
                   FROM $wpdb->musiclinks
                   WHERE name = '$name' AND
                         type = 'artist'
                   LIMIT 1;";

      } else {
         $query = "SELECT link_value as val
                   FROM $wpdb->musiclinks m
                   JOIN $wpdb->musiclinksr r
                      ON m.id = r.id
                   WHERE m.name = '$name' AND
                         m.type = '$type' AND
                         r.link_type = '$val_type'
                   LIMIT 1;";
      }
      
      $results = $wpdb->get_results($query);
      
      if ($results) {
         foreach ($results as $result) {
            return $result->val;
         }
      } else return false;
   } else return false;
}


/**
 * 
 * @param string $name Name of the item
 */
function wpmusiclinks_get_artist_id($name) {
   return wpmusiclinks_get_value($name, "artist", 'id');   
}


/**
 * 
 * @param string $name Name of the festival
 */
function wpmusiclinks_get_festival_id($name) {
   return wpmusiclinks_get_value($name, "festival", 'id');   
}


/**
 * Returns the Music Brainz ID of one certain artist
 * @param string $name name of the artist
 * @return string return the MB ID
 */
function wpmusiclinks_get_artist_mbid($name) {
   return wpmusiclinks_get_value($name, "artist", "mbid");
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
   return wpmusiclinks_get_value($name, "artist", "lastfm");
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
 * Returns the number of items already in our database
 * @return int Number of items added into our database
 */
function wpmusiclinks_get_num_items() {
   global $wpdb;
    
   $query = "SELECT COUNT(*) FROM $wpdb->musiclinks;";
   $num = $wpdb->get_var($query);
   return $num;
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
      $query = "SELECT link_type_name as name, link_value as val
                FROM $wpdb->musiclinks m
                JOIN $wpdb->musiclinksr r
                   ON m.id = r.id
                WHERE m.name = '$name' AND
                      m.type = '$type' 
                ORDER BY r.link_order ASC;";
      $results = $wpdb->get_results($query);
      
      if ($results) {
         $links = "<div class=\"wpmusiclinks\"><strong>$name</strong>: ";
         foreach ($results as $result) {
            if (!empty($result->val))
               $links   .= '<a href="' . $result->val . '" title="' . $result->name . '">' . $result->name . '</a> | ';
         }
         $links = substr($links, 0, strlen($links) - 3) . "</div>";
         return $links;
      } 
   } elseif ($type == "artist") {
      wpmusiclinks_get_info($name, $type);
      return wpmusiclinks_get_links($name, $type);
   } else return "";
}


/**
 * Replaces the shortcode with its corresponding HTML links
 * @param string $atts the shortcode
 */
function wpmusiclinks_shortcode($atts) {
   $atts = shortcode_atts(
            array(
                   artist => '',
                   festival => '',  
                     ), $atts);
   
   $artist = $atts['artist'];
   $festival = $atts['festival'];
   
   $name = '';
   $type = '';
   
   if (empty($artist)) {
      if (empty($festival)) {
         return '';
      } else {
          $type = "festival";
          $name = $festival;  
      }
   } else {
      $type = "artist";
      $name = $artist;
   }   
     
   return wpmusiclinks_get_links($name, $type);
} 


/**
 * Adds a menu in our dashboard for allowed users.
 */
function wpmusiclinks_add_menu() {
   if (function_exists('add_menu_page')) {
      $test = $plugin_path . $mainfile_path;
      add_menu_page('WP Music Links', "WP Music Links", 8, 'wpmusiclinks/wpmusiclinks.php', '', plugins_url('wpmusiclinks/wpmusiclogo.png'));
   }
   if (function_exists('add_submenu_page')) {
      add_submenu_page('wpmusiclinks/wpmusiclinks.php', 'WP Music Links Desktop', "Desktop", 8, 'wpmusiclinks/wpmusiclinks.php', "wpmusiclinks_desktop");
      add_submenu_page('wpmusiclinks/wpmusiclinks.php', 'WP Music Links Add Item', "Add Item", 8, 'wpmusiclinks/wpmusiclinks-add.php', "wpmusiclinks_add_info_manually");
      add_submenu_page('wpmusiclinks/wpmusiclinks.php', 'WP Music Links Edit Item', "Edit Item", 8, 'wpmusiclinks/wpmusiclinks-edit.php' , "wpmusiclinks_edit_info");
   }
}


/**
 * Shows our desktop in our plugin section.
 */
function wpmusiclinks_desktop(){
   global $wpdb;
   echo "<p><h2>WP Music Links Desktop</h2></p>";
   echo "<p>Hi! We have now " . wpmusiclinks_get_num_items() . " items. Thanks for contributing!</p>";
   $last_items = $wpdb->get_results("SELECT *
                                     FROM $wpdb->musiclinks
                                     ORDER BY id DESC
                                     LIMIT 5");
   if ($last_items) {
      echo "<p><h3>Last added items:</h3></p>";
      echo "<p><ul>";
      foreach ($last_items as $item) {
         echo "<li>" . $item->name . "</li>";
      }
      echo "</ul></p>";
   }
   
}


/**
 * creates tables when activating the plugin
 */
add_action('activate_' . $plugin_path . $mainfile_path, 'wpmusiclinks_create_tables');

/**
 * Shortcode replaced by links. [musiclinks type="name"]
 */
add_shortcode('musiclinks', 'wpmusiclinks_shortcode');

/**
 * adds the plugin menu to the admin menu
 * 
 */
add_action('admin_menu', 'wpmusiclinks_add_menu');

// check permissions?
?>