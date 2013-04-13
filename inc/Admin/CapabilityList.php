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

class CapabilityList extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    public function _setup()
    {
        add_action('load-edit.php', array($this, 'loadList'));
    }

    public function loadList()
    {
        $screen = get_current_screen();

        if (empty($screen->post_type) || static::CAP !== $screen->post_type) {
            return;
        }

        $this->modifyStatusLabels();

        add_filter('gettext', array($this, 'switchStati'), 10, 3);
        add_filter("manage_{$screen->id}_columns", array($this, 'columns'));
        add_action("manage_{$screen->post_type}_posts_custom_column", array($this, 'columnCallback'), 10, 2);
    }

    public function switchStati($trans, $orig, $domain)
    {
        if ('default' !== $domain) {
            return $trans;
        }

        switch ($orig) {
        case 'Draft':
        case 'Drafts':
            $trans = __('Inactive', AACL_TD);
            break;
        case 'Published':
            $trans = __('Active', AACL_TD);
            break;
        }

        return $trans;
    }

    public function columns($cols)
    {
        if (isset($cols['date'])) {
            unset($cols['date']);
        }

        $cols['aacl_desc'] = __('Description', AACL_TD);

        return $cols;
    }

    public function columnCallback($col, $post_id)
    {
        if ('aacl_desc' === $col) {
            echo esc_html(get_post_field('post_content', $post_id, 'raw'));
        }
    }

    private function modifyStatusLabels()
    {
        global $wp_post_statuses;

        // this is a hack, I feel back about it.
        foreach ($wp_post_statuses as $status => $obj) {
            switch ($status) {
            case 'draft':
                $obj->label_count = _n_noop(
                    'Inactive <span class="count">(%s)</span>',
                    'Inactive <span class="count">(%s)</span>',
                    AACL_TD
                );
                break;
            case 'publish':
                $obj->label_count = _n_noop(
                    'Active <span class="count">(%s)</span>',
                    'Active <span class="count">(%s)</span>',
                    AACL_TD
                );
                break;
            }
        }
    }
}
