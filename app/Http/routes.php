<?php
use App\Tick;
use App\Order;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
session_start();

Route::get('/', function () {
    return view('welcome');
});


Route::group(['middleware' => 'auth'], function() {

    Route::get('kline', function() {

        $iID = isset($_GET['iID']) ? $_GET['iID'] : 'sn1609';
        $length = isset($_GET['l']) ? $_GET['l'] : 50;
        $krange = isset($_GET['r']) ? $_GET['r'] : 80;
        $data['l'] = $length;
        $data['r'] = $krange;
        $data['iID'] = $iID;

        $sql = "SELECT * FROM `kline` WHERE `instrumnet_id` = '{$iID}' AND `range` = {$krange} ORDER BY id DESC LIMIT {$length}";
        $kline = DB::connection('ctp_1')->select($sql);
        $kline = array_reverse($kline);
        foreach ($kline as $item) {
            $index[] = $item->index;
            if ($item->open_price > $item->close_price) {
                $height[] = $item->open_price;
                $low[] = $item->close_price;
                $down[] = $range[] = $item->open_price - $item->close_price;
                $up[] = '"-"';
            } else {
                $height[] = $item->close_price;
                $low[] = $item->open_price;
                $up[] = $range[] = $item->close_price - $item->open_price;
                $down[] = '"-"';
            }
            $dateTime[$item->index] = $item->close_time;
        }
        if (count($kline) == 0) {
            $data['error'] = 1;
            return view('ctp.kline', $data);
        }
        $data['base'] = $low;
        $data['up'] = $up;
        $data['down'] = $down;
        $data['max'] = max($height);
        $data['min'] = min($low);
        $data['krange'] = min($range);
        $data['index'] = $index;
        $data['dateTime'] = $dateTime;

        $inSQL = implode(',', $index);
        $sql = "SELECT
            m.order_id, m.instrumnet_id, m.kindex, o.is_buy, o.is_open, m.is_forecast, m.is_zhuijia, o.srv_insert_time, o.srv_traded_time, o.start_time, o.start_usec, o.first_time, o.first_usec, o.end_time, o.end_usec, o.price, o.real_price, m.cancel_type, o.status, o.session_id, o.front_id, o.order_ref, o.cancel_tick_price
        FROM
            markov_kline_order as m,
            `order` as o
        WHERE
            m.order_id = o.order_id
            AND m.`instrumnet_id` = '{$iID}'
            AND m.`kindex` in ({$inSQL})
            AND m.`krange` = {$krange}
            AND o.status = 1";
        $order = DB::connection('ctp_1')->select($sql);

        if (count($order) == 0) {
            $data['error'] = 2;
            return view('ctp.kline', $data);
        }
        // 交易盈亏
        $orderData = [];
        $orderMap = [];
        $i = 0;
        foreach ($order as $item) {
            $orderMap[$item->kindex] = $item;
            if ($item->is_open) {
                $orderData[$i]['open_kindex'] = $item->kindex;
                $orderData[$i]['open_price'] = $item->real_price;
                $orderData[$i]['open_is_buy'] = $item->is_buy;
            } else {
                $orderData[$i]['close_kindex'] = $item->kindex;
                $orderData[$i]['close_price'] = $item->real_price;
                $orderData[$i]['close_is_buy'] = $item->is_buy;
                if (isset($orderData[$i]['open_price'])) {
                    if ($orderData[$i]['open_price'] > $orderData[$i]['close_price']) {
                        $orderData[$i]['type'] = $orderData[$i]['close_is_buy'] ? 'up' : 'down';
                    } else {
                        $orderData[$i]['type'] = $orderData[$i]['close_is_buy'] ? 'down' : 'up';
                    }
                }
                $i++;
            }
        }
        $minKline = $kline[0]->index;
        $maxKline = $kline[count($kline) - 1]->index;
        $cPrice[] = 0;
        foreach ($orderData as &$item) {
            if (isset($item['open_kindex'])) {
                $min = $item['open_kindex'] - $minKline;
            } else {
                $min = $item['close_kindex'] - $minKline;
            }
            for ($i = 0; $i < $min; $i++) {
                $item['data'][] = '"-"';
            }
            if (isset($item['open_price'])) $item['data'][] = $item['open_price'];
            if (isset($item['close_price'])) $item['data'][] = $item['close_price'];

            if (isset($item['open_price']) && isset($item['close_price'])) {
                while (count($cPrice) < $item['close_kindex'] - $minKline) {
                    $cPrice[] = end($cPrice);
                }
                $change = $item['open_price'] - $item['close_price'];
                $change = $item['open_is_buy'] ? $change * -1 : $change;
                $cPrice[] = end($cPrice) + $change;
            }
        }
        while (count($cPrice) < $maxKline - $minKline + 1) {
            $cPrice[] = end($cPrice);
        }
        // 交易数据生成完毕

        $data['orderData'] = $orderData;
        $data['cPrice'] = $cPrice;
        return view('ctp.kline', $data);
    });


    Route::get('order/{order?}', function($order = null) {
        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        list($data['list'], $totalPage, $data['pl']) = Order::getAll($page);
        $data['pre'] = $page == 1 ? 1 : $page - 1;
        $data['next'] = $page == $totalPage ? $page : $page + 1;
        // return $data;
        return view('ctp.order', $data);
    });

    Route::get('download', function() {
        return view('ctp.download', ['now' => date('Y-m-d H:i')]);
    });

    Route::post('download', function() {

        $type = $_POST['type'];
        $start = $_POST['startTime'];
        $end = $_POST['endTime'];
        $iID = $_POST['iID'];
        $range = isset($_POST['r']) ? $_POST['r'] : 0;

        if ($type == 'order') {

            $title = ['订单号', '系统单号', '合约', 'K线索引', '买卖', '开平', '订单类型', '报单时间', '最后成交时间/撤单时间', '报单价格', '成交价格', '报单手数', '未成交手数', '盈亏', '手续费', '系统响应耗时', '订单成交耗时', '详细状态'];
            list($list, $_) = Order::getAll(0, $start, $end, $iID, $range);
            array_unshift($list, $title);
        }

        if ($type == 'kline') {
            $title = ['ID', '合约', '幅值', 'K线索引', '开盘时间', '开盘毫秒', '收盘时间', '收盘毫秒', '开盘价', '收盘价', '最高价', '最低价', '成交量', '类型(1:阳/2:阴)', '更新时间'];
            $sql = "SELECT * FROM `kline` WHERE `open_time` >= '{$start}' AND `close_time` <= '{$end}'";
            if ($iID) $sql .= " AND `instrumnet_id` = '{$iID}' AND `range` = {$range}";
            $list = DB::connection('ctp_1')->select($sql);
            foreach ($list as &$item) {
                $item = array_values((array)$item);
            }
            array_unshift($list, $title);
        }

        if ($type == 'tick') {
            $title = ['ID', '合约', '时间', '毫秒', '最新价', '数量', '买一价', '买一量', '卖一价', '卖一量', '更新时间'];
            $sql = "SELECT * FROM `tick` WHERE `time` >= '{$start}' AND `time` <= '{$end}'";
            if ($iID) $sql .= " AND `instrumnet_id` = '{$iID}'";
            $list = DB::select($sql);
            foreach ($list as &$item) {
                $item = array_values((array)$item);
            }
            array_unshift($list, $title);
        }

        array_walk_recursive($list, function(&$item) {
            $item = iconv('utf8', 'gbk', $item);
        });

        $file = $type . "_" . str_replace([' ', ':', '-'], '', $start) . "_" . str_replace([' ', ':', '-'], '', $end);

        $filePath = '/home/dev/source/ctpWeb/public/runtime/' . $file . ".csv";
        $fp = fopen($filePath, 'w');
        foreach ($list as $fields) {
            // fputcsv($fp, $fields);
            fwrite($fp, implode(',', $fields) . PHP_EOL);
        }
        fclose($fp);
        header("Location: /runtime/{$file}.csv");
    });

});

Route::post('login', function() {

    if ($_POST['password'] == 301819) {
        $_SESSION['login'] = true;
        return redirect('order');
    } else {
        return redirect('/');
    }
});

Route::get('logout', function() {

    session_unset();

    return redirect('/');
});
