;$(function() {
    $('#modal').on('show.bs.modal', (e) => {
        const $modal = $(e.currentTarget);
        const id = $(e.relatedTarget).data('user-id');

        if (id) {
            $('#update_user').attr('disabled', false);
            $('[name=id_user]').val(id);
            $('.send-invite').hide();

            $.get('rubric/search', {get_user: 1, id_user: id})
            .done((response) => {
                $('.modal-title').text(response.first_name + ' ' + response.last_name);
                $('.modal-img i').hide();
                $('.modal-img img').attr('src', response.picture).show();

                $('[name=first_name]').val(response.first_name);
                $('[name=last_name]').val(response.last_name);
                $('[name=email]').val(response.email);
                $modal.find('[name=super_admin][value=' + response.super_admin + ']').iCheck('check');
                $modal.find('[name=status][value=' + response.status + ']').iCheck('check');
            });
        } else {
            $('#add_user').attr('disabled', false);
            $('.send-invite').show();
            $modal.find('[name=super_admin][value=1], [value="enabled"]').iCheck('check');
        }

        $('#block_form').validate({
            rules: {
                email: {
                    remote: () => { return 'rubric/search?check_email=1&id_user=' + $modal.find('[name=id_user]').val(); }
                }
            },
            messages: {
                email: {
                    remote: 'This email is already used'
                }
            }
        });
    });

    $('#block_form').on('submit', async function(e) {
        e.preventDefault();

        const $this = $(this);
        const $email = $this.find('[name=email]');
        const $emailError = $this.find('.email-error');
        const action = $this.find('[name=action]').val();

        if ($email.hasClass('error')) {
            return false;
        }

        if (!await checkEmail()) {
            $email.addClass('error');
            $emailError.text('This email is already used').show();
            return false;
        }

        const url = action == 'create' ? 'rubric/create' : 'rubric/update';
        $.post(url, $this.serialize(), function() {
            $('#block_form').trigger('reset');
            $('#modal').modal('hide');
            updateList();
        });
    });

    $(document)
        .on('click', '.remove', function() {
            if (!confirm('Remove rubric confirm')) {
                return false;
            }

            $.post('rubric/delete', {id: $(this).closest('tr').data('id')}, function(response) {
                if (response.success) {
                    updateList();
                }
            });
        })
        .on('click', '.update', function() {
            $.post('rubric/update', {'get-user': 1, id: $(this).closest('tr').data('id')}, response => {
                const $form = $('#block_form');

                $form.find('[name=id_user]').val(response.id_user);
                $form.find('[name=first_name]').val(response.first_name);
                $form.find('[name=last_name]').val(response.last_name);
                $form.find('[name=email]').val(response.email);
                $form.find('[name=status]').each(function() {
                    if ($(this).val() == response.status) {
                        $(this).attr('checked', true);
                    }
                });

                $form.find('.status-container label').iCheck({
                    radioClass: 'iradio_square-green'
                });
            });
        })
        .on('click', '.invite', function() {
            $(this).attr('disabled', true);

            $.post('rubric/invite', {
                send_invite: 1,
                id_user: $(this).closest('tr').data('id')
            }, response => {
                $(this).attr('disabled', false);
                alert(response);
            });
        })
    ;
});

async function checkEmail() {
    const $form = $('#block_form');

    return await $.post('rubric/search', {
        check_email: 1,
        email: $form.find('[name=email]').val(),
        id_user: $form.find('[name=id_user]').val()
    });
}