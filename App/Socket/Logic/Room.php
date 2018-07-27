<?php
namespace App\Socket\Logic;

use EasySwoole\Core\Component\Di;


class Room
{
    /**
     * 获取Redis连接实例
     * @return object Redis
     */
    public static function getRedis()
    {
        return Di::getInstance()->get('REDIS')->handler();
    }

    public static function testSet()
    {
        return self::getRedis()->set('test', '这是一个测试');
    }
    public static function testGet()
    {
        return self::getRedis()->get('test');
    }

    /**
     * 添加用户
     * @param array $info
     * @return mixed
     */
    public static function addUser(array $info)
    {
        $uid = self::getRedis()->incr('userid');
        $info['uid'] = $uid;
        self::getRedis()->hMset('user:'.$uid, $info);
        self::getRedis()->hset('account.to.id',$info['account'],$uid);
        return $uid;

    }


    public static function getUserIdByAccount(string $account)
    {
        return self::getRedis()->hget('account.to.id',$account);
    }

    public static function getUser(int $userId, array $fields)
    {
        return self::getRedis()->hMget('user:'.$userId,$fields);
    }

    /**
     * 添加用户
     * @param int $uid
     * @param string $info
     * @return mixed
     */
    public static function setUser(int $uid, string $info)
    {
        return self::getRedis()->hMset('user:'.$uid, $info);
    }

    /**
     * 进入房间
     * @param  int    $roomId 房间id
     * @param  int    $fd     连接id
     * @return null
     */
    public static function joinRoom(int $roomId, int $fd)
    {
        $userId = self::getUserId($fd);
        self::getRedis()->zAdd('rfMap', $roomId, $fd);
        self::getRedis()->hSet("room:{$roomId}", $fd, $userId);
    }

    /**
     * 登录
     * @param  int    $userId 用户id
     * @param  int    $fd     连接id
     * @return bool
     */
    public static function login(int $userId, int $fd)
    {
        return self::getRedis()->zAdd('online', $userId, $fd);
    }

    /**
     * 获取用户id
     * @param  int    $fd
     * @return int    userId
     */
    public static function getUserId(int $fd)
    {
        return self::getRedis()->zScore('online', $fd);
    }

    /**
     * 获取用户fd
     * @param  int    $userId
     * @return array         用户fd集
     */
    public static function getUserFd(int $userId)
    {
        return self::getRedis()->zRange('online', $userId, $userId, true);
    }

    /**
     * 获取RoomId
     * @param  int    $fd
     * @return int    RoomId
     */
    public static function getRoomId(int $fd)
    {
        return self::getRedis()->zScore('rfMap', $fd);
    }

    /**
     * 获取room中全部fd
     * @param  int    $roomId roomId
     * @return array         房间中fd
     */
    public static function selectRoomFd(int $roomId)
    {
        return self::getRedis()->hKeys("room:{$roomId}");
    }

    /**
     * 退出room
     * @param  int    $roomId roomId
     * @param  int    $fd     fd
     * @return
     */
     public static function exitRoom(int $roomId, int $fd)
     {
         self::getRedis()->hDel("room:{$roomId}", $fd);
         self::getRedis()->zRem('rfMap', $fd);
     }

    /**
     * 关闭连接
     * @param  string $fd 链接id
     */
    public static function close(int $fd)
    {
        $roomId = self::getRoomId($fd);
        self::exitRoom($roomId, $fd);
        self::getRedis()->zRem('online', $fd);
    }
}
