import $ from 'jquery';

export default function(target, init = false, callback = null) {
    if(init === true) {
        $(target).css('opacity', '100');
    } else {
        $(target).fadeTo(4000, 0, function() {
            $(target).addClass('d-none');

            if(callback) {
                callback();
                $("#collapseExample").removeClass('show');
            }
        });
    }
}