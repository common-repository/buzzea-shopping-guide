<?php 
/*
Description: This file contains the admin functions for the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/

/* 
 * function gets the install status
 * checks if subscribed or not
 * takes: 
 * returns: -1 : not subscribed
 			 1 : installed
			 0 : not installed / password bad	
 
 */
function cdp_install_status(){
	
	//has the install been completed before?
		$cdp_check_password_installed = cdp_get_password();
		
		if ($cdp_check_password_installed == FALSE){
			
			$cdp_subscription_check =  cdp_check_subscription(); //check for blog at Buzzea
			//vd($cdp_subscription_check);
			if ($cdp_subscription_check == FALSE){
			
				/********************************/
				/*		NOT Buzzea Subscribed	*/
				/********************************/
				return -1;  // not subscribed
			
			}
			else {
				/************************************************/
				/*		subscribed but NOT Fully Installed		*/
				/************************************************/
				// sepwork: 
				// get the password from buzzea via api
				// save to options
				$blog_details = cdp_get_blog_details();
				//vd($blog_details);
				$settings = array('general_option_password'=>$blog_details['blog_password']);
				// add to wp
				update_option( 'cdp_general_settings', $settings );
				
				// get the blogid from buzzea via api
				// save to options
				update_option( 'cdp_blogid', $blog_details['blogid'] );
				
				return 1; //now installed

			}//else
		} else {
			
			//verify password GOOD
			$cdp_password_verif = cdp_check_subscriptionValid($cdp_check_password_installed);
			
			if ($cdp_password_verif == FALSE){
				
			/************************************************************/
			/*		subscribed but NOT Fully Installed / Password bad	*/
			/************************************************************/
				if (isset($_GET['cdp_tab']) && $_GET['cdp_tab']!='cdp_general_settings'){
					$admin_filename = cdp_get_admin_filename();
					$wp_redir =  admin_url().$admin_filename."?page=cdp_plugin_options&cdp_tab=cdp_general_settings"; 
					wp_redirect($wp_redir);
				}
				return 0;
			} else {
				
				/*
					New level of checking
					due to seperation work
					Is there an install method in the install details?
				*/
				$install_details = cdp_get_install_details();	
				//pr($install_details);
				
				if ($install_details == NULL){
					
					$cdp_password = get_option('cdp_general_settings');
					
					if (isset($cdp_password['general_option_password'])){

						//add this domaine
						$cdp_add = cdp_add($cdp_password['general_option_password']);
						//vd($cdp_add);
						
						/********************************/
						/*		Fully Installed			*/
						/********************************/
						if ($cdp_add == TRUE) return 1; else { return 0; }
						
					} else { return 0; }
					
				} else {
	
					/********************************/
					/*		Fully Installed			*/
					/********************************/
					//$this->install_status = 1;
					return 1;
				}
			}
		}
		
}/* end function */ 

/* 
 * function that adds CDP site on file to buzzea
 * takes: 
 * returns: true / false
 */
function cdp_add($password){
	
	$password = md5($password);
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	$cdp_blogid_to_send = get_option('cdp_blogid');
	
	if (!is_numeric($cdp_blogid_to_send)){
		$cdp_blogid_to_send = NULL;
	}
	
	// initial data
	$initial_data = array();
	$initial_data['domaine_typeid'] 		= 1;
	$initial_data['blogid'] 				= $cdp_blogid_to_send;
	$initial_data['theme_graphiqueid'] 		= 1;
	$initial_data['methode_installationid'] = 1;
		
	//request subscription details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'cdp/add', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 
															'cdp_password' => $password, 
															'cdp_install_method_id' => 1, 
															'cdp_personalisation_defaults' => $GLOBALS['personalisation_defaults'], 
															'cdp_blogid' => $cdp_blogid_to_send,
															'cdp_initial_data' => $initial_data 
															),
										'cookies' => array()
										)
								);	
	//debug 
	//vd($post_result['body']);
	if( is_wp_error( $post_result ) ) { return FALSE; }
	
	$add_results = $post_result['body'];
	
	if ($add_results == TRUE ){
		return TRUE;
	}//if
	else {
		return FALSE; 
	}
} /* end function */

/* 
 * function to add rewrite rules needed 
 * takes: 
 * returns: 
 */
function cdp_add_rules() {
		
		//the following finds all database posts with the shortcode and then adds rewrite rules for those posts
		
		/* Get results from the database. */
		/*global $wpdb;	
		$sql = "SELECT ID, guid FROM `wp_posts` WHERE `post_content` LIKE '%[".CDP_PLUGIN_SHORTCODE."]%' AND `post_status` = 'publish'";
		$pages_with_cdp = $wpdb->get_results( $sql );*/
		
		$pages_with_cdp = cdp_all_locations();
			
		if (is_array($pages_with_cdp) && count($pages_with_cdp)>0){
		
			foreach ($pages_with_cdp as $cdpPage){
				$cdp_permalink = get_permalink( $cdpPage->ID );	
				//vd($cdp_permalink);
				$cdp_rulelink = str_replace(site_url(), '', $cdp_permalink);
				$cdp_rulelink = ltrim($cdp_rulelink, '/'); //remove far left /
				
				$cdp_guilink = str_replace(site_url(), '', $cdpPage->guid );
				$cdp_guilink = ltrim($cdp_guilink, '/'); //remove far left / - gives us ?page_id=53, or , ?p=59 ,or, ?p=46
				// vd($cdp_rulelink);
				// remove .htm/html
				if (substr($cdp_rulelink, -5, 5) == '.html' ){
					$cdp_rulelink = substr_replace($cdp_rulelink, '', -5);
				}elseif(substr($cdp_rulelink, -4, 4) == '.htm'){
					$cdp_rulelink = substr_replace($cdp_rulelink, '', -4);
				}
				
				//remove last ?
				//$cdp_rulelink = rtrim($cdp_rulelink, '?');
				
				// ensure end slash
				$cdp_rulelink = rtrim($cdp_rulelink, '/').'/';
				
				// escape any question marks (special in regex)
				$question_present 	= FALSE;
				$qtest 				= strpos($cdp_rulelink, '?');
				
				if ( $qtest !== FALSE) {
					//there is a ? in the link (due to the permalinks setup)
					$question_present = TRUE;
					
				}
				//$cdp_rulelink = str_replace('?', '(.?.+?)', $cdp_rulelink); 
				//$cdp_rulelink = str_replace('?', '\?', $cdp_rulelink); 
				//orig: add_rewrite_rule( $cdp_rulelink.'(\.html\/|\.htm\/|\/|)'.'(([^/]*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*/?){0,8})', 'index.php'.$cdp_guilink.'&cdp_path_query=$matches[2]', 'top' );
				$argindex = 2; //default
				if ($question_present == TRUE) { $argindex = 3; } //one further ahead
				//vd($cdp_rulelink);
				//add_rewrite_rule( '^'.$cdp_rulelink.'(\.html\/|\.htm\/|\/|)'.'(([^/]*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*/?){0,8})', 'index.php'.$cdp_guilink.'&cdp_path_query=$matches['.$argindex.']', 'top' );
				//add_rewrite_rule( '^/'.$cdp_rulelink.'(\.html\/|\.htm\/|\/|)'.'(([^/]*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*/?){0,8})', 'index.php'.$cdp_guilink.'&cdp_path_query=$matches['.$argindex.']', 'top' );
				
				add_rewrite_rule( $cdp_rulelink.'(\.html\/|\.htm\/|\/|)'.'(([^/]*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*(\/)*/?){0,8})', 'index.php'.$cdp_guilink.'&cdp_path_query=$matches['.$argindex.']', 'top' );
			
			}//foreach 
		}//if 
		flush_rewrite_rules();//remove later
}/* end function */


/* 
 * function updates current version if needed. Adds and flushes rules if there is a version change
 * takes: 
 * returns: 
 */
function cdp_version_check_and_rule_flush(){
		$plugin_version = CDP_PLUGIN_VERSION;
		$version = get_option( 'cdp_version_settings');
		//if versions don't match or
		//no previous version recorded
		if ($version != $plugin_version || $version == false){
			$plugin_version = sanitize_text_field($plugin_version);
			update_option( 'cdp_version_settings', $plugin_version );	
			//echo "updated versions to $plugin_version ";
			cdp_report_version();
			cdp_add_rules();
			flush_rewrite_rules(); //expensive action... thus only on activation and here
		} else {
			//	echo 'check';
			if (CDP_USE_GILL) { gill_check_update(); }   //check for update
		}
}/* end function */ 

/* 
 * function loads necessary admin scripts
 * takes: 
 * returns: 
 */
