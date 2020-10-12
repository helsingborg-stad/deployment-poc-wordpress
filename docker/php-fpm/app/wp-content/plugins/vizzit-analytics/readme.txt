=== Vizzit Analytics for WordPress ===
Contributors: Vizzit International AB
Donate link: https://www.vizzit.se/modules/wordpress/
Tags: admin, page, pages, tree, view, admin menu, menu
Requires at least: 4.4
May work with: >= 3.2
Tested up to: Wordpress 4.6.1
Version: 1.0.2

Web Statistic for your WordPress Blog

== Description ==

This WordPress plugin adds ...

== Installation ==

1. Upload the folder "vizzit-analytics-for-wordpress" to "/wp-content/plugins/"
1. Activate the plugin through the "Plugins" menu in WordPress
1. Done!

== Screenshots ==



== Changelog ==

= 1.0.2 =
- Added a Vizzit access button in the admin bar

= 1.0.1 =
- Minor fix for scheduler

TODO/CHECK:
- disable schedule when deactivating
- check for existence of tables - maybe purge tables?
- "wizard"-like when adding customer_id and keys for the first time: build mail to send with the keys etc.
- build update-function to apply changes such as added options, changed default values, ..
- rewrite XML-status for SOAP - do not rely on php-xml

= 0.7 =
- Minor fixes
- Removed usage of deprecated functions
- Removed notices/warnings

= 0.6 =
- added support for any page type

= 0.5 =
- cleaned up code
- added help texts
- multisite is now supported!
- made some minor ui changes

= 0.4 =
- rewrote to use simpleXML instead of DOMXML in soap class
- modified default values for plugin options
- added Javascript tag path as constant
- fixed URLs for Vizzit Application links
- added fix to subtract level-count if is_front_page/is_home
- added link to settings in Vizzit Plugin description on plugin page
- modified file upload from CURL to standard post
- added date_start when starting processing structure - as well as date_end when finishing
- added check if files/ directory is writable (display error when processing as well as message in admin mode)
- added SOAP-API at URL: {wp_home}/episerver_vizzit/ws/(.+)
- added support for anonymize usernames
- added new database table for storing meta information for processings
- added automatically generated crypt-keys as default values if no customer_id was set before
- added test_mode as option; added "_test" when sending upload and on tag-insert

= 0.3 =
- added more support if qTranslate
  - loop around the active languages in the right order
  - take care of two of their three url-path-settings:
      Use Query Mode (?lang=en)
      Use Pre-Path Mode (Default, puts /en/ in front of URL)
  - take care of the right post-title for each language
  - placed the qTrans language name as node-row when building the tree

= 0.2 =
- ...

= 0.1 =
- It's alive!