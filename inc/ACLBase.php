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

abstract class ACLBase
{
    const CAP       = 'aacl_cap'; // capability post type
    const ROLE      = 'aacl_role'; // role taxonomy
    const A_ROLE    = 'aacl_urole'; // user role taxonomy, alias of ROLE
    const VER       = '0.1';
    const D_ROLE    = 'aacl_default_role'; // the default role options key

    // content restriction
    const ENABLE_FIELD      = '_aacl_enable_restriction';
    const RESTRICT_FIELD    = '_aacl_required_caps';

    const CACHE_USERCAPS    = 'aacl_user_caps';
    const CACHE_USERROLES   = 'aacl_user_roles';
    const CACHE_ROLECAPS    = 'aacl_caps_role';

    private static $reg = array();

    public static function instance()
    {
        $cls = get_called_class();

        if (!isset(self::$reg[$cls])) {
            self::$reg[$cls] = new $cls;
        }

        return self::$reg[$cls];
    }

    public static function init()
    {
        add_action('plugins_loaded', array(static::instance(), '_setup'));
    }

    public static function filter()
    {
        $args = func_get_args();

        if (count($args) < 2) {
            return false;
        }

        $args[0] = static::prefixHook($args[0]);

        return call_user_func_array('apply_filters', $args);
    }

    public static function act()
    {
        $args = func_get_args();

        if (!$args) {
            return false;
        }

        $args[0] = static::prefixHook($args[0]);

        return call_user_func_array('do_action', $args);
    }

    public static function getPostRestrictions($post_id)
    {
        $caps = get_post_meta($post_id, static::RESTRICT_FIELD, true);

        return static::filter(
            'post_restriction_caps',
            $caps ? explode(',', $caps) : array(),
            $post_id
        );
    }

    public static function userCanRead($post_id, $caps=null)
    {
        // admins can read anything, no exceptions
        if (current_user_can(static::getEditCap())) {
            return true;
        }

        // lead plugins completely short circut this.
        if (null !== $can_read = static::filter('pre_user_can_read', null, $post_id, $caps)) {
            return $can_read;
        }

        if (!$caps) {
            $caps = static::getPostRestrictions($post_id);
        }

        $can_read = true;

        if ($caps) {
            $can_read = false;

            foreach ($caps as $cap) {
                if (current_user_can($cap)) {
                    $can_read = true;
                    break;
                }
            }
        }

        return static::filter('user_can_read', $can_read, $post_id, $caps);
    }

    abstract public function _setup();

    protected static function getEditCap()
    {
        return static::filter('edit_capability', 'manage_options');
    }

    protected static function getPostTypes()
    {
        $types = get_post_types(array(
            'public'    => true,
        ));

        return static::filter('content_restriction_types', $types);
    }

    protected static function prefixHook($name)
    {
        return "advancedacl_{$name}";
    }
}
