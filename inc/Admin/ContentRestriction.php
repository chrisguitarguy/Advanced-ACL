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

class ContentRestriction extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    const ENABLE_NONCE = 'aacl_enable_nonce';
    const RESTRICT_NONCE = 'aacl_restrict_nonce';

    public function _setup()
    {
        add_action('add_meta_boxes', array($this, 'addBoxes'));
        add_action('advancedacl_after_cap_status', array($this, 'enableRestriction'));
        add_action('save_post', array($this, 'save'), 10, 2);
    }

    public function addBoxes($post_type)
    {
        if (in_array($post_type, static::getPostTypes())) {
            add_meta_box(
                'aacl-content-restriction',
                __('Content Restriction', AACL_TD),
                array($this, 'restrictionBoxCallback'),
                $post_type,
                'side',
                'low'
            );
        }
    }

    public function enableRestriction($post)
    {
        wp_nonce_field(static::ENABLE_NONCE . $post->ID, static::ENABLE_NONCE, false);

        echo '<div class="misc-pub-section">';

        printf(
            '<label for="%1$s"><input type="checkbox" value="1" id="%1$s" name="%1$s" %2$s /> %3$s</label>',
            esc_attr(static::ENABLE_FIELD),
            checked(get_post_meta($post->ID, static::ENABLE_FIELD, true), 'on', false),
            esc_html__('Allow Content Restriction', AACL_TD)
        );

        echo '</div>';
    }

    public function restrictionBoxCallback($post)
    {
        wp_nonce_field(static::RESTRICT_NONCE . $post->ID, static::RESTRICT_NONCE, false);

        $current = static::getPostRestrictions($post->ID);

        foreach ($this->getRestrictionCaps() as $cap) {
            echo '<p>';
            printf(
                '<label for="%1$s[%2$s]"><input type="checkbox" value="%2$s" name="%1$s[]" id="%1$s[%2$s]" %3$s /> %4$s</label>',
                esc_attr(static::RESTRICT_FIELD),
                esc_attr($cap),
                in_array($cap, $current) ? 'checked="checked"' : '',
                esc_html($cap)
            );
            echo '</p>';
        }
    }

    public function save($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (static::CAP === $post->post_type) {
            $this->saveCapability($post_id, $post);
        } elseif (in_array($post->post_type, static::getPostTypes())) {
            $this->saveRestriction($post_id, $post);
        }
    }

    private function saveCapability($post_id, $post)
    {
        if (!$this->nonceOkay(static::ENABLE_NONCE, static::ENABLE_NONCE . $post_id)) {
            return;
        }

        if (!$this->canEdit($post)) {
            return;
        }

        if (empty($_POST[static::ENABLE_FIELD])) {
            delete_post_meta($post_id, static::ENABLE_FIELD);
        } else {
            update_post_meta($post_id, static::ENABLE_FIELD, 'on');
        }
    }

    private function saveRestriction($post_id, $post)
    {
        if (!$this->nonceOkay(static::RESTRICT_NONCE, static::RESTRICT_NONCE . $post_id)) {
            return;
        }

        if (!$this->canEdit($post)) {
            return;
        }

        $caps = isset($_POST[static::RESTRICT_FIELD]) ? $_POST[static::RESTRICT_FIELD] : false;

        if (!$caps) {
            delete_post_meta($post_id, static::RESTRICT_FIELD);
        } else {
            update_post_meta($post_id, static::RESTRICT_FIELD, implode(',', $caps));
        }
    }

    private function canEdit($post)
    {
        $type = get_post_type_object($post->post_type);

        return current_user_can($type->cap->edit_post, $post->ID);
    }

    private function nonceOkay($name, $action)
    {
        return isset($_POST[$name]) && wp_verify_nonce($_POST[$name], $action);
    }

    private function getRestrictionCaps()
    {
        $posts = get_posts(array(
            'post_type'     => static::CAP,
            'nopaging'      => true,
            'meta_query'    => array(
                array(
                    'key'       => static::ENABLE_FIELD,
                    'value'     => 'on',
                ),
            ),
        ));

        return array_map(function($p) {
            return $p->post_title;
        }, $posts);
    }
}
