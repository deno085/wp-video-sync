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
require_once('admin/deno-timeline-list-table.php');
require_once('deno-video-sync-admin.php');
require_once('deno-video-sync-schema.php');

class DenoVideoSync extends \DenoPluginCore\PluginBase
{
    const DATA_VERSION = 2;
    public $userId = 0;
    public $user = null;
    protected $timelines = array();
    
    /**
     * Get the singleton instance of the plugin
     * @staticvar type $instance
     * @return \DenoVideoSync
     */
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

    /**
     * Initialized the plugin whether in front-end or admin
     * - Creates the custom post type
     * - loads the javscript dependencies
     * - localizes the front-end JS
     */
    public function doInit()
    {
        $this->customPostType();
        $this->user =  wp_get_current_user();
        $this->userId = $this->user->ID;
        if( !is_admin() )
        {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-video-sync', plugins_url('js/jquery-video-sync.js',  __FILE__), false);
            wp_enqueue_script('deno-video-sync', plugins_url('js/deno-video-sync.js',  __FILE__), false);
            
            wp_localize_script( 'deno-video-sync', 'denoVideoSyncConfig', DenoVideoSync::getInstance()->getConfig());
        }
    }

    /**
     * returns data which is converted to json included in the plugin's primary JS file.
     * Usually, this function would return just the plugin's configuration or state information,
     * but here we're including the timeline data as well.
     * @return array
     */
    public function getConfig()
    {
        $config = array();
        $config['pluginConfig'] = parent::getConfig();
        $config['timelines'] = array();
        $timelines = $this->getTimelines();
        foreach($timelines as $timeline)
        {
            $contentItems = $this->getTimelineContent($timeline['id']);
            $config['timelines'][$timeline['id']] = array(
                'contentContainer'=>$timeline['container'],
                'content' => $contentItems
            );         
        }
        return $config;
    }
    
    /**
     * Called when the plugin is installed
     */
    public function doInstall()
    {
        $this->checkDataVersion();
    }
    
    /**
     * Uses the schema classes to ensure the plugin's schema and data is up to date.
     */
    public function checkDataVersion() 
    {
        $installedVersion = DenoVideoSyncSchema::getInstance()->getInstalledVersion();
        if($installedVersion != DenoVideoSync::DATA_VERSION)
        {
            DenoVideoSyncSchema::getInstance()->migrateData();
        }
        
    }

    /**
     * Returns a list of all enabled timelines
     * 
     * @global type $wpdb
     * @return array
     */
    public function getTimelines()
    {
        global $wpdb;
        
        if(!is_array($this->timelines) || count($this->timelines)==0)
        {
            $table_name = $wpdb->prefix . 'deno_videosync_timeline';
            $this->timelines = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE enabled=1"), ARRAY_A);
        }
        return $this->timelines;
    }
    
    /**
     * Returns a list of timeline content, ordered by the timing, for a given timeline
     * 
     * @global type $wpdb
     * @param int $timelineId
     * @return array
     */
    public function getTimelineContent($timelineId)
    {
        global $wpdb;

        $result = array();
        $table_name = $wpdb->prefix . 'deno_videosync_timeline_content';
        $items = $wpdb->get_results($wpdb->prepare("SELECT seconds, content_type, content_data FROM $table_name WHERE timeline_id=".$timelineId." ORDER BY seconds ASC"), ARRAY_A);
        foreach($items as $item)
        {
            $result[$item['seconds']] = trim($this->getSyncContent($item['content_type'], $item['content_data']));
        }
        return $result;
    }

    /**
     * Hook to add the custom post type to wordpress
     */
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
    
    /**
     * Returns a string representation of the sync content passed in
     * 
     * @param string $contentType
     * @param string $contentData
     * @param int $max_len
     * @return string
     */
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
    
