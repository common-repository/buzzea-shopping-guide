<?php 
/*
Description: This file contains the article display functions for the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/

/* 
 * function that get articles in xml and displays
 * takes: 	$limitstart 		start showing from this number, 
 			$limitfor			show this many items
			$cdp_target_path	use this URL in pagination links
 * returns: echoed HTML of articles table
 */
function cdp_articles_table($limitstart=NULL, $limitfor=NULL, $cdp_target_path, $domaineid=NULL) {
	$password = md5(cdp_get_password());
	$cdp_target_path = esc_url($cdp_target_path);
	
	$limit = '';
	if (is_numeric($limitstart) && is_numeric($limitfor)){
		$limit = "/$limitstart/$limitfor/";
	}
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post(CDP_API_URL."articles/get-all$limit", 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_domaineid' => $domaineid ),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }			
	//debug 
	//pas d'articles? verifier ici
	//vd($post_result);
	
	if (CDP_DEBUG){
		vd($post_result['body']);
	}
	//vd($post_result['body']);
	
	$articles_xml_str = $post_result['body'];
	if (strlen($post_result['body']) == 0) { $articlesmsg = __("Pas d'articles en sélection", CDP_PLUGIN_NAME );} else { $articlesmsg = '';}
	
	$xmlcheck = @simplexml_load_string($articles_xml_str);
	if($xmlcheck===FALSE) {
	//It was not an XML string
		$xmlcheck = FALSE;
	} else {
	//It was a valid XML string
		$xmlcheck = TRUE;
	}
	
	if ($xmlcheck === FALSE){
		//fail
				$msg = __($articlesmsg, CDP_PLUGIN_NAME ); //."Pas de données xml" //debug HELP - if you echo anything that in the main library it will disrupt XML
				?>
				<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");
				</script>
				<?php
	}
	
	if ($articles_xml_str != FALSE && $articles_xml_str != '' && $xmlcheck!=FALSE){
		//vd($articles_xml_str); die();
		$articles = new SimpleXMLElement($articles_xml_str);
		//now have articles in an XML object ready to output
		//columns item counter
		if (is_numeric($limitstart)) {
			$i = $limitstart; 
		} else { $i = 0; }
		
		///////////////
		//	LEVEL 1  //
		///////////////
		if ($articles->articles != NULL){
			echo '<table class="widefat">';
			?>
			<?php
			foreach ($articles->articles as $articles){
				$articlesAttributes = $articles->attributes();
				$num_articles = $articlesAttributes->results;
				
				foreach ($articles->article as $article) {
						//attributes
						$articleAttributes = $article->attributes();
						
						/* simple counter */
						$i = $i+1;
						?>
						<tr id="article<?php echo sanitize_text_field($articleAttributes->id) ?>">
						  <td width="10%"><?php echo $i; ?></td>
							<td width="70%"><?php echo sanitize_text_field($article->title) ?></td>
							<td width="10%" align="right"><a href="#" rel="<?php echo $domaineid; ?>" class="deletearticle_<?php echo sanitize_text_field($articleAttributes->id) ?>"><?php echo __("supprimer", CDP_PLUGIN_NAME ); ?></a></td>
                            <td width="10%"><input type="checkbox" rel="<?php echo sanitize_text_field($articleAttributes->id) ?>" id="delete_<?php echo sanitize_text_field($articleAttributes->id) ?>"></td>
						</tr>
						<?php
				}//foreach
			}
			echo '</table>';
		}//if		 
		?>
		
		<?php 
		//if no articles
		//if ($articles->articles == NULL){
		if (!isset($num_articles) || $num_articles == 0){
					//no articles
					$noarticlesmsg = __("Il n'y a pas d'articles", CDP_PLUGIN_NAME );
					echo '<table class="widefat">
						<tr><td colspan="3" align="center">'.$noarticlesmsg.'</td></tr>
						</table>
					';
		} else {
			//have articles... show pagination also	
			cdp_articles_table_pagi($limitstart, $limitfor,$num_articles, $cdp_target_path, $domaineid);
		}

	} else { return FALSE; }
		
}/* function */



/* 
 * function that get contextuelle articles in xml and displays
 * takes: 	$limitstart 		start showing from this number, 
 			$limitfor			show this many items
			$cdp_target_path	use this URL in pagination links
 * returns: echoed HTML of articles table
 */
function cdp_articles_contextuelle_table($limitstart=NULL, $limitfor=NULL, $cdp_target_path) {
	$password = md5(cdp_get_password());
	$cdp_target_path = esc_url($cdp_target_path); ///wp-admin/post.php?post=191&action=edit
	
	$limit = '';
	if (is_numeric($limitstart) && is_numeric($limitfor)){
		$limit = "/$limitstart/$limitfor/";
	}
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	// current post's permalink
	$permalink 	= cdp_get_contextuelle_permalink($cdp_target_path);
	
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post(CDP_API_URL."articles/get-all-contextuelle$limit", 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,	
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, 'cdp_contexte_url' => $permalink ),
										'cookies' => array()
										)
								);	
	if( is_wp_error( $post_result ) ) { return FALSE; }			
	//debug 
	//pas d'articles? verifier ici
	//pr($post_result);
	
	if (CDP_DEBUG){
		vd($post_result['body']);
	}
	//vd($post_result['body']);
	
	$articles_xml_str = $post_result['body'];
	
	if (strlen($post_result['body']) == 0) { $articlesmsg = __("Pas d'articles en sélection", CDP_PLUGIN_NAME );} else { $articlesmsg = '';}
	
	$xmlcheck = @simplexml_load_string($articles_xml_str);
	if($xmlcheck===FALSE) {
	//It was not an XML string
		$xmlcheck = FALSE;
	} else {
	//It was a valid XML string
		$xmlcheck = TRUE;
	}
	
	if ($xmlcheck === FALSE){
		//fail
				$msg = __($articlesmsg, CDP_PLUGIN_NAME ); //."Pas de données xml" //debug HELP - if you echo anything that in the main library it will disrupt XML
				?>
				<script type="text/javascript">jQuery('.nav-tab-wrapper').after("<div class=\"error settings-error\" id=\"setting-error-settings_error\"><p><strong><?php echo $msg; ?></strong></p></div>");
				</script>
				<?php
	}
	
	if ($articles_xml_str != FALSE && $articles_xml_str != '' && $xmlcheck!=FALSE){
		//vd($articles_xml_str); die();
		$articles = new SimpleXMLElement($articles_xml_str);
		//now have articles in an XML object ready to output
		//columns item counter
		if (is_numeric($limitstart)) {
			$i = $limitstart; 
		} else { $i = 0; }
		
		///////////////
		//	LEVEL 1  //
		///////////////
		if ($articles->articles != NULL){
			echo '<table class="widefat">';
			?>
			<?php
			foreach ($articles->articles as $articles){
				$articlesAttributes = $articles->attributes();
				$num_articles = $articlesAttributes->results;
				
				foreach ($articles->article as $article) {
						//attributes
						$articleAttributes = $article->attributes();
						
						/* simple counter */
						$i = $i+1;
						?>
						<tr id="article<?php echo sanitize_text_field($articleAttributes->id) ?>">
						  <td width="10%"><?php echo $i; ?></td>
							<td width="70%"><?php echo sanitize_text_field($article->title) ?></td>
							<td width="10%" align="right"><a href="#" rel="<?php echo $domaineid; ?>" class="deletearticle_<?php echo sanitize_text_field($articleAttributes->id) ?>"><?php echo __("supprimer", CDP_PLUGIN_NAME ); ?></a></td>
                            <td width="10%"><input type="checkbox" rel="<?php echo sanitize_text_field($articleAttributes->id) ?>" id="delete_<?php echo sanitize_text_field($articleAttributes->id) ?>"></td>
                            
						</tr>
						<?php
				}//foreach
			}
			echo '</table>';
		}//if		 
		?>
		
		<?php 
		//if no articles
		//if ($articles->articles == NULL){
		if (!isset($num_articles) || $num_articles == 0){
					//no articles
					$noarticlesmsg = __("Il n'y a pas d'articles", CDP_PLUGIN_NAME );
					echo '<table class="widefat">
						<tr><td colspan="3" align="center">'.$noarticlesmsg.'</td></tr>
						</table>
					';
		} else {
			//have articles... show pagination also	
			cdp_articles_table_pagi($limitstart, $limitfor,$num_articles, $cdp_target_path);
		}

	} else { return FALSE; }
		
}/* function */

