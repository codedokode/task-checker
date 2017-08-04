<?php 

namespace TaskChecker\Web;

use Silex\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Tests\TaskChecker\Helper\TestHelper;

class ControllersTest extends WebTestCase
{
    public function createApplication()
    {
        return TestHelper::getApplication();
    }
    
    private function checkResponseIsHtml200(Client $client)
    {
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Check response contains something
        $this->assertGreaterThan(10, trim(strlen($response->getContent())));
        $this->assertRegExp('~^text/html\b~', $response->headers->get('Content-Type'));
    }

    public function urlProvider()
    {
        return [
            ['/'],
            ['/check/hello-world']
        ];
    }
    
    /**
     * @dataProvider urlProvider
     */
    public function testPagesAreWorking($url)
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', $url);
        $this->checkResponseIsHtml200($client);
    }

    public function testPostingTheCodeWorks()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/check/hello-world');

        $formNode = $crawler->filter('form[data-test-id="taskForm"]')->
            first();
        $form = $formNode->form();

        $crawler2 = $client->submit($form, [
            'source'    =>  '<?php echo "CodeExample";'
        ]);

        $this->checkResponseIsHtml200($client);
    }
}
