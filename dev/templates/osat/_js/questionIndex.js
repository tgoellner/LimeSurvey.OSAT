var questionIndexByDefault = {

    init : function(){
        var questionIndex = $('#index-menu'),
            toggleButton = null;

        if(questionIndex.length)
        {
            questionIndex = questionIndex.first();

            if(questionIndex.find('.dropdown-toggle').length)
            {
                toggleButton = questionIndex.find('.dropdown-toggle').first();

                toggleButton.on('click.osat', function(){
                    var qi = $(this).parents('#index-menu').first();

                    if(qi.hasClass('openByDefault'))
                    {
                        qi.removeClass('openByDefault');
                        qi.removeClass('open');

                        questionIndexByDefault.setStateToSession(qi,'hide');
                    }
                    else {
                        qi.addClass('openByDefault');
                        questionIndexByDefault.setStateToSession(qi,'show');
                    }

                    return false;
                })
            }

            console.log('state : ' + questionIndexByDefault.getStateFromSession(questionIndex));
            if(questionIndexByDefault.getStateFromSession(questionIndex) !== false)
            {
                questionIndex.addClass('open openByDefault');
            }
        }
    },

    getHashFromEl : function(el)
    {
        var hash = '#' + $(el).attr('id') + '.' + $(el).attr('class').replace(/ /g,'.').replace('.openByDefault','').replace('.open','');
        return hash;
    },

    getStateFromSession : function(qi)
    {
        var hash = questionIndexByDefault.getHashFromEl(qi);
        return typeof(localStorage['osat.questionIndex.' + hash]) == 'undefined' ? null : localStorage['osat.questionIndex.' + hash] === 'show';
    },

    setStateToSession : function(qi, state)
    {
        var hash = questionIndexByDefault.getHashFromEl(qi);
        console.log('Now save ' + hash + ' state to ' + state);

        localStorage['osat.questionIndex.' + hash] = state;
    }
};


$(document).on('ready', questionIndexByDefault.init);
