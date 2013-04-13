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

use Chrisguitarguy\AdvancedACL;

function advancedacl_load()
{
    AdvancedACL\Capability::init();

    do_action('advancedacl_loaded');
}
