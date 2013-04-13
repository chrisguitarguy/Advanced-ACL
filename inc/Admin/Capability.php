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

class Capability extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    public function _setup()
    {
        add_action('add_meta_boxes_' . static::CAP, array($this, 'metaBoxes'));
    }

    public function metaBoxes()
    {
        remove_meta_box('submitdiv', static::CAP, 'side');

        add_meta_box(
            'submitdiv',
            __('Save', AACL_TD),
            array($this, 'saveBoxCallback'),
            static::CAP,
            'side',
            'high'
        );
    }

    public function saveBoxCallback($post)
    {
        ?>
        <div style="display:none"><?php submit_button(__('Save', AACL_TD), 'button', 'save'); ?></div>
        <div id="misc-publishing-actions">

            <div class="misc-pub-section">
                <label for="post_status">
                    <?php _e('Status:', AACL_TD); ?>
                    <select name="post_status" id="post_status">
                        <?php foreach ($this->getStati() as $status => $label) {
                            printf(
                                '<option value="%1$s" %2$s>%3$s</option>',
                                esc_attr($status),
                                selected($post->post_status, $status, false),
                                esc_html($label)
                            );
                        } ?>
                    </select>
                </label>
            </div>

        </div>

        <div id="major-publishing-actions" class="submitbox">
            <?php if (current_user_can('delete_post', $post->ID)) {
                echo '<div id="delete-action">';
                printf(
                    '<a class="submitdelete deletion" href="%1$s">%2$s</a>',
                    get_delete_post_link($post->ID),
                    EMPTY_TRASH_DAYS ? __('Move to Trash', AACL_TD) : __('Delete Permanently', AACL_TD)
                );
                echo '</div>';
            } ?>

            <div id="publishing-action">
                <?php submit_button(__('Save', AACL_TD), 'primary button-large', 'save', false); ?>
            </div>

            <div class="clear"> </div>
        </div>
        <?php
    }

    private function getStati()
    {
        return static::filter('capability_statuses', array(
            'draft'         => __('Inactive', AACL_TD),
            'publish'       => __('Active', AACL_TD),
        ));
    }
}
