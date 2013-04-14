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
        if (false !== $cached = wp_cache_get($term_id, 'aacl_caps_role')) {
            return $cached;
        }

        $term = get_term($term_id, static::ROLE);

        if (static::ROLE !== $term->taxonomy) {
            return array();
        }

        $terms = static::resolveParent($term, array($term_id));

        $caps = static::getCapsByRoles($terms);

        array_unshift($caps, $term->slug);

        wp_cache_set($term_id, $caps, 'aacl_caps_role');

        return $caps;
    }

    public static function getRolesForUser($user_id)
    {
        global $wpdb;

        if (false !== $cached = wp_cache_get($user_id, 'aacl_user_roles')) {
            return $cached;
        }

        $stm = $wpdb->prepare(
            "SELECT t.*, tt.* FROM {$wpdb->terms} AS t"
            . " INNER JOIN {$wpdb->term_taxonomy} as TT ON t.term_id = tt.term_id"
            . " WHERE tt.taxonomy = %s AND t.term_group IN ("
                . " SELECT DISTINCT t2.term_group FROM {$wpdb->terms} AS t2"
                . " INNER JOIN {$wpdb->term_taxonomy} AS tt2 ON t2.term_id = tt2.term_id"
                . " INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt2.term_taxonomy_id"
                . " WHERE tt2.taxonomy = %s AND tr.object_id = %d"
            . " )",
            static::ROLE,
            static::A_ROLE,
            $user_id
        );

        $roles = $wpdb->get_results($stm);

        if ($roles) {
            update_term_cache($roles);
        } else {
            $roles = array();
        }

        wp_cache_set($user_id, $roles, 'aacl_user_roles');

        return static::filter('roles_from_aliases', $roles, $user_id);
    }

    public static function getCapabilitiesForUser($user_id)
    {
        global $wpdb;

        if (false !== $cached = wp_cache_get($user_id, 'aacl_user_caps')) {
            return $cached;
        }

        $roles = static::getRolesForUser($user_id);

        if (!$roles) {
            return array();
        }

        $ids = array_map(function($t) {
            return $t->term_id;
        }, $roles);

        // resolve parents.
        foreach ($roles as $role) {
            $ids = static::resolveParent($role, $ids);
        }

        $caps = static::getCapsByRoles(array_unique($ids));

        foreach ($roles as $role) {
            array_unshift($caps, $role->slug);
        }

        wp_cache_set($user_id, $caps, 'aacl_user_caps');

        return static::filter('caps_for_user', $caps, $roles, $user_id);
    }

    private static function resolveParent($term, $parents=array())
    {
        if (empty($term) || is_wp_error($term) || 0 == $term->parent) {
            return $parents;
        }

        // push parent on the parents array
        $parents[] = $term->parent;

        // check the next term to see if it has a parent
        return static::resolveParent(get_term($term->parent, static::ROLE), $parents);
    }

    private static function getCapsByRoles($role_list)
    {
        $posts = get_posts(array(
            'post_type'         => static::CAP,
            'nopaging'          => true,
            'suppress_filters'  => false,
            'tax_query'         => array(
                array(
                    'taxonomy'          => static::ROLE,
                    'terms'             => $role_list,
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
}
