# wp-video-sync
Wordpress plugin to configure the video sync jQuery plugin

The video sync jquery plugin is used to sync text content with a video.  As the video plays, page content changes.  This wordpress plugin creates an admin interface to use this jQuery plugin with the video shortcode.  Admins can create timelines of content based on elapsed seconds of video play time.  Content can be plain text (or raw html) or select from the sync content custom post type.

## Dependencies
This plugin uses jQuery, Underscore, and jQuery UI libraries provided with Wordpress.  It also includes the video sync jQuery plugin available from https://github.com/deno085/jquery-video-sync.  No downloads are necessary to activate the plugin. 

## Activation
When activating the plugin, database tables are created in the current site's database.  These tables are not removed if the plugin is deactivated.

## Usage
The plugin is not 100% plug and play.  Your site's theme may need to be customized to use the plugin, as the plugin will need a container element to change the content for as videos play.  (You can do this without customizing the theme, however you'll need to ensure content authors don't remove the element.)  It is not necessary to add the JavaScript or CSS dependencies to your theme, these are added by the plugin automatically.

