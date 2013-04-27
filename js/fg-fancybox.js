var $title_option;
if('null' == FancyBoxGalleryOptions.title) {
	$title_option = null;
} else {
	$title_option = {type : FancyBoxGalleryOptions.title};
}
var $autoplay;
var $speed;
if ( 0 == FancyBoxGalleryOptions.speed ) {
	$autoplay = false;
	$speed = 0;
} else {
	$autoplay = true;
	$speed = FancyBoxGalleryOptions.speed*1000;
}

jQuery(document).ready(function() {
	jQuery(".fancybox-gallery").fancybox({
    	helpers : {
			title : $title_option
    	},
    	autoPlay : $autoplay,
    	playSpeed : $speed
	});
});