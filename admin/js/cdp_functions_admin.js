/* JS Document */

/*********************************** 
function to open / close parent/child categories 
***********************************
takes: id, current id
	   parentitem
	   childitem
	   imgsrc (for open,close)
	   imgsrcopposite (for open,close)
*/
function ouvrefermecategorie(id, parentitem, childitem, imgsrc,  imgsrcopposite, obj){
	if (jQuery('#'+parentitem+' > div[id^="'+childitem+'"]').filter(":first").css('display') == 'none'){ //similar to jQuery('#subcat910').css('display')== 'none'
		//test current state
		jQuery('#'+parentitem+' > div[id^="'+childitem+'"]').show();
		//jQuery('#puce'+id).attr("src", imgsrc); //change image to allow collapse
		//jQuery("[id*=puce"+id+"]").filter(":first").attr("src", imgsrc);
		
		var id = obj.id; //clicked item
		jQuery('#'+id).attr("src", imgsrc); //work for multiple id's
		
	}else{
		jQuery('#'+parentitem+' > div[id^="'+childitem+'"]').hide();
		//jQuery('#puce'+id).attr("src", imgsrcopposite); //change image to allow expand
		//jQuery("[id*=puce"+id+"]").filter(":first").attr("src", imgsrcopposite);
		
		var id = obj.id; //clicked item
		jQuery('#'+id).attr("src", imgsrcopposite); //work for multiple id's
		
	} 
}

/*
	show/hide of debug notice on general options tab
*/
jQuery(document).ready(function() {
	//create show/hide debug
	jQuery("#cdp_show_debug").click(function() {  
		jQuery('#cdp_debug_data').toggle('slow', function() {
			if (jQuery("#cdp_show_debug").text() == 'Show debug'){
				jQuery("#cdp_show_debug").text('Hide debug');
			} else {
				jQuery("#cdp_show_debug").text('Show debug');
			}
		});
	return false;
  });  
});

/*
	create preview link with color values
*/
jQuery(document).ready(function() {
	//create preview link with color values
	jQuery(".cdp_preview_link").click(function() {  
		var previ_link = jQuery(this).attr('rel');
		var previ_link_query = '';
		
		//get chosen theme
		var themeid = 1; //init
		//themeid = jQuery("input[name=cdp_theme_settings[theme_option_graphic]]:radio").filter(':checked').val();
		themeid = jQuery("input[name*=theme_option_graphic]:radio").filter(':checked').val();
		
		if (themeid == 1) {
			jQuery('#cdp_theme_settings_table_1 .color').each(function(index, thisElem) {
				//alert(thisElem);
				index = index + 1; //starts at 0
				//&domaine_couleur1=000000&domaine_couleur2=0000FF&domaine_couleur3=0000FF&domaine_couleur4=FFFFFF
				previ_link_query = previ_link_query + '&domaine_couleur' + index + '=' + jQuery(thisElem).val();
			});
		} else if (themeid == 2) {
			jQuery('#cdp_theme_settings_table_2 .color').each(function(index, thisElem) {
				//alert(thisElem);
				index = index + 1; //starts at 0
				//&domaine_couleur1=000000&domaine_couleur2=0000FF&domaine_couleur3=0000FF&domaine_couleur4=FFFFFF
				previ_link_query = previ_link_query + '&domaine_couleur' + index + '=' + jQuery(thisElem).val();
			});
			
		}
		
		previ_link_query = previ_link_query + '&themeid=' + themeid 
		//alert(link);
		previ_link = previ_link + previ_link_query;
		
		//open the link in a new window
		window.open(previ_link, "cdp_previ_window", "resizable=1, scrollbars=1, width=1000, height=700");
	return false;
  });  
  

});


