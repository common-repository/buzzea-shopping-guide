<?php 
/*
Description: This file contains the search functions for the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/

/* 
 * function that gets search results for a term in ADMIN area
 * takes: 	$term 		term to seaarch for (via GET)
 * returns: JSON details of articles / False
 */
function cdp_get_search_results_admin(){
	
	if (isset($_GET['term'])){
	$term = esc_attr($_GET['term']);
	}
	$password = md5(cdp_get_password());
	
	//called by ajax
	if (isset($term)){
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url(); 
		}
		
		
		if (isset($_GET['domaineid']) && is_numeric($_GET['domaineid'])){
			$domaineid = intval($_GET['domaineid']);
		}
		
		//request categories details in XML from Buzzea
		$post_result = wp_remote_post(CDP_API_URL."article/search/?term=$term", 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url ,
															 'cdp_password' => $password,
															 'cdp_domaineid' => $domaineid
															 ),
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
	//echo '[{"id":"45","label":"'.$_GET['term'].'ABC - €9.99","value":"ABC"},{"id":"46","label":"DEF - &euro;9.99","value":"DEF"}]';
	die(); //needed
}

/**********END OF FILE **************/
?>