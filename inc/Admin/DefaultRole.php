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

use Chrisguitarguy\AdvancedACL\RoleAlias;

class DefaultRole extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    public function _setup()
    {
        add_action('admin_init', array($this, 'register'));
    }

    public function register()
    {
        register_setting('general', static::D_ROLE, array($this, 'cleaner'));

        add_settings_field(
            static::D_ROLE,
            __('Default Advanced Role', AACL_TD),
            array($this, 'field'),
            'general',
            'default',
            array('label_for' => static::D_ROLE)
        );
    }

    public function cleaner($in)
    {
        return absint($in);
    }

    public function field($args)
    {
        wp_dropdown_categories(array(
            'show_option_none'  => __('Default Advanced Role...', AACL_TD),
            'hide_empty'        => false,
            'id'                => $args['label_for'],
            'name'              => $args['label_for'],
            'taxonomy'          => static::A_ROLE,
            'selected'          => get_option(static::D_ROLE),
        ));
    }
}
