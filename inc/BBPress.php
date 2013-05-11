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

class BBPress extends ACLBase
{
    public function _setup()
    {
        add_filter(static::prefixHook('pre_user_can_read'), array($this, 'changeTopicReply'), 10, 2);
        add_filter(static::prefixHook('content_restriction_types'), array($this, 'removeTopicReply'));
        add_filter('bbp_after_has_topics_parse_args', array($this, 'removeRestrictedTopics'));
        add_filter('bbp_after_has_forums_parse_args', array($this, 'removeRestrictedForums'));
    }

    public function changeTopicReply($null, $post_id)
    {
        // we only want topics and replies
        if (!in_array(get_post_type($post_id), $this->getForumTypes())) {
            return $null;
        }

        if (bbp_is_topic($post_id)) {
            $forum_id = bbp_get_topic_forum_id($post_id);
        } else {
            $forum_id = bbp_get_reply_forum_id($post_id);
        }

        if (!$forum_id) {
            return $null;
        }

        // if we're here we have a forum ID, so check that!
        return static::userCanRead($forum_id);
    }

    public function removeTopicReply($types)
    {
        foreach ($this->getForumTypes() as $t) {
            if (false !== $idx = array_search($t, $types)) {
                unset($types[$idx]);
            }
        }

        return $types;
    }

    /**
     * XXX this is likely to just fall over and kill performance on big sites with
     * a lot of topics. Some sort of JOIN would be better?
     */
    public function removeRestrictedTopics($args)
    {
        $not_in = array();
        foreach (static::getRestrictedTopics() as $topic) {
            if (empty($topic->caps)) {
                continue;
            }

            $caps = explode(',', $topic->caps);

            if (!static::userCanRead($topic->forum_id, $caps)) {
                $not_in[] = $topic->topic_id;
            }
        }

        if ($not_in) {
            $old = isset($args['post__not_in']) ? $args['post__not_in'] : array();

            $not_in = array_unique(array_merge(
                is_array($old) ? $old : explode(',', $old),
                $not_in
            ));

            $args['post__not_in'] = $not_in;
        }

        return $args;
    }

    /**
     * XXX again, likely to fall down on sites with a lot forums. BBPress needs
     * to use the damn main query.
     */
    public function removeRestrictedForums($args)
    {
        $not_in = array();
        foreach (static::getRestrictedForums() as $forum) {
            if (empty($forum->caps)) {
                continue;
            }

            $caps = explode(',', $forum->caps);

            if (!static::userCanRead($forum->forum_id, $caps)) {
                $not_in[] = $forum->forum_id;
            }
        }

        if ($not_in) {
            $old = isset($args['post__not_in']) ? $args['post__not_in'] : array();

            $not_in = array_unique(array_merge(
                is_array($old) ? $old : explode(',', $old),
                $not_in
            ));

            $args['post__not_in'] = $not_in;
        }

        return $args;
    }

    /**
     * Uses wpdb directly to query all topics that have restrictions
     * and return the results.
     *
     * This is used so we don't make any more queries than necessary.
     *
     * @since   0.1
     * @access  public
     * @return  stdClass[] with topic_id, forum_id and caps as the properties
     * @static
     */
    public static function getRestrictedTopics()
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT posts.ID AS topic_id, posts.post_parent AS forum_id, meta.meta_value AS caps'
            . " FROM {$wpdb->posts} AS posts"
            . " LEFT JOIN {$wpdb->postmeta} AS meta"
            . " ON meta.post_id = posts.post_parent"
            . " WHERE posts.post_type = %s"
            . " AND meta.meta_key = %s",
            bbp_get_topic_post_type(),
            static::RESTRICT_FIELD
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Uses wpdb directly to fetch all replys that have restrictions
     * and return their results.
     *
     * @since   0.1
     * @access  public
     * @return  stdClass[] with reply_id, foruM-id and caps as the properites
     * @static
     */
    public static function getRestrictedReplies()
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT posts.ID AS reply_id, meta.meta_value AS forum_id, meta2.meta_value AS caps'
            . " FROM {$wpdb->posts} AS posts"
            . " INNER JOIN {$wpdb->postmeta} AS meta"
            . " ON meta.post_id = posts.ID"
            . " LEFT JOIN {$wpdb->postmeta} AS meta2"
            . " ON meta2.post_id = meta.meta_value"
            . " WHERE posts.post_type = %s"
            . " AND meta.meta_key = %s"
            . " AND meta2.meta_key = %s",
            bbp_get_reply_post_type(),
            '_bbp_forum_id', // xxx is there a bbpress function to get this key?
            static::RESTRICT_FIELD
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Uses wpdb directly to fetch restricted forums.
     *
     * @since   0.1
     * @access  public
     * @return  stdClas[] with forum_id and caps as the properties
     * @static
     */
    public static function getRestrictedForums()
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT posts.ID AS forum_id, meta.meta_value AS caps'
            . " FROM {$wpdb->posts} AS posts"
            . " LEFT JOIN {$wpdb->postmeta} AS meta"
            . ' ON meta.post_id = posts.ID'
            . ' WHERE posts.post_type = %s'
            . ' AND meta.meta_key = %s',
            bbp_get_forum_post_type(),
            static::RESTRICT_FIELD
        );

        return $wpdb->get_results($sql);
    }

    private function getForumTypes()
    {
        return static::filter('bbpress_forum_types', array(
            bbp_get_topic_post_type(),
            bbp_get_reply_post_type(),
        ));
    }
}
