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
 * $Author: 当燃 2016-01-09
 */
namespace Mobile\Controller;

use Mobile\Model\StoreModel;

class IndexController extends MobileBaseController {

    public function index(){
        /*
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;
            // 微信Jssdk 操作类 用分享朋友圈 JS
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();
            print_r($signPackage);
        */
        $how_scope = [
            'is_hot' => 1,
            'is_on_sale' => 1
        ];
        $favourite_scope = [
            'is_recommend' => 1,
            'is_on_sale' => 1
        ];
        $goods_flash_where = '';
        $goods_where = '';
        if (AREA_ID || PROVINCE_ID) {
            $district_where = [];
            if (PROVINCE_ID) {
                $district_where['province_id'] = PROVINCE_ID;
            }
            if (AREA_ID) {
                $district_where['district_id'] = AREA_ID;
            }
            $shops = M('store_shops')->where($district_where)->getField('id', true);
            if (!empty($shops)) {
                $goods_ids = M('goods_shop')->where(['shop_id' => ['in', $shops]])->getField('goods_id', true);
                if (!empty($goods_ids)) {
                    $how_scope['goods_id'] = ['in', $goods_ids];
                    $favourite_scope['goods_id'] = ['in', $goods_ids];
                    $goods_flash_where = " and tp_goods.goods_id in (".implode(",", $goods_ids).")";
                    $goods_where = " and goods_id in (".implode(",", $goods_ids).")";
                }
            }
        }

        $hot_goods = M('goods')->where($how_scope)->order('goods_id DESC')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();//首页热卖商品
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();
        $this->assign('thems',$thems);
        $this->assign('hot_goods',$hot_goods);

        $favourite_goods = M('goods')->where($favourite_scope)->order('goods_id DESC')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        $provinces = M('region')->where(['is_open' => 1, 'parent_id' => 0])->select();

        //获取最新闪购和即将下架闪购
        $thread =48 * 60 * 60;
        $new_flash_goods = M('flash_sale')
            ->join('__GOODS__ on __GOODS__.prom_id = __FLASH_SALE__.id')
            ->where("__GOODS__.is_on_sale=1 and __GOODS__.prom_type =2 {$goods_flash_where} and " . time()." >= start_time and ".time()." <= start_time + {$thread} ")
            ->limit(15)
            ->select();
        $this->assign('new_flash_goods', $new_flash_goods);
        $last_flash_goods = M('flash_sale')
            ->join('__GOODS__ on __GOODS__.prom_id = __FLASH_SALE__.id')
            ->where("__GOODS__.is_on_sale=1 and __GOODS__.prom_type =2 {$goods_flash_where} and " . time()." <= end_time and ".time()." >= end_time - {$thread} ")
            ->limit(15)
            ->select();
        $this->assign('last_flash_goods', $last_flash_goods);

        $now = time();
        $group_buy = <<<sql
select * from `__PREFIX__.group_buy` where start_time < {$now} and end_time > {$now} {$goods_where}
sql;



        //获取区县
        $now_region = M('region')->where(['id' => PROVINCE_ID, 'is_open' => 1])->find();
        $areas = [];
        if ($now_region) {
            if (preg_match("/[天津|北京|重庆|上海]/", $now_region['name'])) {
                $subs = M('region')->where(['parent_id' => $now_region['id'], 'is_open' => 1])->getField('id', true);
                if ($subs) {
                    $areas = M('region')->where(['parent_id' => ['in', $subs], 'is_open' => 1])->select();
                }
            } else {
                $areas = M('region')->where(['parent_id' => $now_region['id'], 'is_open' => 1])->select();
            }
        }
        $this->assign('areas', $areas);
        $this->assign('websites_provinces', $provinces);
        $this->display();
    }


    public function selectProvince()
    {
        $province_id = I('province_id');
        $this->setSelectProvinceCookie($province_id);
        redirect(U('Index/index'));
    }

    public function selectArea()
    {
        $province_id = I('area_id');
        $this->setSelectAreaCookie($province_id);
        redirect(U('Index/index'));
    }

    /**
     * 分类列表显示
     */
    public function categoryList(){
        $this->display();
    }

    /**
     * 模板列表
     */
    public function mobanlist(){
        $arr = glob("D:/wamp/www/svn_tpshop/mobile--html/*.html");
        foreach($arr as $key => $val)
        {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_tpshop/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";
        }
    }

    /**
     * 商品列表页
     */
    public function goodsList(){
        $goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类
        $id = I('get.id',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);
        $this->display();
    }

    public function ajaxGetMore(){
    	$p = I('p',1);
    	$favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1  and goods_state = 1 ")->order('sort DESC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
    	$this->assign('favourite_goods',$favourite_goods);
    	$this->display();
    }

    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/15
     */
    public function street()
    {
        $store_class = M('store_class')->where('')->select();
        $this->assign('store_class', $store_class);//店铺分类
        $this->display();
    }

    /**
     * ajax 获取店铺街
     */
    public function ajaxStreetList()
    {
        $p = I('p',1);
        $sc_id = I('get.sc_id','');
        $store_list = D('store')->getStreetList($sc_id,$p,10);
        foreach($store_list as $key=>$value){
            $store_list[$key]['goods_array'] = D('store')->getStoreGoods($value['store_id'],4);
        }
        $this->assign('store_list',$store_list);
        $this->display();
    }

    /**
     * 品牌街
     * @author dyr
     * @time 2016/08/15
     */
    public function brand()
    {
        $brand_model = M('brand');
        $brand_where['status'] = 0;
        $brand_class = $brand_model->field('cat_name')->group('cat_name')->order(array('sort'=>'desc','id'=>'asc'))->where($brand_where)->select();
        $brand_list = $brand_model->field('id,name,logo,url')->order(array('sort'=>'desc','id'=>'asc'))->where($brand_where)->select();
        $brand_count = count($brand_list);
        for ($i = 0; $i < $brand_count; $i++) {
            if (($i + 1) % 4 == 0) {
                $brand_list[$i]['brandLink'] = 'brandRightLink';
            } else {
                $brand_list[$i]['brandLink'] = 'brandLink';
            }
        }
        $this->assign('brand_list', $brand_list);//品牌列表
        $this->assign('brand_class', $brand_class);//品牌分类
        $this->display();
    }
}
