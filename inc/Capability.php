<?php
/**
 * Advanced ACL
 *
 * @category    WordPress
 * @package     AdvancedACL
 * @since       0.1
 * @author      Christopher Davis <http://christopherdavis.me>
 * @copyright   2013 Christopher Davis
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace Chrisguitarguy\AdvancedACL;

class Capability extends ACLBase
{
    public function _setup()
    {
        add_action('init', array($this, 'registerType'));
        add_filter('wp_insert_post_data', array($this, 'sanitizeTitle'), 10, 2);
    }

    public function registerType()
    {
        $labels = static::filter('capability_type_labels', array(
            'name'                  => __('Capabilities', AACL_TD),
            'singular_name'         => __('Capability', AACL_TD),
            'menu_name'             => __('Advanced ACL', AACL_TD),
            'add_new'               => __('New Capability', AACL_TD),
            'add_new_item'          => __('New Capability', AACL_TD),
            'edit_item'             => __('Edit Capability', AACL_TD),
            'new_item'              => __('New Capability', AACL_TD),
            'search_items'          => __('Search Capabilities', AACL_TD),
            'not_found'             => __('No Capabilities Found', AACL_TD),
            'not_found_in_trash'    => __('No Capabilities in Trash', AACL_TD),
        ));

        $cap = static::getEditCap();
        $args = static::filter('capability_type_args', array(
            'label'                 => __('Capabilities', AACL_TD),
            'labels'                => $labels,
            'public'                => false,
            'show_ui'               => true,
            'show_in_admin_bar'     => false,
            'menu_position'         => 1000,
            'capabilities'          => array(
                'edit_post'             => $cap,
                'read_post'             => $cap,
                'delete_post'           => $cap,
                'edit_posts'            => $cap,
                'edit_others_posts'     => $cap,
                'publish_posts'         => $cap,
                'read_private_posts'    => $cap,
            ),
            'supports'              => array('title'),
            'query_var'             => false,
            'rewrite'               => false,
        ));

        register_post_type(static::CAP, $args);
    }

    public function sanitizeTitle($to_insert, $raw)
    {
        if (isset($to_insert['post_type']) && static::CAP === $to_insert['post_type']) {
            // slugify the title
            $to_insert['post_title'] = sanitize_title_with_dashes($to_insert['post_title'], '', 'save');

            $orig = $to_insert['post_title'];
            $count = 1;
            while ($post_id = $this->capabilityExists($to_insert['post_title'])) {
                // if we get the current post ID back, it's "unique"
                if (!empty($raw['ID']) && $raw['ID'] == $post_id) {
                    break;
                }

                // otherwise make it unique
                $to_insert['post_title'] = $orig . $count++;
            }
        }

        return $to_insert;
    }

    private function capabilityExists($cap)
    {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s",
            $cap,
            static::CAP
        ));
    }
}
