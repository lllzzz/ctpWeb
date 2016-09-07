<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    //
    private static $db = 'ctp_1';

    public static $commission = [
        'sn1609' => 3.9,
        'hc1610' => 6,
        'SR701' => 3.81,
        'SR609' => 3.81,
        'zn1609' => 3.1,
        'cu1608' => 9.5,
        'ag1612' => 8.5,
    ];

    public static $priceRadio = [
        'sn1609' => 1,
        'hc1610' => 10,
        'SR701' => 10,
        'zn1609' => 5,
        'SR609' => 3.81,
        'cu1608' => 5,
        'ag1612' => 15,
    ];

    public function getAll($page = 1, $start = null, $end = null, $iID = null, $range = null)
    {
        if (empty($start)) {
            $today = date('Y-m-d', time());
            $yestoday = date('Y-m-d', strtotime('-1 day'));
            $time = intval(date('Hi', time()));
            if ($time >= 0 && $time <= 859) {
                $start = $yestoday . " 20:59";
            }
            if ($time > 859 && $time <= 1329) {
                $start = $today . " 08:59";
            }
            if ($time > 1329 && $time <= 2059) {
                $start = $today . " 13:29";
            }
            if ($time > 2059 && $time <= 2359) {
                $start = $today . " 20:59";
            }
        }
        if (empty($end)) {
            $end = date('Y-m-d H:i', time());
        }
        $start = $start;
        $end = $end;

        $sql = "SELECT
            m.order_id, m.instrumnet_id, m.kindex, m.krange, o.is_buy, o.is_open, m.is_forecast, m.is_zhuijia, o.srv_insert_time, o.srv_traded_time, o.start_time, o.start_usec, o.first_time, o.first_usec, o.end_time, o.end_usec, o.price, o.real_price, m.cancel_type, o.status, o.session_id, o.front_id, o.order_ref, o.cancel_tick_price, o.srv_end_time
        FROM
            markov_kline_order as m,
            `order` as o
        WHERE
            m.order_id = o.order_id
            and o.start_time > '{$start}'
            and o.start_time < '{$end}'";

        if ($page == 0) {
            $sql .= " and o.status in (1, 2)";
        } else {
            $sql .= " and o.status in (1, 2, 0)";
        }

        if ($iID) {
            $sql .= " and o.instrumnet_id = '{$iID}'";
        }

        if ($range) {
            $sql .= " and m.krange = {$range}";
        }

        $res = DB::connection(self::$db)->select($sql);

        $report = [];
        $no = 1;
        // 初步处理
        foreach ($res as $line) {

            $tmp = [];
            $tmp[] = $line->order_id;
            $tmp[] = "{$line->front_id}:{$line->session_id}:{$line->order_ref}";
            $tmp[] = $line->instrumnet_id;
            $tmp[] = $line->kindex;
            $tmp[] = $line->krange;
            $tmp[] = $line->is_buy ? 'buy' : 'sell';
            $tmp[] = $line->is_open ? 'kai' : 'ping';
            $tmp[] = $line->is_forecast ? '预测单' : ($line->is_zhuijia ? '追价单' : ($line->kindex == -1 ? '强平单' : '实时单'));
            $tmp[] = $line->srv_insert_time;
            $tmp[] = $line->srv_traded_time == '0000-00-00 00:00:00' ? $line->srv_end_time : $line->srv_traded_time;
            $tmp[] = $line->price;
            $tmp[] = $line->real_price == 0 ? $line->cancel_tick_price : $line->real_price;
            $tmp[] = $line->status == 1 ? 1 : 0;
            $tmp[] = $line->status != 1 ? 1 : 0;
            if ($line->status == 1) {
                if ($line->is_open) {
                    $isOpened[$line->instrumnet_id] = true;
                    $openItem[$line->instrumnet_id] = $line;
                    $tmp[] = 0;
                    $tmp[] = 0;
                }
                else if (isset($isOpened[$line->instrumnet_id])){
                    $p = $line->real_price - $openItem[$line->instrumnet_id]->real_price;
                    $p = $line->is_buy ? -$p : $p;
                    $p = $p * self::$priceRadio[$line->instrumnet_id];
                    $p -= self::$commission[$line->instrumnet_id];
                    $totalPrice[$line->instrumnet_id] = isset($totalPrice[$line->instrumnet_id]) ? $totalPrice[$line->instrumnet_id] + $p : $p;
                    $tmp[] = $p;
                    $tmp[] = self::$commission[$line->instrumnet_id];
                } else {
                    $tmp[] = 0;
                    $tmp[] = 0;
                }
            } else {
                $tmp[] = 0;
                $tmp[] = 0;
            }

            // if ($line->is_open && $line->status == 1) {
            //     $openPrice[$iid] = $line->real_price;
            // }
            // if (!$line->is_open && $line->status == 1) {
            //     $p = $line->real_price - $openPrice[$line->instrumnet_id];
            //     if ($line->is_buy) $p *= -1;
            //     $p = $p * self::$priceRadio[$line->instrumnet_id];
            //     $p = $p - self::$commission[$line->instrumnet_id];
            //     $tmp[] = $p;
            //     $totalPrice[$line->instrumnet_id] = isset($totalPrice[$line->instrumnet_id]) ? $totalPrice[$line->instrumnet_id] + $p : $p;
            //     $tmp[] = self::$commission[$line->instrumnet_id];
            //     $openPrice[$line->instrumnet_id] = 0;
            // } else {
            //     $tmp[] = 0;
            //     $tmp[] = 0;
            // }

            $startTime = strtotime($line->start_time) * 1000000 + $line->start_usec;
            $firstTime = strtotime($line->first_time) * 1000000 + $line->first_usec;
            $endTime = strtotime($line->end_time) * 1000000 + $line->end_usec;

            $tmp[] = ($firstTime - $startTime)/1000;
            $tmp[] = ($endTime - $startTime)/1000;

            switch ($line->status) {
                case 1:
                    $tmp[] = '全部成交';
                    break;

                case 2:
                    $tmp[] = '撤单';
                    break;

                default:
                    $tmp[] = '未知';
                    break;
            }
            $report[] = $tmp;
        }

        $total = count($report);
        $totalPage = ceil($total / 40);
        $profitorLoss = ['total' => 0];
        if ($page > 0) {
            $report = array_reverse($report);
            $start = ($page - 1) * 40;
            $unkown = [];
            foreach ($report as $key => $item) {
                $profitorLoss['total'] = isset($profitorLoss['total']) ? $profitorLoss['total'] + $item[14] : $item[14];
                $profitorLoss[$item[2]] = isset($profitorLoss[$item[2]]) ? $profitorLoss[$item[2]] + $item[14] : $item[14];
                if ($item[count($item) - 1] == '未知') {
                    $item[count($item) - 2] = '-';
                    $unkown[] = $item;
                    unset($report[$key]);
                }
            }
            $report = array_merge($unkown, $report);
            $report = array_slice($report, $start, 40);
        }

        return [$report, $totalPage, $profitorLoss];
    }

}
