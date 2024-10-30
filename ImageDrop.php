<?php
/*
Plugin Name: ImageDrop
Plugin URI: http://montania.se/
Description: Drag 'n' drop your images directly into the editor
Version: 1.1.3
Author: Rickard Andersson
Author URI: http://montania.se/
License: GPL2
	
	Copyright 2011  Montania System AB  (email : info@montania.se)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The default grid size in the meta box
 * @var int
 */
if (!defined("ID_DEFAULT_GRID_SIZE")) 
	define("ID_DEFAULT_GRID_SIZE", 32);

/**
 * The default thumbnail width in the meta box
 * @var int 
 */
if (!defined("ID_DEFAULT_THUMB_WIDTH"))
	define("ID_DEFAULT_THUMB_WIDTH", 59);

/**
 * The default thumbnail height in the meta box
 * @var int 
 */
if (!defined("ID_DEFAULT_THUMB_HEIGHT")) 
	define("ID_DEFAULT_THUMB_HEIGHT", 59);

if (!class_exists('ImageDrop')) : 

/**
 * This plugin will enable you to drag and drop images into the editor
 * @author Rickard Andersson <rickard@montania.se> 
 * @copyright Montania System AB
 * @package ImageDrop
 */
class ImageDrop {

	/**
	 * Name of the plugin used for l10n and enqueueing files 
	 * @var string
	 */
	private $plugin_name 	= "imagedrop";
	
	/**
	 * Width of the thumbnail
	 * @var int
	 */
	private $thumb_width 	= ID_DEFAULT_THUMB_WIDTH;
	
	/**
	 * Height of the thumbnail
	 * @var int 
	 */
	private $thumb_height 	= ID_DEFAULT_THUMB_HEIGHT;
	
	/**
	 * How many images should be shown in the thumbnail grid
	 * @var int 
	 */
	private $grid_size 		= ID_DEFAULT_GRID_SIZE;
		
	/**
	 * The constructor is executed when the class is instatiated and the plugin gets loaded.
	 * @since 1.0
	 */
	function __construct() {
		
		// This plugin doesn't have any front end features.
		if (is_admin()) {
			add_action( 'admin_init', array($this, 'init_meta_box') );	
			add_action( 'wp_ajax_id_load_images', array($this, 'load_images') );
			add_action( 'wp_ajax_id_image_count', array($this, 'image_count') );
			add_action( 'wp_ajax_id_search_images', array($this, 'search_images') );
			add_action( 'wp_ajax_id_load_meta_box', array($this, 'load_meta_box') );
			add_action( 'admin_init', array($this, 'init_settings'));
			
			add_filter( 'plugin_action_links', array($this, 'plugin_action_links'), 10, 2 );
			
		    load_plugin_textdomain( $this->plugin_name, false, "/{$this->plugin_name}/language/" );
		    				
			$this->init_scripts();
			$this->init_styles();
			$this->init_options(); 			
		}		
	}
	
	/**
	 * This function is attached to the "plugin_action_links" action and will output the settings url for this plugin
	 * @since 1.1
	 * @param array $links
	 * @param string $file
	 * @return array
	 *
	 */
	function plugin_action_links($links, $file) {
		
		$this_plugin = sprintf("%s/%s", $this->plugin_name, basename( __FILE__ ));
				
		if ($file == $this_plugin) {
			$settings_link = '<a href="'. get_admin_url(null, "options-media.php") .'">Settings</a>';
			array_unshift($links, $settings_link);
		}
		
		return $links;
	}
	
	/**
	 * This function will fetch the current settings from the DB, with fallbacks to their default value
	 * @return void
	 * @since 1.1
	 * @uses ID_DEFAULT_GRID_SIZE
	 * @uses ID_DEFAULT_THUMB_WIDTH
	 * @uses ID_DEFAULT_THUMB_HEIGHT
	 */
	function init_options() {
		$this->grid_size    = get_option( $this->plugin_name . '-gridsize',    ID_DEFAULT_GRID_SIZE );
		$this->thumb_width  = get_option( $this->plugin_name . '-thumbwidth',  ID_DEFAULT_THUMB_WIDTH );
		$this->thumb_height = get_option( $this->plugin_name . '-thumbheight', ID_DEFAULT_THUMB_HEIGHT );		
	}
	
