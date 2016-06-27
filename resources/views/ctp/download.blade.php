@extends('layout')

@section('action', 'download')

@section('content')
<link rel="stylesheet" type="text/css" href="http://www.bootcss.com/p/bootstrap-datetimepicker/bootstrap-datetimepicker/css/datetimepicker.css">
<script src="http://www.bootcss.com/p/bootstrap-datetimepicker/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"></script>


<form class="form-horizontal" action="download" method="POST">
    {!! csrf_field() !!}
    <div class="form-group">
        <label for="startTime" class="col-sm-2 control-label">Start Time</label>
        <div class="col-sm-10">
            <input name="startTime" class="form-control form_datetime" style="width: 140px;" type="text" value="{{ $now }}" readonly>
        </div>
    </div>
    <div class="form-group">
        <label for="startTime" class="col-sm-2 control-label">Stop Time</label>
        <div class="col-sm-10">
            <input name="endTime" class="form-control form_datetime" style="width: 140px;" type="text" value="{{ $now }}" readonly>
        </div>
    </div>
    <div class="form-group">
        <label for="type" class="col-sm-2 control-label">Type</label>
        <div class="col-sm-10">
            <label class="radio-inline">
                <input checked="" type="radio" name="type" id="inlineRadio1" value="order"> Order
            </label>
            <label class="radio-inline">
                <input type="radio" name="type" id="inlineRadio2" value="kline"> KLine
            </label>
            <label class="radio-inline">
                <input type="radio" name="type" id="inlineRadio3" value="tick"> Tick
            </label>
        </div>
    </div>
    <div class="form-group">
        <label for="Instrumnet" class="col-sm-2 control-label">Instrumnet</label>
        <div class="col-sm-10">
        <select class="form-control" id="instrumnet" name="iID" style="width: 140px;"></select>
        </div>
    </div>
    <div class="form-group">
        <label for="Instrumnet" class="col-sm-2 control-label">KRange</label>
        <div class="col-sm-10">
        <select class="form-control" id="range" name="r" style="width: 140px;"></select>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-default">Dowload</button>
        </div>
    </div>

</form>
<script type="text/javascript">
    $(".form_datetime").datetimepicker({format: 'yyyy-mm-dd hh:ii'});
    var iIDRange = {
        '':[0],
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
</script>
@stop
