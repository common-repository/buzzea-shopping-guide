<?php 
/*
Description: This file contains the frontend functions for the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/

/* 
 * function gets language file
 * takes: 
 * returns:
 */
if (!function_exists("cdp_lang_init")) {
	function cdp_lang_init() {
	  // load_plugin_textdomain( $domain, $abs_rel_path, $plugin_rel_path )
	  load_plugin_textdomain( CDP_PLUGIN_NAME, false, CDP_PLUGIN_FOLDER_NAME . '/languages' ); //dirname( plugin_basename( __FILE__ ) )
	}/*end function*/
}/*end function*/

/* 
 * function gets the password saved in wordpress settings for this price comparator
 * password in wordpress must match password on file at Buzzea
 * takes: 
 * returns: password / false
 */
if (!function_exists("cdp_get_password")) {
	function cdp_get_password() {
		
		$pass = '';
		
		$pass_details = get_option( 'cdp_general_settings');
		if ($pass_details !=''){
			$pass = sanitize_text_field($pass_details['general_option_password']);
		}
		
		if ($pass !=''){
			return $pass;
		} else { return FALSE;}
				
	}/*end function*/
}/*end function*/

/* 
 * function that gets latest CDP page with shortcode 
 * takes: 
 * returns: true / false
 */
if (!function_exists("cdp_location")) {
	function cdp_location(){	
		$cdp_url = 	cdp_all_locations($first=TRUE);
		
		if ($cdp_url != '' && $cdp_url!=FALSE){ 
			return rtrim($cdp_url, '/');
		} else {
			return FALSE;	
		}
	} /* end function */
} /* end function */

/* 
 * function that gets all CDP pages with shortcode 
 * takes: 
 * returns: true / false
 */
if (!function_exists("cdp_all_locations")) {
	function cdp_all_locations($first=FALSE){	
		//the following finds all database posts with the shortcode and then adds rewrite rules for those posts
		global $wpdb;	
		
		//set limit to get only latest result
		$limit = '';
		if (isset($first) && $first == TRUE){ $limit = ' LIMIT 0,1 '; }
		
		//choose correct table - remembering prefix!
		$posts_table_name = '`wp_posts`';
		if (isset($wpdb->prefix)){ $posts_table_name = "`{$wpdb->prefix}posts`";}
	
		//find comparateur URLS for this blog
		$sql = "SELECT ID, guid FROM $posts_table_name WHERE `post_content` LIKE '%[".CDP_PLUGIN_SHORTCODE."]%' AND `post_status` = 'publish' ORDER BY $posts_table_name.`post_modified` DESC $limit";
		//echo '<br /><br />'.$sql.'<br /><br />';
		$pages_with_cdp = $wpdb->get_results( $sql );
			
		if (is_array($pages_with_cdp) && count($pages_with_cdp)>0){
			
			if ($limit !='' && isset($pages_with_cdp[0]->ID) && is_numeric($pages_with_cdp[0]->ID) ){
				
				//have limit, want one result
				$cdp_permalink = get_permalink( $pages_with_cdp[0]->ID ); 
				//Notice: Was getting:
				// Trying to get property of non-object in /home/xxx/www/blogname/wp-includes/link-template.php on line 36
				// and
				// Fatal error: Call to a member function get_page_permastruct() on a non-object in /home/xxx/www/blogname/wp-includes/link-template.php on line 276
				//due to being called too soon. Was using add_action('plugins_loaded' for the admin tabs. Now using the later add_action('admin_init'
				
				if ($cdp_permalink != ''){
					return $cdp_permalink;
				}
			} else {
				return $pages_with_cdp;
			}
			
		}	
		else {
			
			//return site_url(); 
			return FALSE; 
		}
	} /* end function */
}/* end function */

/* 
 * function gets the current page / url
 * takes: 
 * returns: URL
 */
