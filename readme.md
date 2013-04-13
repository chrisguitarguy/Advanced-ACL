# Advanced ACL

Extend WordPress's Roles and Capabilities with a GUI and a more complete system
suited for membership sites.

A work in progress.

## Goals

* Exist outside the core roles/caps system but integrate with it -- make
`current_user_can` work with caps added by this plugin.
* No custom tables, use post types and taxonomies
* Use as many core admin area constructs as possible -- make it flexible
* Provide a way to block content based on user caps
* Don't fuck up WP pagination (most plugins do -- use the wrong filters). This
is going to be a hard one. Not sure how yet.
* Make bulk updating users easy
* Provide a well thought out API for programatically adding/remove users from
groups.

## Layout

**Capabilities** provide permissions an abilities. Capabilities are a custom
post type.

**Roles** group capabilities into usable units. Roles are a custom taxonomy.

**Users** can be placed in one or more groups.
