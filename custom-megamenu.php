<?php
/**
 * Plugin Name: Custom Mega Menu
 * Description: A plugin to create and manage custom mega menus for WordPress using Elementor.
 * Version: 1.1.0
 * Author: Yusuf Hasan
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register Mega Menu Location.
function custom_mega_menu_register_location() {
    register_nav_menu('mega-menu', __('Mega Menu'));
}
add_action('init', 'custom_mega_menu_register_location');

// Add Custom Fields to Menu Items.
function custom_mega_menu_add_fields($item_id, $item, $depth, $args) {
    $enable_mega_menu = get_post_meta($item_id, '_menu_item_mega_menu', true);
    $template_id = get_post_meta($item_id, '_menu_item_template_id', true);
    $elementor_templates = \Elementor\Plugin::$instance->templates_manager->get_source('local')->get_items();

    ?>
    <p 
        class="field-custom description description-wide">
        <label 
            for="edit-menu-item-mega-menu-<?php echo $item_id; ?>">
            <input 
                type="checkbox" 
                id="edit-menu-item-mega-menu-<?php echo $item_id; ?>" 
                name="menu-item-mega-menu[<?php echo $item_id; ?>]" 
                value="1" <?php checked($enable_mega_menu, 1); ?>>
            <?php _e('Enable Mega Menu'); ?>
        </label>
    </p>
    <p 
        class="field-custom description description-wide">
        <label 
            for="edit-menu-item-template-<?php echo $item_id; ?>">
            <?php _e('Elementor Template ID (for Mega Menu)'); ?><br>
            <select 
                id="edit-menu-item-template-<?php echo $item_id; ?>" 
                name="menu-item-template[<?php echo $item_id; ?>]">
                <option value=""><?php _e('Select a Template'); ?></option>
                <?php
                if (!empty($elementor_templates)) {
                    foreach ($elementor_templates as $template) {
                        if(stripos($template['title'], 'mega menu') !== false){
                            $selected = selected($template_id, $template['template_id'], false);
                            echo '<option value="' . esc_attr($template['template_id']) . '" ' . $selected . '>' . esc_html($template['title']) . '</option>';
                        }
                    }
                } else {
                    echo '<option value="">' . __('No templates found') . '</option>';
                }
                ?>
            </select>
        </label>
    </p>
    <?php
}


add_action('wp_nav_menu_item_custom_fields', 'custom_mega_menu_add_fields', 10, 4);

// Save Custom Fields.
function custom_mega_menu_save_fields($menu_id, $menu_item_db_id) {
    if (isset($_POST['menu-item-mega-menu'][$menu_item_db_id])) {
        update_post_meta($menu_item_db_id, '_menu_item_mega_menu', 1);
    } else {
        delete_post_meta($menu_item_db_id, '_menu_item_mega_menu');
    }

    if (isset($_POST['menu-item-template'][$menu_item_db_id])) {
        update_post_meta($menu_item_db_id, '_menu_item_template_id', sanitize_text_field($_POST['menu-item-template'][$menu_item_db_id]));
    } else {
        delete_post_meta($menu_item_db_id, '_menu_item_template_id');
    }
}
add_action('wp_update_nav_menu_item', 'custom_mega_menu_save_fields', 10, 2);

// Walker Class for Mega Menu.
class Custom_Mega_Menu_Walker extends Walker_Nav_Menu {
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes)?[]:(array)$item->classes;
        $classes[] = 'menu-item-'.$item->ID;
        $enable_mega_menu = get_post_meta($item->ID, '_menu_item_mega_menu', true);
        if($enable_mega_menu){
            $classes[] = 'has-mega-menu';
        }

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? 'class="'.esc_attr($class_names).'"': '';

        $id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth);
        $id = $id ? 'id="'.esc_attr($id).'"':'';

        $output .= '<li ' . $id . $class_names . '>';
        
        $atts = [
            'title'         => !empty($item->attr_title)?$item->attr_title:'',
            'target'        => !empty($item->target)?$item->target:'',
            'rel'           => !empty($item->xfn) ? $item->xfn : '',
            'href'          => !empty($item->url)?$item->url:'',
            'aria-current'  => $item->current? 'page':''
        ];

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $attributes .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
        }
        
        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
        $item_output .= '</a>';
        $item_output .= $args->after;

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);

        $template_id = get_post_meta($item->ID, '_menu_item_template_id', true);
        if ($enable_mega_menu && $template_id) {
            $output .= '<div class="mega-menu-content">';
            $output .= $this->render_elementor_template($template_id);
            $output .= '</div>';
        }
    }

    public function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= '</li>';
    }

    private function render_elementor_template($template_id) {
        if (!class_exists('\Elementor\Plugin')) {
            return '<p>Elementor is not active.</p>';
        }

        $elementor_instance = \Elementor\Plugin::instance();
        $frontend = $elementor_instance->frontend;
        $template_content = $frontend->get_builder_content_for_display($template_id, true);

        if (!$template_content) {
            return '<p>Invalid or empty template.</p>';
        }

        return $template_content;
    }
}


// Render Mega Menu with Custom Walker.
function custom_mega_menu_display($args) {
    $args['walker'] = new Custom_Mega_Menu_Walker();
    return $args;
}
add_filter('wp_nav_menu_args', 'custom_mega_menu_display');


// Register Mega Menu Templates Admin Page.
function custom_mega_menu_admin_page() {
    add_menu_page(
        __('Mega Menu Templates', 'textdomain'),
        __('Mega Menu Templates', 'textdomain'),
        'manage_options',
        'mega-menu-templates',
        'custom_mega_menu_admin_page_callback',
        'dashicons-menu',
        60
    );
}
add_action('admin_menu', 'custom_mega_menu_admin_page');

// Admin Page Callback.
function custom_mega_menu_admin_page_callback() {
    if (!class_exists('\Elementor\Plugin')) {
        echo '<div class="notice notice-error"><p>' . __('Elementor plugin is required for this feature.', 'textdomain') . '</p></div>';
        return;
    }

    $elementor_templates = \Elementor\Plugin::$instance->templates_manager->get_source('local')->get_items();
    ?>
    <div class="wrap">
        <h1><?php _e('Mega Menu Templates', 'textdomain'); ?></h1>

        <a href="<?php echo admin_url('post-new.php?post_type=elementor_library'); ?>" class="button button-primary" style="margin-bottom: 20px;">
            <?php _e('Add New Template', 'textdomain'); ?>
        </a>
        
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Template Name', 'textdomain'); ?></th>
                    <th><?php _e('Type', 'textdomain'); ?></th>
                    <th><?php _e('Author', 'textdomain'); ?></th>
                    <th><?php _e('Template ID', 'textdomain'); ?></th>
                    <th><?php _e('Actions', 'textdomain'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($elementor_templates)) {
                    foreach ($elementor_templates as $template) {
                        if (stripos($template['title'], 'mega menu') !== false) {
                            echo '<tr>';
                            echo '<td>' . esc_html($template['title']) . '</td>';
                            echo '<td>' . esc_html($template['type']) . '</td>';
                            echo '<td>' . esc_html($template['author']) . '</td>';
                            echo '<td>' . esc_html($template['template_id']) . '</td>';
                            echo '<td><a href="' . admin_url('post.php?post=' . $template['template_id'] . '&action=edit') . '" class="button button-primary">' . __('Edit', 'textdomain') . '</a></td>';
                            echo '</tr>';
                        }
                    }
                } else {
                    echo '<tr><td colspan="3">' . __('No templates found.', 'textdomain') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Fetch All Elementor Templates for Mega Menu Field.
function custom_mega_menu_fetch_templates() {
    if (!class_exists('\Elementor\Plugin')) {
        return [];
    }

    $elementor_templates = \Elementor\Plugin::$instance->templates_manager->get_source('local')->get_items();
    $mega_menu_templates = [];

    foreach ($elementor_templates as $template) {
        if (stripos($template['title'], 'mega menu') !== false) {
            $mega_menu_templates[] = $template;
        }
    }

    return $mega_menu_templates;
}




