=== Post Title Search ACF Field ===
Contributors: @helver
Tags: acf, custom field
Requires at least: 4.4
Tested up to: 4.4
Stable tag: 0.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Post Title Search field for Advanced Custom Fields

== Description ==


This is an add-on for the [Advanced Custom Fields](http://wordpress.org/extend/plugins/advanced-custom-fields/) WordPress plugin, that allows you to add a post title search field that displays post titles that match a given substring.

= Compatibility =

This add-on will work with:

* Advanced Custom Fields version 5.0 and up

== Installation ==


= Plugin =
1. Copy the 'acf-field-post-title' folder into your plugins folder
2. Activate the plugin via the Plugins admin page

= Include =
1.	Copy the 'acf-field-post-title' folder into your theme folder (can use sub folders). You can place the folder anywhere inside the 'wp-content' directory
2.	Edit your functions.php file and add the code below (Make sure the path is correct to include the acf-field-post-title.php file)

`
add_action('acf/register_fields', 'my_register_fields');

function my_register_fields()
{
	include_once('acf-field-post-title/acf-field-post-title.php');
}
`



== Changelog ==
= 0.0.1 =
* Initial revision based on ACF post-object field type.
