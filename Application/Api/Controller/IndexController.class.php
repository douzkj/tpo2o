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
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace Api\Controller;
use Api\Model\StoreModel;
use Think\Controller;
use Home\Logic\StoreLogic;
class IndexController extends BaseController {

    public function index(){
        $this->display();
    }

     /*
     * 获取首页数据
     */
    public function home(){
        //获取轮播图
        $data = M('ad')->where('pid = 2')->field(array('ad_link','ad_name','ad_code'))->cache(true,TPSHOP_CACHE_TIME)->select();
        //广告地址转换
        foreach($data as $k=>$v){
            if(!strstr($v['ad_link'],'http'))
                $data[$k]['ad_link'] = SITE_URL.$v['ad_link'];
            $data[$k]['ad_code'] = SITE_URL.$v['ad_code'];

        }
        //获取大分类
//        $category_arr = M('goods_category')->where('id in(4,5,7)')->field('id,name')->limit(3)->cache(true,TPSHOP_CACHE_TIME)->select();
        $promotion_goods = D('Goods')->getPromotionGoods();
        $high_quality_goods = D('Goods')->getHighQualityGoods();
        $new_goods = D('Goods')->getNewGoods();
        $hot_goods = D('Goods')->getHotGood();
        $result = array(
            array('name'=>'促销商品','goods_list'=>$promotion_goods),
            array('name'=>'精品推荐','goods_list'=>$high_quality_goods),
            array('name'=>'新品上市','goods_list'=>$new_goods),
            array('name'=>'热销商品','goods_list'=>$hot_goods),
        );
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>array('goods'=>$result,'ad'=>$data))));
    }

    /**
     * 获取首页数据
     */
    public function homePage(){
        $promotion_goods = D('Goods')->getPromotionGoods();
        $high_quality_goods = D('Goods')->getHighQualityGoods();
        $new_goods = D('Goods')->getNewGoods();
        $hot_goods = D('Goods')->getHotGood();
        $adv =  D('Goods')->getHomeAdv();
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>array(
                'promotion_goods'=>$promotion_goods,
                'high_quality_goods'=>$high_quality_goods,
                'new_goods'=>$new_goods,
                'hot_goods'=>$hot_goods,
                'ad'=>$adv
            ),
        );
        exit(json_encode($json));
    }

    /**
     * 猜你喜欢
     */

    public function favourite()
    {
        $p = I('p',1);
        $goods_where = array('is_recommend'=>1,'is_on_sale'=>1,'goods_state'=>1);
        $favourite_goods = M('goods')
            ->field('goods_id,goods_name,shop_price')
            ->where($goods_where)
            ->order('sort DESC')
            ->page($p,10)
            ->cache(true,TPSHOP_CACHE_TIME)
            ->select();
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>array(
                'favourite_goods'=>$favourite_goods,
            ),
        );
        exit(json_encode($json));
    }

    /**
     * 获取服务器配置
     */
    public function getConfig()
    {
        $config_arr = M('config')->select();
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$config_arr)));
    }
    /**
     * 获取插件信息
     */
    public function getPluginConfig()
    {
        $data = M('plugin')->where("type='payment' OR type='login'")->select();
        $arr = array();
        foreach($data as $k=>$v){
            unset( $data[$k]['config']);
        
			if(!$v['config_value']){
				$data[$k]['config_value'] = "";
			}else{
				$data[$k]['config_value'] = unserialize($v['config_value']);
			}
			
            if($data[$k]['type'] == 'payment'){
                $arr['payment'][] =  $data[$k];
            }
            if($data[$k]['type'] == 'login'){
                $arr['login'][] =  $data[$k];
            }
        }
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$arr ? $arr : '')));
    }

    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/15
     */
    public function storeStreet()
    {
        $sc_id = I('get.sc_id', '');
        $store_class = M('store_class')->where('')->select();//店铺分类
        $p = I('p',1);
        $store_list = D('store')->getStreetList($sc_id,$p,10);//获取店铺列表
        //遍历获取店铺的四个商品数据
        foreach ($store_list as $key => $value) {
            $goodsList = D('store')->getStoreGoods($value['store_id'], 4);
            $store_list[$key]['cartList'] = $goodsList['goods_list'];
            $store_list[$key]['store_count'] = $goodsList['goods_count'];
        }
        $result = array('store_list' => $store_list, 'store_class' => $store_class);
        exit(json_encode(array('status' => 1, 'msg' => 'success', 'result' => $result)));
    }

    /**
     * 店铺分类
     */
    public function storeClass()
    {
        $store_class = M('store_class')->where('')->select();
        exit(json_encode(array('status' => 1, 'msg' => 'success', 'result' => $store_class)));
    }

    /**
     * 品牌街
     * @author dyr
     * @time 2016/08/15
     */
    public function brandStreet()
    {
        $brand_model = M('brand');
        $brand_where['status'] = 0;
        //品牌分类
        $brand_list = $brand_model->field('id,name,logo,url')->order(array('sort' => 'desc', 'id' => 'asc'))->where($brand_where)->limit("1, 20")->select();
        exit(json_encode(array('status' => 1, 'msg' => 'success', 'result' => $brand_list)));
    }

}