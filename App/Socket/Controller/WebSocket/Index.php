<?php
namespace App\Socket\Controller\WebSocket;

use EasySwoole\Core\Socket\AbstractInterface\WebSocketController;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;

use App\Socket\Logic\Room;

class Test extends WebSocketController
{

    /**
     * 访问找不到的action
     * @param  ?string $actionName 找不到的name名
     * @return string
     */
    public function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found");
    }

    public function index()
    {
    }

    /**
     * 进入房间
     */
    public function intoRoom()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $userId = $param['userId'];
        $roomId = $param['roomId'];

        $fd = $this->client()->getFd();
        Room::login($userId, $fd);
        Room::joinRoom($roomId, $fd);
        $this->response()->write("加入{$roomId}房间");
    }

    /**
     * 发送信息到房间
     */
    public function sendToRoom()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $message = $param['message'];
        $roomId = $param['roomId'];

        //异步推送
        TaskManager::async(function ()use($roomId, $message){
            $list = Room::selectRoomFd($roomId);
            foreach ($list as $fd) {
                ServerManager::getInstance()->getServer()->push($fd, $message);
            }
        });
    }

    /**
     * 发送私聊
     */
    public function sendToUser()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $message = $param['message'];
        $userId = $param['userId'];

        //异步推送
        TaskManager::async(function ()use($userId, $message){
            $fdList = Room::getUserFd($userId);
            foreach ($fdList as $fd) {
                ServerManager::getInstance()->getServer()->push($fd, $message);
            }
        });
    }
}