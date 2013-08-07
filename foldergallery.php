<?php
/*
Plugin Name: Folder Gallery
Version: 1.3.1b2
Plugin URI: http://www.jalby.org/wordpress/
Author: Vincent Jalby
Author URI: http://www.jalby.org
Description: This plugin creates picture galleries from a folder. The gallery is automatically generated in a post or page with a shortcode. Usage: [foldergallery folder="local_path_to_folder" title="Gallery title"]. For each gallery, a subfolder cache_[width]x[height] is created inside the pictures folder when the page is accessed for the first time. The picture folder must be writable (chmod 777).
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

	function foldergallery() {		
		add_action( 'admin_menu', array( $this, 'fg_menu' ) );	
		add_action( 'admin_init', array( $this, 'fg_settings_init' ) );
		add_action('plugins_loaded', array( $this, 'fg_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'fg_styles' ) );
		add_shortcode( 'foldergallery', array( $this, 'fg_gallery' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'fg_plugin_action_links' ) );
	}

	function fg_init() {
		load_plugin_textdomain( 'foldergallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		$fg_options = get_option( 'FolderGallery' );
		if ( empty( $fg_options ) ) {
			update_option( 'FolderGallery', $this->fg_settings_default() );
		}
		if ( ! isset( $fg_options['engine'] ) ) {
			$fg_options['engine'] = 'lightbox2';
			update_option( 'FolderGallery', $fg_options );
		}
		if ( 'lightview' == $fg_options['engine'] ) {
			if ( ! is_dir( plugin_dir_path( __FILE__ ) . 'lightview' ) ) {
				$fg_options['engine'] = 'lightbox2';
				update_option( 'FolderGallery', $fg_options );
			}
		}
		if ( ! isset( $fg_options['thumbnails'] ) ) { // 1.1 update
			$fg_options['thumbnails'] = 'all';
			update_option( 'FolderGallery', $fg_options );
		}
		if ( ! isset( $fg_options['fb_title'] ) ) { // 1.2 update
			$fg_options['fb_title'] = 'float';
			update_option( 'FolderGallery', $fg_options );
		}
		if ( ! isset( $fg_options['fb_speed'] ) ) { // 1.3 update
			$fg_options['subtitle'] = 'default';
			$fg_options['fb_speed'] = 0;
			update_option( 'FolderGallery', $fg_options );
		}
		if ( ! isset( $fg_options['show_thumbnail_subtitles'] ) ) { // 1.3.1 update
			$fg_options['show_thumbnail_subtitles'] = 0;
			update_option( 'FolderGallery', $fg_options );
		}
	}

	function fg_styles(){
		$fg_options = get_option( 'FolderGallery' );
		wp_enqueue_style( 'fg-style', plugins_url( '/css/style.css', __FILE__ ) );
		switch ( $fg_options['engine'] ) {
			case 'lightbox2' :
				wp_enqueue_style( 'fg-lightbox-style', plugins_url( '/css/lightbox.css', __FILE__ ) );
			break;
			case 'fancybox2' :
				wp_enqueue_style( 'fancybox-style', plugins_url( '/fancybox/source/jquery.fancybox.css', __FILE__ ) );
			break;
			case 'lightview' :
				wp_enqueue_style( 'lightview-style', plugins_url( '/lightview/css/lightview/lightview.css', __FILE__ ) );		
			break;
			case 'none' :
				// do nothing for now
			break;
		}
	}

	function fg_scripts(){
		static $firstcall = 1;
		$fg_options = get_option( 'FolderGallery' );	
		switch ( $fg_options['engine'] ) {
			case 'lightbox2' :
				wp_enqueue_script( 'fg-lightbox-script', plugins_url( '/js/fg-lightbox.js', __FILE__ ), array( 'jquery' ) );
				if ( $firstcall ) {
					wp_localize_script( 'fg-lightbox-script', 'FGtrans', array(
						'labelImage' => __( 'Image', 'foldergallery' ),
						'labelOf'    => __( 'of', 'foldergallery' ),
						)
					);
					$firstcall = 0;
				}
			break;
			case 'fancybox2' :
				wp_enqueue_script( 'fancybox-script', plugins_url( '/fancybox/source/jquery.fancybox.pack.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_script( 'fg-fancybox-script', plugins_url( '/js/fg-fancybox.js', __FILE__ ), array( 'jquery' ) );
				if ( $firstcall ) {
					wp_localize_script( 'fg-fancybox-script', 'FancyBoxGalleryOptions', array(
						'title' => $fg_options['fb_title'],
						'speed' => $fg_options['fb_speed'],
						)
					);
					$firstcall = 0;
				}
			break;
			case 'lightview' :
				global $is_IE;
				if ( $is_IE ) {
					wp_enqueue_script( 'excanvas', plugins_url( '/lightview/js/excanvas/excanvas.js', __FILE__  ), array( 'jquery' ) );
				}
				wp_enqueue_script( 'lightview_spinners', plugins_url( '/lightview/js/spinners/spinners.min.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_script( 'lightview-script', plugins_url( '/lightview/js/lightview/lightview.js', __FILE__ ) );   		
			break;
			case 'none' :
				// Do nothing for now
			break;
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
		sort( $files );
		return $files;
	}

	function filename_without_extension ( $filename ) {
		$info = pathinfo($filename);
		return basename($filename,'.'.$info['extension']);
	}
				
	function fg_gallery( $atts ) { // Generate gallery
		$fg_options = get_option( 'FolderGallery' );
		extract( shortcode_atts( array(
			'folder'  => 'wp-content/uploads/',
			'title'   => 'My Gallery',
			'width'   => $fg_options['thumbnails_width'],
			'height'  => $fg_options['thumbnails_height'],
			'columns' => $fg_options['columns'],
			'margin'  => $fg_options['margin'],
			'padding' => $fg_options['padding'],
			'border'  => $fg_options['border'],
			'thumbnails' => $fg_options['thumbnails'],
			'options' => $fg_options['lw_options'],
			'subtitle'=> $fg_options['subtitle'],
			'show_thumbnail_subtitles'=> $fg_options['show_thumbnail_subtitles'],
		), $atts ) );

		$folder = rtrim( $folder, '/' ); // Remove trailing / from path

		if ( !is_dir( $folder ) ) {
			return '<p style="color:red;"><strong>' . __( 'Folder Gallery Error:', 'foldergallery' ) . '</strong> ' .
				sprintf( __( 'Unable to find the directory %s.', 'foldergallery' ), $folder ) . '</p>';	
		}

		$pictures = $this->file_array( $folder );

		$NoP = count( $pictures );		
		if ( 0 == $NoP ) {
			return '<p style="color:red;"><strong>' . __( 'Folder Gallery Error:', 'foldergallery' ) . '</strong> ' .
				sprintf( __( 'No picture available inside %s.', 'foldergallery' ), $folder ) . '</p>';
		}
	
		$cache_folder = $folder . '/cache_' . $width . 'x' . $height;
		if ( ! is_dir( $cache_folder ) ) {
				@mkdir( $cache_folder, 0777 );
		}
		if ( ! is_dir( $cache_folder ) ) {
			return '<p style="color:red;"><strong>' . __( 'Folder Gallery Error:', 'foldergallery' ) . '</strong> ' .
				sprintf( __( 'Unable to create the thumbnails directory inside %s.', 'foldergallery' ), $folder ) . ' ' .
				__( 'Verify that this directory is writable (chmod 777).', 'foldergallery' ) . '</p>';
		}
			
		//$imgstyle = "margin:0px {$margin}px {$margin}px 0px;";
		$imgstyle = "width:{$width}px;";
		$imgstyle .= "margin:0;";
		$imgstyle .= "padding:{$padding}px;";
		$imgstyle .= "border-width:{$border}px;";
		
		$thmbdivstyle = "width:{$width}px;";
		$thmbdivstyle .= "margin:0px {$margin}px {$margin}px 0px;";
		

		//if ( 'all' != $thumbnails ) $columns = 0; // Moved below
			
		$this->fg_scripts();			
		$lightbox_id = uniqid(); //md5( $folder . );
		$gallery_code = '<div class="fg_gallery">';
		
		// If first picture == !!! then skip it (but use it as 'single' thumbnail).
		if ( $this->filename_without_extension( $pictures[ 0 ] ) == '!!!' ) {
			$start_idx = 1 ;		
		} else {
			$start_idx = 0 ;
		}
		// Trick to display only the first thumbnails.		
		if ( intval($thumbnails) > 1 ) { // 1 = single should not be used
			$max_thumbnails_idx = intval($thumbnails) - 1 + $start_idx;
			$thumbnails = 'all';
		} else {
			$max_thumbnails_idx = $NoP - 1 + $start_idx;
		}
		// (single) thumbnail idx set as thumbnails=-n shortcode attribute
		$thumbnail_idx = 0;
		if ( intval($thumbnails) < 0 ) {
			$thumbnail_idx = - intval($thumbnails) -1;
			$thumbnails = 'single';
		}
		
		for ( $idx = $start_idx ; $idx < $NoP ; $idx++ ) {
			// Set the thumbnail to use, depending of thumbnails option.
			if ( 'all' == $thumbnails ) {
				$thumbnail_idx = $idx;	
			}
			$thumbnail = $cache_folder . '/' . strtolower($pictures[ $thumbnail_idx ]);
			// Generate thumbnail
			if ( ! file_exists( $thumbnail ) ) {
				$this->save_thumbnail( $folder . '/' . $pictures[ $thumbnail_idx ], $thumbnail, $width, $height );
			}
			if ( ( $idx > $start_idx && 'all' != $thumbnails ) || $idx > $max_thumbnails_idx ) {
				//$linkstyle = ' style="display:none;"';
				$thmbdivstyle = "display:none;";
				$columns = 0;
			}
			// Set the Picture subtitle
			switch ( $subtitle ) {
				case 'none' :
					$thesubtitle = '';
				break;
				case 'filename' :
					$thesubtitle = $pictures[ $idx ];
				break;
				case 'filenamewithoutextension' :
					$thesubtitle = $this->filename_without_extension( $pictures[ $idx ] );
				break;
				case 'smartfilename' :
					$thesubtitle = $this->filename_without_extension( $pictures[ $idx ] );
					$thesubtitle = preg_replace ( '/^\d+/' , '' , $thesubtitle );
					$thesubtitle = str_replace( '_', ' ', $thesubtitle );
				break;
				default :
					//$thesubtitle = ( 'all' == $thumbnails || $idx > 0 ) ? $title . ' (' . ($idx+1) . '/' . $NoP . ')' : $title;
					$thesubtitle = $title ;
					if ( 'lightbox2' != $fg_options['engine'] ) $thesubtitle .= ' (' . ($idx+1-$start_idx) . '/' . ($NoP-$start_idx) . ')' ;
			}		
			// Let's start
			$gallery_code .= "\n<div class=\"fg_thumbnail\" style=\"$thmbdivstyle\">";
			// Set the link
			switch ( $fg_options['engine'] ) {
				case 'lightbox2' :
					$gallery_code.= '<a title="' . $thesubtitle . '" href="' . home_url( '/' ) . $folder . '/' . $pictures[ $idx ] . '" data-lightbox="' . $lightbox_id . '">';
				break;
				case 'fancybox2' :				
					$gallery_code.= '<a class="fancybox-gallery" title="' . $thesubtitle . '" href="' . home_url( '/' ) . $folder . '/' . $pictures[ $idx ] . '" data-fancybox-group="' . $lightbox_id . '">';
				break;
				case 'lightview' :
					if ( $options ) $options = " data-lightview-group-options=\"$options\"";
					$gallery_code .= '<a title="' . $thesubtitle . '" href="' . home_url( '/' ) . $folder . '/' . $pictures[ $idx ] . '" class="lightview" data-lightview-group="' . $lightbox_id . '"' . $options . '>';
					$options = ''; // group-options required only once per group.
				break;
				case 'none' :
					$gallery_code .= '<a title="' . $thesubtitle . '" href="' . home_url( '/' ) . $folder . '/' . $pictures[ $idx ] . '">';
				break;
			}
			// Set the thumbnail/title 	
			if ( 'none' == $thumbnails ) {
				if ( $idx == $start_idx ) {
					$gallery_code .= '<span class="fg_title_link">' . $title . '</span></a>';
				} else {
					$gallery_code .= '</a>';
				}
			} elseif ( 'single' == $thumbnails ) {
				if ( $idx == $start_idx ) {
					$gallery_code .= '<img src="' . home_url( '/' ) . $thumbnail . '" style="' . $imgstyle . '" alt="' . $title . '" /></a>';
					if ( $title != '' ) {
						$gallery_code .= '<div class="fg_title">' . $title . '</div>';
					}
				} else {
					$gallery_code .= '</a>';
				}
			} elseif ( $idx > $max_thumbnails_idx ) {
				$gallery_code .= '</a>';
			} else {
				$gallery_code .= '<img src="' . home_url( '/' ) . $thumbnail . '" style="' . $imgstyle . '" alt="' . $thesubtitle . '" /></a>';
				if ( 1 == $show_thumbnail_subtitles ) $gallery_code .= '<div class="fg_subtitle">' . $thesubtitle . '</div>';	
			}
			$gallery_code .= '</div>';

			if ( $columns > 0 ) {
				if ( ( $idx + 1 - $start_idx) % $columns == 0 ) $gallery_code .= "\n" . '<br style="clear: both" />';
			}
		}
		$gallery_code .= "\n</div>\n";
		if ( 'all' == $thumbnails ) {
			$gallery_code .= '<br style="clear: both" />';
		}
		return $gallery_code;
	}

	/* --------- Folder Gallery Settings --------- */

	function fg_menu() {
		add_options_page( 'Folder Gallery Settings', 'Folder Gallery', 'manage_options', 'folder-gallery', array( $this, 'fg_settings' ) );
	}

	function fg_settings_init() {
		register_setting( 'FolderGallery', 'FolderGallery', array( $this, 'fg_settings_validate' ) );
	}

	function fg_plugin_action_links( $links ) { 
 		// Add a link to this plugin's settings page
 		$settings_link = '<a href="' . admin_url( 'options-general.php?page=folder-gallery' ) . '">' . __('Settings') . '</a>';
 		array_unshift( $links, $settings_link ); 
 		return $links; 
	}
	
	

	function fg_settings_validate( $input ) {
		$input['columns']    = intval( $input['columns'] );
		$input['thumbnails_width']  = intval( $input['thumbnails_width'] );
			if ( 0 == $input['thumbnails_width'] ) $input['thumbnails_width'] = 150;
		$input['thumbnails_height'] = intval( $input['thumbnails_height'] );
		$input['border']            = intval( $input['border'] );
		$input['padding']           = intval( $input['padding'] );
		$input['margin']            = intval( $input['margin'] );
		if ( ! in_array( $input['thumbnails'], array( 'all','none','single' ) ) ) $input['thumbnails'] = 'all';
		if ( ! in_array( $input['fb_title'], array( 'inside','outside','float','over','null' ) ) ) $input['fb_title'] = 'all';
		if ( ! in_array( $input['subtitle'], array( 'default','none','filename','filenamewithoutextension','smartfilename' ) ) ) $input['subtitle'] = 'default';
		$input['show_thumbnail_subtitles']     = intval( $input['show_thumbnail_subtitles'] );
		$input['fb_speed']          = intval( $input['fb_speed'] );
		return $input;
	}

	function fg_settings_default() {
		$defaults = array(
			'engine'			=> 'lightbox2',
			'border' 			=> 1,
			'padding' 			=> 2,
			'margin' 			=> 5,
			'columns' 	        => 0,
			'thumbnails_width' 	=> 160,
			'thumbnails_height' => 0,
			'lw_options'        => '',
			'thumbnails'		=> 'all',
			'subtitle'			=> 'default',
			'show_thumbnail_subtitles'		=> 0,
			'fb_title'			=> 'float',
			'fb_speed'			=> 0,
		);
		return $defaults;
	}

	function fg_option_field( $field, $label, $extra = 'px' ) {
		$fg_options = get_option( 'FolderGallery' );
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="' . $field . '">' . $label . "</label></th>\n";
		echo '<td><input id="' . $field . '" name="FolderGallery[' . $field . ']" type="text" value="' . $fg_options["$field"] . '" class="small-text"> ' . $extra . "</td>\n";
		echo "</tr>\n";
	}

	function fg_settings()
	{
		$fg_options = get_option( 'FolderGallery' );
		echo '<div class="wrap">' . "\n";
		screen_icon();
		echo '<h2>' . __( 'Folder Gallery Settings', 'foldergallery' ) . "</h2>\n";
		echo '<form method="post" action="options.php">' . "\n";
		settings_fields( 'FolderGallery' );
		echo "\n" . '<table class="form-table"><tbody>' . "\n";
		
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="engine">' . __( 'Gallery Engine', 'foldergallery' ) . '</label></th>' . "\n";
		echo '<td><select name="FolderGallery[engine]" id="FolderGallery[engine]">' . "\n";
		
		echo "\t" .	'<option value="lightbox2"';
		if ( 'lightbox2' == $fg_options['engine'] ) echo ' selected="selected"';
		echo '>Lightbox 2 (default)</option>' . "\n";
		
		echo "\t" .	'<option value="fancybox2"';
		if ( 'fancybox2' == $fg_options['engine'] ) echo ' selected="selected"';
		echo '>Fancybox 2 (free for non commercial site)</option>' . "\n";

		if ( is_dir( plugin_dir_path( __FILE__ ) . 'lightview' ) ) {
			echo "\t" .	'<option value="lightview"';
			if ( 'lightview' == $fg_options['engine'] ) echo ' selected="selected"';
			echo '>Lightview 3 (free for non commercial site)</option>' . "\n";
		}
		
		echo "\t" .	'<option value="none"';
		if ( 'none' == $fg_options['engine'] ) echo ' selected="selected"';
		echo '>' . __( 'None', 'foldergallery') . '</option>' . "\n";

		echo "</select>\n";

		switch ( $fg_options['engine'] ) {
			case 'lightbox2' :
				echo '<p><a href="http://lokeshdhakar.com/projects/lightbox2/" target="_blank">Lightbox</a> is completely free to use. ';
				echo 'If you are using Lightbox on a commercial project and feeling generous, consider a <a href="http://lokeshdhakar.com/projects/lightbox2/#donate" target="_blank">donation</a>. ';
				echo 'All donations are sincerely appreciated. Thanks!</p>';
			break;
			case 'fancybox2' :
				echo '<p><a href="http://fancyapps.com/fancybox/" target="_blank">Fancybox 2</a> is licensed under <a href="http://creativecommons.org/licenses/by-nc/3.0/" target="_blank">Creative Commons Attribution-NonCommercial 3.0 license</a>. ';
				echo 'You are free to use fancyBox for your personal or non-profit website projects.<br />';
				echo 'See <a href="http://fancyapps.com/fancybox/#license" target="_blank">http://fancyapps.com/fancybox</a> for details.</p>';	
			break;
			case 'lightview' :
				echo '<p><a href="http://projects.nickstakenburg.com/lightview" target="_blank">Lightview</a> is licensed under the terms of the <a href="http://projects.nickstakenburg.com/lightview/license" target="_blank">Lightview License</a>. ';
				echo 'You are free to use it on non-commercial websites. Licenses are available for commercial use.</p>';
			break;
		}
		echo "</td>\n</tr>\n";

		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="thumbnails">' . __( 'Display Thumbnails', 'foldergallery' ) . '</label></th>' . "\n";
		echo '<td><select name="FolderGallery[thumbnails]" id="FolderGallery[thumbnails]">' . "\n";
		
		echo "\t" .	'<option value="all"';
		if ( 'all' == $fg_options['thumbnails'] ) echo ' selected="selected"';
		echo '>' . __( 'All', 'foldergallery' ) . '</option>' . "\n";
		
		echo "\t" .	'<option value="single"';
		if ( 'single' == $fg_options['thumbnails'] ) echo ' selected="selected"';
		echo '>' . __( 'Single', 'foldergallery' ) . '</option>' . "\n";

		echo "\t" .	'<option value="none"';
		if ( 'none' == $fg_options['thumbnails'] ) echo ' selected="selected"';
		echo '>' . __( 'None', 'foldergallery' ) . '</option>' . "\n";
		echo "</select>\n";
		echo "</td>\n</tr>\n";

		$this->fg_option_field( 'columns', __( 'Columns', 'foldergallery' ), __( '(0 = auto)', 'foldergallery' ) );
		$this->fg_option_field( 'thumbnails_width', __( 'Thumbnails Width', 'foldergallery' ) );
		$this->fg_option_field( 'thumbnails_height', __( 'Thumbnails Height', 'foldergallery' ), ' px ' . __( '(0 = auto)', 'foldergallery' ) );
		$this->fg_option_field( 'border', __( 'Picture Border', 'foldergallery' ) );
		$this->fg_option_field( 'padding', __( 'Padding', 'foldergallery' ) );
		$this->fg_option_field( 'margin', __( 'Margin', 'foldergallery' ) );
		// Subtitle
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="subtitle">' . __( 'Subtitle Format', 'foldergallery' ) . '</label></th>' . "\n";
		echo '<td><select name="FolderGallery[subtitle]" id="FolderGallery[subtitle]">' . "\n";		
		echo "\t" .	'<option value="default"';
		if ( 'default' == $fg_options['subtitle'] ) echo ' selected="selected"';
		echo '>'. __('Default (Title + Picture Number)', 'foldergallery') . '</option>' . "\n";
		echo "\t" .	'<option value="filename"';
		if ( 'filename' == $fg_options['subtitle'] ) echo ' selected="selected"';
		echo '>' . __('Filename', 'foldergallery') . '</option>' . "\n";
		echo "\t" .	'<option value="filenamewithoutextension"';
		if ( 'filenamewithoutextension' == $fg_options['subtitle'] ) echo ' selected="selected"';
		echo '>' . __('Filename without extension', 'foldergallery') . '</option>' . "\n";	
		echo "\t" .	'<option value="smartfilename"';
		if ( 'smartfilename' == $fg_options['subtitle'] ) echo ' selected="selected"';
		echo '>' . __('Smart Filename', 'foldergallery') . '</option>' . "\n";	
		echo "\t" .	'<option value="none"';
		if ( 'none' == $fg_options['subtitle'] ) echo ' selected="selected"';
		echo '>' . __( 'None', 'foldergallery') . '</option>' . "\n";
		echo "</select>\n";
		echo "</td>\n</tr>\n";
		// show_thumbnail_subtitles
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="show_thumbnail_subtitles">' . __( 'Show Thumbnail Subtitles', 'foldergallery' ) . '</label></th>' . "\n";
		echo '<td><select name="FolderGallery[show_thumbnail_subtitles]" id="FolderGallery[show_thumbnail_subtitles]">' . "\n";		
		echo "\t" .	'<option value="0"';
		if ( '0' == $fg_options['show_thumbnail_subtitles'] ) echo ' selected="selected"';
		echo '>'. __('No', 'foldergallery') . '</option>' . "\n";
		echo "\t" .	'<option value="1"';
		if ( '1' == $fg_options['show_thumbnail_subtitles'] ) echo ' selected="selected"';
		echo '>' . __('Yes', 'foldergallery') . '</option>' . "\n";
		echo "</select>\n";
		echo "</td>\n</tr>\n";


		// Lightview		
		if ( 'lightview' == $fg_options['engine'] ) {			
			echo '<tr valign="top">' . "\n";
			echo '<th scope="row"><label for="lw_options">' . __( 'Lightview Options', 'foldergallery' ) . '</label></th>' . "\n";
			echo '<td><textarea id="lw_options" rows="5" cols="50" name="FolderGallery[lw_options]" class="large-text code">' . $fg_options['lw_options'] . "</textarea>\n";
			echo '<p class="description">' . __( 'Lightview default options, comma-separated.', 'foldergallery' );
			echo " E.g., <code>controls: { slider: false }, skin: 'mac'</code>. ";
			echo __( 'For details, see:', 'foldergallery' );
			echo ' <a href="http://projects.nickstakenburg.com/lightview/documentation/options" target="_blank">http://projects.nickstakenburg.com/lightview</a>.</p>' . "\n";
			echo "</td>\n";
			echo "</tr>\n";
		} else {
			echo '<input type="hidden" name="FolderGallery[lw_options]" id="lw_options" value="' . $fg_options['lw_options'] . '" />';
		}		
		// Fancybox 2 options
		if ( 'fancybox2' == $fg_options['engine'] ) {
			echo '<tr valign="top">' . "\n";
			echo '<th scope="row"><label for="fb_title">' . __( 'Fancybox Subtitle Style', 'foldergallery' ) . '</label></th>' . "\n";
			echo '<td><select name="FolderGallery[fb_title]" id="FolderGallery[fb_title]">' . "\n";
		
			echo "\t" .	'<option value="inside"';
			if ( 'inside' == $fg_options['fb_title'] ) echo ' selected="selected"';
			echo '>' . __( 'Inside', 'foldergallery' ) . '</option>' . "\n";
		
			echo "\t" .	'<option value="outside"';
			if ( 'outside' == $fg_options['fb_title'] ) echo ' selected="selected"';
			echo '>' . __( 'Outside', 'foldergallery' ) . '</option>' . "\n";
			
			echo "\t" .	'<option value="over"';
			if ( 'over' == $fg_options['fb_title'] ) echo ' selected="selected"';
			echo '>' . __( 'Over', 'foldergallery' ) . '</option>' . "\n";
			
			echo "\t" .	'<option value="float"';
			if ( 'float' == $fg_options['fb_title'] ) echo ' selected="selected"';
			echo '>' . __( 'Float', 'foldergallery' ) . '</option>' . "\n";

			echo "\t" .	'<option value="null"';
			if ( 'null' == $fg_options['fb_title'] ) echo ' selected="selected"';
			echo '>' . __( 'None', 'foldergallery' ) . '</option>' . "\n";
			echo "</select>\n";
			echo "</td>\n</tr>\n";
			
			$this->fg_option_field( 'fb_speed', __( 'Autoplay Speed', 'foldergallery' ), ' seconds ' . __( '(0 = off)', 'foldergallery' ) );
			
		} else {
			echo '<input type="hidden" name="FolderGallery[fb_title]" id="fb_title" value="' . $fg_options['fb_title'] . '" />';
			echo '<input type="hidden" name="FolderGallery[fb_speed]" id="fb_speed" value="' . $fg_options['fb_speed'] . '" />';
		}
		echo "</tbody></table>\n";
		submit_button();
		echo "</form></div>\n";
	}
		
} //End Of Class

?>