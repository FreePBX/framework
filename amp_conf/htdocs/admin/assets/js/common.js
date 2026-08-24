// bootstrap-table maps its icons to Bootstrap Icons when it detects Bootstrap 5,
// but the admin only ships Font Awesome 4.7.0. fa-sync is FA5-only, so the
// refresh glyph has to fall back to the FA4 name.
if ($.fn.bootstrapTable) {
    $.fn.bootstrapTable.defaults.iconsPrefix = 'fa';
    $.fn.bootstrapTable.defaults.icons = $.extend({}, $.fn.bootstrapTable.defaults.icons, {
        refresh: 'fa-refresh'
    });
}

// bootstrap-multiselect 2.0 only injects the filter clear icon on Firefox and
// otherwise relies on the native search-cancel control. That native control is
// hidden so it does not clash with the themed X, so Chrome never gets a button
// unless the template already contains one.
if ($.fn.multiselect && $.fn.multiselect.Constructor && $.fn.multiselect.Constructor.prototype.defaults) {
    $.fn.multiselect.Constructor.prototype.defaults.templates.filter =
        '<div class="multiselect-filter d-flex align-items-center">' +
            '<i class="fa fa-search"></i>' +
            '<input type="search" class="multiselect-search form-control" />' +
            '<i class="fa fa-times multiselect-clear-filter"></i>' +
        '</div>';
}

$(document).ready(function () {
    $('.fa-toggle-on').click(function () {
        $(this).toggleClass("fa-toggle-on fa-toggle-off");
    });

    if (!$(".cron-ui").hasClass('row')) {
        $(".cron-ui").addClass('row');
    }

    $('.modal-header').each(function () {
        let el = $(this).children();
        if (el.length > 0) {
            if ($(el[0]).hasClass('mr-auto') || $(el[0]).hasClass('modal-title')) {
                $(this).css('flex-direction', 'row');
            }
        }
    });

    let allNavTabs = $('.nav-tabs');
    allNavTabs.each(function () {
        // Bootstrap 4 nav tab active check
        let tabs = $(this).find('li > a');
        let isTabActive = false;
        for (let tab of tabs) {
            if ($(tab).hasClass('active')) {
                isTabActive = true;
            }
        }
        if (!isTabActive) {
            // Bootstrap 3 nav tab active check
            tabs = $(this).find('li');
            for (let tab of tabs) {
                if ($(tab).hasClass('active')) {
                    $(tab).children('a').addClass('active');
                }
            }
        }
    });

});