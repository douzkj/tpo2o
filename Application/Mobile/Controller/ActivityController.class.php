<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: 当燃   2016-05-10
 */
namespace Mobile\Controller;

class ActivityController extends MobileBaseController {
    public function index(){
        $this->display();
    }

   /**
    * 拼团详情页
    */
    public function group(){
        //form表单提交
        C('TOKEN_ON',true);
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $goods_id = I("get.id",66);

        $group_buy_info = M('GroupBuy')->where("goods_id = $goods_id and ".time()." >= start_time and ".time()." <= end_time ")->find(); // 找出这个商品
        if(empty($group_buy_info))
        {
            $this->error("此商品的拼团活动不存在或已结束",U('Mobile/Goods/goodsInfo',array('id'=>$goods_id)));
        }

        $goods = M('Goods')->where("goods_id = $goods_id")->find();
        $goods_images_list = M('GoodsImages')->where("goods_id = $goods_id")->select(); // 商品 图册

        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id = $goods_id")->select(); // 查询商品属性表

        $Model = new \Think\Model();
        // 商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('SpecGoodsPrice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        if($keys)
        {
             $specImage =  M('SpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
             $keys = str_replace('_',',',$keys);
             $sql  = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
             $filter_spec2 = $Model->query($sql);
             foreach($filter_spec2 as $key => $val)
             {
                 $filter_spec[$val['name']][] = array(
                     'item_id'=> $val['id'],
                     'item'=> $val['item'],
                     'src'=>$specImage[$val['id']],
                     );
             }
        }
        $spec_goods_price  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        M('Goods')->where("goods_id=$goods_id")->save(array('click_count'=>$goods['click_count']+1 )); // 统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $shop_ids = M('goods_shop')->where(['goods_id' => $goods_id])->getField('shop_id', true);
        if ($shop_ids) {
            $shops = M('store_shops')
                ->where(['id' => ['in', $shop_ids], 'store_id' => $goods['store_id']])
                ->order('sort desc, created_at desc')
                ->select();
        }
        $this->assign('shops', $shops);
        if($goods['store_id']>0){
            $store = M('store')->where(array('store_id'=>$goods['store_id']))->find();
            $this->assign('store',$store);
        }
        $group_order_sn = I('group_order_sn');
        if ($group_order_sn) {
            $group_order = M('group_order')->where(['group_order_sn' => $group_order_sn])->find();
            if ($group_order) {
                $group_order['user'] = M('users')->where("user_id = {$group_order['user_id']}")->find();
            }
            if ($group_order['close_at'] < time()) {
                //拼团已失效
                $group_order['group_status'] = 2;
            }
            $this->assign('group_order', $group_order);
        }
        //获取附近在售
        $nearbyGoods = $goodsLogic->getGoodsNearby(3);
        $this->assign('nearbyGoods', $nearbyGoods);
        $this->assign('group_buy_info',$group_buy_info);
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $this->assign('commentStatistics',$commentStatistics);
        $this->assign('goods_attribute',$goods_attribute);
        $this->assign('goods_attr_list',$goods_attr_list);
        $this->assign('filter_spec',$filter_spec);
        $this->assign('goods_images_list',$goods_images_list);
        $this->assign('goods',$goods);
        $this->display();
    }


    /**
     * 团购活动列表
     */
    public function group_list()
    {
    	$count =  M('GroupBuy')->where(time()." >= start_time and ".time()." <= end_time ")->count();// 查询满足要求的总记录数
    	$Page = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数
    	$show = $Page->show();// 分页显示输出
    	$this->assign('page',$show);// 赋值分页输出
        $list = M('GroupBuy')->where(time()." >= start_time and ".time()." <= end_time ")->limit($Page->firstRow.','.$Page->listRows)->select(); // 找出这个商品
        $this->assign('list', $list);
        $this->display();
    }

    public function discount_list(){
    	$prom_list = M('prom_goods')->where("end_time>".time())->select();
    	$this->assign('prom_list', $prom_list);
    	$this->display();
    }

    public function discount_goods_list(){
    	$prom_list = M('prom_goods')->where("end_time>".time())->select();
    	$this->assign('prom_list', $prom_list);
    	$this->display();
    }
}
