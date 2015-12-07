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
            wp_enqueue_script('jquery-video-sync', plugins_url('js/jquery-video-sync.js',  __FILE__), false);
            wp_enqueue_script('deno-video-sync', plugins_url('js/deno-video-sync.js',  __FILE__), false);
            
            wp_localize_script( 'deno-video-sync', 'denoVideoSyncConfig', DenoVideoSync::getInstance()->getConfig());
        }
    }
    
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
    
    public function videoShortcodeClassFilter($class)
    {
        return $class . ' deno-timelime-video-instance';
    }
    
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
     * @param array $post
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

add_filter('wp_video_shortcode', array(DenoVideoSync::getInstance(), 'videoShortcodeFilter'), null, 6);

//adds a class to the video tag in the html renderings
add_filter('wp_video_shortcode_class', array(DenoVideoSync::getInstance(), 'videoShortcodeClassFilter'));

//Adds a custom field to the media library for videos
add_filter('attachment_fields_to_edit', array(DenoVideoSync::getInstance(), 'getAttachmentFields' ), null, 2);
add_filter('attachment_fields_to_save', array(DenoVideoSync::getInstance(), 'saveAttachmentFields'), null, 2);

if(is_admin())
{
    add_action('admin_menu', array(DenoVideoSyncAdmin::getInstance(), 'adminMenu'));
    add_action('admin_init', array(DenoVideoSyncAdmin::getInstance(), 'adminInit'));
}
