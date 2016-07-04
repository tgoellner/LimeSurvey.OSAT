$(document).on('ready', function(e){
    console.log("Show stats");

    $('.is---chart-js').each(function(i, el){
        var data = $(el).attr('data-json');
        if(data)
        {
            if(data = atob(data))
            {
                if(data = JSON.parse(data))
                {
                    console.log(data);
                    new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: ["Red", "Blue", "Yellow", "Green", "Purple", "Orange"],
                            datasets: [{
                                label: '# of Votes',
                                data: [12, 19, 3, 5, 2, 3],
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(54, 162, 235, 0.2)',
                                    'rgba(255, 206, 86, 0.2)',
                                    'rgba(75, 192, 192, 0.2)',
                                    'rgba(153, 102, 255, 0.2)',
                                    'rgba(255, 159, 64, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(255,99,132,1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(255, 159, 64, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            high : 100,
                            low : 0,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                }
            }
        }
    });

    $('.is--chartist').each(function(i, el){
        var data = $(el).attr('data-json');
        if(data)
        {
            if(data = atob(data))
            {
                if(data = JSON.parse(data))
                {
                    var options = {
                        width: '100%',
                        fullWidth: true,
                        seriesBarDistance: 1,

                        axisX : {
                            showGrid: false,
                            showLabel: true,
                            scaleMinSpace: 1
                        },
                        axisY : {
                            showLabel: false,
                            showGrid: false,
                            offset: 0
                        }
                    };
                    var chart = new Chartist.Bar(el, data, options);
                    // console.log(data);
                }
            }
        }
    });

    $(document).on({
        'click.osat' : function(e) {
            var idx = $(this).parent().find('line').index(this);
            var label = $(this).parents('svg').find('.ct-labels .ct-label').eq(idx).text();

            if($('#osatstats-texts *[data-chartist-label="' + label + '"]').length)
            {
                $('#osatstats-texts').attr('data-toggled','1');
                $('#osatstats-texts *[data-chartist-label]').removeClass('is--active');
                $('#osatstats-texts *[data-chartist-label="' + label + '"]').addClass('is--active');
            }
            console.log(label);
        }
    }, '.is--chartist .ct-series-a .ct-bar')
});
