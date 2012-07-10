<?php

global $wpdb;

/**
 * Form where we can edit manually (inside WordPress) the information for an artist, festival...
 */
function wpmusiclinks_edit_info() { ?>
   <form method="post" action="<?php echo admin_url('admin.php?page=wpmusiclinks/wpmusiclinks-edit.php'); ?>">
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

?>