// Callback function for the [buzzea_cdp] shortcode
if (!function_exists("cdp_display_comparateur")) {
	function cdp_display_comparateur() {
		/*global $post;
		vd ($post);*/
		//call get comparateur first in order to:
		//		1. get comparateur HTML 
		//or 	2. act on a new link sent back by the comparateur
		 
		//if this function is called a SHORTCODE has been found 
		
		if (CDP_DEBUG){
			global $wp_rewrite;
			vd ($wp_rewrite);	
		}
		
		if ( !is_category() && is_singular()  ){ //|| is_category()
		
			global $cdp_found;
			$cdp_found = TRUE;
			
			/*This Conditional Tag checks if a singular page is being displayed. Singular page is when any of the following return true: is_single(), is_page() or is_attachment().
			 This is a boolean function, meaning it returns either TRUE or FALSE*/
			require_once CDP_PLUGIN_PATH.'inc/frontend_search_jquery.php';
	
			global $cdp_results;
			$cdp_results = '<!-- cdp_version: '.CDP_PLUGIN_VERSION. ' -->'.$cdp_results;
			return $cdp_results; 
			
		} else {
			//not a single page (i.e. homepage)
			
			/*******************/
			/* Summary version */
			/*******************/
			
			//not a single page (i.e. homepage)
	
			$cdp_personal_details 	= get_option( 'cdp_personal_settings');
			$cdp_theme_details 		= get_option( 'cdp_theme_settings');
			
			$cdp_details_libelle 			= sanitize_text_field($cdp_personal_details['personal_option_libelle']);
	
			$cdp_details_color_text 		= sanitize_text_field($cdp_theme_details['personal_option_color_text']);
			$cdp_details_color_links 		= sanitize_text_field($cdp_theme_details['personal_option_color_links']);
			$cdp_details_color_theme 		= sanitize_text_field($cdp_theme_details['personal_option_color_theme']);
			$cdp_details_color_background 	= sanitize_text_field($cdp_theme_details['personal_option_color_background']);
			
			$post_link=post_permalink();//get_permalink = ugly
			$cdp_link_msg = __('cliquez ici pour voir', CDP_PLUGIN_NAME );
			$cdp_snippet = "<div style='font: #$cdp_details_color_text'>$cdp_link_msg <a href='".$post_link."' style='color: #$cdp_details_color_links'>$cdp_details_libelle</a></div>";
			
			global $cdp_results;
			
			$cdp_results = $cdp_snippet;	
			
			return $cdp_results;	
		}
		
	}/* end function */ 
}/* end function */

/* 
 * function gets the current visitor details
 * takes: 
 * returns: array
 */
if (!function_exists("cdp_get_visitor")) {
	function cdp_get_visitor() {
			if (isset($_SERVER)){
				
				if (isset($_SERVER['REMOTE_ADDR']))			{ 	$remote_addr			= $_SERVER['REMOTE_ADDR'];			} else { $remote_addr = '';}
				if (isset($_SERVER['HTTP_USER_AGENT']))		{ 	$http_user_agent	 	= $_SERVER['HTTP_USER_AGENT'];		} else { $http_user_agent = '';}
				if (isset($_SERVER['HTTP_VIA']))			{ 	$http_via 				= $_SERVER['HTTP_VIA'];				} else { $http_via = '';}
				if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){ 	$http_x_forwarded_for 	= $_SERVER['HTTP_X_FORWARDED_FOR'];	} else { $http_x_forwarded_for = '';}
				
				$visitor 		= array( 'remote_addr' => $remote_addr, 
										 'http_user_agent' => $http_user_agent,
										 'http_via' => $http_via,
										 'http_x_forwarded_for' => $http_x_forwarded_for
										  );
				$visitor_serial = serialize($visitor);
				
				return $visitor_serial;
			}
			else { return FALSE; }
	}/*function*/
}/*fin de la fonction*/
/* 
 * function gets the current page / url
 * takes: 
 * returns: URL
 */
