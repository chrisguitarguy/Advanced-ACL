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

class Ajax extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    private $actions = array(
        'aacl_bulk_add'     => 'bulkAdd',
        'aacl_bulk_remove'  => 'bulkRemove',
    );

    public function _setup()
    {
        foreach ($this->actions as $action => $callback) {
            add_action("wp_ajax_{$action}", array($this, $callback));
        }
    }

    public function bulkAdd()
    {
        if (!$this->valid('aacl_bulk_add')) {
            die('-1');
        }

        $users = isset($_POST['users']) ? $_POST['users'] : false;
        $role = isset($_POST['role']) ? $_POST['role'] : false;

        if (!$role || !$users) {
            die('-1');
        }

        foreach ($users as $user_id) {
            wp_set_object_terms($user_id, array(absint($role)), static::A_ROLE, true);
        }

        die('1');
    }

    public function bulkRemove()
    {
        if (!$this->valid('aacl_bulk_remove')) {
            die('-1');
        }

        $users = isset($_POST['users']) ? $_POST['users'] : false;
        $role = isset($_POST['role']) ? $_POST['role'] : false;

        if (!$role || !$users) {
            die('-1');
        }

        foreach ($users as $user_id) {
            wp_remove_object_terms($user_id, array(absint($role)), static::A_ROLE, true);
        }

        die('1');
    }

    private function valid($action)
    {
        return check_ajax_referer($action, 'token', false);
    }
}
