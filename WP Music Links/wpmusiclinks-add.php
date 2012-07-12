<?php
/**
 * Adding section of WP Music Links
 */

global $wpdb;
$wpdb->musiclinks = $wpdb->prefix . 'musiclinks';
$wpdb->musiclinksr = $wpdb->prefix . 'musiclinks_rel';

add_action('init', 'wpmusiclinks_lang');

/**
 * Returns the MusicBrainz id of a given artist
 * @param string $artist
 */
function wpmusiclinks_get_mbid($artist) {
   $url = (strpos($artist, "-")) ? "http://musicbrainz.org/ws/2/artist/?query=" . urlencode($artist): 
                                   "http://musicbrainz.org/ws/2/artist/?query=artist:" . urlencode($artist);
   $xml = @simplexml_load_file($url);
   if (empty($xml)) die('Problem with the xml');
   foreach($xml->{'artist-list'} as $artistlist) {
      foreach($artistlist->artist as $artistinfo) {
         if (strtolower(str_replace("‐", "-", str_replace("’", "'", $artistinfo->name))) == strtolower($artist)) {
            $mbid = $artistinfo['id'];
            return $mbid;
         }
      }
   }
   return "";
}


/**
 * Gets the information from MusicBrainz API and stores it into our database.
 * @param string $name name of artist
 */
