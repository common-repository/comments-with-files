=== Comments with files ===
Contributors: whodunitagency, leprincenoir
Tags: comments, attachments, files, media, form
Requires at least: 3.5
Tested up to: 4.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add the ability to put attachments in the comments.

== Description ==

FEATURES
- Add attachments(multipe) in comments
- Checking attachments before uploads
- View attached attachment

The default file types allowed :
 - doc, xls, ppt
 - xlsx, docx, pptx
 - odt, ods
 - pdf,
 - gif, jpeg, png

The plugin uses the hook "filter_get_file_mime_types" : It allows to manage the types of files allowed.
Example :


    add_filter( 'filter_get_file_mime_types' , 'filter_get_file_mime_types_custom' );

    function filter_get_file_mime_types_custom( $types ) {

        $types[] = 'text/plain';

        return $types;
    }

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/comments-with-files` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

(soon)

== Screenshots ==

1. Attachment Display

== Changelog ==

= 1.0.0 =
* 01 July 2017
* Initial release \o/