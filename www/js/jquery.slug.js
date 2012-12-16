//
//	jQuery Slug Generation Plugin by Perry Trinier (perrytrinier@gmail.com)
//  Licensed under the GPL: http://www.gnu.org/copyleft/gpl.html

jQuery.fn.slug = function(options) {
	var settings = {
		slug: 'slug', // Class used for slug destination input and span. The span is created on $(document).ready() 
		hide: false	 // Boolean - By default the slug input field is hidden, set to false to show the input field and hide the span. 
	};
	
	if(options) {
		jQuery.extend(settings, options);
	}
	
	$this = jQuery(this);

	jQuery(document).ready( function() {
		if (settings.hide) {
			jQuery('input.' + settings.slug).after("<span class="+settings.slug+"></span>");
			jQuery('input.' + settings.slug).hide();
		}
	});
	
	makeSlug = function() {
			var slugcontent = $this.val();
			var slugcontent_hyphens = slugcontent.replace(/\s/g,'-');
		//	var slugcontent_cz = slugcontent_hyphens.replace(/[ěščřžýáíé]/g,'escrzyauie');
      var slugcontent_cz = slugcontent_hyphens.replace(/[ě]/g,'e');
      slugcontent_cz = slugcontent_cz.replace(/[š]/g,'s');  
      slugcontent_cz = slugcontent_cz.replace(/[č]/g,'c'); 
      slugcontent_cz = slugcontent_cz.replace(/[ř]/g,'r');
      slugcontent_cz = slugcontent_cz.replace(/[ž]/g,'z');
      slugcontent_cz = slugcontent_cz.replace(/[ý]/g,'y');
      slugcontent_cz = slugcontent_cz.replace(/[á]/g,'a');
      slugcontent_cz = slugcontent_cz.replace(/[í]/g,'i');
      slugcontent_cz = slugcontent_cz.replace(/[é]/g,'e');
			var finishedslug = slugcontent_cz.replace(/[^a-zA-Z0-9\-]/g,'');
			jQuery('input.' + settings.slug).val(finishedslug.toLowerCase());
			jQuery('span.' + settings.slug).text(finishedslug.toLowerCase());

		}
		
	jQuery(this).keyup(makeSlug);
		
	return $this;
};
