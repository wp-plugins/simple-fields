=== Plugin Name ===
Contributors: eskapism
Donate link: http://eskapism.se/sida/donate/
Tags: admin, fields, custom fields, field manager, attachments, text areas, input fields, tinymce, radio button, drop down, files, meta box, edit, post, post_meta, post meta, custom
Requires at least: 3.0
Tested up to: 3.0
Stable tag: trunk

Add different kind of input fields to your edit post page. Field can be of type textarea, TinyMCE, checkbox, radio buttons, drop downs or files.

== Description ==

Simple Fields for WordPress let you add groups of fields to you edit post page.

Simple Fields turns WordPress into an even more powerful Content Management System (CMS).

#### Features and highlight

* Add textboxes, text areas, checkboxes, radio buttons, dropdowns, and file browser to the post admin area
* Much easier for the user than regular custom fields
* Group fields together into logical groups. For example combine File + Name + Description into a an Attachments-group.
* Use "repeatable" field groups to add many any amount of field groups to a single post (great for images or attachments!)
* Use drag and drop to change order of added repeatable groups
* Different post types can use different field groups - actually you can even use different field groups even for same post type
* Can be used on any post type, including custom post types
* Nice GUI that looks like it belongs to the regular WordPress GUI

For more information check out my introductory blog post:
http://eskapism.se/blogg/2010/05/simple-fields-wp-custom-fields-on-steroids/

Also check out this short tutorial:
http://eskapism.se/code-playground/simple-fields/tutorial/

#### Beta? You bet!

Please note that this plugin still is in a very early version. Please try it out but be aware of bugs. 
Also, please remember to backup your database, just to be sure if anything goes wrong.
For bugreports, feature request and so on, please contact me at par.thernstrom@gmail.com or through twitter 
(username [eskapism](http://twitter.com/eskapism/))

#### Help and Support
If you have questions/bug reports/feature requests for Simple Fields please use the WordPress [Support Forum](http://wordpress.org/tags/simple-fields?forum_id=10).
There are also [tutorials available for Simple Fields](http://eskapism.se/code-playground/simple-fields/).

#### Donation and more plugins
* If you like this plugin don't forget to [donate to support further development](http://eskapism.se/sida/donate/).
* Check out some [more plugins](http://wordpress.org/extend/plugins/profile/eskapism) by the same author.


== Installation ==

As always, make a backup of your database first!

1. Upload the folder "simple-fields" to "/wp-content/plugins/"
1. Activate the plugin through the "Plugins" menu in WordPress
1. Done!


== Screenshots ==

1. A post in edit, showing two field groups: "Article options" and "Article images".
These groups are just example: you can create your own field groups with any combinatin for fields.
See that "Add"-link above "Article images"? That means that it is repeatable, so you can add as many images as you want to the post.

2. One field group being created (or modified).

3. Group field groups together and make them available for different post types.


== Changelog ==

= 0.3 =
- Field type file now uses wordpress own file browser, so upload and file browsing should work much better now. If you still encounter any problems let me know. Hey, even if it works, please let med know! :)
- Media buttons for tiny now check if current user can use each button before adding it (just like the normal add-buttons work)

= 0.2.9 =
- Fixed a JavaScript error when using the gallery function
- Fixed a warning when using simple_fields_get_post_value() on a post with no post

= 0.2.8 =
- fixed errors when trying to fetch saved values for a post with no post_connector selected
- tinymce-fields can now be resized (does not save them correctly afterwards though...)
- uses require_once instead of require. should fix some problems with other plugins.
- clicking on "+ Add" when using repeatable fields the link changes text to "Adding.." so the user will know that something is happening.
- removed media buttons from regular (non-tiny) textareas
- tiny-editor: can now switch between visual/html

= 0.2.7 =
- file browser had some <? tags instead of <?php
- Could not add dropdown values in admin

= 0.2.6 =
- media buttons for tinymce fields
- fixed some js errors
- content of first tinymce-editor in a repeatable field group would lose it's contents during first save
- drag and drop of repeatable groups with tinymce-editors are now more stable
- code cleanup
- filter by mime types works in file browser

= 0.2.5 =
- used <? instead of <?php in a couple of places
- now uses menu_page_url() instead of hard-coding plugin url
- inherited fields now work again. thanks for the report (and fix!)
- p and br-tags now work in tiny editors, using wpautop()
- moved some code from one file to another. really cool stuff.

= 0.2.4 =
- file browser: search and filter dates should work now
- file browser: pagination was a bit off and could miss files

= 0.2.3 =
- some problems with file browser (some problems still exist)
- added a "Show custom field keys"-link to post edit screen. Clicking this link will reveal the internal custom field keys that are being used to save each simple field value. This key can be used together with for example get_post_meta() or query_posts()
- code cleanups. but still a bit messy.
- removed field type "image". use field type "file" instead.

= 0.2.2 =
- can now delete a post connector
- does no longer show deleted connectors in post edit

= 0.2.1 =
- works on PHP < 5.3.0

= 0.2 =
- Still beta! But actually usable.
- added some functions for getting values

= 0.1 =
- First beta version.