// Callback function for the [buzzea_cdp] shortcode
if (!function_exists("cdp_get_comparateur")) {
	function cdp_get_comparateur() {
	
		if ( !is_category() && is_singular()  ){ //|| is_category()
	
			/***********************/
			/*	Single page / post */
			/***********************/
			$password = md5(cdp_get_password());
			
			//init
			//$cdp_path_query_addition = ''; 
			$cdp_path_query_addition_arr = '';
			$cdp_path_query_addition_new = '';
			
			//GET variable names
			$cdppagparam = CDP_PAGINATION_PARAMETRE;
			$cdporderparam = CDP_ARTICLE_ORDER_PARAMETRE;
			
			// check GET for 'order'
			if (isset($_GET[$cdporderparam])){
				$cdp_path_query_addition_arr[] = "$cdporderparam=". esc_attr($_GET[$cdporderparam]);
			}
			//check GET for 'page'
			//additional for pagination
			//vd($_GET[$cdppagparam]);
			if (isset($_GET[$cdppagparam])){
				$cdp_path_query_addition_arr[] = "$cdppagparam=". intval($_GET[$cdppagparam]);
			}
			
			//join up all items of query
			if (is_array($cdp_path_query_addition_arr) && count($cdp_path_query_addition_arr)>0){
				$i = 0;
				$cdp_path_query_addition_new = '';
				//vd($cdp_path_query_addition_arr);
				foreach ($cdp_path_query_addition_arr as $cdp_path_query_addition_arr_bit){
					$joiner = '&';
					if ($i == 0){ $joiner = '?'; }
					
					$cdp_path_query_addition_new.=$joiner.$cdp_path_query_addition_arr_bit;
					
					$i = $i+1;
				}
			}
			
			//check GET for 'cdpterms' - Search
			if (isset($_GET['cdpterms'])){
				$cdp_path_query_addition_new = esc_attr($_GET['cdpterms']);
				//echo "/". esc_attr($_GET['cdpterms']);
				$cdp_path_query_addition_new = "/".urlencode(utf8_encode(urldecode($cdp_path_query_addition_new)));
			}
			
			// page location without variables
			$cdp_path_query = get_query_var( 'cdp_path_query' ); //i.e. string(29) "appareils-de-cuisson-197.html" 
			//debug 
			//var_dump($cdp_path_query);
			
			// for blogs using default permalink ?p=55 setup
			// can't do a rewrite rule for this...
			if (isset($_GET['p'])){
				if (!is_numeric($_GET['p'])){
				 // we have a path after the page number	
					$cdp_path_query =  esc_attr($_GET['p']);
					$pattern = '/^(\d+)\//';	// remove first 55/
					$replacement = '';			// blank to remove
					$cdp_path_query = preg_replace($pattern, $replacement, $cdp_path_query, 1);
				}
				
			}
			
			if ($cdp_path_query == ''){
				$cdpGotoURL = CDP_APPLICATION_URL;//'http://cdp.moteurdeshopping.com/' - get homepage
				
				if (isset($cdp_path_query_addition_new)){
				$cdpGotoURL = CDP_APPLICATION_URL.$cdp_path_query_addition_new;//'http://cdp.moteurdeshopping.com/' - get homepage
				}
				
			}
			else {
				$cdpGotoURL = CDP_APPLICATION_URL.$cdp_path_query.$cdp_path_query_addition_new; //i.e.  http://cdp.moteurdeshopping.com/appareils-de-cuisson-197.html?cdporder=prix_croissant
			}
			
			if (isset($_SERVER['HTTP_REFERER'])){
				$cdp_wp_http_referer = esc_url($_SERVER['HTTP_REFERER']);
			}else { $cdp_wp_http_referer = ''; }
			
			if (!session_id()) {
				session_start();
			}
			
			//breadcrumb extra
			if (isset($_SESSION['cheminDeFer_Extra'])){
				$cheminDeFer_Extra = esc_attr($_SESSION['cheminDeFer_Extra']);
			}else { $cheminDeFer_Extra  = ''; }
		
			/**********************/
				
			//examiner GET pour 'cdporder' - Trier des articles
			if (isset($_GET['cdporder'])){
				
				if (CDP_SESSION == TRUE){
					if ($_GET['cdporder'] == 'popularite'){
						$_SESSION['cdp_trier_articles'] = 'popularite';	
						$cdp_trier_articles = $_SESSION['cdp_trier_articles'];
					}elseif($_GET['cdporder'] == 'prix_croissant'){
						$_SESSION['cdp_trier_articles'] = 'prix_croissant';	
						$cdp_trier_articles = $_SESSION['cdp_trier_articles'];
					}//if				
				}//if cookies
				
			}//if get
			else {
				//check cookie
				if (isset($_SESSION['cdp_trier_articles'])) {
					$cdp_trier_articles = $_SESSION['cdp_trier_articles'];
				}//if cookie
				else {
					$cdp_trier_articles = '';
				}
	
			}//else
	
			//debug 
			if (CDP_DEBUG){ //CDP_DEBUG
				echo 'going to '.$cdpGotoURL;
				echo '<br />cdp_path_query: ' . $cdp_path_query.'<br />';
				//vd($cdp_path_query_addition_new);
				//global $wp_rewrite;
				//pr($wp_rewrite);
			}
				
			//debug via cookie and password
			if (isset($_COOKIE['cdp_debug'])){
				if (md5($_COOKIE['cdp_debug']) == $password){
					echo 'going to '.$cdpGotoURL.'<br />';
					echo 'cdp_path_query ' . $cdp_path_query.'<br />';
					global $wp_rewrite;
					pr($wp_rewrite);
				}
			}
			
			$visitor_details_serial = cdp_get_visitor(); //serialized details
			
			//do we have a placed CDP via shortcode?
			$cdp_site_url = cdp_location();
			if ($cdp_site_url == FALSE){
				$cdp_site_url = site_url();
			}
			
			$post_result = wp_remote_post($cdpGotoURL, 
											array(
												'method' => 'POST',
												'timeout' => 45,
												'redirection' => 5,
												'httpversion' => '1.0',
												'blocking' => true,
												'headers' => array(),
												'body' => array( 
															'cdp_visitor' => $visitor_details_serial, 
															'cdp_siteurl' => $cdp_site_url, 
															'cdp_password' => $password, 
															'cdp_homeurl' => get_permalink(), 
															'cdp_version' => CDP_PLUGIN_VERSION,
															'mon_cdp_pagination_parametre' => CDP_PAGINATION_PARAMETRE,
															'mon_cdp_article_order_parametre' => CDP_ARTICLE_ORDER_PARAMETRE,
															'mon_cdp_referer' => $cdp_wp_http_referer,
															'mon_cdp_cdf_extra' => $cheminDeFer_Extra,
															'cdp_trier_articles' => $cdp_trier_articles
	
															),
												'cookies' => array()
												)
										);	
			if( is_wp_error( $post_result ) ) { return FALSE; }
			global $cdp_results; 
			//vd($post_result['body']);
			if( is_wp_error( $post_result ) ) {
				 $error_string = $post_result->get_error_message();
				 
				 //get options for colors
				 $cdp_theme_options = get_option('cdp_theme_settings');
				 
				 if (isset($cdp_theme_options['personal_option_color_theme'])){
					$cdp_theme_color = $cdp_theme_options['personal_option_color_theme'];
					$style = " style= \"border: 2px solid #$cdp_theme_color; padding: 10px; margin: 0 0 10px 0; -moz-border-radius:8px; -webkit-border-radius: 8px; border-radius: 8px; \" "; 
				 } else { $style = " style= \"border: 2px solid #D50400; padding: 10px; margin: 10px; -moz-border-radius:8px; -webkit-border-radius: 8px; border-radius: 8px; \" "; }
				 
				 $cdp_results = '<div id="cdp_message" class="cdp_error"'.$style.'><p>' . __('Toutes nos excuses. Nous rencontrons un problème temporaire avec le comparateur. Veuillez revenir ultèrieurement.', CDP_PLUGIN_NAME ) . '</p></div>';
			} else {
				
				//get results
				$cdp_results = $post_result['body']; //force_balance_tags($post_result['body']);
				
				//extract vars	- looking for: [[[cheminDeFer_Extra: - Resolution : 18 MegaPixels]]] 
				$cheminDeFer_Extra =preg_match('/(\[\[\[cheminDeFer_Extra:)(.*)(]]])/', $cdp_results, $cheminDeFer_Extra_matches);
				$cdp_results =preg_replace('/(\[\[\[cheminDeFer_Extra:)(.*)(]]])/', '', $cdp_results); //blank it out after use
				//debug vd($cheminDeFer_Extra_matches);
				
				//put in WP session
				if (isset($cheminDeFer_Extra_matches[2])){
					$_SESSION['cheminDeFer_Extra'] = $cheminDeFer_Extra_matches[2]; //i.e.  " - Resolution : 18 MegaPixels"
				}
				
				//vd($cdp_results);
			}
			
			//check the results - if it is just a URL, we act on it
			if(filter_var($cdp_results, FILTER_VALIDATE_URL) === FALSE){
				//not URL
				$cdp_results = $cdp_results; //force_balance_tags(
			} else {
				//valid URL
				//die($cdp_results);
				wp_redirect($cdp_results);	//jump to it
			}
			
			return $cdp_results;
			
		} else {
			
			///*******************/
	//		/* Summary version */
	//		/*******************/
	//		
	//		//not a single page (i.e. homepage)
	//
	//		vd(get_post_permalink());
	//		$cdp_details = get_option( 'cdp_personal_settings');
	//		$cdp_details_libelle 			= sanitize_text_field($cdp_details['personal_option_libelle']);
	//		$cdp_details_color_text 		= sanitize_text_field($cdp_details['personal_option_color_text']);
	//		$cdp_details_color_links 		= sanitize_text_field($cdp_details['personal_option_color_links']);
	//		$cdp_details_color_theme 		= sanitize_text_field($cdp_details['personal_option_color_theme']);
	//		$cdp_details_color_background 	= sanitize_text_field($cdp_details['personal_option_color_background']);
	//		
	//		$cdp_link_msg = __('cliquez ici pour voir');
	//		$cdp_snippet = "<div style='font: #$cdp_details_color_text'>$cdp_link_msg <a href='".get_post_permalink()."' style='color: #$cdp_details_color_links'>$cdp_details_libelle</a></div>";
	//		
	//		global $cdp_results;
	//		
	//		$cdp_results = $cdp_snippet;	
	//		
	//		return $cdp_results;	
		}
		
	}/*function*/
}/*fin de la fonction*/


