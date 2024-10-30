<div id="imagedrop-size-wrapper">
	<label for="imagedrop-size"><?php _e("Image size", $this->plugin_name) ?></label>
	<select name="imagedrop-size" id="imagedrop-size">
		<option value="full" data-width="auto" data-height="auto"><?php echo _e("Full", $this->plugin_name); ?></option>
		<option value="large" data-width="<?php echo $large_w ?>" data-height="<?php echo $large_h ?>"><?php echo _e("Large", $this->plugin_name); ?> <?php echo $large_str ?></option>
		<option value="medium" data-width="<?php echo $medium_w ?>" data-height="<?php echo $medium_h ?>"><?php echo _e("Medium", $this->plugin_name); ?> <?php echo $medium_str ?></option>
		<option value="thumb" data-width="<?php echo $thumb_w ?>" data-height="<?php echo $thumb_h ?>"><?php echo _e("Thumbnail", $this->plugin_name); ?> <?php echo $thumb_str ?></option>
	</select>
</div>
<div id="imagedrop-search-wrapper">
	<label for="imagedrop-search"><?php _e("Search", $this->plugin_name) ?></label>
	<input type="text" name="imagedrop-search" id="imagedrop-search">
	<button id="imagedrop-search-button" class="search-button button"><?php _e("Search", $this->plugin_name) ?></button>	
</div>
<div class="slide-controls">
	<button class="slide-prev button" disabled><?php _e("&larr; Prev page", $this->plugin_name)?></button>
	<button class="slide-next button" <?php echo count($result) <= $this->grid_size ? "disabled" : ""?> ><?php _e("Next page &rarr;", $this->plugin_name)?></button>
</div>

<div id="slides-wrapper">
	<div class="slide slide-0 loaded">
		<?php $this->render_images($images) ?>
	</div>

<?php for ($slide = 1; $slide < ceil( count($result) / $this->grid_size ); $slide++) : ?>
	<?php // Note: This markup is duplicated in the loading_template JS variable below ?> 
	<div class="slide slide-<?php echo $slide ?> hidden">
		<div class="slide-loading">
			<img src="<?php bloginfo("wpurl")?>/wp-includes/js/thickbox/loadingAnimation.gif" width="208" height="13" alt="Loading ..." />
		</div>
	</div>
<?php endfor; ?>

</div>

<div class="slide-controls">
	<button class="slide-prev button" disabled><?php _e("&larr; Prev page", $this->plugin_name)?></button>
	<button class="slide-next button" <?php echo count($result) <= $this->grid_size ? "disabled" : ""?> ><?php _e("Next page &rarr;", $this->plugin_name)?></button>
</div>

<script type="text/javascript">
var id_data = {slides:<?php echo ceil(count($result) / $this->grid_size) ?>,images:<?php echo count($result) ?>,gridSize:<?php echo $this->grid_size ?>};
var loading_template = '<div class="slide slide-${slide} hidden"><div class="slide-loading"><img src="<?php bloginfo("wpurl")?>/wp-includes/js/thickbox/loadingAnimation.gif" width="208" height="13" alt="Loading ..." /></div></div>';
</script>