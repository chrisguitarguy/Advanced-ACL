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

namespace Chrisguitarguy\AdvancedACL\Migration;

use Chrisguitarguy\AdvancedACL\RoleAlias;

class GroupsMigration implements MigrationInterface
{
    public function migrate()
    {
        $cap_map = array();
        foreach ($this->getCapabilities() as $cap) {
            $post_id = wp_insert_post(array(
                'post_type'     => RoleAlias::CAP,
                'post_title'    => $cap->capability,
                'post_content'  => $cap->description ?: '',
                'post_status'   => 'publish',
            ));

            $cap_map[$cap->capability_id] = $post_id;
        }

        $role_map = array();
        foreach ($this->getRoles() as $role) {
            $term = wp_insert_term($role->name, RoleAlias::ROLE, array(
                'description'   => $role->description ?: '',
            ));

            // can't do anythign if we got an error back.
            if (is_wp_error($term)) {
                continue;
            }

            $role_map[$role->group_id] = isset($term['term_id']) ? intval($term['term_id']) : null;

            foreach ($this->getCapsForGroup($role->group_id) as $cap_id) {
                if (!empty($cap_map[$cap_id])) {
                    wp_set_object_terms($cap_map[$cap_id], array($role_map[$role->group_id]), RoleAlias::ROLE, true);
                }
            }
        }

        $user_save = array();
        foreach ($this->getUserGroups() as $obj) {
            if (!isset($role_map[$obj->group_id])) {
                continue;
            }

            $term = get_term($role_map[$obj->group_id], RoleAlias::ROLE);

            $aliases = $this->getRoleAliases($term->term_group);

            if (!$aliases) {
                continue;
            }

            if (!isset($user_save[$obj->user_id])) {
                $user_save[$obj->user_id] = array();
            }

            foreach ($aliases as $a) {
                $user_save[$obj->user_id][] = intval($a);
            }
        }

        foreach ($user_save as $user_id => $terms) {
            wp_set_object_terms($user_id, array_unique($terms), RoleAlias::A_ROLE, true);
        }

        $this->migrateRestricted();

        return true;
    }

    public function getName()
    {
        return __('Groups Migration', AACL_TD);
    }

    private function getCapabilities()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT capability_id, capability, description FROM {$wpdb->prefix}groups_capability");
    }

    private function getRoles()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT group_id, name, description FROM {$wpdb->prefix}groups_group");
    }

    private function getCapsForGroup($group_id)
    {
        global $wpdb;

        return $wpdb->get_col($wpdb->prepare(
            "SELECT capability_id FROM {$wpdb->prefix}groups_group_capability WHERE group_id = %d",
            $group_id
        ));
    }

    private function getUserGroups()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT user_id, group_id FROM {$wpdb->prefix}groups_user_group");
    }

    private function migrateRestricted()
    {
        global $wpdb;

        $meta = $wpdb->get_results(
            "SELECT post_id, GROUP_CONCAT(DISTINCT meta_value SEPARATOR ',') as caps"
            . " FROM {$wpdb->postmeta}"
            . " WHERE meta_key = 'groups-groups_read_post'"
            . " GROUP BY post_id"
        );

        foreach ($meta as $row) {
            update_post_meta($row->post_id, RoleAlias::RESTRICT_FIELD, $row->caps);
        }
    }

    private function getRoleAliases($term_group)
    {
        global $wpdb;

        return $wpdb->get_col($wpdb->prepare(
            "SELECT t.term_id FROM {$wpdb->terms} as t"
            . " INNER JOIN {$wpdb->term_taxonomy} AS tt"
            . " ON t.term_id = tt.term_id"
            . " WHERE t.term_group = %d AND tt.taxonomy = %s",
            $term_group,
            RoleAlias::A_ROLE
        ));
    }
}
