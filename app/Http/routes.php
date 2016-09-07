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

    Route::get('changeDB', function() {
        $db = $_GET['db'];
        $cmd = "cp /home/dev/source/ctpWeb/.env_{$db} /home/dev/source/ctpWeb/.env";
        system($cmd);
    });

    Route::get('config', function() {
        $res = parse_ini_file(dirname(__FILE__) . '/../../configTmp/s2_tmp.ini');
        $configs = [];
        foreach ($res as $key => $line) {
            $res[$key] = explode('/', $line);
        }

        $startTime = $res['start_trade_time'];
        $iIDs = [];
        foreach ($res['instrumnet_id'] as $iID) {
            $iIDs[] = $iID;
            $configs[$iID]['iID'] = $iID;
            $configs[$iID]['stopTime'] = $res["stop_trade_time_{$iID}"];
        }
        foreach ($res['min_range'] as $i => $item) {
            $configs[$iIDs[$i]]['minRange'] = $item;
        }
        foreach ($res['serial_kline'] as $i => $item) {
            $configs[$iIDs[$i]]['serialKline'] = $item;
        }
        foreach ($res['k_range'] as $i => $item) {
            $configs[$iIDs[$i]]['range'] = $item;
        }
        foreach ($res['peroid'] as $i => $item) {
            $configs[$iIDs[$i]]['peroid'] = $item;
        }
        foreach ($res['threshold_t'] as $i => $item) {
            $configs[$iIDs[$i]]['thresholdT'] = $item;
        }
        foreach ($res['threshold_v'] as $i => $item) {
            $configs[$iIDs[$i]]['thresholdV'] = $item;
        }
        // return $configs;
        $data['configs'] = $configs;
        $data['iIDs'] = $iIDs;
        $data['startTime'] = $startTime;
        return view('ctp.config', $data);
    });

    Route::post('config', function() {
        $_iIDs = $_POST['iIDs'];
        $_iIDs = explode(',', $_iIDs);
        $startTime = implode('/', $_POST['startTime']);
        $del = isset($_POST['del']) ? $_POST['del'] : [];

        $infos['instrumnet_id'] = [];
        $stops = [];
        foreach ($_iIDs as $id) {
            if (isset($_POST[$id]) && !in_array($id, $infos['instrumnet_id']) && !in_array($id, $del)) {
                $info = $_POST[$id];
                $infos['instrumnet_id'][] = $info['iID'];
                $infos['min_range'][] = $info['minRange'];
                $infos['serial_kline'][] = $info['serialKline'];
                $infos['k_range'][] = $info['range'];
                $infos['peroid'][] = $info['peroid'];
                $infos['threshold_t'][] = $info['thresholdT'];
                $infos['threshold_v'][] = $info['thresholdV'];
                $stops[$id] = implode('/', $info['stopTime']);
            }
        }

        $res = parse_ini_file(dirname(__FILE__) . '/../../configTmp/s2_tmp.ini');

        $res['start_trade_time'] = $startTime;
        $res['instrumnet_id'] = implode('/', $infos['instrumnet_id']);
        $res['min_range'] = implode('/', $infos['min_range']);
        $res['serial_kline'] = implode('/', $infos['serial_kline']);
        $res['k_range'] = implode('/', $infos['k_range']);
        $res['peroid'] = implode('/', $infos['peroid']);
        $res['threshold_t'] = implode('/', $infos['threshold_t']);
        $res['threshold_v'] = implode('/', $infos['threshold_v']);
        foreach ($stops as $iID => $item) {
            $res['stop_trade_time_' . $iID] = $item;
        }
        $sort = ['history_back', '', 'is_dev', '', 'rds_db_online', 'mysql_db_online', 'mysql_host', '', 'k_line_service_id', 'trade_logic_service_id', 'trade_strategy_service_id', 'trade_service_id', '', 'root_path', 'flow_path_t', 'flow_path_m', 'log_path', 'pid_path', '', 'market_broker_id', 'market_user_id', 'market_password', 'market_front', '', 'trade_broker_id', 'trade_user_id', 'trade_password', 'trade_front', '', 'instrumnet_id', 'k_range', 'min_range', 'serial_kline', 'peroid', 'threshold_t', 'threshold_v', 'trade_timeout_sec', '', 'start_trade_time'];
        $content = '';
        foreach ($sort as $key) {
            if ($key == '') {
                $content .= PHP_EOL;
                continue;
            }
            $content .= "{$key} = {$res[$key]}" . PHP_EOL;
            unset($res[$key]);
        }
        foreach ($res as $key => $value) {
            $content .= "{$key} = {$res[$key]}" . PHP_EOL;
        }

        file_put_contents(dirname(__FILE__) . '/../../configTmp/s2_tmp.ini', $content);
        return redirect('config');
    });

    Route::get('kline', function() {

        $start = isset($_GET['startTime']) ? $_GET['startTime'] : date('Y-m-d H:i', strtotime('-1 day'));
        $end = isset($_GET['endTime']) ? $_GET['endTime'] : date('Y-m-d H:i');
        $type = isset($_GET['type']) ? $_GET['type'] : 'l';
        $data['start'] = $start;
        $data['end'] = $end;

        $orderType = isset($_GET['order_type']) ? $_GET['order_type'] : 0;
        $data['order_type'] = $orderType;

        $iID = isset($_GET['iID']) ? $_GET['iID'] : 'sn1609';
        $length = isset($_GET['l']) ? $_GET['l'] : 50;
        $krange = isset($_GET['r']) ? $_GET['r'] : 80;
        $data['l'] = $length;
        $data['r'] = $krange;
        $data['iID'] = $iID;

        if ($type == 'l') {
            $sql = "SELECT * FROM `kline` WHERE `instrumnet_id` = '{$iID}' AND `range` = {$krange} ORDER BY id DESC LIMIT {$length}";
        } else {
            $sql = "SELECT * FROM `kline` WHERE `instrumnet_id` = '{$iID}' AND `range` = {$krange} AND `open_time` >= '{$start}' AND `close_time` <= '{$end}' ORDER BY id DESC";
        }
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
        $minTime = $kline[0]->open_time;
        $index[] = -1;

        $inSQL = implode(',', $index);
        $sql = "SELECT
            m.order_id, m.instrumnet_id, m.kindex, o.is_buy, o.is_open, m.is_forecast, m.is_zhuijia, o.srv_insert_time, o.srv_traded_time, o.start_time, o.start_usec, o.first_time, o.first_usec, o.end_time, o.end_usec, o.price, o.real_price, m.cancel_type, o.status, o.session_id, o.front_id, o.order_ref, o.cancel_tick_price
        FROM
            markov_kline_order as m,
            `order` as o
        WHERE
            m.order_id = o.order_id
            and m.instrumnet_id = o.instrumnet_id
            AND m.`instrumnet_id` = '{$iID}'
            AND m.`kindex` in ({$inSQL})
            AND m.`mtime` > '{$minTime}'
            AND m.`krange` = {$krange}
            AND o.status = 1";
        $order = DB::connection('ctp_1')->select($sql);

        if (count($order) == 0) {
            $data['error'] = 2;
            return view('ctp.kline', $data);
        }

        foreach ($order as $key => $item) {
            $order[$key]->isFC = false;
            if ($item->kindex == -1 && $item->status == 1) {
                $sql = "SELECT * FROM `kline` WHERE `instrumnet_id` = '{$item->instrumnet_id}' AND `range` = {$krange} AND `open_time` < '{$item->srv_traded_time}' ORDER BY `id` DESC LIMIT 1";
                $lastKline = DB::connection('ctp_1')->select($sql);
                $lastKline = isset($lastKline[0]) ? $lastKline[0] : null;
                if ($lastKline) {
                    $order[$key]->kindex = $lastKline->index;
                    $order[$key]->isFC = true;
                }

            }
        }

        // 交易盈亏
        $minKline = $kline[0]->index;
        $maxKline = $kline[count($kline) - 1]->index;
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
                $orderData[$i]['close_price'] = $item->real_price;// - Order::$commission[$item->instrumnet_id];
                $orderData[$i]['close_is_buy'] = $item->is_buy;
                if (isset($orderData[$i]['open_price'])) {
                    if ($orderData[$i]['open_kindex'] < $orderData[$i]['close_kindex']) {
                        if ($orderType == 0) {
                            $orderData[$i]['type'] = $orderData[$i]['close_is_buy'] ? 'down' : 'up';
                        } else {
                            if ($orderData[$i]['open_price'] > $orderData[$i]['close_price']) {
                                $orderData[$i]['type'] = $orderData[$i]['close_is_buy'] ? 'up' : 'down';
                            } else {
                                $orderData[$i]['type'] = $orderData[$i]['close_is_buy'] ? 'down' : 'up';
                            }
                        }
                    }
                }
                $i++;
            }
        }

        $cPrice[] = 0;
        foreach ($orderData as &$item) {
            if (isset($item['open_kindex'])) {
                $min = $item['open_kindex'] - $minKline;
            } else {
                $min = $item['close_kindex'] - $minKline;
            }
            $middle = 0;
            if (isset($item['open_kindex']) && isset($item['close_kindex'])) {
                $middle = $item['close_kindex'] - $item['open_kindex'];
            }
            for ($i = 0; $i < $min; $i++) {
                $item['data'][] = '"-"';
            }
            if (isset($item['open_price'])) $item['data'][] = $item['open_price'];
            for ($i=1; $i < $middle; $i++) {
                $item['data'][] = $item['open_price'] + $i * ($item['close_price'] - $item['open_price']) / $middle;
            }
            if (isset($item['close_price'])) $item['data'][] = $item['close_price'];

            if (isset($item['open_price']) && isset($item['close_price'])) {
                while (count($cPrice) <= $item['open_kindex'] - $minKline) {
                    $cPrice[] = end($cPrice);
                }
                $change = $item['open_price'] - $item['close_price'];
                $change = $item['open_is_buy'] ? $change * -1 : $change;
                $col = $item['close_kindex'] - $item['open_kindex'];
                $col = $col == 0 ? 1 : $col;
                $oneChange = $change / $col;
                for ($i=0; $i < $col; $i++) {
                    $cPrice[] = end($cPrice) + $oneChange;
                }

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
        list($data['list'], $totalPage, $data['pl']) = (new Order)->getAll($page);
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

            $title = ['订单号', '系统单号', '合约', 'K线索引', 'K线幅值', '买卖', '开平', '订单类型', '报单时间', '最后成交时间/撤单时间', '报单价格', '成交价格', '报单手数', '未成交手数', '盈亏', '手续费', '系统响应耗时', '订单成交耗时', '详细状态'];
            list($list, $_) = (new Order)->getAll(0, $start, $end, $iID, $range);
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

    Route::get('analysis', function() {
        $start = isset($_GET['startTime']) ? $_GET['startTime'] : date('Y-m-d H:i', strtotime('-1 day'));
        $end = isset($_GET['endTime']) ? $_GET['endTime'] : date('Y-m-d H:i');
        $all = isset($_GET['all']) ? $_GET['all'] : 0;
        $minStart = '2016-06-29 09:00';
        if (strtotime($start) < strtotime($minStart)) $start = $minStart;
        $data['start'] = $start;
        $data['end'] = $end;
        if ($all == 1) $start = $minStart;
        list($list) = (new Order)->getAll(0, $start, $end);
        $fList = [];
        $analysis = [];
        foreach ($list as $item) {
            if (!isset($analysis["{$item[2]}_{$item[4]}"])) $analysis["{$item[2]}_{$item[4]}"] = [];
            $tmp = [
                'iid' => $item[2],
                'kindex' => $item[3],
                'krange' => $item[4],
                'is_buy' => $item[5] == 'buy' ? true : false,
                'is_open' => $item[6] == 'kai' ? true : false,
                'price' => $item[10],
                'real_price' => $item[11],
                'status' => $item[18] == '撤单' ? 2 : 1,
            ];
            switch ($item[7]) {
                case '预测单':
                    $tmp['type'] = 0;
                    break;
                case '强平单':
                    $tmp['type'] = 3;
                    break;
                case '追价单':
                    $tmp['type'] = 2;
                    break;
                case '实时单':
                    $tmp['type'] = 1;
                    break;

                default:
                    # code...
                    break;
            }
            $fList[] = $tmp;
        }
        $isOpened = [];
        $openItem = [];
        foreach ($fList as $item) {
            $iid = "{$item['iid']}_{$item['krange']}";
            if ($analysis[$iid] === []) {
                $analysis[$iid]['total'] = 0;
                $analysis[$iid]['type_0'] = 0;
                $analysis[$iid]['type_1'] = 0;
                $analysis[$iid]['type_2'] = 0;
                $analysis[$iid]['type_3'] = 0;
                $analysis[$iid]['group'] = 0;
                $analysis[$iid]['forecast_open'] = 0;
                $analysis[$iid]['forecast_open_ok'] = 0;
                $analysis[$iid]['real_open'] = 0;
                $analysis[$iid]['real_open_ok'] = 0;
                $analysis[$iid]['forecast_close'] = 0;
                $analysis[$iid]['forecast_close_ok'] = 0;
                $analysis[$iid]['real_close'] = 0;
                $analysis[$iid]['real_close_ok'] = 0;
                $analysis[$iid]['totalPrice'] = 0;
                $openItem[$iid] = [];
            }
            // 总下单
            $analysis[$iid]['total']++;
            // 各类下单
            $analysis[$iid]['type_' . $item['type']]++;
            // 平开组合
            if ($item['status'] == 1) {
                if ($item['is_open']) {
                    $isOpened[$iid] = true;
                    $openItem[$iid] = $item;
                }
                else {
                    if (isset($isOpened[$iid]) && $isOpened[$iid]) {
                        $analysis[$iid]['group']++;
                        $isOpened[$iid] = false;
                        $p = $item['real_price'] - $openItem[$iid]['real_price'];
                        $p = $item['is_buy'] ? -$p : $p;
                        $analysis[$iid]['totalPrice'] += $p * Order::$priceRadio[$item['iid']] - Order::$commission[$item['iid']];
                    }
                }
            }
            // 每根K线操作，用于计算详细
            $analysis[$iid]['kindex'][$item['kindex']][] = $item;
        }
        foreach ($analysis as $iid => $item) {
            foreach ($item['kindex'] as $key => $detail) {
                // 当前操作是否是方向正确的
                $is_right = false;
                $is_double_open = false;
                if (count($detail) > 2) $is_right = true;
                else if (count($detail) == 2) {
                    if ($detail[0]['is_open'] && $detail[1]['is_open']) {
                        $is_right = true;
                        $is_double_open = true;
                    }
                    else if ($detail[0]['status'] == 1 || $detail[1]['status'] == 1) $is_right = true;
                } else {
                    if ($detail[0]['is_open']) $is_right = true;
                    else if ($detail[0]['status'] == 1) $is_right = true;
                }
                if ($is_right) {
                    foreach ($detail as $one) {
                        if ($one['is_open'] && $one['type'] == 0) {
                            if ($is_double_open) {
                                $is_double_open = false;
                            } else {
                                $analysis[$iid]['forecast_open']++;
                            }
                            if ($one['status'] == 1) $analysis[$iid]['forecast_open_ok']++;
                        }
                        if ($one['is_open'] && $one['type'] != 0) {
                            $analysis[$iid]['real_open']++;
                            if ($one['status'] == 1) $analysis[$iid]['real_open_ok']++;
                        }
                        if (!$one['is_open'] && $one['type'] == 0) {
                            $analysis[$iid]['forecast_close']++;
                            if ($one['status'] == 1) $analysis[$iid]['forecast_close_ok']++;
                        }
                        if (!$one['is_open'] && $one['type'] != 0) {
                            $analysis[$iid]['real_close']++;
                            if ($one['status'] == 1) $analysis[$iid]['real_close_ok']++;
                        }
                    }
                }
                unset($analysis[$iid]['kindex']);
            }
        }
        $fAnalysis = [];
        foreach ($analysis as $iid => $line) {
            $tmp = [];
            $tmp[] = $iid;
            $tmp[] = $line['total'];
            $tmp[] = $line['group'];
            $tmp[] = $line['totalPrice'];
            $tmp[] = $line['group'] > 0 ?  number_format($line['totalPrice'] / $line['group'], 2) : '-';
            $tmp[] = $line['type_0'];
            $tmp[] = $line['type_1'];
            $tmp[] = $line['type_2'];
            $tmp[] = $line['type_3'];
            $p = $line['forecast_open'] > 0 ? number_format($line['forecast_open_ok'] / $line['forecast_open'], 3) * 100 . "%" : "-";
            $tmp[] = "{$line['forecast_open']} / {$line['forecast_open_ok']} / {$p}";
            $p = $line['real_open'] > 0 ? number_format($line['real_open_ok'] / $line['real_open'], 3) * 100 . "%" : "-";
            $tmp[] = "{$line['real_open']} / {$line['real_open_ok']} / {$p}";
            $p = $line['forecast_close'] > 0 ? number_format($line['forecast_close_ok'] / $line['forecast_close'], 3) * 100 . "%" : "-";
            $tmp[] = "{$line['forecast_close']} / {$line['forecast_close_ok']} / {$p}";
            $p = $line['real_close'] > 0 ? number_format($line['real_close_ok'] / $line['real_close'], 3) * 100 . "%" : "-";
            $tmp[] = "{$line['real_close']} / {$line['real_close_ok']} / {$p}";
            $fAnalysis[$iid] = $tmp;
        }
        ksort($fAnalysis);
        $data['list'] = $fAnalysis;
        return view('ctp.analysis', $data);
    });

    Route::get('tick', function() {
        $iid = $_GET['iid'];
        $end = time();
        $start = $end - 1;
        $start = date('Y-m-d H:i:s', $start);
        $end = date('Y-m-d H:i:s', $end);
        $start = '2016-06-30 14:59:58';
        $sql = "SELECT * FROM `tick` WHERE `time` >= '{$start}' AND `time` <= '{$end}' AND `instrumnet_id` = '{$iid}'";
        $list = DB::select($sql);
        return $list;
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
