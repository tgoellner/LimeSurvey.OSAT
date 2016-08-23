$(document).on(
{
    'click.osat' : function(e){
        if(this.href.match(/^https?\:\/\//))
        {

            var thishost = window.location.hostname,
                hrefhost = this.href.replace(/^(https?\:\/\/)?([^\/]+)(.*)?$/,"$2"),
                islogout = this.href.match(/function\/logout/),
                dialogue = null;

            if(thishost != hrefhost)
            {
                // dialogue = $('#confirm-leave');
            }
            else if(islogout)
            {
                dialogue = $('#confirm-logout');
            }

            if(dialogue != null)
            {
                // display logout dialogue
                dialogue.find('.btn-ok').attr('href', $(this).attr('href'));
                dialogue.find('.btn-ok').attr('target', $(this).attr('target'));
                dialogue.modal('show');
                return false;
            }
        }
    }
}, 'a[href]:not(.btn-ok)');

$(document).on('ready', function(e){
    if($('#save-progress').length && $('#navigator-container').length)
    {
        $('#save-progress').insertAfter($('#navigator-container'));
    }
});
