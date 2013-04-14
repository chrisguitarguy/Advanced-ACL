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

namespace Chrisguitarguy\AdvancedACL\Admin;

use Chrisguitarguy\AdvancedACL\Role;
use Chrisguitarguy\AdvancedACL\RoleAlias;

class UserList extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    public function _setup()
    {
        add_action('load-users.php', array($this, 'load'));
        add_action('restrict_manage_users', array($this, 'bulkActions'), -1000);
    }

    public function load()
    {
        $screen = get_current_screen();

        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
        add_filter("manage_{$screen->id}_columns", array($this, 'columns'));
        add_filter('manage_users_custom_column', array($this, 'columnCallback'), 10, 3);
    }

    public function enqueue()
    {
        wp_enqueue_style(
            'aacl.user_list',
            AACL_URL . 'css/user_list.css',
            array(),
            static::VER,
            'screen'
        );

        wp_enqueue_script(
            'aacl.user_bulk',
            AACL_URL . 'js/user_bulk.js',
            array('jquery'),
            static::VER
        );

        wp_localize_script('aacl.user_bulk', 'aacl_js', array(
            'add'       => wp_create_nonce('aacl_bulk_add'),
            'remove'    => wp_create_nonce('aacl_bulk_remove'),
            'updated'   => esc_html__('Roles updated.', AACL_TD),
            'error'     => esc_html__('Error updating roles.', AACL_TD),
        ));
    }

    public function columns($cols)
    {
        $cols['aacl_role'] = __('Advanced Roles', AACL_TD);

        return $cols;
    }

    public function columnCallback($rv, $col, $user_id)
    {
        if ('aacl_role' === $col) {
            $roles = Role::getRolesForUser($user_id);

            if (!$roles) {
                $rv = esc_html__('None', AACL_TD);
            } else {
                $links = array();
                foreach ($roles as $role) {
                    $links[] = sprintf(
                        '<a href="%1$s">%2$s</a>',
                        get_edit_term_link($role->term_id, static::ROLE),
                        esc_html($role->name)
                    );
                }

                $rv = implode(', ', $links);
            }
        }

        return $rv;
    }

    public function bulkActions()
    {
        $roles = RoleAlias::getRolesOption();
        ?>
        <div id="aacl-bulk-wrap">
            <?php $this->printSelect('aacl_role_bulk', $roles); ?>
            <a href="#" class="button" id="aacl_role_add_cue"><?php _e('Add Advanced Role', AACL_TD); ?></a>
            <a href="#" class="button" id="aacl_role_remove_cue"><?php _e('Remove Advanced Role', AACL_TD); ?></a>
        </div>
        <?php
    }

    private function printSelect($id, array $options)
    {
        printf('<select name="%1$s" id="%1$s">', esc_attr($id));
        echo '<option>', esc_html__('Advanced Role...', AACL_TD), '</option>';
        foreach ($options as $value => $label) {
            printf('<option value="%1$s">%2$s</option>', esc_attr($value), esc_html($label));
        }
        echo '</select>';
    }
}
