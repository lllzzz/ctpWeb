@extends('layout')

@section('action', 'config')

@section('content')
<style type="text/css">
    td, th {
        text-align: center;
    }
</style>
<div class="alert alert-warning" role="alert">每个合约只能配置一组参数，如果需要统一合约开多个幅值，请人工设置</div>
<form class="form-inline" method="POST">
    {!! csrf_field() !!}
    <button type="submit" class="btn btn-default">Save</button>
    <button class="btn btn-default" id="btn_new">New</button>
    <br><br>
    <input type="hidden" id="iIDs" name="iIDs" value="{{ implode(',', $iIDs) }}">
    <div class="form-group">
        <label for="startTime" class="control-label">开始时间:</label>
        @foreach ($startTime as $one)
            <input name="startTime[]" class="form-control" style="width: 65px;" type="text" value="{{ $one }}">
        @endforeach
    </div>
    <br><br>
    <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
              <th>是否删除</th> <th>合约</th> <th>终止时间</th> <th>最小波动</th> <th>连续K线止损(2表示关闭)</th> <th>KRange</th> <th>Period</th> <th>Threshold Trend</th> <th>Threshold Vibrate</th>
            </tr>
        </thead>
        <tbody id="table_body">
        @foreach ($configs as $iID => $line)
            <tr>
                <td>
                    <input type="checkbox" name="del[]" value="{{$iID}}">
                </td>
            @foreach ($line as $i => $item)
                <td>
                @if ($i == 'stopTime')
                    @foreach ($item as $one)
                    <input type="text" name="{{$iID}}[{{$i}}][]" class="form-control input-sm" value="{{$one}}" style="width: 55px;">
                    @endforeach
                @elseif ($i == 'iID')
                <input type="text" name="{{$iID}}[{{$i}}]" class="form-control input-sm" value="{{$item}}" style="width: 65px;" readonly="">
                @else
                <input type="text" name="{{$iID}}[{{$i}}]" class="form-control input-sm" value="{{$item}}" style="width: 55px;">
                @endif
                </td>
            @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</form>
<script>
    var update = function(obj) {
        var line = $(obj).parent().parent();
        var iid = obj.value;
        line.find('input').each(function() {
            var one = $(this).data('one');
            $(this).attr("name", iid + one);
        });
        var iIDs = $('#iIDs').val();
        iIDs = iIDs.split(',');
        iIDs.push(iid);
        $('#iIDs').val(iIDs.join(','))
    };

    $('#btn_new').click(function() {
        var html = '<tr>';
        html += '<td></td>';
        html += '<td><input type="text" data-one="[iID]" class="form-control input-sm" style="width: 65px;" onkeyup="update(this)"></td>';
        html += '<td>';
        html += '<input type="text" data-one="[stopTime][]" class="form-control input-sm" style="width: 55px;"> ';
        html += '<input type="text" data-one="[stopTime][]" class="form-control input-sm" style="width: 55px;"> ';
        html += '<input type="text" data-one="[stopTime][]" class="form-control input-sm" style="width: 55px;"> ';
        html += '</td>';
        html += '<td><input type="text" data-one="[minRange]" class="form-control input-sm" style="width: 55px;"></td>';
        html += '<td><input type="text" data-one="[serialKline]" class="form-control input-sm" style="width: 55px;"></td>';
        html += '<td><input type="text" data-one="[range]" class="form-control input-sm" style="width: 55px;"></td>';
        html += '<td><input type="text" data-one="[peroid]" class="form-control input-sm" style="width: 55px;"></td>';
        html += '<td><input type="text" data-one="[thresholdT]" class="form-control input-sm" style="width: 55px;"></td>';
        html += '<td><input type="text" data-one="[thresholdV]" class="form-control input-sm" style="width: 55px;"></td>';
        html += '</tr>';
        $('#table_body').append(html);
        return false;
    });
</script>
@stop