	/**
	 * This function will add the plugin settings to the "media" settings page
	 * @since 1.1
	 * @return void
	 */
	function init_settings() {
		add_settings_section( $this->plugin_name, 'ImageDrop', array($this, 'settings_section_callback'), 'media' );
		
		register_setting( $this->plugin_name, $this->plugin_name . '-gridsize',    array($this, 'settings_int_validation') );
		register_setting( $this->plugin_name, $this->plugin_name . '-thumbwidth',  array($this, 'settings_int_validation') );
		register_setting( $this->plugin_name, $this->plugin_name . '-thumbheight', array($this, 'settings_int_validation') );
		
		add_settings_field( $this->plugin_name . '-gridsize',    __("Grid size",    $this->plugin_name), array($this, 'settings_gridsize_render'),    "media", $this->plugin_name, array('label_for' => $this->plugin_name . '-gridsize') );
		add_settings_field( $this->plugin_name . '-thumbwidth',  __("Thumb width",  $this->plugin_name), array($this, 'settings_thumbwidth_render'),  "media", $this->plugin_name, array('label_for' => $this->plugin_name . '-thumbwidth') );
		add_settings_field( $this->plugin_name . '-thumbheight', __("Thumb height", $this->plugin_name), array($this, 'settings_thumbheight_render'), "media", $this->plugin_name, array('label_for' => $this->plugin_name . '-thumbheight') );		
	}	
	

	/**
	 * This function will render the input for the grid size setting
	 * @since 1.1
	 * @return void
	 * @uses ID_DEFAULT_GRID_SIZE
	 */
	function settings_gridsize_render() { ?>
		<input type="number" name="<?php echo $this->plugin_name ?>-gridsize" id="<?php echo $this->plugin_name ?>-gridsize" value="<?php echo get_option( $this->plugin_name . '-gridsize', ID_DEFAULT_GRID_SIZE ) ?>">
		<span class="description"><?php _e("How many images should be shown in the meta box", $this->plugin_name)?></span>
	<?php 
	}
	
	/**
	 * This function will render the input for the thumb width setting
	 * @since 1.1
	 * @return void
	 * @uses ID_DEFAULT_THUMB_WIDTH
	 */
	function settings_thumbwidth_render() { ?>
		<input type="number" name="<?php echo $this->plugin_name ?>-thumbwidth" id="<?php echo $this->plugin_name ?>-thumbwidth" value="<?php echo get_option( $this->plugin_name . '-thumbwidth', ID_DEFAULT_THUMB_WIDTH ) ?>">
		<span class="description"><?php _e("Width of the thumnails in the meta box, measured in pixels", $this->plugin_name)?></span>
	<?php 
	}
	
	/**
	 * This function will render the input for the thumb height setting
	 * @since 1.1
	 * @return void
	 * @uses ID_DEFAULT_THUMB_HEIGHT
	 */
	function settings_thumbheight_render() { ?>
		<input type="number" name="<?php echo $this->plugin_name ?>-thumbheight" id="<?php echo $this->plugin_name ?>-thumbheight" value="<?php echo get_option( $this->plugin_name . '-thumbheight', ID_DEFAULT_THUMB_HEIGHT ) ?>">
		<span class="description"><?php _e("Height of the thumnails in the meta box, measured in pixels", $this->plugin_name)?></span>
	<?php 
	}
	
	/**
	 * This function will validate the input from the settings form and convert it to an integer. If the input
	 * isn't numeric the number zero is returned.
	 * @param mixed $input
	 * @since 1.1
	 * @return int 
	 */
	function settings_int_validation($input) {
		return is_numeric($input) ? (int) $input : 0;
	}

	/** 
	 * This function will be called when the settings section is loaded onto the page
	 * @since 1.1
	 * @return void
	 */
	function settings_section_callback() {
		settings_fields( $this->plugin_name );
	}
	
	/**
	 * This function is hooked to the "wp_ajax_id_image_count" action and will return the
	 * number of images currently in the DB to detect any changes and then reload the interface
	 * @return void
	 * @since 1.0
	 * @uses $wpdb
	 */
	function image_count() {

		/** @var $wpdb wpdb  */
		global $wpdb;

		$q = "SELECT count(1) as count
				FROM `{$wpdb->prefix}posts` p
				WHERE `post_type` = 'attachment' 
				AND post_mime_type LIKE 'image/%'
				ORDER BY p.id DESC";

		$result = $wpdb->get_results($q);
		
		die($result[0]->count);
	}
	
