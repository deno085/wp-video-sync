<?php
/*
 * Author: Chris Walker
 * Author URI: http://github.com/deno085
 * License: MIT
*/

class DenoVideoSyncSchema1 implements \DenoPluginCore\SchemaInterface
{
    protected $currentVersion;
   
    public function __construct()
    {
        $this->currentVersion = get_option('deno-videosync-db_version');
    }    

    public function checkSchema()
    {
        $installedVersion = get_option('deno-videosync-db_version');
        return ($installedVersion == DenoVideoSync::DSV_DATA_VERSION);
    }
    
    public function updateSchema()
    {
   	global $wpdb;

        $table = $wpdb->prefix.'deno_videosync_timeline';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . $table . " (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `videourl` varchar(255) NOT NULL DEFAULT '',
        `container` varchar(255) NOT NULL DEFAULT '',
        `enabled` int(11) NOT NULL DEFAULT 1,
        UNIQUE KEY id (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option( "deno-videosync-db_version", "1" );   
    }
    
}