function cdp_load_admin_scripts(){
	
	if (!is_admin()) { return FALSE; }
	//echo 'loading admin scripts';
	
	$load_scripts = FALSE;
	$load_scripts_color = FALSE;
	
	if ( (isset( $_GET['page']) && 
			(
				$_GET['page'] == 'cdp_plugin_options' ||
				$_GET['page'] == 'cdp_plugin_options_guide' ||
				$_GET['page'] == 'cdp_plugin_options_banner' 
			) ) 
			|| ( strstr($_SERVER["REQUEST_URI"], 'post.php') ) 
			|| ( strstr($_SERVER["REQUEST_URI"], 'post-new.php') ) 	
		){

		$load_scripts = TRUE;
	}
	
	if (isset( $_GET['page']) && ($_GET['page'] == 'cdp_plugin_options_guide' || $_GET['page'] == 'cdp_plugin_options_banner') ){
		$load_scripts_color = TRUE;
	}
	
	// load scripts for admin 
	// if plugin options page OR post edit page
	if ($load_scripts){

		wp_enqueue_style(CDP_PLUGIN_NAME.'_admin', CDP_PLUGIN_URL.'/'. CDP_STYLE_FILE_ADMIN, '', CDP_PLUGIN_VERSION, 'all');
		wp_enqueue_script(CDP_PLUGIN_NAME,  CDP_PLUGIN_URL.'/'. CDP_JS_FUNCTIONS_ADMIN);
	}
	
	if ($load_scripts_color){
		//load javascript color picker - add pages as needed - from http://jscolor.com
		if (is_admin()){ /*isset( $_GET['page']) && $_GET['cdp_tab']=='cdp_theme_settings' && */
			wp_enqueue_script('jscolor',CDP_PLUGIN_URL.'/'. 'js/jscolor/jscolor.js');
		}
	}//if cdp plugin options
	
	
	if ($load_scripts){
		//load jquery UI for use on this page - add pages as needed
		if ( is_admin()){ /*&& $_GET['cdp_tab']=='cdp_article_settings'*/ //isset( $_GET['cdp_tab']) &&
			//load latest jQuery
			if( wp_script_is( 'jquery', 'done' ) || wp_script_is( 'jquery', 'registered' )) {
				//do nothing
			} else {
				//load JQuery			
				wp_deregister_script('jquery');
				wp_register_script('jquery', CDP_JQUERY_FILE);
				wp_enqueue_script('jquery');
				
			}
			//load latest jQuery-UI
			if( wp_script_is( 'jquery-ui', 'done' ) || wp_script_is( 'jquery-ui', 'registered' )) {
				//do nothing
			} else {
				//load JQuery-UI			
				wp_deregister_script('jquery-ui');
				wp_enqueue_script('jquery-ui',CDP_JQUERY_UI_FILE);
				wp_enqueue_style(CDP_PLUGIN_NAME,  CDP_JQUERY_UI_CSS_FILE);	// plugin_dir_url( __FILE__ ) .
			}
			
		}//if
	}//if
	
	
}/* end function */ 

/* 
 * function that updates CDP version on file at buzzea
 * takes: 
 * returns: true / false
 */
function cdp_report_version(){
	
	$plugin_version = CDP_PLUGIN_VERSION;
	$password = md5(cdp_get_password());
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//send version details to Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'version/update', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_version' => $plugin_version,),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$update_version_results = $post_result['body'];
	if ($update_version_results == FALSE ){
	return FALSE;
	}//if
	else {
		return TRUE;
	}
		
} /* end function */

/* 
 * function gets the current page / url
 * takes: 
 * returns: URL
 */
function cdp_current_page(){
	
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		return esc_url($cdp_site_url.$_SERVER['REQUEST_URI']);
}/* end function */ 



/* 
 * function that adds CDP site on file to buzzea / updates
 * takes: 
 * returns: true / false
 */
function cdp_update($password, $newurl=''){
	
	$password = md5($password);
	
	$cdp_site_url = site_url();
	
	if ($newurl != ''){
		$cdp_site_url = $newurl;
	}

	//request subscription details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'cdp/add', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_install_method_id' => 1),
										'cookies' => array()
										)
								);	
	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$add_results = $post_result['body'];
	if ($add_results == TRUE ){
		return TRUE;
	}//if
	else {
		return FALSE; 
	}
} /* end function */



/* 
 * function that compares latest CDP page with shortcode and the one on file
 * cdp_update called if they are different 
 * takes: 
 * returns: true / false
 */
function cdp_check_and_update_location(){
	
	$install_details = cdp_get_install_details();	
	//vd($install_details);
	if (is_array($install_details)){
		$old_cdp_url_on_file = esc_url($install_details['url']); 
		
		//find comparateur URLS for this blog
		
		$cdp_permalink = cdp_location();
		
		if ($cdp_permalink != ''){
			
			if ($cdp_permalink != $old_cdp_url_on_file){
				
				$password 	= md5(cdp_get_password());
				$cdp_update = cdp_update($password, $cdp_permalink);
			}else {
				return FALSE;
			}
			
		}//if 
		else {
			return FALSE;
		}
		
		
	}//if
	else { 
		
		//no install details - probably due to change of URL/shortcode location
		//send over new location
		$cdp_permalink = cdp_location();
		
		if ($cdp_permalink == FALSE){ $cdp_permalink = site_url(); }
		
		//send over new location
		if ($cdp_permalink != ''){
			$password 	= md5(cdp_get_password());
			$cdp_update = cdp_update($password, $cdp_permalink);			
		}//if 
	
	
		return FALSE;		
	}//else
				
}
/* 
 * function that checks if this CDP site is on file at buzzea
 * takes: 
 * returns: true / false
 */
function cdp_check_subscription(){
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//request subscription details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'subscription/check', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$subscription_check_results = $post_result['body'];
	if ($subscription_check_results == FALSE ){
	return FALSE;
	}//if
	else {
		return (int) $post_result['body']; //id
	}
		
} /* end function */

/* 
 * function that checks if this CDP site is on file at buzzea AND they have provided the correct password
 * takes: 
 * returns: true / false
 */
function cdp_check_subscriptionValid($password){
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	$password = md5($password);
	//request subscription details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'subscriptionvalid/check', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' =>$cdp_site_url, 'cdp_password' => $password),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$subscription_check_results = $post_result['body'];
	if ($subscription_check_results == FALSE ){
		return FALSE;
	}//if
	else {
		return $post_result['body']; //id
	}
} /* end function */

/* 
 * function that checks if this CDP site is on file at buzzea
 * takes: 
 * returns: true / false
 */
function cdp_check_cdp_exists(){
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//request subscription details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'exists/check', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$cdp_exists_check_results = $post_result['body'];
	if ($cdp_exists_check_results == FALSE ){
		return FALSE;
	}//if
	else {
		return TRUE;
	}
} /* end function */

/* 
 * function that gets install details for this CDP
 * takes: password
 * returns: array with $install_details['url'], $install_details['method']
 */
function cdp_get_install_details($password=NULL){

	if ($password == NULL){
		//echo cdp_get_password();
		$password = md5(cdp_get_password());
		//echo $password;
	} else {
		$password = md5($password);
	}
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	//vd($cdp_site_url);
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	//request installation details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'settings-install/get', 
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
	if (($post_result['body'])== ''){
	//try again with smaller url
	//request installation details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'settings-install/get', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => site_url(), 'cdp_password' => $password ),
										'cookies' => array()
										)
								);	
	}
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$install_details_results = @unserialize($post_result['body']);
	$install_details = array();
	if (is_array($install_details_results)){
		//vd($install_details_results);
		if (array_key_exists('domaineid', $install_details_results)) { 
			$install_details['domaineid'] = intval($install_details_results['domaineid']); 
			
			//need domaineid as a WP option
			$existing_domaineid = get_option('cdp_domaineid');
			
			//insert if dont have one
			if (!is_numeric($existing_domaineid)){
				add_option('cdp_domaineid',$install_details['domaineid'] );
			}
			
		}//if array_key_exists
		
		// blog id
		
		if (array_key_exists('blogid', $install_details_results)) { 
			$install_details['blogid'] = intval($install_details_results['blogid']); 
			
			//need domaineid as a WP option
			$existing_blogid = get_option('cdp_blogid');
			
			//insert if dont have one
			if (!is_numeric($existing_blogid)){
				add_option('cdp_blogid',$install_details['blogid'] );
			}
			
		}//if array_key_exists
		
		if (array_key_exists('domaine_url', $install_details_results)) { 
			$install_details['url'] = esc_url($install_details_results['domaine_url']); 
		}//if array_key_exists
		
		if (array_key_exists('methode_installation_libelle', $install_details_results)) {
			$install_details['method'] = sanitize_text_field($install_details_results['methode_installation_libelle']); 
		}//if array_key_exists
	return $install_details;
	
	}//if
	else {
		//fail
	}
} /* end function */

/* 
 * function that gets blog details for this CDP
 * takes: 
 * returns: array with blogid, blog_password
 */
