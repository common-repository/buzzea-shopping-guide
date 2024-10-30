<?php
/*
Plugin Name: Buzzea - Shopping Guide
Plugin URI: http://wordpress.org/extend/plugins/buzzea-shopping-guide/
Description: Faites profiter votre lectorat d'un outil paramétrable de comparaison de prix et monétisez l'audience de ces pages au CPC.
Version: 1.3.4
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2012 BUZZEA.com (email : info@buzzea.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

Developer: Gordon Fortune

*/

define('CDP_PLUGIN_VERSION', '1.3.4');
define('CDP_PLUGIN_NAME', 'buzzea-shopping-guide');//buzzea-wp-prix-comparateur
define('CDP_PLUGIN_SHORT_NAME', __('BUZZEA Guide Shopping', CDP_PLUGIN_NAME ) );
define('CDP_PLUGIN_PATH', plugin_dir_path( __FILE__ )); //e.g. /home/mycompany/www/myblog/wp-content/plugins/buzzea-wp-prix-comparateur/ 
define('CDP_PLUGIN_URL', plugins_url().'/'.CDP_PLUGIN_NAME);
define('CDP_PLUGIN_FOLDER_NAME', CDP_PLUGIN_NAME);
define('CDP_PLUGIN_FILE', basename(__FILE__));

//img urls
define('CDP_IMG_URL', CDP_PLUGIN_URL.'/img/');
define('CDP_IMG_URL_ADMIN', CDP_PLUGIN_URL.'/admin/img/'); 

$this_file = __FILE__;
$cdp_extra = ''; //ignore 
define('CDP_USE_GILL', false);  // use gill updater (updater used when plugin not stored in online WP repository)
define('CDP_URL_EXTRA', 'http://cdp'.$cdp_extra);
define('CDP_API_URL', 'http://cdp'.$cdp_extra.'.moteurdeshopping.com/api/');
define('CDP_APPLICATION_URL', 'http://cdp'.$cdp_extra.'.moteurdeshopping.com/');
define('CDP_WIDGET_URL', '.moteurdeshopping.com/widget'); 														// combines with 'extra''
define('CDP_PREVI_URL', 'http://previ'.$cdp_extra.'.moteurdeshopping.com/?action=previsualiser&domaineid=1'); 	// previews of styles
define('CDP_DEBUG', FALSE);
define('CDP_SESSION', TRUE); 																					// allow use of $_SESSION
define('CDP_AJAX_URL', admin_url( 'admin-ajax.php' )); 															// used for ajax requests
define('CDP_PLUGIN_SHORTCODE', 'cdp_buzzea');
define('CDP_PRODUCT_JUMP_URL', 'clic/'); 																		// the page associated with redirecting to a merchants site / product

/************************** You can edit the following settings as required for your blog *************************************/

define('CDP_ITEMS_PER_LIST_ADMIN', 10);				// num aricles to show in list
define('CDP_PAGINATION_PARAMETRE', 'cdppage');		// paging convention i.e. url/?mypag=1 or l/?cdppage=1 -- this parameter is shared for admin and frontend paging
define('CDP_ARTICLE_ORDER_PARAMETRE', 'cdporder');	// to allow ordering by price ascending / popularity
define('CDP_ADMIN_ARTICLES_PER_CATEGORY', 15); 		// how many articles to show when click on category in admin (less than 15 not advised here)

/************************** END of editable settings. Please don't edit below this line **************************************/

// file locations
define('CDP_STYLE_FILE_DEFAULT', 'css/cdp_style_1.css');
define('CDP_STYLE_FILE', 'css/cdp_style_');
define('CDP_STYLE_FILE_ADMIN', 'admin/css/cdp_admin.css');
define('CDP_JS_FUNCTIONS', 'js/cdp_functions.js');
define('CDP_JS_FUNCTIONS_ADMIN', 'admin/js/cdp_functions_admin.js');
define('CDP_LIGHTBOX_JS_FILE', 'js/lightbox.js');
define('CDP_LIGHTBOX_CSS_FILE', 'css/lightbox.css'); 

