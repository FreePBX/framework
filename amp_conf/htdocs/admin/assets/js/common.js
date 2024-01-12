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