function cdp_get_blog_details(){

	//do we have a placed CDP via shortcode?
	$cdp_site_url = site_url();
	$cdp_site_url = str_ireplace('http://', '', $cdp_site_url);
	$cdp_site_url = str_ireplace('https://', '', $cdp_site_url);
	
	//request installation details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'settings-blog/get', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url),
										'cookies' => array()
										)
								);	
								
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$results = @unserialize($post_result['body']);
	$blog_details = array();
	if (is_array($results)){
		
		if (array_key_exists('blogid', $results)) { 
			$blog_details['blogid'] = intval($results['blogid']); 
			
			/*//need blogid as a WP option
			$existing_blogid = get_option('cdp_blogid');
			
			//insert if dont have one
			if (!is_numeric($existing_blogid)){
				add_option('cdp_blogid',$blog_details['blogid'] );
			}*/
			
		}//if array_key_exists
		
		if (array_key_exists('blog_password', $results)) { 
			$blog_details['blog_password'] = $results['blog_password']; 
			
			/*//need blogid as a WP option
			$existing_blog_password = get_option('cdp_password');
			
			//insert if dont have one
			if ($existing_blog_password == '' || $existing_blog_password == FALSE){
				add_option('blog_password',$blog_details['cdp_password'] );
			}*/
			
		}//if array_key_exists
		
	return $blog_details;
	
	}//if
	else {
		//fail
		
		return FALSE;
	}
} /* end function */

/* 
 * function that checks the general options submitted 
 * takes: input array of options
 * returns: options sanitized ready to save / FALSE
 *			adds sucess / fail messages 
 */