/* 
 * function that outputs header to article list table
 * takes: 	-
 * returns: echoed HTML of articles table header
 */
function cdp_article_list_header($domaineid = FALSE){
	
	echo '<h4>'.__("Vos Articles déja en Selection", CDP_PLUGIN_NAME ).'</h4>';
	
	cdp_article_list_header_group_actions($domaineid);
	
	echo '<table class="widefat">
		
		<tr>
			<td width="10%"><strong>'.__("Nb.", CDP_PLUGIN_NAME ).'</strong></td>
			<td width="70%"><strong>'.__("Article", CDP_PLUGIN_NAME ).'</strong></td>
			<td width="10%" align="right" style="text-align:right"><strong>'.__("Action", CDP_PLUGIN_NAME ).'</strong></td>
			<td width="10%"><div class="cdp_group_actions_container_selectall">
							<input id="select_all_cdc_'.$domaineid.'" type="checkbox">
						</div></td>
		</tr>
		
		
		</table>';
	
} /* end function */ 


/* 
 * function that outputs header group actions to article list table
 * takes: 	-
 * returns: echoed HTML of articles table header
 */
function cdp_article_list_header_group_actions( $domaineid = FALSE ){
	
	if (is_numeric($domaineid) && $domaineid > 0){
		$domaineid_ext = '_'.$domaineid;
		$domaineid_rel = 'rel="'.$domaineid.'"';
		
	} else {
		$domaineid_ext = '';	
		$domaineid_rel = '';
	}
	
	
	echo '<table class="cdp_group_actions_table" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td>
					<div class="cdp_group_actions_container">
						<div class="cdp_group_actions_container_selector">
							<select class="cdpgroupaction" name="cdpgroupaction">
								<option selected="selected" value="-1">Actions groupées</option>
								<option value="delete">Retirer les produits selectionnés</option>
								<option value="delete_all">Retirer tous les produits</option>
							</select>
						</div>
						
						<div class="cdp_group_actions_container_apply"><a onclick="Javascript:return false;" class="button" href="#" role="button" aria-disabled="false">
						<span id="cdp_group_actions_apply'.$domaineid_ext.'" '.$domaineid_rel.'>Appliquer</span></a></div>
					</div>
				</td>
			</tr>
		</table>';
} /* end function */ 