function wpmusiclinks_get_info($name) {
   require_once('simple_html_dom/simple_html_dom.php');
    
   $mbid = wpmusiclinks_get_mbid($name);
   $url = "http://musicbrainz.org/artist/" . $mbid;
   $name = str_replace("'", "’", $name);
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
 * @param string $mbid Music Brainz ID of the artist
 * @param string $website Website of the artist
 * @param string $facebook Facebook of the artist
 * @param string $twitter Twitter of the artist
 * @param string $lastfm Last.fm profile of the artist
 */
function wpmusiclinks_add_artist($name, $mbid, $website, $facebook, $twitter, $lastfm) {
   wpmusiclinks_add_item($name, "artist", $mbid, $website, $facebook, $twitter, $lastfm);
}


/**
 * Adds a festival with its information on our database
 * @param string $name Name of the festival
 * @param string $website Website of the festival
 * @param string $facebook Facebook of the festival
 * @param string $twitter Twitter of the festival
 * @param string $lastfm Last.fm event of the festival
 */
function wpmusiclinks_add_festival($name, $website, $facebook, $twitter, $lastfm) {
   wpmusiclinks_add_item($name, "festival", '', $website, $facebook, $twitter, $lastfm);
}


/**
 * 
 * @param string $name Name of the item
 * @param string $type Type of the item
 * @param string $mbid In case of an artist, Music Brainz of the artist; otherwise, empty
 * @param string $website Website of the item
 * @param string $facebook Facebook of the item
 * @param string $twitter Twitter of the item
 * @param string $lastfm Last.fm profile/event of the item
 */
function wpmusiclinks_add_item($name, $type, $mbid, $website, $facebook, $twitter, $lastfm) {
   global $wpdb;
   
   if ($type!="artist") {
      $wpdb->insert($wpdb->musiclinks,
               array ('name' => $name,
                        'type' => $type)
             );      
   } else {
      $wpdb->insert($wpdb->musiclinks,
               array ('name' => $name,
                        'type' => $type,
                        'mbid' => $mbid)
             );
   }
   
   $lastid = $wpdb->insert_id;
   
   $wpdb->insert($wpdb->musiclinksr,
               array ('id' => $lastid,
                      'link_type' => 'website',
                      'link_type_name' => __('Official website', 'wpmusiclinks'),
                      'link_value' => $website,
                      'link_order' => '1')
            );
   $wpdb->insert($wpdb->musiclinksr,
            array ('id' => $lastid,
                     'link_type' => 'facebook',
                     'link_type_name' => 'Facebook',
                     'link_value' => $facebook,
                     'link_order' => '2')
   );

   $wpdb->insert($wpdb->musiclinksr,
            array ('id' => $lastid,
                     'link_type' => 'twitter',
                     'link_type_name' => 'Twitter',
                     'link_value' => $twitter,
                     'link_order' => '3')
   );

   $wpdb->insert($wpdb->musiclinksr,
            array ('id' => $lastid,
                     'link_type' => 'lastfm',
                     'link_type_name' => 'Last.fm',
                     'link_value' => $lastfm,
                     'link_order' => '4')
   );
       

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
function wpmusiclinks_add_info_manually() { 


   ### Form Processing 
   if(!empty($_POST['do'])) {
   	// Decide What To Do
   	switch($_POST['do']) {
   		case __('Add Item', 'wpmusiclinks'):
   			$name = str_replace("'", "’", addslashes(trim($_POST['wpmusiclinks_name'])));
   			$type = addslashes(trim($_POST['wpmusiclinks_type']));
   			$mbid = addslashes(trim($_POST['wpmusiclinks_mbid']));
   			$website = addslashes(trim($_POST['wpmusiclinks_website']));
   			$facebook = addslashes(trim($_POST['wpmusiclinks_facebook']));
   			$twitter = addslashes(trim($_POST['wpmusiclinks_twitter']));
   			$lastfm = addslashes(trim($_POST['wpmusiclinks_lastfm']));
   			if (empty($lastfm) && $type="artist") $lastfm = "http://last.fm/music/" . $name;
   			
   			// yes, I'm omitting the order right now... sorry for it! I'll fix it later, promised.
   			if ($type == "artist") 
   			   wpmusiclinks_add_artist($name, $mbid, $website, $facebook, $twitter, $lastfm);
   			elseif ($type != "festival") {
   			   $text = '<p style="color: red;">'.sprintf(__('Problem Adding Item \'%s\': Not an artist or festival.', 'wpmusiclinks'), stripslashes($name)).'</p>';
   			   break;
   			} else 
   			   wpmusiclinks_add_festival($name, $website, $facebook, $twitter, $lastfm);
   			
   			$text = '<p style="color: green;">'.sprintf(__('Item \'%s\' Added Successfully.', 'wpmusiclinks'), stripslashes($name)).' <a href="admin.php?page=wpmusiclinks/wpmusiclinks.php">'.__('Manage Items', 'wpmusiclinks').'</a></p>';
   
   			break;
   	}
   }
   
    if(!empty($text)) {
      echo '<!-- Last Action --><div id="message" class="updated fade">'.stripslashes($text).'</div>';
   } ?>
   
   <form method="post" action="<?php echo admin_url('admin.php?page='.plugin_basename(__FILE__)); ?>">
   <div class="wrap">
   	<h2><?php _e('Add item', 'wpmusiclinks'); ?></h2>
   	<h3><?php _e('Item information', 'wpmusiclinks'); ?></h3>
   	<table class="form-table">
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Name', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_name" value="" /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Type', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_type" value="" /></td>
   		</tr>
   		<tr>
   			<th width="30%" scope="row" valign="top"><?php _e('MusicBrainz ID', 'wpmusiclinks') ?> <?php _e('(Just for artists)', 'wpmusiclinks') ?></th>
   			<td width="70%"><input type="text" size="50" name="wpmusiclinks_mbid" value="" /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Website', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_website" value="" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_web_order" value="1" disabled /></td>
   
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Facebook', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_facebook" value="" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_fb_order" value="2" disabled /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Twitter', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_twitter" value="" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_tw_order" value="3" disabled /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Last.fm', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_lastfm" value="" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_fm_order" value="4" disabled /></td>
   		</tr>
   
   	</table>
   	
   	
   	<p><em><?php _e('Yes, order is disabled so far. It will be like this until I change it. My apologies...', 'wpmusiclinks') ?></em></p>
   
   	<p style="text-align: center;"><input type="submit" name="do" value="<?php _e('Add Item', 'wpmusiclinks'); ?>"  class="button-primary" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'wpmusiclinks'); ?>" class="button" onclick="javascript:history.go(-1)" /></p>
   </div>
   </form>
  <?
}

?>