/*
	selecting/deselecting child branches of categories
	when choosing categories for a CDP
*/
jQuery(document).ready(function() {
	//categories 
	jQuery("#cats input").live('click', function(e) { 
				//set the 'only these categories' radio option to selected... 
				jQuery("input[name=all_categories]:radio").filter('[value=only-selected]').attr('checked', true);
				//id of correct article
				var $this = jQuery(this); 
				var currentinputid 		=  "#"+$this.attr("id");
				var currentinputclass 	=  "."+$this.parent().attr("class"); //i.e. div id="cat909" class="cat-parent"
				//console.log(currentinputclass);
				if (currentinputclass == '.cat-parent'){
					//parent level
					if (jQuery(currentinputid).is(':checked')) {
						//if NOT checked, set check
						//console.log('set check');
						jQuery(currentinputid).parent().find(':checkbox').attr('checked', true);  
					}else{
						//if checked, unset check
						//console.log('un check');
						jQuery(currentinputid).parent().find(':checkbox').attr('checked', false);  
					}
				}
				else {
					//child level
					//console.log('child-level');
					//console.log('currentinputid is '+currentinputid);

					//for anything beneath top level parents
					//as above - with 2 parent levels (due to extra div)					
					if (jQuery(currentinputid).is(':checked')) {
						//from here we need each parent of this item to be checked also...
						//ensures when a sub sub sub cat is chosen solo its parents are also selected
						var currentdivid = jQuery(currentinputid).parent().parent().attr("id"); //jQuery("#subsubcat863").parent().attr("id");
						//console.log('currentdivid is '+currentdivid);
						//for each of the parent levels find the first check box and set to true
						//do not do for those in the .not
						jQuery("#"+currentdivid).parent().not('#cats, #cats-left, #cats-right').find(':checkbox:first').attr('checked', true);
						jQuery("#"+currentdivid).parent().parent().not('#cats, #cats-left, #cats-right').find(':checkbox:first').attr('checked', true);
						jQuery("#"+currentdivid).parent().parent().parent().not('#cats, #cats-left, #cats-right').find(':checkbox:first').attr('checked', true);
						jQuery(currentinputid).parent().parent().not('#cats, #cats-left, #cats-right').find(':checkbox').attr('checked', true);  
					}else{
						jQuery(currentinputid).parent().parent().find(':checkbox').attr('checked', false);  
					}
				}
					
			});	
});


/*
	theme swapper
*/
jQuery(document).ready(function() {
	
	
	// once loaded
	var themeid = jQuery("input[name*=theme_option_graphic]:radio").filter(':checked').val();

		if (themeid == 1) {
			jQuery("#cdp_theme_settings_table_1").show();
			jQuery("#cdp_theme_settings_table_2").hide();
			
			/*$("cdp_theme_settings_table_1 input").prop('disabled', false);
			$("cdp_theme_settings_table_2 input").prop('disabled', true);*/
		} else if (themeid == 2) {
			jQuery("#cdp_theme_settings_table_2").show();
			jQuery("#cdp_theme_settings_table_1").hide();
			
			/*$("cdp_theme_settings_table_1 input").prop('disabled', true);
			$("cdp_theme_settings_table_2 input").prop('disabled', false);*/
		}
	// end 
	
	jQuery("input[name*=theme_option_graphic]:checked").live('click', function(e) { 

		//which one was clicked?
		//var themeid = jQuery("input[name=cdp_theme_settings[theme_option_graphic]]:checked").val();
		var themeid = jQuery("input[name*=theme_option_graphic]:radio").filter(':checked').val();

		if (themeid == 1) {
			jQuery("#cdp_theme_settings_table_1").show();
			jQuery("#cdp_theme_settings_table_2").hide();
			
			/*$("cdp_theme_settings_table_1 input").prop('disabled', false);
			$("cdp_theme_settings_table_2 input").prop('disabled', true);*/
		} else if (themeid == 2) {
			jQuery("#cdp_theme_settings_table_2").show();
			jQuery("#cdp_theme_settings_table_1").hide();
			
			/*$("cdp_theme_settings_table_1 input").prop('disabled', true);
			$("cdp_theme_settings_table_2 input").prop('disabled', false);*/
		}
	});	
});


/* VTip - hover over help messages */

this.vtip = function () {
    this.xOffset = -10;
    this.yOffset = 10;
	
	
	
    jQuery(".vtip").unbind().hover(function (e) {
		
		/* awareness of window. show to left if too small */
		var winwidth = jQuery(window).width();
		var pos = jQuery(this).offset();
		if (winwidth < 1000){ xOffset = -400; } else { xOffset = -10;}
		/*end:awareness*/
	
        this.t = this.title;
        this.title = '';
        this.top = (e.pageY + yOffset);
        this.left = (e.pageX + xOffset);
        jQuery('body').append('<p id="vtip"><img id="vtipArrow" />' + this.t + '</p>');
        jQuery('p#vtip').css("top", this.top + "px").css("left", this.left + "px").fadeIn("slow");
    }, function () {
        this.title = this.t;
        jQuery("p#vtip").fadeOut("slow").remove();
    }).mousemove(function (e) {
        this.top = (e.pageY + yOffset);
        this.left = (e.pageX + xOffset);
        jQuery("p#vtip").css("top", this.top + "px").css("left", this.left + "px");
    });
};
jQuery(document).ready(function() {
	vtip();
});

/* end file */