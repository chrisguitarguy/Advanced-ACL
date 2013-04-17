# Advanced ACL

Advanced ACL extend's WordPress' built in roles and capabilities with an admin
area to add and remove capabilities and groups.

**NOTE:** This plugin required PHP 5.3+

## Features

**Exists outside WP's Roles and Capabilities**

But works with it. Capabilities are custom post types; roles are a custom
taxonomy.

Capabilities get added "virtually" whenever something (the WP core or userland
code) calls `current_user_can`.

This, of course, means no custom tables: everything fits with WordPress' DB
schema.

**Uses Normal Admin Area Constructs**

Meaning if you need to customize an admin screen, you can just like you would
with any other custom post type or taxonomy.

**Provides Content Restriction Capabilities**

Restrict content by capability. This removes posts that require capabilities
from the front end completely. And it tries not to mess up pagination in the
process.

**Easy to Bulk Update Users**

Select the users you want, add or remove them to a group from an admin area.

**Relatively Performant**

Plugins like this obviously incur some overhead, but internally the Advanced ACL
uses the core `wp_cache_*` API and tries to make as few database queries as
possible.

**Hierarchical Roles**

A parent role may have capabilities `x` and `y`. A child of that parent role may
have capability `z` specifically assigned to it, but it will also inherit
capabilities `x` and `y`.

## Enhancing Performance

### Keep your role hierarchy as flat as possible

Each child in the tree of roles generates a additional DB query.

### Remove Content Restriction

If you don't need it, remove it. It's self contained.

    <?php
    add_action('advancedacl_loaded', function() {
        // remove from the frontend
        remove_action(
            'plugins_loaded',
            array(\Chrisguitarguy\AdvancedACL\ContentRestriction::instance(), '_setup')
        );

        // remove from the admin area.
        remove_action(
            'plugins_loaded',
            array(\Chrisguitarguy\AdvancedACL\Admin\ContentRestriction::instance(), '_setup')
        );
    });
