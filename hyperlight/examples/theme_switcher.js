$(document).ready(function() {
    $('#switch-buttons a').each(function (i, btt) {
        $(btt).click(function () {
            if ($(this).hasClass('active'))
                return false;
            $('#switch-buttons a').each(function (i, btt) { $(btt).removeClass('active'); });
            var cssPath = '../colors/' + this.id.replace('theme-', '') + '.css';
            var cssAlreadyLoaded = false;
            $('pre.source-code').each(function (j, code) {
                $(code).fadeOut('normal', function() {
                    if (!cssAlreadyLoaded) {
                        cssAlreadyLoaded = true;
                        $('link#theme').attr({href: cssPath});
                    }
                });
            });
            $('pre.source-code').each(function (j, code) { $(code).fadeIn('normal'); });
            $(this).addClass('active');
            return false;
        });
    });
});
