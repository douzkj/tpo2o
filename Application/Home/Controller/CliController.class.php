<?php
/**
 * 定时控制访问.
 * User: donghuaibin
 * Date: 2019-08-13
 * Time: 21:32
 */
namespace Home\Controller;

use Think\Controller;

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
        $group_orders = M('group_order')->where('group_status = 0 and close_at < ' .time() )->select();
        foreach (array_chunk($group_orders, 50) as $group_chunk) {
            foreach ($group_chunk as $group_order) {
                M('group_order')->where(['id' => $group_order['id']])->save([
                    'group_status' => 2
                ]);
                M('order')->where(['group_order_id' => $group_order['id']])->save([
                    'group_status' => 2,
                    'order_status' => 3
                ]);
            }
        }
    }
}
