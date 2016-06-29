@extends('layout')

@section('action', 'order')

@section('content')
<div class="panel panel-default">
  <!-- Default panel contents -->
<div class="panel-heading">
    <span id='refresh' class="glyphicon glyphicon-refresh" aria-hidden="true" style=""></span>&nbsp;
    <h4 style="display: inline-block;">近一时段订单</h4>
</div>
<div class="panel-body">
    <p>本时段盈亏： {{ $pl['total'] }}</p>
    <p>
        @foreach ($pl as $name => $item)
            <?php if ($name == 'total') continue; ?>
            {{ $name }}： {{ $item }}  &nbsp;&nbsp;&nbsp;&nbsp;
        @endforeach
    </p>
</div>
<div class="table-responsive">

    <table class="table table-striped">
      <thead>
        <tr>
          <th>单号</th> <th>合约</th> <th>K线</th> <th>幅值</th> <th>买卖</th> <th>开平</th> <th>类型</th> <th>报单时间</th> <th>成交/撤单时间</th> <th>报单价格</th> <th>成交价格</th> <th>报单手数</th> <th>未成交手数</th> <th>盈亏</th> <th>响应耗时</th> <th>成交耗时</th> <th>详细状态<th>
        </tr>
      </thead>
      <tbody>
        @foreach ($list as $line)
        <tr>
            {{-- <th scope="row">{{ $line[0] }}</th> --}}
            @foreach ($line as $i => $item)
                <?php if (in_array($i, [1, 15])) continue; ?>
                <td>{{ $item }}</td>
            @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
</div>
    <nav>
    <ul class="pager">
    <li><a href="?p={{ $pre }}">Previous</a></li>
    <li><a href="?p={{ $next }}">Next</a></li>
    </ul>
    </nav>
<script>
    $('#refresh').click(function() {
        window.location.href=window.location.href;
    })
</script>
@stop