// JQ sources
define('CDP_JQUERY_FILE', 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js');
define('CDP_JQUERY_UI_FILE', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js');
define('CDP_JQUERY_UI_CSS_FILE', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/ui-lightness/jquery-ui.css');

// other
define('CDP_SUBSCRIPTION_URL', 'https://espaceclients.buzzea.com/blogueur/'); // for sign up for Buzzea account if desired by user
define('CDP_BUZZEA_TEL', '+33 (0)1 84 19 04 89');  // phone
define('CDP_BUZZEA_TEL_LOCAL', '01 84 19 04 89');  // phone
define('CDP_BUZZEA_TEL_GLOBAL', '+33.1 84 19 04 89');  // phone
define('CDP_BUZZEA_SUPPORT_EMAIL', 'technique@buzzea.com');  // phone

//load_plugin_textdomain('buzzea-wp-prix-comparateur', false, basename( dirname( __FILE__ ) ) . '/languages' );

if (is_admin()){
	// default colors for first install
	$GLOBALS['theme_defaults'] = array(
	
		'theme_option_graphic' => '1',
					
		'theme1_personal_option_items_per_page' => '21',
		'theme1_personal_option_items_per_row' => '3',
		'theme1_personal_option_cats_per_row' => '3',

		'theme1_personal_option_color_text' => 'FFFFFF',
		'theme1_personal_option_color_links' => '000000',
		'theme1_personal_option_color_theme' => 'D50400',
		'theme1_personal_option_color_background' => 'FFFFFF',
		
		'theme1_personal_option_color_text2' => '000000',
		'theme1_personal_option_color_links2' => '336699',
		'theme1_personal_option_color_theme2' => 'EBEBED',
		
		'theme1_personal_option_color_text3' => '336699',
		'theme1_personal_option_color_links3' => '666666',
		'theme1_personal_option_color_theme3' => 'FFFFFF',
		
		'theme1_personal_option_color_text4' => 'FFFFFF',
		'theme1_personal_option_color_links4' => '000000',
		'theme1_personal_option_color_theme4' => 'A28877',
		
		
		'theme2_personal_option_items_per_page' => '21',
		'theme2_personal_option_items_per_row' => '3',
		'theme2_personal_option_cats_per_row' => '2',

		'theme2_personal_option_color_text' => 'FFFFFF',
		'theme2_personal_option_color_links' => '000000',
		'theme2_personal_option_color_theme' => 'D50400',
		'theme2_personal_option_color_background' => 'FFFFFF',
		
		'theme2_personal_option_color_text2' => '000000',
		'theme2_personal_option_color_links2' => '336699',
		'theme2_personal_option_color_theme2' => 'EBEBED',
		
		'theme2_personal_option_color_text3' => '336699',
		'theme2_personal_option_color_links3' => '666666',
		'theme2_personal_option_color_theme3' => 'f6b134',
		
		'theme2_personal_option_color_text4' => 'FFFFFF',
		'theme2_personal_option_color_links4' => '000000',
		'theme2_personal_option_color_theme4' => 'A28877'
	
			);
			
	$GLOBALS['personalisation_defaults'] = array(
		'personal_option_libelle' => __('Nom de Votre Site', CDP_PLUGIN_NAME ),
		'personal_option_libelle_comparateur' => __('Votre Guide Shopping', CDP_PLUGIN_NAME ),
			);
			
	$GLOBALS['behaviour_defaults'] = array(
		'behaviour_option_cdc_choice' => '1',
		'behaviour_option_cdc_only_choice' => '0',
		'behaviour_option_recherche_choice' => '1'
			);
			
	$GLOBALS['article_defaults'] = array(
		'article_option_autofiller' => '0'
			);
			
	$GLOBALS['widget_defaults'] = array( 
		'widget_option_active' => 'TRUE',
		'widget_option_size' => '',
		'widget_option_limit' => '10',
		'widget_option_show_beside_cdp' => 'TRUE',
		'widget_option_show_after_page_content' => 'FALSE',
		'widget_option_show_after_post_content' => 'FALSE',
		'widget_option_terms' => '',
		'widget_option_category' => __('Coups de Coeur', CDP_PLUGIN_NAME ),
		'widget_option_category_id' => ''
	);
}// is_admin

// required files
require_once CDP_PLUGIN_PATH.'inc/frontend_functions.php'; // functions to display / navigate comparateur
//initialize language
add_action('plugins_loaded', 'cdp_lang_init');
//needs language...
require_once CDP_PLUGIN_PATH.'admin/inc/functions_widget.php';

if (is_admin()){
	// required files admin
	require_once CDP_PLUGIN_PATH.'admin/inc/functions_admin.php';
	require_once CDP_PLUGIN_PATH.'admin/inc/functions_article.php';
	require_once CDP_PLUGIN_PATH.'admin/inc/functions_article_display.php';
	require_once CDP_PLUGIN_PATH.'admin/inc/functions_search.php';
	require_once CDP_PLUGIN_PATH.'admin/inc/tabs.php';
	
	// custom updates/upgrades
	$update_check = CDP_APPLICATION_URL."download/wp/latest/cdp_wp_plugin.chk";
	
	//OFF for distributed version:
	// simulates update mechanism of wordpress
	if (CDP_USE_GILL) {require_once  CDP_PLUGIN_PATH.'admin/inc/gill-updates.php';}
	
	// Initialize the plugin
	add_action( 'plugins_loaded', create_function( '', '$admin_tabs = new admin_tabs;' ) );// was: plugins_loaded //init
	add_action( 'admin_init', 'cdp_version_check_and_rule_flush' );
	add_action( 'admin_init', 'cdp_check_and_update_location' );
	
	//add scripts
	add_action( 'admin_init', 'cdp_load_admin_scripts' );
}//is_admin

/* ajax actions frontend */
add_action('wp_ajax_nopriv_get_search_results', 'cdp_get_search_results'); 	// wp_ajax_nopriv_ for front end
/* ajax actions backend */
if (is_admin()){
	add_action('wp_ajax_get_search_results', 'cdp_get_search_results'); 	// wp_ajax_nopriv_ for front end, wp_ajax_ for front end
}

/**************************************************************************************/
//BEGIN 	Admin Functions
/**************************************************************************************/

/* ajax actions */
if (is_admin()){
	
	// articles
	add_action('wp_ajax_cdp_getarticles', 'cdp_show_articles_ajax' ); 		// js call to  action: 'getarticles' then goes to show_articles_ajax
	add_action('wp_ajax_cdp_deletearticle', 'cdp_delete_article_ajax' ); 	// js call to  action: 'deletearticle' then goes to delete_article_ajax
	add_action('wp_ajax_cdp_addarticle', 'cdp_add_article_ajax' ); 			// js call to  action: 'addarticle' then goes to add_article_ajax
	add_action('wp_ajax_cdp_showarticles_for_category', 'cdp_showarticles_for_category_ajax' ); //js call to  action
	// delete articles
	add_action('wp_ajax_cdp_delete_multiple_articles', 'cdp_delete_multiple_articles_ajax' ); 		
	add_action('wp_ajax_cdp_delete_all_articles', 'cdp_delete_all_articles_ajax' ); 		
	// contextuelle articles
	add_action('wp_ajax_cdp_getarticlescontextuelle', 'cdp_show_articles_contextuelle_ajax' ); 		// js call to  action: 'getarticlescontextuelle' then goes to show_articles_contextuelle_ajax
	add_action('wp_ajax_cdp_deletearticlecontextuelle', 'cdp_delete_article_contextuelle_ajax' ); 	// js call to  action: 'deletearticlecontextuelle' then goes to delete_article_contextuelle_ajax
	add_action('wp_ajax_cdp_addarticlecontextuelle', 'cdp_add_article_contextuelle_ajax' ); 		// js call to  action: 'addarticle_contextuelle' then goes to add_article_contextuelle_ajax
	add_action('wp_ajax_cdp_showarticles_for_category_contextuelle', 'cdp_showarticles_for_category_contextuelle_ajax' ); //js call to  action
	// delete contextuelle articles
	add_action('wp_ajax_cdp_delete_multiple_articles_contextuelle', 'cdp_delete_multiple_articles_contextuelle_ajax' ); 		
	add_action('wp_ajax_cdp_delete_all_articles_contextuelle', 'cdp_delete_all_articles_contextuelle_ajax' ); 		
	// contextuelle editor
	add_action('wp_ajax_cdp_getcontextuelle_editor', 'cdp_display_contextuelle_editor_ajax' ); 		
	// search
	add_action('wp_ajax_get_search_results_admin', 'cdp_get_search_results_admin');
	// widgets / banners
	add_action('wp_ajax_cdp_updatewidget', 'cdp_update_widget_ajax' ); 		
	add_action('wp_ajax_cdp_updatewidget_contextuelle', 'cdp_update_widget_contextuelle_ajax' ); 		
	add_action('wp_ajax_cdp_addwidget', 'cdp_add_widget_ajax' ); 		
	add_action('wp_ajax_cdp_banners_list', 'cdp_banners_list_ajax' ); 		
	add_action('wp_ajax_cdp_banners_delete', 'cdp_banners_delete_ajax' ); 		
	
}


////////////////////
// Add rules 
//  to control navigation of the comparator application that is being displayed in a page / post via shortcode
//
	register_activation_hook( __FILE__, 'cdp_activate' );

	function cdp_activate() {
		cdp_add_rules();
		flush_rewrite_rules(); // expensive action... thus only on activation
	}
	// Flush when deactivated
	register_deactivation_hook( __FILE__, 'cdp_deactivate' );

	function cdp_deactivate() {
		flush_rewrite_rules();
	}

	// Add the rewrite rule
	add_action( 'admin_init', 'cdp_add_rules' );// not on 'init' - too expensive

	// Add the query vars so that WP recognizes them
	add_filter( 'query_vars', 'cdp_add_query_var' );
	function cdp_add_query_var( $vars ) {
		$vars[] = 'cdp_path_query'; // matches with cdp_add_rules rules
		return $vars;
	}

// Add rules - END
////////////////////

/**************************************************************************************/
//END 	Admin Functions
/**************************************************************************************/

// Register a new shortcode: [cdp_buzzea], once that code is found on a page cdp_display_comparateur is invoked
add_shortcode(CDP_PLUGIN_SHORTCODE, 'cdp_display_comparateur'); 

// If query var product as a value, include product listing
//add_action( 'template_redirect', 'cdp_product_jump' );

/* technique from: http://beerpla.net/2010/01/13/wordpress-plugin-development-how-to-include-css-and-javascript-conditionally-and-only-when-needed-by-the-posts/ */
add_filter('the_posts', 'cdp_conditionally_add_scripts_and_styles'); // the_posts gets triggered before wp_head

/* 
 * function enqueues scripts only when shortcode found
 * takes: $posts
 */
if (!function_exists("cdp_conditionally_add_scripts_and_styles")) {
	function cdp_conditionally_add_scripts_and_styles($posts){
		if (empty($posts)) return $posts;
	 
		$shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
		foreach ($posts as $post) {
			if (stripos($post->post_content, '[cdp_buzzea]') !== false) {
				$shortcode_found = true; // success!
				break;
			}
		}
	 
		if ($shortcode_found) {
			// this page / post has a CDP shortcode - act on it and either redirect to the URL or show the HTML supplied	
			// If query URL is not correct and we receive a better one, redirect
			add_action( 'template_redirect', 'cdp_get_comparateur', 10 );
			
			//remove canonical redirection of categorized posts - conflicts with add_rewrite_rules and stops them working
			remove_filter('template_redirect', 'redirect_canonical');
			
			//load latest jQuery
			if( wp_script_is( 'jquery', 'done' ) || wp_script_is( 'jquery', 'registered' )) {
				//do nothing
			} else {
				//load JQuery	
				wp_deregister_script('jquery');
				if (CDP_JQUERY_FILE != ''){ wp_register_script('jquery', CDP_JQUERY_FILE); }
				wp_enqueue_script('jquery');		
			}
			
			//load latest jQuery-UI
			if( wp_script_is( 'jquery-ui', 'done' ) || wp_script_is( 'jquery-ui', 'registered' )) {
				//do nothing
			} else {
				//load JQuery-UI	
				wp_deregister_script('jquery');
				if (CDP_JQUERY_FILE != ''){ wp_register_script('jquery', CDP_JQUERY_FILE); }
				wp_enqueue_script('jquery');
				
				wp_deregister_script('jquery-ui');
				if (CDP_JQUERY_UI_FILE != ''){ wp_register_script('jquery-ui', CDP_JQUERY_UI_FILE); } 
				wp_enqueue_script('jquery-ui');		
			}
			
			if(  (wp_script_is( 'jquery', 'done' ) || wp_script_is( 'jquery', 'registered' )) && (wp_script_is( 'jquery-ui', 'done' ) || wp_script_is( 'jquery-ui', 'registered')) ) {
				//have JQ and JQ-UI
				//load functions 
				wp_enqueue_script(CDP_PLUGIN_NAME.'_functions',  plugin_dir_url( __FILE__ ) . CDP_JS_FUNCTIONS, '', CDP_PLUGIN_VERSION, 'all'); //search
				wp_enqueue_style(CDP_PLUGIN_NAME,  CDP_JQUERY_UI_CSS_FILE);
			}
			
			/* get graphic id and choose correct style file*/
			$cdp_style_file_locn = CDP_STYLE_FILE_DEFAULT;
			$cdp_theme_details 		= get_option( 'cdp_theme_settings');
			if (isset($cdp_theme_details['theme_option_graphic'])){
				$cdp_graphic_id = intval($cdp_theme_details['theme_option_graphic']);
				$cdp_style_file_locn = CDP_STYLE_FILE .$cdp_graphic_id. '.css'; // i.e. css/cdp_style_1.css
			}
			
			/* load styles/scripts: cdp_style.css, lightbox.css, lightbox.js  */
			wp_enqueue_style(CDP_PLUGIN_NAME.'_style',  plugin_dir_url( __FILE__ ) . $cdp_style_file_locn, '', CDP_PLUGIN_VERSION, 'all');
			wp_enqueue_style(CDP_PLUGIN_NAME.'_lightbox_style',  plugin_dir_url( __FILE__ ) . CDP_LIGHTBOX_CSS_FILE, '', CDP_PLUGIN_VERSION, 'all');
			wp_enqueue_script(CDP_PLUGIN_NAME.'_lightbox_js',  plugin_dir_url( __FILE__ ) . CDP_LIGHTBOX_JS_FILE, '', CDP_PLUGIN_VERSION, 'all');
			
		}
		
		else {
			// no shortcode found
			
			// add allowed banners after content
			add_filter( 'the_content', 'cdp_banners_after_content' );	
			
		}//else 
	 
		return $posts;
	}/* end function*/ 
}/*end if*/

//add menu link to the area just beneath plugin name on Extensions page
if (!function_exists("cdp_plugin_add_settings_link")) {
	function cdp_plugin_add_settings_link($links) {
		$settings_link = '<a href="options-general.php?page=cdp_plugin_options">Settings</a>';
		array_push( $links, $settings_link );
		return $links;
	}/*end function*/
}/*end if*/

$plugin = plugin_basename(__FILE__);
add_filter( "plugin_action_links_$plugin", 'cdp_plugin_add_settings_link' );


///////////////////////////////////////////////////////////////////////////////
// adds the contextuelle editor to admin of pages / posts
add_action( 'admin_init', 'cdp_add_meta_box_contextuelle' );

/* end file */ 
?>