<?php 

namespace TaskChecker\Web\Csrf;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

/**
 * Uses cookies to check for CSRF tokens
 */
class CsrfService
{
    /** @var TokenGeneratorInterface */
    private $tokenGenerator;

    /**
     * @var Cookie Cookie to set at an `after` stage
     */
    private $cookieToSet;

    private $tokenInRequest;

    private $tokenName = 'csrf_token';   

    /**
     * Check token on POST/PUT/DELETE/PATCH request and 
     * throw an error if it is invalid or missing
     */
    private $autoValidate;

    private $cookieHttpOnly = true;

    private $cookiePath = '/';

    private $requestValidated = false;

    public function __construct(
        TokenGeneratorInterface $tokenGenerator, 
        $autoValidate = false
    ) {
        $this->tokenGenerator = $tokenGenerator;  
        $this->autoValidate = $autoValidate;
    }

    public function runBefore(Request $request)
    {
        $this->tokenInRequest = $request->cookies->get($this->tokenName);

        if ($this->autoValidate) {
            // If we have a modify request
            // then check CSRF token
            $method = mb_strtoupper($request->getMethod());
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                return null;
            }

            // Require a valid token
            $formToken = $request->get($this->tokenName);
            if (!$this->isFormTokenValid($formToken)) {
                throw new CsrfCheckFailedException("Invalid CSRF token");
            }

            $this->requestValidated = true;
        }
    }

    public function runAfter(Request $request, Response $response)
    {
        if ($this->cookieToSet) {
            $response->headers->setCookie($this->cookieToSet);
            // Cannot be shared
            $response->setPrivate();
        }
    }

    /**
     * Renew existing or generate a new token.
     */
    public function makeToken()
    {
        $existingToken = $this->getTokenInRequest();
        if ($existingToken !== null) {
            $this->setTokenCookie($existingToken);
            return $existingToken;
        } else {
            $newToken = $this->tokenGenerator->generateToken();
            $this->setTokenCookie($newToken);
            return $newToken;
        }
    }

    private function getTokenInRequest()
    {
        return $this->tokenInRequest;
    }

    private function setTokenCookie($token)
    {
        $expire = new \DateTime('+1 week');

        $this->cookieToSet = new Cookie(
            $this->tokenName,
            $token,
            $expire,
            $this->cookiePath,
            null,
            false,
            $this->cookieHttpOnly
        );
    }

    public function isFormTokenValid($formToken)
    {
        $token = strval($this->getTokenInRequest());
        if ($token === '') {
            return false;
        }

        return hash_equals($token, strval($formToken));
    }

    public function isRequestValidated()
    {
        return $this->requestValidated;
    }
}
