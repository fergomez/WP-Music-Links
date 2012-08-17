<?php
/**
 * Updating section of WP Music Links
 */

global $wpdb;
$wpdb->musiclinks = $wpdb->prefix . 'musiclinks';
$wpdb->musiclinksr = $wpdb->prefix . 'musiclinks_rel';

$mainfile_path = 'wpmusiclinks.php';
$addfile_path = 'wpmusiclinks-add.php';

require_once($mainfile_path);
require_once($addfile_path);

/**
 * Adds an item with its information on our database
 * @param string $name Name of the item
 * @param string $mbid Music Brainz ID of the item
 * @param string $website Website of the item
 * @param string $facebook Facebook of the item
 * @param string $twitter Twitter of the item
 * @param string $lastfm Last.fm profile of the item
 */
function wpmusiclinks_update_item($id, $type, $name, $mbid, $website, $facebook, $twitter, $lastfm) {
   global $wpdb;   
   if ($type!="artist") {
      $wpdb->update($wpdb->musiclinks,
               array ('name' => $name,
                      'type' => $type),
               array ('id' => $id ));      
   } else {
      $wpdb->update($wpdb->musiclinks,
               array ('name' => $name,
                      'type' => $type,
                      'mbid' => $mbid),
               array ('id' => $id));
   }
   
   // I feel very bad for this, but "update" was giving me tons of problems. This is just temporary. Promised   
   $query = "DELETE FROM $wpdb->musiclinksr WHERE id=" . $id;
   $wpdb->query($query);
   
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($id, 'website', '" . __('Official website', 'wpmusiclinks') . "', '$website', 1)";
   $wpdb->query($query);
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($id, 'facebook', 'Facebook', '$facebook', 2)";
   $wpdb->query($query);
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($id, 'twitter', 'Twitter', '$twitter', 3)";
   $wpdb->query($query);
   $query = "INSERT INTO $wpdb->musiclinksr (id, link_type, link_type_name, link_value, link_order) VALUES
   ($id, 'lastfm', 'Last.fm', '$lastfm', 4)";
   $wpdb->query($query);
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
function wpmusiclinks_update_artist($id, $name, $mbid, $website, $facebook, $twitter, $lastfm) {
   wpmusiclinks_update_item($id, "artist", $name, $mbid, $website, $facebook, $twitter, $lastfm);
}


/**
 * Adds a festival with its information on our database
 * @param string $name Name of the festival
 * @param string $website Website of the festival
 * @param string $facebook Facebook of the festival
 * @param string $twitter Twitter of the festival
 * @param string $lastfm Last.fm event of the festival
 */
function wpmusiclinks_update_festival($id, $name, $website, $facebook, $twitter, $lastfm) {
   wpmusiclinks_update_item($id, "festival", $name, '', $website, $facebook, $twitter, $lastfm);
}


/**
 * Form where we can edit manually (inside WordPress) the information for an artist, festival...
 */
function wpmusiclinks_edit_info() { 
   
   $name = $type = $mbid = $website = $facebook = $twitter = $lastfm = $id = "";
   ### Form Processing 
   if(!empty($_POST['do'])) {
   	// Decide What To Do
   	switch($_POST['do']) {
   	    case __('Select Item', 'wpmusiclinks'):
   		   $name = str_replace("'", "’", addslashes(trim($_POST['wpmusiclinks_name'])));
   		   $type = addslashes(trim($_POST['wpmusiclinks_type']));
   		   
   		   if (!wpmusiclinks_cache_check($name, $type)) {
   		      $text = '<p style="color: red;">'.sprintf(__('Problem updating Item \'%s\': Not an artist or festival found in our database. Please, try another one.', 'wpmusiclinks'), stripslashes($name)).'</p>';
   		      $type = "";
   		      break;
   		   }
   		   
   		   if ($type == "artist") {
   		      $mbid =  wpmusiclinks_get_artist_mbid($name);
   		      $website = wpmusiclinks_get_artist_website($name);
   		      $facebook = wpmusiclinks_get_artist_facebook($name);
   		      $twitter = wpmusiclinks_get_artist_twitter($name);
   		      $lastfm = wpmusiclinks_get_artist_lastfm($name);
   		      $id = wpmusiclinks_get_artist_id($name);
   		      
   		   } elseif ($type == "festival") {
   		      $website = wpmusiclinks_get_festival_website($name);
   		      $facebook = wpmusiclinks_get_festival_facebook($name);
   		      $twitter = wpmusiclinks_get_festival_twitter($name);
   		      $lastfm = wpmusiclinks_get_festival_lastfm($name);
   		      $id = wpmusiclinks_get_festival_id($name);
   		       
   		   } else 
   		      $type = "fail";
   		   
   	       break;
   		case __('Edit Item', 'wpmusiclinks'):
   			$name = str_replace("'", "’", addslashes(trim($_POST['wpmusiclinks_name'])));
   			$type = addslashes(trim($_POST['wpmusiclinks_type']));
   			$mbid = addslashes(trim($_POST['wpmusiclinks_mbid']));
   			$website = addslashes(trim($_POST['wpmusiclinks_website']));
   			$facebook = addslashes(trim($_POST['wpmusiclinks_facebook']));
   			$twitter = addslashes(trim($_POST['wpmusiclinks_twitter']));
   			$lastfm = addslashes(trim($_POST['wpmusiclinks_lastfm']));
   			$id = addslashes(trim($_POST['wpmusiclinks_id']));
   			 
   			if (empty($lastfm) && $type == "artist") $lastfm = "http://last.fm/music/" . $name;   
   			
   			// yes, I'm omitting the order right now... sorry for it! I'll fix it later, promised.
   			if ($type == "artist") 
   			   wpmusiclinks_update_artist($id, $name, $mbid, $website, $facebook, $twitter, $lastfm);
   			elseif ($type != "festival") {
   			   $text = '<p style="color: red;">'.sprintf(__('Problem updating Item \'%s\': Not an artist or festival.', 'wpmusiclinks'), stripslashes($name)).'</p>';
   			   break;
   			} else 
   			   wpmusiclinks_update_festival($id, $name, $website, $facebook, $twitter, $lastfm);
   			
   			$text = '<p style="color: green;">'.sprintf(__('Item \'%s\' Updated Successfully.', 'wpmusiclinks'), stripslashes($name)).' <a href="admin.php?page=wpmusiclinks/wpmusiclinks.php">'.__('Manage Items', 'wpmusiclinks').'</a></p>';
            
   			break;
   	}
   }
   
    if(!empty($text)) {
      echo '<!-- Last Action --><div id="message" class="updated fade">'.stripslashes($text).'</div>';
    }
    
    if($type=="fail") {
       echo '<!-- Last Action --><div id="message" class="updated fade"><p style="color: red;">' . _e('Problem getting item', 'wpmusiclinks') . '</p></div>';
    }
    
   ?>
   
   <form method="post" action="<?php echo admin_url('admin.php?page='.plugin_basename(__FILE__)); ?>">
   <div class="wrap">
   	<h2><?php _e('Update item', 'wpmusiclinks'); ?></h2>
   	<h3><?php _e('Item information', 'wpmusiclinks'); ?></h3>
   	<table class="form-table">
   	    <tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Name', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_name" value="<?php echo $name; ?>" /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Type', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_type" value="<?php echo $type; ?>" /></td>
   		</tr>
   		
   		<?php if ($type == "artist" || $type == "festival") { ?>
   		<tr>
   			<th width="30%" scope="row" valign="top"><?php _e('MusicBrainz ID', 'wpmusiclinks') ?> <?php _e('(Just for artists)', 'wpmusiclinks') ?></th>
   			<td width="70%"><input type="text" size="50" name="wpmusiclinks_mbid" value="<?php echo $mbid; ?>" /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Website', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_website" value="<?php echo $website; ?>" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_web_order" value="1" disabled /></td>
   
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Facebook', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_facebook" value="<?php echo $facebook; ?>" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_fb_order" value="2" disabled /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Twitter', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_twitter" value="<?php echo $twitter; ?>" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_tw_order" value="3" disabled /></td>
   		</tr>
   		<tr>
   			<th width="20%" scope="row" valign="top"><?php _e('Last.fm', 'wpmusiclinks') ?></th>
   			<td width="80%"><input type="text" size="50" name="wpmusiclinks_lastfm" value="<?php echo $lastfm; ?>" /> <?php _e('Order', 'wpmusiclinks') ?> <input type="text" size="1" name="wpmusiclinks_fm_order" value="4" disabled /></td>
   		</tr>   	
   		<tr>
   		   <th><input type="hidden" name="wpmusiclinks_id" value="<?php echo $id; ?>" /><th>
   		</tr>
   </table>
   <p><em><?php _e('Yes, order is disabled so far. It will be like this until I change it. My apologies...', 'wpmusiclinks') ?></em></p>
	
   <p style="text-align: center;"><input type="submit" name="do" value="<?php _e('Edit Item', 'wpmusiclinks'); ?>"  class="button-primary" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'wpmusiclinks'); ?>" class="button" onclick="javascript:history.go(-1)" /></p>
   
   		<?php } else { ?>
   </table>	
   <p style="text-align: center;"><input type="submit" name="do" value="<?php _e('Select Item', 'wpmusiclinks'); ?>"  class="button-primary" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'wpmusiclinks'); ?>" class="button" onclick="javascript:history.go(-1)" /></p>
      		
   		<?php } ?>

   	

</div>
   </form>
  <?
}

?>