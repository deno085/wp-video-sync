<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Namespace DenoPluginCore;

/**
 * Base class for Wordpress plugins
 * @package DenoPluginCore
 */
abstract class PluginBase
{
    abstract public static function getInstance();
    
    /**
     * Name used to store configuration in WP options
     * 
     * @var string 
     */
    protected $optionVarName;
    
    protected function __construct() 
    {
        $this->optionVarName = '';
    }
    
    /**
     * Returns a single config setting 
     * 
     * @param string $name
     * @param mized $default
     * @return mixed
     */
    public function getOption($name, $default='')
    {
        $options = get_option($this->optionVarName);
        if(array_key_exists($name, $options))
        {
            return $options[$name];
        }
        return $default;
    }  

    /**
     * Returns all plugin configuration
     * @return array
     */
    public function getConfig()
    {
        $config = get_option($this->optionVarName);
        if(!is_array($config))
        {
            $config = array();
        }
        return $config;
    }    
    
    /**
     * Returns array containing current user information, if applicable
     * 
     * @return array
     */
    protected function getUserData()
    {
        $user = wp_get_current_user();
        if($user)
        {
            return array(
                'name'          => $user->first_name.' '.$user->last_name,
                'display_name'  => $user->user_login,
                'email'         => $user->user_email
            );
        }
        return array();
    }    
}