function cdp_validate_general_options ( $input ) {
	
	//check the nonce to see should we continue
	if (wp_verify_nonce( $_POST['_wpnonce'], 'cdp_general_settings-options' ) && ($_POST['action'] == 'update')){
		
		$valid_options = array();
		
		//put the clean options into array to send for update
		if (array_key_exists('cdp_general_settings', $_POST)){
			// password
			if (array_key_exists('general_option_password', $_POST['cdp_general_settings'])){
				$dirty_password = $_POST['cdp_general_settings']['general_option_password'];
				$clean_password = sanitize_text_field(filter_var($dirty_password, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[a-zA-Z0-9]+$/"))));
				
				if ($clean_password!=FALSE && strlen($clean_password)<11){
					
					$valid_options['general_option_password'] = strip_tags(sanitize_text_field($_POST['cdp_general_settings']['general_option_password']));
					
				}//if
			}//if
		
			// menu option
			if (array_key_exists('general_option_menu_choice', $_POST['cdp_general_settings'])){
				$dirty_menu_option = $_POST['cdp_general_settings']['general_option_menu_choice'];
				
				$clean_menu_option = filter_var($dirty_menu_option, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[0-1]{1}$/")));
				if ($clean_menu_option!==FALSE){
					$valid_options['general_option_menu_choice'] = strip_tags(sanitize_text_field($_POST['cdp_general_settings']['general_option_menu_choice']));
				}//if
			}//if
		}//if 

		if (count($valid_options)>0){
			
			// check install status
			$general_options = get_option('cdp_general_settings');
			// if password in wordpress db and not blank
			if (isset($general_options['general_option_password']) && $general_options['general_option_password'] !=''){
				$installedpasscheck = TRUE;
				$valid_options['general_option_password'] = $general_options['general_option_password']; // to return and avoid overwrite
			}else{
				$installedpasscheck = FALSE;	
			}
			
			// check password present
			if ($installedpasscheck === FALSE || $installedpasscheck === ''){
				//	NOT fully installed
			  	//check if password ok 
			  	$valid_site = cdp_check_subscriptionValid($valid_options['general_option_password']);
				//vd($valid_site);die();
				if ($valid_site == TRUE){
					//$cdp_add = cdp_add($valid_options['general_option_password']);
					//success
					$msg_general = __("Vos options générales ont été enregistrées dans WordPress.", CDP_PLUGIN_NAME );
					add_settings_error(
						'settings_updated', // setting title - not important here
						'settings_updated', // error ID - not important here
						"$msg_general", // error message
						'updated' // type of message
					);
					
					if ($cdp_add != TRUE){
						return FALSE; //problem
					}//if*/
					
				}//if
				else {
					$cdp_buzzea_tel = CDP_BUZZEA_TEL;
					$msg = __("Oups, il y a un problème avec le mot de passe.<br />
					Il correspond au mot de passe fourni sur votre interface Buzzea et est composé de chiffres et de lettres uniquement.<br />
					  Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au ", CDP_PLUGIN_NAME ).$cdp_buzzea_tel;
					add_settings_error(
						'general_option', // setting title - not important here
						'general_option_error', // error ID - not important here
						"$msg", // error message
						'error' // type of message
					);	
					
					return FALSE; 
				}
			}//if
			
			
			return $valid_options; //return them to save...
		
		} else {
			
			//no data, problem	
			// problem with the password
				$cdp_buzzea_tel = CDP_BUZZEA_TEL;
				$msg = __("Oups, il y a un problème avec le mot de passe.<br />
				Il correspond au mot de passe fourni sur votre interface Buzzea et est composé de chiffres et de lettres uniquement.<br />
				  Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au ", CDP_PLUGIN_NAME ).$cdp_buzzea_tel;
				add_settings_error(
					'general_option', // setting title - not important here
					'general_option_error', // error ID - not important here
					"$msg", // error message
					'error' // type of message
				);	
				
				return FALSE;
		} //else
		
	} else {
			
			// problem with the nonce
			$msg = __("Action incorrecte - nous ne pouvons pas enregistrer vos options", CDP_PLUGIN_NAME );
			add_settings_error(
				'category_option', // setting title - not important here
				'category_option_error', // error ID - not important here
				"$msg", // error message
				'error' // type of message
			);	
	}//else
		
}/* function */

/* 
 * function that sends personalisation details to buzzea for update
 * takes: details array of options
 * returns: TRUE
 *			adds sucess / fail messages 
 */
function cdp_transmit_personal_settings($details_array) {
		
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
	$details['wordpress'] = TRUE;
	//pr($details);
	//request installation details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'settings/save', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_personalisation_details' => $details  ),
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
			$msg = __("Oups, il y a un problème avec la mise à jour. Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au ", CDP_PLUGIN_NAME ).$cdp_buzzea_tel;
			add_settings_error(
				'personal_option', // setting title - not important here
				'personal_option_error', // error ID - not important here
				"$msg", // error message
				'error' // type of message
			);
		}//else
		
} /* function */

/* 
 * function that sends behaviour details to buzzea for update
 * takes: details array of options
 * returns: TRUE
 *			adds sucess / fail messages 
 */
function cdp_transmit_behaviour_settings($details_array) {
		
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//send to buzzea
	//get password from WP options	
	$password = md5(cdp_get_password());
	$details = array();
	$details = $details_array;
	
	//request installation details from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'settings/save', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_personalisation_details' => $details  ),
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
		}//else
		
} /* function */



/* 
 * function that sends article details to buzzea for update
 * takes: details array of options
 * returns: TRUE
 *			adds sucess / fail messages 
 */
function cdp_transmit_article_settings($details_array) {
		
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
	$post_result = wp_remote_post(CDP_API_URL.'settings-article-options/save', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_article_options' => $details  ),
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
			$msg = __("Oups, il y a un problème avec la mise à jour. Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au ", CDP_PLUGIN_NAME ).$cdp_buzzea_tel;
			add_settings_error(
				'personal_option', // setting title - not important here
				'personal_option_error', // error ID - not important here
				"$msg", // error message
				'error' // type of message
			);
		}//else
		
		
		return TRUE;
} /* function */


/* 
 * function that validates personalisation details before sending to buzzea for update
 * takes: input array of options
 * returns: input (sanitized)
 *			adds sucess / fail messages 
 */
function cdp_validate_personal_options( $input ) {
	
	//vd ($input); die();
	//check the nonce to see should we continue
	if (wp_verify_nonce( $_POST['_wpnonce'], 'cdp_personal_settings-options' ) && ($_POST['action'] == 'update')){
			
		$valid = array();
		$error = FALSE;
		$msg = '';
		
		$filter_pattern 		= "/^[-+\p{L}\p{N}0-9_,\"\'&@\*!\?:\. ]+$/";
		$filter_pattern_numbers = "/^[0-9]{1,3}$/";
		$filter_pattern_boolean	= "/^[0-1]{1}$/";
	
		$valid['version'] = CDP_PLUGIN_VERSION;
		
		//=============== Libelle Blog
		
		//The following pattern will match strings that contain only letters, digits, '+' or '-', including international characters such as 'å' or 'ö'
		if ((preg_match($filter_pattern, $input['personal_option_libelle'])>0) && (strlen($input['personal_option_libelle']) <= 100) ){ //^[a-zA-Z0-9_ ]*
			$valid['personal_option_libelle'] = sanitize_text_field($input['personal_option_libelle']);
		} else { $valid['personal_option_libelle'] = '';}
		
		//The following pattern will match strings that contain only letters, digits, '+' or '-', including international characters such as 'å' or 'ö'
		if ((preg_match($filter_pattern, $input['personal_option_libelle_comparateur'])>0) && (strlen($input['personal_option_libelle_comparateur']) <= 100) ){ //^[a-zA-Z0-9_ ]*
			$valid['personal_option_libelle_comparateur'] = sanitize_text_field($input['personal_option_libelle_comparateur']);
		} else { $valid['personal_option_libelle_comparateur'] = '';}
		
		// Something dirty entered? Warn user.
		if( $valid['personal_option_libelle'] != $input['personal_option_libelle'] ) {
			$msg .= __("*Libelle de votre site invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
			add_settings_error(
				'personal_option_libelle', // setting title
				'personal_option_libelle_error', // error ID
				"$msg", // error message
				'error' // type of message
			);	
			
			$error = TRUE;
		}//if
		
		//=============== Libelle Comparateur
		
		// Something dirty entered? Warn user.
		if( $valid['personal_option_libelle_comparateur'] != $input['personal_option_libelle_comparateur'] ) {
			$msg .= __("*Libelle de comparateur invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
			add_settings_error(
				'personal_option_libelle_comparateur', // setting title
				'personal_option_libelle_comparateur_error', // error ID
				"$msg", // error message
				'error' // type of message
			);	
			
			$error = TRUE;
		}//if
		
		//==========================	Transmit to Buzzea	
		if ($error == FALSE) {
			//send the details	
			$update = cdp_transmit_personal_settings($valid);
			return $valid; //return data to save in WP db
		} else {
			$msg = __("Mise à jour invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />'.$msg;
			add_settings_error(
				'personal_option', // setting title
				'personal_option_error', // error ID
				"$msg", // error message
				'error' // type of message
			);
			
			//prepare previous inputs to return in GET and re-populate form
			$inputs = '';
			foreach ($input as $key => $val){
					$inputs.="&$key=".urlencode($val);
			}
			//this method here is needed as there is no way to return error data
			$admin_filename = cdp_get_admin_filename();
			$wp_redir =  admin_url().$admin_filename."?page=cdp_plugin_options&cdp_tab=cdp_personal_settings";

			wp_redirect( $wp_redir . $inputs . '&cdp_personal_settings_msg='.urlencode(($msg)) );
    		exit(); 
		}
		
	} else {
			    
		// problem with the nonce
		$msg = __("Action incorrecte - nous ne pouvons pas enregistrer vos catégories", CDP_PLUGIN_NAME );
		add_settings_error(
			'personal_option', // setting title - not important here
			'personal_option_error', // error ID - not important here
			"$msg", // error message
			'error' // type of message
		);	
	}//else
	
}/* end function */


/* 
 * function that validates behaviour details before sending to buzzea for update
 * takes: input array of options
 * returns: input (sanitized)
 *			adds sucess / fail messages 
 */
function cdp_validate_behaviour_options( $input ) {
	
	//vd ($_POST); die();
	//check the nonce to see should we continue
	if (wp_verify_nonce( $_POST['_wpnonce'], 'cdp_behaviour_settings-options' ) && ($_POST['action'] == 'update')){
			
		$valid = array();
		$error = FALSE;
		$msg = '';
		
		$filter_pattern 		= "/^[-+\p{L}\p{N}0-9_,\"\'&@\*!\?:\. ]+$/";
		$filter_pattern_numbers = "/^[0-9]{1,3}$/";
		$filter_pattern_boolean	= "/^[0-1]{1}$/";
	
		$valid['version'] = CDP_PLUGIN_VERSION;
		
		//=============== Coup de coeur
		
		//The following pattern will match boolean
		if ((preg_match($filter_pattern_boolean, $input['behaviour_option_cdc_choice']) >0) && $input['behaviour_option_cdc_choice'] >= 0 ){ 
			$valid['behaviour_option_cdc_choice'] = sanitize_text_field($input['behaviour_option_cdc_choice']);
		} else { $valid['behaviour_option_cdc_choice'] = '';}
		
		// Something dirty entered? Warn user.
		if( $valid['behaviour_option_cdc_choice'] != $input['behaviour_option_cdc_choice'] ) {
			$msg .= __("*Choix d'articles en selection invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
			add_settings_error(
				'behaviour_option_cdc_choice', // setting title
				'behaviour_option_cdc_choice_error', // error ID
				"$msg", // error message
				'error' // type of message
			);	
			
			$error = TRUE;
		}//if
		
		//The following pattern will match boolean
		if ((preg_match($filter_pattern_boolean, $input['behaviour_option_cdc_only_choice']) >0) && $input['behaviour_option_cdc_only_choice'] >= 0 ){ 
			$valid['behaviour_option_cdc_only_choice'] = sanitize_text_field($input['behaviour_option_cdc_only_choice']);
		} else { $valid['behaviour_option_cdc_only_choice'] = '';}
		
		// Something dirty entered? Warn user.
		if( $valid['behaviour_option_cdc_only_choice'] != $input['behaviour_option_cdc_only_choice'] ) {
			$msg .= __("*Choix de seulement articles en selection invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
			add_settings_error(
				'behaviour_option_cdc_only_choice', // setting title
				'behaviour_option_cdc_only_choice_error', // error ID
				"$msg", // error message
				'error' // type of message
			);	
			
			$error = TRUE;
		}//if
		
		//=============== Search options
		
		//The following pattern will match boolean
		if ((preg_match($filter_pattern_boolean, $input['behaviour_option_recherche_choice']) >0) && $input['behaviour_option_recherche_choice'] >= 0 ){ 
			$valid['behaviour_option_recherche_choice'] = sanitize_text_field($input['behaviour_option_recherche_choice']);
		} else { $valid['behaviour_option_recherche_choice'] = '';}
		
		// Something dirty entered? Warn user.
		if( $valid['behaviour_option_cdc_choice'] != $input['behaviour_option_cdc_choice'] ) {
			$msg .= __("*Choix d'articles en selection invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
			add_settings_error(
				'behaviour_option_cdc_choice', // setting title
				'behaviour_option_cdc_choice_error', // error ID
				"$msg", // error message
				'error' // type of message
			);	
			
			$error = TRUE;
		}//if
	
		//==========================	Transmit to Buzzea	
		if ($error == FALSE) {
			//send the details	
			$update = cdp_transmit_behaviour_settings($valid);
			return $valid; //return data to save in WP db
		} else {
			$msg = __("Mise à jour invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />'.$msg;
			add_settings_error(
				'behaviour_option', // setting title
				'behaviour_option_error', // error ID
				"$msg", // error message
				'error' // type of message
			);
			
			//prepare previous inputs to return in GET and re-populate form
			$inputs = '';
			foreach ($input as $key => $val){
					$inputs.="&$key=".urlencode($val);
			}
			//this method here is needed as there is no way to return error data
			$admin_filename = cdp_get_admin_filename();
			$wp_redir =  admin_url().$admin_filename."?page=cdp_plugin_options&cdp_tab=cdp_behaviour_settings";

		
		
			wp_redirect( $wp_redir . $inputs . '&cdp_behaviour_settings_msg='.urlencode(($msg)) );
    		exit(); 
		}
		
	} else {
			    
		// problem with the nonce
		$msg = __("Action incorrecte - nous ne pouvons pas enregistrer vos coordonées", CDP_PLUGIN_NAME );
		add_settings_error(
			'behaviour_option', // setting title - not important here
			'behaviour_option_error', // error ID - not important here
			"$msg", // error message
			'error' // type of message
		);	
	}//else
	
}/* end function */

/* 
 * function that returns correct admin url/filename to use
 * takes: 
 * returns: admin url (string)
 *			
 */
function cdp_get_admin_filename() {
	$general_options = get_option('cdp_general_settings');
	
	// big menu item
	if (isset($general_options['general_option_menu_choice']) && $general_options['general_option_menu_choice'] == '1'){
		//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		return 'admin.php';
	}else{
		// sub menu item instead
		return 'options-general.php';
	}

}

/* 
 * function that validates personalisation details before sending to buzzea for update
 * takes: input array of options
 * returns: input (sanitized)
 *			adds sucess / fail messages 
 */
function cdp_validate_theme_options( $input ) {
	
	//pr ($input); die();
	//check the nonce to see should we continue
	if (wp_verify_nonce( $_POST['_wpnonce'], 'cdp_theme_settings-options' ) && ($_POST['action'] == 'update')){
			
		$valid = array();
		$error = FALSE;
		$msg = '';
		
		$filter_pattern 		= "/^[-+\p{L}\p{N}0-9_,\"\'&@\*!\?:\. ]+$/";
		$filter_pattern_numbers = "/^[0-9]{1,3}$/";
		$filter_pattern_boolean	= "/^[0-1]{1}$/";
	
		$valid['version'] = CDP_PLUGIN_VERSION;
		
		//======================= Theme Choice
		
		//The following pattern will match numbers
		if ((preg_match($filter_pattern_numbers, $input['theme_option_graphic']) >0) && $input['theme_option_graphic'] > 0 ){ 
			$valid['theme_option_graphic'] = sanitize_text_field($input['theme_option_graphic']);
		} else { $valid['theme_option_graphic'] = '';}
		
		// Something dirty entered? Warn user.
		if( $valid['theme_option_graphic'] != $input['theme_option_graphic'] ) {
			$msg .= __("*Graphic option invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
			add_settings_error(
				'theme_option_graphic', // setting title
				'theme_option_graphic_error', // error ID
				"$msg", // error message
				'error' // type of message
			);	
			
			$error = TRUE;
		}//if
		
		//==========================	items per row
	
		if (isset($input['theme1_personal_option_items_per_page'])){
			//The following pattern will match numbers
			if ((preg_match($filter_pattern_numbers, $input['theme1_personal_option_items_per_page']) >0) && $input['theme1_personal_option_items_per_page'] > 0 ){ 
				$valid['theme1_personal_option_items_per_page'] = sanitize_text_field($input['theme1_personal_option_items_per_page']);
			} else { $valid['theme1_personal_option_items_per_page'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_items_per_page'] != $input['theme1_personal_option_items_per_page'] ) {
				$msg .= __("*Nombre d'articles par page invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				add_settings_error(
					'personal_option_items_per_page', // setting title
					'personal_option_items_per_page_error', // error ID
					"$msg", // error message
					'error' // type of message
				);	
				
				$error = TRUE;
				
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_items_per_page'])){
			//The following pattern will match numbers
			if ((preg_match($filter_pattern_numbers, $input['theme2_personal_option_items_per_page']) >0) && $input['theme2_personal_option_items_per_page'] > 0 ){ 
				$valid['theme2_personal_option_items_per_page'] = sanitize_text_field($input['theme2_personal_option_items_per_page']);
			} else { $valid['theme2_personal_option_items_per_page'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_items_per_page'] != $input['theme2_personal_option_items_per_page'] ) {
				$msg .= __("*Nombre d'articles par page invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				add_settings_error(
					'personal_option_items_per_page', // setting title
					'personal_option_items_per_page_error', // error ID
					"$msg", // error message
					'error' // type of message
				);	
				
				$error = TRUE;
			}//if
		}//if
		
		//============================	Items per page
		
		if (isset($input['theme1_personal_option_items_per_row'])){
			//The following pattern will match numbers
			if ((preg_match($filter_pattern_numbers, $input['theme1_personal_option_items_per_row']) >0) && $input['theme1_personal_option_items_per_row'] > 0 ){ 
				$valid['theme1_personal_option_items_per_row'] = sanitize_text_field($input['theme1_personal_option_items_per_row']);
			} else { $valid['theme1_personal_option_items_per_row'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_items_per_row'] != $input['theme1_personal_option_items_per_row'] ) {
				$msg .= __("*Nombre d'articles par ligne invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				add_settings_error(
					'personal_option_items_per_row', // setting title
					'personal_option_items_per_row_error', // error ID
					"$msg", // error message
					'error' // type of message
				);	
				
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_items_per_row'])){
			//The following pattern will match numbers
			if ((preg_match($filter_pattern_numbers, $input['theme2_personal_option_items_per_row']) >0) && $input['theme2_personal_option_items_per_row'] > 0 ){ 
				$valid['theme2_personal_option_items_per_row'] = sanitize_text_field($input['theme2_personal_option_items_per_row']);
			} else { $valid['theme2_personal_option_items_per_row'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_items_per_row'] != $input['theme2_personal_option_items_per_row'] ) {
				$msg .= __("*Nombre d'articles par ligne invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				add_settings_error(
					'personal_option_items_per_row', // setting title
					'personal_option_items_per_row_error', // error ID
					"$msg", // error message
					'error' // type of message
				);	
				
				$error = TRUE;
			}//if
		}//if
		
		//============================	CAts per page
		
		if (isset($input['theme1_personal_option_cats_per_row'])){
			//The following pattern will match numbers
			if ((preg_match($filter_pattern_numbers, $input['theme1_personal_option_cats_per_row']) >0) && $input['theme1_personal_option_cats_per_row'] > 0 ){ 
				$valid['theme1_personal_option_cats_per_row'] = sanitize_text_field($input['theme1_personal_option_cats_per_row']);
			} else { $valid['theme1_personal_option_cats_per_row'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_cats_per_row'] != $input['theme1_personal_option_cats_per_row'] ) {
				$msg .= __("*Nombre de catégories par ligne invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_cats_per_row'])){
			//The following pattern will match numbers
			if ((preg_match($filter_pattern_numbers, $input['theme2_personal_option_cats_per_row']) >0) && $input['theme2_personal_option_cats_per_row'] > 0 ){ 
				$valid['theme2_personal_option_cats_per_row'] = sanitize_text_field($input['theme2_personal_option_cats_per_row']);
			} else { $valid['theme2_personal_option_cats_per_row'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_cats_per_row'] != $input['theme2_personal_option_cats_per_row'] ) {
				$msg .= __("*Nombre de catégories par ligne invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		
	
	//==========================	couleurs
		
		//==========================	theme1
		
		//The following pattern will match FF0000 etc for hex colors
		if (isset($input['theme1_personal_option_color_text'])){
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_text'])>0)){
				$valid['theme1_personal_option_color_text'] =sanitize_text_field($input['theme1_personal_option_color_text']);
			} else { $valid['theme1_personal_option_color_text'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_text'] != $input['theme1_personal_option_color_text'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_text2'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_text2'])>0)){
				$valid['theme1_personal_option_color_text2'] =sanitize_text_field($input['theme1_personal_option_color_text2']);
			} else { $valid['theme1_personal_option_color_text2'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_text2'] != $input['theme1_personal_option_color_text2'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_text3'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_text3'])>0)){
				$valid['theme1_personal_option_color_text3'] =sanitize_text_field($input['theme1_personal_option_color_text3']);
			} else { $valid['theme1_personal_option_color_text3'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_text3'] != $input['theme1_personal_option_color_text3'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_text4'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_text4'])>0)){
				$valid['theme1_personal_option_color_text4'] =sanitize_text_field($input['theme1_personal_option_color_text4']);
			} else { $valid['theme1_personal_option_color_text4'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_text4'] != $input['theme1_personal_option_color_text4'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_links'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_links'])>0)){
				$valid['theme1_personal_option_color_links'] = sanitize_text_field($input['theme1_personal_option_color_links']);
			} else { $valid['theme1_personal_option_color_links'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_links'] != $input['theme1_personal_option_color_links'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_links2'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_links2'])>0)){
				$valid['theme1_personal_option_color_links2'] = sanitize_text_field($input['theme1_personal_option_color_links2']);
			} else { $valid['theme1_personal_option_color_links2'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_links2'] != $input['theme1_personal_option_color_links2'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_links3'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_links3'])>0)){
				$valid['theme1_personal_option_color_links3'] = sanitize_text_field($input['theme1_personal_option_color_links3']);
			} else { $valid['theme1_personal_option_color_links3'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_links3'] != $input['theme1_personal_option_color_links3'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_links4'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_links4'])>0)){
				$valid['theme1_personal_option_color_links4'] = sanitize_text_field($input['theme1_personal_option_color_links4']);
			} else { $valid['theme1_personal_option_color_links4'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_links4'] != $input['theme1_personal_option_color_links4'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_theme'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_theme'])>0)){
				$valid['theme1_personal_option_color_theme'] = sanitize_text_field($input['theme1_personal_option_color_theme']);
			} else { $valid['theme1_personal_option_color_theme'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_theme'] != $input['theme1_personal_option_color_theme'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_background'])){
			//The following pattern will match FF0000 etc for hex colors
			if (preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_background'])>0){
				$valid['theme1_personal_option_color_background'] = sanitize_text_field($input['theme1_personal_option_color_background']);
			} else { $valid['theme1_personal_option_color_background'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_background'] != $input['theme1_personal_option_color_background'] ) {
				$msg .= __("*Couleur non valide pour le fond, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_theme2'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_theme2'])>0)){
				$valid['theme1_personal_option_color_theme2'] = sanitize_text_field($input['theme1_personal_option_color_theme2']);
			} else { $valid['theme1_personal_option_color_theme2'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_theme2'] != $input['theme1_personal_option_color_theme2'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_theme3'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_theme3'])>0)){
				$valid['theme1_personal_option_color_theme3'] = sanitize_text_field($input['theme1_personal_option_color_theme3']);
			} else { $valid['theme1_personal_option_color_theme3'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_theme3'] != $input['theme1_personal_option_color_theme3'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme1_personal_option_color_theme4'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme1_personal_option_color_theme4'])>0)){
				$valid['theme1_personal_option_color_theme4'] = sanitize_text_field($input['theme1_personal_option_color_theme4']);
			} else { $valid['theme1_personal_option_color_theme4'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme1_personal_option_color_theme4'] != $input['theme1_personal_option_color_theme4'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		
		//==========================	theme2
		
		//The following pattern will match FF0000 etc for hex colors
		if (isset($input['theme2_personal_option_color_text'])){
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_text'])>0)){
				$valid['theme2_personal_option_color_text'] =sanitize_text_field($input['theme2_personal_option_color_text']);
			} else { $valid['theme2_personal_option_color_text'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_text'] != $input['theme2_personal_option_color_text'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_text2'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_text2'])>0)){
				$valid['theme2_personal_option_color_text2'] =sanitize_text_field($input['theme2_personal_option_color_text2']);
			} else { $valid['theme2_personal_option_color_text2'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_text2'] != $input['theme2_personal_option_color_text2'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_text3'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_text3'])>0)){
				$valid['theme2_personal_option_color_text3'] =sanitize_text_field($input['theme2_personal_option_color_text3']);
			} else { $valid['theme2_personal_option_color_text3'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_text3'] != $input['theme2_personal_option_color_text3'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_text4'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_text4'])>0)){
				$valid['theme2_personal_option_color_text4'] =sanitize_text_field($input['theme2_personal_option_color_text4']);
			} else { $valid['theme2_personal_option_color_text4'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_text4'] != $input['theme2_personal_option_color_text4'] ) {
				$msg .= __("*Couleur non valide pour le texte, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_links'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_links'])>0)){
				$valid['theme2_personal_option_color_links'] = sanitize_text_field($input['theme2_personal_option_color_links']);
			} else { $valid['theme2_personal_option_color_links'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_links'] != $input['theme2_personal_option_color_links'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_links2'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_links2'])>0)){
				$valid['theme2_personal_option_color_links2'] = sanitize_text_field($input['theme2_personal_option_color_links2']);
			} else { $valid['theme2_personal_option_color_links2'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_links2'] != $input['theme2_personal_option_color_links2'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_links3'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_links3'])>0)){
				$valid['theme2_personal_option_color_links3'] = sanitize_text_field($input['theme2_personal_option_color_links3']);
			} else { $valid['theme2_personal_option_color_links3'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_links3'] != $input['theme2_personal_option_color_links3'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_links4'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_links4'])>0)){
				$valid['theme2_personal_option_color_links4'] = sanitize_text_field($input['theme2_personal_option_color_links4']);
			} else { $valid['theme2_personal_option_color_links4'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_links4'] != $input['theme2_personal_option_color_links4'] ) {
				$msg .= __("*Couleur non valide pour les liens, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_theme'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_theme'])>0)){
				$valid['theme2_personal_option_color_theme'] = sanitize_text_field($input['theme2_personal_option_color_theme']);
			} else { $valid['theme2_personal_option_color_theme'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_theme'] != $input['theme2_personal_option_color_theme'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_background'])){
			//The following pattern will match FF0000 etc for hex colors
			if (preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_background'])>0){
				$valid['theme2_personal_option_color_background'] = sanitize_text_field($input['theme2_personal_option_color_background']);
			} else { $valid['theme2_personal_option_color_background'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_background'] != $input['theme2_personal_option_color_background'] ) {
				$msg .= __("*Couleur non valide pour le fond, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_theme2'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_theme2'])>0)){
				$valid['theme2_personal_option_color_theme2'] = sanitize_text_field($input['theme2_personal_option_color_theme2']);
			} else { $valid['theme2_personal_option_color_theme2'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_theme2'] != $input['theme2_personal_option_color_theme2'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_theme3'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_theme3'])>0)){
				$valid['theme2_personal_option_color_theme3'] = sanitize_text_field($input['theme2_personal_option_color_theme3']);
			} else { $valid['theme2_personal_option_color_theme3'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_theme3'] != $input['theme2_personal_option_color_theme3'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		if (isset($input['theme2_personal_option_color_theme4'])){
			//The following pattern will match FF0000 etc for hex colors
			if ((preg_match("/^(?:[0-9a-fA-F]{3}){1,2}$/", $input['theme2_personal_option_color_theme4'])>0)){
				$valid['theme2_personal_option_color_theme4'] = sanitize_text_field($input['theme2_personal_option_color_theme4']);
			} else { $valid['theme2_personal_option_color_theme4'] = '';}
			
			// Something dirty entered? Warn user.
			if( $valid['theme2_personal_option_color_theme4'] != $input['theme2_personal_option_color_theme4'] ) {
				$msg .= __("*Couleur non valide pour le thème, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
				$error = TRUE;
			}//if
		}//if
		
		//===================================
		if ($error == FALSE) {
			//send the details	
			$update = cdp_transmit_personal_settings($valid);
			return $valid; //return data to save in WP db
		} else {
			$msg = __("Mise à jour invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />'.$msg;
			add_settings_error(
				'personal_option', // setting title
				'personal_option_error', // error ID
				"$msg", // error message
				'error' // type of message
			);
			
			//prepare previous inputs to return in GET and re-populate form
			$inputs = '';
			foreach ($input as $key => $val){
					$inputs.="&$key=".urlencode($val);
			}
			//this method here is needed as there is no way to return error data
			$admin_filename = cdp_get_admin_filename();
			$wp_redir =  admin_url().$admin_filename."?page=cdp_plugin_options&cdp_tab=cdp_theme_settings";
			wp_redirect( $wp_redir . $inputs . '&cdp_theme_settings_msg='.urlencode(($msg)) );
    		exit(); 
		}
		
	} else {
			    
		// problem with the nonce
		$msg = __("Action incorrecte - nous ne pouvons pas enregistrer vos catégories", CDP_PLUGIN_NAME );
		add_settings_error(
			'personal_option', // setting title - not important here
			'personal_option_error', // error ID - not important here
			"$msg", // error message
			'error' // type of message
		);	
	}//else
	
}/* end function */

/* 
 * function that validates article options before sending to buzzea for update
 * takes: input array of options
 * returns: input (sanitized)
 *			adds sucess / fail messages 
 */
function cdp_validate_article_options( $input ) {
	
	//vd ($input); die();
	//check the nonce to see should we continue
	if (wp_verify_nonce( $_POST['_wpnonce'], 'cdp_article_settings-options' ) && ($_POST['action'] == 'update')){
			
		$valid = array();
		$error = FALSE;
		$msg = '';
		
		$filter_pattern_numbers = "/^[0-9]{1,3}$/"; //0 - 999
		
		//The following pattern will match numbers
		if ((preg_match($filter_pattern_numbers, $input['article_option_autofiller']) >=0) && $input['article_option_autofiller'] >=0 ){ 
			$valid['article_option_autofiller'] = sanitize_text_field($input['article_option_autofiller']);
		} else { $valid['article_option_autofiller'] = '';}
		
		// Something dirty entered? Warn user.
		if( $valid['article_option_autofiller'] != $input['article_option_autofiller'] ) {
			$msg .= __("*Nombre d'articles pour auto selection invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />';
			add_settings_error(
				'article_option_autofiller', // setting title
				'article_option_autofiller_error', // error ID
				"$msg", // error message
				'error' // type of message
			);	
			
			$error = TRUE;
		}//if
		
		
		if ($error == FALSE) {
			//send the details	
			$update = cdp_transmit_article_settings($valid);
			return $valid; //return data to save in WP db
		} else {
			$msg = __("Mise à jour invalide, merci de corriger", CDP_PLUGIN_NAME ).'<br />'.$msg;
					
			$inputs = '';
			//this method here is needed as there is no way to return error data
			$admin_filename = cdp_get_admin_filename();
			$wp_redir =  admin_url().$admin_filename."?page=cdp_plugin_options&cdp_tab=cdp_article_settings";
			wp_redirect( $wp_redir . $inputs . '&cdp_article_settings_msg='.urlencode(($msg)) );
    		exit(); 
		}
		
	} else {
			    
		// problem with the nonce
		$msg = __("Action incorrecte - nous ne pouvons pas enregistrer vos options");
		add_settings_error(
			'article_option', // setting title - not important here
			'article_option_error', // error ID - not important here
			"$msg", // error message
			'error' // type of message
		);	
	}//else
	
}/* end function */



/* 
 * function that validates category choice details before sending to buzzea for update
 * takes: input array of options
 * returns: input (sanitized)
 *			adds sucess / fail messages 
 */
function cdp_validate_category_options( $input ) {
	//vd(wp_verify_nonce( $_POST['_wpnonce'], 'cdp_category_settings-options' ));
	
	//check the nonce to see should we continue
	if (wp_verify_nonce( $_POST['_wpnonce'], 'cdp_category_settings-options' ) && ($_POST['action'] == 'update')){
		
		$valid_cats = array();
		
		//put the all/selected choice into array to send for update
		if ($_POST["all_categories"] == 'only-selected' || $_POST["all_categories"] == 'all-categories'){
			$valid_cats['all_categories'] = sanitize_text_field($_POST["all_categories"]);
		}//if 

		//get the category id choices
		foreach ($_POST as $key => $val){
			//check all post content for categorie484 etc.
			if ((preg_match("/^categorie([0-9]+)$/", $key)>0 && $val == 'on' )){
				$catid = intval(str_replace('categorie','', $key));
				$valid_cats[] = $catid;
			}//if
		}//foreach
		
		if (count($valid_cats) > 0){
			//we have choices to update
			//echo 'transmitting';
			$update_categories = cdp_transmit_category_options($valid_cats);
			if ($update_categories == TRUE || count($valid_cats) == 1){
				//success
				$msg = __("Catégories enregistrées", CDP_PLUGIN_NAME );
				add_settings_error(
					'settings_updated', // setting title - not important here
					'settings_updated', // error ID - not important here
					"$msg", // error message
					'updated' // type of message
				);
				$msg ='';	
				return 1; //saved into WPDB to signify some categories are selected
			} else {
				// problem with the update
				$cdp_buzzea_tel = CDP_BUZZEA_TEL;
				$msg = __("Oups, il y a un problème avec la mise à jour. Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter au ", CDP_PLUGIN_NAME ).$cdp_buzzea_tel;
				add_settings_error(
					'category_option', // setting title - not important here
					'category_option_error', // error ID - not important here
					"$msg", // error message
					'error' // type of message
				);	
			}
			
		} else { return FALSE;}
				
	} else {
			
			// problem with the nonce
			$msg = __("Action incorrecte - nous ne pouvons pas enregistrer vos catégories", CDP_PLUGIN_NAME );
			add_settings_error(
				'category_option', // setting title - not important here
				'category_option_error', // error ID - not important here
				"$msg", // error message
				'error' // type of message
			);	
	}//else
		
}/* function */

/* 
 * function that sends category choice details to buzzea for update
 * takes: details array of options
 * returns: TRUE / FALSE
 */
function cdp_transmit_category_options ($details_array) {
		
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
	$post_result = wp_remote_post(CDP_API_URL.'categories/save', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_category_details' => $details  ),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	$update_details = $post_result['body'];
	if ($update_details == 1 ){
		//success
		return TRUE;
	}//if
	else {
		return FALSE;
		}//else
	
} /* end function */

/* 
 * function that get categories in xml and confirms they exist
 * takes: -
 * returns: TRUE / FALSE
 */
function cdp_categories_check() {
		
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	$password = md5(cdp_get_password());
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post(CDP_API_URL.'categories/get-all', 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	$categories_xml_str = $post_result['body'];
	if (CDP_DEBUG){
		vd($categories_xml_str);
	}
	
	$xmlcheck = simplexml_load_string($categories_xml_str);
	
	if($xmlcheck===FALSE) {
	//It was not an XML string
		return FALSE;
	} else {
		//It was a valid XML string
		if ($categories_xml_str != FALSE && $categories_xml_str != ''){
			$categories = new SimpleXMLElement($categories_xml_str);
			//options previously chosen?
			$categoriesAttributes = $categories->categories->attributes();
			if ($categoriesAttributes->allselected == 'yes') { 
				return TRUE; // categories already in selection
			} else { 
				//look for a selected category at parent level 
				foreach ($categories->categories->category as $category) {
					$catAttributes = $category->attributes(); 
					if ($catAttributes->selected == 'yes') { 
						return TRUE;
					}
					
				}//foreach
				
				return FALSE; //if we got this far = no categories selected
			}//else
		}//if
	}//else
	
}/* function */

/* 
 * function that get categories in xml and display in collapse/expand selector tree 
 * categories are displayed in 2 columns. $catspercolumn defines how many in first column
 * takes: - $slim: 		this is to return only categories already in selection. Not the whole lot.
 *			$prefix: 	to ensure different class / div id's to allow different jQuery functions use/target the output
 *			$no_domaine	to allow retrieval of all categories independent of domaineid
 * returns: echoed HTML
 */
function cdp_categories_table($slim=FALSE, $prefix = '', $catid_selected='', $no_domaine=FALSE) {
	
	//slim is to have only a tree of categories that are selectable
	if (!isset($slim)) { $slim=FALSE; }
	if ($slim == TRUE) { $prefix = $prefix; } else { $prefix = ''; }
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
		
	$lessimg = CDP_IMG_URL_ADMIN.'puce_less.png';
	$moreimg = CDP_IMG_URL_ADMIN.'puce_more.png';
	$catspercolumn = -1; //num items per first column, -1 to ignore
	$displaynone = 'none'; //to hide child categories
	
	$onclick = '';//init
	$imgsrc = $lessimg;
	
	//test if jQuery is available
	if( wp_script_is( 'jquery', 'done' ) || wp_script_is( 'jquery', 'registered' )) {
		// do nothing 
	} else {
		// show all categories as a fail safe for non jquery users
		$displaynone = 'block';
		//could load JQ here...
	}
	
	$password = md5(cdp_get_password());
	
	
	if ($no_domaine == FALSE){
		$cats_url = CDP_API_URL.'categories/get-all';
	}
	else {
		$cats_url = CDP_API_URL.'categories/get-all-no-domaine';
		//want to get all categories regardless of domains
	}
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post($cats_url, 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password),
										'cookies' => array()
										)
								);	
								
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	$categories_xml_str = $post_result['body'];
	if (CDP_DEBUG){
		vd($categories_xml_str);
	}
	
	//echo "<br /><pre>$categories_xml_str</pre><br />";
	
	//echo $categories_xml_str; die();
	$xmlcheck = simplexml_load_string($categories_xml_str);
	
	if($xmlcheck===FALSE) {
	//It was not an XML string
		$xmlcheck = FALSE;
	} else {
	//It was a valid XML string
		$xmlcheck = TRUE;
	}
	
	
	if ($xmlcheck === FALSE){
		//fail
				$msg = __("Pas des Categories", CDP_PLUGIN_NAME ); //Pas de données xml //debug HELP - if you echo anything that in the main library it will disrupt XML
				?>
				<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");
				</script>
				<?php
	}
	
	if ($categories_xml_str != FALSE && $categories_xml_str != '' && $xmlcheck != FALSE){
			
		//vd($categories_xml_str); die();
		$categories = new SimpleXMLElement($categories_xml_str);
		//vd($categories_xml_str);
		//output option to choose all categories
		$categoriesAttributes = $categories->categories->attributes();
		if ($categoriesAttributes->allselected == 'yes') { $allselected_checked = 'checked'; $selection_checked = '';} else { $allselected_checked = ''; $selection_checked = 'checked'; }
			if ($slim == FALSE){
			?>
			<div id="cats-all">
				<input type="radio"  value="all-categories" name="all_categories" <?php echo $allselected_checked; ?> ><?php echo __('Tous les rayons', CDP_PLUGIN_NAME ); ?><br />
				<input type="radio"  value="only-selected" name="all_categories" <?php echo $selection_checked; ?> ><?php echo __('Seulement les rayons suivants', CDP_PLUGIN_NAME ); ?>
			</div>
			<?php
			}
		
		//now have categories in an XML object ready to output
		
		$i = 0;//columns item counter
		$j = 0;//slim mcounter
		
		///////////////
		//	LEVEL 1  //
		///////////////
		if ($categories->categories->category != NULL){
			
			$cdp_catdivid = 'id="cats"';
			if ($slim == TRUE){ $cdp_catdivid = 'class="cats_widget"'; }
				
			?>
            <br clear="all" /><div <?php echo $cdp_catdivid; ?>><?php
			
			foreach ($categories->categories->category as $category) {

				/* choices for column display */
				if ($i == 0){ echo '<div id="'.$prefix.'cats-left">'; }
				if ($i == $catspercolumn){ echo '</div><!-- '.$prefix.'cats-left --><div id="'.$prefix.'cats-right">'; }
				
				
				$catAttributes = $category->attributes(); 
				if ($catAttributes->selected == 'yes') $checked="checked"; else $checked="";
				
				
				if ($slim == TRUE && $i == 0 && $j == 0){
					
					//checked
					if ('' === $catid_selected){ $checked="checked"; } else {$checked="";}
					
					?>
                    <!--cdc-->
					<div class="cat-parent" id="<?php echo $prefix;?>cat">
					<img id="<?php echo $prefix;?>puce" src="<?php echo $imgsrc;?>" <?php echo $onclick;?> >
					<input id="<?php echo $prefix;?>categorie" rel="<?php echo $prefix;?>" type="checkbox" <?php echo $checked;?> name="categorie">
					<label for="<?php echo $prefix;?>categorie"><?php echo __('Coups de Coeur', CDP_PLUGIN_NAME );?></label>	
                    </div>
                    
                    <?php //checked
					if (0 == $catid_selected && '' !== $catid_selected){ $checked="checked"; } else {$checked="";}?>
                    
                    <!--uncat-->
                    <div class="cat-parent" id="<?php echo $prefix;?>cat0">
					<img id="<?php echo $prefix;?>puce0" src="<?php echo $imgsrc;?>" <?php echo $onclick;?> >
					<input id="<?php echo $prefix;?>categorie0" rel="<?php echo $prefix;?>" type="checkbox" <?php echo $checked;?> name="categorie0">
					<label for="<?php echo $prefix;?>categorie0"><?php echo __('Uncategorized', CDP_PLUGIN_NAME ); /*non classé*/ ?></label>	
                    </div>
					<?php
										
					$j = $j+1;
				}
				
				if ($slim == TRUE){
					$checked=""; //want nothing checked
					//except...
					if ($catAttributes->id == $catid_selected){ $checked="checked"; } else {$checked="";}
					
					// if domain specified then execute this piece
					// returns only selected cats
					if ($no_domaine == FALSE){
						if ($catAttributes->selected != 'yes'){ continue; } //only want selected top-level categories - skip next bit
					}
				}
				//increment i
				$i = $i+1;
				
				//prep the onclick
				if ($category->subcategories->subcategory != NULL){ $imgsrc = $moreimg; $imgsrc_opposite = $lessimg; }else{ $imgsrc = $lessimg;  $imgsrc_opposite = $moreimg; } //choose expansion image		
				$onclick="onclick=\"ouvrefermecategorie(".sanitize_text_field($catAttributes->id).", '".$prefix."cat".sanitize_text_field($catAttributes->id)."', '".$prefix."subcat', '".$imgsrc_opposite."', '".$imgsrc."', this)\" style=\"cursor:pointer;\""; //setup correct onclick action
				
				?>
			    <div class="cat-parent" id="<?php echo $prefix;?>cat<?php echo esc_attr($catAttributes->id); ?>">
					<img id="<?php echo $prefix;?>puce<?php echo sanitize_text_field($catAttributes->id); ?>" src="<?php echo $imgsrc;?>" <?php echo $onclick;?> >
					<input id="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($catAttributes->id); ?>" rel="<?php echo $prefix;?>" type="checkbox" <?php echo $checked;?> name="categorie<?php echo sanitize_text_field($catAttributes->id); ?>">
					<label for="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($catAttributes->id); ?>"><?php echo sanitize_text_field($category->title); ?> <?php if ($slim == TRUE && $no_domaine == FALSE){echo '('.sanitize_text_field($category->nb_articles).')';} ?></label>
				
				<?php 
				
				///////////////
				//	LEVEL 2  //
				///////////////
				if ($category->subcategories->subcategory != NULL){
					foreach ($category->subcategories->subcategory as $subcategory) {
						
						$subcatAttributes = $subcategory->attributes();
						if ($subcatAttributes->selected == 'yes') $checked="checked"; else $checked="";
						
						if ($slim == TRUE){
							$checked=""; //want nothing checked
							//except...
							if ($subcatAttributes->id == $catid_selected){ $checked="checked"; } else {$checked="";}
							// if domain specified then execute this piece
							// returns only selected cats
							if ($no_domaine == FALSE){
								if ($subcatAttributes->selected != 'yes'){ continue; } //only want selected categories
							}
						}
						
						//prep the onclick
						if ($subcategory->subcategories->subcategory != NULL){ $imgsrc = $moreimg; $imgsrc_opposite = $lessimg; }else{ $imgsrc = $lessimg;  $imgsrc_opposite = $moreimg;} //choose expansion image	
						$onclick="onclick=\"ouvrefermecategorie(".sanitize_text_field($subcatAttributes->id).",'".$prefix."subcat".sanitize_text_field($subcatAttributes->id)."', '".$prefix."subsubcat', '".$imgsrc_opposite."', '".$imgsrc."', this)\" style=\"cursor:pointer;\"";//setup correct onclick action
						
						?><div id="<?php echo $prefix;?>subcat<?php echo sanitize_text_field($subcatAttributes->id); ?>" style="padding-left: 17px; display:<?php echo $displaynone; ?>">
						<div>
							<img id="<?php echo $prefix;?>puce<?php echo sanitize_text_field($subcatAttributes->id); ?>" src="<?php echo $imgsrc;?>" <?php echo $onclick;?>>
							<input id="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($subcatAttributes->id); ?>" rel="<?php echo $prefix;?>" type="checkbox" <?php echo $checked;?> name="categorie<?php echo sanitize_text_field($subcatAttributes->id); ?>">
							<label for="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($subcatAttributes->id); ?>"><?php echo sanitize_text_field($subcategory->title); ?> <?php if ($slim == TRUE && $no_domaine == FALSE){echo '('.sanitize_text_field($subcategory->nb_articles).')';} ?></label>
							
                    	</div>	
						<?php 
						
						///////////////
						//	LEVEL 3  //
						///////////////
						if ($subcategory->subcategories->subcategory != NULL){
							foreach ($subcategory->subcategories->subcategory as $subsubcategory) {
								
								$subsubcatAttributes = $subsubcategory->attributes();
								if ($subsubcatAttributes->selected == 'yes') $checked="checked"; else $checked="";
								
								if ($slim == TRUE){
									$checked=""; //want nothing checked
									//except...
									if ($subsubcatAttributes->id == $catid_selected){ $checked="checked"; } else {$checked="";}
									// if domain specified then execute this piece
									// returns only selected cats
									if ($no_domaine == FALSE){
										if ($subsubcatAttributes->selected != 'yes'){ continue; } //only want selected categories
									}
								}
								
								//prep the onclick
								if ($subsubcategory->subcategories->subcategory != NULL){ $imgsrc = $moreimg; $imgsrc_opposite = $lessimg; }else{ $imgsrc = $lessimg;  $imgsrc_opposite = $moreimg;} //choose expansion image	
								$onclick="onclick=\"ouvrefermecategorie(".sanitize_text_field($subsubcatAttributes->id).",'".$prefix."subsubcat".sanitize_text_field($subsubcatAttributes->id)."', '".$prefix."subsubsubcat', '".$imgsrc_opposite."', '".$imgsrc."', this)\" style=\"cursor:pointer;\"";//setup correct onclick action
								?><div id="<?php echo $prefix;?>subsubcat<?php echo sanitize_text_field($subsubcatAttributes->id); ?>" style="padding-left: 34px; display: <?php echo $displaynone ?>;">
								<div>
								<img id="<?php echo $prefix;?>puce<?php echo sanitize_text_field($subsubcatAttributes->id); ?>" src="<?php echo $imgsrc?>" <?php echo $onclick;?>>
								<input id="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($subsubcatAttributes->id); ?>" rel="<?php echo $prefix;?>" type="checkbox" <?php echo $checked;?> name="categorie<?php echo sanitize_text_field($subsubcatAttributes->id); ?>">
								<label for="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($subsubcatAttributes->id); ?>"><?php echo sanitize_text_field($subsubcategory->title); ?> <?php if ($slim == TRUE && $no_domaine == FALSE){echo '('.sanitize_text_field($subsubcategory->nb_articles).')';} ?></label>
								
                                </div>	
								<?php 
								
								///////////////
								//	LEVEL 4  //
								///////////////
								if ($subsubcategory->subcategories->subcategory != NULL){
									foreach ($subsubcategory->subcategories->subcategory as $subsubsubcategory) {
										
										$subsubsubcatAttributes = $subsubsubcategory->attributes();
										if ($subsubsubcatAttributes->selected == 'yes') $checked="checked"; else $checked="";
										
										if ($slim == TRUE){
											$checked=""; //want nothing checked
											//except...
											if ($subsubsubcatAttributes->id == $catid_selected){ $checked="checked"; } else {$checked="";}
											// if domain specified then execute this piece
											// returns only selected cats
											if ($no_domaine == FALSE){
												if ($subsubsubcatAttributes->selected != 'yes'){ continue; } //only want selected categories
											}
										}
										
										$onclick='';//nothing at this level to expand "onclick=\"ouvrefermecategorie('subsubsubcat".$subsubsubcatAttributes->id."', 'subsubsubsubcat')\" style=\"cursor:pointer;\"";//setup correct onclick action
										?><div id="<?php echo $prefix;?>subsubsubcat<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>" style="padding-left: 51px; display: <?php echo $displaynone ?>;"><?php 
										
										?>
											<div>
											<img id="<?php echo $prefix;?>puce<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>" src="<?php echo CDP_IMG_URL_ADMIN;?>puce_less.png" <?php echo $onclick;?>>
											<input id="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>" rel="<?php echo $prefix;?>" type="checkbox" <?php echo $checked;?> name="categorie<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>">
											<label for="<?php echo $prefix;?>categorie<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>"><?php echo sanitize_text_field($subsubsubcategory->title); ?><?php if ($slim == TRUE && $no_domaine == FALSE){echo '('.sanitize_text_field($subsubsubcategory->nb_articles).')';} ?></label>
											</div>	
										<?php 
								
										?></div><!-- subsubsubcat --><?php
									}//foreach
								}//if
								
								?></div><!-- subsubcat --><?php
							}//foreach
						}//if
						
						?></div><!-- 	subcat --><?php
					}//foreach
				
				}//if
				?></div><!-- cat-parent --><?php
			}//foreach
			
			?></div><!-- cats-right --><?php 
			?></div><!-- cats --><br clear="all" /><?php
		}//if
		 //echo $categories_xml->categories->category[0]->title;
			
	} else { return FALSE; }
		//$update_details = $post_result['body'];
}/* function */
	
//from http://wordpress.stackexchange.com/questions/41588/how-to-get-the-clean-permalink-in-a-draft
// gets the proposed link for a new article
function cdp_get_draft_permalink( $post_id ) {

	//require_once ABSPATH . '/wp-admin/includes/post.php';
	list( $permalink, $postname ) = get_sample_permalink( $post_id );

	return str_replace( '%postname%', $postname, $permalink );
}

/**********END OF FILE **************/
?>