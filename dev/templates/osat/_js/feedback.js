var osatfeedback = {
    init : function()
    {
        osatfeedback.addSubmitEvent();
    },

    addSubmitEvent : function(content)
    {
        content = typeof content == 'undefined' ? $('body') : content;

        $(content).find('#osat-feedback-form').on('submit.osatfeedback', osatfeedback.submitForm);
    },

    submitForm : function() {
        var form = this,
            container = $(form).parents('.osat-feedback').length ? $(form).parents('.osat-feedback').first() : $(form);

        $(container).addClass('is--loading');

        // Assign handlers immediately after making the request,
        // and remember the jqxhr object for this request
        var jqxhr = $.ajax({
            type : 'POST',
            url : $(form).attr('action'),
            headers: {
                "OSATFEEDBACK_AJAX" : "TRUE"
            },
            data : $(form).serialize()
        })
        .done($.proxy(function(data) {
            var container = $(this).hasClass('osat-feedback') ? $(this) : $(this).find('.osat-feedback'),
                data = $(data).hasClass('osat-feedback') ? $(data) : $(data).find('.osat-feedback');

            if(container && data)
            {
                $(container).replaceWith($(data));
                osatfeedback.addSubmitEvent(data);
            }
            else
            {
                $(this).addClass('has--error');
            }
        }, container))
        .fail(function() {
            $(this).addClass('has--error');x
        })
        .always($.proxy(function(data) {
            $(this).removeClass('is--loading');
        }, container));

        return false;
    }
}

$(document).on('ready', osatfeedback.init);
