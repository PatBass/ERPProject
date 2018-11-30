<?php

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\PaymentBundle\Service\Payment\Gateway\Fake as FakeGateway;
use KGC\RdvBundle\Entity\CarteBancaire;

class CommonContext extends MinkContext implements KernelAwareContext, SnippetAcceptingContext
{
    protected $httpClient;

    protected $kernel;

    protected $application;

    protected $response;

    protected $lastRequestUri;

    protected $json_schemas_directory;

    protected $prefix_url = '';
    protected $environment = 'dev';
    protected $log_dir = null;

    // Each time we create a user, we store it there
    protected $users = [];

    protected $lastCreditCard = null;

    public function setKernel(KernelInterface $kernelInterface) {
        $this->kernel = $kernelInterface;

        $this->application = new Application($this->kernel);
    }

    public function __construct(array $parameters = array()) {
        $this->json_schemas_directory = $parameters['json_schemas'];
        $this->httpClient = new HttpClient(array(
            'base_uri' => $parameters['base_url']
        ));

        if(isset($parameters['prefix_url'])) {
            $this->prefix_url = $parameters['prefix_url'];
        }

        if(isset($parameters['environment'])) {
            $this->environment = $parameters['environment'];
        }

        if(isset($parameters['log_dir'])) {
            $this->log_dir = $parameters['log_dir'];
        }
    }

    public function iSendARequestTo($httpMethod, $uri, $datas = array(), $options = array(), $use_prefix = true) {

        if($use_prefix && $this->prefix_url != '') {
            $uri = $this->prefix_url.$uri;
        }

        if(in_array($this->environment, ['dev', 'test'])) {
            $uri = '/app_'.$this->environment.'.php'.$uri;
        }

        $method = strtolower($httpMethod);

        $this->lastRequestUri = $uri;

        try {
            switch($httpMethod) {
                case 'POST':
                    $options['form_params'] = $datas;
                    $this->response = $this->httpClient->$method($uri, $options, $datas);
                    break;
                default:
                    $this->response = $this->httpClient->$method($uri, $options);
            }
        } catch (RequestException $e) {
            $this->response = $e->getResponse();
        }
    }

    /**
     * @When I call url :url
     */
    public function iCallUrl($url) {
        $this->iSendARequestTo('GET', $url);
    }

    /**
     * @When I call url :url with parameters:
     */
    public function iCallUrlWithParameters($url, TableNode $table) {
        foreach ($table as $row) {
            $parameters = $row;
        }

        foreach ($parameters as &$param) {
            $decodeValue = json_decode($param, true);
            if ($decodeValue !== null) {
                $param = $decodeValue;
            }
        }

        $this->iSendARequestTo('POST', $url, $parameters);
    }

    /**
     * @When /^I run command "([^"]*)"$/
     */
    public function iRunCommand($name, array $parameters = [])
    {
        $command = $this->application->find($name);

        if (method_exists($command, 'setCheckedUsers')) {
            $checkedUsers = [];
            foreach ($this->users as $user) {
                $checkedUsers[] = $user['id'];
            }
            $command->setCheckedUsers($checkedUsers);
        }

        $this->tester = new CommandTester($command);
        $this->tester->execute(['command' => $command->getName()] + $parameters);
    }

    /**
     * @Given /^I run command "([^"]*)" with parameters:$/
     */
    public function iRunCommandWithParameters($command, PyStringNode $parameterJson)
    {
        $commandParameters = json_decode($parameterJson->getRaw(), true);
        if (null === $commandParameters) {
            throw new \InvalidArgumentException(
                "PyStringNode could not be converted to json."
            );
        }

        $this->iRunCommand($name, $commandParameters);
    }

    /**
     * @Then /^I should see "([^"]*)" in the command output$/
     */
    public function iShouldSeeInTheCommandOutput($regexp)
    {
        $display = $this->tester->getDisplay();
        if (!preg_match('/'.$regexp.'/', $display)) {
            throw new \Exception("Command display not matching\n\tExpected: \"".addslashes($regexp)."\".\n\tGot: \"".$display."\"");
        }
    }

    /**
     * @Then /^I should not see "([^"]*)" in the command output$/
     */
    public function iShouldNotSeeInTheCommandOutput($regexp)
    {
        $display = $this->tester->getDisplay();

        if (preg_match('/'.$regexp.'/', $display)) {
            throw new \Exception("Command display not matching\n\tNOT expected: \"".addslashes($regexp)."\".\n\tGot: \"".$display."\"");
        }
    }

