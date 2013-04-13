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

class RoleList extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    public function _setup()
    {
        add_action('load-edit-tags.php', array($this, 'load'));
        add_filter(static::ROLE . '_row_actions', array($this, 'rowActions'));
    }

    public function load()
    {
        $screen = get_current_screen();

        if (empty($screen->taxonomy) || static::ROLE !== $screen->taxonomy) {
            return;
        }

        add_filter("manage_{$screen->id}_columns", array($this, 'columns'));
        add_action('admin_head', array($this, 'head'));
    }

    public function columns($cols)
    {
        if (isset($cols['slug'])) {
            unset($cols['slug']);
        }

        return $cols;
    }

    public function head()
    {
        ?>
        <style type="text/css">
            .fixed .column-posts { width: 20%; }
        </style>
        <?php
    }

    public function rowActions($actions)
    {
        if (isset($actions['view'])) {
            unset($actions['view']);
        }

        return $actions;
    }
}
