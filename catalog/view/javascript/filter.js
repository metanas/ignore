$(document).ready(function () {
    if (String(getURLVar("price-max")) || String(getURLVar("price-min")) || String(getURLVar("color")) || String(getURLVar("manufacture")) || String(getURLVar("size")) || String("special"))
        setFilter();
    $('body').on('click', '.filter-filter', function (e) {
            e.stopPropagation();
            if ($(".dropdown-filter", this.parentNode).css('display') === 'block') {
                $(".dropdown-filter", this.parentNode).hide(100);
                $(this.parentNode).css({"border": "1px solid #e7e7e7", "width": "100%"});
                $(this.parentNode).css({"position": "relative"});
            } else {
                $('.dropdown-filter').hide(100);
                $('.dropdownFilter').css({"border": "1px solid #e7e7e7"});
                $(".dropdown-filter", this.parentNode).show(200);
                $(this.parentNode).css({"border": "2px solid black", "width": "90%"});
                $(this.parentNode).css({"position": "absolute"});
            }
        }
    );

    $("body").on('click', 'input:checkbox', function (event) {
        var param = '';
        const filt = getURLVar(event.target.name.replace("[]", ''));
        const newFilt = event.target.value.replace(" ", ".");
        if (event.target.checked) {
            if (filt !== '') {
                param = filt + "_" + newFilt;
            } else {
                param = newFilt;
            }
        } else {
            param = filt.replace(newFilt, '');
            param = param.replace("__", '');
            param = param.replace(/_$/, '');
            param = param.replace(/^_/, '');
        }
        console.log(param);
        updateQueryStringParam(event.target.name.replace("[]", ""), param);
        event.stopPropagation();
    });

    $("body").on("change", '#ex12c', function (e) {
        $("#max-price").html(e.value['newValue'][1] + " DHS");
        $("#min-price").html(e.value['newValue'][0] + " DHS");
        if (e.value['oldValue'][0] !== e.value['newValue'][0]) {
            removeFilterFormUrl("price-min", e.value['oldValue'][0]);
            updateQueryStringParam("price-min", e.value['newValue'][0]);
        }
        if (e.value['oldValue'][1] !== e.value['newValue'][1]) {
            removeFilterFormUrl("price-max", e.value['oldValue'][1]);
            updateQueryStringParam("price-max", e.value['oldValue'][1]);
        }
    });

    $("body").on("click", '.filter-done', function (e) {
        filterGenerator();
    });

    $(document).on('click', '', function (e) {
        $.map($(".dropdown-filter").get(), function (item) {
            if (item.style.display === "block" && $.inArray(e.target.tagName, ['INPUT', 'SPAN', 'LABEL', 'BUTTON']) === -1) {
                filterGenerator();
            }
        });
    });
});

function removeFilter(e) {
    removeFilterFormUrl(e.dataset.info, e.innerText.replace(" ", ".").replace("prix.min: ", "").replace("prix.max: ", ""));
    filterGenerator();
}

function removeFilterFormUrl(name, value) {
    var param = decodeURI(getURLVar(name)).replace(value, '');
    param = param.replace("__", '_');
    param = param.replace(/_$/, '');
    param = param.replace(/^_/, '');
    updateQueryStringParam(name, param);
}

$(document).on('click', '', function (e) {
    $.map($(".dropdown-filter").get(), function (item) {
        if (item.style.display === "block" && $.inArray(e.target.tagName, ['INPUT', 'SPAN', 'LABEL', 'BUTTON']) === -1) {
            filterGenerator();
        }
    });
});

function filterGenerator() {
    var url = "index.php?route=product/category/filter&path=" + String(getURLVar("path"));
    if (String(getURLVar("manufacture")) !== '') {
        url += "&manufacture=" + String(getURLVar("manufacture")).replace(".", " ");
    }

    if (String(getURLVar("color")) !== '') {
        url += "&color=" + String(getURLVar("color")).replace(".", " ");
    }

    if (String(getURLVar("size")) !== '') {
        url += "&size=" + String(getURLVar("size"));
    }

    if (String(getURLVar("special")) !== '') {
        url += "&special=" + String(getURLVar("special"));
    }

    if (String(getURLVar("price-min")) !== '') {
        url += "&price-min=" + String(getURLVar("price-min"));
    }

    if (String(getURLVar("price-max")) !== '') {
        url += "&price-max=" + String(getURLVar("price-max"));
    }
    $.ajax({
        url: url,
        type: "GET",
        beforeSend: function () {
            $('body').loading({message: "chargement.."});
        },
        complete: function () {
            $('body').loading('stop');
        },
        success: function (json) {
            $('footer').prev().remove();
            $('header').after(json);
            setFilter();
        },
        error: function (result, status, s) {
            $('body').loading('stop');
        },
    });
}

function setFilter() {
    $(".filter-content").empty();
    const filters = {
        "manufacture": String(getURLVar("manufacture")),
        "color": String(getURLVar("color")),
        "size": String(getURLVar("size")),
        "special": String(getURLVar("special")),
        "price-max": String(getURLVar("price-max")),
        "price-min": String(getURLVar("price-min"))
    };
    var filter_count = 0;
    Object.entries(filters).forEach(function ([key, value]) {
        if (value !== "") {
            const filterSpliter = value.split('_');
            for (i = 0; i < filterSpliter.length; i++) {
                filter_count++;
                var prefix = "";
                if (key === "price-min") {
                    prefix = "prix min: ";
                } else if (key === "price-max") {
                    prefix = "prix max: ";
                }
                $(".filter-content").append('<div class="filter-generate" onclick="removeFilter(this)" style="display: inline;margin-bottom: 15px" data-info="' + key + '">' + prefix + filterSpliter[i].replace(".", " ") + '<i class="fa fa-close" style="margin-left: 5px"></i></div>');
            }

            if (filter_count >= 3 && $('.clear-all-filters').get().length === 0) {
                $(".filter-content").prepend('<span class="filter-generate clear-all-filters" onclick="removeAllFilters()">Retirer tous les filtres<i class="fa fa-close" style="margin-left: 5px"></i></span>');
            }
            $('.dropdown-filter').hide(200);
            $('.dropdownFilter').css({"border": "1px solid #e7e7e7"});
            $('.dropdownFilter').css({"position": "relative"});
        }
    });
}

function removeAllFilters() {
    updateQueryStringParam('manufacture', '');
    updateQueryStringParam('price-min', '');
    updateQueryStringParam('price-max', '');
    updateQueryStringParam('color', '');
    updateQueryStringParam('special', '');
    updateQueryStringParam('size', '');
    filterGenerator()
}

function updateQueryStringParam(param, value) {
    baseUrl = [location.protocol, '//', location.host, location.pathname].join('');
    urlQueryString = document.location.search;
    let newParam = param + '=' + value;
    let params = '?' + newParam;
    // If the "search" string exists, then build params from it
    if (urlQueryString && value !== '') {
        keyRegex = new RegExp('([\?&])' + param + '[^&]*');
        // If param exists already, update it
        if (urlQueryString.match(keyRegex) !== null) {
            params = urlQueryString.replace(keyRegex, "$1" + newParam);
        } else { // Otherwise, add it to end of query string
            params = urlQueryString + '&' + newParam;
        }
    } else {
        params = urlQueryString.replace("&" + param + "=" + getURLVar(param), "");
    }

    window.history.replaceState({}, "", baseUrl + params);
}

function openSlide() {
    $("#slidebar").css({"width": "100%"});
}

function closeSlide() {
    $("#slidebar").css({"width": "0px"});
}