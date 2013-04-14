jQuery(document).ready(function($) {
    function getUsers() {
        var users = [];

        $('#the-list .check-column input[type="checkbox"]:checked').each(function() {
            users.push($(this).val());
        });

        return users;
    }

    function getChangeRole() {
        return $('#aacl_role_bulk').val();
    }

    function showSuccess() {
        createMessage('updated', aacl_js.updated);
    }

    function showError() {
        createMessage('error', aacl_js.error);
    }

    function createMessage(cls, msg) {
        var msg = $('<div />')
            .attr('class', cls)
            .addClass('aacl-ajax-message')
            .append(jQuery('<p>').html(msg));

        $('#wpbody .aacl-ajax-message').remove();

        $('#wpbody .wrap > h2').after(msg);
    }

    function ajaxFinisher(res) {
        if ('1' == res) {
            showSuccess();
        } else {
            showError();
        }
    }

    function doAjax(action, token) {
        var users = getUsers(), role = getChangeRole();

        if (!role || !users.length) {
            return false;
        }

        $.post(ajaxurl, {
            token: token,
            action: action, 
            users: users,
            role: role
        }, ajaxFinisher);

    }

    $('#aacl_role_add_cue').click(function(e) {
        e.preventDefault();
        doAjax('aacl_bulk_add', aacl_js.add);
    });

    $('#aacl_role_remove_cue').click(function(e) {
        e.preventDefault();
        doAjax('aacl_bulk_remove', aacl_js.remove);
    });
});
