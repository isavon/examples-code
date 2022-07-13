$('select[name="complex_id[]"]').on({
    'select2:select': function() {
        var $this = $(this);

        $.ajax({
            url: '/rubricator/save-tower',
            data: {
                complexes: $(this).val(),
                type: $(this).data('type'),
                listingType: $(this).data('listing-type')
            },
            type: 'POST',
            dataType: 'json',
            error: function(response) {
                console.log(response);
            },
            success: function(response) {
                if (response.added.length == 0) {
                    return false;
                }

                var $table = $this.closest('.box-body').find('.table');
                $table.find('tbody').append($('<tr>', {'data-id': response.added.id})
                    .append($('<td>', {text: response.added.name}))
                    .append($('<td>')
                        .append($('<select>', {name: 'show_price'})
                            .append($('<option>', {value: 1, text: 'Показывать'}))
                            .append($('<option>', {value: 0, text: 'По запросу'}))
                        )
                    )
                );

                if ($table.hasClass('d-none')) {
                    $table.removeClass('d-none');
                }
            }
        });
    },
    'select2:unselect': function() {
        var $this = $(this);

        $.ajax({
            url: '/rubricator/save-tower',
            data: {
                complexes: $(this).val(),
                type: $(this).data('type'),
                listingType: $(this).data('listing-type')
            },
            type: 'POST',
            dataType: 'json',
            error: function(response) {
                console.log(response);
            },
            success: function(response) {
                if (response.deleted.length == 0) {
                    return false;
                }

                var $table = $this.closest('.box-body').find('.table');
                for (var i in response.deleted) {
                    $table.find('tr[data-id="' + response.deleted[i] + '"]').remove();
                }

                if (!$table.find('tbody tr').length) {
                    $table.addClass('d-none');
                }
            }
        });
    },
});

$(document).on('change', 'select[name="show_price"]', function() {
    var $this = $(this);

    $.ajax({
        url: '/rubricator/change-show-price?id=' + $this.closest('tr').data('id'),
        data: {
            value: $this.val()
        },
        type: 'POST',
        dataType: 'json',
        error: function(response) {
            console.log(response);
        }
    });
});