    /**
     * Returns the sync content passed in
     * 
     * @param string $contentType
     * @param string $contentData
     * @return string
     */    
    public function getSyncContent($contentType, $contentData)
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
                $content = $post->post_content;
            break;
        }        
        return $content;
    }

    /**
     * Hook on the wordpress video shortcode to add the plugin's class to the video tag
     * @param type $class
     * @return string
     */
    public function videoShortcodeClassFilter($class)
    {
        return $class . ' deno-timelime-video-instance';
    }
    
    /**
     * Hook to the wordpress video shortcode to customize the output of the shortcode html.
     * Adds attributes to the video tag produced by the shortcode:
     * - data-deno-video-timeline-post-id: post ID being rendered (this is the post the video is embedded in)
     * - data-deno-timeline-id: timeline ID associated with the video
     * @param string $output
     * @param array $atts
     * @param unknown $video
     * @param int $post_id
     * @param string $library
     * @return string
     */
    public function videoShortcodeFilter($output, $atts, $video, $post_id, $library )
    {
        $timelineId = 0;
        if(array_key_exists('sync-timeline', $atts))
        {
            $timelineId = (int)$atts['sync-timeline'];
        }
        if(!$timelineId)
        {
             $timelineId = (int)get_post_meta($post_id, 'deno-timeline-assigned-id', true);
        }
        if(!$timelineId)
        {
            $timelines = $this->getTimelines();
            foreach($timelines as $timeline)
            {
                $timelineSrc = $timeline['videourl'];
                foreach($atts as $attr=>$value)
                {
                    if($timelineSrc==$value)
                    {
                        $timelineId = $timeline['id'];
                        break;
                    }
                }
                if($timelineId)
                {
                    break;
                }
            }
        }
        if($timelineId)
        {
            $newAttributes  = 'data-deno-timeline-id="'.$timelineId.'" '; 
            $newAttributes .= 'data-deno-video-timeline-post-id="'.$post_id.'" ';
            $output = str_replace('<video ', '<video '.$newAttributes, $output);
        }
        return $output;
    }

    /**
     * Hook to add a selector to videos in the library to associate a timeline to
     * @param array $form_fields
     * @param WP_Post $post
     * @return array
     */
    public function getAttachmentFields($form_fields, $post)
    {
        if( substr($post->post_mime_type, 0, 5) == 'video' )
        {            
            $value = get_post_meta($post->ID, 'deno-timeline-assigned-id', true);
            $timelines = $this->getTimelines();
            $options = '<option value="">No Content Timeline</option>';
            foreach($timelines as $timeline)
            {                
                $select = ($timeline['id']==$value) ? ' selected' : '';
                $options .= '<option value="'.$timeline['id'].'"'.$select.'>'.$timeline['name'].'</option>';
            }
            
            $form_fields['deno-timeline-assigned-id'] = array(
                'value' => $value ? $value : '',
                'label' => __( 'Content Timeline' ),
                'helps' => __( 'Select a content sync timeline' ),
                'input' => 'html',
                'html'  => "<select name=\"attachments[{$post->ID}][deno-timeline-assigned-id]\">$options</select>"
            );
        }
        return $form_fields;
    }
    
    /**
     * Hook to save custom fields associated with videos in the WP library
     * @param WP_Post $post
     * @param array $attachment
     * @return array
     */
    function saveAttachmentFields($post, $attachment) 
    {     
        if(array_key_exists('deno-timeline-assigned-id', $attachment))
        {
            if( false)
            {
                // adding our custom error
                $post['errors']['deno-timeline-assigned-id']['errors'][] = __('Unable to load video sync timeline');
            }
            else
            {
                if($attachment['deno-timeline-assigned-id'] !== '')
                {
                    update_post_meta($post['ID'], 'deno-timeline-assigned-id', $attachment['deno-timeline-assigned-id']);
                }
                else
                {
                    delete_post_meta($post['ID'], 'deno-timeline-assigned-id');
                }                    
            }
        }
        
        return $post;
    }    
}

/* WP event hooks */
register_activation_hook(__FILE__, array(DenoVideoSync::getInstance(), 'doInstall'));    

add_action( 'plugins_loaded', array(DenoVideoSync::getInstance(), 'checkDataVersion'));
add_action('init',  array(DenoVideoSync::getInstance(), 'doInit'), 0);

//Customizes the output of the [video] shortcode
add_filter('wp_video_shortcode', array(DenoVideoSync::getInstance(), 'videoShortcodeFilter'), null, 6);

//adds a class to the video tag in the html rendering of the [video] shortcone
add_filter('wp_video_shortcode_class', array(DenoVideoSync::getInstance(), 'videoShortcodeClassFilter'));

//Adds a custom field to the media library for videos
add_filter('attachment_fields_to_edit', array(DenoVideoSync::getInstance(), 'getAttachmentFields' ), null, 2);
add_filter('attachment_fields_to_save', array(DenoVideoSync::getInstance(), 'saveAttachmentFields'), null, 2);

if(is_admin())
{
    //admin specific init
    add_action('admin_menu', array(DenoVideoSyncAdmin::getInstance(), 'adminMenu'));
    add_action('admin_init', array(DenoVideoSyncAdmin::getInstance(), 'adminInit'));
}
