<?php 
/*
Description: This file contains the admin jquery functions for widgets/banners in the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/
	
	$widgetnonceupdate = wp_create_nonce( 'widgetnonceupdate' );
	
?><script type="text/javascript">
    
	function cdp_confirmAction(txt){
		var agree=confirm(txt);
		if (agree)
			return true ;
		else 
			return false ;
	}
	   
	jQuery(document).ready(function() {
		
		//list the banners
		cdp_banners_list_ajax(jQuery, '', '', '<?php echo $this->widget_settings_key;?>');
		
		// close/open widget table
		jQuery('[id^="cdp_widgets_table_opener_"]:not(.cdp_delete_banner_btn)').live('click', function(e) { 
			
			//alert(jQuery(this).attr('id'));
			
			var rel = jQuery(this).attr('rel');
			//alert('#cdp_widgets_table_'+rel);
			//open / close selected one
			jQuery('#cdp_widgets_table_'+rel).toggle('fast');
			jQuery('#cdp_widgets_table_'+rel).addClass('cdp_open_widgets_table');
			
			//close all the other ones
			//not the openers
			//not the current selected one
			jQuery('.cdp_open_widgets_table').not('#cdp_widgets_table_'+rel).hide('slow',function() {
   				// my callback
				// trying to position the newly opened table somewhere useful
				// improve later
				var rowpos = jQuery(this).position(); //posn of opener
				//console.log(rowpos.top);
				jQuery('body,html').scrollTop(rowpos.top);	
				jQuery('#cdp_widgets_table_opener_'+rel).focus();
			});
			
			
			
		});
		
		// prevent return from sending form here
		jQuery('.cdp_delete_banner_btn').live('click', function(e) { 
			e.preventDefault();
			var bannerid = jQuery(this).attr('rel');
			
			if (cdp_confirmAction("<?php echo __("Etes-vous sur de vouloir supprimer cette bannière?", CDP_PLUGIN_NAME ); ?>")){
				
			  	jQuery('#cdp_widgets_table_'+bannerid).remove();
				jQuery('#cdp_widgets_table_opener_'+bannerid).hide('slow');
  
			 	cdp_delete_banner_js(jQuery, '', '', bannerid);
			  	//remove current chosenarticle
				
			  } else { return false; }
				  
		});
		
		// create show/hide iframe notice
		jQuery("[id*=cdp_show_iframe]").live('click', function(e) { 
			
			var $this 				= jQuery(this); 
			var currentinputid 		=  $this.attr("id"); //"#"+	//cdp_show_iframe_300x250
			var currentsize			= currentinputid.replace("cdp_show_iframe_",""); 
			
			jQuery('#cdp_iframe_data_'+currentsize).toggle('slow', function() {
				if ($this.text() == '<?php echo __('Afficher le code du widget à intégrer à mon site', CDP_PLUGIN_NAME );?>'){
					$this.text('<?php echo __('Cacher le code du widget', CDP_PLUGIN_NAME );?>');
				} else {
					$this.text('<?php echo __('Afficher le code du widget à intégrer à mon site', CDP_PLUGIN_NAME );?>');
				}
			});
			return false;
		});  

		//.not("[class*=cdp_enselection_table]")
		// check/watch for changes to widget form
		jQuery('[id^="cdp_widgets_table_"] :input')
		.live('change', function(e) { 
			
			if(jQuery('.cdp_enselection_area').find(jQuery(this)).length == 1) {
			   //do nothing
			}else { 
				var widgetsize = jQuery(this).attr('rel');
				//alert('change spotted');
				saveWidget(widgetsize); // save changes to widget
			}

		});
		
		
		// show notice to hit return
		jQuery('[id*="widget_option_terms"]')
		.live('keypress', function(e) { 
			var widgetsize = jQuery(this).attr('rel');
			jQuery('#'+widgetsize+'_term_notice').fadeIn('slow');
			jQuery('#'+widgetsize+'_term_notice').delay(3000).fadeOut('slow');
		});
		
		// prevent return from sending form here
		jQuery('[id*="widget_option_terms"]')
		.live('keydown', function(e) { 
			if(e.keyCode == 13) {
			  e.preventDefault();
			  
			  var widgetsize = jQuery(this).attr('rel');
			  saveWidget(widgetsize); // save changes to widget
			
			  return false;
			}
		});
		
		
		
		
	});
	
	/*
	Widget cat selector
	selecting/deselecting a single child element of categories
	when choosing categories for a widget for a CDP
	Also puts selected cat name into appropriate text input
	*/
	jQuery(document).ready(function() {
		//categories 
		jQuery(".cats_widget input").live('click', function(e) { 
					//set the 'only these categories' radio option to selected... 
					//jQuery("input[name=all_categories]:radio").filter('[value=only-selected]').attr('checked', true);
					//id of correct article
					
					var $this = jQuery(this); 
					var currentinputid 		=  $this.attr("id"); //"#"+	//300x250categorie909
					var catparts 	= currentinputid.split('categorie');
					var catid 			= catparts[1];
					var widgetdomaineid	= catparts[0];
					
					var catname	=  $this.nextAll().first().text(); // to get text of label //Alimentaire
					
					//alert(catid);
					
					//set values in inputs: the goal
					jQuery("#cdp_widget_settings_"+widgetdomaineid+"_widget_option_category").val(catname);
					jQuery("#cdp_widget_settings_"+widgetdomaineid+"_widget_option_category_id").val(catid);
					
					jQuery("#"+widgetdomaineid+"cats-left").find(':checkbox').attr('checked', false);  
					jQuery("#"+widgetdomaineid+"cats-right").find(':checkbox').attr('checked', false);  
					
					jQuery("#"+currentinputid).attr('checked', true);  
				});	
	});

	// save changes to widget
	function saveWidget(widgetsize){
		
		/****************************/
		/*  get chosen widget's data
		/****************************/
		
		var thisWidgetTable = '#cdp_widgets_table_'+widgetsize;
		var widgets = {};//object
		
		//{"300x250":{"widget_option_active":"TRUE","widget_option_size":"300x250","widget_option_limit":"10","widget_option_terms":"chiens","widget_option_category_id":"811"}}
		
		// size
		jQuery(thisWidgetTable+' input[name*="widget_option_size"]').each(
			function() {
				var size = jQuery(this).attr('rel');
				thisval = jQuery(this).val();
				// widgets[size] = { 'widget_option_size': thisval }
				
				widgets[size] = { 'widget_option_size': thisval }; 
				widgets[size]['widget_option_size'] = thisval 
			}
		);
		
		// active
		jQuery(thisWidgetTable+' input[name*="widget_option_active"]').each(
		
			function() {
				var size = jQuery(this).attr('rel');
				thisval = jQuery(this).val();
				//widgets[size] = { 'widget_option_active': thisval }; 
				widgets[size]['widget_option_active'] = thisval 
			}
		);
		
		
		
		// show beside
		jQuery(thisWidgetTable+' input[name*="widget_option_show_beside_cdp"]').filter(':checked').each(
			function() {
				var size = jQuery(this).attr('rel');
				thisval = jQuery(this).val();
				//widgets[size] = { 'widget_option_limit': thisval };
				widgets[size]['widget_option_show_beside_cdp']= thisval; 
				//alert(widgets[size]['widget_option_limit']);
			}
		);	
		
		// after post
		jQuery(thisWidgetTable+' input[name*="widget_option_show_after_post_content"]').filter(':checked').each(
			function() {
				var size = jQuery(this).attr('rel');
				thisval = jQuery(this).val();
				//widgets[size] = { 'widget_option_limit': thisval };
				widgets[size]['widget_option_show_after_post_content']= thisval; 
				//alert(widgets[size]['widget_option_limit']);
			}
		);	
		
		// after page
		jQuery(thisWidgetTable+' input[name*="widget_option_show_after_page_content"]').filter(':checked').each(
			function() {
				var size = jQuery(this).attr('rel');
				thisval = jQuery(this).val();
				//widgets[size] = { 'widget_option_limit': thisval };
				widgets[size]['widget_option_show_after_page_content']= thisval; 
				//alert(widgets[size]['widget_option_limit']);
			}
		);	
		
		// limit
		jQuery(thisWidgetTable+' select[name*="widget_option_limit"]').each(
			function() {
				var size = jQuery(this).attr('rel');
				thisval = jQuery(this).val();
				//widgets[size] = { 'widget_option_limit': thisval };
				widgets[size]['widget_option_limit']= thisval; 
				//alert(widgets[size]['widget_option_limit']);
			}
		);	
		
		// label
		jQuery(thisWidgetTable+' input[name*="widget_option_label"]').each(
			function() {
					
					var size = jQuery(this).attr('rel');
					thisval = jQuery(this).val();
					//widgets[size] = { 'widget_option_terms': thisval }
					widgets[size]['widget_option_label']= thisval 
					//alert(widgets[size]['widget_option_terms']);
			}
		);	
		
		// label limit
		jQuery(thisWidgetTable+' input[name*="widget_option_label_limit"]').each(
			function() {
					
					var size = jQuery(this).attr('rel');
					thisval = jQuery(this).val();
					//widgets[size] = { 'widget_option_terms': thisval }
					widgets[size]['widget_option_label_limit']= thisval 
					//alert(widgets[size]['widget_option_label_limit']);
			}
		);	
		
		// contextuel flag
		jQuery(thisWidgetTable+' input[name*="widget_contextuel"]').each(
			function() {
					
					var size = jQuery(this).attr('rel');
					thisval = jQuery(this).val();
					//widgets[size] = { 'widget_option_terms': thisval }
					widgets[size]['widget_contextuel']= thisval 
					//alert(widgets[size]['widget_contextuel']);
			}
		);	
		
		// terms
		jQuery(thisWidgetTable+' input[name*="widget_option_terms"]').each(
			function() {
					
					var size = jQuery(this).attr('rel');
					thisval = jQuery(this).val();
					//widgets[size] = { 'widget_option_terms': thisval }
					widgets[size]['widget_option_terms']= thisval 
					//alert(widgets[size]['widget_option_terms']);
			}
		);	
		
		// widgetdetails[300x250][widget_option_terms]
		jQuery(thisWidgetTable+' input[name$="widget_option_category]"]').each(
			function() {
					
					var size = jQuery(this).attr('rel');
					thisval = jQuery(this).val();
					//widgets[size] = { 'widget_option_terms': thisval }
					widgets[size]['widget_option_category']= thisval 
					//alert(widgets[size]['widget_option_terms']);
			}
		);	
		
		// widgetdetails[300x250][widget_option_category_id]
		jQuery(thisWidgetTable+' input[name*="widget_option_category_id"]').each(
			function() {
					
					var size = jQuery(this).attr('rel');
					thisval = jQuery(this).val();
					//alert(thisval);
					//widgets[size] = { 'widget_option_category_id': thisval }
					widgets[size]['widget_option_category_id']= thisval 
					//alert(widgets[size]['widget_option_category_id']);
			}
		);	
		
		// widgetdetails[300x250][widget_option_category_id]
		jQuery(thisWidgetTable+' input[name*="widget_option_color"]').each(
			function() {
					
					var size = jQuery(this).attr('rel');
					thisval = jQuery(this).val();
					//alert(thisval);
					//widgets[size] = { 'widget_option_category_id': thisval }
					widgets[size]['widget_option_color']= thisval 
					//alert(widgets[size]['widget_option_category_id']);
			}
		);	
		
		widgetdata = JSON.stringify(widgets);
		//alert(widgetdata);
		/*******************************/
		/* end get chosen widget's data
		/*******************************/
		// ajax call
		cdp_update_widget_js(jQuery, widgetsize, widgetdata,  '<?php echo $widgetnonceupdate; ?>', '<?php echo 'article_add_success_txt'; ?>', '<?php echo 'article_add_fail_txt'; ?>');
		
		
	}//func 
	
	/*
	 * for messages
	*/
	function successMsg(msg,mydiv){
		mydiv.html();
		mydiv.html(msg);
		mydiv.removeClass();
		mydiv.addClass('cdp_updated settings-error cdp_align_center');
		mydiv.fadeIn('slow').delay('1000').fadeOut('slow');
		
	}
	
	/*
	 * for processing messages
	*/
	function enCoursMsg(msg,mydiv){
		mydiv.html();
		
		mydiv.html(msg+'<img src="<?php echo CDP_IMG_URL_ADMIN.'cdp_load_circ_sml.gif';?>" />');
		
		mydiv.removeClass();
		mydiv.addClass('cdp_notice settings-error cdp_align_center');
		mydiv.fadeIn('slow').delay('3000');
	}
	
	
	/*********************************** 
		ajax call to update a widget
	 ***********************************
		takes: jQuery (equivalent of $)
				widgetid, 
				widgetnonceadd
	*/
	function cdp_update_widget_js(jQuery, widgetsize, widgetdata,  widgetnonceupdate, successtxt, failtxt) {
		
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_updatewidget', widgetsize: widgetsize, _ajax_nonce: widgetnonceupdate, widgetdata: widgetdata  },
			beforeSend: function() { 
				enCoursMsg("<?php echo __("Veuillez patienter, s'il vous plaît", CDP_PLUGIN_NAME );?>",jQuery('#cdp_iframe_status_'+widgetsize));
				jQuery('#setting-error-settings_error').remove(); jQuery('#setting-error-settings_updated').remove(); /* clear notice area */ 
				jQuery('#'+widgetsize+'_term_notice').fadeOut('slow');
				}, 
			success: function(html){  //if data is retrieved, store it in html
				
				// hackishly force iframe to reload
				var iframe = document.getElementById('cdp_iframe_widget_'+widgetsize);
				iframe.src = iframe.src;
				successMsg('<?php echo __("Bannière mise à jour", CDP_PLUGIN_NAME );?>',jQuery('#cdp_iframe_status_'+widgetsize));
				
			}//success function
		}); //close jQuery.ajax(
	}
	/* end function*/
	
	
	/*********************************** 
		ajax call to add a widget
	 ***********************************
		takes: jQuery (equivalent of $)
				widgetid, 
				widgetnonceadd
	*/
	<?php 
		$widgetnonceadd = wp_create_nonce( 'widgetnonceadd' );
	?>
	
	// click to add banner
	jQuery('#cdp_add_banner').click(function () {
		var widgettypeid = jQuery('#cdp_widget_settings').val();
		
		if (isNaN(widgettypeid) || widgettypeid=='' ){
			//stop here
			alert('<?php echo __("Sélectionnez une bannière s\'il vous plaît", CDP_PLUGIN_NAME );?>')
		}else {
			//continue	to add
			cdp_add_widget_js(jQuery, widgettypeid, '<?php echo $widgetnonceadd; ?>', 'successtxt', 'failtxt'); 
		}
	});
	
	
	// ajax add banner
	function cdp_add_widget_js(jQuery, widgettypeid, widgetnonceadd, successtxt, failtxt) {
		
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_addwidget', widgettypeid: widgettypeid, _ajax_nonce: widgetnonceadd  },
			beforeSend: function() { 
				enCoursMsg("<?php echo __("Veuillez patienter, s'il vous plaît", CDP_PLUGIN_NAME );?>",jQuery('#cdp_add_widget_status'));
				}, 
			success: function(html){  //if data is retrieved, store it in html
				successMsg('<?php echo __("Bannière mise à jour", CDP_PLUGIN_NAME );?>',jQuery('#cdp_add_widget_status'));
				
				//refresh the list of banners
				cdp_banners_list_ajax(jQuery, '', '', '<?php echo $this->widget_settings_key;?>');
				
			}//success function
		}); //close jQuery.ajax(
	}
	/* end function*/
	
	
	/*********************************** 
		ajax call to list banners
	 ***********************************
		takes: jQuery (equivalent of $)
				widgetid, 
				widgetnonceadd
	*/
	<?php 
		$widgetnoncelist = wp_create_nonce( 'widgetnoncelist' );
	?>
	function cdp_banners_list_ajax(jQuery, successtxt, failtxt, widget_settings_key) {
		
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_banners_list', _ajax_nonce: '<?php echo $widgetnoncelist;?>', widget_settings_key: widget_settings_key   },
			beforeSend: function() { 
					
					jQuery('#cdp_banners_loader_holder').show('slow');
					jQuery('#cdp_banners_loader').show('slow'); // make visible
				
				}, 
			success: function(html){  //if data is retrieved, store it in html
					jQuery('#cdp_banners_loader_holder').hide('fast');
					jQuery('#cdp_banners_loader').hide('fast'); // make visible
					jQuery('#cdp_banners_list').show('fast');
					jQuery('#cdp_banners_list').html(html);
			}//success function
		}); //close jQuery.ajax(
	}
	/* end function*/
	
	
	/*********************************** 
		ajax call to delete banners
	 ***********************************
		takes: jQuery (equivalent of $)
				widgetid, 
				widgetnonceadd
	*/
	<?php 
		$widgetnoncedeletebanner = wp_create_nonce( 'widgetnoncedeletebanner' );
	?>
	function cdp_delete_banner_js(jQuery, successtxt, failtxt, bannerid) {
		
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_banners_delete', bannerid: bannerid, _ajax_nonce: '<?php echo $widgetnoncedeletebanner;?>'  },
			beforeSend: function() { 
					//alert('tryingdelete');
					jQuery('#cdp_banners_loader_holder').show('slow');
					jQuery('#cdp_banners_loader').show('slow'); // make visible
				
				}, 
			success: function(html){  //if data is retrieved, store it in html
					jQuery('#cdp_banners_loader_holder').hide('fast');
					jQuery('#cdp_banners_loader').hide('fast'); // make visible
					jQuery('#cdp_banners_list').show('fast');
					
					//refresh
					//cdp_banners_list_ajax(jQuery, '', '', '<?php echo $this->widget_settings_key;?>');
					
					//jQuery('#cdp_banners_list').html(html);
			}//success function
		}); //close jQuery.ajax(
	}
	/* end function*/
	
	
	
	
</script>
<?php 
/* end of file */
?>