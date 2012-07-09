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
   
$plugin_path = 'wpmusiclinks.php';


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
 *       - mb_id : in case of "artist", music brainz id; otherwise, null
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
                              `link_type_name` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
                              `link_value` varchar(150)  CHARACTER SET utf8 NOT NULL DEFAULT '',
                              `link_order` int(11) UNSIGNED,
                              PRIMARY KEY (`id`, `link_type`),
                             CONSTRAINT fk_musiclinks_rel
                               FOREIGN KEY (id)
                               REFERENCES $wpdb->musiclinks (id)) $charset_collate;";

   maybe_create_table($wpdb->musiclinks, $create_table['links']);
   maybe_create_table($wpdb->musiclinksr, $create_table['linksr']);   
   
   // we add one example to our database
   $name = 'Metallica';
   $website = 'http://www.metallica.com';
   $facebook = 'https://www.facebook.com/metallica';
   $twitter = 'https://www.twitter.com/metallica';
   $lastfm = 'http://last.fm/music/metallica';
   
   wpmusiclinks_add_artist($name, '', $website, $facebook, $twitter, $lastfm);
   
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
 * 
 * @param unknown_type $artist
 */
function wpmusiclinks_get_mbid($artist) {
   $xml = @simplexml_load_file('http://musicbrainz.org/ws/2/artist/?query=artist:' . $artist);
   foreach($xml->{'artist-list'} as $artistlist) {
      foreach($artistlist->artist as $artistinfo) {
         $mbid = $artistinfo['id'];
         return $mbid;
      }
   }
   return "";
}


/**
 * Gets the information from MusicBrainz API and stores it into our database.
 * @param string $name name of artist
 */
function wpmusiclinks_get_info($name) {
   require_once('simple_html_dom.php');
   
   $mbid = wpmusiclinks_get_mbid($name);
   $url = "http://musicbrainz.org/artist/" . $mbid;
   
   $html = @file_get_html($url);
   
   $twitter = "";
   $facebook = "";
   $website = "";
   
   foreach($html->find('ul.external_links li') as $a) {
      if ($a->class == "twitter") $twitter = $a->first_child()->href;
      elseif ($a->class == "facebook") $facebook = $a->first_child()->href;
      elseif ($a->class == "home") $website = $a->first_child()->href;
   }
      
   $lastfm = "http://last.fm/music/" . $name;
   echo " TW: " . $twitter;
   echo " FB: " . $facebook;
   echo " Web: " . $website;
   
   wpmusiclinks_add_artist($name, '', $website, $facebook, $twitter, $lastfm);
    
}

/**
 * Adds an artist with its information on our database
 * @param string $name Name of the artist
 * @param string $website Website of the artist
 * @param string $facebook Facebook of the artist
 * @param string $twitter Twitter of the artist
 * @param string $lastfm Last.fm profile of the artist
 */
function wpmusiclinks_add_artist($name, $mbid, $website, $facebook, $twitter, $lastfm) {
   global $wpdb;
   
   $wpdb->insert($wpdb->musiclinks, 
            array ('name' => $name,
                   'type' => 'artist'),
            array ('%s', '%s')
            );
   
   $lastid = $wpdb->insert_id;
   
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($lastid, 'website', 'Official website', '$website', 1)";
   $wpdb->query($query);
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($lastid, 'facebook', 'Facebook', '$facebook', 2)";
   $wpdb->query($query);
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($lastid, 'twitter', 'Twitter', '$twitter', 3)";
   $wpdb->query($query);
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($lastid, 'lastfm', 'Last.fm', '$lastfm', 4)";
   $wpdb->query($query);
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
         $links = "<strong>$name</strong>: ";
         foreach ($results as $result) {
            $links   .= '<a href="' . $result->val . '" title="' . $result->name . '">' . $result->name . '</a> | ';
         }
         $links = substr($links, 0, strlen($links) - 3);
         return $links;
      } 
   } elseif ($type == "artist") {
      wpmusiclinks_get_info($name, $type);
      //return wpmusiclinks_get_links($name, $type);
      // infinite loop; for now on
      return "";
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
      $post = str_replace($post, '[musiclinks ' . type . '="' . name . '"]', wpmusiclinks_get_links($name, $type));
   }
   return $post;
} 


/**
 * Parses old links from older posts and stores the information in our database
 * @param string $html the HTML code from the links from previous posts
 */
