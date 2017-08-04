<?php 

namespace Tests\TaskChecker\Web;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use TaskChecker\Web\Csrf\CsrfCheckFailedException;
use TaskChecker\Web\Csrf\CsrfService;

class CsrfServiceTest extends \PHPUnit_Framework_TestCase
{
    private $checker;

    public function setUp()
    {
        $this->checker = new CsrfService(
            new UriSafeTokenGenerator(),
            true
        );
    }

    public function testCanGenerateRandomToken()
    {
        $token = $this->checker->makeToken();
        $this->assertInternalType('string', $token);
        $this->assertNotEmpty($token);
    }

    public function testAcceptsEarlierGeneratedToken()
    {
        // Token is generated on the first page and checked on the other
        $token = null;
        $this->emulateNavigation([
            function ($checker) use (&$token) {
                $token = $checker->makeToken();
            },
            function ($checker) use (&$token) {
                $this->assertTrue($checker->isFormTokenValid($token));
            }
        ]);
    }

    /**
     * Tests that tokens do not expire too fast, for example if 
     * several tabs with the pages are opened, tokens in different 
     * tabs don't overwrite each other.
     */
    public function testTokenDoesntExpireAfterNavigatingSeveralPages()
    {
        $token1 = null;
        $token2 = null;
        $this->emulateNavigation([
            function ($checker) use (&$token1) {
                $token1 = $checker->makeToken();
            },
            function ($checker) use (&$token2) {
                $token2 = $checker->makeToken();
            },
            function ($checker) use (&$token1, &$token2) {
                $this->assertTrue($checker->isFormTokenValid($token1));
                $this->assertTrue($checker->isFormTokenValid($token2));
            }
        ]);
    }

    public function testDoesntAcceptEmptyOrInvalidToken()
    {
        $this->emulateNavigation([
            function ($checker) {

            },
            function ($checker) {
                $this->assertFalse($checker->isFormTokenValid(''));
                $this->assertFalse($checker->isFormTokenValid(null));
                $this->assertFalse($checker->isFormTokenValid('invalidtoken'));
            }
        ]);
    }

    public function testAutoValidationWithInvalidToken()
    {
        $req1 = Request::create('http://csrf.example.com/1');
        $req2 = Request::create('http://csrf.example.com/2', 'POST');

        $this->setExpectedException(CsrfCheckFailedException::class);
        $this->emulateNavigationWithAutoValidation(
            $req1,
            $req2
        );
    }

    public function testAutoValidationIsNotInvokedOnSafeRequest()
    {
        $req1 = Request::create('http://csrf.example.com/1');
        $req2 = Request::create('http://csrf.example.com/2', 'HEAD');

        $this->emulateNavigationWithAutoValidation(
            $req1,
            $req2
        );
    }
    
    public function testAutoValidationWithValidToken()
    {
        $req1 = Request::create('http://csrf.example.com/1');
        $req2 = Request::create('http://csrf.example.com/2', 'POST');

        $this->emulateNavigationWithAutoValidation(
            $req1,
            $req2,
            function ($checker) use ($req2) {
                $token = $checker->makeToken();
                $req2->request->set('csrf_token', $token);
            }
        );
    }

    public function testDoesntAcceptEmptyToken()
    {
        $checker = new CsrfService(
            new UriSafeTokenGenerator,
            false
        );

        $req = Request::create('http://csrf.example.com', 'POST');
        $checker->runBefore($req);

        $this->assertFalse($checker->isFormTokenValid(''));
    }

    public function testIsRequestValidatedIsNotSetByDefault()
    {
        $checker = new CsrfService(
            new UriSafeTokenGenerator,
            true
        );

        $req = Request::create('http://csrf.example.com/1', 'GET');
        $checker->runBefore($req);

        $this->assertFalse($checker->isRequestValidated());
    }
    
    public function testIsRequestValidatedSetWithValidToken()
    {
        $req1 = Request::create('http://csrf.example.com/1');
        $req2 = Request::create('http://csrf.example.com/2', 'POST');

        $this->emulateNavigationWithAutoValidation(
            $req1,
            $req2,
            function ($checker) use ($req2) {
                $token = $checker->makeToken();
                $req2->request->set('csrf_token', $token);
            },
            function ($checker) {
                $this->assertTrue($checker->isRequestValidated());
            }
        );
    }
    
    /**
     * Emulate navigation through several pages while persisting 
     * cookies
     */
    private function emulateNavigation(array $handlers)
    {
        $i = 1;
        $response = new Response('');

        foreach ($handlers as $handler) {
            $req = Request::create(
                "http://csrf.example.com/$i",
                'GET'
            );

            $this->copyCookiesToRequest($response, $req);

            $checker = new CsrfService(
                new UriSafeTokenGenerator,
                false
            );

            $checker->runBefore($req);

            $handler($checker);

            $response = new Response('');
            $checker->runAfter($req, $response);
        }
    }

    private function emulateNavigationWithAutoValidation(
        Request $req1,
        Request $req2,
        callable $handler1 = null,
        callable $handler2 = null
    ) {
        $checker = new CsrfService(
            new UriSafeTokenGenerator,
            true
        );

        $checker->runBefore($req1);
        if ($handler1) {
            $handler1($checker);
        }
        $response = new Response();
        $checker->runAfter($req1, $response);

        $this->copyCookiesToRequest($response, $req2);

        $checker2 = new CsrfService(
            new UriSafeTokenGenerator,
            true
        );

        $checker2->runBefore($req2);
        if ($handler2) {
            $handler2($checker2);
        }
        $response2 = new Response();
        $checker2->runAfter($req2, $response2);
    }

    private function copyCookiesToRequest(Response $response, Request $req)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $req->cookies->set($cookie->getName(), $cookie->getValue());
        }
    }
}