<?php 
/*
Description: This file contains the Widget functions for the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

// inspiration: http://azuliadesigns.com/create-wordpress-widgets/
*/

/* 
 * function that returns widget details AVAIlABLE for this CDP site - on file at buzzea. i.e. sizes and id's
 * Available to use - but not necessarily selected yet for use
 * takes: 
 * returns: array
 */
if (!function_exists("cdp_get_widgets_available")) {
	function cdp_get_widgets_available(){
		 
		$password = md5(cdp_get_password());
		
		// do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		// request subscription details from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'settings-widget/get-available', 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//pr($post_result['body']);
		$results = $post_result['body'];
		if ($results == FALSE ){
		return FALSE;
		}//if
		else {
			$widgets = unserialize ($post_result['body']); //serial array
			if (is_array($widgets)){ return $widgets; } else { return FALSE; }
		}
			
	} /* end function */
} /* end function */

/* 
 * function that returns array of widget details for this CDP site - on file at buzzea
 * takes: 
 * returns: array
 */
if (!function_exists("cdp_get_widgets_remote")) {
	function cdp_get_widgets_remote(){
		
		$password = md5(cdp_get_password());
		
		// sepwork
		// need blog id to send over
		$blogid = get_option('cdp_blogid'); 
		$typeid = 2; //looking for widgets not guides/comparators 
		
		// do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		// request subscription details from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'settings-widget/get', 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_blogid' => $blogid,  'cdp_typeid' => $typeid ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		$results = $post_result['body'];
		if ($results == FALSE ){
			return FALSE;
		}//if
		else {
			$widgets = unserialize ($post_result['body']); //serial array
			if (is_array($widgets)){ 
				return $widgets; 
			} else { return FALSE; }
		}
			
	} /* end function */
} /* end function */

/* 
 * function that returns array of widget details for this CDP site - from WP db
 * takes: 
 * returns: array
 */
if (!function_exists("cdp_get_widgets_local")) {
	function cdp_get_widgets_local(){
		$widgets = get_option('cdp_widget_settings');
		//pr($widgets);
		if (is_array($widgets)){
			return $widgets;
		} else {
			return FALSE;
		}
	} /* end function */
} /* end function */


/* 
 * function called by Ajax to add widget
 * takes: 	
 * returns: result / false
 */
if (!function_exists("cdp_add_widget_ajax")) {
	function cdp_add_widget_ajax(){
		//pr($_POST);
		if (wp_verify_nonce( $_POST['_ajax_nonce'], 'widgetnonceadd' ) && $_POST['action'] == 'cdp_addwidget' && isset($_POST['widgettypeid'])){
		
		$blogid = get_option('cdp_blogid'); 
		$typeid = 2; //looking for widgets not guides/comparators 
		
		$data_clean = array();
		$data_clean['domaine_typeid'] 			= intval($typeid);
		$data_clean['blogid'] 					= intval($blogid);
		$data_clean['theme_graphiqueid'] 		= intval('3');
		$data_clean['widgetid'] 				= intval($_POST['widgettypeid']);
		$data_clean['methode_installationid'] 	= intval('1');
		
		// send for update
		$update_result = cdp_add_widget($data_clean);
	
		die(); //0 issue
		} else { return FALSE; }
	} /* end function */ 
}

/* 
 * function called by Ajax to update widget
 * takes: 	$_POST['articleid']
 * returns: result / false
 */
if (!function_exists("cdp_update_widget_ajax")) {
	function cdp_update_widget_ajax(){
		
		if (wp_verify_nonce( $_POST['_ajax_nonce'], 'widgetnonceupdate' ) && $_POST['action'] == 'cdp_updatewidget'){
				
		//$widgetoptions = get_option( $this->widget_settings_key ); 
		//$widgetid = intval($widgetoptions[$_POST['widgetsize']]['widgetid']);
		
		$input = json_decode(stripslashes($_POST['widgetdata']), true);
		//pr($input);
		
		// validate data ok
		$data_clean = cdp_validate_widget_options( $input );
		pr($data_clean );
		
		// send for update
		$update_result = cdp_transmit_widget_settings($data_clean);
		
		////////////////////////////////
		// update WP db with data also
		//
		$current_widgets = get_option( 'cdp_widget_settings' );
	    //vd($data_clean);
//		vd($current_widgets);
		
		//get ids of changed widgets
		$widgetids_to_update = array_keys($data_clean);
		
		//update widgets that were edited
		foreach ($widgetids_to_update as $widgetid_to_update){
		
			$i = 0;
			//overwrite existing entry in array with the new data
			foreach ($current_widgets as $current_widget){
				
				if (isset($current_widget['domaineid']) && $current_widget['domaineid'] == $widgetid_to_update ){
					//echo 'update widget with clean details';
					//vd($data_clean[$widgetid_to_update]);
					
					//$current_widgets[$i] = $data_clean[$widgetid_to_update];
					$current_widgets[$i]['widget_option_terms'] 					= $data_clean[$widgetid_to_update]['widget_option_terms'];
					$current_widgets[$i]['widget_option_active'] 					= $data_clean[$widgetid_to_update]['widget_option_active'];
					$current_widgets[$i]['widget_option_show_beside_cdp'] 			= $data_clean[$widgetid_to_update]['widget_option_show_beside_cdp'];
					$current_widgets[$i]['widget_option_show_after_post_content'] 	= $data_clean[$widgetid_to_update]['widget_option_show_after_post_content'];
					$current_widgets[$i]['widget_option_show_after_page_content'] 	= $data_clean[$widgetid_to_update]['widget_option_show_after_page_content'];
					$current_widgets[$i]['widget_option_size'] 						= $data_clean[$widgetid_to_update]['widget_option_size'];
					$current_widgets[$i]['widget_option_limit'] 					= $data_clean[$widgetid_to_update]['widget_option_limit'];
					$current_widgets[$i]['widget_option_terms'] 					= $data_clean[$widgetid_to_update]['widget_option_terms'];
					$current_widgets[$i]['widget_option_category'] 					= $data_clean[$widgetid_to_update]['widget_option_category'];
					$current_widgets[$i]['widget_option_category_id'] 				= $data_clean[$widgetid_to_update]['widget_option_category_id'];
					$current_widgets[$i]['widget_option_color'] 					= $data_clean[$widgetid_to_update]['widget_option_color'];
					
					$current_widgets[$i]['widget_option_label'] 					= $data_clean[$widgetid_to_update]['widget_option_label'];
					$current_widgets[$i]['widget_option_label_limit'] 				= $data_clean[$widgetid_to_update]['widget_option_label_limit'];
					
				}
				
				$i = $i+1;
			}// for
		}
		
		$updated_widgets = $current_widgets;
		//vd($updated_widgets);
		
		//re-insert the updated array of widgets
		update_option( 'cdp_widget_settings', $updated_widgets ); 
		
		// order important. leave here.
		// if no 'aftercontent" still set - update
		if ($update_widgets_extra_aftercontent == TRUE ){
			update_option( 'cdp_widget_settings_aftercontent', 'FALSE' ); // set to false
		} else {
			update_option( 'cdp_widget_settings_aftercontent', 'TRUE' ); //blank
		}
		
		die(); //0 issue
		} else { return FALSE; }
	} /* end function */ 
}

/* 
 * function that validates widget options before sending to buzzea for update
 * takes: input array of options
 * returns: input (sanitized)
 *			adds sucess / fail messages 
 */
