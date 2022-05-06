$(document).ready(function () {
    $('.fa-toggle-on').click(function () {
        $(this).toggleClass("fa-toggle-on fa-toggle-off");
    });

    if (!$(".cron-ui").hasClass('row')) {
        $(".cron-ui").addClass('row');
    }

});