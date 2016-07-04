var osatstats = {
    init : function()
    {
        $(document).on({
            'click.osatstats' : osatstats.toggleQuestions
        }, 'tr.osatstats-table--group');

        $(document).on({
            'change.osatstats' : osatstats.switchFilter
        }, '#assessmentfilter select');

        $(document).on({
            'change.osatstats' : function(e) {
                $(this).parents('form').first().trigger('submit.osatstats');
            }
        }, '#assessmentfilter select');

        $(document).on({
            'click.osatstats' : osatstats.resetFilter
        }, '#assessmentfilter button');

        osatstats.addSubmitEvent();


        $(document).on({
            'click.osatstats' : osatstats.displayAssessment
        }, '.is--chart tr.osatstats-table--group');
    },

    toggleQuestions : function(e)
    {
        var tbody = $(this).is('tbody') ? this : $(this).parents('tbody');
        if(tbody.length)
        {
            tbody.toggleClass('has--details');
        }
    },

    switchFilter : function(e)
    {
        var form = $(this).parents('form').first(),
            value = [],
            label = $('label[for="' + this.id + '"]').first();

        for( var i = 0; i < this.options.length; i++)
        {
            if(this.options[i].selected == true)
            {
                value.push($.trim($(this.options[i]).text()));
            }
        }

        if(value.length)
        {
            $(label).text(value.join(', ', value));
        }
        else
        {
            $(label).text($(label).attr('title'));
        }

        // $(form).submit();
        // osatstats.submitForm(form);
    },

    resetFilter : function(e)
    {
        var form = $(this).parents('form').first();
        $(form).find('select').each(function(i,el){
            el.selectedIndex = 0;
            $.proxy(osatstats.switchFilter, el);
        });

        $(form).trigger('submit.osatstats')

        return false;
    },

    addSubmitEvent : function(content)
    {
        content = typeof content == 'undefined' ? $('body') : content;

        $(content).find('#assessmentfilter').on('submit.osatstats', osatstats.submitForm);
    },

    submitForm : function() {
        var form = this,
            container = $(form).parents('.osatstats-table').length ? $(form).parents('.osatstats-table').first() : $(form);

        $(container).addClass('is--loading');

        // Assign handlers immediately after making the request,
        // and remember the jqxhr object for this request
        var jqxhr = $.ajax({
            type : 'POST',
            url : $(form).attr('action'),
            headers: {
                "OSATAJAX" : "TRUE"
            },
            data : $(form).serialize()
        })
        .done($.proxy(function(data) {
            var container = $(this).hasClass('osatstats-table') ? $(this) : $(this).find('.osatstats-table'),
                data = $(data).hasClass('osatstats-table') ? $(data) : $(data).find('.osatstats-table');

            if(container && data)
            {
                $(container).replaceWith($(data));
                osatstats.addSubmitEvent(data);
            }
            else
            {
                $(this).addClass('has--error');
            }
        }, container))
        .fail(function() {
            $(this).addClass('has--error');
            console.log('ERROR');
        })
        .always($.proxy(function(data) {
            $(this).removeClass('is--loading');
        }, container));

        return false;
    },

    displayAssessment : function()
    {
        var gid = $(this).parents('tbody').attr('data-gid'),
            text = $('.osatstats-text--assessment[data-gid="' + gid + '"]');

        $('.osatstats-text').not('[data-toggled]').attr('data-toggled', true);
        $('.osatstats-text .osatstats-text--assessment.is--active').removeClass('is--active');

        if(text)
        {
            $(text).addClass('is--active');
        }
    }
}

$(document).on('ready', osatstats.init);