/* 
 * function that outputs footer to article list table
 * takes: 	-
 * returns: echoed HTML of articles table footer
 */
function cdp_article_list_footer(){
	echo '<table class="widefat"><tfoot>
			<tr>
				<th>'.__("Nb.", CDP_PLUGIN_NAME ).'</th>
				<th>'.__("Article", CDP_PLUGIN_NAME ).'</th>
				<th>'.__("Action", CDP_PLUGIN_NAME ).'</th>
			</tr>
		</tfoot>
		</table>';
} /* end function */ 

/* 
 * function that outpus pagination on the articles table
 * takes: 	$limitstart 		start showing from this number, 
 			$limitfor			show this many items
			$num_articles		how many articles on file
			$cdp_target_path	use this URL in pagination links
 * returns: echoed HTML of articles table pagination
 */
function cdp_articles_table_pagi($limitstart=NULL, $limitfor=NULL, $num_articles, $cdp_target_path, $domaineid=NULL) {
	/* Instantiate class */
	require_once("pagination.class.php");
	$limitstart 	= (int) $limitstart;
	$limitfor		= (int) $limitfor;
	$num_articles 	= (int) $num_articles;
	
	$cdppagparam = CDP_PAGINATION_PARAMETRE;
	
 	$p = new pagination;
        $p->items($num_articles);
        $p->limit($limitfor); // Limit entries per page
        $p->target($cdp_target_path);
        $p->currentPage(1); // Gets and validates the current page
        $p->calculate(); // Calculates what to show
        $p->parameterName($cdppagparam);
        $p->adjacents(1); //No. of page away from the current page
                 
       if(!isset($_POST['paging'])) {
            $p->page = 1;
        } else {
            $p->page = $_POST['paging'];  //leave as paging.. for transport
        }
		//vd($_REQUEST);
		//determine correct numbers for message 
		if ($p->page == 1){ 
			$articlefrom = $p->page;
			$articleto = $limitfor;
		} else { 
			$articlefrom = (($p->page -1) * $limitfor)+1;
			$articleto   = (($articlefrom -1) + $limitfor);
			
		}
		
		if ($articleto > $num_articles) $articleto = $num_articles;

	?>
	<div class="tablenav">
        <div class="tablenav-pages">
        <span class="displaying-num"> <?php echo __("l'affichage", CDP_PLUGIN_NAME ); ?> <?php echo $articlefrom; ?> - <?php echo $articleto; ?> <?php echo __('de', CDP_PLUGIN_NAME ); ?> <?php echo $num_articles; ?> </span><?php echo $p->show(); ?>
        </div>
    </div>
	<?php
} /* end function */ 