	/**
	 * This function is hooked to the "wp_ajax_id_load_images" action and serves pages of
	 * images to the meta box by ajax calls.
	 * @return void 
	 * @since 1.0
	 * @uses $wpdb
	 */
	function load_images() {

		/** @var $wpdb wpdb  */
		global $wpdb;
	
		$slide = is_numeric($_POST['slide']) ? $_POST['slide'] : -1;
	
		if ($slide > -1) {
			
			$limit = $slide * $this->grid_size;
			
			$q = "SELECT p.ID, p.guid
				FROM `{$wpdb->prefix}posts` p
				WHERE `post_type` = 'attachment' 
				AND post_mime_type LIKE 'image/%'
				ORDER BY p.id DESC
				LIMIT $limit,{$this->grid_size}";
			
			$result = $wpdb->get_results($q);
					
			if (count($result) > 0) {

				$images = array();

				foreach ($result as $post) {
					$images[] = array(
						'full' => wp_get_attachment_image_src( $post->ID, 'full' ),
						'large' => wp_get_attachment_image_src( $post->ID, 'large' ),
						'medium' => wp_get_attachment_image_src( $post->ID, 'medium' ),
						'thumbnail' => wp_get_attachment_image_src( $post->ID, 'thumbnail' ),
						'id' => $post->ID
					);
				}

				$this->render_images($images);
			}
		}
	
		die();
	}
	
	/**
	 * This function is hooked to the "wp_ajax_id_search_images" action and will return the matching
	 * images to a user search. Unlike load_images the result isn't paginated, so that needs to be handled
	 * by the javascript that receives the response.
	 * @return void 
	 * @since 1.1
	 * @uses $wpdb
	 */	
	function search_images() {

		/** @var $wpdb wpdb  */
		global $wpdb;
		
		$query = esc_sql( $_POST['query'] );
		
		if (strlen($query) > 0) {
			$q = "SELECT p.ID, p.guid
				FROM `{$wpdb->prefix}posts` p
				WHERE `post_type` = 'attachment' 
				AND `post_mime_type` LIKE 'image/%'
				AND `post_title` LIKE '%{$query}%'
				ORDER BY p.id DESC";
							
			$result = $wpdb->get_results($q);
					
			if (count($result) > 0) {

				$images = array();

				foreach ($result as $post) {
					$images[] = array(
						'full' => wp_get_attachment_image_src( $post->ID, 'full' ),
						'large' => wp_get_attachment_image_src( $post->ID, 'large' ),
						'medium' => wp_get_attachment_image_src( $post->ID, 'medium' ),
						'thumbnail' => wp_get_attachment_image_src( $post->ID, 'thumbnail' ),
						'id' => $post->ID
					);
				}

				$this->render_images($images);
			}
		}
	
		die();
	}
	
	/**
	 * This function is hooked to the "admin_init" action and will add the meta box to the edit page
	 * @todo Add custom post types
	 * @since 1.0
	 * @return void
	 */
	function init_meta_box() {
		
		$post_types = array_unique( array_merge( get_post_types( array('show_ui' => true, 'capability_type' => 'post' ) ), get_post_types( array('show_ui' => true, 'capability_type' => 'page' ) ) ) );

		foreach ($post_types as $post_type) {
			add_meta_box( $this->plugin_name, __("Drag 'n' drop images", $this->plugin_name), array($this, 'meta_box'), $post_type, 'side', 'high' );
		}
	}
	
	/**
	 * Private function to render the image tags with all the metadata needed for the plugin to work
	 * @param array $images
	 * @since 1.0
	 * @return void
	 */
	private function render_images($images) {
		foreach ($images as $image) : ?>
	
		<img src="<?php echo $image['thumbnail'][0] ?>" 
			width="<?php echo $this->thumb_width ?>" 
			height="<?php echo $this->thumb_height ?>" 
			alt="" 
			class="imagedrop" 
			data-width="<?php echo $image['full'][1] ?>" 
			data-height="<?php echo $image['full'][2] ?>" 
			data-url="<?php echo $image['full'][0] ?>" 
			data-large-width="<?php echo $image['large'][1] ?>" 
			data-large-height="<?php echo $image['large'][2] ?>" 
			data-large-url="<?php echo $image['large'][0] ?>"
			data-medium-width="<?php echo $image['medium'][1] ?>" 
			data-medium-height="<?php echo $image['medium'][2] ?>" 
			data-medium-url="<?php echo $image['medium'][0] ?>" 
			data-thumbnail-width="<?php echo $image['thumbnail'][1] ?>" 
			data-thumbnail-height="<?php echo $image['thumbnail'][2] ?>" 
			data-thumbnail-url="<?php echo $image['thumbnail'][0] ?>"
			data-id="<?php echo $image['id'] ?>" />
	
		<?php endforeach; 		
	}
	
