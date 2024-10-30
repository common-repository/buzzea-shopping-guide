<?php 
/*
Description: This file contains the admin tabs functions for the Buzzea WP guide shopping
It displays the admin tabs and sets up their functionality for saving choices
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/
//http://theme.fm/2011/10/how-to-create-tabs-with-the-settings-api-in-wordpress-2590/
class admin_tabs { 
	
	/*
	 * For easier overriding we declared the keys
	 * here as well as our tabs array which is populated
	 * when registering settings
	 */
	private $general_settings_key 	= 'cdp_general_settings';
	private $theme_settings_key 	= 'cdp_theme_settings';
	private $personal_settings_key 	= 'cdp_personal_settings';
	private $behaviour_settings_key = 'cdp_behaviour_settings';
	private $category_settings_key 	= 'cdp_category_settings';
	private $article_settings_key 	= 'cdp_article_settings';
	private $widget_settings_key 	= 'cdp_widget_settings';
	private $plugin_settings_tabs 	= array();
	private $install_status 		= 0;

	private $plugin_options_key 	= 'cdp_plugin_options';

	

	/*
	 * Fired during plugins_loaded (very very early),
	 * so don't miss-use this, only actions and filters,
	 * current ones speak for themselves.
	 */
	function __construct() {
		add_action( 'init', array( &$this, 'load_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_theme_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_personal_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_behaviour_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_category_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_article_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_widget_settings' ) );
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
		
		// sepwork
		if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options' ){ 
			$this->plugin_options_key = 'cdp_plugin_options';
		} else if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_guide' ){ 
			$this->plugin_options_key = 'cdp_plugin_options_guide';
		} else if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_banner' ){ 
			$this->plugin_options_key = 'cdp_plugin_options_banner';
		}
		
		
	}//func
	
	/*
	 * Loads both the general and advanced settings from
	 * the database into their respective arrays. Uses
	 * array_merge to merge with default values if they're
	 * missing.
	 */
	function load_settings() {
		
		//$_SESSION['cdp_settings_loaded'] = TRUE;
		
		if (is_admin()){
			//check install status
			//echo 'checking status';
			$this->install_status = cdp_install_status(); 
			
		}
		
		// get options
		$this->general_settings 	= (array) get_option( $this->general_settings_key );
		$this->theme_settings 		= (array) get_option( $this->theme_settings_key );
		$this->personal_settings 	= (array) get_option( $this->personal_settings_key );
		$this->behaviour_settings 	= (array) get_option( $this->behaviour_settings_key ); //comportement
		$this->category_settings 	= (array) get_option( $this->category_settings_key );
		$this->article_settings 	= (array) get_option( $this->article_settings_key );
		$this->widget_settings 		= (array) get_option( $this->widget_settings_key );
		
		//pr($this->widget_settings);	
		
		// get defaults
		$theme_defaults 	= $GLOBALS['theme_defaults'];
		$personal_defaults 	= $GLOBALS['personalisation_defaults'];
		$article_defaults 	= $GLOBALS['article_defaults'];
		$general_defaults 	= array( 'general_option_password' => '');
		$category_defaults 	= array();
		$behaviour_defaults = $GLOBALS['behaviour_defaults'];
		$widget_defaults 	= $GLOBALS['widget_defaults'];
		
		// Merge with defaults
		$this->general_settings = array_merge( $general_defaults, $this->general_settings );

		//update/add if not there already
		if ((isset($this->general_settings[0])) && $this->general_settings[0]== FALSE) {
			//no saved personal_settings
			update_option('cdp_general_settings', $general_defaults);
		}
		
		//update/add if not there already
		if ((isset($this->theme_settings[0])) && $this->theme_settings[0]== FALSE) {
			//no saved theme_settings
			update_option('cdp_theme_settings', $theme_defaults);
		}
		
		//update/add if not there already
		if ((isset($this->personal_settings[0])) && $this->personal_settings[0]== FALSE) {
			//no saved personal_settings
			update_option('cdp_personal_settings', $personal_defaults);
		}
		
		//update/add if not there already
		if ((isset($this->behaviour_settings[0])) && $this->behaviour_settings[0]== FALSE) {
			//no saved personal_settings
			update_option('cdp_behaviour_settings', $behaviour_defaults);
		}

		//update/add if not there already
		if ((isset($this->category_settings[0])) && $this->category_settings[0]== FALSE) {
			//no saved category_settings
			update_option('cdp_category_settings', $category_defaults);
		}
		
		//update/add if not there already
		if ((isset($this->article_settings[0])) && $this->article_settings[0]== FALSE) {
			//no saved category_settings
			update_option('cdp_article_settings', $article_defaults);
		}
		
		//update/add if not there already
		/*if ((isset($this->widget_settings[0])) && $this->widget_settings[0]== FALSE) {
			//no saved category_settings
			//update_option('cdp_widget_settings', $widget_defaults); 
		}*/
		
		//merge settings
		$this->theme_settings 		= array_merge($theme_defaults , $this->theme_settings );
		$this->personal_settings 	= array_merge($personal_defaults , $this->personal_settings );
		$this->behaviour_settings 	= array_merge($behaviour_defaults , $this->behaviour_settings );
		$this->article_settings 	= array_merge($article_defaults , $this->article_settings );
		//OFF $this->widget_settings 		= array_merge($widget_defaults , $this->widget_settings ); 
		
		// overide whats there after an error 
		// with the data that was sent and gave the error (original input)
		if (isset($_GET['cdp_theme_settings_msg']) ){
			
			if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
				//do nothing - don't want this after successful update
			}
			else {
				//used to repopulate form with the 'bad' data
				
				$msg = $_GET['cdp_theme_settings_msg'];
				
				if (isset($_GET['personal_option_color_text'])){ 			$this->theme_settings["personal_option_color_text"]=$_GET['personal_option_color_text'];	}
				if (isset($_GET['personal_option_color_links'])){ 			$this->theme_settings["personal_option_color_links"]=$_GET['personal_option_color_links'];	}
				if (isset($_GET['personal_option_color_theme'])){ 			$this->theme_settings["personal_option_color_theme"]=$_GET['personal_option_color_theme'];	}
				if (isset($_GET['personal_option_color_background'])){ 		$this->theme_settings["personal_option_color_background"]=$_GET['personal_option_color_background'];	}
				if (isset($_GET['personal_option_color_theme2'])){ 			$this->theme_settings["personal_option_color_theme2"]=$_GET['personal_option_color_theme2'];	}
				if (isset($_GET['personal_option_color_links2'])){ 			$this->theme_settings["personal_option_color_links2"]=$_GET['personal_option_color_links2'];	}
				if (isset($_GET['personal_option_color_text2'])){ 			$this->theme_settings["personal_option_color_text2"]=$_GET['personal_option_color_text2'];	}
				if (isset($_GET['personal_option_color_theme3'])){ 			$this->theme_settings["personal_option_color_theme3"]=$_GET['personal_option_color_theme3'];	}
				if (isset($_GET['personal_option_color_links3'])){ 			$this->theme_settings["personal_option_color_links3"]=$_GET['personal_option_color_links3'];	}
				if (isset($_GET['personal_option_color_text3'])){ 			$this->theme_settings["personal_option_color_text3"]=$_GET['personal_option_color_text3'];	}
				if (isset($_GET['personal_option_color_theme4'])){ 			$this->theme_settings["personal_option_color_theme4"]=$_GET['personal_option_color_theme4'];	}
				if (isset($_GET['personal_option_color_links4'])){ 			$this->theme_settings["personal_option_color_links4"]=$_GET['personal_option_color_links4'];	}
				if (isset($_GET['personal_option_color_text4'])){ 			$this->theme_settings["personal_option_color_text4"]=$_GET['personal_option_color_text4'];	}
			}
		}
		
		// overide what's there after an error 
		// with the data that was sent and gave the error (original input)
		if (isset($_GET['cdp_personal_settings_msg']) ){
			
			if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
				//do nothing - don't want this after successful update
			}
			else {
				//used to repopulate form with the 'bad' data
				
				$msg = $_GET['cdp_personal_settings_msg'];
				
				if (isset($_GET['personal_option_libelle'])){ 					$this->personal_settings["personal_option_libelle"]=$_GET['personal_option_libelle'];	}
				if (isset($_GET['personal_option_libelle_comparateur'])){ 		$this->personal_settings["personal_option_libelle_comparateur"]=$_GET['personal_option_libelle_comparateur'];	}
				
				if (isset($_GET['theme1_personal_option_color_text'])){ 		$this->personal_settings["theme1_personal_option_color_text"]	=	$_GET['theme1_personal_option_color_text'];	}
				if (isset($_GET['theme1_personal_option_color_links'])){ 		$this->personal_settings["theme1_personal_option_color_links"]	=	$_GET['theme1_personal_option_color_links'];	}
				if (isset($_GET['theme1_personal_option_color_theme'])){ 		$this->personal_settings["theme1_personal_option_color_theme"]	=	$_GET['theme1_personal_option_color_theme'];	}
				if (isset($_GET['theme1_personal_option_color_background'])){ 	$this->personal_settings["theme1_personal_option_color_background"]=$_GET['theme1_personal_option_color_background'];	}
				if (isset($_GET['theme1_personal_option_color_theme2'])){ 		$this->personal_settings["theme1_personal_option_color_theme2"]	=	$_GET['theme1_personal_option_color_theme2'];	}
				if (isset($_GET['theme1_personal_option_color_links2'])){ 		$this->personal_settings["theme1_personal_option_color_links2"]	=	$_GET['theme1_personal_option_color_links2'];	}
				if (isset($_GET['theme1_personal_option_color_text2'])){ 		$this->personal_settings["theme1_personal_option_color_text2"]	=	$_GET['theme1_personal_option_color_text2'];	}
				if (isset($_GET['theme1_personal_option_color_theme3'])){ 		$this->personal_settings["theme1_personal_option_color_theme3"]	=	$_GET['theme1_personal_option_color_theme3'];	}
				if (isset($_GET['theme1_personal_option_color_links3'])){ 		$this->personal_settings["theme1_personal_option_color_links3"]	=	$_GET['theme1_personal_option_color_links3'];	}
				if (isset($_GET['theme1_personal_option_color_text3'])){ 		$this->personal_settings["theme1_personal_option_color_text3"]	=	$_GET['theme1_personal_option_color_text3'];	}
				if (isset($_GET['theme1_personal_option_color_theme4'])){ 		$this->personal_settings["theme1_personal_option_color_theme4"]	=	$_GET['theme1_personal_option_color_theme4'];	}
				if (isset($_GET['theme1_personal_option_color_links4'])){ 		$this->personal_settings["theme1_personal_option_color_links4"]	=	$_GET['theme1_personal_option_color_links4'];	}
				if (isset($_GET['theme1_personal_option_color_text4'])){ 		$this->personal_settings["theme1_personal_option_color_text4"]	=	$_GET['theme1_personal_option_color_text4'];	}
				
				if (isset($_GET['theme2_personal_option_color_text'])){ 		$this->personal_settings["theme2_personal_option_color_text"]	=	$_GET['theme2_personal_option_color_text'];	}
				if (isset($_GET['theme2_personal_option_color_links'])){ 		$this->personal_settings["theme2_personal_option_color_links"]	=	$_GET['theme2_personal_option_color_links'];	}
				if (isset($_GET['theme2_personal_option_color_theme'])){ 		$this->personal_settings["theme2_personal_option_color_theme"]	=	$_GET['theme2_personal_option_color_theme'];	}
				if (isset($_GET['theme2_personal_option_color_background'])){ 	$this->personal_settings["theme2_personal_option_color_background"]=$_GET['theme2_personal_option_color_background'];	}
				if (isset($_GET['theme2_personal_option_color_theme2'])){ 		$this->personal_settings["theme2_personal_option_color_theme2"]	=	$_GET['theme2_personal_option_color_theme2'];	}
				if (isset($_GET['theme2_personal_option_color_links2'])){ 		$this->personal_settings["theme2_personal_option_color_links2"]	=	$_GET['theme2_personal_option_color_links2'];	}
				if (isset($_GET['theme2_personal_option_color_text2'])){ 		$this->personal_settings["theme2_personal_option_color_text2"]	=	$_GET['theme2_personal_option_color_text2'];	}
				if (isset($_GET['theme2_personal_option_color_theme3'])){ 		$this->personal_settings["theme2_personal_option_color_theme3"]	=	$_GET['theme2_personal_option_color_theme3'];	}
				if (isset($_GET['theme2_personal_option_color_links3'])){ 		$this->personal_settings["theme2_personal_option_color_links3"]	=	$_GET['theme2_personal_option_color_links3'];	}
				if (isset($_GET['theme2_personal_option_color_text3'])){ 		$this->personal_settings["theme2_personal_option_color_text3"]	=	$_GET['theme2_personal_option_color_text3'];	}
				if (isset($_GET['theme2_personal_option_color_theme4'])){ 		$this->personal_settings["theme2_personal_option_color_theme4"]	=	$_GET['theme2_personal_option_color_theme4'];	}
				if (isset($_GET['theme2_personal_option_color_links4'])){ 		$this->personal_settings["theme2_personal_option_color_links4"]	=	$_GET['theme2_personal_option_color_links4'];	}
				if (isset($_GET['theme2_personal_option_color_text4'])){ 		$this->personal_settings["theme2_personal_option_color_text4"]	=	$_GET['theme2_personal_option_color_text4'];	}
			}
		}
		
		// overide what's there after an error 
		// with the data that was sent and gave the error (original input)
		if (isset($_GET['cdp_behaviour_settings_msg']) ){
			
			if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
				//do nothing - don't want this after successful update
			}
			else {
				//used to repopulate form with the 'bad' data
				
				$msg = $_GET['cdp_behaviour_settings_msg'];
			
				if (isset($_GET['personal_option_items_per_page'])){ 			$this->behaviour_settings["personal_option_items_per_page"]	=$_GET['personal_option_items_per_page'];	}
				if (isset($_GET['personal_option_items_per_row'])){ 			$this->behaviour_settings["personal_option_items_per_row"] 	=$_GET['personal_option_items_per_row'];	}
				if (isset($_GET['personal_option_cdc_choice'])){ 				$this->behaviour_settings["personal_option_items_per_row"] 	=$_GET['personal_option_cdc_choice'];	}
				
			}
		}

		$this->category_settings = array_merge( array(
			'category_option' => ''
		), $this->category_settings );
		
		$this->article_settings = array_merge( array(
			'article_option' => ''
		), $this->article_settings );
		
	}
	
	/*
	 * Registers the general settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_general_settings() {
		
		$this->plugin_settings_tabs[$this->general_settings_key] = __('Général', CDP_PLUGIN_NAME );
		register_setting( $this->general_settings_key, $this->general_settings_key, 'cdp_validate_general_options' ); 
		if ($this->install_status == 0){
			/************************************************/
			/*		subscribed but NOT Fully Installed		*/
			/************************************************/
			add_settings_section( 'section_general', __('Installation : Options Générales', CDP_PLUGIN_NAME ), array( &$this, 'section_general_desc' ), $this->general_settings_key );
			//add_settings_field( 'general_option_data', __('<strong></strong>'), array( &$this, 'field_general_option_data_install' ), $this->general_settings_key, 'section_general' );
			add_settings_field( 'general_option_password', '', array( &$this, 'field_general_option_password' ), $this->general_settings_key, 'section_general' );
			//not shown again once install completed ok	
		} else {
			add_settings_section( 'section_general', __('Options Générales', CDP_PLUGIN_NAME ), array( &$this, 'section_general_desc' ), $this->general_settings_key );
			add_settings_field( 'general_option_data', __('<strong></strong>', CDP_PLUGIN_NAME ), array( &$this, 'field_general_option_data' ), $this->general_settings_key, 'section_general' );
		}
	}
	
	/*
	 * Registers the personal settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_theme_settings() {
		
		// what tab to mark as active?
		if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_guide' ){ 
			$tab = 'cdp_theme_settings';
		} else { 
			$tab = FALSE;
		}
		
		$fieldlabel1 = __('Couleur des textes (libellé catégorie, Guide Shopping libellé en haut à droite, bouton "Voir L\'offre" , bouton "Voir l\'offre la plus économique")' , CDP_PLUGIN_NAME );
		$fieldlabel2 = __('Couleur des liens (libellé Sous-catégories, Prix page catégorie)', CDP_PLUGIN_NAME );
		$fieldlabel3 = __('Couleur du thème (fond de la boite de recherche, fond du libellé de la catégorie, h1, fond des boutons, Articles H1, libellé Article page catégorie, bordure tableau Marchand, libellé article marchand , article marchand prix)', CDP_PLUGIN_NAME );
		$fieldlabel4 = __('Couleur du fond (fond principal)', CDP_PLUGIN_NAME );
		
		$fieldlabel5 = __('Couleur du thème 2 (fond du footer, fond de la ligne sous la boîte de recherche, bordure sur \'trier par\')', CDP_PLUGIN_NAME );
		$fieldlabel6 = __('Couleur des liens 2(fil d\'Ariane, liens du footer et droits d\'auteur)', CDP_PLUGIN_NAME );
		$fieldlabel7 = __('Couleur des textes 2 (\'Resultats 1 à 10 sur 20\', \'Trier par\', libellé fiche technique, Recherche texte, Trier)', CDP_PLUGIN_NAME );
		
		$fieldlabel8 = __('Couleur du thème 3 (fond d\'articles disponibles) ', CDP_PLUGIN_NAME );
		$fieldlabel9 = __('Couleur des textes 3 (textes/liens d\'articles disponible)', CDP_PLUGIN_NAME );
		$fieldlabel10 = __('Couleur des liens 3 (Nombre de sites marchand)', CDP_PLUGIN_NAME );
			
		$fieldlabel11 = __('Couleur du thème 4 (fond fiche technique, bordure)', CDP_PLUGIN_NAME );
		$fieldlabel12 = __('Couleur des textes 4 (texte fiche technique)', CDP_PLUGIN_NAME );
		$fieldlabel13 = __('Couleur des liens 4 (libellé des groupes de fiches techniques, valeurs de caractéristiques)', CDP_PLUGIN_NAME );
		
		$this->plugin_settings_tabs[$this->theme_settings_key] = __('Thème', CDP_PLUGIN_NAME );
		
		register_setting( $this->theme_settings_key, $this->theme_settings_key, 'cdp_validate_theme_options');//array( &$this, 'validate_plugin_options' )
		add_settings_section( 'section_theme', __('Options de Théme', CDP_PLUGIN_NAME ), array( &$this, 'section_theme_desc' ), $this->theme_settings_key );
		add_settings_field( 'theme_option', __('', CDP_PLUGIN_NAME ), array( &$this, 'field_theme_option' ), $this->theme_settings_key, 'section_theme', array( 'class' => 'giveitalash' ) );
		
	}/*func*/
	
	/*
	 * Registers the personal settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_personal_settings() {
		$this->plugin_settings_tabs[$this->personal_settings_key] = __('Personnalisation', CDP_PLUGIN_NAME );
		
		register_setting( $this->personal_settings_key, $this->personal_settings_key, 'cdp_validate_personal_options');//array( &$this, 'validate_plugin_options' )
		add_settings_section( 'section_personal', __('Options de Personnalisation', CDP_PLUGIN_NAME ), array( &$this, 'section_personal_desc' ), $this->personal_settings_key );
		add_settings_field( 'personal_option', __('', CDP_PLUGIN_NAME ), array( &$this, 'field_personal_option' ), $this->personal_settings_key, 'section_personal', array( 'class' => 'giveitalash' ) );
	}/*func*/
	
	/*
	 * Registers the behaviour settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_behaviour_settings() {
		
		$this->plugin_settings_tabs[$this->behaviour_settings_key] = __('Comportement', CDP_PLUGIN_NAME );
		
		register_setting( $this->behaviour_settings_key, $this->behaviour_settings_key, 'cdp_validate_behaviour_options');//array( &$this, 'validate_plugin_options' )
		add_settings_section( 'section_behaviour', __('Options de Comportement', CDP_PLUGIN_NAME ), array( &$this, 'section_behaviour_desc' ), $this->behaviour_settings_key );
		add_settings_field( 'behaviour_option', __('', CDP_PLUGIN_NAME ), array( &$this, 'field_behaviour_option' ), $this->behaviour_settings_key, 'section_behaviour', array( 'class' => 'giveitalash' ) );
	}/*func*/
	
	/*
	 * Registers the category settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_category_settings() {
		$this->plugin_settings_tabs[$this->category_settings_key] = __('Rayons', CDP_PLUGIN_NAME );
		register_setting( $this->category_settings_key, $this->category_settings_key, 'cdp_validate_category_options' );
		add_settings_section( 'section_category', __('Rayons en Sélection', CDP_PLUGIN_NAME ), array( &$this, 'section_category_desc' ), $this->category_settings_key );
	}/*func*/
	
	/*
	 * Registers the article settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_article_settings() {
		$this->plugin_settings_tabs[$this->article_settings_key] = __('Sélections', CDP_PLUGIN_NAME );
		register_setting( $this->article_settings_key, $this->article_settings_key, 'cdp_validate_article_options' ); 
		//'cdp_validate_article_options' = callback function after registration
		add_settings_section( 'section_article', __('Articles en Sélection', CDP_PLUGIN_NAME ), array( &$this, 'section_article_desc' ), $this->article_settings_key );
	}/*func*/
	
	/*
	 * Registers the widget settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_widget_settings() {
		
		// what tab to mark as active?
		if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_banner' ){ 
			$tab = 'cdp_widget_settings';
		} else { 
			$tab = FALSE;
		}
		
		// if widget tab
		if ( $tab == 'cdp_widget_settings'){
			
			// need domaine id
			$install_details = cdp_get_install_details();
			
			// want domaineid to continue (getting null when just installing the plugin)
			// wait until domaineid
			if (isset($install_details['domaineid']) && is_numeric($install_details['domaineid'])){
			
				/*************************************/
				// 	Pre Transmit Widget CDP settings
				/*************************************/
				
				//removed... see older versions
				
				/****************************************/
				// 	END Pre Transmit Widget CDP settings
				/****************************************/
			}//if have domaineid
		}//if tab
		
		$this->plugin_settings_tabs[$this->widget_settings_key] = __('Bannières', CDP_PLUGIN_NAME );
		register_setting( $this->widget_settings_key, $this->widget_settings_key, 'cdp_validate_widget_options' ); 

		// 'cdp_validate_widget_options' = callback function after registration
		add_settings_section( 'section_widget', __('Paramètres des Bannières', CDP_PLUGIN_NAME ), array( &$this, 'section_widget_desc' ), $this->widget_settings_key );
		
		// add_settings_field( $id, $title, $callback, $page, $section, $args ); 
		add_settings_field( 'widget_option', __('', CDP_PLUGIN_NAME ), array( &$this, 'field_widget_option' ), $this->widget_settings_key, 'section_widget', 
			array('label_for'=>'cdp_widget_settings', ) );
		
	}/*func*/
	
	/*
	 * The following methods provide descriptions
	 * for their respective sections, used as callbacks
	 * with add_settings_section
	 */
	function section_general_desc() {
		if ($this->install_status == 0){
			/************************************************/
			/*		subscribed but NOT Fully Installed		*/
			/************************************************/
			echo __('Pour terminer l\'installation veuillez renseigner ici le mot de passe disponible sur votre interface Buzzea', CDP_PLUGIN_NAME );
		} else {
			echo __('Vous pouvez voir les paramètres de base ici.', CDP_PLUGIN_NAME );
		}
	}
	function section_theme_desc() { echo __('Vous pouvez choisir parmi les options ci-dessous pour changer votre thème de votre guide shopping.', CDP_PLUGIN_NAME ); 
	 }
	function section_personal_desc() { echo __('Vous pouvez choisir parmi les options ci-dessous pour personnaliser votre guide shopping.', CDP_PLUGIN_NAME );
	 }
	function section_behaviour_desc() { echo __('Vous pouvez choisir parmi les options ci-dessous pour définir le comportement de votre guide shopping.', CDP_PLUGIN_NAME );
	 }
	function section_category_desc() { echo __('Vous pouvez afficher tous les rayons disponibles ou uniquement ceux qui vous semblent pertinents avec votre ligne &eacute;ditoriale.', CDP_PLUGIN_NAME );
	}
	function section_article_desc() { echo __('Vous pouvez mettre en avant les produits qui vous semblent les plus adapt&eacute;s &agrave; votre lectorat.', CDP_PLUGIN_NAME ); echo '<br />';
	echo __('Vous pouvez choisir des articles dans les catégories ci-dessous ou rechercher des articles avec la fonction de recherche.', CDP_PLUGIN_NAME );
	}
	
	function section_widget_desc() { 
	
		$widgets_url = admin_url( 'widgets.php');
		
		echo __('Vous pouvez choisir parmi les options ci-dessous pour personnaliser vos bannières pour votre guide shopping.', CDP_PLUGIN_NAME );
		echo '<br />';
		echo __('Quand vous avez terminé vous pouvez activer vos bannières dans', CDP_PLUGIN_NAME ); echo '<strong>&nbsp;';
		echo __('Appearance', CDP_PLUGIN_NAME );echo '</strong> > <strong><a href="'.$widgets_url.'">';
		echo __('Widgets', CDP_PLUGIN_NAME );echo'</a></strong>.<br />';
		echo __('Ensuite, faites glisser «Buzzea Bannière de Shopping... » dans la zone dans laquelle vous souhaitez voir apparaître le carrousel.', CDP_PLUGIN_NAME ); echo '<br />';		
		echo __('Si votre thème ne supporte pas les widgets, vous pouvez copier/coller le code pour vos bannières et le mettre dans votre thème manuellement.', CDP_PLUGIN_NAME ); 
	}

	/*
	 * Personal Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_general_option_password() {
		?>
		<table cellpadding="0" cellspacing="0">
        <tr><td>Mot de passe du Comparateur</td><td><input type="password" name="<?php echo $this->general_settings_key; ?>[general_option_password]" value="<?php if (isset ($this->general_settings['general_option_password'])) echo esc_attr( $this->general_settings['general_option_password'] ); ?>" /></td>
        </tr></table>
		<?php
	}
	
	/*
	 * General Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_general_option_data() {
				// get menu choice
				if ( $this->general_settings['general_option_menu_choice'] == 1){
					$cdc_menu_choice_checked_yes = 'checked=""';
					$cdc_menu_choice_checked_no  = '';
				} else {
					$cdc_menu_choice_checked_yes = '';
					$cdc_menu_choice_checked_no  = 'checked=""';
				}
			?>
            <table cellpadding="0" cellspacing="0">
                
                <tr>
                    <td valign="top"><strong><?php echo __('Paramètres de votre guide shopping', CDP_PLUGIN_NAME ); ?></strong></td>
                    <td>
                    <?php
                    
                    $install_details = cdp_get_install_details();
                    //vd($install_details);
                    if (is_array($install_details)){
                        $cdp_install['url'] = esc_url($install_details['url']); 
                        $cdp_install['method'] = sanitize_text_field($install_details['method']); 
                        
                    }//if
                    else { 
                            $cdp_install ='';
                            if (isset($_GET['settings-updated']) && $_GET['settings-updated']!=true){
                            echo __("S'il vous plaît vérifier à nouveau votre mot de passe pour le plugin. Elle doit correspondre à un mot de passe que nous avons dans nos dossiers. <br />Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous téléphoner au numéro ci-dessous.", CDP_PLUGIN_NAME );
                            }
                            
                          }//else
                    
                    if (isset($cdp_install['url'])){ $cdp_install_url = $cdp_install['url']; } else { $cdp_install_url = ' - '; }
                    if (isset($cdp_install['method'])){ $cdp_install_method = $cdp_install['method']; } else { $cdp_install_method = ' - '; }
                    ?>
                    <?php 
						$cdp_buzzea_tel_local 	= CDP_BUZZEA_TEL_LOCAL;
						$cdp_buzzea_tel_global 	= CDP_BUZZEA_TEL_GLOBAL;
					?>
                    <?php /*?><p><strong><?php echo __('URL', CDP_PLUGIN_NAME );?>&nbsp;:</strong>&nbsp;<a href="<?php echo $cdp_install_url; ?>" target="_blank"><?php echo $cdp_install_url; ?></a></p><?php */?>
                    <p><strong><?php echo __("M&eacute;thode d'installation&nbsp;:", CDP_PLUGIN_NAME );?></strong> <?php echo $cdp_install_method; ?> - <?php echo __("Version", CDP_PLUGIN_NAME );?> <?php echo CDP_PLUGIN_VERSION; ?>
					<br /><br /><?php echo __("Pour modifier cette méthode d'installation, veuillez nous contacter aux coordonnées suivantes :", CDP_PLUGIN_NAME );?></p>
					<p style="padding-left: 10px;"><?php echo __("Depuis la France : ", CDP_PLUGIN_NAME ).$cdp_buzzea_tel_local; ?><br /><?php echo __("Depuis l'étranger : ", CDP_PLUGIN_NAME ).$cdp_buzzea_tel_global; ?><br /><?php  echo __("Horaires d'ouverture : de 10h00 à 12h00 et de 13h00 à 18h00 du Lundi au Vendredi (heure de Paris)", CDP_PLUGIN_NAME );?><br />
					<?php  echo __("Ou par mail :", CDP_PLUGIN_NAME );?>&nbsp;<a href=\"mailto:<?php echo CDP_BUZZEA_SUPPORT_EMAIL; ?>\"><?php echo CDP_BUZZEA_SUPPORT_EMAIL; ?></a></p>
                    <?php
                    if ($this->install_status == 1){
				   		//hide the save button
						?>
                        <script type="text/javascript">
                            jQuery(document).ready(function(jQuery) {
                                jQuery('#submits').hide();
                            });
                        </script>
						<?php 
				    } //if installed
                    ?>
                    </td>
                </tr>
            </table>
			<?php
	}/*func*/
	
	/*
	 * Theme Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_theme_option() {
		?>
         <script type="text/javascript">
	      /*
            Color reset
          */
            jQuery(document).ready(function() {
                 jQuery(".cdp_reset_colors_link").click(function() {  
                            
                    jQuery('#cdp_theme_settings_table_1 .color').each(function(index, thisElem) {
                        old_color = jQuery(thisElem).val();
                        new_color = jQuery(this).attr('alt');
                        
                        //swap colors
                        jQuery(thisElem).val(new_color);
                        jQuery(thisElem).attr('alt', old_color);
                        
                        //thisid = jQuery(thisElem).attr('id');
                        thisitem = jQuery(thisElem)[0]; /* In jQuery, to get the same result as document.getElementById, you can access the jQuery Object and get the first element in the object (Remember JavaScript objects act similar to associative arrays). */
                        var myPicker = new jscolor.color(thisitem, {});
                        //var myPicker = new jscolor.color(document.getElementById(thisid), {});
                        myPicker.fromString(new_color);  // now you can access API via 'myPicker' variable
                    });
                    
                    jQuery('#cdp_theme_settings_table_2 .color').each(function(index, thisElem) {
                        old_color = jQuery(thisElem).val();
                        new_color = jQuery(this).attr('alt');
                        //alert(old_color);
                        //swap colors
                        jQuery(thisElem).val(new_color);
                        jQuery(thisElem).attr('alt', old_color);
                        
                        //thisid = jQuery(thisElem).attr('id');
                        //thisitem = document.getElementById(thisid);
                        
                        thisitem = jQuery(thisElem)[0]; /* In jQuery, to get the same result as document.getElementById, you can access the jQuery Object and get the first element in the object (Remember JavaScript objects act similar to associative arrays). */
                        var myPicker2 = new jscolor.color(thisitem, {});
                        myPicker2.fromString(new_color);  // now you can access API via 'myPicker' variable
                    });
            
                    alert('<?php echo __('Maintenant vous pouvez enregistrer ces paramètres, ou cliquez sur "Reinitialiser défauts" à nouveau pour revenir aux réglages précédents', CDP_PLUGIN_NAME );  ?>'); 
                    
                    return true;
                  });  
            });	  
         </script>
         <table cellpadding="0" cellspacing="0" id="cdp_theme_table">
            
            <!-- Graphics -->
            <tr>
                <td width="250" valign="top" class="cdp_label"><strong><?php echo __('Thème Graphique', CDP_PLUGIN_NAME ); ?></strong></td>
                <td></td>
            </tr>
            
            <tr>
                <?php
                    if ( $this->theme_settings['theme_option_graphic'] == 1){
                        $theme1_checked = 'checked=""';
                        $theme2_checked = '';
                        $theme1_table_hidden = '';
                        $theme2_table_hidden = 'style="display:none;"';
                    } else {
                        $theme1_checked = '';
                        $theme2_checked = 'checked=""';
                        $theme1_table_hidden = 'style="display:none;"';
                        $theme2_table_hidden = '';
                    }
                ?>
                <td valign="top"><?php echo __('Vous pouvez choisir parmi :', CDP_PLUGIN_NAME ); ?></td>
                <td align="center">
                    <!-- thumb table -->
                    <table width="100%">
                        <tr>
                            <td align="center"><?php echo '<img width="250" src="'.CDP_IMG_URL_ADMIN.'cdp_theme_1.jpg" />'; ?></td>
                            <td align="center"><?php echo '<img width="250" src="'.CDP_IMG_URL_ADMIN.'cdp_theme_2.jpg" />'; ?></td>
                        </tr>
                        
                        <tr>
                            <td align="center">
                                <input type="radio" value="1" <?php echo $theme1_checked; ?> name="<?php echo $this->theme_settings_key; ?>[theme_option_graphic]">&nbsp;1
                            </td>
                            <td align="center">
                                <input type="radio" value="2" <?php echo $theme2_checked; ?> name="<?php echo $this->theme_settings_key; ?>[theme_option_graphic]">&nbsp;2
                            </td>
                        </tr>
                     </table>
                    <!-- end thumb table -->
                     
                     <a href="#" class="button-secondary cdp_preview_link" rel="<?php echo CDP_PREVI_URL; ?>"><?php echo __('Prévisualisation dans une nouvelle fenêtre', CDP_PLUGIN_NAME );?></a>
                     
                </td>        	
            </tr>
            
            <!-- Theme: Settings + Couleurs -->
            
            <tr>
                <td valign="top" class="cdp_label"><strong><?php echo __('Paramètres du thème', CDP_PLUGIN_NAME );?></strong></td>
                <td></td>
            </tr>
            
            <!-- Theme 1-->
             <tr>
                <td valign="top" class="cdp_label" colspan="2">
                    
                    <table cellspacing=0 cellpadding=0 id="cdp_theme_settings_table_1"<?php echo $theme1_table_hidden; ?>>
                        
                                <tr>
                                    <td valign="top" class="cdp_label"><strong><?php echo __('Theme 1', CDP_PLUGIN_NAME ); ?></strong></td>
                                    <td></td>
                                </tr>
                         <!-- Nombre d'articles -->
                                <tr>
                                    <td valign="top" class="cdp_label"><strong><?php echo __('Nombre d\'articles ', CDP_PLUGIN_NAME ); ?></strong></td>
                                    <td></td>
                                </tr>
                                
                                <tr>
                                    <td valign="top"><?php echo __('Nombre d\'articles par page :', CDP_PLUGIN_NAME ); ?></td>
                                    <td><input type="text" class="libelle_input_nombre" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_items_per_page]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_items_per_page'] ); ?>" /></td>        	
                                </tr>
                                
                                 <tr>
                                    <td valign="top"><?php echo __('Nombre d\'articles par ligne :', CDP_PLUGIN_NAME ); ?></td>
                                    <td><input type="text" class="libelle_input_nombre" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_items_per_row]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_items_per_row'] ); ?>" /></td>        	
                                </tr>
                                
                                 <tr>
                                    <td valign="top"><?php echo __('Nombre de catégories par ligne :', CDP_PLUGIN_NAME ); ?></td>
                                    <td><input type="text" class="libelle_input_nombre" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_cats_per_row]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_cats_per_row'] ); ?>" /></td>        	
                                </tr>
                                
                                <!-- spacer -->
                                <tr>
                                    <td colspan="2" style="border-bottom:1px solid #CACACC;"></td>
                                </tr><tr><td colspan="2"></td></tr>
                      
                        <!-- Couleurs: Theme 1 -->
                                    
                                    <tr>
                                        <td valign="top" class="cdp_label"><strong><?php echo __('Couleurs : ', CDP_PLUGIN_NAME ); ?></strong></td>
                                        <td></td>
                                    </tr>
                                
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des textes', CDP_PLUGIN_NAME ); ?></strong><br />
                                                          <span class="cdp_bullet">-&nbsp;<?php echo __('libellé catégorie', CDP_PLUGIN_NAME ); ?></span><br />
                                                          <span class="cdp_bullet">-&nbsp;<?php echo __('Guide Shopping libellé en haut à droite', CDP_PLUGIN_NAME ); ?></span><br />
                                                          <span class="cdp_bullet">-&nbsp;<?php echo __('bouton "Voir L\'offre"', CDP_PLUGIN_NAME ); ?></span><br />
                                                          <span class="cdp_bullet">-&nbsp;<?php echo __('bouton "Voir l\'offre la plus économique', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            <div style="float:left"><div style="float:left">#</div><input id="personal_option_color_text" type="text" style="float:left" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_text']; ?>"  name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_text]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_text'] ); ?>" /></div>    
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des liens', CDP_PLUGIN_NAME ); ?></strong><br />
                                                         <span class="cdp_bullet">-&nbsp;<?php echo __('libellé Sous-catégories', CDP_PLUGIN_NAME ); ?></span><br />
                                                         <span class="cdp_bullet">-&nbsp;<?php echo __('Prix page catégorie', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_links" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_links']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_links]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_links'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur du thème', CDP_PLUGIN_NAME ); ?></strong><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fond de la boite de recherche', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fond du libellé de la catégorie, h1, fond des boutons,', CDP_PLUGIN_NAME ); ?></span><br /> 
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('Articles H1, libellé Article page catégorie', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('bordure tableau marchand', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('libellé article marchand , article marchand prix', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_theme" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_theme']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_theme]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_theme'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur du fond', CDP_PLUGIN_NAME ); ?></strong> <br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fond principal', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_background" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_background']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_background]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_background'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur du thème 2 ', CDP_PLUGIN_NAME ); ?></strong><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fond du footer ', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fond de la ligne sous la boîte de recherche', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('bordure sur \'trier par\'', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_theme2" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_theme2']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_theme2]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_theme2'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des liens 2', CDP_PLUGIN_NAME ); ?></strong><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fil d\'Ariane', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('liens du footer et droits d\'auteur', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_links2" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_links2']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_links2]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_links2'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des textes 2', CDP_PLUGIN_NAME ); ?></strong> <br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('\'Resultats 1 à 10 sur 20\'', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('\'Trier par\'', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('libellé fiche technique', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('Recherche texte, Trier', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_text2" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_text2']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_text2]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_text2'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur du thème 3', CDP_PLUGIN_NAME ); ?></strong> <br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fond d\'articles disponibles', CDP_PLUGIN_NAME ); ?> </span></td>
                                        <td>
                                            #<input id="personal_option_color_theme3" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_theme3']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_theme3]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_theme3'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                     <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des textes 3', CDP_PLUGIN_NAME ); ?></strong><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('textes/liens d\'articles disponible', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_text3" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_text3']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_text3]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_text3'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des liens 3 ', CDP_PLUGIN_NAME ); ?></strong><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('Nombre de sites marchand', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_links3" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_links3']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_links3]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_links3'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur du thème 4', CDP_PLUGIN_NAME ); ?></strong> <br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('fond fiche technique', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('bordure', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_theme4" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_theme4']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_theme4]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_theme4'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des textes 4', CDP_PLUGIN_NAME ); ?></strong> <br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('texte fiche technique', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_text4" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_text4']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_text4]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_text4'] ); ?>" />
                                        </td>        	
                                    </tr>
                                    
                                    <tr>
                                        <td valign="top"><strong><?php echo __('Couleur des liens 4', CDP_PLUGIN_NAME ); ?></strong> <br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('libellé des groupes de fiches techniques', CDP_PLUGIN_NAME ); ?></span><br />
                                                 <span class="cdp_bullet">-&nbsp;<?php echo __('valeurs de caractéristiques', CDP_PLUGIN_NAME ); ?></span></td>
                                        <td>
                                            #<input id="personal_option_color_links4" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme1_personal_option_color_links4']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme1_personal_option_color_links4]" value="<?php echo esc_attr( $this->theme_settings['theme1_personal_option_color_links4'] ); ?>" />
                                        </td>        	
                                    </tr>
                        
                    </table>
                
               <!-- </td>
                
            </tr>-->
            <!-- Theme 1 end --> <!-- Theme 2-->

                <table cellspacing=0 cellpadding=0 id="cdp_theme_settings_table_2"<?php echo $theme2_table_hidden; ?>>
                            <tr>
                                <td valign="top" class="cdp_label"><strong><?php echo __('Theme 2', CDP_PLUGIN_NAME ); ?></strong></td>
                                <td></td>
                            </tr>
                    
                     <!-- Nombre d'articles -->
                            <tr>
                                <td valign="top" class="cdp_label"><strong><?php echo __('Nombre d\'articles', CDP_PLUGIN_NAME ); ?></strong></td>
                                <td></td>
                            </tr>
                            
                            <tr>
                                <td valign="top"><?php echo __('Nombre d\'articles par page :', CDP_PLUGIN_NAME ); ?></td>
                                <td><input type="text" class="libelle_input_nombre" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_items_per_page]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_items_per_page'] ); ?>" /></td>        	
                            </tr>
                            
                             <tr>
                                <td valign="top"><?php echo __('Nombre d\'articles par ligne :', CDP_PLUGIN_NAME ); ?></td>
                                <td><input type="text" class="libelle_input_nombre" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_items_per_row]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_items_per_row'] ); ?>" /></td>        	
                            </tr>
                            
                             <tr>
                                <td valign="top"><?php echo __('Nombre de catégories par ligne :', CDP_PLUGIN_NAME ); ?></td>
                                <td><input type="text" class="libelle_input_nombre" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_cats_per_row]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_cats_per_row'] ); ?>" /></td>        	
                            </tr>
                            
                            <!-- spacer -->
                            <tr>
                                <td colspan="2" style="border-bottom:1px solid #CACACC;"></td>
                            </tr><tr><td colspan="2"></td></tr>
                  
                    <!-- Couleurs: Theme 2 -->
                                
                                <tr>
                                    <td valign="top" class="cdp_label"><strong><?php echo __('Couleurs :', CDP_PLUGIN_NAME ); ?></strong></td>
                                    <td></td>
                                </tr>
                            
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des textes', CDP_PLUGIN_NAME ); ?></strong><br />
                                          <span class="cdp_bullet">-&nbsp;<?php echo __('libellé catégorie', CDP_PLUGIN_NAME ); ?></span><br />
                                          <span class="cdp_bullet">-&nbsp;<?php echo __('Guide Shopping libellé en haut à droite', CDP_PLUGIN_NAME ); ?></span><br />
                                          <span class="cdp_bullet">-&nbsp;<?php echo __('bouton "Voir L\'offre"', CDP_PLUGIN_NAME ); ?></span><br />
                                          <span class="cdp_bullet">-&nbsp;<?php echo __('bouton "Voir l\'offre la plus économique', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        <div style="float:left"><div style="float:left">#</div><input id="personal_option_color_text" type="text" style="float:left" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_text']; ?>"  name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_text]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_text'] ); ?>" /></div>    
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des liens', CDP_PLUGIN_NAME ); ?></strong><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('libellé Sous-catégories', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('Prix page catégorie', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_links" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_links']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_links]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_links'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur du thème', CDP_PLUGIN_NAME ); ?></strong><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('fond de la boite de recherche', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('fond du libellé de la catégorie, h1, fond des boutons,', CDP_PLUGIN_NAME ); ?></span><br /> 
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('Articles H1, libellé Article page catégorie', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('bordure tableau marchand', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('libellé article marchand , article marchand prix', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_theme" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_theme']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_theme]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_theme'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur du fond', CDP_PLUGIN_NAME ); ?></strong> <br />
					                     <span class="cdp_bullet">-&nbsp;<?php echo __('fond principal', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_background" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_background']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_background]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_background'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur du thème 2 ', CDP_PLUGIN_NAME ); ?></strong><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('fond du footer ', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('fond de la ligne sous la boîte de recherche', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('bordure sur \'trier par\'', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_theme2" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_theme2']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_theme2]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_theme2'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des liens 2', CDP_PLUGIN_NAME ); ?></strong><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('fil d\'Ariane', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('liens du footer et droits d\'auteur', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_links2" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_links2']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_links2]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_links2'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des textes 2', CDP_PLUGIN_NAME ); ?></strong> <br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('\'Resultats 1 à 10 sur 20\'', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('\'Trier par\'', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('libellé fiche technique', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('Recherche texte, Trier', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_text2" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_text2']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_text2]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_text2'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur du thème 3', CDP_PLUGIN_NAME ); ?></strong> <br />
                     					<span class="cdp_bullet">-&nbsp;<?php echo __('fond d\'articles disponibles', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_theme3" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_theme3']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_theme3]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_theme3'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                 <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des textes 3', CDP_PLUGIN_NAME ); ?></strong><br />
					                     <span class="cdp_bullet">-&nbsp;<?php echo __('textes/liens d\'articles disponible', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_text3" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_text3']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_text3]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_text3'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des liens 3', CDP_PLUGIN_NAME ); ?></strong><br />
                    					 <span class="cdp_bullet">-&nbsp;<?php echo __('Nombre de sites marchand', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_links3" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_links3']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_links3]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_links3'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur du thème 4', CDP_PLUGIN_NAME ); ?></strong> <br />
										 <span class="cdp_bullet">-&nbsp;<?php echo __('fond fiche technique', CDP_PLUGIN_NAME ); ?></span><br />
										 <span class="cdp_bullet">-&nbsp;<?php echo __('bordure', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_theme4" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_theme4']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_theme4]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_theme4'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des textes 4', CDP_PLUGIN_NAME ); ?></strong> <br />
                     					<span class="cdp_bullet">-&nbsp;<?php echo __('texte fiche technique', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_text4" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_text4']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_text4]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_text4'] ); ?>" />
                                    </td>        	
                                </tr>
                                
                                <tr>
                                    <td valign="top"><strong><?php echo __('Couleur des liens 4', CDP_PLUGIN_NAME ); ?></strong> <br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('libellé des groupes de fiches techniques', CDP_PLUGIN_NAME ); ?></span><br />
                                         <span class="cdp_bullet">-&nbsp;<?php echo __('valeurs de caractéristiques', CDP_PLUGIN_NAME ); ?></span></td>
                                    <td>
                                        #<input id="personal_option_color_links4" type="text" class="color" alt="<?php echo $GLOBALS['theme_defaults']['theme2_personal_option_color_links4']; ?>" name="<?php echo $this->theme_settings_key; ?>[theme2_personal_option_color_links4]" value="<?php echo esc_attr( $this->theme_settings['theme2_personal_option_color_links4'] ); ?>" />
                                    </td>        	
                                </tr>
                    
                </table>
            
            </td>
            
        </tr>
        <!-- Theme 2 end -->
            
        </table>
		<?php
	}
	
	/*
	 * Personal Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_personal_option() {
		?>
         <table cellpadding="0" cellspacing="0" id="cdp_personal_table">
         
         	<!-- Libelles -->
            <tr>
                <td valign="top" class="cdp_label"><strong><?php echo __('Les libellés', CDP_PLUGIN_NAME ); ?></strong></td>
                <td></td>
            </tr>
             <tr>
                <td valign="top" class="cdp_label"></td>
                <td><?php echo '<img width="450" src="'.CDP_IMG_URL_ADMIN.'cdp_aide_1.jpg" />';?></td>        	
            </tr>
            <tr>
                <td valign="top" class="cdp_label"><?php echo __('Libellé de votre site ', CDP_PLUGIN_NAME ); ?>:</td>
                <td><input type="text" class="libelle_input" name="<?php echo $this->personal_settings_key; ?>[personal_option_libelle]" value="<?php echo esc_attr( $this->personal_settings['personal_option_libelle'] ); ?>" />
                <span title="<?php echo __('Le nom de votre site', CDP_PLUGIN_NAME ); ?>" class="vtip">?</span>
                </td>        	
            </tr>
            
            <tr>
                <td valign="top"><?php echo __('Libellé de votre guide shopping ', CDP_PLUGIN_NAME ); ?>:</td>
                <td><input type="text" class="libelle_input" name="<?php echo $this->personal_settings_key; ?>[personal_option_libelle_comparateur]" value="<?php echo esc_attr( $this->personal_settings['personal_option_libelle_comparateur'] ); ?>" />
                <span title="<?php echo __('e.g. guide d\'achat de vêtements <br />ou<br />moto shopping guide ', CDP_PLUGIN_NAME ); ?>" class="vtip">?</span></td>        	
            </tr>
            
            <!-- spacer -->
            <tr>
                <td colspan="2" style="border-bottom:1px solid #CACACC;"></td>
            </tr><tr><td colspan="2"></td></tr>
           
        </table>
		<?php
	}
	
	/*
	 * Behaviour Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_behaviour_option() {
		?>
         <table cellpadding="0" cellspacing="0" id="cdp_behaviour_table">
            <!-- CDC : Recherche choices -->
            <tr>
                <td valign="top" class="cdp_label"><?php echo __('<strong>Coups de Coeur / Recherche</strong>', CDP_PLUGIN_NAME ); ?></td>
                <td></td>
            </tr>
            <tr>
            	<?php
				
					if ( $this->behaviour_settings['behaviour_option_cdc_only_choice'] == 1){
						$cdc_only_choice_checked_yes = 'checked=""';
						$cdc_only_choice_checked_no  = '';
					} else {
						$cdc_only_choice_checked_yes = '';
						$cdc_only_choice_checked_no  = 'checked=""';
					}
				?>
                <td valign="top"><?php echo __('Afficher uniquement les produits en sélection ?', CDP_PLUGIN_NAME ); ?></td>
                <td><input type="radio" value="1" <?php echo $cdc_only_choice_checked_yes; ?> name="<?php echo $this->behaviour_settings_key; ?>[behaviour_option_cdc_only_choice]"><? echo __('Oui', CDP_PLUGIN_NAME ); ?>&nbsp;&nbsp;<input type="radio" value="0" <?php echo $cdc_only_choice_checked_no; ?> name="<?php echo $this->behaviour_settings_key; ?>[behaviour_option_cdc_only_choice]"><? echo __('Non', CDP_PLUGIN_NAME ); ?> 
                	 <span title=" - <?php echo __("Si vous choisissez 'non', alors le guide shopping affichera tous les produits des rayons que vous aurez choisis dans l'onglet 'Rayons'", CDP_PLUGIN_NAME ); ?><br /><br />
    						- <?php echo __("Si vous choisissez 'oui', alors le guide shopping affichera uniquement les produits que vous aurez choisi dans l'onglet 'Sélection'", CDP_PLUGIN_NAME ); ?>" class="vtip">?</span>

                </td>        	
            </tr>
            <tr>
            	<?php
                	if ( $this->behaviour_settings['behaviour_option_cdc_choice'] == 1){
						$cdc_choice_checked_yes = 'checked=""';
						$cdc_choice_checked_no  = '';
					} else {
						$cdc_choice_checked_yes = '';
						$cdc_choice_checked_no  = 'checked=""';
					}
				?>
                <td valign="top"><?php echo __('Afficher les produits en sélection sur la 1ère page du guide shopping ?', CDP_PLUGIN_NAME ); ?></td>
                <td><input type="radio" value="1" <?php echo $cdc_choice_checked_yes; ?> name="<?php echo $this->behaviour_settings_key; ?>[behaviour_option_cdc_choice]"><? echo __('Oui', CDP_PLUGIN_NAME ); ?>&nbsp;&nbsp;<input type="radio" value="0" <?php echo $cdc_choice_checked_no; ?> name="<?php echo $this->behaviour_settings_key; ?>[behaviour_option_cdc_choice]"><? echo __('Non', CDP_PLUGIN_NAME ); ?> 
                     <span title=" - <?php echo __("Si vous choisissez 'non', alors le guide shopping affichera que les rayons sur la page d'accueil", CDP_PLUGIN_NAME ); ?><br /><br />
                      - <?php echo __("Si vous choisissez 'oui', alors le guide shopping affichera votre sélection de produits au dessus des rayons sur la page d'accueil", CDP_PLUGIN_NAME ); ?>" class="vtip">?</span>
                </td>        	
            </tr>
            <tr>
            	<?php
                	if ( $this->behaviour_settings['behaviour_option_recherche_choice'] == 1){
						$recherche_choice_checked_yes = 'checked=""';
						$recherche_choice_checked_no  = '';
					} else {
						$recherche_choice_checked_yes = '';
						$recherche_choice_checked_no  = 'checked=""';
					}
				?>
                <td valign="top"><?php echo __('Afficher tous les produits sur la page de recherche ?', CDP_PLUGIN_NAME ); ?></td>
                <td><input type="radio" value="1" <?php echo $recherche_choice_checked_yes; ?> name="<?php echo $this->behaviour_settings_key; ?>[behaviour_option_recherche_choice]"><? echo __('Oui', CDP_PLUGIN_NAME ); ?>&nbsp;&nbsp;<input type="radio" value="0" <?php echo $recherche_choice_checked_no; ?> name="<?php echo $this->behaviour_settings_key; ?>[behaviour_option_recherche_choice]"><? echo __('Non', CDP_PLUGIN_NAME ); ?>  
                    <span title="  - <?php echo __("Si vous choisissez 'non', alors le guide shopping affichera que les produits pertinents des rayons que vous aurez choisis dans l'onglet 'Rayons'", CDP_PLUGIN_NAME ); ?><br /><br />
                    - <?php echo __("Si vous choisissez 'oui', alors le guide shopping affichera tous les produits pertinents", CDP_PLUGIN_NAME ); ?>" class="vtip">?</span>
                </td>        	
            </tr>
            <!-- spacer -->
            <tr>
                <td colspan="2" style="border-bottom:1px solid #CACACC;"></td>
            </tr><tr><td colspan="2"></td></tr>
        </table>
		<?php
	}
	
	/*
	 * Category Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_category_option() {
		?>
		<input type="text" name="<?php echo $this->category_settings_key; ?>[category_option]" value="<?php echo esc_attr( $this->category_settings['category_option'] ); ?>" />
		<?php
	}
	
	/*
	 * Category Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_article_option() {
		//blank
	}
	
	/*
	 * Category Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_article_option_other() {
		?>
		<input type="text" name="<?php echo $this->article_settings_key; ?>[article_option_recherche_tous_categories]" value="<?php echo esc_attr( $this->article_settings['article_option_recherche_tous_categories'] ); ?>" />
		<?php
	}	
	
	
	/*
	 * Category Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_widget_option() {
		
		// need domaine id
		if (isset($this->install_details)){
			$install_details = $this->install_details;
		}
		else {
			$install_details = cdp_get_install_details();
		}
		
		//which tab
		$tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : FALSE;

		if ( $tab == 'cdp_widget_settings'){
						
			// deal with any error message
			if (isset($_GET['cdp_widget_settings_msg']) ){
				if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
					// do nothing - don't want this after successful update
				}
				else {
					// display error message
					$msg = urldecode($_GET['cdp_widget_settings_msg']);
					?>
						<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
					<?php
					$msg ='';
				}//else
			} //if //end error message
		}
		
		?>
		<div id="cdp_add_widget_status"></div>
		<?php
		
		// gets available sizes + ids from Buzzea
		$widgets_available = cdp_get_widgets_available(); 
		$all_widgets_available = $widgets_available;
		
		// available widgets to choose from
		cdp_widgets_dropdown($widgets_available);
		
		?><input type="button" value="<?php echo __('Ajouter une bannière', CDP_PLUGIN_NAME );?>" class="button-primary" id="cdp_add_banner" name="cdp_add_banner"><?php
		
		
		/*********************************************/
		// 	Output widget options to allow user edit
		/*********************************************/
		//	pr($this->widget_settings);
		
		if (isset($this->widgets_available)){
			$widgets_available	= $this->widgets_available;
		} else { $widgets_available	= ''; }
		
		// necessary jquery 
		require_once CDP_PLUGIN_PATH.'admin/inc/jquery_widgets.php'; 
		
		?>
		<div id="cdp_banners_holder">
            <div id="cdp_banners_loader_holder" style="display:none"><div id="cdp_banners_loader"><img src='<?php echo CDP_IMG_URL_ADMIN.'cdp-ajax-loader.gif';?>' /></div></div>
            <div id="cdp_banners_list" style="display:none"></div><!-- receives banners via ajax-->
        </div>
        <?php //hide the save button ?>
		<script type="text/javascript">
            jQuery(document).ready(function(jQuery) {
                jQuery('#submit').hide();
            });
        </script>
		<?php
		
		/******************************************************************************************/
		// 	User has made Widget changes. They've been filtered and are ready to transmit to buzzea
		/******************************************************************************************/
		if ( isset($_GET['cdp_tab']) && $_GET['cdp_tab'] == 'cdp_widget_settings' && isset($_GET['settings-updated']) && $_GET['settings-updated'] == true ){
			$details_array = $this->widget_settings;
			$update = cdp_transmit_widget_settings($details_array);
			//pr($details_array);
		}
		
		
	}

	/*
	 * The main menu for the plugin in admin area
	 * Called during admin_menu, adds an options
	 * page under Settings called My Settings, rendered
	 * using the plugin_options_page method.
	 */
	function add_admin_menus() {
		$general_options = get_option('cdp_general_settings');
		
		//reminder: vd($this->plugin_options_key); // 'cdp_plugin_options'
		add_menu_page( __('Buzzea Shopping', CDP_PLUGIN_NAME ), __('Buzzea Shopping', CDP_PLUGIN_NAME ), 'manage_options', 'cdp_plugin_options', array( &$this, 'plugin_options_page') );
  			
			// remove unwanted repeated menu item
			// http://wordpress.org/support/topic/top-level-menu-duplicated-as-submenu-in-admin-section-plugin
			add_submenu_page('cdp_plugin_options','','','manage_options','cdp_plugin_options',''); // trick to remove unwanted repeated sub
			
			// sub menu items
			add_submenu_page( 'cdp_plugin_options', __('Guide Shopping', CDP_PLUGIN_NAME ), __('Guide Shopping', CDP_PLUGIN_NAME ), 'manage_options', 'cdp_plugin_options_guide', array( &$this, 'plugin_options_page'));
			add_submenu_page( 'cdp_plugin_options', __('Bannières', CDP_PLUGIN_NAME ), __('Bannières', CDP_PLUGIN_NAME ), 'manage_options', 'cdp_plugin_options_banner', array( &$this, 'plugin_options_page'));
 
	
	}// func
	
	/*
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 */
	function plugin_options_page() {
		?>
		<div class="wrap">
        	<?php 
				
				//$cdp_check_password_installed = cdp_get_password(); //has the install been completed before?
			//vd($this->install_status);
				if ($this->install_status == 0 || $this->install_status == -1){
					
					/************************************/
					/*		NOT Already Installed OK	*/
					/************************************/
					
					//only show general tab
					$this->plugin_settings_tabs = ''; 
					$this->plugin_settings_tabs[$this->general_settings_key] = __('Général', CDP_PLUGIN_NAME );
						
					if ($this->install_status == -1){

						/************************************/
						/*		NOT Subscribed to BUZZEA	*/
						/************************************/
							$this->plugin_notice_form(); //show notice
					}
					else {

						/********************************/
						/*		Subscribed to BUZZEA	*/
						/********************************/
					
						//this CDP site is subscribed with Buzzea, continue install	
						$this->plugin_options_form();	
					}	
				}
				else {
					
					/********************************/
					/*		Already Installed OK	*/
					/********************************/
					$this->install_status = 1;
					
					//install completed before
					$this->plugin_options_form();	
				}
			?>
		</div>
		<?php
	}
	
	// to show a notice in case of unsubscribed user
	//
	function plugin_notice_form() {
		
		$this->plugin_options_tabs();
		
		//do we have a placed CDP via shortcode?
		$cdp_site_url = cdp_location();
		if ($cdp_site_url == FALSE){
			$cdp_site_url = site_url();
		}
		
		$votre_site = $cdp_site_url;
		
		echo __("<h3>Malheureusement...</h3>", CDP_PLUGIN_NAME );
		$cdp_buzzea_tel = CDP_BUZZEA_TEL; 
		echo __("<p>Nous ne retrouvons pas l'URL  <strong>$votre_site</strong> dans notre base de données. <br /> <br />
		Il se peut que ce site ne soit pas inscrit chez Buzzea. Sinon, vous pouvez nous contacter au $cdp_buzzea_tel </p>", CDP_PLUGIN_NAME );
		/* s'il vous plaît suivez ce lien pour vous <a href='".CDP_SUBSCRIPTION_URL."' target='_blank'>inscrire</a> dès maintenant.<br />
		<p>Peut-être que nous avons votre site dans le dossier - mais l'URL est différente, si vous pensez que c'est le problème s'il vous plaît nous contacter au */
			
	} /* end function */ 
	
	
	/*
	 * Plugin Options form
	 * The main form full of options / tabs
	 */
	function plugin_options_form() {
		
		// which tab is active
		// default
		$tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->general_settings_key;
		
		// determine active tab
		if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options' ){ 
		
			// general
			$tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->general_settings_key;

		} else if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_guide' ){ 
			
			// theme (guide)
			$tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->theme_settings_key;		

		} else if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_banner' ){ 
		
			// banners
			$tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->widget_settings_key;
			
		}
		
		// selecting which tabs to show...
		$this->plugin_options_tabs();	
		
		?>
        <form method="post" action="options.php">
            <?php wp_nonce_field( 'update-options' ); ?>
            <?php settings_fields( $tab ); ?>
            <?php do_settings_sections( $tab ); ?>
            <?php 
			    
				/********************************/
                // 	Show General CDP settings
                /********************************/
					if ( $tab == 'cdp_general_settings'){
						
						// now want a success message for when viewing via admin page (opposed to admin options in general settings) ...
						if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true && !isset($_GET['cdp_theme_settings_msg'])) {
							$admin_filename_check =  cdp_get_admin_filename(); //page or general options area?
							
							if ($admin_filename_check == 'admin.php' && strstr($_SERVER["REQUEST_URI"], 'admin.php') ){
								//if current page is admin.php
								
								// display error message
								$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"updated\" id=\"setting-error-settings_updated\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}
						}
						
						//is there an error to act on?
						if (isset($_GET['cdp_theme_settings_msg']) ){
							if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
								//do nothing - don't want this after successful update
							}
							else {
								// display error message
								$msg = urldecode($_GET['cdp_thme_settings_msg']);
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}//else
						}
					
					}	
				
				/********************************/
                // 	Show Theme CDP settings
                /********************************/
					if ( $tab == 'cdp_theme_settings'){
						
						// now want a success message for when viewing via admin page (opposed to admin options in general settings) ...
						if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true && !isset($_GET['cdp_theme_settings_msg'])) {
							$admin_filename_check =  cdp_get_admin_filename(); //page or general options area?
							if ($admin_filename_check == 'admin.php'){
								// display error message
								$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"updated\" id=\"setting-error-settings_updated\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}
						}
						
						//is there an error to act on?
						if (isset($_GET['cdp_theme_settings_msg']) ){
							if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
								//do nothing - don't want this after successful update
							}
							else {
								// display error message
								$msg = urldecode($_GET['cdp_thme_settings_msg']);
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}//else
						}
					?>
					<p>&nbsp;<a href="#" class="button-secondary cdp_preview_link" rel="<?php echo CDP_PREVI_URL; ?>"><?php echo __('Prévisualisation dans une nouvelle fenêtre', CDP_PLUGIN_NAME );?></a>&nbsp;<a href="#" class="button-secondary cdp_reset_colors_link" ><?php echo __('Rétablir les couleurs par défaut', CDP_PLUGIN_NAME );?></a></p>
					<?php
					}				
				
				/********************************/
                // 	Show Personal CDP settings
                /********************************/
					if ( $tab == 'cdp_personal_settings'){
						
						// now want a success message for when viewing via admin page (opposed to admin options in general settings) ...
						if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true && !isset($_GET['cdp_personal_settings_msg'])) {
							$admin_filename_check =  cdp_get_admin_filename(); //page or general options area?
							if ($admin_filename_check == 'admin.php'){
								// display error message
								$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"updated\" id=\"setting-error-settings_updated\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}
						}
						
						//is there an error to act on?
						if (isset($_GET['cdp_personal_settings_msg']) ){
							if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
								//do nothing - don't want this after successful update
							}
							else {
								// display error message
								$msg = urldecode($_GET['cdp_personal_settings_msg']);
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}//else
						}
					?>
					<p><input type="submit" value="Enregistrer les modifications" class="button-primary" id="submit" name="submit"></p>
					<?php
					}//if tab
					
				/********************************/
                // 	Show Behaviour CDP settings
                /********************************/
					if ( $tab == 'cdp_behaviour_settings'){
						
						// now want a success message for when viewing via admin page (opposed to admin options in general settings) ...
						if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true && !isset($_GET['cdp_behaviour_settings_msg'])) {
							$admin_filename_check =  cdp_get_admin_filename(); //page or general options area?
							if ($admin_filename_check == 'admin.php'){
								// display error message
								$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"updated\" id=\"setting-error-settings_updated\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}
						}
						
						//is there an error to act on?
						if (isset($_GET['cdp_behaviour_settings_msg']) ){
							if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
								//do nothing - don't want this after successful update
							}
							else {
								// display error message
								$msg = urldecode($_GET['cdp_behaviour_settings_msg']);
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}//else
						}
					?>
					<p><input type="submit" value="Enregistrer les modifications" class="button-primary" id="submit" name="submit"></p>
					<?php
					}//if tab	
					
					
					/********************************/
					// 	Transmit Personal CDP settings
					/********************************/
					if ( $tab == 'cdp_personal_settings' && isset($_GET['settings-updated']) && $_GET['settings-updated'] == true ){
						$details_array = $this->personal_settings;
						$update = cdp_transmit_personal_settings($details_array);
					}//if
					
					/************************************/
					// 	Transmit Behaviour CDP settings
					/************************************/
					if ( $tab == 'cdp_behaviour_settings' && isset($_GET['settings-updated']) && $_GET['settings-updated'] == true ){
						$details_array = $this->behaviour_settings;
						//$update = cdp_transmit_personal_settings($details_array); //re-use same function
					}//if
                
                /********************************/
                // 	Show Categories
                /********************************/
					if ( $tab == 'cdp_category_settings'){
						//display categories selector
						cdp_categories_table();
						if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == true){
							//check success
						}//if settings-updated
					}//if cdp_category_settings
                
                /********************************/
                // 	Begin: Show Articles
                /********************************/
					if ( $tab == 'cdp_article_settings'){
						//check are any categories selected..
						
						// now want a success message for when viewing via admin page (opposed to admin options in general settings) ...
						if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true && !isset($_GET['cdp_article_settings_msg'])) {
							$admin_filename_check =  cdp_get_admin_filename(); //page or general options area?
							if ($admin_filename_check == 'admin.php'){
								// display error message
								$msg = __("Vos options ont été enregistrées", CDP_PLUGIN_NAME );
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"updated\" id=\"setting-error-settings_updated\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}
						}
						
						//is there an error to act on?
						if (isset($_GET['cdp_article_settings_msg']) ){
							if (isset($_GET['settings-updated']) && $_GET['settings-updated']==true) {
								//do nothing - don't want this after successful update
							}
							else {
								// display error message
								$msg = urldecode($_GET['cdp_article_settings_msg']);
								?>
									<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");</script>
								<?php
								$msg ='';
							}//else
						} //if //end error message
						
						$cdp_categories_selected  = get_option( 'cdp_category_settings');
						if ($cdp_categories_selected == FALSE || $cdp_categories_selected == 0){
							//check again in case...
							$cdp_categories_selected_check = cdp_categories_check(); 
							//need some selected categories first please!
							if ($cdp_categories_selected_check == FALSE){
									$msg = __("Oups, vous devrez choisir vos catégories avant de choisir des produits.<br />", CDP_PLUGIN_NAME );
								?><script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");</script><?php
									$msg ='';
							} else { 
								/*All ok there are categories - continue as normal */
							}//else
						}//if categories
						
						
						$cdppagparam = CDP_PAGINATION_PARAMETRE;
						//current page minus 'paging' parametre. used for pagination
						$cdp_target_path = esc_url(preg_replace("/&$cdppagparam=([0-9]+)$/", '', $_SERVER['REQUEST_URI']));
						// /wp-admin/options-general.php?page=cdp_plugin_options&tab=cdp_article_settings&paging=1 minus the &paging=1
							?>
                             <?php echo __("<p>Mettre en avant automatiquement jusqu'à&nbsp;", CDP_PLUGIN_NAME ); ?><input type="text" class="cdp_article_settings_article_option_autofiller" name="<?php echo $this->article_settings_key; ?>[article_option_autofiller]" value="<?php echo esc_attr( $this->article_settings['article_option_autofiller'] ); ?>" placeholder="0 - 999" />&nbsp;
							 <?php echo __("articles dans chaque catégorie (sauf celles où des mises en avant sont déjà effectives)", CDP_PLUGIN_NAME ); ?>
        <br />	<strong><?php echo __("Ces mises en avant seront réactualisées chaque jour.", CDP_PLUGIN_NAME ); ?></strong></p>
		
						<div id="cdp_enselection_holder">
							
                            <table cellpadding="5" cellspacing="0" id="cdp_enselection_table" >
                            	<tr>
									<td class="cdp_ajouter_left" rowspan="2">                            
                                            <div id="cdp_ajouter_left_title"><?php echo __("<p>Ajouter en parcourant le catalogue :</p>", CDP_PLUGIN_NAME ); ?></div>
                                            <?php 
                                             //show categories and their articles
											 	
                                                cdp_categories_articles_table();
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
					
					 // show articles
					 require_once CDP_PLUGIN_PATH.'admin/inc/jquery_articles.php'; 
					
					}//if articles tab
            
				/********************************/
                // 	End: Show Articles
                /********************************/
			?>
               
            <?php 
					
					//show submit - but not for these tabs below
					if ( $tab != 'cdp_personal_settings' && 
						 $tab != 'cdp_behaviour_settings' &&
						 $tab != 'cdp_general_settings' &&
						 $tab != 'cdp_widget_settings'
					    ){  submit_button(); }
					
			?>
         </form><?php
		 
		
		
	}
	/*
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->general_settings_key;
		
		screen_icon();//wp own icon
		echo '<h2 class="nav-tab-wrapper">';
		
		// sepwork:
	
		if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options' ){ 
		
			$this->plugin_settings_tabs = array(); //blank what's already there
			$this->plugin_settings_tabs[$this->general_settings_key] = __('Général', CDP_PLUGIN_NAME ); // new tab
			
			
			$current_tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->general_settings_key;

		} else if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_guide' ){ 
		
			$this->plugin_settings_tabs = array(); //blank what's already there
			$this->plugin_settings_tabs[$this->theme_settings_key] 		= __('Thème', CDP_PLUGIN_NAME ); // 
			$this->plugin_settings_tabs[$this->personal_settings_key] 	= __('Personnalisation', CDP_PLUGIN_NAME ); // 
			$this->plugin_settings_tabs[$this->behaviour_settings_key] = __('Comportement', CDP_PLUGIN_NAME ); // 
			$this->plugin_settings_tabs[$this->category_settings_key] = __('Rayons', CDP_PLUGIN_NAME ); // 
			$this->plugin_settings_tabs[$this->article_settings_key] = __('Sélections', CDP_PLUGIN_NAME ); // 
			
			$current_tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->theme_settings_key;
		

		} else if (isset( $_GET['page'] ) && $_GET['page'] == 'cdp_plugin_options_banner' ){ 
		
			$this->plugin_settings_tabs = array(); //blank what's already there
			$this->plugin_settings_tabs[$this->widget_settings_key] 		= __('Bannières', CDP_PLUGIN_NAME ); // 
			
			$current_tab = isset( $_GET['cdp_tab'] ) ? $_GET['cdp_tab'] : $this->widget_settings_key;
		}
		
		// show tabs		
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&cdp_tab=' . $tab_key . '">' . $tab_caption . '</a>';	
		}
		echo '</h2>';
	}
	
	
}; // class
					
?>