/* 
 * function that gets search results for a term
 * takes: 	$term 		term to seaarch for (via GET)
 * returns: JSON details of articles / False
 
 	Tested/ working as of 10:15 02/10/2012
 */
if (!function_exists("cdp_get_search_results")) {
	function cdp_get_search_results(){
		
		check_ajax_referer( 'my-special-string-jquery-search-security', 'security' );//'security' is GET variable name 
	
		if (isset($_GET['term'])){
			$term = esc_attr($_GET['term']);
			$term = str_replace(' ','+', $term); //for transport 
		}
		
		//called by ajax
		if (isset($term)){
			//
			$visitor_details_serial = cdp_get_visitor(); //serialized details
			
			//do we have a placed CDP via shortcode?
			$cdp_site_url = cdp_location();
			if ($cdp_site_url == FALSE){
				$cdp_site_url = site_url();
			}
			
			$post_result = wp_remote_post(CDP_API_URL."search/autocomplete/?term=$term", 
											array(
												'method' => 'POST',
												'timeout' => 45,
												'redirection' => 5,
												'httpversion' => '1.0',
												'blocking' => true,
												'headers' => array(),
												'body' => array( 'cdp_visitor' => $visitor_details_serial, 'cdp_siteurl' => $cdp_site_url ),
												'cookies' => array()
												)
										);	
			if( is_wp_error( $post_result ) ) { return FALSE; }										
			//debug 
			//vd($post_result['body']);
			if (isset($post_result['body'])){
				echo $post_result['body'];
			} else { return FALSE; }
		
		} else { return FALSE; }
		
		die(); //needed
	}/*function*/
}/*fin de la fonction*/

/*************		GENERAL FUNCTIONS 	*******************/
/* 
 * function to var_dump items when needed (abbreviation of vardump) 
 * takes: 	$var to dump
 * returns: dumped var
 */

if (!function_exists("vd")) {
	
	function vd($var){
		return var_dump($var);
	}/*function*/
	
} /* end function */ 

//abbreviation of print_r
if (!function_exists("pr")) {
	function pr($var){
		
		if ($var==NULL || $var == ''){
			vd($var);
		}
		else{
			echo '<pre>';
			print_r($var);
			echo '</pre>';
		}
		
	}/*function*/
}/*fin de la fonction*/
/**********END OF FILE **************/
?>