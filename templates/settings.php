<div class="wrap">
  <h2>Review Stream Settings</h2>
  <form method="post" action="options.php">
    <?php @settings_fields('wprs_group');?>
    <?php @do_settings_fields('wprs_group');?>
    <?php 
      $type = get_option('rs_type');
      if(!$type) {
        // Default
        $type = 'LocalBusiness';
      }
      $format = get_option('rs_schema');
      if(!$format) {
        // Default
        $format = 'microdata';
      }
      $show_aggregate_rating = get_option('rs_show_aggregate_rating');
      if($show_aggregate_rating === null) {
        // Default
        $show_aggregate_rating = true;
      }
      $show_reviews = get_option('rs_show_reviews');
      if($show_reviews === null) {
        // Default
        $show_reviews = true;
      }
      $show_powered_by = get_option('rs_show_powered_by');
      if($show_powered_by === null) {
        // Default
        $show_powered_by = true;
      }
    ?>

    <p style="font-size:1.2em">Embed the Review Stream into your content using the shortcode [reviewstream]. Also supports count and path attributes, ie, [reviewstream count="3" path="custompath"]</p>

    <table class="form-table">
      <tr valign="top">
        <th scope="row"><label for="rs_type">Entity type</label></th>
        <td><select name="rs_type" id="rs_type">
          <option value="LocalBusiness"<?php echo $type == 'LocalBusiness'?' selected="selected"':'';?>>Local Business</option>
          <option value="Product"<?php echo $type == 'Product'?' selected="selected"':'';?>>Product</option>
        </select></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="rs_schema">Review schema</label></th>
        <td><select name="rs_schema" id="rs_schema">
          <option value="microdata"<?php echo $format == 'microdata'?' selected="selected"':'';?>>Microdata</option>
          <option value="rdfa"<?php echo $format == 'rdfa'?' selected="selected"':'';?>>RDFa</option>
        </select></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="rs_api_token">API token</label></th>
        <td><input type="text" name="rs_api_token" id="rs_api_token" value="<?php echo get_option('rs_api_token');?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="rs_path"><?php echo $this->brand;?> path or shortname</label></th>
        <td><input type="text" name="rs_path" id="rs_path" value="<?php echo get_option('rs_path');?>" /><br /><small>(e.g. for <?php echo $this->brand_domain;?>/yourname use <strong><em>yourname</em></strong>)</small></td>
      </tr>
      <tr valign="top">
        <th scope="row">What to show</th>
        <td><input type="checkbox" name="rs_show_aggregate_rating" id="rs_show_aggregate_rating" value="true"<?php echo $show_aggregate_rating?' checked="checked"':'';?> /><label for="rs_show_aggregate_rating">Aggregate Rating</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="rs_show_reviews" id="rs_show_reviews" value="true"<?php echo $show_reviews?' checked="checked"':'';?> /><label for="rs_show_reviews">Reviews</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="rs_show_powered_by" id="rs_show_powered_by" value="true"<?php echo $show_powered_by?' checked="checked"':'';?> /><label for="rs_show_powered_by">"Powered By" Footer</label></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="rs_default_count">Default review count</label></th>
        <td><input type="text" name="rs_default_count" id="rs_default_count" value="<?php echo get_option('rs_default_count');?>" /><br /><small>Must be between 1 and 20</small></td>
      </tr>
    </table>
    <?php @submit_button(); ?>
  </form>
</div>