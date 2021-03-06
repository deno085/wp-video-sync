<?php
/*
 * Author: Chris Walker
 * Author URI: http://github.com/deno085
 * License: MIT
*/

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class DenoTimelineListTable extends WP_List_Table
{
    protected $dbTable;
    
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'Timeline',
            'plural' => 'Timelines',
        ));        
        $this->dbTable = 'deno_videosync_timeline';
    }    
    
    function column_cb($item) 
    {
        return  '<input type="checkbox" name="id[]" id="deno-timeline-cb'.$item['id'].'" value="'.$item['id'].'" />';
    }
    
    
    /**
     * this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($item, $column_name)
    {
        switch($column_name)
        {
            case 'name':
                return '<a href="admin.php?page=deno_timeline_form&id='.$item['id'].'">'.$item['name'].'</a>';
                break;
            case 'enabled':
                return ((int)$item['enabled']==1) ? "Yes" : "No";
                break;
            case "videourl":
                return '<a href="'.$item['videourl'].'">'.$item['videourl'].'</a>';
                break;
            default:
                return $item[$column_name];
                break;
        }        
    }    
    
    /**
     * This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'name' => __('Name', 'timeline_table'),
            'videourl' => __('Video', 'timeline_table'),
            'enabled' => __('enabled', 'timeline_table'),
        );
        return $columns;
    }    

    /**
     * This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
            'videourl' => array('email', false),
            'enabled' => array('age', false),
        );
        return $sortable_columns;
    }    
    
    /**
     * Return array of bult actions if has any
     *
     * @return array
     */
    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete',
            'enable' => 'Enable',
            'disable'=> 'Disable'
        );
        return $actions;
    }

    /**
     * This method processes bulk actions
     */
    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->dbTable; 

        $action = $this->current_action();
        switch($action)
        {
            case 'delete':
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) 
                {
                    $ids = implode(',', $ids);
                }
                if (!empty($ids)) 
                    {
                    $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
                }
            break;
            case 'enable':
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) 
                {
                    $ids = implode(',', $ids);
                }
                if (!empty($ids)) 
                    {
                    $wpdb->query("UPDATE $table_name SET enabled=1 WHERE id IN($ids)");
                }
            break;         
            case 'disable':
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) 
                {
                    $ids = implode(',', $ids);
                }
                if (!empty($ids)) 
                    {
                    $wpdb->query("UPDATE $table_name SET enabled=0 WHERE id IN($ids)");
                }
            break;                
        }
    }    
    
    /**
     * Gets rows from database and prepares them to be shown in table
     */
    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->dbTable;

        $per_page = 5; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
} 