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

class CapabilityEdit extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    public function _setup()
    {
        add_action('add_meta_boxes_' . static::CAP, array($this, 'metaBoxes'));
        add_action('load-post.php', array($this, 'loadEditScreen'));
        add_action('load-post-new.php', array($this, 'loadEditScreen'));
        add_filter('post_updated_messages', array($this, 'statusMessages'));
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

    public function loadEditScreen()
    {
        $screen = get_current_screen();

        if (empty($screen->post_type) || static::CAP !== $screen->post_type) {
            return;
        }

        add_action('edit_form_after_title', array($this, 'showContent'));
        add_filter('enter_title_here', array($this, 'enterTitle'));
    }

    public function saveBoxCallback($post)
    {
        submit_button(__('Save', AACL_TD), 'hidden', 'pre_save', false); ?>

        <div id="misc-publishing-actions">

            <?php static::act('before_cap_status', $post); ?>

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

            <?php static::act('after_cap_status', $post); ?>

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

        static::act('after_cap_save', $post);
    }

    public function statusMessages($messages)
    {
        $messages[static::CAP] = array_fill(1, 10, __('Capability updated.', AACL_TD));

        return $messages;
    }

    public function showContent()
    {
        $content = empty($_GET['post']) ? '' : get_post_field('post_content', $_GET['post'], 'raw');

        echo '<h3 style="padding-left: 0; padding-right:0">';
        echo '<label for="cap_desc">', __('Description', AACL_TD), '</label>';
        echo '</h3>';

        echo '<p>';
        printf(
            '<textarea name="content" id="cap_desc" class="widefat" rows="10">%1$s</textarea>',
            esc_textarea($content)
        );
        echo '</p>';
    }

    public function enterTitle($t)
    {
        return __('Capability Name', AACL_TD);
    }

    private function getStati()
    {
        return static::filter('capability_statuses', array(
            'draft'         => __('Inactive', AACL_TD),
            'publish'       => __('Active', AACL_TD),
        ));
    }
}
