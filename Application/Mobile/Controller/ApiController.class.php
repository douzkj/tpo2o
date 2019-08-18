<?php
/**
 * 工具api.
 * User: donghuaibin
 * Date: 2019-07-30
 * Time: 15:54
 */
namespace Mobile\Controller;


use Think\Image;

class ApiController extends MobileBaseController {
    use ApiTrait;

    public function setLngLat()
    {
        setcookie('longitude',$_REQUEST['longitude'],null,'/');
        setcookie('latitude',$_REQUEST['latitude'],null,'/');
    }

    public function upload()
    {
        if(IS_POST){
//            $data = I('post.');
            $res = [];
            $flag = false;
            foreach($_FILES as $k=>$v){
                if(!empty($v['tmp_name'])){
                    $flag = true;
                }
            }
            if($flag){
                $upload = new \Think\Upload();//实例化上传类
                $upload->maxSize   =  1024*1024*30;//设置附件上传大小 管理员10M  否则 3M
                $upload->exts      =  array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->savePath  =  'store/cert/'; // 设置附件上传根目录
                $upload->replace   =  true; //存在同名文件是否是覆盖，默认为false
                $upinfo  =  $upload->upload($_FILES);
                if(!$upinfo) {
                    $this->errorResponse($upload->getError());//上传错误提示错误信息
                }else{
                    foreach($upinfo as $key => $val)
                    {
                        $res[$key] = '/Public/upload/'.$val['savepath'].$val['savename'];
                    }
                }
            }
            $this->successResponse($res, '提交成功');
        }
    }

    public function goodsShare()
    {
        $data = $_REQUEST;
        $user = session('user');
        if (!$user) {
            $this->errorResponse('用户未登录', 401);
        }
        if (!$user['is_distribut']) {
            $this->errorResponse('请先成为代理', 403);
        }
        if ( ! isset($data['goods_id'])) {
            $this->errorResponse('参数有误');
        }
        //找出对应的商品poster
        $goods = M('goods')->where(['goods_id' => $data['goods_id'], 'is_on_sale' =>1, 'goods_state' => 1])->find();
        if (!$goods) {
            $this->errorResponse('商品状态已下架');
        }
        $poster = M('store')->where(['store_id' => $goods['store_id']])->getField('poster');
        if (!$poster) {
            $this->errorResponse('此商家未配置海报');
        }
        $hash = md5($poster);
        $image = new \Think\Image();
        $original_img = "." . $poster;
        $image->open($original_img);
        $path = "Public/upload/poster/share/{$hash}/";
        $is_group = $data['is_group'];
        $target = $is_group ? "/Mobile/Activity/group" : '/Mobile/Goods/goodsInfo';
        $url = U($target, ["id" => $data['goods_id'], 'first_leader' => $user['user_id']], true, true);
        $filename =  md5($url).$image->type();
        if (file_exists($path . $filename)) {
            //若存在，则直接返回
            $this->successResponse(['path' => "/" . $path . $filename]);
        }
        if (!is_dir($path)) {
            mkdir($path,0777,true);
        }
        //生成代理分享二维码
        //获取当前用户的此商品分享token
        $user_qrcode = $path . "user_code_". $user['user_id'] . ".png";
        if (! file_exists($user_qrcode)) {
            vendor('phpqrcode.phpqrcode');
            \QRcode::png($url, "./" . $user_qrcode, QR_ECLEVEL_L, 3, 2);
        }
        $share_image = "./" . $path . $filename;
        $image->open($original_img)->water("./".$user_qrcode, Image::IMAGE_WATER_SOUTHWEST, 80)->save($share_image);
        $this->successResponse([
            'path' => $share_image,
            'fullUrl' => U($share_image, [],true, true)
        ]);

    }
}