if (!function_exists("cdp_validate_widget_options")) {
	function cdp_validate_widget_options( $input ) {
		//pr($input);die;
		// check the nonce to see should we continue
		if ( (wp_verify_nonce( $_POST['_wpnonce'], 'cdp_widget_settings-options' ) || wp_verify_nonce( $_POST['_ajax_nonce'], 'widgetnonceupdate' ))
			&& 
			( ($_POST['action'] == 'update') || ($_POST['action'] == 'cdp_updatewidget') ) 
			){
			//vd( $input);	
			$valid = array();
			$error = FALSE;
			$msg = '';
			
			$filter_pattern_numbers 	= "/^[0-9]{1,2}$/"; //0 - 999
			$filter_pattern_categories = "/^[0-9]*|-1$/"; //0 - 999
			$filter_pattern 			= "/^[-+\p{L}\p{N}0-9_,\"\'&@\*!\?:\.\(\) ]+$/";
		
			// get keys of array	
			$array_keys = array_keys($input);
			
			if (is_array($array_keys)){
				
				$update_widgets_extra_aftercontent = FALSE; // to update the need to look for widget_option_show_after_page_content / widget_option_show_after_post_content
				
				$i = 0; 
				
				foreach ($array_keys as $widgetkey){
					
					
					// sepwork
					// not using [486x60] array index style anymore
					// todo... clean up and remove this foreach
					// overriding for now
					//
					//$widgetkey = $i; // 
					$i = $i + 1;
					
					/***********************************/
					/* Height/Width/Domaineid/WidgetID */
					/***********************************/
					if (isset($input[$widgetkey]['widgetid']))				{  $valid[$widgetkey]['widgetid'] = intval($input[$widgetkey]['widgetid']); }
					if (isset($input[$widgetkey]['domaineid']))				{  $valid[$widgetkey]['domaineid'] = intval($input[$widgetkey]['domaineid']); }
					if (isset($input[$widgetkey]['widget_hauteur']))		{  $valid[$widgetkey]['widget_hauteur'] = intval($input[$widgetkey]['widget_hauteur']); }
					
					if (isset($input[$widgetkey]['widget_largeur']))		{  
																				if ($input[$widgetkey]['widget_largeur'] == 'auto'){ $valid[$widgetkey]['widget_largeur'] = 'auto'; }
																				else { $valid[$widgetkey]['widget_largeur'] = intval($input[$widgetkey]['widget_largeur']); }}
					
					/**********/
					/* Active */
					/**********/
					if (isset($input[$widgetkey]['widget_option_active'])){
						//The following pattern will match 
						if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_active']) >0 ){ 
							
							$valid[$widgetkey]['widget_option_active'] = sanitize_text_field($input[$widgetkey]['widget_option_active']);
							
						} else { $valid[$widgetkey]['widget_option_active'] = 'FALSE';}
						
						// Something dirty entered? Warn user.
						if( $valid[$widgetkey]['widget_option_active'] != $input[$widgetkey]['widget_option_active'] ) {
							$msg .= __("*Activité pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
							add_settings_error(
								'widget_option_active', // setting title
								'widget_option_active_error', // error ID
								"$msg", // error message
								'error' // type of message
							);	
							
							$error = TRUE;
						}//if
					} else { $valid[$widgetkey]['widget_option_active'] = 'FALSE';}
					
					/**********/
					/* contextuel */
					/**********/
					if (isset($input[$widgetkey]['widget_contextuel'])){
						//The following pattern will match 
						if (preg_match($filter_pattern, $input[$widgetkey]['widget_contextuel']) >0 ){ 
							
							$valid[$widgetkey]['widget_contextuel'] = sanitize_text_field($input[$widgetkey]['widget_contextuel']);
							
						} else { $valid[$widgetkey]['widget_contextuel'] = 'FALSE';}
						
						// Something dirty entered? Warn user.
						if( $valid[$widgetkey]['widget_contextuel'] != $input[$widgetkey]['widget_contextuel'] ) {
							$msg .= __("*Contextuel pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
							add_settings_error(
								'widget_contextuel', // setting title
								'widget_contextuel_error', // error ID
								"$msg", // error message
								'error' // type of message
							);	
							
							$error = TRUE;
						}//if
					} else { $valid[$widgetkey]['widget_contextuel'] = 'FALSE';}
					
					/**********************/
					/* Show beside CDP 	  */
					/**********************/
					if (isset($input[$widgetkey]['widget_option_show_beside_cdp'])){
						//The following pattern will match 
						if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_show_beside_cdp']) >0 ){ 
							
							$valid[$widgetkey]['widget_option_show_beside_cdp'] = sanitize_text_field($input[$widgetkey]['widget_option_show_beside_cdp']);
							
						} else { $valid[$widgetkey]['widget_option_show_beside_cdp'] = 'FALSE';}
					
						// Something dirty entered? Warn user.
						if( $valid[$widgetkey]['widget_option_show_beside_cdp'] != $input[$widgetkey]['widget_option_show_beside_cdp'] ) {
							$msg .= __("*Affichage pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
							add_settings_error(
								'widget_option_show_beside_cdp', // setting title
								'widget_option_show_beside_cdp_error', // error ID
								"$msg", // error message
								'error' // type of message
							);	
							
							$error = TRUE;
						}//if
					} else { $valid[$widgetkey]['widget_option_show_beside_cdp'] = 'FALSE';}
					
					/**********************/
					/* Show below posts   */
					/**********************/
					if (isset($input[$widgetkey]['widget_option_show_after_post_content'])){
						//The following pattern will match 
						if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_show_after_post_content']) >0 ){ 
							
							$valid[$widgetkey]['widget_option_show_after_post_content'] = sanitize_text_field($input[$widgetkey]['widget_option_show_after_post_content']);
							
							$update_widgets_extra_aftercontent = TRUE; // to update the need to look for widget_option_show_after_page_content / widget_option_show_after_post_content
							
						} else { $valid[$widgetkey]['widget_option_show_after_post_content'] = 'FALSE';}
					
						// Something dirty entered? Warn user.
						if( $valid[$widgetkey]['widget_option_show_after_post_content'] != $input[$widgetkey]['widget_option_show_after_post_content'] ) {
							$msg .= __("*Affichage pour widget apres posts invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
							add_settings_error(
								'widget_option_show_after_post_content', // setting title
								'widget_option_show_after_post_content_cdp_error', // error ID
								"$msg", // error message
								'error' // type of message
							);	
							
							$error = TRUE;
						}//if
					} else { $valid[$widgetkey]['widget_option_show_after_post_content'] = 'FALSE';}
					
					/**********************/
					/* Show below pages   */
					/**********************/
					if (isset($input[$widgetkey]['widget_option_show_after_page_content'])){
						//The following pattern will match 
						if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_show_after_page_content']) >0 ){ 
							
							$valid[$widgetkey]['widget_option_show_after_page_content'] = sanitize_text_field($input[$widgetkey]['widget_option_show_after_page_content']);
							
							$update_widgets_extra_aftercontent = TRUE; // to update the need to look for widget_option_show_after_page_content / widget_option_show_after_post_content
							
						} else { $valid[$widgetkey]['widget_option_show_after_page_content'] = 'FALSE';}
						
						// Something dirty entered? Warn user.
						if( $valid[$widgetkey]['widget_option_show_after_page_content'] != $input[$widgetkey]['widget_option_show_after_page_content'] ) {
							$msg .= __("*Affichage pour widget apres pages invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
							add_settings_error(
								'widget_option_show_after_page_content', // setting title
								'widget_option_show_after_page_content_cdp_error', // error ID
								"$msg", // error message
								'error' // type of message
							);	
							
							$error = TRUE;
						}//if
					} else { $valid[$widgetkey]['widget_option_show_after_page_content'] = 'FALSE';}
					
					/*********/
					/* Size	 */
					/*********/
					
					//The following pattern will match numbers
					if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_size']) >0 ){ 
						
						$valid[$widgetkey]['widget_option_size'] = sanitize_text_field($input[$widgetkey]['widget_option_size']);
						
					} else { $valid[$widgetkey]['widget_option_size'] = '';}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_size'] != $input[$widgetkey]['widget_option_size'] ) {
						$msg .= __("*Size pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_size', // setting title
							'widget_option_size_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
					
					/************/
					/* Limit	*/
					/************/
					//The following pattern will match numbers
					if ((preg_match($filter_pattern_numbers, $input[$widgetkey]['widget_option_limit']) >0) && $input[$widgetkey]['widget_option_limit'] < 20 ){ 
						
						$valid[$widgetkey]['widget_option_limit'] = intval($input[$widgetkey]['widget_option_limit']);
						
					} else { $valid[$widgetkey]['widget_option_limit'] = 20;}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_limit'] != $input[$widgetkey]['widget_option_limit'] ) {
						$msg .= __("*Nombre limite pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_limit', // setting title
							'widget_option_limit_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
									
					/*************/
					/* Terms	 */
					/*************/
					//The following pattern will match text
					if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_terms']) >0 ){ 
						
						$valid[$widgetkey]['widget_option_terms'] = sanitize_text_field($input[$widgetkey]['widget_option_terms']);
						
					} else { $valid[$widgetkey]['widget_option_terms'] = '';}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_terms'] != $input[$widgetkey]['widget_option_terms'] ) {
						$msg .= __("*Termes pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_terms', // setting title
							'widget_option_terms_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
					
					/*************/
					/* Label	 */
					/*************/
					//The following pattern will match text
					if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_label']) >0 ){ 
						
						$valid[$widgetkey]['widget_option_label'] = sanitize_text_field($input[$widgetkey]['widget_option_label']);
						
					} else { $valid[$widgetkey]['widget_option_label'] = '';}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_label'] != $input[$widgetkey]['widget_option_label'] ) {
						$msg .= __("*Libelle pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_label', // setting title
							'widget_option_label_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
					
					/*************/
					/* Label limit	 */
					/*************/
					//The following pattern will match text
					if (preg_match($filter_pattern_numbers, $input[$widgetkey]['widget_option_label_limit']) >0 ){ 
						
						$valid[$widgetkey]['widget_option_label_limit'] = sanitize_text_field($input[$widgetkey]['widget_option_label_limit']);
						
					} else { $valid[$widgetkey]['widget_option_label_limit'] = '';}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_label_limit'] != $input[$widgetkey]['widget_option_label_limit'] ) {
						$msg .= __("*Libelle limite pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_label_limit', // setting title
							'widget_option_label_limit_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
					
					/*************/
					/* Color	 */
					/*************/
					//The following pattern will match colors
					if (preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input[$widgetkey]['widget_option_color']) >0 ){ 
						
						$valid[$widgetkey]['widget_option_color'] = sanitize_text_field($input[$widgetkey]['widget_option_color']);
						
					} else { $valid[$widgetkey]['widget_option_color'] = '';}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_color'] != $input[$widgetkey]['widget_option_color'] ) {
						$msg .= __("*Coueur pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_color', // setting title
							'widget_option_color_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
					
					/*****************/
					/* Category Name */
					/*****************/
					//The following pattern will match text
					if (preg_match($filter_pattern, $input[$widgetkey]['widget_option_category']) >0 ){ 
						
						$valid[$widgetkey]['widget_option_category'] = $input[$widgetkey]['widget_option_category'];
						
					} else { $valid[$widgetkey]['widget_option_category'] = '';}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_category'] != $input[$widgetkey]['widget_option_category'] ) {
						
						
						/*vd($valid[$widgetkey]['widget_option_category']);
						vd($input[$widgetkey]['widget_option_category']); die();*/
						
						$msg .= __("*Catégorie pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_category', // setting title
							'widget_option_category_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
					
					/*****************/
					/* Category ID   */
					/*****************/
					//The following pattern will match text
					if (preg_match($filter_pattern_categories, $input[$widgetkey]['widget_option_category_id']) >0 ){ 
						
						$valid[$widgetkey]['widget_option_category_id'] = sanitize_text_field($input[$widgetkey]['widget_option_category_id']);
						
					} else { $valid[$widgetkey]['widget_option_category_id'] = '';}
					
					// Something dirty entered? Warn user.
					if( $valid[$widgetkey]['widget_option_category_id'] != $input[$widgetkey]['widget_option_category_id'] ) {
						//vd($valid[$widgetkey]['widget_option_category_id']);
						//vd($input[$widgetkey]['widget_option_category_id']);
						$msg .= __("*Catégorie ID pour widget invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
						add_settings_error(
							'widget_option_category_id', // setting title
							'widget_option_category_id_error', // error ID
							"$msg", // error message
							'error' // type of message
						);	
						
						$error = TRUE;
					}//if
				
				}//foreach
				
				// update local widget extra options
				if ($update_widgets_extra_aftercontent == TRUE){
					update_option( 'cdp_widget_settings_aftercontent', 'TRUE' );
					
				} else {
					update_option( 'cdp_widget_settings_aftercontent', 'FALSE' ); //blank
				}
			
			}// is_array
			else { $error = TRUE; }
			
			//pr( $input); die();
			if ($error == FALSE) {
				// send the details	
				return $valid; //return data to save in WP db
			} else {
				$msg = __("Mise à jour invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />'.$msg;
				
				$inputs = '';
				//this method here is needed as there is no way to return error data
				$admin_filename = cdp_get_admin_filename();
				$wp_redir =  admin_url().$admin_filename."?page=cdp_plugin_options&cdp_tab=cdp_widget_settings";
				wp_redirect( $wp_redir . $inputs . '&cdp_widget_settings_msg='.urlencode(($msg)) );
				exit(); 
			}
			
		} else {
					
			// problem with the nonce
			$msg = __("Action incorrecte - nous ne pouvons pas enregistrer vos options");
			add_settings_error(
				'widget_option', // setting title - not important here
				'widget_option_error', // error ID - not important here
				"$msg", // error message
				'error' // type of message
			);	
		}//else
	}/* end function */	
}/* end function */

/* 
 * function that sends widget details to buzzea for update
 * takes: details array of options
 * returns: TRUE
 *			adds sucess / fail messages 
 */
if (!function_exists("cdp_transmit_widget_settings")) { 
	function cdp_transmit_widget_settings($details_array) {
			
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		//send to buzzea
		//get password from WP options	
		//$pass =  md5( esc_attr( $this->general_settings['general_option_password'] ) );
		$password = md5(cdp_get_password());
		$details = array();
		$details = $details_array;
	
		//request installation details from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'settings-widget/save', 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_widget_details' => $details  ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		$update_details = $post_result['body'];
	
		if ($update_details == 1 ){
			//success
			$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
			add_settings_error(
				'settings_updated', // setting title - not important here
				'settings_updated', // error ID - not important here
				"$msg", // error message
				'updated' // type of message
			);
					
			return TRUE;
		}//if
		else {
				//echo __("S'il vous plaît vérifier à nouveau votre mot de passe pour le plugin. Elle doit correspondre à un mot de passe que nous avons dans nos dossiers.<br/>Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au +33 (0)1 84 19 04 89");
				// problem with the update
				$cdp_buzzea_tel = CDP_BUZZEA_TEL;
				$msg = __("Oups, il y a un problème avec la mise à jour. Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au $cdp_buzzea_tel");
				add_settings_error(
					'personal_option', // setting title - not important here
					'personal_option_error', // error ID - not important here
					"$msg", // error message
					'error' // type of message
				);
				
				return FALSE;
			}//else
			
	} /* function */
} /* function */

/* 
 * function that sends widget details to buzzea for update
 * takes: details array of options
 * returns: TRUE
 *			adds sucess / fail messages 
 */
if (!function_exists("cdp_add_widget")) { 
	function cdp_add_widget($details_array) {
			
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		//send to buzzea
		//get password from WP options	
		//$pass =  md5( esc_attr( $this->general_settings['general_option_password'] ) );
		$password = md5(cdp_get_password());
		$details = array();
		$details = $details_array;
	
		//request installation details from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'settings-widget/add', 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_widget_details' => $details  ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		$update_details = $post_result['body'];
	
		if ($update_details == 1 ){
			//success
			$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
			add_settings_error(
				'settings_updated', // setting title - not important here
				'settings_updated', // error ID - not important here
				"$msg", // error message
				'updated' // type of message
			);
					
			return TRUE;
		}//if
		else {
				//echo __("S'il vous plaît vérifier à nouveau votre mot de passe pour le plugin. Elle doit correspondre à un mot de passe que nous avons dans nos dossiers.<br/>Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au +33 (0)1 84 19 04 89");
				// problem with the update
				$cdp_buzzea_tel = CDP_BUZZEA_TEL;
				$msg = __("Oups, il y a un problème avec la mise à jour. Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au $cdp_buzzea_tel");
				add_settings_error(
					'personal_option', // setting title - not important here
					'personal_option_error', // error ID - not important here
					"$msg", // error message
					'error' // type of message
				);
				
				return FALSE;
			}//else
			
	} /* function */
} /* function */

/* 
 * function that sends widget details to buzzea for update
 * takes: details array of options
 * returns: TRUE
 *			adds sucess / fail messages 
 */
if (!function_exists("cdp_delete_widget")) {
	function cdp_delete_widget($widgetid) {
			
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		//send to buzzea
		//get password from WP options	
		//$pass =  md5( esc_attr( $this->general_settings['general_option_password'] ) );
		$password = md5(cdp_get_password());
		$details = array();
		$details = $details_array;
	
		//request installation details from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'settings-widget/delete', 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_widgetid' => $widgetid  ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		$update_details = $post_result['body'];
	
		//vd($update_details);
		//$update_details
		if ($update_details == 1 ){
			
			// finally delete from local
			
			$delete = cdp_delete_widget_local($widgetid);

			
			if ($delete == TRUE){
			
				//success
				$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
				add_settings_error(
					'settings_updated', // setting title - not important here
					'settings_updated', // error ID - not important here
					"$msg", // error message
					'updated' // type of message
				);
				
				return TRUE;		
			} else {
				return FALSE;	
			}
					
	
		}//if
		else {
				//echo __("S'il vous plaît vérifier à nouveau votre mot de passe pour le plugin. Elle doit correspondre à un mot de passe que nous avons dans nos dossiers.<br/>Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au +33 (0)1 84 19 04 89");
				// problem with the update
				$cdp_buzzea_tel = CDP_BUZZEA_TEL;
				$msg = __("Oups, il y a un problème avec la mise à jour. Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au $cdp_buzzea_tel");
				add_settings_error(
					'personal_option', // setting title - not important here
					'personal_option_error', // error ID - not important here
					"$msg", // error message
					'error' // type of message
				);
				
				return FALSE;
			}//else
			
	} /* function */
} /* function */
/* 
 * function to find and delete a widget from local db
 * takes: id
 * returns: true/false
 */
if (!function_exists("cdp_delete_widget_local")) {
	function cdp_delete_widget_local($domaineid){
		
		if (!is_numeric($domaineid)){ return FALSE; }
		
		// get local widgets
		$current_widgets = get_option( 'cdp_widget_settings' );
		$updated_widgets = array();
		
		if (is_array($current_widgets)){
			
			// check through current options... 
			// if no 'aftercontent" still set - update
			foreach ($current_widgets as $current_widget){
				
				if (isset($current_widget['domaineid']) && $current_widget['domaineid'] == $domaineid ){
					// skip	
					// leave this widget out of the updated array
				} else {
					$updated_widgets[] = $current_widget;
				}
			}//for
			
			//vd($updated_widgets);
			
			// local wordpress update of databse with these new details
			// can't use update_option() as being called by ajax
			// so gutted the update_option function and using the below
			// tested ok
			
			global $wpdb;
			$newvalue = maybe_serialize( $updated_widgets );
			$result = $wpdb->update( $wpdb->options, array( 'option_value' => $newvalue ), array( 'option_name' => 'cdp_widget_settings' ) );
			

		}
		else { return FALSE; }
		
	}//func
}//func

/* 
 * function that outputs the carousel to the blog - the advert / banner itself
 * takes: args, params
 * returns: iframe
 */
if (!function_exists("cdpCarousel")) {
	function cdpCarousel($widget_args, $widget_params) 
	{
		global $cdp_found; //to know if we are on a page that also is showing a full comparateur
		$continue = TRUE;
		
		//echo 'show caro';
		
		//pr($widget_params[params]);
		if (isset($widget_params['params']['widget_option_active']) && ($widget_params['params']['widget_option_active'] != 'FALSE')){
		
			//are we on a page with a CDP?
			if ($cdp_found){
				if (isset($widget_params['params']['widget_option_show_beside_cdp']) && 
						($widget_params['params']['widget_option_show_beside_cdp'] == FALSE || $widget_params['params']['widget_option_show_beside_cdp'] == 'FALSE') ){
					//don't want to show widget when CDP present
					$continue = FALSE;
				}//if isset 
			
			}//if cdp found
			
			if ($continue){
				//if have data
				if ( 	
						isset($widget_params['params']['widget_largeur']) && 
						isset($widget_params['params']['widget_hauteur']) && 
						isset($widget_params['params']['domaineid']) && 
						isset($widget_params['params']['widgetid']) 
					){
				  
					$width		= esc_attr($widget_params['params']['widget_largeur']);
					$height 	= esc_attr($widget_params['params']['widget_hauteur']);
					$domaineid	= esc_attr($widget_params['params']['domaineid']);
					$widgetid	= esc_attr($widget_params['params']['widgetid']);
					
					if ($width == 'auto'){ $width = '100%'; } else { $width = intval($width); }
					
					//http://cdp91.moteurdeshopping.com/widget1
					echo "<iframe src=\"".CDP_URL_EXTRA."$domaineid".CDP_WIDGET_URL."$widgetid\" width=\"$width\" height=\"$height\" frameborder=\"0\" name=\"cdp_iframe_widget_{$width}x{$height}\" scrolling=\"no\"></iframe>";
				
				} else {
					echo '<!--cdp widget fail-->';
				}
			}//if continue
		}//if active
	}/* end function */
}/* end function */
 
 
/* 
 * function acts as callback for add_action 
 	// add_action("plugins_loaded", "cdpCarousel_init");
 * Once the plugin is loaded we register the banners into their widget areas for use
 *
 */
if (!function_exists("cdpCarousel_init")) {
	function cdpCarousel_init()
	{
		//OLD: $widgets = cdp_get_widgets_remote(); //call the API to get an array of widgets on file at Buzzea
		//echo 'here i am';
		if (is_admin()){
			$widgets_remote = cdp_get_widgets_remote(); // could lock this down to just admin... reduce weight
		}
		//pr($widgets_remote);
		//die;

		//important!
		//check widgets on file
		// get local widgets
		$current_widgets = get_option( 'cdp_widget_settings' );
		
		if (!is_array($current_widgets)){
			
			if (!isset($widgets_remote)){
				//if don't have from before...
				$widgets_remote = cdp_get_widgets_remote(); // could lock this down to just admin... reduce weight
			}	
			//
			//echo 'updating from fresh';
			//vd($widgets_remote );
			// local wordpress update of databse with these new details
			update_option( 'cdp_widget_settings', $widgets_remote );	
		} else {
			
			// check the remote widgets
			// skip any we have already on file

			$array_of_local_widgets_ids  = array();
			$updated_widgets = array();		
			
			//gather all current ids
			foreach ($current_widgets as $current_widget){
				$array_of_local_widgets_ids[] = $current_widget['domaineid'];
				$updated_widgets[] = $current_widget; 
			}
			
			
			//if already have
			//unset remmote widget from array
			if (isset($widgets_remote) && is_array($widgets_remote)){
				foreach ($widgets_remote as $key=>$widget_remote){
					if (in_array($widget_remote['domaineid'], $array_of_local_widgets_ids)){
						//have already
						//unset ($widgets_remote[$key]);//get rid of it 
						// skip
					} else {
						$updated_widgets[] = $widget_remote; //let this one in
					}
				}
			}
			
			// now have merged arrays
			// use to update
			if (count($updated_widgets) > 0){
				
				//echo 'updating';
				//pr($updated_widgets);
				update_option( 'cdp_widget_settings', $updated_widgets );
			}
			//update_option( 'cdp_widget_settings', $widgets_remote );
		}
		
		
		/////////////////////////////////////////////////////
		//	To fill the widget areas with available widgets
		/////////////////////////////////////////////////////
		$widgets = cdp_get_widgets_local();
		//pr($widgets);
		if (is_array($widgets)){
		
			$myvar_name='';//init
			 
			foreach ($widgets as $widget_arr){
				//vd($widget_arr);
				//if have all details
				if (
					isset($widget_arr['widget_largeur']) &&
					isset($widget_arr['widget_hauteur']) &&
					isset($widget_arr['domaineid']) &&
					isset($widget_arr['widgetid']) /*&&
					( isset($widget_arr['widget_option_active']) && $widget_arr['widget_option_active'] == TRUE )*/
				){
					
					//vd($widget_arr['widget_largeur']);
					
					$widget_size = $widget_arr['widget_largeur'] . 'x' . $widget_arr['widget_hauteur'];
					$widgets_size_display = str_replace('x', ' x ',$widget_size); //add a space for display
					
					//$$myvar_name receives the name of the function created on the fly below
					//names will be lambda_1, lambda_2, etc. (dictated by php)
					
					//echo 'cdpcaro call';
					//this function calls cdpCarousel
					$$myvar_name = create_function('$widget_args, $widget_params', 'cdpCarousel($widget_args, $widget_params);'); // create_function(args, string of the actual function);
					//this function then acts as a callback in the below...
					
					$bannier_de_shopping_txt = __('Buzzea Bannière ', CDP_PLUGIN_NAME );
					$bannier_de_shopping_desc_txt = __('Bannière de Shopping avec vos articles', CDP_PLUGIN_NAME );
					$bannier_de_shopping_desc_txt .='
					'; // line return
					
					$bannier_de_shopping_txt = $bannier_de_shopping_txt.$widget_size;
					
					$bannier_de_shopping_desc_txt = $bannier_de_shopping_desc_txt.$widget_size;
					//add category and search terms to name of banner
					if (isset($widget_arr['widget_option_category']) && $widget_arr['widget_option_category']!=''){
						$cat = ' - ' . esc_attr( $widget_arr['widget_option_category'] ); 
						$bannier_de_shopping_txt.= $cat;
						$bannier_de_shopping_desc_txt.= $cat;
					} else { $cat='';}
					  
					if (isset($widget_arr['widget_option_terms']) && $widget_arr['widget_option_terms']!=''){
						$terms = ' - ' . esc_attr( $widget_arr['widget_option_terms'] );
						$bannier_de_shopping_txt.=$terms;
						$bannier_de_shopping_desc_txt.= $terms;
						
					} else { $terms='';}
						
					$unique_widget_identifier = 'cdp_widget_'.$widget_size.$cat.$terms;    			// unique widget id
					$unique_widget_identifier = str_replace(' ', '_', $unique_widget_identifier);   // remove spaces
					$unique_widget_identifier = str_replace('-', '_', $unique_widget_identifier);   // replace dashes
					
					//echo "register $widget_size<br />";
					wp_register_sidebar_widget(
						$unique_widget_identifier,
						$bannier_de_shopping_txt,      			// widget name
						$$myvar_name,  							// callback function - CDP_300x250(); CDP_160x400(); etc. (actually lamda_1, lamda_2, etc)	 
						array(                  // options
							'description' => $bannier_de_shopping_desc_txt
						),
						array(                  // params
							'params' => $widget_arr //the details of the widget from Buzzea
						)
					);
					
				}//if issets
				
			}//for
		}//if
	 
	}/* end function */
}/* end function */

/* 
 * function to list active banners (called by ajax)
 *
 */
if (!function_exists("cdp_banners_delete_ajax")) {
	function cdp_banners_delete_ajax(){
		
		if (wp_verify_nonce( $_POST['_ajax_nonce'], 'widgetnoncedeletebanner' ) && $_POST['action'] == 'cdp_banners_delete'){
			
			$widget_settings_key	= $_POST['widget_settings_key'];			
			$widget_settings		= (array) get_option( $widget_settings_key );
			$widgetdomaineid		= intval ($_POST['bannerid']);
			
			//vd($widgetdomaineid);
			
			
			cdp_delete_widget($widgetdomaineid);
			
			die(); //0 issue
		}
		
	}//function
}//function

/* 
 * function to list active banners (called by ajax)
 *
 */
if (!function_exists("cdp_banners_list_ajax")) {
	function cdp_banners_list_ajax(){
		
		if (wp_verify_nonce( $_POST['_ajax_nonce'], 'widgetnoncelist' ) && $_POST['action'] == 'cdp_banners_list'){
			
			$widget_settings_key	= $_POST['widget_settings_key'];			
			$widget_settings		= (array) get_option( $widget_settings_key );

			cdp_banners_list($widget_settings, $widget_settings_key);
			
			die(); //0 issue
		}
		
	}//function
}//function

/* 
 * function to list active banners
 *
 */
if (!function_exists("cdp_banners_list")) {
	function cdp_banners_list($widget_settings, $widget_settings_key){
	
		
		//sepwork
		$widgets_available = get_option( 'cdp_widget_settings' );
		//pr($widgets_available);
		if (is_array($widgets_available)){
			?>
          	
            <table id="cdp_widgets_table" border=0 cellpadding=0 cellspacing=0 style="width:100%; table-layout:fixed;">
				<tr>
                	<td style="padding:0px; margin:0px;"><?php
						$i = 0; 
						foreach ($widgets_available as $widgets_choice){
							
							//vd($widgets_choice);
							//vd($widgets_choice['widget_domaine_libelle_limit']);
							
							$widgets_size = $widgets_choice['widget_largeur'].'x'.$widgets_choice['widget_hauteur'];
							
							$widgets_size_as_prefix = $widgets_size;
							$widgets_size_display = $widgets_size;
							$widgets_size_display = str_replace('x', ' x ',$widgets_size_display); //add a space for display
							$widgets_size = $widgets_choice['domaineid']; //new
							
							
							$active_check = $widgets_choice['widget_option_active'];
							if ($active_check != 'FALSE'){ $checked = ' checked="checked" '; } else { $checked = ''; } 
							
							$show_check = $widgets_choice['widget_option_show_beside_cdp'];
							if ($show_check != 'FALSE' && !is_null($show_check)){ $show_checked = ' checked="checked" '; } else { $show_checked = ''; } 
							
							$show_after_page_check = $widgets_choice['widget_option_show_after_page_content'];
							
							if ($show_after_page_check != 'FALSE' && !is_null($show_after_page_check)){ $show_after_page_check = ' checked="checked" '; } else { $show_after_page_check = ''; } 
							
							$show_after_post_check = $widgets_choice['widget_option_show_after_post_content'];
							if ($show_after_post_check != 'FALSE' && !is_null($show_after_post_check)){ $show_after_post_check = ' checked="checked" '; } else { $show_after_post_check = ''; } 
							
							//pr($widget_settings[$widgets_size]);
							/*if (isset($install_details['domaineid'])){
								$domaineid = intval($install_details['domaineid']);
							}*/
							$widget_domaineid = intval($widgets_choice['domaineid']);
							
							if (isset($widgets_choice['widgetid'])){
								$widgetid = intval($widgets_choice['widgetid']);
							}
							
							?>
                           
                             <?php 
								// choose height for widget td
								if (isset($widgets_choice['widget_hauteur'])){
									$sample_td_height 		= $widgets_choice['widget_hauteur'] + 100;
									$sample_td_height_html 	= " height='$sample_td_height' "; 
								}else{
									$sample_td_height_html 	= '';
								}
							?>
                            <?php 
							/*******************************/
							/* Output sample view of widget
							/*******************************/
							
							//http://cdp91.moteurdeshopping.com/widget1
							ob_start();
							
								//width
								if ($widgets_choice['widget_largeur'] == 'auto'){
									$widget_largeur = '100%';
									?>
									<div class="cdp_notice settings-error cdp_align_center"><strong><?php echo __("Remarque", CDP_PLUGIN_NAME); ?></strong><?php echo " : "; echo __("Cette bannière prend automatiquement la largeur de la page / l'endroit où il est placé ", CDP_PLUGIN_NAME);?></div><br />

									<?php
								} else {
									$widget_largeur = $widgets_choice['widget_largeur'];
								}
							?>
                                <iframe src="<?php echo CDP_URL_EXTRA.$widget_domaineid.CDP_WIDGET_URL.$widgetid; ?>" width="<?php echo $widget_largeur; ?>" height="<?php echo $widgets_choice['widget_hauteur']; ?>" frameborder="0" name="cdp_iframe_widget_<?php echo $widget_domaineid; /*widgets_size_as_prefix*/?>" id="cdp_iframe_widget_<?php echo $widget_domaineid; /*$widgets_size_as_prefix*/?>" scrolling="no"></iframe>
                                <br /><a href="#" id="cdp_show_iframe_<?php echo $widget_domaineid?>"><?php echo __('Afficher le code du widget à intégrer à mon site', CDP_PLUGIN_NAME );?></a>
                                <div id="cdp_iframe_data_<?php echo $widget_domaineid?>" style="display:none; width:400px">
                                    <textarea readonly="readonly" cols="60" rows="4">&lt;iframe width="<?php echo esc_attr($widget_largeur); ?>" height="<?php echo esc_attr($widgets_choice['widget_hauteur']); ?>" scrolling="no" frameborder="0" name="cdp_iframe_widget_<?php echo $widget_domaineid?>" src="<?php echo CDP_URL_EXTRA.$widget_domaineid.CDP_WIDGET_URL.$widgetid; ?>"&gt;&lt;/iframe&gt;</textarea>
                                    <?php echo __('<br />Si votre thème de Wordpress n\'a pas d\'endroits pour \'Widgets\', pour installer ce widget sur votre site, il vous faut copier le code ci-dessus et le coller à l\'endroit désiré dans le code source de votre site.<br>Nous vous conseillons de faire en sorte que ce widget soit bien visible et sur toutes les pages de votre site afin d\'optimiser le nombre de clics sur les articles. ', CDP_PLUGIN_NAME );?>
                                </div>
                            <?php
								$html_iframe_sample = ob_get_contents();
							ob_clean();		
						
							ob_end_flush();
							//sepwork 
							// rel="<?php echo $widget_size;
							// changed to 
							// rel="<?php echo $widgetid;
							
							?>       							
							<table id="cdp_widgets_table_opener_<?php echo $widget_domaineid;?>" rel="<?php echo $widget_domaineid;?>" border=0 cellpadding=0 cellspacing=0 width="100%" style="cursor:pointer;">
                            <tr>
                                	<td colspan="3" class="cdp_nopad" style="background: #cccccc; padding: 0 0 0 5px; vertical-align:middle">
                                    <img src='<?php echo CDP_IMG_URL_ADMIN.'cdp_settings_icon.gif';?>' class="cdp_banner_settings_icon" />
                                    	<div class="cdp_banner_settings_title"><?php echo __("Taille", CDP_PLUGIN_NAME ); ?> <strong><?php echo $widgets_size_display; ?></strong>
                                       
                                        <?php 
											//output category and search terms
											  if (isset($widgets_choice['widget_option_category']) && $widgets_choice['widget_option_category']!=''){
												echo ' - ' . esc_attr( $widgets_choice['widget_option_category'] ); 
											  }
											  
											  if (isset($widgets_choice['widget_option_terms']) && $widgets_choice['widget_option_terms']!=''){
												echo ' - ' . esc_attr( $widgets_choice['widget_option_terms'] );
											  }
										?>
                                        
                                        </div>
                                        <div class="cdp_banner_settings_action"><a href="#" class="cdp_delete_banner_btn" rel="<?php echo $widget_domaineid;?>"><?php echo __("Supprimer", CDP_PLUGIN_NAME ); ?></a></div>
                                    </td>
                            </tr>
                            <tr>
                                	<td colspan="3" class="cdp_nopad" style="height:5px; font-size:0px">
                                    </td>
                            </tr>
                            </table>
                            
                            <table id="cdp_widgets_table_<?php echo $widget_domaineid;?>" rel="<?php echo $widget_domaineid;?>" class="cdp_widgets_table" border=0 cellpadding=0 cellspacing=0 width="100%" style="display:none; table-layout:fixed;"><!-- table-layout:fixed; to avoid expansion due to ajax content -->
                            <colgroup>
                               <col span="1" style="width: 20%;">
                               <col span="1" style="width: 20%;">
                               <col span="1" style="width: 60%;">
                            </colgroup>
                            <tr>
                                	<td colspan="3" class="cdp_nopad">
                                    	<div class="cdp_iframe_status_holder"> 
	                                    	<div id="cdp_iframe_status_<?php echo $widget_domaineid?>" class="cdp_iframe_status"></div> 
                                        </div>
                                    </td>
                            </tr>
                            <?php
                            // nous voulons le widget au dessous - si c'est trop large 
							if ($widgets_choice['widget_largeur'] > 460 || $widgets_choice['widget_largeur']=='auto'){
								?> 
								<tr>
                                	<td colspan="3">
                                        <!-- start widget top table -->
                                        <table id="cdp_widgets_table_top_<?php echo $widgets_size;?>" border=0 cellpadding="0" cellspacing="10" width="100%">
                                            <tr>
                                                <td valign="top" align="left" class="cdp_nopad">
                                                    <?php
                                                        echo $html_iframe_sample; 
                                                        $html_iframe_sample = '';
                                                    ?>
                                                </td>
                                            </tr>
                                        </table>
                                     </td>
                                </tr>
								<?php
							}// if large
							?>
                           <!-- <tr>
								<td>
									<?php echo __("Type de Bannière", CDP_PLUGIN_NAME ); ?>
								</td>
								<td>
									<?php cdp_widgets_dropdown($widgets_available, $widgetid); ?>
							   </td>                                
							</tr>  -->  
                            <tr>
								<td width="20%">
									<?php echo __("Taille", CDP_PLUGIN_NAME ); ?> <strong><?php echo $widgets_size_display; ?></strong> - <?php echo __("Actif", CDP_PLUGIN_NAME ); ?>
								</td>
								<td width="20%">
									
									<input type="hidden" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widgetid]" value="<?php echo $widgetid;?>"/>
									<input type="hidden" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][domaineid]" value="<?php echo $domaineid;?>"/>
									<input type="hidden" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_largeur]" value="<?php echo $widgets_choice['widget_largeur'];?>"/>
									<input type="hidden" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_hauteur]" value="<?php echo $widgets_choice['widget_hauteur'];?>"/>
                                    <input type="hidden" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_contextuel]" rel="<?php echo $widget_domaineid; ?>" value="<?php echo $widgets_choice['widget_contextuel'];?>"/>
									<input type="hidden" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_label_limit]" rel="<?php echo $widget_domaineid; ?>" value="<?php echo $widgets_choice['widget_option_label_limit	'];?>"/>
                                    
									<input type="hidden" rel="<?php echo $widget_domaineid; /*leave as size*/?>" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_size]" value="<?php echo $widgets_size_as_prefix;?>"/>
									<input type="checkbox" <?php echo $checked;?> rel="<?php echo $widget_domaineid;?>" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_active]" value="TRUE" placeholder="" />
								</td>      
								
								<td  width="60%" rowspan="8"  valign="top"<?php echo $sample_td_height_html;?>>
									<?php echo $html_iframe_sample; ?>
								</td>                                                          
							</tr>						
							
							<tr>
								<td>
									<?php echo __("Afficher en même temps sur <br />les pages de mon Guide Shopping", CDP_PLUGIN_NAME ); ?>
								</td>
								<td colspan="1">
									<input type="checkbox" rel="<?php echo $widget_domaineid;?>" <?php echo $show_checked;?> name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_show_beside_cdp]" value="TRUE" placeholder="" />
							   </td>                                
							</tr>    
                            
                            <tr>
								<td>
									<?php echo __("Afficher apres <br />les pages de mon site", CDP_PLUGIN_NAME ); ?>
								</td>
								<td colspan="1">
									<input type="checkbox" rel="<?php echo $widget_domaineid;?>" <?php echo $show_after_page_check;?> name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_show_after_page_content]" value="TRUE" placeholder="" />
							   </td>                                
							</tr>    
                            
                            <tr>
								<td>
									<?php echo __("Afficher apres <br />les articles de mon site", CDP_PLUGIN_NAME ); ?>
								</td>
								<td colspan="1">
									<input type="checkbox" rel="<?php echo $widget_domaineid;?>" <?php echo $show_after_post_check;?> name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_show_after_post_content]" value="TRUE" placeholder="" />
							   </td>                                
							</tr>   
                            
                            <?php 
									//
									//vd($widgets_choice['widget_option_label_limit']);
									if (isset($widgets_choice['widget_option_label_limit']) && $widgets_choice['widget_option_label_limit'] > 0 ){
							?>
                            <tr>
								<td>       
									<?php echo __("Libelle de la bannière", CDP_PLUGIN_NAME ); ?><br />
									( <?php echo $widgets_choice['widget_option_label_limit']; echo '&nbsp;'; echo __("caractères maximum", CDP_PLUGIN_NAME ); ?> )
								</td>
								<td colspan="1">
									<input rel="<?php echo $widget_domaineid;?>" type="text"  id="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_label]" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_label]" value="<?php echo esc_attr( $widgets_choice['widget_option_label'] ); ?>" placeholder="" />
									<div id="<?php echo $widgets_size;?>_label" style="display:none; position:absolute;" class="cdp_banner_label"><?php echo __('Appuyez sur retourne pour mettre la bannière à jour...', CDP_PLUGIN_NAME ); ?></div>
                                </td>                                
							</tr>   
                            <?php 
									}//if widget_domaine_libelle_limit
							?>
							<tr>
								<td>
									<?php echo __("Couleur", CDP_PLUGIN_NAME ); ?>
								</td>
								<td colspan="1">
                                	<script type="text/javascript">
									var myPicker = new jscolor.color(document.getElementById('<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_color]'), {})
									myPicker.fromString('<?php echo esc_attr( $widgets_choice['widget_option_color'] ); ?>')  // now you can access API via 'myPicker' variable
									</script>
									<input rel="<?php echo $widget_domaineid;?>" type="text" id="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_color]" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_color]" value="<?php echo esc_attr( $widgets_choice['widget_option_color'] ); ?>" class="color" autocomplete="off">
							   </td>                                
							</tr>    
                            
							<tr>
								<td>
									<?php echo __("<strong>Limite</strong> d'Articles à afficher", CDP_PLUGIN_NAME ); ?>
								</td>
								<td colspan="1">
									<select rel="<?php echo $widget_domaineid;?>" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_limit]" style="width: 50px">
									  <?php 
									  //pr($widgets_choice);
									  for ($i=5; $i<21; $i++): 
												$selected = '';
												if ($i == $widgets_choice['widget_option_limit']) {$selected = ' SELECTED ';} else { $selected = ''; }
									  ?>
											  <option value="<?php echo $i; ?>"<?php echo $selected; ?>><?php echo $i; ?></option>
									  <?php endfor; ?>
									</select> 
							   </td>                                
							</tr>  
							 
							<tr>
								<td>       
									<?php echo __("Affiche les articles liés aux <strong>termes</strong>", CDP_PLUGIN_NAME ); ?>
								</td>
								<td colspan="1">
									<input rel="<?php echo $widget_domaineid;?>" type="text"  id="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_terms]" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_terms]" value="<?php echo esc_attr( $widgets_choice['widget_option_terms'] ); ?>" placeholder="" />
									<div id="<?php echo $widgets_size;?>_term_notice" style="display:none; position:absolute;" class="cdp_term_notice"><?php echo __('Appuyez sur retourne pour mettre la bannière à jour...', CDP_PLUGIN_NAME ); ?></div>
                                </td>                                
							</tr>      
							
							 <tr>
								<td colspan="1" class="cdp_padd_cell">       
									<strong><em><u><?php echo __("Ou", CDP_PLUGIN_NAME ); ?></u></em></strong>
								</td>
                                <td></td>
							</tr>    
							
							<tr>
								<td valign="top">       
									<?php echo __("des articles dans la <strong>catégorie</strong>", CDP_PLUGIN_NAME ); ?>
									 <p style="width: 210px;"><?php echo __("<br /><br /><strong>Remarque :</strong><br />Si aucun produit correspondant à vos choix n'est trouvé, afin d'assurer une continuité de service alors des articles aléatoires seront quand même affichés.", CDP_PLUGIN_NAME ); ?></p>
								</td>
								<td colspan="2">
									<input rel="<?php echo $widget_domaineid;?>" type="text" style="margin-bottom:10px" readonly id="<?php echo $widget_settings_key; ?>_<?php echo $widget_domaineid;?>_widget_option_category" name="<?php echo $widget_settings_key; ?>[<?php echo $widget_domaineid;?>][widget_option_category]" value="<?php echo esc_attr( $widgets_choice['widget_option_category'] ); ?>" placeholder="" />
									<input type="hidden" id="<?php echo $widget_settings_key; ?>_<?php echo $widget_domaineid;?>_widget_option_category_id" rel="<?php echo $widget_domaineid;?>" name="<?php echo $widget_settings_key; ?>[<?php echo $widget_domaineid;?>][widget_option_category_id]" value="<?php echo esc_attr( $widgets_choice['widget_option_category_id'] ); ?>" placeholder="" />
									<!--
                                    <input type="hidden" id="<?php echo $widget_settings_key; ?>_<?php echo $widgets_size;?>_widget_option_category_id" rel="<?php echo $widget_domaineid;?>" name="<?php echo $widget_settings_key; ?>[<?php echo $widgets_size;?>][widget_option_category_id]" value="<?php echo esc_attr( $widget_settings[$widgets_size]['widget_option_category_id'] ); ?>" placeholder="" />
                                    -->
									
									<?php 
									
									$catid_selected = esc_attr( $widgets_choice['widget_option_category_id'] ); //pass to allow checked boxes
									
									if ($catid_selected == '-1'){ $catid_selected=''; } //to invoke default
									
									//$no_domaine=TRUE:
									//want all cats regardless of domains
									cdp_categories_table($slim=TRUE, $widget_domaineid, $catid_selected, $no_domaine=TRUE); ?>
                                    
								</td>                                
							</tr> 
							
                            <tr>
                            	<td colspan="3" class="cdp_enselection_area">
                                    <!-- start GF -->
                                    <div class="cdp_enselection_holder">
                                		<p><strong><?php echo __("Coups de Coeur :", CDP_PLUGIN_NAME ); ?></strong></p>
                                        <table cellpadding="5" cellspacing="0" class="cdp_enselection_table" rel="<?php echo $widget_domaineid?>" >
                                            <tr>
                                                <td class="cdp_ajouter_left" rowspan="2">                            
                                                        <div id="cdp_ajouter_left_title"><?php echo __("<p>Ajouter en parcourant le catalogue :</p>", CDP_PLUGIN_NAME ); ?></div>
                                                        <?php 
                                                         //show categories and their articles
                                                            
                                                            $not_domaine_specific = TRUE;
                                                                
                                                            //show categories tree in banners edit area
                                                            cdp_categories_articles_table($not_domaine_specific);
                                                             // show articles
                                                            require_once CDP_PLUGIN_PATH.'admin/inc/jquery_articles_widgets.php'; 
                                                        ?>
                                                </td>
                                                
                                                <td class="cdp_ajouter_middle" rowspan="2">                                  
                                                    <?php echo __("ou", CDP_PLUGIN_NAME ); ?>
                                                </td>
                                                
                                                <td class="cdp_ajouter_right" height="50px">
                                                    <div id="cdp_ajouter_right_title"><?php echo __("<p>Ajouter en faisant une recherche :</p>", CDP_PLUGIN_NAME ); ?></div>
                                                    <div id="cdp_addarticles" class="ui-helper-clearfix">
                                                        <div><label id="cdp_addarticlesboxLabel"><?php echo __("Ajouter des articles ", CDP_PLUGIN_NAME ); ?>:</label><input id="cdp_addarticlesbox" type="text"></div>
                                                    </div><!-- search box -->
                                                </td>
                                            </tr>
                                            <tr><td></td></tr>
                                        </table>
                                    </div>
                                    
                                    <div class="cdp_articles_per_cat_holder" id="cdp_articles_per_cat_holder_<?php echo $widget_domaineid?>" rel="<?php echo $widget_domaineid?>">
                                        <div class="cdp_articles_per_cat_loader_holder" id="cdp_articles_per_cat_loader_holder_<?php echo $widget_domaineid?>" rel="<?php echo $widget_domaineid?>" style="display:none"><div class="cdp_articles_per_cat_loader" id="cdp_articles_per_cat_loader_<?php echo $widget_domaineid?>" rel="<?php echo $widget_domaineid?>"><img src='<?php echo CDP_IMG_URL_ADMIN.'cdp-ajax-loader.gif';?>' /></div></div>
                                        <div class="cdp_articles_per_cat" id="cdp_articles_per_cat_<?php echo $widget_domaineid?>" rel="<?php echo $widget_domaineid?>" style="display:none"></div><!-- receives articles per category-->
                                    </div>
                                   
                                    <div id="articles">
                                    <?php  cdp_article_list_header($widget_domaineid); ?>
                                        <div id='cdp_articles_list_loading'><p><img src='<?php echo CDP_IMG_URL_ADMIN.'cdp-ajax-loader.gif';?>' /></p></div>
                                        <div id="cdp_articles_list">
                                            <!-- populated at runtime -->
                                        </div><!-- cdp_articles_list --> 
                                    </div><!-- cdp_articles --> 
                                    
                                    <!-- end GF -->
                                </td>
                            </tr>
							<!--<tr>
								<td colspan="3" class="cdp_hr">       
									<hr>
								</td>
							</tr> -->
							</table>
							<?php
							
							$i = $i + 1;
						}//for
				?>
            	</td>
            </tr>
            </table><?php
		}//if is_array
		else {
			echo __("Pas de bannières disponibles", CDP_PLUGIN_NAME );
		}
	}//function
}//function


