var reposBalloons = function() {
    var threshold_left = 100,
        threshold_right = $(document).width() - 100;

    $('*[data-balloon]').each(function(i, el){
        var l = $(el).offset().left,
            r = $(el).offset().left + $(el).outerWidth();

        if(l < threshold_left)
        {
            $(el).attr('data-balloon-pos', 'topleft');
        }
        else if(r > threshold_right)
        {
            $(el).attr('data-balloon-pos', 'topright');
        }
        else
        {
            $(el).attr('data-balloon-pos', null);
        }

        $(el).on('click.osatballoon', function(e){
            if($(this).parents('.answer-item').length)
            {
                try {
                    e.preventDefault();
                }
                catch(ev) {}
                return false;
            }
        })
    });
};

$(window).on('resize', reposBalloons);

$(document).on('ready', function(){
    reposBalloons();
});
