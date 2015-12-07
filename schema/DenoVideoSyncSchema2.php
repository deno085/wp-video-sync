<?php
/*
 * Author: Chris Walker
 * Author URI: http://github.com/deno085
 * License: MIT
*/

class DenoVideoSyncSchema2 implements \DenoPluginCore\SchemaInterface
{
    protected $currentVersion;
   
    public function __construct()
    {
        $this->currentVersion = get_option('deno-videosync-db_version');
    }    

    public function checkSchema()
    {
        $installedVersion = get_option('deno-videosync-db_version');
        return ($installedVersion == DenoVideoSync::DATA_VERSION);
    }
    
    public function updateSchema()
    {
   	global $wpdb;

        $table = $wpdb->prefix.'deno_videosync_timeline_content';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . $table . " (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `timeline_id` int(11) NOT NULL DEFAULT 0,
        `seconds` int(11) NOT NULL DEFAULT 0,
        `content_type` varchar(50) NOT NULL DEFAULT 'text',
        `content_data` TEXT NULL,
        UNIQUE KEY id (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option( "deno-videosync-db_version", "2" );   
    }
    
}

