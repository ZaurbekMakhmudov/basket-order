<?php


namespace App\BasketOrderBundle\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * Class TokenService
 * @package App\BasketOrderBundle\Service
 */
class TokenService
{
    const SZ_KEY_PREFIX = 'SZ_';

    const TOKEN_KEYS = 'TOKEN_KEYS';

    private ChainAdapter $cache;

    public function __construct(string $redisUrl)
    {
        $this->cache = new ChainAdapter([
            new RedisAdapter(RedisAdapter::createConnection($redisUrl)),
        ]);
    }

    /**
     * @return array
     */
    public function getTokenKeysFromCache() {

        return $this->getTokenDataFromCache($this::SZ_KEY_PREFIX . $this::TOKEN_KEYS);
    }

    /**
     * @param $tokenKey
     * @param bool $doDeleteKey
     * @return bool
     */
    public function setTokenKeysToCache($tokenKey, $doDeleteKey = false) {

        $tokenKeys = $this->getTokenKeysFromCache();
        if($doDeleteKey) {
            if(isset($tokenKeys[$tokenKey])) {
                unset($tokenKeys[$tokenKey]);
            }
        } else {
            $tokenKeys[$tokenKey] = $tokenKey;
        }
        return $this->setTokenDataToCache($this::SZ_KEY_PREFIX . $this::TOKEN_KEYS, $tokenKeys);
    }

    /**
     * @param $token
     * @param bool $isPermanentAccount
     * @return mixed
     */
    public function getTokenDataFromCacheByToken($token, $isPermanentAccount = false) {
        $tokenKeys = $this->getTokenKeysFromCache();
        if(is_array($tokenKeys)) {
            foreach ($tokenKeys as $tokenKey) {
                if ($tokenData = $this->getTokenDataFromCache($tokenKey)) {
                    if ($token == $tokenData['data']['token']) {

                        return $tokenData;
                    }
                }
            }
        }

        if($isPermanentAccount) {
            if($username = $this->getUsernameByToken($token)) {

                return $this->createToken($username, 0, $token);
            }
        }

        return false;
    }

    /**
     * @param $username
     * @return string
     */
    private function getTokenKey($username) {
        return $this::SZ_KEY_PREFIX . $username;
    }

    /**
     * @param $username
     * @param int $expire
     * @param bool $permanentToken
     * @return array|false
     */
    public function createToken($username, $expire = 0, $permanentToken = false) {
        $tokenKey = $this->getTokenKey($username);
        $tokenData = $this->makeTokenData($username, $expire, $permanentToken);
        if($this->setTokenDataToCache($tokenKey, $tokenData, $expire)) {
            $this->setTokenKeysToCache($tokenKey);
            return $tokenData;
        }
        return false;
    }

    /**
     * @param $tokenKey
     * @param $tokenData
     * @param $expire
     * @return bool
     */
    private function setTokenDataToCache($tokenKey, $tokenData, $expire = 0) {
        try {
            $item = $this->getItemByKey($tokenKey);
            $item->set($tokenData);
            if($expire > 0) {
                $item->expiresAfter($expire);
            }
            return $this->cache->save($item);
        } catch (\Exception $e) {
            echo $e;
        }
    }

    /**
     * @param $tokenKey
     * @return mixed
     */
    private function getTokenDataFromCache($tokenKey) {

        return $this->getItemByKey($tokenKey)->get();
    }

    /**
     * @param $tokenKey
     * @return CacheItem
     */
    private function getItemByKey($tokenKey) {

        return $this->cache->getItem($tokenKey);
    }

    /**
     * @param $username
     * @param $expire
     * @param bool $permanentToken
     * @return array
     */
    private function makeTokenData($username, $expire, $permanentToken = false) {
        $tokenData = [];
        $tokenData['username'] = $username;
        if($permanentToken) {
            $tokenData['data']['token'] = $permanentToken;
        } else {
            $tokenData['data']['token'] = bin2hex(random_bytes(32));
        }
        if($expire) {
            $tokenData['data']['expiresAt'] = date('Y-m-d\TH:i:sP', time() + $expire);
        }
        return $tokenData;
    }

    /**
     * @param $username
     * @return false|mixed
     */
    public function getTokenDataByUsername($username)
    {
        $tokenKey = $this->getTokenKey($username);
        if ($tokenData = $this->getTokenDataFromCache($tokenKey)) {

            return $tokenData;
        } else {

            return false;
        }
    }

    /**
     * @param $token
     * @return false|string
     */
    private function getUsernameByToken($token)
    {
        foreach ($_ENV['users'] as $username => $userdata) {
            if($token == $userdata['password']) {

                return $username;
            }
        }

        return false;
    }

    /**
     * @param $username
     * @return bool
     */
    public function clearCachedData($username) {
        $tokenKey = $this->getTokenKey($username);
        if($deleted =  $this->cache->deleteItem($tokenKey) ) {
            $this->setTokenKeysToCache($tokenKey, true);
        }

        return $deleted;
    }

}