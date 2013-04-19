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
    private $template_found = false;

    public function _setup()
    {
        add_action('pre_get_posts', array($this, 'alterQuery'));
        add_action('template_redirect', array($this, 'catchSingular'));
    }

    public function alterQuery(\WP_Query $q)
    {
        if (
            is_admin() || // do nothing in the admin area
            !$q->is_main_query() || // only modify the main query
            current_user_can(static::getEditCap()) || // if a user can assign roles, etc. leave them alone
            !(is_archive() || is_home()) // we need to be on an archive page or the home page.
        ) {
            return;
        }

        $restricted = $this->getRestrictedPosts($q->query);

        if (!$restricted) {
            return;
        }

        $exclude = array();
        foreach ($restricted as $id => $cap_str) {
            $caps = explode(',', $cap_str);

            if (!static::userCanRead($id, $caps)) {
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

    public function catchSingular()
    {
        global $wp_query;

        if (!is_singular()) {
            return;
        }

        $post_id = get_queried_object_id();

        if (static::userCanRead($post_id)) {
            return;
        }

        $this->template_found = static::filter('restricted_template', locate_template('restricted.php'), $post_id);

        if ($this->template_found) {
            add_filter('template_include', array($this, 'hijackTemplate'));
        } else {
            // we didn't find a restricted template, set the 404
            $wp_query->set_404();
        }
    }

    public function hijackTemplate()
    {
        return $this->template_found;
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
