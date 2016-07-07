$(document).on('ready', function()
{
    console.log("DROPDOWN");

    $(document).on(
    {
        'change.osat' : function(e)
        {
            // window.location.href = location;
            $(this).trigger('updatelabel.osat');
        },
        'updatelabel.osat' : function(e)
        {
            var label = $('label[for="' + $(this).attr('id') + '"]');
            var value = this.options[this.selectedIndex].value;
            if(label.length)
            {
                if(!value)
                {
                    value = $(label).attr('data-title');
                }
                $(label).text(value);
            }
        }
    }, '.form-group.is-select select');
    $('.form-group.is-select select').trigger('updatelabel.osat');
});
