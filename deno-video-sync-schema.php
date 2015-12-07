<?php
/*
 * Author: Chris Walker
 * Author URI: http://github.com/deno085
 * License: MIT
*/

require_once 'wp-plugin-core/SchemaMigrationBase.php';

class DenoVideoSyncSchema extends \DenoPluginCore\SchemaMigrationBase
{
    public static function getInstance()
    {
        static $instance = null;

        if($instance === null)
        {
            $instance = new DenoVideoSyncSchema();
        }

        return $instance;
    }    
    
    public function getInstalledVersion()
    {
        return (int)get_option('deno-videosync-db_version');        
    }
    
    public function getTargetVersion()
    {
        return DenoVideoSync::DATA_VERSION;
    }
    
    public function getSchemaVersionObject($version)
    {
        $className = 'DenoVideoSyncSchema'.$version;
        if(!class_exists($className))
        {
            require_once 'schema/'.$className.'.php';
        }
        if(class_exists($className))
        {   
            return new $className();       
        }
        return false;
    }
}