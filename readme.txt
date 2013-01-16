=== Folder Gallery ===
Contributors: vjalby
Tags: gallery, folder, lightbox
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 0.95
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin generates picture galleries from a folder using a shortcode.

== Description ==

This plugin creates picture galleries from a folder. 
The pictures folder must be uploaded (using FTP) somewhere on the server (e.g. wp-content/upload). It must be writable (chmod 777).

To include a gallery in a post or a page, you have to use the following shortcode :

	`[foldergallery folder="local_path_to_folder" title="Gallery title"]`

For each gallery, a subfolder cache_[width]x[height] is created inside the pictures folder when the page is accessed for the first time. 

An Options page allow to set the default paramaters of the galleries :

* Number of images per row (columns)
* Thumbnails width and height (width & height)
* Picture border (border)
* Padding and Margin (padding & margin)
 
Any of theses settings can be override using the corresponding shortcode :

	`[foldergallery folder="path" title="title" columns=3 
			width=150 height=90 border=1 padding=2 margin=10]`
 
This plugin uses Lightbox v2.51 by Lokesh Dhakar - http://www.lokeshdhakar.com 
and Fancybox v2.1.3 by Janis Skarnelis - http://www.fancyapps.com/fancybox/

Sample, contact available at http://jalby.org/wordpress/

== Installation ==

1. Unzip the archive foldergallery.zip
2. Upload the directory 'foldergallery' to the '/wp-content/plugins/' directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings / Folder Gallery to change the default settings
5. Upload a folder of pictures to 'wp-content/upload/MyPictures'
6. Insert the following short code in post or page :

	[foldergallery folder="wp-content/upload/MyPictures" title="My Picture Gallery"]

== Frequently Asked Questions ==

= Can I use lightview 3 instead of lightbox 2? =

Yes! However, you have to download and install lightview 3 by hand. Here's how:

1. Download lightview from http://projects.nickstakenburg.com/lightview/download
2. Unzip the archive then rename the directory to 'lightview' (i.e., remove version number).
3. Upload the directory 'lightview' to '/wp-content/plugins/foldergallery'.
4. Go To Settings / Folder Gallery and select Lightview as Gallery Engine.
5. Done!


You can specify lightview options with the shortcode attribute 'options':

	`[foldergaller folder="path" title="My Gallery"
		options="controls: { slider: false }, skin: 'mac'"]`
	
You can set default options in Folder Gallery Options Page. 

See http://projects.nickstakenburg.com/lightview/documentation for details about Lightview options.

Lightview need to be reinstall everytime the plugin is updated.

= Can I use Folder Gallery along with another Lightbox plugin? =

If your Lightbox plugin automatically handles images, you may set the lightbox engine to 'None' in Folder Gallery Options.
This works with

* Fancybox 1.0.7+ by Kevin Sylvestre
* jQuery Colorbox 4.5+ by Arne Franken
* Lightview Plus 3.1.3+ by Puzich
* Maybe other (tell me !)

Otherwise, you should set a different lightbox engine (than the one used by your plugin) in Folder Gallery Options.

== Screenshots ==
1. Folder Gallery Options
2. Folder Gallery in a post
3. Folder Gallery Lightbox

== Changelog ==

= 0.97 [2013-01-??] =
* Scripts are only loaded on page with galleries
* Add support for fancybox (included)
* Add an option to change gallery 'engine' (Lightbox, Fancybox, Lightview when installed or None)
* Misc changes 


= 0.95 [2013-01-10] =
* Internationalization (English, French)
* Support for Lightview 3 (see FAQ)
* Code cleaning
* Small improvements

= 0.92 [2013-01-05] =
* Add a 0-column option (When 'Images per Row' is set to 0, the number of columns is set automatically.)
* Misc changes

= 0.90 [2013-01-05] =
* First released version