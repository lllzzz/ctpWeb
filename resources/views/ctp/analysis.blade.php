@extends('layout')

@section('action', 'analysis')

@section('content')
<link rel="stylesheet" type="text/css" href="http://www.bootcss.com/p/bootstrap-datetimepicker/bootstrap-datetimepicker/css/datetimepicker.css">
<script src="http://www.bootcss.com/p/bootstrap-datetimepicker/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"></script>
<div class="alert alert-warning" role="alert">由于系统存在脏数据，统计起始日期为2016-06-29 09:00以后，默认为一天内的数据统计</div>
<form class="form-inline" method="GET">
  <div class="form-group">
    <label for="startTime" class="control-label">Start Time</label>
    <input name="startTime" class="form-control form_datetime" style="width: 140px;" type="text" value="{{ $start }}" readonly>
    &nbsp;
  </div>
  <div class="form-group">
    <label for="startTime" class="control-label">Stop Time</label>
    <input name="endTime" class="form-control form_datetime" style="width: 140px;" type="text" value="{{ $end }}" readonly>
    &nbsp;
  </div>
  <button type="submit" id="btn" class="btn btn-default">Show By Time</button>
  <button type="submit" id="all_btn" class="btn btn-default">Show All</button>
  <input type="hidden" name="all" value="0" id="all">
</form>
<br>

<div class="table-responsive">

    <table class="table table-striped">
      <thead>
        <tr>
          <th>合约</th> <th>总下单</th> <th>开平组合</th> <th>盈亏</th> <th>每手盈亏</th> <th>预测单</th> <th>实时单</th> <th>追价单</th> <th>强平单</th> <th>开仓预测/成功/成功率</th> <th>实时开仓/成功/成功率</th> <th>平仓预测/成功/成功率</th> <th>实时平仓/成功/成功率</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($list as $line)
        <tr>
            @foreach ($line as $i => $item)
                <td>{{ $item }}</td>
            @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
</div>

<script type="text/javascript">
    $(".form_datetime").datetimepicker({format: 'yyyy-mm-dd hh:ii'});
    $('#btn').click(function() {
      $('#all').val(0);
    })
    $('#all_btn').click(function() {
      $('#all').val(1);
    })
</script>
@stop
