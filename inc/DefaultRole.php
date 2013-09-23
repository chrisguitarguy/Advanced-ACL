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

class DefaultRole extends ACLBase
{
    public function _setup()
    {
        add_action('user_register', array($this, 'setDefaultRole'));
    }

    public function setDefaultRole($user_id)
    {
        $role = absint($this->getDefaultRole());
        if (!$role || !term_exists($role, static::A_ROLE)) {
            return;
        }

        wp_set_object_terms($user_id, array($role), static::A_ROLE, true);

        static::act('set_default_role', $role, $user_id);
    }

    protected function getDefaultRole($user_id)
    {
        return static::filter('default_role', get_option(static::D_ROLE), $user_id);
    }
}
