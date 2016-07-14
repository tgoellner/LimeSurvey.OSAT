$(document).on('ready', function()
{
    $(document).on(
    {
        'change.osat' : function(e)
        {
            var location = window.location.href.replace(/(\?|&)lang=[a-z]{2}/i, '');
            var hash = location.match(/#.*$/) ? location.match(/#.*$/)[0] : '';
            location = location.replace(hash,'');
            location+= (location.indexOf('?') > -1 ? '&' : '?') + 'lang=' + this.options[this.selectedIndex].value + hash;

            // window.location.href = location;
            $(this).trigger('updatelabel.osat');
        },
        'updatelabel.osat' : function(e)
        {
            var label = $('label[for="' + $(this).attr('id') + '"]');
            var value = this.options[this.selectedIndex].value;
            value = value[0].toUpperCase() + value.slice(1);
            $(label).text(value);
        }
    }, '.languagechanger');
});
