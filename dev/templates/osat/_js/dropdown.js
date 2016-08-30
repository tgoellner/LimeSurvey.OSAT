$(document).on('ready', function()
{
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

    $('select.sort-options').each(function(i, select)
    {
        var options = $(select).find('option');

        if(options.length > 1)
        {
            var selected = $(selected).val();

            $(options).each(function(i, option){
                var value = option.getAttribute('value');
                if (typeof value === typeof undefined || value === false || value === null)
                {
                    value = option.textContent;
                }
                value = value.toLowerCase().trim().replace(/ö/ig,'oe').replace(/ä/ig,'ae').replace(/ü/ig,'ue');
                option.setAttribute('data-value', value);
            });


            $(select).html(options.sort(function(a, b){
                var a_text = a.getAttribute('data-value'),
                    b_text = b.getAttribute('data-value');

                // console.log(a.getAttribute('data-value') + " / " + b.getAttribute('data-value') + " | " + a_text + " / " + b_text);

                return a_text == b_text ? 0 : a_text < b_text ? -1 : 1;
            }));

            $(select).val(selected);
        }
    });
});
