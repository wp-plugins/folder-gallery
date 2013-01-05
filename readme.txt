=== Folder Gallery ===
Contributors: vjalby
Tags: gallery, folder, lightbox
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 0.91
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin generates picture galleries from a folder using a shortcode.

== Description ==

This plugin creates picture galleries from a folder. 
The pictures folder must be uploaded (using FTP) somewhere on the server (e.g. /wp-content/upload). It must be writable (chmod 777).

To include a gallery in a post or a page, you have to use the following shortcode :

	[foldergallery folder="path_to_folder" title="Gallery title"]. 

For each gallery, a subfolder cache_[width]x[height] is created inside the pictures folder when the page is accessed for the first time. 

An Options page allow to set the default paramaters of the galleries :
* Number of images per row (cols)
* Thumbnails width and height (width & height)
* Picture border width (border)
* Padding and Margins (padding & margin)
 
Any of theses settings can be override using the corresponding shortcode :

	[foldergallery folder="path" title="title" cols=3 width=150 height=90 border=1 padding=2 margin=10]
 
This plugin uses Lightbox v2.51, by Lokesh Dhakar - http://www.lokeshdhakar.com 

== Installation ==

1. Unzip the archive foldergallery.zip
2. Upload the directory 'foldergallery' to the '/wp-content/plugins/' directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings / Folder Gallery to change the default settings
5. Upload a folder of pictures to '/wp-content/upload/MyPictures'
6. Insert the following short code in post or page :

	[foldergallery folder="/wp-content/upload/MyPictures" title="My Picture Gallery"]

== Changelog ==

= 0.9 =
* First released version