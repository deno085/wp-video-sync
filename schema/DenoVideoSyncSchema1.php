<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
        `videourl` varchar(255) NOT NULL,
        `enabled` int(11) NOT NULL,
        UNIQUE KEY id (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option( "deno-videosync-db_version", "1" );   
    }
    
}
