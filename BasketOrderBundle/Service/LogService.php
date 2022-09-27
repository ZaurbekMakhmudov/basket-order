<?php

namespace App\BasketOrderBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class LogService
{
    private LoggerInterface $logger;
    private string $username;


    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
        if($this->user)
            $this->username = $this->user->getUsername();
        else
            $this->username = 'none';
    }

    /**
     *
     * @param LoggerInterface $logger - Логгер для основного канала
     * @return void
     */
    public function setVars(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    /**
     * @param $path - Путь к методу в котором вызван метод
     * @param $out - Контент необходимый записать в логи
     * @param $customType - Необязательный параметр, нужен на случай если необходимо обозначить какой-то свой тип данных
     * @param $request - Поле на случай, если необходимо в лог добавить Request
     * @return void
     */
    public function create($path, $out, $customType = null, $request = null, $isForward = false)
    {

        $outData = $this->getOut($out, $isForward);
        $path = $this->getPath($path);
        if($out instanceof Request || !is_null($request)){
            $path = $this->getPath($path, 'request');
            if ($outData['content'] === 'null')
                $outData['content'] = "";
        }
        if($out instanceof Response)
            $this->logger->notice(vsprintf("%s %s %s", [$outData['type'], $outData['content'], $this->initLogInfo()]));
        elseif(isset($outData['method']))
            $this->logger->notice(vsprintf("%s: %s %s %s %s", [$outData['type'], $outData['method'], $path, $outData['content'], $this->initLogInfo()]));
        else
            $this->logger->notice(vsprintf("%s: %s %s %s", [$outData['type'], $path, $outData['content'], $this->initLogInfo()]));



    }


    public function  getPath($path, $type = 'null')
    {
        if($type == 'request')
            return str_replace('_', '/', $path);
        if(is_array($path))
            return $this->preparePath($path);
        else
            return $path;
    }

    public function preparePath($path): string
    {
        return vsprintf("%s::%s line:%s", [str_replace('/', '\\', str_replace('/var/www/html/', '',str_replace('.php', '', $path['file']))), $path['function'], $path['line']]);
    }


    public function getOut($out, $isForward = false): array
    {

        if($out instanceof Response && !$isForward)
            return ['content' => json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Response[' . $out->getStatusCode() . ']'];
        elseif ($out instanceof Response && $isForward) {
            return ['content' => json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Forward Response[' . $out->getStatusCode() . ']'];
        }
        elseif (is_array($out))
            return ['content' => json_encode($out, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Method'];
        elseif (is_string($out))
            return ['content' => $out, 'type' => 'Method'];
        elseif (is_integer($out))
            return ['content' => $out, 'type' => 'Integer'];
        elseif ($out instanceof Request && !$isForward){
            if(json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) == null)
                return ['content' => json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Request', 'method' => $out->getMethod()];
            return ['content' => json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Request',  'method' => $out->getMethod()];
        } elseif ($out instanceof Request && $isForward){
            if(json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) == null)
                return ['content' => json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Forward Request', 'method' => ''];
            return ['content' => json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Forward Request', 'method' => ''];
        }
        if($out->getContent())
            return ['content' => json_encode(json_decode($out->getContent()), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'type' => 'Response[' . $out->getStatusCode() . ']'];
        return ['content' => $out, 'type' => 'UndefinedProperty'];
    }

    public function getRequest($request)
    {
        if($request instanceof Request)
            return json_encode(json_decode($request->getContent()), JSON_UNESCAPED_UNICODE);
        elseif (is_array($request))
            return json_encode($request, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        else
            return [];
    }

    public function createCustomRequest(array $request, $isConsoleUser = false)
    {
        if(
            isset($request['method']) &&
            isset($request['url']) &&
            isset($request['body'])
        ) {
            if(isset($request['type']))
                $this->logger->notice(vsprintf("%s: %s %s %s %s", [$request['type'], $request['method'], $request['url'], $this->getOut($request['body'])['content'], $this->initLogInfo()]));
            else
                $this->logger->notice(vsprintf("Request: %s %s %s %s", [$request['method'], $request['url'], $this->getOut($request['body'])['content'], $this->initLogInfo()]));
        }
    }

    public function createCustomResponse(array $response)
    {
        if(
            isset($response['code']) &&
            isset($response['content'])
        ) {
            if(isset($response['type']))
                $this->logger->notice(vsprintf('%s[%s]: %s %s', [$response['type'], $response['code'], $this->getOut($response['content'])['content'], $this->initLogInfo()]));
            else
                $this->logger->notice(vsprintf('Response[%s]: %s %s', [$response['code'], $this->getOut($response['content'])['content'], $this->initLogInfo()]));
        }
    }


    public function initLogInfo(): string
    {
        try {
            $username = $this->username;
        } catch (\Exception $e) {
            $username = 'none';
        }
        return json_encode([
          'process_id' => getmypid(),
          'uid' => uniqid(),
          'user' => $username
        ]);
    }
}