    /**
     * @When With :username, I call authenticated url :url
     */
    public function withUsernameICallAuthenticatedUrl($username, $url, $method = 'GET', $data = array()) {
        $user = $this->iShouldHaveUser($username);

        $this->iSendARequestTo($method, $url, $data, array(
            'headers' => array(
                'Authorization' => 'Bearer '.$user['token']
            )
        ));
    }

    /**
     * @Then I should have json object
     */
    public function itsJsonObject() {
        $content_types = $this->getResponse()->getHeader('Content-Type');
        if(!in_array('application/json', $content_types)) {
            throw new Exception("Not json object received : ".json_encode($content_types));
        }
    }

    /**
     * Check if username exists here (called before by iHaveUsername ...)
     * After calling this method, you can safely access to username index in $this->users
     *
     * @param string $username The clear username (not processed)
     *
     * return array $user
     */
    protected function iShouldHaveUser($username) {
        if(isset($this->users[$username])) {
            return $this->users[$username];
        }
        else {
            throw new Exception('Unknown username '.$username, 1);
        }
    }

    /**
     * @Then the response status code should be :status_code_asserted
     */
    public function theResponseStatusCodeShouldBe($status_code_asserted) {
        $status_code = $this->getResponseStatusCode();

        if($status_code != $status_code_asserted) {
            if($status_code == 500) {
                $this->catchResponse('error.html');
            }
            throw new Exception('Unexpected status code : '.$status_code.' for url '.$this->lastRequestUri);
        }
    }

    /**
     * Try to get response body
     */
    protected function getResponseBody() {
        return $this->getResponse('body')->getBody(true);
    }

    /**
     * Try to get response body as a json
     */
    protected function getResponseJson($as_array = true) {
        $this->itsJsonObject();
        $content = trim($this->getResponseBody());
        $json = json_decode($content, $as_array);
        return $json;
    }

    /**
     * Try to get response status code
     */
    protected function getResponseStatusCode() {
        return $this->getResponse('status code')->getStatusCode();
    }

    /**
     * Try to get response
     */
    protected function getResponse($try = '') {
        if($this->response === null) {
            throw new Exception('There is no response'.($try !== '' ? ' to get '.$try: '').'. If you work with docker, make sure the targeted container is up.');
        }

        return $this->response;
    }

    /**
     * Log the last response in log dir file if possible
     *
     * @return void
     */
    protected function catchResponse($file) {
        if(is_string($this->log_dir) && $this->response !== null) {
            if(!is_dir($this->log_dir)) {
                $parts = explode('/', $this->log_dir);
                $dir = '';
                foreach($parts as $part) {
                    if(!is_dir($dir .= "/$part")) {
                        mkdir($dir);
                    }
                }
            }

            file_put_contents($this->log_dir.'/'.$file, trim($this->getResponseBody()));
        }
    }

    /**
     * @Then the json status should be :status
     */
    public function theJsonStatusShouldBe($status) {
        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        if(isset($json['status'])) {
            if($json['status'] != $status) {
                throw new Exception('Unexpected status : "'.$json['status'].'" with message "'.$json['message'].'"');
            }
        }
        else {
            throw new Exception('No status present: '.$content);
        }
    }

    /**
     * @Then the JSON field ":field" should be an empty array
     */
    public function theJsonFieldShouldBeAnEmptyArray($field) {
        $content = trim($this->response->getBody(true));
        $json = json_decode($content);

        if($json instanceof \stdClass && property_exists($json, $field)) {
            if(is_array($json->$field) && !empty($json->$field)) {
                throw new Exception('The json field is not empty (Actual value : '.json_encode($json->$field).')');
            }
        }
    }

    /**
     * @Then the JSON field ":field" should be :type
     */
    public function theJsonFieldShouldBeType($field, $type = 'null') {
        $content = trim($this->getResponseBody());
        $json = json_decode($content);

        if(!($json instanceof \stdClass)) {
            throw new Exception('Unparsable content');
        }

        if(!property_exists($json, $field)) {
            throw new Exception('Property '.$field.' does not exist in json content');
        }

        $assert = false;
        $assert_method = null;
        switch ($type) {
            case 'null':
            case 'bool':
            case 'numeric':
            case 'float':
            case 'int':
            case 'string':
            case 'object':
            case 'array':
                $assert_method = 'is_'.$type;
                break;

            default:
                break;
        }

        if($assert_method === null) {
            throw new Exception('Unknown type assert : '.$type);
        }

        if(!$assert_method($json->$field)) {
            throw new Exception('The field '.$field.' is not of type "'.$type.'", but is of type "'.gettype($json->$field).'" instead');
        }
    }

