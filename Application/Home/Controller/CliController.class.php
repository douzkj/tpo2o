<?php
/**
 * 定时控制访问.
 * User: donghuaibin
 * Date: 2019-08-13
 * Time: 21:32
 */
namespace Home\Controller;

use Think\Controller;
use Think\Log;

class CliController extends Controller
{
    use ApiTrait;

    public function __construct()
    {
        parent::__construct();
        $token = I('cli_token');
        if ($token != C('CLI_TOKEN')) {
            $this->errorResponse('token缺失');
        }
    }

    /**
     * 拼团订单到期关闭
     */
    public function groupOrderRunner()
    {
        $now = time();
        $group_orders = M('group_order')->where('group_status = 0 and close_at <= ' .$now )->select();
        foreach (array_chunk($group_orders, 50) as $group_chunk) {
            foreach ($group_chunk as $group_order) {
                echo "拼团订单【{$group_order['group_order_sn']}】退款中 \r\n";
                $this->closeGroupOrder($group_order, $now);
                echo "拼团订单【{$group_order['group_order_sn']}】退款成功 \r\n";
            }
        }
    }

    protected function closeGroupOrder($group_order, $time)
    {
        M()->startTrans();
        try {
            $res1 = M('group_order')->where([
                'id' => $group_order['id'],
                'group_status' => 0,
                'close_at' => ['elt', $time]
            ])->save([
                'group_status' => 2
            ]);
            $orders = M('order')->lock(true)->where([
                'pay_status' => 1,
                'group_order_id' => $group_order['id'],
                'group_status' => 0
            ])->select();
            foreach ($orders as $order) {
                M('order')->where(['order_id' => $order['order_id']])->save([
                    'group_status' => 2,
                    'order_status' => 3
                ]);
                accountLog($order['user_id'], $order['total_amount'], 0, "拼团订单【{$order['order_sn']}】退回金额【{$order['total_amount']}】");
                echo "订单【{$order['order_sn']}】退回成功 \r\n";
            }
            if ($res1) {
                M()->commit();
            } else {
                M()->rollback();
                echo "拼团订单【{$group_order['group_order_sn']}】无退回 \r\n";
            }
        } catch (\Exception $exception) {
            M()->rollback();
            echo "拼团订单【{$group_order['group_order_sn']}】拼团自动结束更新错误:" . $exception->getMessage() . "\r\n";
        }
    }
}
