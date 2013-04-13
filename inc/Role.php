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

class Role extends ACLBase
{
    public function _setup()
    {
        add_action('init', array($this, 'register'));
    }

    public function register()
    {
        $labels = static::filter('role_tax_labels', array(
            'name'              => __('Roles', AACL_TD),
            'singular_name'     => __('Role', AACL_TD),
            'all_items'         => __('All Roles', AACL_TD),
            'edit_item'         => __('Edit Role', AACL_TD),
            'update_item'       => __('Update Role', AACL_TD),
            'add_new_item'      => __('New Role', AACL_TD),
            'new_item_name'     => __('Role Name', AACL_TD),
            'parent_item'       => __('Parent Role', AACL_TD),
            'parent_item_colon' => __('Parent Role:', AACL_TD),
            'search_items'      => __('Search Roles', AACL_TD),
            'popular_items'     => __('Frequently Used Roles', AACL_TD),
            'not_found'         => __('No Roles Found', AACL_TD),
        ));

        $cap = static::getEditCap();

        $args = static::filter('role_tax_args', array(
            'label'             => __('Roles', AACL_TD),
            'labels'            => $labels,
            'public'            => false,
            'query_var'         => false,
            'rewrite'           => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'capabilities'      => array(
                'manage_terms'      => $cap,
                'edit_terms'        => $cap,
                'delete_terms'      => $cap,
                'assign_terms'      => $cap,
            ),
        ));

        register_taxonomy(static::ROLE, static::CAP, $args);
    }

    public static function getCapsForRole($term_id)
    {
        $term = get_term($term_id);

        if (static::ROLE !== $term->taxonomy) {
            return array();
        }

        $terms = static::resolveParent($terms, array($term_id));

        $posts = get_posts(array(
            'post_type'         => static::CAP,
            'nopaging'          => true,
            'suppress_filters'  => false,
            'tax_query'         => array(
                array(
                    'taxonomy'          => static::ROLE,
                    'terms'             => $terms,
                    'include_children'  => false,
                    'operator'          => 'IN',
                ),
            ),
        ));

        $caps = array_map(function($post) {
            return $post->post_title;
        }, $posts);

        return $caps;
    }

    private static function resolveParent($term, $parents=array())
    {
        if (empty($term) || is_wp_error($term) || 0 == $term->parent) {
            return $parents;
        }

        // push parent on the parents array
        $parents[] = $term->parent;

        // check the next term to see if it has a parent
        return static::resolveParent(get_term($term->parent), $parents);
    }
}
