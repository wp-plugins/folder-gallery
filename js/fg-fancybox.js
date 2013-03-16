var $title_option;
if('null' == FancyBoxGalleryOptions.title) {
	$title_option = null;
} else {
	$title_option = {type : FancyBoxGalleryOptions.title};
}

jQuery(document).ready(function() {
	jQuery(".fancybox-gallery").fancybox({
    	helpers : {
			title : $title_option
    	}
	});
});