<?php
/**
 * Api服务trait.
 * User: donghuaibin
 * Date: 2019-07-30
 * Time: 15:57
 */
namespace Home\Controller;


trait ApiTrait
{

    public function _empty()
    {
        $this->errorResponse('not found', 404);
    }

    public function successResponse($data = [], $msg = 'success')
    {
        $this->allowAccess();
        $this->ajaxReturn([
            'code' => 200,
            'message' => $msg,
            'data' => $data
        ]);
    }

    public function allowAccess()
    {
        header("Access-Control-Request-Method:GET,POST");
        header("Access-Control-Allow-Credentials:true");
        $origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
        $allow_origin = array(
            'http://www.ebb.com',
            'http://www.weixiu.local'
        );
        if(in_array($origin, $allow_origin)){
            header('Access-Control-Allow-Origin:'.$origin);
        }
//        header('Access-Control-Allow-Origin:http://www.ebb.com');
    }

    public function errorResponse($msg = '', $code = 400)
    {
        $this->allowAccess();
        $this->ajaxReturn([
            'code' => $code,
            'message' => $msg,
            'data' => []
        ]);
    }
}

