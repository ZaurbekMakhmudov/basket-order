<?php

namespace App\BasketOrderBundle\Security;

use App\BasketOrderBundle\Service\TokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiKeyAuthenticatorMemory extends AbstractGuardAuthenticator
{
    const KEY = 'X-RAINBOW-ESHOP-KEY';
    const KEY_NEW = 'Authorization';

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService) {
        $this->tokenService = $tokenService;
    }

    public function supports(Request $request)
    {

        return ( $request->headers->has($this::KEY) || $request->headers->has($this::KEY_NEW) );
    }

    public function getCredentials(Request $request)
    {
        if( $request->headers->has($this::KEY) ) {

            return $request->headers->get($this::KEY);
        } elseif( $request->headers->has($this::KEY_NEW) ) {

            return substr($request->headers->get($this::KEY_NEW), 7);
        } else {

            return null;
        }
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if( $tokenData = $this->tokenService->getTokenDataFromCacheByToken($credentials, true) ) {

            return $userProvider->loadUserByUsername($tokenData['username']);
        }

        return null;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {

        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse([
            'message' => $exception->getMessageKey()
        ], 401);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // allow the authentication to continue
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
