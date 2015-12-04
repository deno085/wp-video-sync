<?php

class DenoVideoSyncAdmin
{
    public static function getInstance()
    {
        static $instance = null;

        if($instance === null)
        {
            $instance = new DenoVideoSyncAdmin();
        }

        return $instance;
    }

    protected function __construct()
    {

    }

    public function adminMenu()
    {
        add_options_page('Video Sync Settings',
            'Video Sync',
            'manage_options',
            'deno-videosync-settings',
            array(DenoVideoSyncAdmin::getInstance(),'renderOptionsPage')
        );
        
        add_menu_page(__('Content Timeline'), __('Content Timelines'), 'activate_plugins', 'deno_timelines', array(DenoVideoSyncAdmin, 'timelineTablePageHandler'));
        add_submenu_page('deno_timelines', __('Content Timelines'), __('Timelines'), 'activate_plugins', 'deno_timelines', array(DenoVideoSyncAdmin, 'timelineTablePageHandler'));
        $page = add_submenu_page('deno_timelines', __('Add new timeline'), __('Add new timeline'), 'activate_plugins', 'deno_timeline_form', array(DenoVideoSyncAdmin, 'timelineFormHandler'));
        
        add_action( 'admin_print_styles-'.$page, array(DenoVideoSyncAdmin::getInstance(), 'timelineFormCss'));
    }
    
    public function timelineFormCSS()
    {
        wp_enqueue_style( 'deno_timeline_css' );
    }

    public function adminInit()
    {
        register_setting(
            'deno-videosync_options',
            'deno-videosync_options',
            array(DenoVideoSyncAdmin::getInstance(), 'adminOptionsValidate')
        );

        add_settings_section(
            'deno-videosync_platform_options',
            'Platform Settings',
            array(DenoVideoSyncAdmin::getInstance(), 'renderOptionsSection'),
            'deno-videosync_sections'
        );

        add_settings_field(
            'platformURL',
            'Platform URL',
            array(DenoVideoSyncAdmin::getInstance(), 'renderOptionPlatformURL'),
            'deno-videosync_sections',
            'deno-videosync_platform_options'
        );
        
        wp_register_style( 'deno_timeline_css', plugins_url('admin/timeline_form.css', __FILE__) );   
        
    }


    public function adminOptionsValidate($input)
    {
        $output = get_option('deno-videosync_options');

        $output['platformURL'] = $input['platformURL'];
        return $output;
    }

    public function renderOptionsPage()
    {
        ob_start();
        include 'admin/settings.php';
        $output = ob_get_contents();
        ob_end_clean();
        echo $output;
    }

    public function renderOptionsSection()
    {
        echo "<p>VideoSync configuration settings</p>";
    }

    public function renderOptionPlatformURL()
    {
        $options = get_option('deno-videosync_options');
        $value = '';
        if(is_array($options) && array_key_exists('platformURL', $options))
        {
            $value = $options['platformURL'];
        }
        echo "<input id='sitm-option-platformURL' name='deno-videosync_options[platformURL]' size='40' type='text' value='$value' />";
    }

    /**
     * List page handler
     *
     * This function renders our custom table
     * Notice how we display message about successfull deletion
     * Actualy this is very easy, and you can add as many features
     * as you want.
     *
     * Look into /wp-admin/includes/class-wp-*-list-table.php for examples
     */    
    public function timelineTablePageHandler()
    {
        global $wpdb;

        $table = new DenoTimelineListTable();
        $table->prepare_items();

        $message = '';
        if ('delete' === $table->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d'), count($_REQUEST['id'])) . '</p></div>';
        }
        $page = $_REQUEST['page'];
        echo '<div class="wrap">';
        echo '<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>';
        echo '<h2>'.__('Timelines').' <a class="add-new-h2" href="'.get_admin_url(get_current_blog_id(), 'admin.php?page=deno_timeline_form').'">'.__('Add new').'</a></h2>';
        echo $message;
        echo '<form id="persons-table" method="GET"><input type="hidden" name="page" value="'.$page.'"/>';
        echo $table->display();
        echo '</form>';
        echo '</div>';
    }
    
    public function timelineFormHandler()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'deno_videosync_timeline'; 
        $content_table = $wpdb->prefix . 'deno_videosync_timeline_content';
        
