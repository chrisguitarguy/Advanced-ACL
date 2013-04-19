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

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

use Chrisguitarguy\AdvancedACL\ACLBase;
use Chrisguitarguy\AdvancedACL\Role;
use Chrisguitarguy\AdvancedACL\RoleAlias;

require_once __DIR__ . '/inc/ACLBase.php';
require_once __DIR__ . '/inc/Role.php';
require_once __DIR__ . '/inc/RoleAlias.php';

/** Posts **********/

$caps = $wpdb->get_results($wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
    ACLBase::CAP
));

foreach ($caps as $cap) {
    wp_delete_post($cap);
}

/** Taxonomies **********/

// register our taxonomies, so we can `get_terms` them.
Role::instance()->register();
RoleAlias::instance()->register();

$taxonomies = array(ACLBase::ROLE, ACLBase::A_ROLE);
foreach ($taxonomies as $tax) {
    $terms = get_terms($tax, array('hide_empty' => false));

    if (!$terms) {
        continue;
    }

    foreach ($terms as $term) {
        wp_delete_term($term->term_id, $tax);
    }
}
