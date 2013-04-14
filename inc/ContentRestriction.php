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
        foreach ($restricted as $id => $cap_str) {
            $caps = explode(',', $cap_str);

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
                $exclude[] = $id;
            }
        }

        $not_in = $q->get('post__not_in');

        if (!$not_in) {
            $not_in = array();
        } elseif (!is_array($not_in)) {
            $not_in = explode(',', $not_in);
        }

        $q->set('post__not_in', array_unique(array_merge($not_in, $exclude)));
    }

    public function changeFields($fields)
    {
        global $wpdb;

        return "{$wpdb->posts}.ID, {$wpdb->postmeta}.meta_value AS post_parent";
    }

    private function getRestrictedPosts($vars)
    {
        // XXX id=>parent is used in the `fields` argument below. If you don't
        // select `ids` or `id=>parent` WP runs the posts array through `get_post`
        // to fill out all the post objects. We don't won't that. We just want
        // ID => restrictions pairs. So we fake post_parent by hooking into
        // `posts_fields` and pretending our meta field is post_parent.
        // This is very much a hack, but I need get_posts to deal with actually
        // parsing the query vars and such. It also lets us skip over a lot of
        // comment nonsense and other stuff.
        $args = array_replace($vars, array(
            'nopaging'          => true,
            'suppress_filters'  => false,
            'fields'            => 'id=>parent',
            'orderby'           => 'ID', // order doesn't matter, make it easy
            'order'             => 'ASC',
            'meta_query'        => array(
                array(
                    'key'       => static::RESTRICT_FIELD,
                    'compare'   => 'EXISTS',
                ),
            ),
        ));

        add_filter('posts_fields', array($this, 'changeFields'));

        $posts = get_posts($args);

        remove_filter('posts_fields', array($this, 'changeFields'));

        return static::filter('restricted_posts', $posts, $args, $vars);
    }
}
