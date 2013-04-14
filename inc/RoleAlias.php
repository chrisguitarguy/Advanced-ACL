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

class RoleAlias extends ACLBase
{
    public function _setup()
    {
        add_action('init', array($this, 'register'));
        add_action('created_' . static::ROLE, array($this, 'create'));
        add_action('edited_' . static::ROLE, array($this, 'edit'));
        add_action('delete_' . static::ROLE, array($this, 'delete'), 10, 3);
    }

    public function register()
    {
        $cap = static::getEditCap();

        $args = static::filter('role_tax_args', array(
            'label'             => __('User Roles', AACL_TD),
            'public'            => false,
            'query_var'         => false,
            'rewrite'           => false,
            'show_ui'           => false,
            'hierarchical'      => false,
            'capabilities'      => array(
                'manage_terms'      => $cap,
                'edit_terms'        => $cap,
                'delete_terms'      => $cap,
                'assign_terms'      => $cap,
            ),
        ));

        register_taxonomy(static::A_ROLE, 'user', $args);
    }

    public function create($term_id)
    {
        $term = get_term($term_id, static::ROLE);

        wp_insert_term($term->name, static::A_ROLE, array(
            'slug'      => $term->slug . '_user',
            'alias_of'  => $term->slug,
        ));
    }

    public function edit($term_id)
    {
        $term = get_term($term_id, static::ROLE);

        $others = $this->getTerms($term->term_group);

        if ($others) {
            foreach ($others as $t) {
                 wp_update_term($t->term_id, static::A_ROLE, array(
                    'name'      => $term->name,
                    'slug'      => $term->slug . '_user',
                    'alias_of'  => $term->slug,
                ));
            }
        } else {
            $this-create($term_id);
        }
    }

    public function delete($term_id, $tt_id, $term)
    {
        global $wpdb;

        foreach ($this->getTerms($term->term_group) as $t) {
            $res = wp_delete_term($t->term_id, static::A_ROLE);
        }
    }

    public function termGroupSql($pieces, $taxonomies, $args)
    {
        global $wpdb;

        if (!isset($pieces['where'])) {
            $pieces['where'] = 'WHERE 1';
        }

        if (isset($args['term_group'])) {
            $pieces['where'] .= $wpdb->prepare(' AND t.term_group = %d', $args['term_group']);
        }

        return $pieces;
    }

    public static function getRolesOption()
    {
        $terms = get_terms(static::A_ROLE, array(
            'hide_empty'    => false,
        ));

        $out = array();

        if (!$terms || is_wp_error($terms)) {
            return $out;
        }

        foreach ($terms as $t) {
            $out[$t->term_id] = $t->name;
        }

        return static::filter('role_dropdown_terms', $out);
    }

    public static function getRolesFromAlias($terms)
    {
        global $wpdb;

        if (!is_array($terms)) {
            $terms = array($terms);
        }

        $terms = array_map('absint', $terms);
        $where = implode(', ', array_fill(0, count($terms), '%d'));

        $sql = "SELECT t.*, tt.* FROM {$wpdb->terms} AS t"
            . " INNER JOIN {$wpdb->term_taxonomy} as TT ON t.term_id = tt.term_id"
            . " WHERE tt.taxonomy = %s AND t.term_group IN ("
                . " SELECT DISTINCT t2.term_group FROM {$wpdb->terms} AS t2"
                . " INNER JOIN {$wpdb->term_taxonomy} as tt2 ON t2.term_id = tt2.term_id"
                . " WHERE tt2.taxonomy = %s AND t2.term_id IN ({$where})"
            . " )";

        $args = array($sql, static::ROLE, static::A_ROLE);
        foreach ($terms as $t) {
            $args[] = $t;
        }

        $stm = call_user_func_array(array($wpdb, 'prepare'), $args);

        $roles = $wpdb->get_results($stm);

        return static::filter('roles_from_aliases', $roles, $terms);
    }

    private function getTerms($term_group)
    {
        static::enableTermGroup();

        $terms = get_terms(static::A_ROLE, array(
            'term_group'    => $term_group,
            'hide_empty'    => false,
        ));

        static::disableTermGroup();

        return $terms;
    }

    private static function enableTermGroup()
    {
        add_filter('terms_clauses', array(static::instance(), 'termGroupSql'), 10, 3);
    }

    private static function disableTermGroup()
    {
        remove_filter('terms_clauses', array(static::instance(), 'termGroupSql'), 10, 3);
    }
}
