;(function() {
    "use strict";

    $(function() {
        // показать, скрыть изображения
        $(".show-hide-wrapper .action").on("click", function() {
            var $this = $(this),
                $imagesWrapper = $(".images-wrapper");

            if ($imagesWrapper.is(":hidden")) {
                $imagesWrapper.animate({height: "show"});
                $this.text('СКРЫТЬ ИЗОБРАЖЕНИЯ');

                return;
            }

            $imagesWrapper.animate({height: "hide"});
            $this.text('ПОКАЗАТЬ ИЗОБРАЖЕНИЯ');
        });

        // удаление изображений
        $(".img-remove").on("click", function() {
            if (!confirm("Удалить изображения?")) {
                return false;
            }

            var $this = $(this);

            $.ajax({
                url: "image-delete",
                data: {
                    productId: $this.data("product-id"),
                    imageId: $this.data("image-id")
                },
                type: "GET",
                dataType: "json",
                error: function() {
                    errorBox("Ошибка при удалении изображения.");
                },
                success: function(response) {
                    if (!response["success"]) {
                        errorBox("Что-то пошло не так. Изображение не удалено.");
                        return;
                    }

                    $this.closest(".image-wrapper").remove();
                    successBox("Изображение удалено!");
                }
            });
        });

        $(".image-wrapper .img-copy").on("click", function() {
            let $tmp = $("<input>", {value: $(this).closest(".image-wrapper").find(".image img").attr("src")});
            $("body").append($tmp);

            $tmp.select();

            let success = document.execCommand("copy");
            $("body").find($tmp).remove();

            if (success) {
                successBox("Скопировано в буфер обмена");
            } else {
                errorBox("Ошибка копирования");
            }
        });

        $(document)
            .on("click", ".field-product-source .create, .field-product-aliase .create, .field-product-video .create", function() {
                var $formGroup = $(this).closest(".input-group"),
                    $formGroupNew = $formGroup.clone(false);

                $formGroupNew
                    .find("input").val("").removeAttr("value").end()
                    .find(".remove")
                        .removeAttr("data-id")
                        .removeAttr("data-product-id")
                ;

                $formGroup.after($formGroupNew);
            })
            .on("click", ".field-product-source .remove", function() {
                if ($(".field-product-source .input-group").length <= 1) {
                    return false;
                }

                var $this = $(this);

                if (!$this.data("id")) {
                    $this.closest(".input-group").remove();
                    return false;
                }

                if (!confirm("Удалить источник данных?")) {
                    return false;
                }

                $.ajax({
                    url: "source-delete",
                    data: {
                        id: $this.data("id"),
                        productId: $this.data("product-id")
                    },
                    type: "GET",
                    dataType: "json",
                    error: function() {
                        errorBox("Ошибка при удалении источника данных.");
                    },
                    success: function(response) {
                        if (!response["success"]) {
                            errorBox("Ошибка! Источник данных не удалено.");
                            return;
                        }

                        $this.closest(".input-group").remove();
                        successBox("Источник данных удален!");
                    }
                });
            })
            .on("click", ".field-product-aliase .remove", function() {
                if ($(".field-product-aliase .input-group").length <= 1) {
                    return false;
                }

                var $this = $(this);

                if (!$this.data("id")) {
                    $this.closest(".input-group").remove();
                    return false;
                }

                if (!confirm("Удалить алиас?")) {
                    return false;
                }

                $.ajax({
                    url: "alias-delete",
                    data: {
                        id: $this.data("id"),
                        productId: $this.data("product-id")
                    },
                    type: "GET",
                    dataType: "json",
                    error: function() {
                        errorBox("Ошибка при удалении алиаса.");
                    },
                    success: function(response) {
                        if (!response["success"]) {
                            errorBox("Ошибка! Алиас не удалено.");
                            return;
                        }

                        $this.closest(".input-group").remove();
                        successBox("Алиас удален!");
                    }
                });
            })
            .on("click", ".field-product-video .remove", function() {
                if ($(".field-product-video .input-group").length <= 1) {
                    return false;
                }

                var $this = $(this);

                if (!$this.data("id")) {
                    $this.closest(".input-group").remove();
                    return false;
                }

                if (!confirm("Удалить видео?")) {
                    return false;
                }

                $.ajax({
                    url: "video-delete",
                    data: {
                        id: $this.data("id"),
                        productId: $this.data("product-id")
                    },
                    type: "GET",
                    dataType: "json",
                    error: function() {
                        errorBox("Ошибка при удалении видео.");
                    },
                    success: function(response) {
                        if (!response["success"]) {
                            errorBox("Ошибка! Видео не удалено.");
                            return;
                        }

                        $this.closest(".input-group").remove();
                        successBox("Видео удалено!");
                    }
                });
            })
        ;

        // сохранение значения характеристики
        $(".save").on("click", function(e) {
            e.preventDefault();

            var $this   = $(this),
                $parent = $this.closest("tr"),
                values  = [];

            if ($this.data("url") === undefined) {
                return errorBox("Не указан URL. Сохранение невозможно.");
            }

            var $input = $parent.find(".product-feature-value");

            switch ($input.prop("tagName")) {
                case "SELECT":
                    $input.find("option:selected").each(function() {
                        var val = $.trim($(this).text());

                        if (val) {
                            values.push(val);
                        }
                    });
                    break;

                case "INPUT":
                case "TEXTAREA":
                    var val = $.trim($input.val());

                    if (val) {
                        values.push(val);
                    }
                    break;
            }

            $this.attr("disabled", true);
            $.ajax({
                url: $this.data("url"),
                data: {
                    value:  values,
                    unitId: $parent.find(".unit").val()
                },
                type: "POST",
                dataType: "json",
                error: function(response) {
                    errorBox(response.responseJSON ? response.responseJSON.message : "Ошибка во время сохранения");
                    $this.attr("disabled", false);
                },
                success: function(response) {
                    $this.attr("disabled", false);
                    $parent.find(".current-value").text(response["success"]);
                    successBox("Значение характеристики обновлено");
                }
            });
        });

        // сохранение значений блока характеристик
        $(".save-all").on("click", function() {
            $(this).closest(".card").find(".save").trigger("click");
        });

        $(".img-view").on("click", function() {
            let $img = $(this).closest(".image-wrapper").find("img");

            $("#photoModal")
                .find(".modal-body").empty().append($("<img>", {src: $img.attr("src"), class: "img-responsive"})).end()
                .modal()
            ;
        });

        $(".image-info-block .info").on({
            mouseenter: function() {
                $(this).parent().find(".image-info").fadeIn("fast");
            },
            mouseleave: function() {
                $(this).parent().find(".image-info").fadeOut("fast");
            },
        });

        $(".download-selected").on("click", function(e) {
            e.preventDefault();

            let $checked = $(".images-wrapper input:checked");

            if ($checked.length == 0) {
                errorBox("Выберите товары");
                return false;
            }

            let $form = $("<form>", {action: $(this).data("action"), method: "post"});
            $form.append($("<input>", {name: yii.getCsrfParam(), value: yii.getCsrfToken()}));

            $checked.each(function() {
                $form.append($(this).clone());
            });

            $("body").append($form);
            $form.submit();
            $form.remove();

            $checked.each(function() {
                $(this).prop("checked", false);
            });
        });

        $(".img-main").on("click", function() {
            let $this = $(this);

            $.ajax({
                url: "main-image",
                data: {
                    id: $this.data("id")
                },
                type: "GET",
                dataType: "json",
                error: function(response) {
                    errorBox(response.responseJSON ? response.responseJSON.message : "Ошибка во время выполнения");
                    $this.attr("disabled", false);
                },
                success: function(response) {
                    if (!response["success"]) {
                        errorBox("Ошибка назначения главного фото");
                        return false;
                    }

                    $(".images-wrapper .img-main.active").removeClass("active");
                    $this.addClass("active");
                }
            });
        });
    });
})(jQuery);