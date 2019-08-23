<?php
/**
 * 分销控制器
 * User: donghuaibin
 * Date: 2019-08-08
 * Time: 21:08
 */
namespace Mobile\Controller;

class DistributeController extends MobileBaseController
{

    protected $user;
    protected $user_id;

    public function __construct()
    {
        parent::__construct();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id = {$user['user_id']}")->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            if (!$this->user_id) {
                header("location:" . U('Mobile/User/login'));
                exit;
            }
            if ( ! $this->user['is_distribut']) {
                $this->error('请先绑定手机成为代理', U('Mobile/User/distribute')); exit;
            }
            $this->assign('user', $user); //存储用户信息
        }
    }

    public function index()
    {
        $today_commission = M('account_log')
            ->where(['user_id' => $this->user_id, 'user_money' => ['gt', 0]])
            ->sum('user_money');
        $this->assign('today_commission', $today_commission ? : "0.00");
        $this->display();
    }

    public function agent_list()
    {
        $today = strtotime(date('Y-m-d'));
        $sql = <<<sql
select a.*, b.invite_num from __PREFIX__users a RIGHT JOIN 
(
select count(*) as invite_num, first_leader  from __PREFIX__users 
WHERE is_distribut = 1 and first_leader <> 0 and reg_time > {$today} 
 group by first_leader
) b on a.user_id = b.first_leader order by b.invite_num desc limit 0, 10
sql;
        $lists = M()->query($sql);
        $this->assign('lists', $lists);
        $this->display();
    }

    public function group()
    {
        $groups = M('users')->where(['first_leader' => $this->user_id, 'is_distribut' => 1])->order('reg_time desc')->select();
        $this->assign('groups', $groups);
        $this->display();
    }

    public function rank()
    {
        $sql = <<<sql
select a.*, b.today_money as today_money from __PREFIX__users a 
right join 
(select user_id, sum(user_money) as today_money 
 from __PREFIX__account_log  
 where user_money > 0
 GROUP BY user_id) b on a.user_id = b.user_id
order by b.today_money desc limit 0, 10
sql;
        $ranks = M()->query($sql);
        $this->assign('ranks', $ranks);
        $this->display();
    }

    public function invite()
    {
        //获取代理的推广注册二维码
        $url = U('/Mobile/User/distribute', ["first_leader" =>  $this->user_id], true, true);
        $invite_token = md5($url);
        $path = "Public/upload/agent/{$this->user_id}/";
        $filename = "{$invite_token}.png";
        $image = $path . $filename;
        if ( ! file_exists($path . $filename)) {
            if (!is_dir($path)) {
                mkdir($path,0777,true);
            }
            //生成代理邀请用户注册
            //获取当前用户的此商品分享token

            $user_qrcode = $path . $filename;
            if (! file_exists($user_qrcode)) {
                vendor('phpqrcode.phpqrcode');
                \QRcode::png($url, "./" . $user_qrcode, QR_ECLEVEL_H, 3, 2);
            }
        }
        //若存在，则直接返回
        $this->assign('invite_image', "/" . $image);
        $this->display();
    }

    public function store_invite()
    {
        if ( ! $this->user['menter_control']) {
            $this->error('未开通此权限');
            exit;
        }
        //获取代理的推广注册二维码
        $url = U('/Mobile/User/menter', ["first_leader" =>  $this->user_id], true, true);
        $invite_token = md5($url);
        $path = "Public/upload/agent/{$this->user_id}/";
        $filename = "{$invite_token}.png";
        $image = $path . $filename;
        if ( ! file_exists($path . $filename)) {
            if (!is_dir($path)) {
                mkdir($path,0777,true);
            }
            //生成代理邀请用户注册
            //获取当前用户的此商品分享token

            $user_qrcode = $path . $filename;
            if (! file_exists($user_qrcode)) {
                vendor('phpqrcode.phpqrcode');
                \QRcode::png($url, "./" . $user_qrcode, QR_ECLEVEL_H, 3, 2);
            }
        }
        //若存在，则直接返回
        $this->assign('invite_image', "/" . $image);
        $this->display();
    }
}
