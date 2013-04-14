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

class ContentRestriction extends ACLBase
{
    public function _setup()
    {
        add_action('pre_get_posts', array($this, 'alterQuery'));
    }

    public function alterQuery(\WP_Query $q)
    {
        if (is_admin() || !$q->is_main_query() || current_user_can(static::getEditCap())) {
            return;
        }

        $restricted = $this->getRestrictedPosts($q->query);

        if (!$restricted) {
            return;
        }

        $exclude = array();
        foreach ($restricted as $r) {
            $caps = static::getPostRestrictions($r);

            $can_read = false;
            foreach ($caps as $cap) {
                // A user must have ONE of the caps to read the post
                // an OR relation, in other words.
                if (current_user_can($cap)) {
                    $can_read = true;
                    break;
                }
            }

            // we the user didn't have any of the caps, exclude the post
            if (!$can_read) {
                $exclude[] = $r;
            }
        }

        $not_in = $q->get('post__not_in');

        if (!$not_in) {
            $not_in = array();
        } elseif (!is_array($not_in)) {
            $not_in = explode(',', $not_id);
        }

        $q->set('post__not_in', array_unique(array_merge($not_in, $exclude)));
    }

    private function getRestrictedPosts($vars)
    {
        $vars = array_replace($vars, array(
            'nopaging'          => true,
            'fields'            => 'ids',
            'supress_filters'   => false,
            'meta_query'        => array(
                array(
                    'key'       => static::RESTRICT_FIELD,
                    'compare'   => 'EXISTS',
                ),
            ),
        ));

        return static::filter('restricted_posts', get_posts($vars));
    }
}
