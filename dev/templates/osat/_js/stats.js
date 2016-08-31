var osatstats = {
    init : function()
    {
        $(document).on({
            'click.osatstats' : osatstats.toggleQuestions
        }, '.osatstats-table:not(.is--chart) tr.osatstats-table--group');

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
            'click.osatstats' : osatstats.activateAssessment
        }, '*[data-gid]:not(.osatstats-text--assessment)');

        osatstats.addPrevNextButtons();
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

        // delete if filters cann be added
        $(form).find('select').not(this).each(function(i,el){
            el.selectedIndex = 0;
            $.proxy(osatstats.switchFilter, el);
        });

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
                "OSATSTATS_AJAX" : "TRUE"
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
                $(window).trigger('resize');
            }
            else
            {
                $(this).addClass('has--error');
            }
        }, container))
        .fail(function() {
            $(this).addClass('has--error');
        })
        .always($.proxy(function(data) {
            $(this).removeClass('is--loading');
        }, container));

        return false;
    },

    activateAssessment : function(e)
    {
        var gid = $(this).attr('data-gid');

        $(".is--active[data-gid]").removeClass('is--active');
        $('[data-gid="' + gid + '"]').addClass('is--active');

        var url = location.href,
            id = $('.osatstats-text--assessment.is--active').attr('id');

        if(this.nodeName.toLowerCase() != 'button')
        {
            location.href = "#"+id;                 //Go to the target element.
        }
    },

    addPrevNextButtons : function()
    {
        var elements = $('.osatstats-text--assessment[data-gid]'),
            length = elements.length;

        if(length > 1)
        {
            for(var i = 0; i < length; i++)
            {
                var navigation = $('<div class="osatstats-text--assessment--navigation" />').appendTo($(elements[i])),
                    types = {
                        'previous' : typeof(elements[i-1]) != 'undefined' ? $(elements[i-1]) : null,
                        'next' : typeof(elements[i+1]) != 'undefined' ? $(elements[i+1]) : null
                    };

                for(var p in types)
                {
                    if(types[p])
                    {
                        var title = [];
                        if(types[p].find('.osatstats-text--assessment--label').length)
                        {
                            title.push(types[p].find('.osatstats-text--assessment--label').text());
                        }
                        if(types[p].find('.osatstats-text--assessment--groupname').length)
                        {
                            title.push(types[p].find('.osatstats-text--assessment--groupname').text());
                        }
                        title = title.join(': ');

                        navigation.append('<a href="#' + types[p].attr('id') + '" class="osatstats-text--assessment--navigation-button is--' + p + '" data-gid="' + types[p].attr('data-gid') + '">' + title + '</a>');
                    }
                }
            }
        }
    }
}

$(document).on('ready', osatstats.init);
