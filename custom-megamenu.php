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

    // $elementor_templates = \Elementor\plugin::$instances-> templates_manager->get_source('local')->get_items();
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
        $enable_mega_menu = get_post_meta($item->ID, '_menu_item_mega_menu', true);
        $template_id = get_post_meta($item->ID, '_menu_item_template_id', true);

        $classes = implode(' ', $item->classes);
        if ($enable_mega_menu) {
            $classes .= ' has-mega-menu';
        }

        $output .= '<li class="viva-custom-megamenu menu-item ' . esc_attr($classes) . '">';

        $output .= '<a href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a>';

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


