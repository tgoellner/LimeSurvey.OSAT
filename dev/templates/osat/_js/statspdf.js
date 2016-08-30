var osat_statspdf = {
    'html_prefix' : '.osatstats',

    init : function() {
        $(document).on({
            'click.osatjspdf' : function(e){
                try {
                    e.preventDefault()
                }
                catch(ev) {};

                var options = this.getAttribute('data-jspdf-options') ? JSON.parse(this.getAttribute('data-jspdf-options')) : {};

                osat_statspdf.print(options);

                return false;
            }
        }, '.jspdf-print');
    },

    print : function(options) {
        var html =  '<div><div class="wrapper">' + this.getH1() + this.getH2() + '</div>' +
                    '<div class="diagram"></div>' + // this.getDiagram() +
                    this.getTexts() +
                    this.getFooter() +
                    '</div>';

        html = $(html).get(0).innerHTML;

        html = window.btoa(unescape(encodeURIComponent(html)));

        url = window.location.href;

        // html = this.insertInlineStyles(html);
        var form = $('<form style="display:none" action="' + url + '" target="_blank" method="post" enctype="multipart/form-data" />');
        $('input[name="YII_CSRF_TOKEN"]').first().clone().appendTo(form);
        $('<input type="hidden" name="action" value="statspdf" />').appendTo(form);
        $('<input type="hidden" name="html" value="' + html + '" />').appendTo(form);
        $('<input type="hidden" name="data" value="' + window.btoa(JSON.stringify(this.getDiagramData())) + '" />').appendTo(form);
        $('<input type="hidden" name="options" value="' + window.btoa(JSON.stringify(options)) + '" />').appendTo(form);
        $('<input type="submit" />').appendTo(form);

        form.appendTo($(('body')));
        form.submit();
        form.remove();
    },

    getStyles : function() {
        var base = {
            'font-family' : 'Arial, sans-serif',
            'font-size' : '12px',
            'font-weight' : 'normal',
            'text-transform' : 'none',
            'font-style' : 'normal',
            'line-height' : '14pt',
            'letter-spacing' : '0',
            'color' : '#000',
            'text-align' : 'left'
        },
        styles = {
            'h1' : {},
            'h2' : {},
            'h3' : {},
            'h4' : {},
            'h5' : {},
            'p' : {}
        }

        for(var p in base) {
            base[p] = window.getComputedStyle($(this.html_prefix).get(0), null).getPropertyValue(p);
        }

        for(var p in styles) {
            if($(this.html_prefix + ' ' + p).length) {
                var el = $(this.html_prefix + ' ' + p).get(0);
                for(var q in base) {
                    styles[p][q] = window.getComputedStyle(el, null).getPropertyValue(q);
                }
            }
        }

        styles.body = base;

        return styles;
    },

    getCss : function() {
        var styles = this.getStyles(),
            css = [];

        for(var p in styles) {
            var attr = [];
            for(var q in styles[p]) {
                attr.push(q + ' : ' + styles[p][q]);
            }
            if(attr.length) {
                css.push(p + " {\n\t" + attr.join(";\n\t") + ";\n}");
            }
        }

        css = css.join("\n\n", css);

        return css;
    },

    insertInlineStyles : function(html) {
        var styles = this.getStyles();

        for(var p in styles) {
            var attr = [];
            for(var q in styles[p]) {
                attr.push(q + ':' + styles[p][q]);
            }
            if(attr.length) {
                styles[p] = attr.join(';');
            }
        }

        for(var p in styles) {
            var reg = new RegExp('<(' + p + ')>', 'ig');

            html = html.replace(reg,'<$1 style="' + styles[p] + '">');
        }

        return html;
    },

    trim : function(text) {
        return $.trim(text).replace(/\s/g,' ').replace(/ +/g,' ');
    },

    getH1 : function() {
        var h1 = '';

        if($(this.html_prefix + '--header--title').length)
        {
            h1+= '<h1>' + this.trim($(this.html_prefix + '--header--title').first().text()) + '</h1>';
        }
        return h1;
    },

    getH2 : function() {
        var h2 = '';

        if($(this.html_prefix + '--header--owner').length)
        {
            var el = $(this.html_prefix + '--header--owner').first().clone();
            el.find('br').replaceWith(',');
            el = el.text();
            el= el.split(',');

            h2 = [];

            for(var i=0; i<el.length; i++) {
                el[i] = this.trim(el[i]);
                if(el[i]) {
                    h2.push(el[i]);
                }
            }

            if(h2.length) {
                h2 = '<h2>' + h2.join(', ', h2) + '</h2>';
            }
            else {
                h2 = '';
            }
        }
        return h2;
    },

    getDiagramData : function() {
        var bars = [];

        $('#' + this.html_prefix.substr(1) + '-chart table ' + this.html_prefix + '-table--group').each(function(i, group){
            var bar = {};

            if($(group).find('.is--total button').length)
            {
                var el = $(group).find('.is--total button').get(0);

                bar.total = {
                    height: parseFloat(el.style.height) / 100,
                    color: window.getComputedStyle(el, null).getPropertyValue("background-color")
                }
            }

            if(bar.total) {
                bar.label = $(group).find('.is--label').length ? $.trim($(group).find('.is--label').text()) : bars.length + 1;

                if($(group).find('.is--average').length)
                {
                    var el = $(group).find('.is--average').get(0);
                    if(parseInt(window.getComputedStyle(el, null).getPropertyValue("height")) > 0)
                    {
                        bar.average = {
                            height: parseFloat(el.style.height) / 100, // window.getComputedStyle(el, null).getPropertyValue("height"),
                            color: window.getComputedStyle(el, null).getPropertyValue("background-color")
                        }
                    }
                }

                bars.push(bar);
            }
        });

        return bars;
    },

    getDiagram : function() {
        var width = typeof(arguments[0]) != 'undefined' ? parseInt(arguments[0]) : 180,
            height = typeof(arguments[1]) != 'undefined' ? parseInt(arguments[1]) : Math.round((width * 0.5) * 100) / 100,
            unit = 'mm',
            diagram = '',
            bars = this.getDiagramData();

        if(bars.length) {
            var bar_padding = 0.05,
                bar_gap = 0.02,
                bar_lastgap = 0.05;

            var bar_width = 1 - (bar_padding * 2) - bar_lastgap;
            if(bars.length > 3) {
                bar_width-= (bars.length - 2) * bar_gap;
            }
            bar_width = Math.round( (bar_width / bars.length) * 1000) / 1000;


            diagram+= '<div class="diagram" style="width:' + width + unit + ';height:'+ height + unit + ';padding:' + (Math.floor(bar_padding * width) - 2) + unit + ';">';

            diagram+= '<div class="bar-grid-top" style="margin-left:-' + bar_padding + unit + ';margin-right:-' + bar_padding + unit + ';border-top:1pt dotted #666;z-index:5;"></div>';
            diagram+= '<div class="bar-grid-mid" style="margin-top:' + (Math.floor((height - bar_padding - bar_padding) * 50) / 100) + unit + ';margin-bottom:' + (Math.floor((height - bar_padding - bar_padding) * -50) / 100) + unit + ';margin-left:-' + bar_padding + unit + ';margin-right:-' + bar_padding + unit + ';border-top:1pt dotted #666;z-index:5;"></div>';

            $(bars).each(function(i, bar){
                var marginright = bar_gap,
                    barwrap = {
                        width : Math.round((bar_width * width) * 100) / 100,
                        height : Math.round((height * (1 - bar_padding * 2)) * 100) / 100,
                        'padding-right' : Math.round((bar_gap * width) * 100) / 100
                    },
                    total = {
                        width: Math.floor((barwrap.width * (bar.average ? 0.75 : 1)) * 100) / 100,
                        height: Math.floor((bar.total.height * barwrap.height) * 100) / 100
                    },
                    average = !bar.average ? null : {
                        width: Math.floor((barwrap.width - total.width) * 100) / 100,
                        height: Math.round((bar.average.height * barwrap.height) * 100) / 100
                    };

                if(i == bars.length - 2)
                {
                    barwrap['padding-right'] = Math.round((bar_lastgap * width) * 100) / 100;
                }
                else if(i == bars.length - 1)
                {
                    barwrap['padding-right'] = 0;
                }

                diagram+= '<div class="bar-wrap" style="width:' + barwrap.width + unit + ';height:'+ barwrap.height + unit + ';padding-right:' + barwrap['padding-right'] + unit + ';">';

                total['margin-top'] = Math.round((barwrap.height - total.height) * 100) / 100;
                diagram+= '<div class="bar-total" style="width:' + total.width + unit + ';height:' + total.height + unit + ';margin-top:' + total['margin-top'] + unit + ';background-color:' + bar.total.color + '"></div>';
                if(average) {
                    average['margin-top'] = Math.round((total.height - average.height) * 100) / 100;
                    diagram+= '<div class="bar-average" style="width:' + average.width + unit + ';height:' + average.height + unit + ';margin-top:' + average['margin-top'] + unit + ';background-color:' + bar.average.color + '"></div>';
                }
                diagram+= '<div class="bar-legend" style="width:100%;">' + bar.label + '</div>';

                diagram+= '</div>';
            });

            diagram+= '<div class="bar-grid-base" style="margin-top:-18pt;margin-left:-' + bar_padding + unit + ';margin-right:-' + bar_padding + unit + ';border-top:1pt dotted #666;z-index:5;"></div>';

            diagram+='</div>';
        }
        return diagram;
    },

    getTexts : function() {
        var prefix = this.html_prefix + '-text--assessment',
            texts = [];

        $('#main-col ' + prefix).each(function(i, el){
            var headline = '',
                summary = '',
                assessment = '';

            if($(el).find(prefix + '--label').length || $(el).find(prefix + '--groupname').length)
            {
                var tmp = [];
                if($(el).find(prefix + '--label').length)
                {
                    tmp.push($.trim($(el).find(prefix + '--label').text()));
                }
                if($(el).find(prefix + '--groupname').length)
                {
                    tmp.push($.trim($(el).find(prefix + '--groupname').text()));
                }

                headline+='<h3>' + tmp.join('<br>', tmp) + '</h3>';
            }

            if($(el).find(prefix + '--summary').length) {
                summary+='<strong>Some content in bold</strong> <strong><em>strong italic</em></strong> and other <ul><li>eone</li><li>two</li><li>Three with some very sdkjfhdksj hldfgjh lakjgh lkdsfjgh lkdfsjgh ldksfjgh lkdfsjgh lkdjsfhg lkjdsfhg lkjsdfhg lkjsdhg lkdjfh gl</li><li>four</li></ul> <ol><li>eone</li><li>two</li><li>Three with some very sdkjfhdksj hldfgjh lakjgh lkdsfjgh lkdfsjgh ldksfjgh lkdfsjgh lkdjsfhg lkjdsfhg lkjsdfhg lkjsdhg lkdjfh gl</li><li>four</li></ol>',
                summary+= $(el).find(prefix + '--summary').html();
            }
            if($(el).find(prefix + '--assessment').length) {
                assessment+= $(el).find(prefix + '--assessment').html();
            }

            texts.push(headline + summary + assessment);
        });

        return texts.length ? '<div class="wrapper">' + texts.join('') + '</div>' : '';
    },

    getFooter : function() {
        var footer = [],
            block, img, src;

        if($('.page-footer--legal').length) {
            block = $('.page-footer--legal').first().clone(1);

            if(block.find('*[data-target]').length) {
                img = block.find('*[data-target]').first();
                src = window.getComputedStyle($('.page-header--logo').get(0)).getPropertyValue('background-image');
                if(src) {
                    src = src.replace(/url\("(data:[^"]+)"\)/,"$1");
                    img.replaceWith('<img src="' + src + '" /><br /><br />');
                }
            }

            footer.push('<div class="page-footer--legal">' + block.html() + '</div>');
        }

        if($('.page-footer--sponsors').length) {
            block = $('.page-footer--sponsors').first().clone(1);

            if(block.find('.logo-erasmus-plus').length) {
                img = block.find('.logo-erasmus-plus').first();
                src = window.getComputedStyle($('.page-footer--sponsors .logo-erasmus-plus').get(0), ':before').getPropertyValue('background-image');
                if(src) {
                    src = src.replace(/url\("(data:[^"]+)"\)/,"$1");
                    img.replaceWith('<br /><img src="' + src + '" /><br /><br />');
                }
            }

            footer.push('<div class="page-footer--sponsors">' + block.html()+ '</div>');
        }

        return footer.length ? '<div class="page-footer">' + footer.join("\n") + '</div>' : '';
    }
}

$(document).on('ready', osat_statspdf.init);
