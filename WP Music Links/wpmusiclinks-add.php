<?php

global $wpdb;
$wpdb->musiclinks = $wpdb->prefix . 'musiclinks';
$wpdb->musiclinksr = $wpdb->prefix . 'musiclinks_rel';



/**
 * Returns the MusicBrainz id of a given artist
 * @param string $artist
 */
function wpmusiclinks_get_mbid($artist) {
   $xml = @simplexml_load_file('http://musicbrainz.org/ws/2/artist/?query=artist:' . $artist);
   if (empty($xml)) die('');
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
    
   wpmusiclinks_add_artist($name, $mbid, $website, $facebook, $twitter, $lastfm);

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
                     'type' => 'artist',
                     'mbid' => $mbid)
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
   <form method="post" action="<?php echo admin_url('admin.php?page=wpmusiclinks/wpmusiclinks-add.php'); ?>">
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

?>