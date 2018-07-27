<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/1/9
 * Time: 下午1:04
 */

namespace EasySwoole;

use \EasySwoole\Core\AbstractInterface\EventInterface;
use \EasySwoole\Core\Swoole\ServerManager;
use \EasySwoole\Core\Swoole\EventRegister;
use \EasySwoole\Core\Http\Request;
use \EasySwoole\Core\Http\Response;
// 引入EventHelper
use \EasySwoole\Core\Swoole\EventHelper;
// 引入Di
use \EasySwoole\Core\Component\Di;
// 注意这里是指额外引入我们上文实现的解析器
use \App\Socket\Parser\WebSocket;
// 引入上文Redis连接
use \App\Utility\Redis;
// 引入上文Room文件
use \App\Socket\Logic\Room;

Class EasySwooleEvent implements EventInterface {

    public static function frameInitialize(): void
    {
        // TODO: Implement frameInitialize() method.
        date_default_timezone_set('Asia/Shanghai');
    }

    public static function mainServerCreate(ServerManager $server,EventRegister $register): void
    {
        // 注册WebSocket解析器
        EventHelper::registerDefaultOnMessage($register, WebSocket::class);
        //注册onClose事件
        $register->add($register::onClose, function (\swoole_server $server, $fd, $reactorId) {
            //清除Redis fd的全部关联
            var_dump($fd.'========'.$reactorId);
            Room::close($fd);
            $userId = Room::getUserId($fd);
            $userInfo = Room::getUser($userId,['account']);
            $account = $userInfo['account']??'';
            $data = json_encode(['msgType'=>'disconnect','msg'=>$account.'退出群聊'],JSON_UNESCAPED_UNICODE);
            foreach ($server->connections as $client) {
                if($fd != $client){
                    $server->send($client, $data);
                }
            }
        });
        // 注册Redis
        Di::getInstance()->set('REDIS', new Redis(Config::getInstance()->getConf('REDIS')));
    }

    public static function onRequest(Request $request,Response $response): void
    {
        // TODO: Implement onRequest() method.
    }

    public static function afterAction(Request $request,Response $response): void
    {
        // TODO: Implement afterAction() method.
    }

    
}