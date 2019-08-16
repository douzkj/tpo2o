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
        $this->display();
    }

    public function agent_list()
    {
        $this->display();
    }

    public function group()
    {
        $this->display();
    }

    public function rank()
    {
        $this->display();
    }

    public function invite()
    {
        //获取代理的推广注册二维码
        $invite_token = md5("invite-". $this->user_id);
        $path = "Public/upload/agent/{$this->user_id}/";
        $filename = "{$invite_token}.png";
        $image = $path . $filename;
        if ( ! file_exists($path . $filename)) {
            if (!is_dir($path)) {
                mkdir($path,0777,true);
            }
            //生成代理邀请用户注册
            //获取当前用户的此商品分享token
            $url = U('/Mobile/User/login', ["first_leader" =>  $this->user_id], true, true);
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
