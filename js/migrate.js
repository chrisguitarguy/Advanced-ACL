jQuery(document).ready(function($) {
    function makeMigrateRequest(migrate) {
        $.post(ajaxurl, {
            action: aacl_migrate.action,
            nonce: aacl_migrate.nonce,
            migration: migrate
        }, function(res) {
            if ('1' == res) {
                alert(aacl_migrate.complete);
            } else {
                alert(aacl_migrate.error);
            }
        });
    }

    $('body').on('click', '#aacl_migrate_cue', function(e) {
        var m;

        e.preventDefault();

        m = $('#aacl_migrate').val();

        if (!m) {
            alert(aacl_migrate.none);
        }

        makeMigrateRequest(m);
    });
});
