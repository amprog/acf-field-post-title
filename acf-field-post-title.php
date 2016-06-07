<?php
/*
Plugin Name: Advanced Custom Fields: Post Title Search
Plugin URI: https://github.com/amprog/acf-field-post-title
Description: Post Title Search for ACF
Version: 1.0.0
Author: Eric Helvey
Author URI: http://github.com/helver
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: acf-field-post-title
Domain Path: /languages
*/


class acf_field_post_title_search
{
	/*
	*  Construct
	*
	*  @description:
	*  @since: 0.0.1
	*  @created: 5/23/2016
	*/

	function __construct()
	{
		load_plugin_textdomain( 'acf-field-post-title-search', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// version 5+
		add_action('acf/include_field_types', array($this, 'include_field_types'));

	}


	/*
	*  Init
	*
	*  @description:
	*  @since: 0.0.1
	*  @created: 5/23/2016
	*/

	function include_field_types()
	{
		include_once('post_title_search.php');
	}

}

new acf_field_post_title_search();

?>