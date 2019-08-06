<?php
/**
 * 地图显示
 */
namespace Mobile\Controller;

class MapController extends MobileBaseController
{
    public function byStore()
    {
        $shop_id = I('shop_id');
        $shop = M('StoreShops')->where(['id' => $shop_id])->find();

        $this->assign('shop', $shop);
        $this->display('index');
    }
}
