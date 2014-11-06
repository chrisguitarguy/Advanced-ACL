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

class UserEdit extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    const NONCE = 'aacl_user_edit';

    public function _setup()
    {
        foreach (array('edit_user_profile_update', 'personal_options_update') as $a) {
            add_action($a, array($this, 'save'));
        }

        foreach (array('show_user_profile', 'edit_user_profile') as $a) {
            add_action($a, array($this, 'fields'));
        }
    }

    public function save($user_id)
    {
        if (!current_user_can(static::getEditCap())) {
            return;
        }

        if (
            !isset($_POST[static::NONCE]) ||
            !wp_verify_nonce($_POST[static::NONCE], static::NONCE . $user_id)
        ) {
            return;
        }

        $terms = isset($_POST['tax_input'][static::A_ROLE]) ? $_POST['tax_input'][static::A_ROLE] : array();

        $terms = array_filter(array_map('absint', $terms));

        wp_set_object_terms($user_id, $terms, static::A_ROLE, false);

        wp_cache_delete($user_id, self::CACHE_USERROLES);
        wp_cache_delete($user_id, self::CACHE_USERCAPS);
    }

    public function fields($user)
    {
        if (!current_user_can(static::getEditCap())) {
            return;
        }

        wp_nonce_field(static::NONCE . $user->ID, static::NONCE, false);

        echo '<h3>', esc_html__('Advanced Roles', AACL_TD), '</h3>';

        echo '<ul class="aacl-terms-list">';
        wp_terms_checklist($user->ID, array(
            'taxonomy'          => static::A_ROLE,
            'checked_on_top'    => false,
        ));
        echo '</ul>';
    }
}
