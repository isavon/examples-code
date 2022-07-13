;(function($) {
    "use strict";

    $(function() {
        $(".export-block a").on("click", function(e) {
            e.preventDefault();

            let $this = $(this),
                keys = $(".grid-view").yiiGridView("getSelectedRows");

            if (keys.length == 0) {
                return errorBox("Выберите товары");
            }

            $.ajax({
                url: $this.attr("href"),
                data: {
                    ids: keys
                },
                type: "POST",
                dataType: "json",
                error: function(response) {
                    errorBox(response.responseJSON ? response.responseJSON.message : "Ошибка во время выполнения");
                },
                success: function(response) {
                    if (!response["success"]) {
                        errorBox("Возникла ошибка. Обратитесь к администратору");
                        return;
                    }

                    location.href = "/user/product-export/list";
                }
            });
        });

        $(document).on("click", ".channels img", function() {
            let $this = $(this),
                $parent = $this.closest(".channels"),
                $messageBlock = $parent.find(".channel-message");

            $this.parent().find(".active").removeClass("active");
            $messageBlock.attr("class", "channel-message");

            if ($this.attr("class")) {
                let html = $this.data("message");

                if ($this.data("url")) {
                    html += '<br><a href="' + $this.data("url") + '" target="_blank">Ссылка на товар</a>';
                }

                $messageBlock.html(html).addClass($this.attr("class"));
                $("[data-toggle=tooltip]").tooltip();

                return;
            }

            $this.addClass("active");

            $messageBlock.empty().append($("<a>", {
                href: "#",
                class: "add-to-channel",
                text: "Добавить (" + $this.attr("alt") + ")",
                "data-channel-id": $this.data("channel-id")
            }));
        });

        $(document).on("click", ".add-to-channel", function(e) {
            e.preventDefault();

            let $this = $(this);

            $.ajax({
                url: "add-to-channel",
                data: {
                    pid: $this.closest("tr").data("key"),
                    cid: $this.data("channel-id")
                },
                dataType: "json",
                error: function(response) {
                    errorBox(response.responseJSON ? response.responseJSON.message : "Ошибка во время выполнения");
                },
                success: function(response) {
                    if (!response["success"]) {
                        errorBox("Произошла ошибка. Обратитесь к администратору.");

                        return;
                    }

                    $this
                        .parent().empty().text(response["message"]).addClass("in_process")
                        .closest(".channels").find(".active")
                            .removeClass("active")
                            .addClass("in_process")
                            .attr("data-message", response["message"])
                    ;
                }
            });
        });

        $(".photo img").on("click", function() {
            let $img = $(this);

            $("#photoModal")
                .find(".modal-body").empty().append($("<img>", {src: $img.attr("src"), class: "img-responsive"})).end()
                .modal()
            ;
        });
    });
})(jQuery);