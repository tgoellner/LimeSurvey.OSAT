$(document).on('ready', function()
{
    $('form#limesurvey').each(function(i,el){
        var url = $(el).attr('action');
        $(el).attr('action', url + '#limesurvey');
        if(url.indexOf('#') <= -1)
        {
            $(el).attr('action', url + '#limesurvey');
        }
    });
});
