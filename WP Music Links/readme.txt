=== WP Music Links ===
Contributors: fergomez
Tags: links, music, artists, festival, social network, facebook, twitter, last.fm
Requires at least: 3.0
Tested up to: 3.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds links to social networks of artists and festivals easily in your posts.

== Description ==

This plugin allows you to create a database for social networks of artists and festivals to include in your music weblog posts. You can add them manually, but the program will also try to get them by itself from Music Brainz, the Wikipedia for music.

== Installation ==

Just upload your plugin and activate it. It will create the database tables needed for its correct working and it will add manualy one example: Metallica.

== Frequently Asked Questions ==

= Can I store links for other type of items such as agencies, labels...? =

Right now the plugin supports just artists and festivals, but eventually in a future version will accept this.

= Why can't I change the order of the links? =

Well, it will also be updated (hopefully) in the next version.

== Changelog ==

= 0.1.6 =
* Fixed bug: when there is no information about an artist on MusicBrainz, now the plugin does not crash.
* Enhancement: "<br />" removed from the HTML code of the shortcode, so in case of two shortcodes, we can place them as we want
 
= 0.1.5 = 
* Modified quickcode for adding also the type of the item, even easier.

= 0.1.4 =
* Added quickcode for editor: javascript alert box for writing the names easier thanks to TinyMCE editor.

= 0.1.3 = 
* Fixed bug: adding festival with no Last.fm information, it would set the item as an artist ("=" on an if instead of "==").
* Enhancement: select tag for type choice (either "artist" either "festival").

= 0.1.2 = 
* Fixed bug: editing festival with no Last.fm information, it would set the item as an artist ("=" on an if instead of "==").
* Enhancement: if the plugin does not find the selected item in the edition, it will not continue and will ask for another one.

= 0.1.1 =
* Fixed bug: problem with artists having an ampersand ("&") on their name.

= 0.1 =
* Basic working getting information automaticly from MusicBrainz or manually. 
* Also successful editing process.  

== Acknowledgments ==
* Thanks to www.psdgraphics.com for the free Photoshop PSD file for the plugin icon.