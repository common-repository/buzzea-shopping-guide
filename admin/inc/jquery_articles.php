<?php 
/*
Description: This file contains the admin jquery functions for articles in the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

/////////////////////////////////////////////////////////////////////////////////////////
NOTE: selection of articles for widgets from below a POST/ PAGE does not use this file
		see jquery_articles_contextual.php instead
/////////////////////////////////////////////////////////////////////////////////////////


*/
	
	$article_add_success_txt = __("L\'article a été ajouté à votre comparateur", CDP_PLUGIN_NAME );
	$article_add_fail_txt = __("L\'article existe déjà dans votre comparateur", CDP_PLUGIN_NAME );
	
	// nonces used
	$articlesnonceadd 						= wp_create_nonce( 'articlesnonceadd' );
	$articles_showarticlescategory_nonce	= wp_create_nonce( 'articlesnonceshowarticlescategory' );
	$articlesnonceadd 						= wp_create_nonce( 'articlesnonceadd' );
	$articlesnoncemultipledelete 			= wp_create_nonce( 'noncemultipledelete' );
	$articlesnoncealldelete		 			= wp_create_nonce( 'noncealldelete' );
	$articlesnoncedelete 					= wp_create_nonce( 'articlesnoncedelete' );
	
	$cdppagparam = CDP_PAGINATION_PARAMETRE;
	//current page minus 'paging' parametre. used for pagination
	$cdp_target_path = esc_url(preg_replace("/&$cdppagparam=([0-9]+)$/", '', $_SERVER['REQUEST_URI']));
	