/* 
 * function called by filter to add chosen banners after content
 *
 */
if (!function_exists("cdp_banners_after_content")) {
	function cdp_banners_after_content($content){
					
		// not category
		// is single page / post
		if ( !is_category() && !is_archive() && is_singular()  ){
			
			// return the content
			echo $content;
			
			// check have any contextuelle banners been implimented by admin user
			$contextuelle_banners = get_option('cdp_widget_contextuelle_settings');
			
			$permalink= get_permalink(); // needed for reference 
			
			// 1. check contextuelle banners allowed
			//
			
			// if this link is in contextuelle array
			// and is set to active
			if (isset($contextuelle_banners[$permalink]) && $contextuelle_banners[$permalink] == 1  ){
				$domaineid = get_option('cdp_domaineid');
				if (is_numeric($domaineid)){
					echo "<div style='padding-top:20px'><iframe src=\"".CDP_URL_EXTRA."$domaineid".CDP_WIDGET_URL."9\" width=\"100%\" height=\"150\" frameborder=\"0\" name=\"cdp_iframe_widget_autox150\" scrolling=\"no\"></iframe></div>";
				}
			}
			else {
			
				//2. check if other widgets set to be allowed to show here
				//
				
				// quick check to see if allowed/chosen via the banners tab
				$widgets_allowed_here = get_option( 'cdp_widget_settings_aftercontent' );
				
				if ($widgets_allowed_here == 'TRUE'){
				
					// get banners that are chosen to show under content
					$all_banner_widgets = get_option( 'cdp_widget_settings' );
					
					foreach ($all_banner_widgets as $current_widget){
						// if selected to be here
						// and is page/post
						// and not homepage
						if (
							(isset($current_widget[widget_option_show_after_post_content]) && $current_widget[widget_option_show_after_post_content] == 'TRUE' && !is_page() && !is_home())
							||
							(isset($current_widget[widget_option_show_after_page_content]) && $current_widget[widget_option_show_after_page_content] == 'TRUE' && is_page() && !is_home())
						){
							// get domaineid
							if (isset($current_widget['domaineid'])){
								$domaineid = intval($current_widget['domaineid']);
							}
							// get widgetid
							if (isset($current_widget['widgetid'])){
								$widgetid = intval($current_widget['widgetid']);
							}
							// get width
							if (isset($current_widget['widget_largeur'])){
								$width = $current_widget['widget_largeur'];
								if ($width == 'auto'){ $width = '100%'; } else { $width = intval($width); }
							}
							// get height
							if (isset($current_widget['widget_hauteur'])){
								$height = intval($current_widget['widget_hauteur']);
							}
							// get name							
							if (isset($current_widget['widget_option_size'])){
								$widget_name = $current_widget['widget_option_size'];
			
							}
							
							// output the banner
							echo "<div style='padding-top:20px'><iframe src=\"".CDP_URL_EXTRA."$domaineid".CDP_WIDGET_URL."$widgetid\" width=\"$width\" height=\"$height\" frameborder=\"0\" name=\"cdp_iframe_widget_{$width}x{$height}\" scrolling=\"no\"></iframe></div>";
			
						}
						
					}//for
				}//if widgets allowed
			}
		}
		else {
				// nothing to do
				
				// return content untouched
				return $content;
			}
	}//function
}//function