    /**
     * @Then the JSON field ":field" should be equal to ":value"
     */
    public function theJsonFieldShouldBeEqualTo($field, $value) {
        $content = trim($this->response->getBody(true));
        $json = json_decode($content);

        if($json instanceof \stdClass && property_exists($json, $field)) {
            if (empty($json->$field) || $json->$field != $value) {
                throw new Exception('The json field is not empty (Actual value : '.json_encode($json->$field).')');
            }
        }
    }

    /**
     * @Then the JSON response should be equal to:
     */
    public function theJsonResponseShouldBeEqualTo(PyStringNode $markdown) {
        $json = json_decode($stream = $this->response->getBody(true));
        $expectedJson = json_decode($markdown->getRaw());

        if (!$expectedJson instanceof \stdClass) {
            throw new Exception('Invalid json format');
        }
        if ($json != $expectedJson) {
            throw new Exception('Unexpected json response (Actual value : '.json_encode($json).')');
        }
    }

    /**
     * @Then the object ":object" should be formated as ":schema_file"
     */
    public function theObjectShouldBeFormatedAs($object, $schema_file) {
        $content = trim($this->getResponseBody());
        $json = json_decode($content);

        if($json instanceof \stdClass && property_exists($json, $object)) {
            $result = $this->validJsonSchema($json->$object, $schema_file);
            if($result['status'] != 'OK') {
                throw new Exception('The json object does not match the assert : '.implode($result['errors'], ', '));
            }
        }
        else {
            throw new Exception('No '.$object.' present');
        }
    }

    /**
     * @Then the object ":object" should contains :x elements
     */
    public function theObjectShouldContainsXElements($object, $x) {
        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        if(!isset($json[$object])) {
            throw new Exception('No '.$object.' present');
        }

        if(count($json[$object]) != $x) {
            throw new Exception('We expect '.$x.' elements for '.$object.', but we got '.count($json[$object]).' here');
        }
    }

    /**
     * @Then the response should be formated as ":schema_file"
     */
    public function theResponseShouldBeFormatedAs($schema_file) {
        $content = trim($this->getResponseBody());
        $json = json_decode($content);

        if($json instanceof \stdClass) {
            $result = $this->validJsonSchema($json, $schema_file);
            if($result['status'] != 'OK') {
                throw new Exception('The json object does not match the assert : '.implode($result['errors'], ', '));
            }
        }
        else {
            throw new Exception('Malformed json');
        }
    }