	/**
	 * Loading the /scripts/script.js from the plugin directory and the jQuery templating plugin. 
	 * By default the scripts are enqueued in the page footer for faster page loading. 
	 * @since 1.0
	 * @return void
	 */
	private function init_scripts() {

		// Only enqueue scripts on frontend. 
		if(is_admin()) {
			// Only enqueue the script if it actually exists. 
			if (file_exists(WP_PLUGIN_DIR . "/{$this->plugin_name}/js/admin-script.min.js")) {
				wp_enqueue_script("jquery-templates", "http://ajax.microsoft.com/ajax/jquery.templates/beta1/jquery.tmpl.min.js", array('jquery'), "1.0b1", true);
				wp_enqueue_script($this->plugin_name . "-admin-script", WP_PLUGIN_URL . "/{$this->plugin_name}/js/admin-script.min.js", array('jquery', 'jquery-templates'), '1.0.3', true);
			}			
		}
	}
	
	/**
	 * Loading the stylesheet for this plugin. 
	 * @since 1.0
	 * @return void
	 */
	private function init_styles() {
				
		// Only enqueue styles in admin and if the stylesheet actually exists. 
		if(is_admin()) {		
			if (file_exists(WP_PLUGIN_DIR . "/{$this->plugin_name}/styles/admin-style.css")) {
				wp_enqueue_style($this->plugin_name . "-admin-style", WP_PLUGIN_URL . "/{$this->plugin_name}/styles/admin-style.css", array(), '1.0', 'all');
			}			
		}
	}

	/**
	 * This function will render the default meta box again, called from ajax
	 * @since 1.1.2
	 * @return void
	 */
	function load_meta_box() {
		$this->meta_box();
		die();
	}

	/**
	 * This function will render the meta box used for the drag-n-drop function
	 * @since 1.0
	 * @param int $post_id
	 * @return void
	 * @uses $wpdb
	 */
	function meta_box($post_id = 0) {

		/** @var $wpdb wpdb  */
		global $wpdb;
		
		$q = "SELECT p.ID, p.guid
				FROM `{$wpdb->prefix}posts` p
				WHERE `post_type` = 'attachment' 
				AND post_mime_type LIKE 'image/%'
				ORDER BY p.id DESC";
		
		$result = $wpdb->get_results($q);
		
		if (count($result) > 0) {
			
			$large_w = get_option("large_size_w");
			$large_h = get_option("large_size_h");
			$large_str = sprintf("%sx%s", $large_w, $large_h);
			
			$medium_w = get_option("medium_size_w");
			$medium_h = get_option("medium_size_h");
			$medium_str = sprintf("%sx%s", $medium_w, $medium_h);
			
			$thumb_w = get_option("thumbnail_size_w");
			$thumb_h = get_option("thumbnail_size_h");
			$thumb_str = sprintf("%sx%s", $thumb_w, $thumb_h);
			
			$i = 0;
			
			foreach ($result as $post) {
				$images[] = array(
					'full' => wp_get_attachment_image_src( $post->ID, 'full' ),
					'large' => wp_get_attachment_image_src( $post->ID, 'large' ),
					'medium' => wp_get_attachment_image_src( $post->ID, 'medium' ),
					'thumbnail' => wp_get_attachment_image_src( $post->ID, 'thumbnail' ),
					'id' => $post->ID
				);
				
				if (++$i == $this->grid_size) {
					break;
				}
			}
			
			require "pages/meta_box.php";
		}
	}
}

endif; // class exists imagedrop

// Register the plugin if the user agent isn't Opera
if ( !isset($_SERVER['HTTP_USER_AGENT']) || substr($_SERVER['HTTP_USER_AGENT'], 0, 5) != "Opera" ) {
	add_action("init", create_function('', 'new ImageDrop();'));
}