/* 
 * function that get categories in xml and display in collapse/expand selector tree 
 * categories are displayed in 2 columns. $catspercolumn defines how many in first column
 * Note: used in 2 places --- selection of articles / selection of articles to use in a widget 
 * takes: -
 * returns: echoed HTML
 */
function cdp_categories_articles_table($no_domaine=FALSE) {
	
	$articles_showarticlescategory_nonce	 = wp_create_nonce( 'articlesnonceshowarticlescategory' );
	$articlesnonceadd 						 = wp_create_nonce( 'articlesnonceadd' );
	/*$showarticles_for_category_success_txt 	 = __("success txt here", CDP_PLUGIN_NAME );
	$showarticles_for_category_fail_txt 	 = __("fail here", CDP_PLUGIN_NAME );*/
	
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
	
	//do we have a placed CDP via shortcode?
	$cdp_site_url = cdp_location();
	if ($cdp_site_url == FALSE){
		$cdp_site_url = site_url();
	}
	
	// ALL cats or just some?
	if ($no_domaine == FALSE){
		$cats_url = CDP_API_URL.'categories/get-all';
	}
	else {
		$cats_url = CDP_API_URL.'categories/get-all-no-domaine';
		//want to get all categories regardless of domains
	}
		//vd($cats_url);
	//request categories details in XML from Buzzea
	$post_result = wp_remote_post($cats_url, 
									array(
										'method' => 'POST',
										'timeout' => 45,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking' => true,
										'headers' => array(),
										'body' => array( 'cdp_siteurl' => $cdp_site_url, 'cdp_password' => $password, ),
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
		//now have categories in an XML object ready to output
		$i = 0;//columns item counter
		
		///////////////
		//	LEVEL 1  //
		///////////////
		if ($categories->categories->category != NULL){
			
			?><!--<br clear="all" />--><div id="cats_articles"><?php
			
			foreach ($categories->categories->category as $category) {
				/* choices for column display */
				if ($i == 0){ echo '<div id="cats-left">'; }
				if ($i == $catspercolumn){ echo '</div><!-- cats-left --><div id="cats-right">'; }
				$i = $i+1;
				
				$catAttributes = $category->attributes(); 
				if ($catAttributes->selected == 'yes') $checked="checked"; else $checked="";
				if ($catAttributes->selected == 'yes' || $no_domaine == TRUE){ 
						
						//prep the onclick
						if ($category->subcategories->subcategory != NULL){ $imgsrc = $moreimg; $imgsrc_opposite = $lessimg; }else{ $imgsrc = $lessimg;  $imgsrc_opposite = $moreimg; } //choose expansion image		
						$onclick="onclick=\"ouvrefermecategorie(".sanitize_text_field($catAttributes->id).", 'cat".sanitize_text_field($catAttributes->id)."', 'subcat', '".$imgsrc_opposite."', '".$imgsrc."', this)\" style=\"cursor:pointer;\""; //setup correct onclick action
						?>
						<div class="cat-parent" id="cat<?php echo esc_attr($catAttributes->id); ?>">
							<?php if($category->nb_articles >= 0): ?>
                                <div id="categorie<?php echo sanitize_text_field($catAttributes->id); ?>" name="categorie<?php echo sanitize_text_field($catAttributes->id); ?>">
                                <img id="puce<?php echo sanitize_text_field($catAttributes->id); ?>" src="<?php echo $imgsrc;?>" <?php echo $onclick;?> >
                                <a href="#" id="cdp_cat_arts_<?php echo esc_attr($catAttributes->id); ?>" <?php echo $onclick;?>><?php echo sanitize_text_field($category->title); ?>
                                <?php if ($no_domaine == FALSE){ ?>
                                	(<?php echo sanitize_text_field($category->nb_articles); ?>)</a>
                                <?php } ?>
                            	</div>
                            <?php endif; ?>
						<?php 
						
						///////////////
						//	LEVEL 2  //
						///////////////
						if ($category->subcategories->subcategory != NULL){
							foreach ($category->subcategories->subcategory as $subcategory) {
								
								$subcatAttributes = $subcategory->attributes();
								if ($subcatAttributes->selected == 'yes') $checked="checked"; else $checked="";
								if ($subcatAttributes->selected == 'yes' || $no_domaine == TRUE){
																
								//prep the onclick
								if ($subcategory->subcategories->subcategory != NULL){ $imgsrc = $moreimg; $imgsrc_opposite = $lessimg; }else{ $imgsrc = $lessimg;  $imgsrc_opposite = $moreimg;} //choose expansion image	
								$onclick="onclick=\"ouvrefermecategorie(".sanitize_text_field($subcatAttributes->id).",'subcat".sanitize_text_field($subcatAttributes->id)."', 'subsubcat', '".$imgsrc_opposite."', '".$imgsrc."', this)\" style=\"cursor:pointer;\"";//setup correct onclick action
								
								/*onclick="cdp_show_cat_article_js(jQuery,'<?php echo esc_attr($subcatAttributes->id); ?>', '<?php echo $articles_showarticlescategory_nonce; ?>','<?php echo $showarticles_for_category_success_txt; ?>','<?php echo $showarticles_for_category_fail_txt; ?>' );return false;"*/
								?><div id="subcat<?php echo sanitize_text_field($subcatAttributes->id); ?>" style="padding-left: 17px; display:<?php echo $displaynone; ?>">
									<?php if($subcategory->nb_articles >= 0): ?>								
                                    <div>
                                        <div id="categorie<?php echo sanitize_text_field($subcatAttributes->id); ?>" name="categorie<?php echo sanitize_text_field($subcatAttributes->id); ?>">
                                        <img id="puce<?php echo sanitize_text_field($subcatAttributes->id); ?>" src="<?php echo $imgsrc;?>" <?php echo $onclick;?>>
                                        <a href="#" id="cdp_cat_arts_<?php echo esc_attr($subcatAttributes->id); ?>" <?php echo $onclick;?>><?php echo sanitize_text_field($subcategory->title); ?>
                                        <?php if ($no_domaine == FALSE){ ?>
                                        	(<?php echo sanitize_text_field($subcategory->nb_articles); ?>)
                                        <?php } ?>
                                        </a>
                                        </div>
                                    </div>	
                                    <?php endif; ?>
								<?php 
								

								///////////////
								//	LEVEL 3  //
								///////////////
								if ($subcategory->subcategories->subcategory != NULL){
									foreach ($subcategory->subcategories->subcategory as $subsubcategory) {
										
										$subsubcatAttributes = $subsubcategory->attributes();
										if ($subsubcatAttributes->selected == 'yes') $checked="checked"; else $checked="";
										//if ($subsubcatAttributes->selected != 'yes') { break; }//avoid next bit
										
										if ($subsubcatAttributes->selected == 'yes' || $no_domaine == TRUE) { 
										
											//prep the onclick
											if ($subsubcategory->subcategories->subcategory != NULL){ $imgsrc = $moreimg; $imgsrc_opposite = $lessimg; }else{ $imgsrc = $lessimg;  $imgsrc_opposite = $moreimg;} //choose expansion image	
											$onclick="onclick=\"ouvrefermecategorie(".sanitize_text_field($subsubcatAttributes->id).",'subsubcat".sanitize_text_field($subsubcatAttributes->id)."', 'subsubsubcat', '".$imgsrc_opposite."', '".$imgsrc."', this)\" style=\"cursor:pointer;\"";//setup correct onclick action
											?><div id="subsubcat<?php echo sanitize_text_field($subsubcatAttributes->id); ?>" style="padding-left: 34px; display: <?php echo $displaynone ?>;">
											<?php if($subsubcategory->nb_articles >= 0): ?>
                                                <div>
                                                <div id="categorie<?php echo sanitize_text_field($subsubcatAttributes->id); ?>" type="checkbox" <?php echo $checked;?> name="categorie<?php echo sanitize_text_field($subsubcatAttributes->id); ?>">
                                                <img id="puce<?php echo sanitize_text_field($subsubcatAttributes->id); ?>" src="<?php echo $imgsrc?>" <?php echo $onclick;?>>
                                                <a href="#" id="cdp_cat_arts_<?php echo esc_attr($subsubcatAttributes->id); ?>" <?php echo $onclick;?>><?php echo sanitize_text_field($subsubcategory->title); ?>
                                               	<?php if ($no_domaine == FALSE){ ?>
	                                                (<?php echo sanitize_text_field($subsubcategory->nb_articles); ?>)
                                                <?php } ?>
                                                </a></div>
                                                </div>	
                                            <?php endif; ?>
											<?php 
											
											///////////////
											//	LEVEL 4  //
											///////////////
											if ($subsubcategory->subcategories->subcategory != NULL){
												foreach ($subsubcategory->subcategories->subcategory as $subsubsubcategory) {
													
													$subsubsubcatAttributes = $subsubsubcategory->attributes();
													if ($subsubsubcatAttributes->selected == 'yes') $checked="checked"; else $checked="";
													if ($subsubsubcatAttributes->selected == 'yes' || $no_domaine == TRUE) { 
														$onclick='';//nothing at this level to expand "onclick=\"ouvrefermecategorie('subsubsubcat".$subsubsubcatAttributes->id."', 'subsubsubsubcat')\" style=\"cursor:pointer;\"";//setup correct onclick action
														?><div id="subsubsubcat<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>" style="padding-left: 51px; display: <?php echo $displaynone ?>;"><?php 
														
														?>
                                                        <?php if($subsubsubcategory->nb_articles >= 0): ?>
															<div>
															<div id="categorie<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>" type="checkbox" <?php echo $checked;?> name="categorie<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>">
															<img id="puce<?php echo sanitize_text_field($subsubsubcatAttributes->id); ?>" src="<?php echo CDP_IMG_URL_ADMIN;?>puce_less.png" <?php echo $onclick;?>>
															<a href="#" id="cdp_cat_arts_<?php echo esc_attr($subsubsubcatAttributes->id); ?>" <?php echo $onclick;?>><?php echo sanitize_text_field($subsubsubcategory->title); ?>
                                                            <?php if ($no_domaine == FALSE){ ?>
                                                            	(<?php echo sanitize_text_field($subsubsubcategory->nb_articles); ?>)
                                                           	<?php } ?>
                                                            </a></div>
															</div>	
                                                        <?php endif; ?>
														<?php 
														?></div><!-- subsubsubcat --><?php
													}//if ($subsubsubcatAttributes->selected == 'yes') { 
												}//foreach
											}//if
											?></div><!-- subsubcat --><?php
										}//if ($subsubcatAttributes->selected == 'yes')
									}//foreach
								}//if
								?></div><!-- 	subcat --><?php
								}//if ($subcatAttributes->selected == 'yes'){
							}//foreach
						}//if
						?></div><!-- cat-parent --><?php
					}//if ($catAttributes->selected == 'yes'){ 
				}//foreach
				?></div><!-- cats-right --><?php 
			?></div><!-- cats --><!--<br clear="all" />--><?php
		}//if
		 //echo $categories_xml->categories->category[0]->title;

	} else { return FALSE; }
		//$update_details = $post_result['body'];
}/* function */	

// inspired by: http://wpengineer.com/1991/example-how-to-add-meta-boxes-to-edit-area/
function cdp_add_meta_box_contextuelle($data) {
	
	add_meta_box( 'banners_contextuelle',
				__( 'Bannières contextuelles par ', CDP_PLUGIN_NAME ).CDP_PLUGIN_SHORT_NAME,
				cdp_meta_box_banner_contextuelle,
				'post', 'normal', 'high'
				);
}

/**********END OF FILE **************/
?>