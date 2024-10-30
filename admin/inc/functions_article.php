<?php 
/*
Description: This file contains the article functions for the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/


/* 
 * function that is called by ajax to call cdp_articles_table and show articles table
 * takes: -
 * returns: other function call results
 */
function cdp_display_contextuelle_editor_ajax(){
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'contextuelle_editornonce' ) && $_POST['action'] == 'cdp_getcontextuelle_editor'){
	
	$postid = intval($_POST['postid']);
	
	cdp_meta_box_banner_contextuelle_content($postid);
	die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function that is called by ajax to call cdp_articles_table and show articles table
 * takes: -
 * returns: other function call results
 */
function cdp_show_articles_ajax(){
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlesnonce' ) && $_POST['action'] == 'cdp_getarticles'){
	
		// get limits
		if (is_numeric($_POST['limitstart']) && is_numeric($_POST['limitfor'])){
			$limitstart = $_POST['limitstart'];
			$limitfor  = $_POST['limitfor'];
		} else { $limitstart = 0; $limitfor = 10; }
		
		// target_path is the page this list is showing on. used in pagination later
		if (isset($_POST['target_path'])){
			$cdp_target_path = esc_url($_POST['target_path']);
		}
		
		if (isset($_POST['domaineid']) && is_numeric($_POST['domaineid'])){
			$domaineid = intval($_POST['domaineid']);
		}
		
		
		cdp_articles_table($limitstart,$limitfor, $cdp_target_path, $domaineid);
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function that is called by ajax to call cdp_articles_contextuelle_table and show contextuelle articles table
 * function originated from cdp_articles_table
 * Shows the list of **selected** articles
 * takes: -
 * returns: other function call results
 */
function cdp_show_articles_contextuelle_ajax(){
	
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlescontextuellenonce' ) && $_POST['action'] == 'cdp_getarticlescontextuelle'){
	
		// get limits
		if (is_numeric($_POST['limitstart']) && is_numeric($_POST['limitfor'])){
			$limitstart = $_POST['limitstart'];
			$limitfor  = $_POST['limitfor'];
		} else { $limitstart = 0; $limitfor = 10; }
		
		// target_path is the page this list is showing on. used in pagination later
		if (isset($_POST['target_path'])){
			$cdp_target_path = esc_url($_POST['target_path']);
		}
		
		cdp_articles_contextuelle_table($limitstart,$limitfor, $cdp_target_path);
		
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function called by Ajax to show articles for category
 * takes: 	$_POST['categorieid']
 * returns: result / false
 */
function cdp_showarticles_for_category_ajax(){
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlesnonceshowarticlescategory' ) && $_POST['action'] == 'cdp_showarticles_for_category' && is_numeric($_POST['categorieid'])){
		
		if (isset($_POST['domaineid']) && is_numeric($_POST['domaineid'])){
			$domaineid = intval($_POST['domaineid']);
		} else {
			$domaineid = NULL;
		}
		
		$show_result = cdp_showarticles_for_category($_POST['categorieid'], $_POST['limitfrom'], $_POST['limitfor'], $domaineid);
		die(); //0 issue
		
	} else { return FALSE; }
} /* end function */ 

/* 
 * function called by Ajax to show articles for category
 * takes: 	$_POST['categorieid']
 * returns: result / false
 */
function cdp_showarticles_for_category_contextuelle_ajax(){
	
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlesnonceshowarticlescategorycontextuelle' ) && $_POST['action'] == 'cdp_showarticles_for_category_contextuelle' && is_numeric($_POST['categorieid'])){
	
		if (isset($_POST['target_path'])){
			$cdp_target_path = esc_url($_POST['target_path']);
		}
		
		$show_result = cdp_showarticles_for_category_contextuelle($_POST['categorieid'], $_POST['limitfrom'], $_POST['limitfor'], $cdp_target_path);
		
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 


/* 
 * function to show articles once a category is chosen (category tree)
 * takes: 	$categorieid
 * returns: ajoute (added) : existe (already on file) : TRUE / FALSE 
 */
function cdp_showarticles_for_category($categorieid, $limitfrom, $limitfor, $domaineid=FALSE) {
	
	$password 			= md5(cdp_get_password());
	$articlesnonceadd 	= wp_create_nonce( 'articlesnonceadd' );
	
	//limits
	$limitfrom  = intval($limitfrom);
	$limitfor 	= intval($limitfor);
	
	if (is_numeric($categorieid)){
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		//request categories details in XML from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'articles/showpercategory/', 
										array(

											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' 			=> $cdp_site_url, 
															'cdp_password' 			=> $password , 
															'cdp_categorieid' 		=> $categorieid, 
															'cdp_domaineid' 		=> $domaineid, 
															/*'cdp_add_nonce' 		=> $articlesnonceadd, 
															'cdp_add_txt_success' 	=> $article_add_success_txt, 
															'cdp_add_txt_fail' 		=> $article_add_fail_txt,  */
															'cdp_limitfrom' 		=> $limitfrom,  
															'cdp_limitfor' 			=> $limitfor,  
															),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		if ($post_result['body'] != FALSE){
			//echo 'ajouté'; 
			echo $post_result['body'];//added
			
		}
		else { return FALSE; }
	
	} else { return FALSE; }
		
} /* end function */

/* 
 * function to show articles once a category is chosen (category tree)<br />
 * used on contextuelle page 
 * shows within category
 * takes: 	$categorieid
 * returns: ajoute (added) : existe (already on file) : TRUE / FALSE 
 */
function cdp_showarticles_for_category_contextuelle($categorieid, $limitfrom, $limitfor, $cdp_target_path) {
	
	//vd($cdp_target_path);
	$password 		 	= md5(cdp_get_password());
	$articlesnonceadd 	= wp_create_nonce( 'articlesnonceadd' );

	//limits
	$limitfrom  = intval($limitfrom);
	$limitfor 	= intval($limitfor);
	
	// current post's permalink
	$permalink 	= cdp_get_contextuelle_permalink($cdp_target_path);
	//vd($permalink);
	if (is_numeric($categorieid)){
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		//request categories details in XML from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'articles/showpercategory_contextuelle/', 
										array(

											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' 			=> $cdp_site_url, 
															'cdp_password' 			=> $password , 
															'cdp_categorieid' 		=> $categorieid, 
															/*'cdp_add_nonce' 		=> $articlesnonceadd, 
															'cdp_add_txt_success' 	=> $article_add_success_txt, 
															'cdp_add_txt_fail' 		=> $article_add_fail_txt,  */
															'cdp_limitfrom' 		=> $limitfrom,  
															'cdp_limitfor' 			=> $limitfor,  
															'cdp_contexte_url' 		=> $permalink,
															),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		if ($post_result['body'] != FALSE){
			//echo 'ajouté'; 
			echo $post_result['body'];//added
			
		}
		else { return FALSE; }
	
	} else { return FALSE; }
		
} /* end function */


/* 
 * function that gets permalink based on an admin path 
 * takes: 	$cdp_target_path	///wp-admin/post.php?post=191&action=edit
 * returns: http link
 */
function cdp_get_contextuelle_permalink($cdp_target_path) {
	
	if (!isset($cdp_target_path)){return FALSE;}
	
	// current post's permalink
	// need to get the id
	$subject = esc_url($cdp_target_path);
	$pattern = '/post=[0-99999999]+(&)?/';
	preg_match($pattern, $subject, $matches, FALSE, 3);
	//print_r($matches);
	
	if (is_array($matches)){
		$id = $matches[0];
		
		$id = str_replace('post=', '', $id);
		$id = str_replace('&', '', $id);
		//$id = intval($id);
	}
	
	if (isset($id) && is_numeric($id)){
		$permalink 	= get_permalink($id);  
		return $permalink;
	} else {
		return FALSE; 
	}
	
}

/* 
 * function called by ajax to remove an article
 * takes: -
 * returns: result / false
 */
function cdp_delete_article_ajax(){
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlesnoncedelete' ) && $_POST['action'] == 'cdp_deletearticle' && is_numeric($_POST['articleid'])){
		
		if (isset($_POST['domaineid']) && is_numeric($_POST['domaineid'])){
			$domaineid = intval($_POST['domaineid']);
		} else {
			$domaineid = NULL;	
		}
		
		$delete_result = cdp_article_delete($_POST['articleid'], $domaineid);
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function called by ajax to remove an article
 * takes: -
 * returns: result / false
 */
function cdp_delete_multiple_articles_ajax(){
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'noncemultipledelete' ) && $_POST['action'] == 'cdp_delete_multiple_articles' ){
		
		if (isset($_POST['domaineid']) && is_numeric($_POST['domaineid'])){
			$domaineid = intval($_POST['domaineid']);
		} else {
			$domaineid = NULL;	
		}
		
		$delete_result = cdp_article_multiple_delete($_POST['articleids'], $domaineid);
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function called by ajax to remove an article
 * takes: -
 * returns: result / false
 */
function cdp_delete_multiple_articles_contextuelle_ajax(){
	
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'noncemultipledelete' ) && $_POST['action'] == 'cdp_delete_multiple_articles_contextuelle' ){
	
		$delete_result = cdp_article_multiple_contextuelle_delete( $_POST['contexte_url'], $_POST['articleids']);
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function called by ajax to remove all articles in selection
 * takes: -
 * returns: result / false
 */
function cdp_delete_all_articles_ajax(){
	
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'noncealldelete' ) && $_POST['action'] == 'cdp_delete_all_articles' ){
		
		if (isset($_POST['domaineid']) && is_numeric($_POST['domaineid'])){
			$domaineid = intval($_POST['domaineid']);
		} else {
			$domaineid = NULL;	
		}
		
		$delete_result = cdp_article_all_delete($domaineid);
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function called by ajax to remove all articles in selection
 * takes: -
 * returns: result / false
 */
function cdp_delete_all_articles_contextuelle_ajax(){
	
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'noncealldelete' ) && $_POST['action'] == 'cdp_delete_all_articles_contextuelle' ){
	
		$delete_result = cdp_article_contextuelle_all_delete($_POST['contexte_url']);
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function that deletes an article by calling api to delete
 * takes: 	$articleid 		id of article to delete
 * returns: true / false
 */
function cdp_article_delete($articleid, $domaineid = FALSE ) {
	$password = md5(cdp_get_password());
	if (is_numeric($articleid)){
		
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		//request categories details in XML from Buzzea
		$post_result = wp_remote_post(CDP_API_URL."article/delete/", 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url,
															 'cdp_password' => $password , 
															 'cdp_domaineid' => $domaineid,
															 'cdp_articleid' => $articleid ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		if ($post_result['body'] == 1){
			return TRUE;
		} else { return FALSE; }
	} else { return FALSE; }
		
} /* end function */ 

/* 
 * function that deletes articles by calling api to delete
 * takes: 	$articleid 		id of article to delete
 * returns: true / false
 */
function cdp_article_multiple_delete($articleids, $domaineid=FALSE) {
	$password = md5(cdp_get_password());
	
	
	// check we're only dealing with numbers
	$articles = explode(',', $articleids);

	foreach ($articles as $singlearticleid){
		if 	(!(is_numeric(intval($singlearticleid)))){
			return FALSE;
		}
	}
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post(CDP_API_URL."article/multi_delete/", 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url,
														 'cdp_password' => $password , 
														 'cdp_domaineid' => $domaineid , 
														 'cdp_articleids' => $articleids ),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }								
	//debug 
	//vd($post_result['body']);
	if ($post_result['body'] == 1){
		return TRUE;
	} else { return FALSE; }
	
		
} /* end function */ 

/* 
 * function that deletes articles by calling api to delete
 * takes: 	$articleid 		id of article to delete
 * returns: true / false
 */
function cdp_article_multiple_contextuelle_delete($contexte_url, $articleids) {
	$password = md5(cdp_get_password());
	
	if ($contexte_url == '') { return FALSE; }
	
	// check we're only dealing with numbers
	$articles = explode(',', $articleids);

	foreach ($articles as $singlearticleid){
		if 	(!(is_numeric(intval($singlearticleid)))){
			return FALSE;
		}
	}
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post(CDP_API_URL."article_contextuelle/multi_delete/", 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password , 'cdp_contexte_url' => $contexte_url, 'cdp_articleids' => $articleids ),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	if ($post_result['body'] == 1){
		return TRUE;
	} else { return FALSE; }
	
		
} /* end function */ 

/* 
 * function that deletes all articles by calling api to delete
 * takes: 	$articleid 		id of article to delete
 * returns: true / false
 */
function cdp_article_all_delete($domaineid=FALSE) {
	$password = md5(cdp_get_password());
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post(CDP_API_URL."article/delete-all/", 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 
										'cdp_domaineid' => $domaineid ,
										'cdp_password' => $password ),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	if ($post_result['body'] == 1){
		return TRUE;
	} else { return FALSE; }
	
		
} /* end function */ 

/* 
 * function that deletes all articles by calling api to delete
 * takes: 	$articleid 		id of article to delete
 * returns: true / false
 */
function cdp_article_contextuelle_all_delete($contexte_url) {

	if ($contexte_url == '') { return FALSE; }

	$password = md5(cdp_get_password());
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post(CDP_API_URL."article_contextuelle/delete-all/", 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password,  'cdp_contexte_url' => $contexte_url ),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }
	//debug 
	//vd($post_result['body']);
	if ($post_result['body'] == 1){
		return TRUE;
	} else { return FALSE; }
	
		
} /* end function */ 
/* 
 * function called by ajax to remove an article
 * takes: -
 * returns: result / false
 */
function cdp_delete_article_contextuelle_ajax(){
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlescontextuellenoncedelete' ) && $_POST['action'] == 'cdp_deletearticlecontextuelle' && is_numeric($_POST['articleid'])){
	
		// target_path is the page this list is showing on. used in pagination later
		if (isset($_POST['target_path'])){
			$cdp_target_path = esc_url($_POST['target_path']);
		}
		
		$articleid = intval($_POST['articleid']);
		
		$delete_result = cdp_article_contextuelle_delete($articleid, $cdp_target_path);
		
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function that deletes an article by calling api to delete
 * takes: 	$articleid 		id of article to delete
 * returns: true / false
 */
function cdp_article_contextuelle_delete($articleid, $cdp_target_path ) {
	$password = md5(cdp_get_password());
	if (is_numeric($articleid)){
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		$cdp_target_path = esc_url($cdp_target_path);
		
		// current post's permalink
		$permalink 	= cdp_get_contextuelle_permalink($cdp_target_path);
		
		//request categories details in XML from Buzzea
		$post_result = wp_remote_post(CDP_API_URL."article_contextuelle/delete/", 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password , 'cdp_contexte_url' => $permalink, 'cdp_articleid' => $articleid ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		if ($post_result['body'] == 1){
			return TRUE;
		} else { return FALSE; }
	} else { return FALSE; }
		
} /* end function */ 



/* 
 * function called by Ajax to add article
 * takes: 	$_POST['articleid']
 * returns: result / false
 */
function cdp_add_article_ajax(){
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlesnonceadd' ) && $_POST['action'] == 'cdp_addarticle' && is_numeric($_POST['articleid'])){
		
		if (isset($_POST['domaineid']) && is_numeric($_POST['domaineid'])){
			$domaineid = intval($_POST['domaineid']);
		} else {
			$domaineid = NULL;	
		}
		
		$add_result = cdp_article_add($_POST['articleid'], $domaineid );
		
		die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function to add article
 * takes: 	$articleid
 * returns: ajoute (added) : existe (already on file) : TRUE / FALSE 
 */
function cdp_article_add($articleid, $domaineid = FALSE) {
	$password = md5(cdp_get_password());
	if (is_numeric($articleid)){
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		//request categories details in XML from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'article/add/', 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl'   => $cdp_site_url, 
															 'cdp_password'  => $password , 
															 'cdp_domaineid' => $domaineid, 
															 'cdp_articleid' => $articleid ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		if ($post_result['body'] == 1){
			echo 'ajouté'; //added
			return TRUE;
		}
		elseif ($post_result['body'] === 'existe'){
			echo 'existe'; //need to echo result
			return 'existe';
		}
		else { return FALSE; }
	
	} else { return FALSE; }
		
} /* end function */

/* 
 * function called by Ajax to add contextuelle article (related to a post/page)
 * takes: 	$_POST['articleid']
 * returns: result / false
 */
function cdp_add_article_contextuelle_ajax(){
	
	if (wp_verify_nonce( $_POST['_ajax_nonce'], 'articlescontextuellenonceadd' ) && $_POST['action'] == 'cdp_addarticlecontextuelle' && is_numeric($_POST['articleid'])){
	
	// target_path is the page this list is showing on. used in pagination later
	if (isset($_POST['target_path'])){
		$cdp_target_path = esc_url($_POST['target_path']);
	}
	
	$articleid = intval($_POST['articleid']);
	
	$add_result = cdp_article_contextuelle_add($articleid, $cdp_target_path);
	//vd($add_result);
	die(); //0 issue
	} else { return FALSE; }
} /* end function */ 

/* 
 * function to add article
 * takes: 	$articleid
 * returns: ajoute (added) : existe (already on file) : TRUE / FALSE 
 */
function cdp_article_contextuelle_add($articleid, $cdp_target_path) {
	
	$password = md5(cdp_get_password());
	
	if (is_numeric($articleid)){
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		$cdp_target_path = esc_url($cdp_target_path);
		
		// current post's permalink
		$permalink 	= cdp_get_contextuelle_permalink($cdp_target_path);
		//vd($permalink );
		//request categories details in XML from Buzzea
		$post_result = wp_remote_post(CDP_API_URL.'article_contextuelle/add/', 
										array(
											'method' => 'POST',
											'timeout' => 45,
											'redirection' => 5,
											'httpversion' => '1.0',
											'blocking' => true,
											'headers' => array(),
											'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password , 'cdp_contexte_url' => $permalink,  'cdp_articleid' => $articleid ),
											'cookies' => array()
											)
									);	
		if( is_wp_error( $post_result ) ) { return FALSE; }
		//debug 
		//vd($post_result['body']);
		if ($post_result['body'] == 1){
			echo 'ajouté'; //added
			return TRUE;
		}
		elseif ($post_result['body'] === 'existe'){
			echo 'existe'; //need to echo result
			return 'existe';
		}
		else { return FALSE; }
	
	} else { return FALSE; }
		
} /* end function */
/**********END OF FILE **************/
?>