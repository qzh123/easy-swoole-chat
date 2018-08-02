<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/4/3
 * Time: 下午1:49
 */

namespace EasySwoole\Core\Socket\AbstractInterface;

use EasySwoole\Core\Component\Spl\SplStream;
use EasySwoole\Core\Socket\Client\WebSocket;
use EasySwoole\Core\Socket\Common\CommandBean;

abstract class WebSocketController extends Controller
{
    private $client;
    function __construct(WebSocket $client,CommandBean $request,SplStream $response)
    {
        $this->client = $client;
        parent::__construct( $request, $response);
    }

    protected function client():WebSocket
    {
        // TODO: Implement client() method.
        return $this->client;
    }

    public function format(int $status, array $data){
        return json_encode(['status'=>$status,'data'=>$data],JSON_UNESCAPED_UNICODE);
    }
}