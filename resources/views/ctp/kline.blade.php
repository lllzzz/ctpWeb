@extends('layout')

@section('action', 'kline')

@section('content')

<form class="form-inline" method="GET">
  <div class="form-group">
    <label for="startTime" class="control-label">Instrumnet:</label>
    <select class="form-control" id="instrumnet" name="iID" style="width: 140px;"></select>
    &nbsp;
  </div>
  <div class="form-group">
  <label for="startTime" class="control-label">Range:</label>
    <select class="form-control" id="range" name="r" style="width: 140px;"></select>
    &nbsp;
  </div>
  <div class="form-group">
    <label for="startTime" class="control-label">Length:</label>
    <input type="text" class="form-control" style="width: 140px;" name="l" value="{{$l}}">
    &nbsp;
  </div>
  <button type="submit" class="btn btn-default">Show</button>
</form>
<br>

<script src="/js/echart.js"></script>
<script src="/js/dark.js"></script>
@if (isset($error) && $error == 1)
    <h5>该参数没有K线</h5>
@else
<div id="main" style="width: 100%;height:400px;"></div>
<div id="main2" style="width: 100%;height:400px;"></div>
<script>
    var iIDRange = {
        'sn1609': [80],
        'SR609': [6],
        'cu1608': [60],
        'hc1610': [8],
        'zn1608': [30],
    };

    for (iID in iIDRange) {

        if (iID == 'sn1609') {
            $('#instrumnet').append('<option value="' + iID + '" selected>' + iID + '</option>');
            var ranges = iIDRange[iID];
            for (var i = 0; i < ranges.length; i++) {
                if (i == 0) $('#range').append('<option value="' + iIDRange[iID][i] + '" selected>' + iIDRange[iID][i] + '</option>');
                else $('#range').append('<option value="' + iIDRange[iID][i] + '">' + iIDRange[iID][i] + '</option>');
            }
        } else {
            $('#instrumnet').append('<option value="' + iID + '">' + iID + '</option>');
        }
    }

    $('#instrumnet').change(function() {
        var iID = $('#instrumnet option:selected').val();
        console.log(iID);
        var ranges = iIDRange[iID];
        $('#range').empty();
        for (var i = 0; i < ranges.length; i++)
            $('#range').append('<option value="' + iIDRange[iID][i] + '">' + iIDRange[iID][i] + '</option>');
    })

    var klineInfo = {
        @foreach ($dateTime as $k => $t)
        {{$k}}: '{{$t}}',
        @endforeach
    };

    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('main'));
    var myChart2 = echarts.init(document.getElementById('main2'));

    option = {
        title: {
            text: '最新K线图',
            subtext: '{{$iID}}',
        },
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            },
            formatter: function (params) {
                console.log(params);
                var tar;
                if (params[1].value != '-') {
                    tar = params[1];
                }
                else {
                    tar = params[2];
                }
                return tar.name + '<br/>' + tar.seriesName + ' : ' + tar.value + '<br/>' + klineInfo[tar.name];
            }
        },
        xAxis: {
            type : 'category',
            splitLine: {show:false},
            data :  [{{ implode(',', $index )}}]
        },
        yAxis: {
            type : 'value',
            min: {{ $min }},
            max: {{ $max }}
        },
        series: [
            {
                name: '最低价',
                type: 'bar',
                stack: '总量',
                itemStyle: {
                    normal: {
                        barBorderColor: 'rgba(0,0,0,0)',
                        color: 'rgba(0,0,0,0)'
                    },
                    emphasis: {
                        barBorderColor: 'rgba(0,0,0,0)',
                        color: 'rgba(0,0,0,0)'
                    }
                },
                data: [{{ implode(',', $base )}}]
            },
            {
                name: '涨',
                type: 'bar',
                stack: '总量',
                @if ($l <= 50)
                label: {
                    normal: {
                        show: true,
                        position: 'top'
                    }
                },
                @endif
                data: [<?php echo htmlspecialchars_decode(implode(',', $up )) ?>]
            },
            {
                name: '跌',
                type: 'bar',
                stack: '总量',
                @if ($l <= 50)
                label: {
                    normal: {
                        show: true,
                        position: 'top'
                    }
                },
                @endif
                data: [<?php echo htmlspecialchars_decode(implode(',', $down )) ?>]
            },
            @if (!isset($error) || $error != 2)
            @foreach ($orderData as $i => $item)
            {
                name: '交易',
                type: 'line',
                stack: '交易{{$i}}',
                itemStyle: {
                    normal: {color: @if (isset($item['type']) && $item['type'] == 'up') '#ff0000' @else '#00ff00' @endif}
                },
                data: [<?php echo htmlspecialchars_decode(implode(',', $item['data']))?>]
            },
            @endforeach
            @endif
        ]
    };
    myChart.setOption(option);

    @if (!isset($error) || $error != 2)
    option2 = {
        title: {
            text: '对应资金变化',
        },
        xAxis: {
            type : 'category',
            splitLine: {show:false},
            data :  [{{ implode(',', $index )}}]
        },
        yAxis: {
            type : 'value',
            min: {{ min($cPrice) }},
            max: {{ max($cPrice) }}
        },
        series: [
            {
                name: '资金变化',
                type: 'line',
                stack: '资金变化',
                itemStyle: {
                    normal: {
                        color: 'rgb(255, 70, 131)'
                    }
                },
                areaStyle: {
                    normal: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                            offset: 0,
                            color: 'rgb(255, 158, 68)'
                        }, {
                            offset: 1,
                            color: 'rgb(255, 70, 131)'
                        }])
                    }
                },
                data: [<?php echo implode(',', $cPrice)?>]
            }
        ]
    };
    myChart2.setOption(option2);
    @endif
</script>
@endif
@stop
