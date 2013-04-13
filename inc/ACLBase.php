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
    const CAP   = 'aacl_cap';
    const ROLE  = 'aacl_role';

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

    abstract public function _setup();

    private static function prefixHook($name)
    {
        return "advancedacl_{$name}";
    }
}
