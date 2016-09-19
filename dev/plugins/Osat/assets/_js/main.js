$(document).on('ready', function(e)
{
    $(document).on(
    {
        'toggle.osat' : function(e)
        {
            $(this).nextAll('.controls').first().toggleClass('is--open');
        },
        'close.osat' : function(e)
        {
            $(this).nextAll('.controls').first().removeClass('is--open');
        },

        'click.osat' : function(e)
        {
            $(this).trigger('toggle.osat');
        }
    }, '.setting.control-group.setting-text label.control-label, .setting.control-group.setting-html label.control-label');


    $(document).on(
    {
        'toggle.osat' : function(e)
        {
            var items = $(this).nextAll();
            for(var i = 0; i < items.length; i++)
            {
                var el = items[i];
                if($(el).is('.setting.control-group.setting-info'))
                {
                    break;
                }
                else
                {
                    $(el).toggleClass('is--open');
                    if($(el).hasClass('is--open'))
                    {
                        if($(el).find('label.control-label').length)
                        {
                            $(el).find('label.control-label').trigger('close.osat');
                            if(i == 0)
                            {
                                $(el).find('label.control-label').trigger('toggle.osat');
                            }
                        }
                    }
                }
            };
        },

        'click.osat' : function(e)
        {
            $(this).trigger('toggle.osat');
        }
    }, '.setting.control-group.setting-info');

    // first is open!
    $('.setting.control-group.setting-info').first().trigger('toggle.osat');

});