?><script type="text/javascript">

	/*********************************************
		ajax call to show articles per category
	 *********************************************
		takes: jQuery (equivalent of $)
				articleid, 
				articlesnonceadd
	*/
	
	jQuery(document).ready(function(jQuery) {
		
		/*
		 * show articles for this category id
		*/
		jQuery("a[id^='cdp_cat_arts_']").live('click', function(e) { 
				e.preventDefault(); // this stops it from firing the link and is the same as having return false; at the end of the function
				
				var $this = jQuery(this); 
				var catid 		=  "#"+$this.attr("id");
				var catid 		= catid.replace("#cdp_cat_arts_", ""); // get number of id
				
				cdp_show_cat_article_js(catid);
				
				window.current_catid = catid; // global
				
		});
		
		/*
		 * add article by id (selection of articles (not widget))
		*/
		jQuery("a[class^='cdp_cat_arts_add_']").live('click', function(e) { 
				e.preventDefault(); // this stops it from firing the link and is the same as having return false; at the end of the function
				
				var $this = jQuery(this); 
				var artid 		=  $this.attr("class");
				var artid 		= artid.replace("cdp_cat_arts_add_", ""); // get number of id
				
				// cdp_show_cat_article_js(catid);
				cdp_add_article_js(jQuery, artid, '<?php echo $articlesnonceadd; ?>', '<?php echo $article_add_success_txt; ?>', '<?php echo $article_add_fail_txt; ?>');
			
				
		});
		
		/*
		 * delete article by id
		*/
		jQuery("a[class^='cdp_cat_arts_del_']").live('click', function(e) { 
				e.preventDefault(); // this stops it from firing the link and is the same as having return false; at the end of the function
				 
				var $this = jQuery(this); 
				var artid 		=  $this.attr("class");
				var artid 		= artid.replace("cdp_cat_arts_del_", ""); // get number of id

				if (cdp_confirmAction("<?php echo __("Etes-vous sur de vouloir supprimer cet article ?", CDP_PLUGIN_NAME ); ?>")){
	 				cdp_delete_article_js(artid);
			    } else { return false; }
		});
		
		
		/*
		 * Actions groupée
		*/
		//1.9: jQuery(document).on('click', "[id=cdp_group_actions_apply]", function(e) {
		jQuery("[id=cdp_group_actions_apply]").live('click', function(e) { 
			//function groupactionCdp(){ 
			
			var selectval = jQuery('.cdpgroupaction').val();
			if (selectval == 'delete'){
				var valeurs = [];
				jQuery('input:checked[id^=delete_]').each(function() {  /* ^ name starts with */
					valeurs.push(jQuery(this).attr('rel'));
				});
				if (valeurs !='' && valeurs !=','){
					
				multipleDelArticleEnSelection(valeurs, '0');
				} else {
					alert('<?php echo __("Aucune article en selection", CDP_PLUGIN_NAME ); ?>');	
					
				}
			} 
			else if (selectval == 'delete_all'){
					 DelTousArticleEnSelection(); //del tous
			} else {
				//rien
			}
			
		//}
		});	
			
		/*
		 * select/de-select all items
		*/
		//1.9: jQuery(document).on('click', "[id*=select_all_cdc_]", function(e) {
		jQuery("[id*=select_all_cdc_]").live('click', function(e) { 

			var jQuerythis 			= jQuery(this); 
			var currentinputid 		=  jQuerythis.attr("id"); //"#"+	//widgetopener_300x250
			var currentdomain		= currentinputid.replace("select_all_cdc_",""); 
			
			// is this checked? check all others / uncheck all others
			if (jQuery('#'+currentinputid).is(':checked')) {
				
				//if NOT checked, set checks
				//console.log('set check');
				jQuery('#cdp_articles_list').find(':checkbox').not("[id*=select_all_cdc_]").prop('checked', true);
			}else{
				
				//if checked, unset checks
				//console.log('un check');
				//$('articleEnSelectionDisplayZone').find(':checkbox').prop('checked', true);
				jQuery('#cdp_articles_list').find(':checkbox').not("[id*=select_all_cdc_]").prop('checked', false);
			}
		
			return true;	
		});	
													
		/*
		 * Delete plusieurs articles en selection a la fois
		*/
		function multipleDelArticleEnSelection(articleids){
			
			if (cdp_confirmAction("<?php echo __("Êtes-vous sûr de vouloir supprimer ces articles ?", CDP_PLUGIN_NAME ); ?>")){
				
				//ajax call to delete multiple articles
				jQuery.ajax({
					type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_delete_multiple_articles', articleids:articleids,  _ajax_nonce: '<?php echo $articlesnoncemultipledelete; ?>' },
					beforeSend: function() { 
						/*jQuery('#setting-error-settings_error').remove(); jQuery('#setting-error-settings_updated').remove(); /* clear notice area */ 
						jQuery("#cdp_articles_list").fadeOut('fast'); 
						jQuery("#cdp_articles_list_loading").fadeIn('fast');
						
						}, 
					success: function(html){  // if data is retrieved, store it in html
						
						// successful 
						cdp_show_articles_js();
					}
				}); //close jQuery.ajax(

			}// if

		}// function
		
		/*
		 * Delete tous articles en selection
		*/
		function DelTousArticleEnSelection(){
			if (cdp_confirmAction("<?php echo __("Êtes-vous sûr de vouloir supprimer tous ces articles ?", CDP_PLUGIN_NAME ); ?>")){
				
				//ajax call to delete multiple articles
				jQuery.ajax({
					type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_delete_all_articles',  _ajax_nonce: '<?php echo $articlesnoncealldelete; ?>' },
					beforeSend: function() { 
						/*jQuery('#setting-error-settings_error').remove(); jQuery('#setting-error-settings_updated').remove(); /* clear notice area */ 
						jQuery("#cdp_articles_list").fadeOut('fast'); 
						jQuery("#cdp_articles_list_loading").fadeIn('fast');
						
						}, 
					success: function(html){  // if data is retrieved, store it in html
	
						// successful 
						cdp_show_articles_js();
					}
				}); //close jQuery.ajax(

			}// if

		}// function
	
			
		/*
		*  auto-request more articles at end of scroll, in the article per category area
		*/
		jQuery('#cdp_articles_per_cat').scroll(function() {
   			
			// widths and positions
			divWidth 		= jQuery('#cdp_articles_per_cat_holder').width();
			divContentWidth = jQuery("#cdp_articles_per_cat").get(0).scrollWidth; // jQuery('#cdp_articles_per_cat').width();
			scrollLeftPosn 	= jQuery('#cdp_articles_per_cat').scrollLeft();
			
			// check if far enough (200 from end)
			if( scrollLeftPosn + divWidth >  divContentWidth - 200 ) {
			   // gone past 'the point' we want to call more items
			   
			   // call more items
			   var categorieid = window.current_category_id;
			   if (window.more_articles_called == false && window.no_more_articles == false	){
					// not in middle of a call and have articles to call
				    cdp_show_cat_article_js(categorieid,more=true);
					window.more_articles_called = true; // lock until complete, avoid multiple calls
			   } else {
				    // console.log("awaiting unlock!");
			   }
			     
			} else {
					//  not there yet - do nothing
				}
	   
		});
		
	});// ready
	
	/*
	 * fill div with articles for category
	 */
	function cdp_show_cat_article_js(categorieid,more) {
		
		window.no_more_articles = false; //reset 'end of articles available'
			
		// more - true:  used to call more of the same category
		//		 false: call up the beginning of the range of articles
		
		if (more == undefined){ 
			more = false; 
			window.more_articles_called = false;
			window.articles_first_call = true;    			// this a first call or subsequent call?
			jQuery('#cdp_articles_per_cat').scrollLeft(0); 	// reset scroll to start
		} // avoid undefined
		else {
				window.articles_first_call = false;
			}

		// article limits
		var limitfrom = 0;
		var limitfor  = <?php echo CDP_ADMIN_ARTICLES_PER_CATEGORY; ?>;

		// first call needs a good number of articles to fill the div
		if (limitfor < 15 ){ limitfor = 15; }
		
		// choose how to increment limitfrom to advance the range
		// if global defined...
		if (window.current_category_limitfrom != undefined && more == true ){
			// have one from previous pass

			if (categorieid != window.current_category_id){
				// if we have changed category, reset
				limitfrom = 0;
				window.current_category_id = categorieid; 					// update to the new category
			} else {
				// increment the limitfrom to move along the range
				limitfrom = window.current_category_limitfrom + limitfor; 	// previous one + increment
				window.current_category_limitfrom = limitfrom; 				// update global for next pass
			}
			
		} else {
			// if global not defined, define one to keep track
			window.current_category_limitfrom = limitfrom; 		// define global for next pass
			window.current_category_id 		  = categorieid; 	// define global for next pass
		}

		jQuery('#cdp_articles_per_cat').show('fast');	//show resulting articles
		
		//ajax call for more articles
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_showarticles_for_category', categorieid: categorieid, limitfrom: limitfrom, limitfor: limitfor, _ajax_nonce: '<?php echo $articles_showarticlescategory_nonce; ?>' },
			beforeSend: function() { 
				jQuery('#setting-error-settings_error').remove(); jQuery('#setting-error-settings_updated').remove(); /* clear notice area */ 
				jQuery('#cdp_articles_per_cat_loader_holder').show('show');
				jQuery('#cdp_articles_per_cat_loader').show('slow'); // make visible
				
				}, 
			success: function(html){  //if data is retrieved, store it in html
				
				window.more_articles_called = false; //unlock - allowing future calls
				
				jQuery('#cdp_articles_per_cat_loader_holder').delay(500).hide('show');
				jQuery('#cdp_articles_per_cat_loader').delay(500).hide('slow'); // make invisible
				
				jQuery('#cdp_articles_per_cat').show('slow'); // make visible
				
				if (html != ''){
					//successful 
					if (more != true ) {
						// means viewing via click on category, not via autoload of articles
						jQuery('#cdp_articles_per_cat').html(''); // blank it (remove prog bar)
					}
					if (html == 'pas_articles'){
						// no more articles - end of the line
						window.no_more_articles = true; 
						html = '<?php echo __("Il n\'y a pas des articles", CDP_PLUGIN_NAME );?>';
						if (window.articles_first_call == true){
							jQuery('#cdp_articles_per_cat').html(html);	
							window.no_more_articles = false; // release
						}
					} else {
					
					// we have articles 
					prev_html 	= jQuery('#cdp_articles_per_cat').html();	 // accumulate new html
					new_html 	= prev_html + html;
					jQuery('#cdp_articles_per_cat').html(new_html);			// update html to div
					
					}
					
				} else {
					jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong>"+failtxt+"</strong></p></div>");		
				}
			}
		}); //close jQuery.ajax(
	}
	/* end function*/	
	
	
	/*********************************** 
		ajax call to add an article
	 ***********************************
		takes: jQuery (equivalent of $)
				articleid, 
				articlesnonceadd
	*/
	function cdp_add_article_js(jQuery, articleid, articlesnonceadd, successtxt, failtxt) {
		
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_addarticle', articleid: articleid, _ajax_nonce: articlesnonceadd },
			beforeSend: function() { jQuery('#setting-error-settings_error').remove(); jQuery('#setting-error-settings_updated').remove(); /* clear notice area */ }, 
			success: function(html){  //if data is retrieved, store it in html
				
				if (html == 'existe'){
					//unsuccessful addition - already selected
					jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong>"+failtxt+"</strong></p></div>");	
					
					//remove it from the articlesbox too
					jQuery("span a[class^='deletearticle_"+articleid+" remove']").parent("span").fadeOut('slow').delay(800);
					
				} else if (html == 'ajouté') {
					//successful addition
					jQuery('.nav-tab-wrapper').after("<div class=\"updated settings-error\" id=\"setting-error-settings_updated\"><p><strong>"+successtxt+"</strong></p></div>");	
					setTimeout('cdp_show_articles_js()', '800');
					
					//new - refresh the category articles div
					if (!isNaN(window.current_catid)){
						//global current id
						catid = window.current_catid;
						setTimeout('cdp_show_cat_article_js(catid)', '800');
						
					}//if	
					
				}//else if
			
			}//success function
		}); //close jQuery.ajax(
	}
	/* end function*/


	jQuery(function(){
		
		/************************/
		/* ADMIN article search	*/
		/************************/
		
		//attach autocomplete
		jQuery("#cdp_addarticlesbox").autocomplete({
			
			position: { my : "right top", at: "right bottom" },
			
			minLength: 2,
			//define callback to format results
			source: function(req, add){ //, add
				
				jQuery.getJSON('<?php echo CDP_AJAX_URL;?>?action=get_search_results_admin', req, function(data) {
					add(data);
				});
				
			},
			
			//define select handler
			select: function(e, ui) {
				//alert(ui.item.id);
				
				var chosenarticleid = ui.item.id;
				//ui.item.id has article id. 
				//add article
				
				cdp_add_article_js(jQuery, chosenarticleid, '<?php echo $articlesnonceadd; ?>','<?php echo $article_add_success_txt; ?>','<?php echo $article_add_fail_txt; ?>' ); 
				
				//create formatted chosenarticle
				var chosenarticle = ui.item.value,
					span = jQuery("<span>").text(chosenarticle),
					a = jQuery("<a>").addClass('deletearticle_'+ chosenarticleid).addClass("remove").attr({
						href: "javascript:",
						title: "Remove " + chosenarticle
						
					}).text("x").appendTo(span);
				
				//add chosenarticle to chosenarticle div
				span.insertBefore("#cdp_addarticlesbox");
				jQuery("#cdp_addarticlesboxLabel").insertBefore("#cdp_addarticlesbox");
				//jQuery("#cdp_addarticlesbox").focus();
				
				
			},
		
			//define select handler
			change: function() {
				//prevent 'addarticlesbox' field being updated and correct position
				jQuery("#cdp_addarticlesbox").val("").css("top", 2);
				
				
			},
			
			//define select handler
			close: function() {
				//prevent 'addarticlesbox' field being updated and correct position
				jQuery("#cdp_addarticlesbox").val("").css("top", 2);
				
			}
		}); /* end of autocomplete*/
		
		//add click handler to addarticles div
		jQuery("#cdp_addarticles").click(function(){
			
			//focus 'to' field
			//jQuery("#cdp_addarticlesbox").focus();
		});
		
	});
