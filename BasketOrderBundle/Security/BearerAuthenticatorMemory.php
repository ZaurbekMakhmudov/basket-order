<?php


namespace App\BasketOrderBundle\Security;

use App\BasketOrderBundle\Service\TokenService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;

class BearerAuthenticatorMemory extends AbstractGuardAuthenticator
{
    const KEY = 'Authorization';

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService) {
        $this->tokenService = $tokenService;
    }

    public function supports(Request $request)
    {
        return $request->headers->has($this::KEY)
            && 0 === strpos($request->headers->get($this::KEY), 'Bearer ');
    }

    public function getCredentials(Request $request)
    {
        return substr($request->headers->get($this::KEY), 7);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if( $tokenData = $this->tokenService->getTokenDataFromCacheByToken($credentials) ) {

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
        return new JsonResponse([
            'message' => 'Authentication Required'
        ], 401);

    }

    public function supportsRememberMe()
    {
        return false;
    }
}
