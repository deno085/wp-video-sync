<?php
/**
 * Plugin Name: WP Video Sync
 * Plugin URI: http://github.com/deno085/wp-video-sync
 * Description: Manage the syncing of video time line to WP content
 * Version: 1.0.0
 * Author: Chris Walker
 * Author URI: http://github.com/deno085
 * Text Domain: denovideosync
 * License: MIT
 */
require_once('wp-plugin-core/PluginBase.php');
require_once('deno-timeline-list-table.php');
require_once('deno-video-sync-admin.php');
require_once('deno-video-sync-schema.php');

class DenoVideoSync extends \DenoPluginCore\PluginBase
{
    const DATA_VERSION = 2;
    public $userId = 0;
    public $user = null;

    public static function getInstance()
    {
        static $instance = null;

        if($instance === null)
        {
            $instance = new DenoVideoSync();
        }

        return $instance;
    }

    protected function __construct()
    {
        parent::__construct();
        $this->optionVarName = 'deno-videosync_options';
    }

    public function doInit()
    {
        $this->customPostType();
        $this->user =  wp_get_current_user();
        $this->userId = $this->user->ID;
        if( !is_admin() )
        {
            wp_enqueue_script('jquery');
            wp_enqueue_script('deno-video-sync', plugins_url('deno-video-sync.js',  __FILE__), false);
            wp_enqueue_script('jquery-video-sync', plugins_url('jquery-video-sync.js',  __FILE__), false);

            wp_localize_script( 'deno-video-sync', 'denoVideoSyncConfig', DenoVideoSync::getInstance()->getConfig());
        }
    }
    
    public function doInstall()
    {
        $this->checkDataVersion();
    }
    
    public function checkDataVersion() 
    {
        $installedVersion = DenoVideoSyncSchema::getInstance()->getInstalledVersion();
        if($installedVersion != DenoVideoSync::DATA_VERSION)
        {
            DenoVideoSyncSchema::getInstance()->migrateData();
        }
        
    }

    public function customPostType()
    {
        $labels = array(
            'name'                => __( 'Sync Content'),
            'singular_name'       => __( 'Sync Content'),
            'menu_name'           => __( 'Sync Content'),
            'parent_item_colon'   => __( 'Parent Content'),
            'all_items'           => __( 'All Sync Content'),
            'view_item'           => __( 'View Content'),
            'add_new_item'        => __( 'Add New Content'),
            'add_new'             => __( 'Add New'),
            'edit_item'           => __( 'Edit Content'),
            'update_item'         => __( 'Update Content'),
            'search_items'        => __( 'Search Content'),
            'not_found'           => __( 'Not Found'),
            'not_found_in_trash'  => __( 'Not found in Trash'),
        );

        $args = array(
            'label'               => __( 'deno_videosync_content' ),
            'description'         => __( 'Video Sync Content'),
            'labels'              => $labels,
            // Features this CPT supports in Post Editor
            'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields' ),
            // You can associate this CPT with a taxonomy or custom taxonomy.
            'taxonomies'          => array( 'category', 'post_tag' ),
            /* A hierarchical CPT is like Pages and can have
            * Parent and child items. A non-hierarchical CPT
            * is like Posts.
            */
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'page',
        );

        // Registering your Custom Post Type
        register_post_type( 'deno_sync_content', $args );
    }

    public function queryPostType($query)
    {
        if(is_category() || is_tag())
        {
            $post_type = $query->get('post_type'); 
            if($post_type)
            {
                $post_type = $post_type;
            }
            else
            {
                $post_type = array('post', 'deno-videosync-content');
            }
            $query->set('post_type',$post_type);
            return $query;
        }
    }
    
    public function getContentPreview($contentType, $contentData, $max_len=70)
    {
        $content = '';
        switch($contentType)
        {
            case 'text':
                $content = $contentData;
            break;
            case 'post':
                $postId = (int)$contentData;
                $post = get_post($postId);
                $content = $post->post_title;
            break;
        }
        if(strlen($contentData) > $max_len)
        {
            $content = substr($content,0, $max_len);
            $content .= '..';
        }        
        return $content;
    }
}

/* WP event hooks */
register_activation_hook(__FILE__, array(DenoVideoSync::getInstance(), 'doInstall'));    

add_action( 'plugins_loaded', array(DenoVideoSync::getInstance(), 'checkDataVersion'));
add_action('init',  array(DenoVideoSync::getInstance(), 'doInit'), 0);
 
if(is_admin())
{
    wp_enqueue_script('underscore');
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_script('deno-video-sync-admin', plugins_url('admin/deno-video-timeline-admin.js',  __FILE__), false);
    foreach($wp_scripts->registered as $script=>$obj)
    {
        if($script=='jquery-ui-core')
        {
            $jqueryUIver = $obj->ver;
            break;
        }
    }
    wp_enqueue_style("deno-timeline-jquery-ui-css", "http://ajax.googleapis.com/ajax/libs/jqueryui/$jqueryUIver/themes/ui-lightness/jquery-ui.min.css");
    add_action('admin_menu', array(DenoVideoSyncAdmin::getInstance(), 'adminMenu'));
    add_action('admin_init', array(DenoVideoSyncAdmin::getInstance(), 'adminInit'));
}