function wpmusiclinks_parse_existing_links($html) {
   // regex and we get the website, facebook and twitter
}


/**
 * Form where we can add manually (inside WordPress) the information for an artist, festival...
 */
function wpmusiclinks_add_info_manually() { ?>
   <form method="post" action="<?php echo admin_url('admin.php?page=2'.plugin_basename(__FILE__)); ?>">
   <?php //wp_nonce_field('wp-polls_add-poll'); ?>
   <div class="wrap">
      <h2><?php _e('Add Item', 'wpmusiclinks'); ?></h2>
      <h3><?php _e('Item information', 'wpmusiclinks'); ?></h3>
      <table class="form-table">
         <tr>
            <th width="20%" scope="row" valign="top"><?php _e('Item name', 'wpmusiclinks') ?></th>
            <td width="80%"><input type="text" size="70" name="wpmusiclinks_name" value="" /></td>
         </tr>
         <tr>
            <th width="20%" scope="row" valign="top"><?php _e('Item type', 'wpmusiclinks') ?></th>
            <td width="80%"><input type="text" size="70" name="wpmusiclinks_type" value="" /></td>
         </tr>         
      </table>


      <p style="text-align: center;"><input type="submit" name="do" value="<?php _e('Add Item', 'wpmusiclinks'); ?>"  class="button-primary" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'wpmusiclinks'); ?>" class="button" onclick="javascript:history.go(-1)" /></p>
   </div>
   </form>   <?
}


/**
 * Form where we can edit manually (inside WordPress) the information for an artist, festival...
 */
function wpmusiclinks_edit_info() { ?>
   <form method="post" action="<?php echo admin_url('admin.php?page=3'.plugin_basename(__FILE__)); ?>">
   <?php //wp_nonce_field('wp-polls_add-poll'); ?>
   <div class="wrap">
      <h2><?php _e('Edit Item', 'wpmusiclinks'); ?></h2>
      <h3><?php _e('Item information', 'wpmusiclinks'); ?></h3>
      <table class="form-table">
         <tr>
            <th width="20%" scope="row" valign="top"><?php _e('Item name', 'wpmusiclinks') ?></th>
            <td width="80%"><input type="text" size="70" name="wpmusiclinks_name" value="" /></td>
         </tr>
         <tr>
            <th width="20%" scope="row" valign="top"><?php _e('Item type', 'wpmusiclinks') ?></th>
            <td width="80%"><input type="text" size="70" name="wpmusiclinks_type" value="" /></td>
         </tr>         
      </table>


      <p style="text-align: center;"><input type="submit" name="do" value="<?php _e('Edit Item', 'wpmusiclinks'); ?>"  class="button-primary" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'wpmusiclinks'); ?>" class="button" onclick="javascript:history.go(-1)" /></p>
   </div>
   </form>   <?
   
}


/**
 * Adds a menu in our dashboard for allowed users.
 */
function wpmusiclinks_add_menu() {
   if (function_exists('add_menu_page')) {
      add_menu_page('WP Music Links', "WP Music Links", 8, basename(__file__), '', plugins_url('wpmusiclogo.png', __FILE__));
   }
   if (function_exists('add_submenu_page')) {
      add_submenu_page(basename(__file__), 'WP Music Links Desktop', "Desktop", 8, basename(__file__), "wpmusiclinks_desktop");
      add_submenu_page(basename(__file__), 'WP Music Links Add Item', "Add Item", 8, '2' . basename(__file__) , "wpmusiclinks_add_info_manually");
      add_submenu_page(basename(__file__), 'WP Music Links Edit Item', "Edit Item", 8, '3' . basename(__file__) , "wpmusiclinks_edit_info");
   }
}


/**
 * Shows our info in our plugin section.
 */
function wpmusiclinks_desktop(){
   global $wpdb;
   echo "<p>Hi!</p>";
   //echo wpmusiclinks_get_links("Metallica", "artist");
   //echo wpmusiclinks_get_links("Megadeth", "artist");
   wpmusiclinks_get_info("Muse");
   
}

// creates tables when activating the plugin
add_action('activate_' . $plugin_path, 'wpmusiclinks_create_tables');
// replaces the shortcodes in our content
add_filter('the_content', 'wpmusiclinks_replace_shortcodes');
// adds the plugin menu to the admin menu
add_action('admin_menu', 'wpmusiclinks_add_menu');

// check permissions?
?>