/* 
 * function called by Ajax to update widget
 * takes: 	$_POST['articleid']
 * returns: result / false
 */
if (!function_exists("cdp_update_widget_contextuelle_ajax")) {
	function cdp_update_widget_contextuelle_ajax(){
		
		if (wp_verify_nonce( $_POST['_ajax_nonce'], 'widgetcontextuellenonceupdate' ) && $_POST['action'] == 'cdp_updatewidget_contextuelle' ){
			//vd($_POST);
			$input = json_decode(stripslashes($_POST['widgetdata']), true);
			
			$current_settings = get_option('cdp_widget_contextuelle_settings');
	
			// what was passed? 
			// is it active?
			if (isset( $input['autox150']['active'])){
				$active = $input['autox150']['active'];
				//vd($active);
			}
			
			// which url?
			if (isset($_POST['widgeturl'])){
				$url = filter_var($_POST['widgeturl'], FILTER_SANITIZE_URL);
				$current_settings[$url]=$active; //update
				
				// local wordpress update of databse with these new details	
				update_option( 'cdp_widget_contextuelle_settings', $current_settings );
			}
			
			die(); //0 issue
		} else { return FALSE; }
	} /* end function */ 
} /* end function */ 

// meta box on post/page
// below post/article in admin 
// allows addition of articles to contextuel banners
// This bit was NOT fun! Not at all ;)
// 1. check via jquery if we have a permalink  (check content in div id=edit-slug-box)
// 2. load box via jquery
// 3. or don't load if no permalink
if (!function_exists("cdp_meta_box_banner_contextuelle")) {
	function cdp_meta_box_banner_contextuelle($data) {
	
		// jquery needed
		require_once CDP_PLUGIN_PATH.'admin/inc/jquery_articles_contextual.php'; 
			
		// below is a similar article selection tool to that used for putting articles in a selection / coups de coeur
		// goal is that it will look the same but behave independantly 
		
		// get this current admin url
		$cdp_current_wp_admin_path = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]; 
		$cdp_current_wp_admin_path = filter_var($cdp_current_wp_admin_path, FILTER_SANITIZE_URL);
		
		$permalink 	= cdp_get_contextuelle_permalink($cdp_current_wp_admin_path);
			
		global $post;
		$postid = $post->ID;

		if ($permalink == FALSE){
		?>
	   <div id="cdp_meta_box_contextuelle_notice"><p><?php echo __("Une bannière contextuelle affichera des articles en rapport avec cette page / article. Les articles seront automatiquement ajoutés et peuvent être éditées manuellement.", CDP_PLUGIN_NAME ); ?></p>
			<input type="submit" class="metabox_submit button button-highlighted" value="<?php echo __("Ajoutez une bannière contextuelle", CDP_PLUGIN_NAME ); ?>" /><!-- jquery click publish -->
		</div>
		<div id="cdp_meta_box_contextuelle_loader_holder" style="display:none"><div id="cdp_meta_box_contextuelle_loader"><img src='<?php echo CDP_IMG_URL_ADMIN.'cdp-ajax-loader.gif';?>' /></div></div>
		<div id="cdp_meta_box_contextuelle"></div><!-- receives via jquery-->
		<?php
		}	
		else {
			// fill with content
			cdp_meta_box_banner_contextuelle_content($postid);
		}
	}
}
///
if (!function_exists("cdp_meta_box_banner_contextuelle_content")) {
	function cdp_meta_box_banner_contextuelle_content($postid) {
			
		?>
				<table id="cdp_posts_add_articles_area" width="100%" cellspacing="5px">
					<tr valign="top">
						<td>
							
							<div id="cdp_enselection_holder">
										
										<table cellpadding="5" cellspacing="0" id="cdp_enselection_table" >
											<tr>
												<td class="cdp_ajouter_left" rowspan="2">                            
														<div id="cdp_ajouter_left_title"><?php echo __("<p>Ajouter en parcourant le catalogue :</p>", CDP_PLUGIN_NAME ); ?></div>
														<?php 
														 //show categories and their articles
															$not_domaine_specific = TRUE;
															
															//show categories tree in contextuelle articles area
															cdp_categories_articles_table($not_domaine_specific);
															
															
														?>
												</td>
												
												<td class="cdp_ajouter_middle" rowspan="2">                                  
													<?php echo __("ou", CDP_PLUGIN_NAME ); ?>
												</td>
												
												<td class="cdp_ajouter_right" height="50px">
													<div id="cdp_ajouter_right_title"><?php echo __("<p>Ajouter en faisant une recherche :</p>", CDP_PLUGIN_NAME ); ?></div>
													<div id="cdp_addarticles" class="ui-helper-clearfix">
														<div><label id="cdp_addarticlesboxLabel"><?php echo __("Ajouter des articles ", CDP_PLUGIN_NAME ); ?>:</label><input id="cdp_addarticlesbox" type="text"></div>
													</div><!-- search box -->
												</td>
											</tr>
											<tr><td></td></tr>
										</table>
									</div>
						
						</td>
					</tr>
				</table>
				
				<div id="cdp_articles_per_cat_holder">
					<div id="cdp_articles_per_cat_loader_holder" style="display:none"><div id="cdp_articles_per_cat_loader"><img src='<?php echo CDP_IMG_URL_ADMIN.'cdp-ajax-loader.gif';?>' /></div></div>
					<div id="cdp_articles_per_cat" style="display:none"></div><!-- receives articles per category-->
				</div>
									
				<div id="articles">
				<?php  cdp_article_list_header(); ?>
					<div id='cdp_articles_list_loading'><p><img src='<?php echo CDP_IMG_URL_ADMIN.'cdp-ajax-loader.gif';?>' /></p></div>
					<div id="cdp_articles_list">
						<!-- populated at runtime -->
					</div><!-- cdp_articles_list --> 
				</div><!-- cdp_articles --> 
				<?php 
					
					// need to check here that banner 9 (the contextual banner used) is available
					// if not already added to this site it needs to be 
					
					$main_context_widget_found = FALSE;
					
					$widgets = cdp_get_widgets_local();					
					
					if (is_array($widgets)){
						//pr($widgets);
						//search for widget 9
						
						foreach ($widgets as $widget_arr){
							//vd($widget_arr);
							//if have all details
							//if (isset($widget_arr['widgetid']) && $widget_arr['widgetid'] == 9){
							if (isset($widget_arr['widget_hauteur']) && $widget_arr['widget_hauteur'] == 150 &&
								isset($widget_arr['widget_largeur']) && $widget_arr['widget_largeur'] == 'auto' &&
								isset($widget_arr['widget_contextuel']) && $widget_arr['widget_contextuel'] == 1
								){
								
								$main_context_widget_found = TRUE;
								$main_context_widget_id = intval ($widget_arr['widgetid']);
							}
						}
					}
			
					//vd($main_context_widget_found);die;
					
					if ($main_context_widget_found == FALSE){
						//Need to add the widget	
						//echo 'Need to add the widget	';
						$widgets_available = cdp_get_widgets_available();
						//pr($widgets_available);
						
						foreach ($widgets_available as $widget_arr){
							//vd($widget_arr);
							//if have all details
							//if (isset($widget_arr['widgetid']) && $widget_arr['widgetid'] == 9){
							if (isset($widget_arr['widget_hauteur']) && $widget_arr['widget_hauteur'] == 150 &&
								isset($widget_arr['widget_largeur']) && $widget_arr['widget_largeur'] == 'auto' &&
								isset($widget_arr['widget_contextuel']) && $widget_arr['widget_contextuel'] == 1
								){
								
								$main_context_widget_id = intval ($widget_arr['widgetid']);
							}
						}
						
						//vd($main_context_widget_id);
						if (isset($main_context_widget_id) && is_numeric($main_context_widget_id)){
							
							$blogid = get_option('cdp_blogid'); 
							$typeid = 2; //looking for widgets not guides/comparators 
							
							$data_clean = array();
							$data_clean['widgetid'] = intval(9);//$main_context_widget_id
							
							// send for update
							//$update_result = cdp_add_widget($data_clean);
							
							$update_result = cdp_transmit_personal_settings($data_clean);
						}
					}
					
					//get the permalink for this banner / article
					$permalink= cdp_get_draft_permalink($postid); // needed for reference 
					
					// get local list of contextual banners 
					$contextuelle_banners = get_option('cdp_widget_contextuelle_settings');
					//pr($contextuelle_banners);
					
					// if none
					if ($contextuelle_banners === false){
						// first time loading any contextual banner
						
						// add option
						$contextuelle_banners_array = array($permalink => 0); // 0 inactive
						add_option( 'cdp_widget_contextuelle_settings', $contextuelle_banners_array);
						
						// retrieve
						$contextuelle_banners = get_option('cdp_widget_contextuelle_settings');
						
					} else {
						//we've added context banners before
						//now ensure this new one is recorded locally (prevents resending)
						
						$banner_checked = '';	
						
						//if this banner already recorded
						if (isset($contextuelle_banners[$permalink])){

							//is this banner active?
							$banner_active = $contextuelle_banners[$permalink]; //stored value in options
							
							if ($banner_active == 1){
								$banner_checked = ' checked="checked" '; //show checked
							} 
							
						} else {
							
							//get current settings
							$current_settings = get_option('cdp_widget_contextuelle_settings');
							
							//first time we're looking at this new banner
							//let's record it's url locally
							$active = 1; //default
							$current_settings[$permalink]=$active; //update
							
							// local wordpress update of databse with these new details	
							update_option( 'cdp_widget_contextuelle_settings', $current_settings );
							
							$banner_checked = ' checked="checked" '; //show checked
							
						}
						
						
					} 
	
					
					
				?>
				<!-- the banner -->
				<br /><br clear="all">
				<div class="cdp_notice settings-error cdp_align_center" >
					<strong><?php echo __("Remarque"); ?></strong><?php echo " : "; echo __("Cette bannière prend automatiquement la largeur de la page / l'endroit où il est placé ", CDP_PLUGIN_NAME); ?><br />
					<?php  echo __("Nous recommandons 10 articles minimum ", CDP_PLUGIN_NAME);	?>
				</div>
				<div class="cdp_iframe_status_holder"><div id="cdp_iframe_status_autox150" class="cdp_iframe_status"></div></div>
				<div style="width:100%">
				<?php  echo __("Afficher cette bannière après cet article sur mon site ", CDP_PLUGIN_NAME);	?>&nbsp;<input type="checkbox" placeholder="" value="TRUE" id="banner_contextuelle_active_autox150" name="banner_autox150_active" rel="autox150" <?php echo $banner_checked; ?>>
				<br /><br />
				<? $domaineid = get_option('cdp_domaineid'); ?>
				<iframe width="100%" height="150" scrolling="no" frameborder="0" id="cdp_iframe_widget_autox150" name="cdp_iframe_widget_autox150" src="<?php echo CDP_URL_EXTRA.$domaineid.CDP_WIDGET_URL; ?>9?finalurl=<?php echo $permalink;?>"></iframe>
	
				<input type="hidden" value="<?php echo $permalink;?>" id="cdp_widget_contextuelle_url_autox150" rel="autox150" />
				</div>
			<?php
		
	}
}

