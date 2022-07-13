;(function($) {
    $(function() {
        $('input[id*=datefrom], input[id*=dateto]').on('change', function() {
            $(this).closest('form').submit();
        });

        $(document).on('click', '[data-toggle="cian-list-lightbox"]', function(e) {
            e.preventDefault();

            $.get('/list/get-photos', {id: $(this).data('id')}, function(response) {
                if (!response) {
                    return false;
                }

                var $photosBlock = $('#photos');
                $photosBlock.empty();

                $.each(response, function (index, url) {
                    $photosBlock.append('<a href="' + url + '" data-toggle="lightbox" data-gallery="example-gallery"><img src="' + url + '" class="img-fluid"></a>');
                });

                $photosBlock.find('a:first').ekkoLightbox();
            });
        });

        $(document).on('change', '.cian-bet', function() {
            let $this = $(this);
            let $parent = $this.closest('td');
            let bet = parseInt($this.val());

            if (isNaN(bet) || bet < 0) {
                $parent.addClass('has-error');
                $this.removeClass('changed');
            } else {
                $parent.removeClass('has-error');
                $this.val(bet);
                $this.addClass('changed');
            }

            if ($('.cian-bet.changed').length > 0) {
                $('#save_bets').removeClass('btn-primary').addClass('btn-danger').prop('disabled', false);
            } else {
                $('#save_bets').removeClass('btn-danger').addClass('btn-primary').prop('disabled', true);
            }
        });

        window.onbeforeunload = function() {
            if ($('#save_bets').hasClass('btn-danger')) {
                return false;
            }
        }

        $(document).on('click', '#save_bets.btn-danger', function() {
            let $this = $(this);
            let data = [];

            $('.cian-bet.changed').each(function() {
                data.push({
                    id: $(this).data('id'),
                    bet: parseInt($(this).val()),
                    type: $(this).hasClass('list') ? 'list' : 'listing'
                });
            });

            $this.prop('disabled', true).text('Сохранение...');

            $.ajax({
                url: '/bet-cian/update-bets',
                data: {data},
                type: 'POST',
                dataType: 'json',
                error: function(response) {
                    console.log('ERROR', response);
                },
                success: function(response) {
                    if (!response.success) {
                        return false;
                    }

                    $('.cian-bet.changed').each(function() {
                        $(this).removeClass('changed');
                    });
                    $('#save_bets').text('Сохранено').removeClass('btn-danger').addClass('btn-primary').prop('disabled', true);
                }
            })
            .always(function() {
                setTimeout(function() {
                    $this.text('Сохранить ставки');
                }, 2000);
            });
        });

        $(document).on('load input paste', '.cian-cost, .cian-cost-m2', function() {
            let $this = $(this);
            let $parent = $this.closest('tr');
            let $inputCost = $parent.find('.cian-cost');
            let $inputCostM2 = $parent.find('.cian-cost-m2');
            let area = parseFloat($parent.find('.area').text());
            let listingType  = parseInt($parent.find('.listing-type').data('type'));
            let cost;
            let costM2;

            $inputCost.parent().removeClass('has-error');
            $inputCostM2.parent().removeClass('has-error');

            if ($this.hasClass('cian-cost')) {
                cost = parseFloat($this.val());

                if (!cost) {
                    $inputCost.parent().addClass('has-error');
                    return false;
                }

                costM2 = cost / area;
                if (listingType == 1) {
                    costM2 *= 12;
                }
                costM2 = costM2.toFixed(2);

                $inputCostM2.val(costM2);
            } else {
                costM2 = parseFloat($this.val());

                if (!costM2) {
                    $inputCostM2.parent().addClass('has-error');
                    return false;
                }

                cost = costM2 * area;
                if (listingType == 1) {
                    cost /= 12;
                }
                cost = cost.toFixed(2);

                $inputCost.val(cost);
            }
        });

        $(document).on('change', '.cian-cost, .cian-cost-m2', function() {
            let $this = $(this);
            let $parent = $this.closest('tr');
            let $inputCost = $parent.find('.cian-cost');
            let $inputCostM2 = $parent.find('.cian-cost-m2');
            let cost = $inputCost.val();
            let costM2 = $inputCostM2.val();

            if ($inputCost.parent().hasClass('has-error') || $inputCostM2.parent().hasClass('has-error')) {
                return false;
            }

            $inputCost.prop('disabled', true).val('Сохранение...');
            $inputCostM2.prop('disabled', true).val('Сохранение...');

            $.ajax({
                url: '/bet-cian/update-costs',
                data: {
                    listing_id: $parent.data('key'),
                    cost: cost,
                    costM2: costM2
                },
                type: 'POST',
                dataType: 'json',
                error: function(response) {
                    var message = response.responseJSON ? response.responseJSON.message : (response.responseText ? response.responseText : 'Ошибка при сохранении');

                    $inputCost.val(message).parent().addClass('has-error');
                    $inputCostM2.val(message).parent().addClass('has-error');
                },
                success: function(response) {
                    if (!response.success) {
                        $inputCost.val(response.mess).parent().addClass('has-error');
                        $inputCostM2.val(response.mess).parent().addClass('has-error');

                        return false;
                    }

                    $inputCost.val('Сохранено').parent().addClass('has-success');
                    $inputCostM2.val('Сохранено').parent().addClass('has-success');
                }
            })
            .always(function() {
                setTimeout(function() {
                    $inputCost.prop('disabled', false).val(cost).parent().removeClass(['has-error', 'has-success']);
                    $inputCostM2.prop('disabled', false).val(costM2).parent().removeClass(['has-error', 'has-success']);
                }, 2000);
            });;
        });

        $('.table tbody td:first-child').on('click', function() {
            let $this = $(this).parent();
            let $next = $this.next();
            let $bet  = $this.find('.cian-bet');

            if ($next.hasClass('statistic')) {
                $next.fadeOut(function() {
                    $next.remove();
                });
                return;
            }

            $('#griditems .table tbody tr').css('cursor', 'wait');
            $.ajax({
                url: '/bet-cian/stat?id=' + ($bet.hasClass('list') ? $bet.data('id') : $this.data('idd')),
                dataType: 'json',
                error: function(response) {
                    console.log('error', response);
                },
                success: function(response) {
                    let $tr = $('<tr>', {class: 'statistic', style: 'display:none'});
                    let $td = $('<td>', {colspan: 14});

                    if (!response) {
                        $td.addClass('text-danger').text('Статистика по объявлению не найдена');
                    } else {
                        $td.append($('<span>')
                            .append($('<small>', {class: 'd-block', text: 'Показы: ' + response.showsCount}))
                            .append($('<small>', {class: 'd-block', text: 'Клики: ' + response.views}))
                            .append($('<small>', {class: 'd-block', text: 'Расхлопы: ' + response.shows}))
                            .append($('<small>', {class: 'd-block', text: 'Позиция в cити: ' + response.posCity}))
                            .append($('<small>', {class: 'd-block', text: 'Позиция в башне: ' + response.posTower}))
                            .append($('<small>', {class: 'd-block', text: 'Позиция на этаже: ' + response.posFloor}))
                        );

                        if ($bet.hasClass('list')) {
                            $td.append($('<span>').append($('<a>', {text: 'Ссылка на список', href: $bet.data('link')})));
                        }
                    }

                    $tr.append($td);
                    $this.after($tr);
                    $tr.fadeIn();
                }
            })
            .always(function() {
                $('#griditems .table tbody tr').css('cursor', 'default');
            });
        });

        $('#balance_type').on('change', function() {
            let $this = $(this);

            $.ajax({
                url: '/bet-cian/set-balance-type',
                data: {val: $this.val()},
                dataType: 'json',
                type: 'POST',
                error: function(response) {
                    console.log('error', response);
                },
                success: function(response) {
                    $("#balance").text(response.balance);
                    $("#plan").text(response.plan);
                    $("#rest").text(response.rest);

                    if (response.rest < 0) {
                        $("#rest").closest("h4").removeClass("text-fuchsia").addClass("text-danger");
                    } else {
                        $("#rest").closest("h4").removeClass("text-danger").addClass("text-fuchsia");
                    }
                }
            });
        })

    });
})(jQuery);