</script>
<?php 
//loading articles with ajax inspired by http://ocaoimh.ie/wp-content/uploads/2008/11/helloworld3.txt / http://ocaoimh.ie/2008/11/01/make-your-wordpress-plugin-talk-ajax/

  $articlesnonce = wp_create_nonce( 'articlesnonce' );
  
	//what page are we on?
	$cdppagparam = CDP_PAGINATION_PARAMETRE;
	if (isset($_GET[$cdppagparam])){
		if (is_numeric($_GET[$cdppagparam])){
			
			$paging = $_GET[$cdppagparam];
			
			if ($paging == 1){
				$limitstart = 0;
			} else {
				$limitstart = CDP_ITEMS_PER_LIST_ADMIN * ($paging -1);
			}
			$limitfor   = CDP_ITEMS_PER_LIST_ADMIN;
		}
	}
	else {
		//defaults
		$limitstart = 0;
		$limitfor   = CDP_ITEMS_PER_LIST_ADMIN;	
		$paging		= 1;
	}//else
	 
	$cdp_article_deleted_msg = __("L\'article a été enlevé de votre comparateur", CDP_PLUGIN_NAME );
	 
?>
<script type="text/javascript">
	// When the DOM is ready, add behavior to the link
	function cdp_confirmAction(txt){
		var agree=confirm(txt);
		if (agree)
			return true ;
		else 
			return false ;
	}

	/*********************************** 
		ajax call to show articles
	 ***********************************
		takes: nothing
	*/
	function cdp_show_articles_js(  ) {
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_getarticles', limitstart: '<?php echo $limitstart; ?>', limitfor: '<?php echo $limitfor; ?>', paging: '<?php echo $paging; ?>',  target_path: '<?php echo $cdp_target_path; ?>',  _ajax_nonce: '<?php echo $articlesnonce; ?>' },
			beforeSend: function() {jQuery("#cdp_articles_list").fadeOut('fast'); jQuery("#cdp_articles_list_loading").fadeIn('fast');}, //fadeIn loading just when link is clicked
			success: function(html){ //so, if data is retrieved, store it in html
				jQuery("#cdp_articles_list").html(html); //fadeIn the html inside helloworld div
				jQuery("#cdp_articles_list_loading").fadeOut('fast');
				jQuery("#cdp_articles_list").fadeIn("fast"); //animation
				//OFF jQuery("#cdp_addarticlesbox").focus();
			}
		}); //close jQuery.ajax(
	}
	/*getarticles corresponds to: add_action( 'wp_ajax_getarticles', 'show_articles_ajax' );       show_articles_ajax is the main function that does work*/
	
	/*********************************** 
		ajax call to delete article
	 ***********************************
		takes: articleid
	*/
	function cdp_delete_article_js( articleid ) {
		jQuery.ajax({
			type: "post",url: "<?php echo CDP_AJAX_URL; ?>",data: { action: 'cdp_deletearticle',articleid: articleid, _ajax_nonce: '<?php echo $articlesnoncedelete; ?>' },
			beforeSend: function() {  jQuery('#setting-error-settings_error').remove(); jQuery('#setting-error-settings_updated').remove();/* clear notice area */ /*jQuery("#cdp_addarticlesbox").focus();*/}, //fadeIn loading just when link is clicked
			success: function(html){ //so, if data is retrieved, store it in html
				
				jQuery('.nav-tab-wrapper').after("<div class=\"updated settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $cdp_article_deleted_msg; ?></strong></p></div>");	
				
				var idtofade = 'article'+articleid;
				//jQuery("#cdp_articles_list").html(html); //fadeIn the html inside helloworld div
				jQuery('tr[id="'+idtofade+'"]').fadeOut('slow').delay(400);
				
				jQuery('a[class="deletearticle_'+articleid+' remove"]').parent().fadeOut('slow').delay(400);
			
				setTimeout('cdp_show_articles_js()', '1200');
				/*jQuery("#cdp_articles_list_loading").fadeOut('fast');
				jQuery("#cdp_articles_list").fadeIn("fast"); //animation*/
				//OFF jQuery("#cdp_addarticlesbox").focus();
				
				//new - refresh the category articles div
				if (!isNaN(window.current_catid)){
					//global current id
					catid = window.current_catid;
					setTimeout('cdp_show_cat_article_js(catid)', '800');
				}
			}
		}); //close jQuery.ajax(
	}
	/*cdp_deletearticle corresponds to: add_action( 'wp_ajax_cdp_deletearticle', 'delete_article_ajax' );       delete_article_ajax is the main function that does work*/
	
	
	
	/*********************************************************************************************************
		Actions to perform when document loads
	 *********************************************************************************************************
		takes: jQuery (equivalent of $)
				
	*/	
	jQuery(document).ready(function(jQuery) {
		
		/* show articles - start */
			cdp_show_articles_js();
		/* show articles - end */
		
		/* delete an article - start */
			/* determine what article id to delete and call delete function*/								
			jQuery("a[class^='deletearticle_']").live('click', function(e) { 
				e.preventDefault(); // this stops it from firing the link and is the same as having return false; at the end of the function
				  
				  //id of correct article
				  var $this = jQuery(this); 
				  var divIDnum = $this.attr("class").replace("deletearticle_", ""); //get number of id
				  var divIDnum = divIDnum.replace(" remove", ""); //get number of id - the space is important!
				  //debug alert(divIDnum);
				  
				  if (cdp_confirmAction("<?php echo __("Etes-vous sur de vouloir supprimer cet article?", CDP_PLUGIN_NAME ); ?>")){
				  
				  cdp_delete_article_js(divIDnum);
				  //alert (divIDnum);
				  
				  //remove current chosenarticle
					jQuery(this).parent().remove();
					
					//correct 'addarticlesbox' field position
					if(jQuery("#addarticles span").length === 0) {
						jQuery("#addarticlesbox").css("top", 0);
					}	
				  
				  } else { return false; }
				  //cdp_show_articles_js();
			});
		/* delete an article - end */
	});
</script>
<?php 
/* end of file */
?>