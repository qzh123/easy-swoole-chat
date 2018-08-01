<?php
namespace App\Socket\Controller\WebSocket;

use EasySwoole\Core\Socket\AbstractInterface\WebSocketController;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;

use App\Socket\Logic\Room;
use PhpParser\Node\Expr\Empty_;

class Index extends WebSocketController
{

    /**
     * 访问找不到的action
     * @param  ?string $actionName 找不到的name名
     * @return string
     */
    public function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found！！！！！！");
    }

    public function index()
    {
    }

    /**
     * 登录
     */
    public function login(){
        $param = $this->request()->getArg('data');
        $account = $param['account']??'';
        $passwd = $param['passwd']??'';
        $info = ['account'=>$account,'passwd'=>$passwd];
        $userId = Room::getUserIdByAccount($account);
        $fd = $this->client()->getFd();
        if (!$userId){
            //ServerManager::getInstance()->getServer()->push($fd, $this->format('sys','账号不存在'));
            //暂时先不弄注册，没有直接创建
            Room::addUser($info);
            $userId = Room::getUserIdByAccount($account);
            Room::login($userId,$fd);
            $this->response()->write($this->format(200,['msgType'=>'login','msg'=>'登录成功（已自动为您注册账号）']));
        }else{
            //判断是否已经登录过了
            $fdArr = Room::getUserFd($userId);
            if (count($fdArr) > 0){
                $this->response()->write($this->format(10000,['msgType'=>'login','msg'=>'您已经登录，同一账号请勿重复登录']));
            }else{
                //根据用户id获取密码
                $userInfo = Room::getUser($userId,['account','passwd']);
                if ($passwd != $userInfo['passwd']){
                    $this->response()->write($this->format(10000,['msgType'=>'login','msg'=>'密码错误']));
                }else{
                    Room::login($userId,$fd);
                    $fdArr = Room::getUserFd($userId);
                    $this->response()->write($this->format(200,['msgType'=>'login','msg'=>'登录成功']));
                }
            }
        }
    }


    /**
     * 进入房间
     */
    public function intoRoom()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');

        $account = $param['account'];
        $roomId = $param['roomId'];
        $userFd = $this->client()->getFd();
        Room::joinRoom($roomId, $userFd);
        $roomArr = Room::selectRoomFd($roomId);
        $member = count($roomArr);
        $data = ['msgType'=>'joinRoom','msg'=>$account . '加入了' . $roomId.'号聊天室','roomId'=>$roomId,'count'=>$member];
        $userId = Room::getUserIdByAccount($account);
        //获取用户参与的房间信息，并更新
        $roomInfo = Room::getUser($userId,['rooms']);
        $roomInfo = $roomInfo['rooms'];
        if (empty($roomInfo)){
            $roomInfo = $roomInfo.$roomId;
        }else{
            $roomArr = explode(',',$roomInfo);
            if (!in_array($roomId,$roomArr)){
                $roomInfo = $roomInfo.','.$roomId;
            }
        }
        Room::setUser($userId, ['rooms'=>$roomInfo]);
        //异步推送
        $message = $this->format(200,$data);
        TaskManager::async(function ()use($roomId, $message, $userFd){
            $list = Room::selectRoomFd($roomId);
            foreach ($list as $fd) {
                if ($fd != $userFd){
                    ServerManager::getInstance()->getServer()->push($fd, $message);
                }
            }
        });
        $this->response()->write($message);
    }

    /**
     * 发送信息到房间
     */
    public function sendToRoom()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $from = $param['from']??'';
        $message = $param['message']??'';
        $roomId = $param['roomId'];
        $userFd = $this->client()->getFd();
        $message = $this->format(200,['msgType'=>'roomChat','msg'=>$message,'from'=>$from,'roomId'=>$roomId]);
        //异步推送
        TaskManager::async(function ()use($roomId, $message, $userFd){
            $list = Room::selectRoomFd($roomId);
            foreach ($list as $fd) {
                if ($fd != $userFd) {
                    ServerManager::getInstance()->getServer()->push($fd, $message);
                }
            }
        });
        $this->response()->write($message);
    }

    /**
     * 发送私聊
     */
    public function sendToUser()
    {
        // TODO: 业务逻辑自行实现
        $param = $this->request()->getArg('data');
        $message = $param['message'];
        $account = $param['from'];
        $to = $param['to'];
        $userId = Room::getUserIdByAccount($account);
        $userFd = $this->client()->getFd();
        $message = $this->format(200,['msgType'=>'roomChat','msg'=>$message,'from'=>$account,'to'=>$to]);
        //异步推送
        TaskManager::async(function ()use($userId, $message, $userFd){
            $fdList = Room::getUserFd($userId);
            foreach ($fdList as $fd) {
                if ($fd != $userFd) {
                    ServerManager::getInstance()->getServer()->push($fd, $message);
                }
            }
        });
        $this->response()->write($message);
    }

    function hello()
    {
        $this->response()->write('call hello with arg:'.$this->request()->getArg('content'));

    }

    public function who(){
        $this->response()->write('your fd is '.$this->client()->getFd());
    }

    function delay()
    {
        $this->response()->write('this is delay action');
        $client = $this->client();
        //测试异步推送
        TaskManager::async(function ()use($client){
            sleep(1);
            Response::response($client,'this is async task res'.time());
        });
    }
}