//
if (!function_exists("cdp_widgets_dropdown")) {
	function cdp_widgets_dropdown($widgets_available, $current_widgetid=NULL, $widget_domaineid=NULL ){
		
		if (!is_array($widgets_available)){ return FALSE; }
		
		?>
		<select rel="<?php echo $widget_domaineid;?>" id="cdp_widget_settings<?php if (is_numeric($widget_domaineid)){ echo "_$widget_domaineid"; } ?>" name="cdp_widget_settings<?php if (is_numeric($widget_domaineid)){ echo "[$widget_domaineid]"; } ?>[widget_option_widgettypeid]" style="width: 200px">
		<?php
			if (!is_numeric($current_widgetid)){
			?>
				<option value=""><?php echo __("Sélectionnez une bannière", CDP_PLUGIN_NAME); ?></option>
			<?php
			}
		?>
		<?php
			foreach ($widgets_available as $widget){
				?>
				  <option value="<?php echo $widget['widgetid']; ?>"<?php if ($widget['widgetid'] == $current_widgetid){ echo ' selected '; } ?>><?php echo $widget['widget_largeur'].' x '.$widget['widget_hauteur']; 
				  if ($widget['widget_contextuel'] == 1) { echo ' - '; echo __("contextuel", CDP_PLUGIN_NAME); }
				  ?></option>
				<?php	
			}//foreach
		?>
		</select>
		<?php
	}
}
// call relevant actions
add_action("plugins_loaded", "cdpCarousel_init");
/**********END OF FILE **************/
?>