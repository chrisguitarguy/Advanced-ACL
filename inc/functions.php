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

use Chrisguitarguy\AdvancedACL;

function advancedacl_load()
{
    AdvancedACL\Capability::init();
    AdvancedACL\Role::init();
    AdvancedACL\RoleAlias::init();
    AdvancedACL\ContentRestriction::init();

    if (is_admin()) {
        AdvancedACL\Admin\CapabilityEdit::init();
        AdvancedACL\Admin\CapabilityList::init();
        AdvancedACL\Admin\RoleList::init();
        AdvancedACL\Admin\UserList::init();
        AdvancedACL\Admin\UserEdit::init();
        AdvancedACL\Admin\ContentRestriction::init();
        AdvancedACL\Admin\Ajax::init();
        AdvancedACL\Admin\MigrationManager::init();
    }

    if (class_exists('bbPress', false)) {
        AdvancedACL\BBPress::init();
    }

    do_action('advancedacl_loaded');
}
