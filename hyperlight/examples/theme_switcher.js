$(document).ready(function() {
    $('#switch-buttons a').each(function (i, btt) {
        $(btt).click(function () {
            $('#switch-buttons a').each(function (i, btt) { $(btt).removeClass('active'); });
            $('link#theme').attr({href: '../colors/' + this.id.replace('theme-', '') + '.css'});
            $(this).addClass('active');
            return false;
        });
    });
});
