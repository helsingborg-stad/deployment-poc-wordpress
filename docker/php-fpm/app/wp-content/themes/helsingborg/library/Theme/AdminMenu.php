<?php

namespace Helsingborg\Theme;

class AdminMenu
{
    public function __construct()
    {
        add_action('admin_head', array($this, 'menuHighlight'));
        add_action('admin_menu', array($this, 'manageAdminMenu'), 50);
        add_action('admin_menu', array($this, 'removeDefaultActions'), 500);
    }

    /**
     * Add custom post types to Post submenu. (need Nested Pages plugin)
     * @return void
     */
    public function manageAdminMenu()
    {
        if (function_exists('get_field')) {
            $type_definitions = get_field('avabile_dynamic_post_types', 'option');
            if (is_array($type_definitions) && !empty($type_definitions)) {
                foreach ($type_definitions as $type_definition_key => $type_definition) {
                    if ($type_definition['hierarchical']) {
                        remove_menu_page('edit.php?post_type=' . sanitize_title(substr($type_definition['post_type_name'], 0, 19)));
                        add_submenu_page('nestedpages', $type_definition['post_type_name'], $type_definition['post_type_name'], 'publish_posts', 'admin.php?page=nestedpages-' . sanitize_title(substr($type_definition['post_type_name'], 0, 19)));
                    }
                }
            }
        }
    }

    /**
     * Highlights nested page sub menus
     * @return void
     */
    public function menuHighlight()
    {
        global $parent_file, $submenu_file;

        if (isset($_GET['page']) && preg_match('/nestedpages-.*?/i', $_GET['page'])) {
            $parent_file    = 'nestedpages';
            $submenu_file   = 'admin.php?page=' . $_GET['page'];
        }
    }

    /**
     * Remove default "default pages".
     * @return void
     */
    public function removeDefaultActions()
    {
        foreach ((array) array('edit.php?post_type=page') as $item) {
            remove_submenu_page('nestedpages', $item);
        }
    }
}
