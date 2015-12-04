<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Namespace DenoPluginCore;

require_once('SchemaInterface.php');

abstract class SchemaMigrationBase
{
    abstract public function getInstalledVersion();
    abstract public function getTargetVersion();
    abstract public function getSchemaVersionObject($version);
    
    public function migrateData()
    {
        $endVersion = $this->getTargetVersion();
        $startVersion = $this->getInstalledVersion();
        if($startVersion < $endVersion)
        {
            for($version = $startVersion+1; $version <= $endVersion; $version++)
            {
                $objSchema = $this->getSchemaVersionObject($version);
                if(is_object($objSchema))
                {                    
                    $objSchema->updateSchema();
                    unset($objSchema);
                }
            }
        }
    }    
}