        $message = '';
        $notice = '';

        // this is default $item which will be used for new records
        $default = array(
            'id' => 0,
            'name' => '',
            'videourl' => '',
            'enabled' => 0,
        );

        if(wp_verify_nonce($_REQUEST['nonce'], 'deno_timeline_edit')) 
        {
            $item = array();
            foreach($default as $field=>$value)
            {
                $item[$field] = (array_key_exists($field, $_REQUEST)) ? $_REQUEST[$field] : $default[$field];
            }
            $item_valid = self::timelineValidate($item);
            if($item_valid===true) 
            {
                if($item['id'] == 0) 
                {
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;
                    if($result) 
                    {
                        $jsonData = stripslashes($_REQUEST['contentData']);
                        $content = json_decode($jsonData, true);
                        if(is_array($content))
                        {
                            foreach($content as $contentItem)
                            {
                                $contentItem['timeline_id'] = $item['id'];
                                $contentResult = $wpdb->insert($content_table, $contentItem);
                                $contentItem['id'] = $wpdb->insert_id;
                                $item['content'][] = $contentItem;
                            }
                        }
                        $message = __('Item was successfully saved');
                    } 
                    else 
                    {
                        $notice = __('There was an error while saving item');
                    }
                } 
                else 
                {
                    $wpdb->update($table_name, $item, array('id' => $item['id']));
                    if($wpdb->result) 
                    {
                        $wpdb->delete($content_table,"timeline_id= ".$item['id']);
                        $jsonData = stripslashes($_REQUEST['contentData']);
                        $content = json_decode($jsonData, true);
                        foreach($content as $contentItem)
                        {
                            $contentItem['timeline_id'] = $item['id'];
                            $contentResult = $wpdb->insert($content_table, $contentItem);
                            $contentItem['id'] = $wpdb->insert_id;
                            $item['content'][] = $contentItem;
                        }                        
                        $message = __('Item was successfully updated');
                    }    
                    else 
                    {
                        $notice = __('There was an error while updating item');
                    }
                }
            } 
            else 
            {
                $notice = $item_valid;
            }
        }
        else 
        {
            // if this is not post back we load item to edit or give new one to create
            $item = $default;
            if(isset($_REQUEST['id'])) 
            {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
                if(!$item) 
                {
                    $item = $default;
                    $notice = __('Item not found');
                }
                else
                {
                    $item['content'] = $wpdb->get_results("SELECT * FROM $content_table WHERE timeline_id=".$item['id'], ARRAY_A);
                }
            }
        }        
        
        if(!array_key_exists('content', $item))
        {
            $item['content'] = array();
        }
        
        for($x=0;$x<count($item['content']); $x++)
        {
            $item['content'][$x]['preview'] = DenoVideoSync::getInstance()->getContentPreview($item['content'][$x]['content_type'], $item['content'][$x]['content_data']);
        }
        
        // here we adding our custom meta box
        add_meta_box('timeline_form_meta_box', 'Timeline data', array(DenoVideoSyncAdmin, 'timelineFormMetaBoxHandler'), 'timeline', 'normal', 'default');
        add_meta_box('timeline_form_sidebar_box', 'Add new Content', array(DenoVideoSyncAdmin, 'timelineFormNewContentBoxHandler'), 'timeline', 'side', 'default');
        
        $query = new WP_Query(array(
            'post_type'     => 'deno_sync_content',
            'post_status'   => array('publish', 'future')
        ));
        $syncPosts = array();
        while($query->have_posts()) 
        {
            $query->the_post();
            $syncPosts[] = array(
                'id'    => $query->post->ID, 
                'title' => get_the_title($query->post->ID)
            );
        }        
        wp_reset_postdata();
        include('admin/timeline_form.php');   
    }
    
    public function timelineFormMetaBoxHandler($item)
    {
        include('admin/timeline_metabox.php');
    }
    
    public function timelineFormNewContentBoxHandler($item)
    {
        include('admin/new_content_metabox.php');
    }
    
    private static function timelineValidate($item)
    {
        $messages = array();

        if (empty($item['name'])) $messages[] = __('Name is required');
        if (empty($item['videourl'])) $messages[] = __('Video URL is required');
        if (empty($messages)) return true;
        return implode('<br />', $messages);        
    }
}