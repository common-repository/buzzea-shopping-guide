<?php 
/*
Description: This file contains the frontend jquery search functions for the Buzzea WP Comparateur de Prix
Author: Buzzea.com
Author URI: http://Buzzea.com
License: GPLv2

Copyright 2013 BUZZEA.com (email : info@buzzea.com)

*/

$ajax_nonce = wp_create_nonce("my-special-string-jquery-search-security");

?><script type="text/javascript">
	jQuery(document).ready(function(jQuery) {
						
		requrl = '<?php echo CDP_AJAX_URL;?>';

		requrl = requrl+'?action=get_search_results&security=<?php echo $ajax_nonce;?>'	/*get_search_results*/

		cdp_getAutoCompleteSearch(requrl);
			
	});
</script><?php 
/* end of file */ 
?>