    /**
     * Use JsonSchema to validate the returned json
     */
    protected function validJsonSchema($object, $schema_file) {
        $retriever = new JsonSchema\Uri\UriRetriever;
        $schema = $retriever->retrieve('file://'.realpath($this->kernel->getContainer()->getParameter('kernel.root_dir').'/..'.$this->json_schemas_directory.$schema_file));

        $validator = new JsonSchema\validator();
        $validator->check($object, $schema);
        $result = array(
            'status' => 'KO',
            'errors' => array()
        );
        if($validator->isValid()) {
            $result['status'] = 'OK';
        }
        else {
            foreach ($validator->getErrors() as $error) {
                $result['errors'][] = sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
        }

        return $result;
    }

    /**
     * Get a unique id to usernames created for test purpose, to be sure there isn't conflict with unique database column
     */
    protected function getUniqueUsernameId($username) {
        return $username.'-behattest';
    }

    /**
     * Get a unique id to emails created for test purpose, to be sure there isn't conflict with unique database column
     */
    protected function getUniqueEmailId($name) {
        return 'behattest-'.strtolower($name).'@behattest.fr';
    }

    protected function getEntityManager() {
        return $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function generateCreditCardParameters($validity = 'valid')
    {
        return [
            'creditCard' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'number' => $this->getCardNumberByValidity($validity),
                'securityCode' => '123',
                'expireAt' => ['year' => (string)(new \DateTime($validity == 'expired' ? '-1 month' : '+2 year'))->format('Y'), 'month' => date('n')]
            ]
        ];
    }

    /**
     * @Then Wait :x seconds
     */
    public function waitXSeconds($x) {
        sleep($x);
    }

    /**
     * Accessibility method to get the current user connected from security context
     */
    protected function getUserFromSecurity() {
        return $this->kernel->getContainer()->get('security.token_storage')->getToken()->getUser();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // CHAINING STEPS : theses steps are "macro steps", they call two or more steps at same time
    ///////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Then the response should be json object with status :status
     */
    public function theResponseShouldBeJsonObjectWithStatus($status) {
        $this->theResponseStatusCodeShouldBe(200);
        $this->itsJsonObject();
        $this->theJsonStatusShouldBe($status);
    }

    protected function getClientFromUsernameAndWebsiteSlug($username, $website_slug)
    {
        $origin = SharedWebsiteManager::getReferenceFromSlug($website_slug);
        if (!(is_string($origin) && $origin != '')) {
            throw new Exception('Unknown website '.$website_slug);
        }

        $ClientRepository = $this->getEntityManager()->getRepository('KGCSharedBundle:Client');
        return $ClientRepository->findOneBy([
            'nom' => 'BEHAT',
            'prenom' => $username,
            'origin' => $origin
        ]);
    }

    /**
     * @Given :client_username has no credit card on :website_slug
     */
    public function hasNoPaymentAlias($client_username, $website_slug)
    {
        $user = $this->iShouldHaveUser($client_username);

        $origin = SharedWebsiteManager::getReferenceFromSlug($website_slug);
        if(!(is_string($origin) && $origin != '')) {
            throw new Exception('Unknown website '.$website_slug);
        }

        $em = $this->getEntityManager();

        $ClientRepository = $em->getRepository('KGCSharedBundle:Client');
        $client = $ClientRepository->find($user['id']);
        if($client === null) {
            throw new Exception('Unknown client '.$user['id']);
        }

        $this->lastCreditCard = null;
    }

    /**
     * @param string $validity
     *
     * @return string
     */
    protected function getCardNumberByValidity($validity) {
        switch ($validity) {
            case 'invalid':
                $cardNumber = FakeGateway::INVALID_CARD_NUMBER;
                break;
            case 'invalidCardData':
                $cardNumber = FakeGateway::INVALID_CARD_DATA_NUMBER;
                break;
            case 'exception':
                $cardNumber = FakeGateway::EXCEPTION_CARD_NUMBER;
                break;
            default:
                $cardNumber = FakeGateway::VALID_CARD_NUMBER;
        }

        return $cardNumber;
    }

    /**
     * @Given :username has a :state credit card on :website_slug
     */
    public function hasACreditCardOn($username, $state, $website_slug)
    {
        $client = $this->getClientFromUsernameAndWebsiteSlug($username, $website_slug);



        $carteBancaire = (new CarteBancaire)
            ->setFirstName($username)
            ->setLastName('BEHAT')
            ->setNumero($this->getCardNumberByValidity($state))
            ->setExpiration((new \DateTime($state == 'expired' ? '-1 month' : '+2 year'))->format('m/y'))
            ->setCryptogramme('123')
            ->setInterdite($state == 'cbi');

        $client->addCartebancaires($carteBancaire);
        $em = $this->getEntityManager();
        $em->persist($client);
        $em->flush($client);

        $this->lastCreditCard = $carteBancaire;
    }

    /**
     * @Then :username should have a payment alias on :website_slug
     */
    public function shouldHaveAPaymentAliasOn($username, $website_slug)
    {
        $em = $this->getEntityManager();

        $website = $em->getRepository('KGCSharedBundle:Website')->findOneByReference(SharedWebsiteManager::getReferenceFromSlug($website_slug));
        $client = $this->getClientFromUsernameAndWebsiteSlug($username, $website_slug);

        $alias = $em->getRepository('KGCPaymentBundle:PaymentAlias')->findLastOneByClientAndGateway($client, $website->getPaymentGateway());

        if (!$alias) {
            throw new \Exception('User should have an alias');
        }
    }

    /**
     * @Then :username should not have a payment alias on :website_slug
     */
    public function shouldNotHaveAPaymentAliasOn($username, $website_slug)
    {
        $em = $this->getEntityManager();

        $website = $em->getRepository('KGCSharedBundle:Website')->findOneByReference(SharedWebsiteManager::getReferenceFromSlug($website_slug));
        $client = $this->getClientFromUsernameAndWebsiteSlug($username, $website_slug);

        $alias = $em->getRepository('KGCPaymentBundle:PaymentAlias')->findLastOneByClientAndGateway($client, $website->getPaymentGateway());

        if ($alias) {
            throw new \Exception('User should not have any alias');
        }
    }
}