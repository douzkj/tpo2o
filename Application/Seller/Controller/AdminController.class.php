<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2016-05-09
 */

namespace Seller\Controller;
use Admin\Logic\StoreLogic;
use Think\Verify;

class AdminController extends BaseController {

    public function index(){
    	$res = $list = array();
    	$keywords = I('keywords');
    	if(empty($keywords)){
    		$res = D('seller')->where("store_id=".STORE_ID)->select();
    	}else{
    		$res = D()->query("select * from __PREFIX__seller where store_id=".STORE_ID." and seller_name like '%$keywords%' order by seller_id");
    	}
    	$group = D('seller_group')->getField('group_id,group_name');

    	if($res && $group){
    		foreach ($res as $val){
    			$val['role'] =  $group[$val['group_id']];
    			$val['enabled'] = $val['enabled']>0 ? '启用' : '停用';
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
        $this->display();
    }

    public function admin_info(){
    	$seller_id = I('get.seller_id');
    	if($seller_id>0){
    		$info = D('seller')->where("seller_id=$seller_id")->find();
    		if($info){
    			$user = M('users')->where("user_id=".$info['user_id'])->find();
    		}
    		$info['user_name'] = empty($user['mobile']) ? $user['email'] : $user['mobile'];
    		$this->assign('info',$info);
    		$this->assign('seller', session('seller'));
    	}
    	$role = D('seller_group')->where('1=1')->select();
    	$shops = M('store_shops')->where(['store_id' => STORE_ID])->select();
    	$this->assign('role',$role);
    	$this->assign('shops',$shops);
    	$this->display();
    }

    public function adminHandle(){
    	$data = I('post.');

    	if($data['act'] == 'del' && $data['seller_id']>0){
    		//删除店铺管理员
    		$manage = M('seller')->where(array('seller_id'=>$data['seller_id']))->find();
    		if($manage['store_id'] == STORE_ID){
    			$r = M('seller')->where('seller_id='.$data['seller_id'])->delete();
    			sellerLog('删除店铺管理员'.$manage['seller_name']);
    		}else{
    			exit(json_encode(0));//只能删除本店的管理员
    		}
    		exit(json_encode(1));
    	}

    	if($data['seller_id']>0){
    		$seller = session('seller');//修改密码
    		if(($data['seller_id'] == $seller['seller_id']) || $seller['is_admin']){
    		    $update = [
                    'shop_id' => $data['shop_id'],
                    'group_id' => $data['group_id'],
                    'enabled' => $data['enabled']
                ];
    		    if (!empty($data['password'] && !empty($data['password2']))) {
                    if($data['password'] == $data['password2']){
                        $this->error("两次密码一致",U('admin/admin_info',array('seller_id'=>$seller['seller_id'])));
                    }else{
                        if (M('seller')->where(['seller_id' => $seller['seller_id'], 'password' => encrypt($data['password'])])->count() > 0) {
                            $update['password'] = encrypt($data['password2']);
//                            $r = M('users')->where(array('user_id'=>$seller['user_id']))->save(array('password'=>encrypt($data['password2'])));
//                        if(M('users')->where(array('user_id'=>$seller['user_id'],'password'=>encrypt($data['password'])))->count()>0){
//                            $r = M('users')->where(array('user_id'=>$seller['user_id']))->save(array('password'=>encrypt($data['password2'])));
                        }else{
                            $this->error("原密码错误",U('admin/admin_info',array('seller_id'=>$seller['seller_id'])));
                        }
                    }
                }
                $r = M('seller')->where(['seller_id' => $data['seller_id']])->save($update);
    		}else{
    			$this->error("非法操作",U('Index/welcome'));//只能修改自己的密码
    		}
    	}else{
    		//验证商家后台登陆账号是否有同名
    		if(M('seller')->where("seller_name='".$data['seller_name']."'")->count()){
    			$this->error("此登陆账号名已被注册，请更换",U('Admin/admin_info'));
    		}
    		$uname = check_email($data['user_name']) ? 'email' : 'mobile';

    		//查找验证绑定用户
    		$userinfo = M('users')->where("$uname ='".$data['user_name']."'")->find();
    		if(empty($userinfo)){
    			$this->error("请先注册前台会员",U('Admin/admin_info'));
    		} else{
    			if(M('seller')->where("user_id=".$userinfo['user_id'])->count()){
    				$this->error("该用户已经添加过店铺管理员",U('Admin/admin_info'));
    			}
//    			$data['password'] = encrypt($data['password']);
    			$data['user_id'] = $userinfo['user_id'];
    			$data['store_id'] = STORE_ID;
    			$data['add_time'] = time();
    			unset($data['seller_id']);
    			$r = M('seller')->add($data);
    		}
    	}
    	if($r){
    		$this->success("操作成功",U('Admin/index'));
    	}else{
    		$this->error("操作失败",U('Admin/index'));
    	}
    }


    /*
     * 管理员登陆
     */
    public function login(){
        if(session('?seller_id') && session('seller_id')>0){
             $this->error("您已登录",U('Index/index'));
        }

        if(IS_POST){
            $verify = new Verify();
            if (!$verify->check(I('post.vertify'), "seller_login")) {
            	exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
            }
            $seller_name = I('post.username');
            $password = I('post.password');
            if(!empty($seller_name) && !empty($password)){
				$seller = M('seller')->where(array('seller_name'=>$seller_name, "password" => encrypt($password)))->find();
				if($seller){
//					$user = M('users')->where("user_id=".$seller['user_id']." and password='".encrypt($password)."'")->find();
//					if($user){
						if($seller['is_admin'] == 0 && $seller['enabled'] == 1){
							exit(json_encode(array('status'=>0,'msg'=>'该账户还没启用激活')));
						}
						if($seller['group_id'] > 0){
							 $group = M('seller_group')->where(array('group_id'=>$seller['group_id']))->find();
							 $seller['act_limits'] = $group['act_limits'];
							 $seller['smt_limits'] = $group['smt_limits'];
						}
						session('seller',$seller);
						session('seller_id',$seller['seller_id']);
						session('store_id',$seller['store_id']);
						M('seller')->where(array('seller_id'=>$seller['seller_id']))->save(array('last_login_time'=>time()));
						sellerLog('商家管理中心登录',__ACTION__);
						$url = session('from_url') ? session('from_url') : U('Index/index');
						exit(json_encode(array('status'=>1,'url'=>$url)));
//					}else{
//						exit(json_encode(array('status'=>0,'msg'=>'账号密码不正确')));
//					}
				}else{
					exit(json_encode(array('status'=>0,'msg'=>'账号或密码错误')));
				}
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'请填写账号密码')));
            }
        }
        $this->display();
    }

    /**
     * 退出登陆
     */
    public function logout(){
        session_unset();
        session_destroy();
        $this->success("退出成功",U('Seller/Admin/login'));
    }

    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        	'reset' => false
        );
        $Verify = new Verify($config);
        $Verify->entry("seller_login");
    }

    public function role(){
    	$list = D('seller_group')->order('group_id desc')->select();
    	$this->assign('list',$list);
    	$this->display();
    }

    public function role_info(){
    	$role_id = I('get.group_id');
    	$tree = $detail = array();
    	if($role_id){
    		$detail = M('seller_group')->where("group_id=$role_id")->find();
    		$this->assign('detail',$detail);
    	}
    	$menu_list2 = getMenuList();
    	$this->assign('menu_list',$menu_list2);
    	$smt_list = M('store_msg_tpl')->select();
    	$this->assign('smt_list', $smt_list);
    	$this->display();
    }

    public function roleSave(){
    	$data = I('post.');
    	$data['act_limits'] = is_array($data['act_limits']) ? implode(',', $data['act_limits']) : '';
    	$data['smt_limits'] = is_array($data['smt_limits']) ? implode(',', $data['smt_limits']) : '';
    	if(empty($data['group_id'])){
    		$data['store_id'] = STORE_ID;
    		$r = M('seller_group')->add($data);
    	}else{
    		$r = M('seller_group')->where('group_id='.$data['group_id'])->save($data);
    	}
		if($r){
			sellerLog('管理角色',__ACTION__);
			$this->success("操作成功!",U('Admin/role'));
		}else{
			$this->success("操作失败!",U('Admin/role'));
		}
    }

    public function roleDel(){
    	$role_id = I('post.group_id');
    	$admin = D('admin')->where('group_id='.$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = M('admin_role')->where("group_id=$role_id")->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }

    public function log(){
    	$Log = M('');
		$db_prefix = C('DB_PREFIX');
    	$p = I('p',1);
		$admin_id = session('admin_id');
		if($admin_id){
		    $logs = $Log->table($db_prefix.'admin_log as al')->join($db_prefix.'admin as a ON a.admin_id =al.admin_id')->where('a.admin_id ='.$admin_id)->order('log_time DESC')->page($p.',20')->select();
		    $this->assign('list',$logs);
		    $count = $Log->table($db_prefix.'admin_log as al')->join($db_prefix.'admin as a ON a.admin_id =al.admin_id')->where('a.admin_id ='.$admin_id)->count();
		    $Page = new \Think\Page($count,20);
		    $show = $Page->show();
		}
    	$this->assign('page',$show);
    	$this->display();
    }

    /**
     *  商家登录后 处理相关操作
     */
     public function login_task()
     {

        // 多少天后自动分销记录自动分成
         if(file_exists(APP_PATH.'Common/Logic/DistributLogic.class.php')){
            $distributLogic = new \Common\Logic\DistributLogic();
            $distributLogic->auto_confirm(STORE_ID); // 自动确认分成
         }

        // 商家结算
        $storeLogic = new StoreLogic();
        $storeLogic->auto_transfer(STORE_ID); // 自动结算

     }

	/**
	 * 清空系统缓存
	 */
	public function cleanCache()
    {
        delFile('./Public/upload/goods/thumb');// 删除缩略图
        $html_arr = glob("./Application/Runtime/Html/*.html");
        foreach ($html_arr as $key => $val) {
            // 删除详情页
            if (strstr($val, 'Home_Goods_goodsInfo') || strstr($val, 'Home_Goods_ajaxComment') || strstr($val, 'Home_Goods_ajax_consult'))
                unlink($val);
        }
        $this->success("清除成功!!!");
    }
}

