<?php
/*
Plugin Name: Folder Gallery
Version: 0.95
Plugin URI: http://www.jalby.org/wordpress/
Author: Vincent Jalby
Author URI: http://www.jalby.org
Text Domain: foldergallery
Description: This plugin creates picture galleries from a folder. The gallery is automatically generated in a post or page with a shortcode. Usage: [foldergallery folder="path_to_folder" title="Gallery title"]. For each gallery, a subfolder cache_[width]x[height] is created inside the pictures folder when the page is accessed for the first time. The picture folder must be writable (chmod 777).
Tags: gallery, folder, lightbox, lightview
Requires: 3.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: foldergallery
Domain Path: /languages
*/

/*  Copyright 2013  Vincent Jalby  (wordpress /at/ jalby /dot/ org)

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

new foldergallery();

class foldergallery{

	var $lightview_path;

	function foldergallery() {		
		add_action( 'admin_menu', array( $this, 'fg_menu' ) );
		add_action( 'admin_init', array( $this, 'fg_settings_init' ) );
		add_action('plugins_loaded', array( $this, 'fg_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'fg_styles_and_scripts' ) );
		add_shortcode( 'foldergallery', array( $this, 'fg_gallery' ) );
	}

	function fg_init() {
		global $lightview_path;
		load_plugin_textdomain( 'foldergallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		$options = get_option( 'FolderGallery' );
		if ( empty( $options ) ) {
			update_option( 'FolderGallery', $this->fg_settings_default() );
		}
		$lightview_path = plugin_dir_path( __FILE__ ) . 'lightview';
		if ( ! is_dir( $lightview_path ) ) {
			$lightview_path = FALSE;
		}
	}

	function fg_styles_and_scripts(){
		global $lightview_path;
		wp_enqueue_style( 'fg-style', plugins_url( '/css/style.css', __FILE__ ) );
		if ( $lightview_path ) {
			global $is_IE;
			if ( $is_IE ) {
				wp_enqueue_script( 'excanvas', plugins_url( '/lightview/js/excanvas/excanvas.js', __FILE__  ), array( 'jquery' ) );
			}
			wp_enqueue_script( 'lightview_spinners', plugins_url( '/lightview/js/spinners/spinners.min.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'lightview', plugins_url( '/lightview/js/lightview/lightview.js', __FILE__ ) );   		
			wp_enqueue_style( 'lightview', plugins_url( '/lightview/css/lightview/lightview.css', __FILE__ ) );
		} else {
			wp_enqueue_style( 'fg-lightbox-style', plugins_url( '/css/lightbox.css', __FILE__ ) );
			wp_enqueue_script( 'fg-lightbox-script', plugins_url( '/js/lightbox.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'fg-lightbox-script', 'FGtrans', array(
				'labelImage' => __( 'Image', 'foldergallery' ),
				'labelOf'    => __( 'of', 'foldergallery' ),
				)
			);
		}
	}

	/* --------- Folder Gallery Main Functions --------- */

	function save_thumbnail( $path, $savepath, $th_width, $th_height ) { // Save thumbnail
		$image = wp_get_image_editor( $path );
		if ( ! is_wp_error( $image ) ) {
			if ( 0 == $th_height ) { // 0 height => auto
				$size = $image->get_size();
				$width = $size['width'];
				$height = $size['height'];
				$th_height = floor( $height * ( $th_width / $width ) );
			}
			$image->resize( $th_width, $th_height, true );
			$image->save( $savepath );
		}
	}

	function file_array( $directory ) { // List all JPG & PNG files in $directory
		$files = array();
		if( $handle = opendir( $directory ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
				if ( 'jpg' == $ext || 'png' == $ext ) {
					$files[] = $file;
				}
			}
			closedir( $handle );
		}
		return $files;
	}
				
	function fg_gallery( $atts ) { // Generate gallery
		global $lightview_path;
		$options = get_option( 'FolderGallery' );
		extract( shortcode_atts( array(
			'folder'  => 'wp-content/uploads/',
			'title'   => 'My Gallery',
			'width'   => $options['thumbnails_width'],
			'height'  => $options['thumbnails_height'],
			'cols'    => $options['images_per_row'],
			'margin'  => $options['margin'],
			'padding' => $options['padding'],
			'border'  => $options['border'],
			'options' => $options['lw_options'],
		), $atts ) );

		$folder = rtrim( $folder, '/' ); // Remove trailing / from path

		if ( !is_dir( $folder ) ) {
			echo '<p style="color:red;"><strong>' . __( 'Folder Gallery Error:', 'foldergallery' ) . '</strong> ';
			printf( __( 'Unable to find the directory %s.', 'foldergallery' ), $folder );
			echo '</p>';	
			return;
		}
	
		$cache_folder = $folder . '/cache_' . $width . 'x' . $height;
		if ( ! is_dir( $cache_folder ) ) {
				@mkdir( $cache_folder, 0777 );
		}
		if ( ! is_dir( $cache_folder ) ) {
			echo '<p style="color:red;"><strong>' . __( 'Folder Gallery Error:', 'foldergallery' ) . '</strong> ';
			printf( __( 'Unable to create the thumbnails directory inside %s.', 'foldergallery' ), $folder );
			_e( 'Verify that this directory is writable (chmod 777).', 'foldergallery' );
			echo '</p>';
			return;
		}
	
		$lightbox_id = md5( $folder );
	
		$pictures = $this->file_array( $folder );

		$imgstyle = "margin:0px {$margin}px {$margin}px 0px;";
		$imgstyle .= "padding:{$padding}px;";
		$imgstyle .= "border-width:{$border}px;";

		$gallery_code = '<div class="fg_gallery">';

		$NoP = count( $pictures );
		for ( $idx = 0 ; $idx < $NoP ; $idx++ ) {
			$thumbnail = $cache_folder . '/' . $pictures[ $idx ];
			if ( ! file_exists( $thumbnail ) ) {
				$this->save_thumbnail( $folder . '/' . $pictures[ $idx ], $thumbnail, $width, $height );
			}
			$gallery_code .= "\n";
			if ( $lightview_path ) {
				if ( $options ) $options = " data-lightview-group-options=\"$options\"";
				$gallery_code.= '<a title="' . $title . '" href="' . home_url( '/' ) . $folder . '/' . $pictures[ $idx ] . '" class="lightview" data-lightview-group="' . $lightbox_id . '"' . $options . '>';
				$options = ''; // group-options required only once per group.
			} else {
				$gallery_code.= '<a title="' . $title . '" href="' . home_url( '/' ) . $folder . '/' . $pictures[ $idx ] . '" rel="lightbox[' . $lightbox_id . ']">';
			}
			$gallery_code.= '<img src="' . home_url( '/' ) . $thumbnail . '" style="' . $imgstyle . '" alt="' . $title . ' [' . ( $idx + 1 ) . ']' . '" /></a>';

			if ( $cols>0 ) {
				if ( ( $idx + 1 ) % $cols == 0 ) $gallery_code .= "\n" . '<div class="fg_clear"></div>';
			}
		}
		if ( 0 == $NoP ) {
			echo '<p style="color:red;"><strong>' . __( 'Folder Gallery Error:', 'foldergallery' ) . '</strong> ';
			printf( __( 'No picture available inside %s.', 'foldergallery' ), $folder );
			echo '</p>';
		}	
		$gallery_code .= "\n</div>";
		$gallery_code .= "\n" . '<div class="fg_clear"></div>';
		return $gallery_code;
	}

	/* --------- Folder Gallery Settings --------- */

	function fg_menu() {
		add_options_page( 'Folder Gallery Settings', 'Folder Gallery', 'manage_options', 'foldergallery-settings', array( $this, 'fg_settings' ) );
	}

	function fg_settings_init() {
		register_setting( 'FolderGallery', 'FolderGallery', array( $this, 'fg_settings_validate' ) );
	}

	function fg_settings_validate( $input ) {
		$input['images_per_row']    = intval( $input['images_per_row'] );
		$input['thumbnails_width']  = intval( $input['thumbnails_width'] );
			if ( 0 == $input['thumbnails_width'] ) $input['thumbnails_width'] = 150;
		$input['thumbnails_height'] = intval( $input['thumbnails_height'] );
		$input['border']            = intval( $input['border'] );
		$input['padding']           = intval( $input['padding'] );
		$input['margin']            = intval( $input['margin'] );
		return $input;
	}

	function fg_settings_default() {
			$defaults = array(
				'border' 			=> 1,
				'padding' 			=> 2,
				'margin' 			=> 5,
				'images_per_row' 	=> 0,
				'thumbnails_width' 	=> 160,
				'thumbnails_height' => 0,
				'lw_options'        => '',
			);
			return $defaults;
	}

	function fg_option_field( $field, $label, $extra = 'px' ) {
		$options = get_option( 'FolderGallery' );
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="' . $field . '">' . $label . "</label></th>\n";
		echo '<td><input id="' . $field . '" name="FolderGallery[' . $field . ']" type="text" value="' . $options["$field"] . '" class="small-text"> ' . $extra . "</td>\n";
		echo "</tr>\n";
	}

	function fg_settings()
	{
		global $lightview_path;
		echo '<div class="wrap">' . "\n";
		screen_icon();
		echo '<h2>' . __( 'Folder Gallery Settings', 'foldergallery' ) . "</h2>\n";
		echo '<form method="post" action="options.php">' . "\n";
		settings_fields( 'FolderGallery' );
		echo "\n" . '<table class="form-table"><tbody>' . "\n";
		$this->fg_option_field( 'images_per_row', __( 'Images Per Row', 'foldergallery' ), __( '(0 = auto)', 'foldergallery' ) );
		$this->fg_option_field( 'thumbnails_width', __( 'Thumbnails Width', 'foldergallery' ) );
		$this->fg_option_field( 'thumbnails_height', __( 'Thumbnails Height', 'foldergallery' ), ' px ' . __( '(0 = auto)', 'foldergallery' ) );
		$this->fg_option_field( 'border', __( 'Picture Border', 'foldergallery' ) );
		$this->fg_option_field( 'padding', __( 'Padding', 'foldergallery' ) );
		$this->fg_option_field( 'margin', __( 'Margin', 'foldergallery' ) );
		if ( $lightview_path ) {
			$options = get_option( 'FolderGallery' );
			echo '<tr valign="top">' . "\n";
			echo '<th scope="row"><label for="lw_options">' . __( 'Lightview Options', 'foldergallery' ) . '</label></th>' . "\n";
			echo '<td><textarea id="options" rows="5" cols="50" name="FolderGallery[lw_options]" class="large-text code">' . $options['lw_options'] . "</textarea>\n";
			echo '<p class="description">' . __( 'Lightview default options, comma-separated.', 'foldergallery' );
			echo " E.g., <code>controls: { slider: false }, skin: 'mac'</code>. ";
			echo __( 'For details, see:', 'foldergallery' );
			echo ' <a href="http://projects.nickstakenburg.com/lightview/documentation/options" target="_blank">http://projects.nickstakenburg.com/lightview</a>.</p>' . "\n";
			echo "</td>\n";
			echo "</tr>\n";
		}		
		echo "</tbody></table>\n";
		submit_button();
		echo "</form></div>\n";
	}
		
} //End Of Class

?>