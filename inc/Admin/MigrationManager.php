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

/**
 * Takes care of handling migrations from other ACL-like plugins.
 *
 * @since   0.1
 */
class MigrationManager extends \Chrisguitarguy\AdvancedACL\ACLBase
{
    const ACTION = 'aacl_do_migration';
    const NONCE  = 'aacl_migrate_nonce';

    private $migrations = array();

    public function __construct()
    {
        $this->addMigration('groups', new \Chrisguitarguy\AdvancedACL\Migration\GroupsMigration());
    }

    public function _setup()
    {
        add_action('admin_menu', array($this, 'addPage'));
        add_action('wp_ajax_' . static::ACTION, array($this, 'ajax'));
    }

    public function addPage()
    {
        $page = add_management_page(
            __('Advanced ACL Migration', AACL_TD),
            __('ACL Migrate', AACL_TD),
            static::getEditCap(),
            'aacl-migrate',
            array($this, 'pageCallback')
        );

        add_action("admin_print_scripts-{$page}", array($this, 'enqueue'));
    }

    public function pageCallback()
    {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e('Advanced ACL Migration Manager', AACL_TD); ?></h2>
            <div class="hide-if-no-js">
                <h3><?php _e('Available Migrations', AACL_TD); ?></h3>
                <select id="aacl_migrate">
                    <?php foreach ($this->migrations as $name => $m) {
                        printf(
                            '<option value="%1$s">%2$s</option>',
                            esc_attr($name),
                            esc_html($m->getName())
                        );
                    } ?>
                </select>

                <p>
                    <button type="button" class="button-primary" id="aacl_migrate_cue">Migrate</button>
                </p>
            </div>
        </div>
        <?php
    }

    public function enqueue()
    {
        wp_enqueue_script(
            'aacl-migrate',
            AACL_URL . 'js/migrate.js',
            array('jquery'),
            static::VER
        );

        wp_localize_script(
            'aacl-migrate',
            'aacl_migrate',
            array(
                'action'    => static::ACTION,
                'nonce'     => wp_create_nonce(static::NONCE),
                'none'      => __('No Migration Specified!', AACL_TD),
                'complete'  => __('Migration Complete!', AACL_TD),
                'error'     => __('Error completing migration', AACL_TD),
            )
        );
    }

    public function ajax()
    {
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], static::NONCE)
        ) {
            die('-1');
        }

        $m = isset($_POST['migration']) ? $_POST['migration'] : false;

        if (!$m || !isset($this->migrations[$m])) {
            die('-1');
        }

        if ($this->migrations[$m]->migrate()) {
            die('1');
        }

        die('-1');
    }

    public function addMigration($name, \Chrisguitarguy\AdvancedACL\Migration\MigrationInterface $m)
    {
        $this->migrations[$name] = $m;
        return $this;
    }

    public function removeMigration($name)
    {
        if (isset($this->migrations[$name])) {
            unset($this->migrations[$name]);
            return true;
        }

        return false;
    }
}
