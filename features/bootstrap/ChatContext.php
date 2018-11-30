<?php

use Behat\Behat\Tester\Exception\PendingException;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Entity\Profil;
use KGC\ChatBundle\Command\SubscriptionCommand;
use KGC\ChatBundle\Entity\ChatFormula;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatParticipant;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatPromotion;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\ChatBundle\Entity\ChatRoomConsumption;
use KGC\ChatBundle\Entity\ChatRoomFormulaRate;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\ChatBundle\Entity\ChatType;
use KGC\ChatBundle\Service\UserManager;
use KGC\ChatBundle\Exception as ChatException;
use KGC\ChatBundle\Service\SubscriptionBatch;
use KGC\CommonBundle\Traits\NextReceiptDate;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Entity\PaymentAlias;
use KGC\RdvBundle\Entity\CarteBancaire;

class ChatContext extends CommonContext
{
    use NextReceiptDate;

    private $token;

    private $rooms = array();

    // Call 'But No "psychic/room/chat_payment ..." clean' in scenario to disable this clean in after scenario
    private $clean = array();

    // Each time we create an expired formula rate, we store it
    private $expiredFormulaRates = [];

    // When a formula rate is selected, we might want to store it for future process
    private $formula_rate_selected = null;

    protected $realPromoCodes = [];
    protected $promotions = [];

    public function setKernel(KernelInterface $kernelInterface) {
        parent::setKernel($kernelInterface);

        $this->application->add(new SubscriptionCommand());
    }

    /**
     * @Then I should have token
     */
    public function iHaveToken() {
        $token = $this->getToken();
        if(!(is_string($token) && $token != '')) {
            throw new Exception('Token is not present');
        }
    }

    /**
     * Convert string chat type to real constant
     *
     * @param string $chat_type
     *
     * @return integer
     */
    private function getChatTypeConstantFromString($chat_type) {
        $chat_types = array(
            'minute' => ChatType::TYPE_MINUTE,
            'question' => ChatType::TYPE_QUESTION,
        );

        return isset($chat_types[$chat_type]) ? $chat_types[$chat_type]: null;
    }

    /**
     * Convert string utilisateur's sexe to real constant
     *
     * @param string $sexe
     *
     * @return integer
     */
    private function getUtilisateurSexeConstantFromString($sexe) {
        $sexes = array(
            'man' => Utilisateur::SEXE_MAN,
            'woman' => Utilisateur::SEXE_WOMAN
        );

        return isset($sexes[$sexe]) ? $sexes[$sexe]: null;
    }

    /**
     * @Given I have a psychic named ":username" with password ":password"
     */
    public function iHaveAPsychicNamedUsernameWithPassword($username, $password, $chat_type = null, $sexe = null) {
        $processed_username = $this->getUniqueUsernameId($username);
        $UtilisateurRepository = $this->getEntityManager()->getRepository('KGCUserBundle:Utilisateur');
        $psychic = $UtilisateurRepository->findOneByUsername($processed_username);

        if($psychic === null) {
            if($profilPsychic = $this->getEntityManager()->getRepository('KGCUserBundle:Profil')->findOneByRole(Profil::VOYANT)) {
                $psychic = new Utilisateur();
                $psychic->setUsername($processed_username)
                        ->setPassword($password)
                        ->setMainProfil($profilPsychic);

                $UserManager = $this->kernel->getContainer()->get('kgc.user.manager');
                $UserManager->updateUser($psychic);
            }
            else {
                throw new Exception('No psychic profil found');
            }
        }

        $psychic = $UtilisateurRepository->findOneByUsername($processed_username);

        if($psychic === null) {
            throw new Exception('Unable to create psychic');
        }

        $psychic_sexe = Utilisateur::SEXE_MAN;
        if(($constant_sexe = $this->getUtilisateurSexeConstantFromString($sexe)) !== null) {
            $psychic_sexe = $constant_sexe;
        }

        $psychic->setSexe($psychic_sexe);

        $type = ChatType::TYPE_MINUTE;
        if(($constant_type = $this->getChatTypeConstantFromString($chat_type)) !== null) {
            $type = $constant_type;
        }

        if($chatType = $this->getEntityManager()->getRepository('KGCChatBundle:ChatType')->findOneByType($type)) {
            $psychic->setChatType($chatType);
        }
        else {
            throw new Exception('No chat type found');
        }

        $this->getEntityManager()->flush();

        $this->users[$username] = array(
            'id' => $psychic->getId(),
            'type' => UserManager::TYPE_PSYCHIC,
            'token' => null,
            'password' => $password
        );
    }

    /**
     * @Given I have a psychic named ":username" with password ":password" on chat type ":chat_type"
     */
    public function iHaveAPsychicNamedWithPasswordOnChatType($username, $password, $chat_type) {
        // Just an alias of previous rule
        $this->iHaveAPsychicNamedUsernameWithPassword($username, $password, $chat_type);
    }

    /**
     * @Given I have a client named ":username" with password ":password" on website ":website"
     */
    public function iHaveAClientNamedUsernameWithPassword($username, $password, $website, $email = null) {
        $client = $this->getClientFromUsernameAndWebsiteSlug($username, $website);

        if($client === null) {
            $email = $email ?: $this->getUniqueEmailId($username);
            $origin = SharedWebsiteManager::getReferenceFromSlug($website);

            $dateNaissance = new \DateTime();
            $dateNaissance->setDate(1992, 8, 18);
            $client = new Client();
            $client->setMail($email)
                   ->setUsername($email)
                   ->setNom('Behat')
                   ->setPrenom($username)
                   ->setDateNaissance($dateNaissance)
                   ->setPlainPassword($password)
                   ->setOrigin($origin)
                   ->setEnabled(true);

            $UserManager = $this->kernel->getContainer()->get('fos_user.user_manager');
            $UserManager->updateUser($client);
        }

        $client = $this->getClientFromUsernameAndWebsiteSlug($username, $website);

        if($client === null) {
            throw new Exception(sprintf('Unable to create client (%s, %s, %s)', $username, $email, $origin));
        }

        $this->users[$username] = array(
            'id' => $client->getId(),
            'type' => UserManager::TYPE_CLIENT,
            'token' => null,
            'password' => $password,
            'website_slug' => $website,
            'origin' => SharedWebsiteManager::getReferenceFromSlug($website),
            'usernameOrigin' => $client->getUsernameOrigin(),
        );
    }

    /**
     * @Then I log in with :username
     */
    public function iLogInWithUsername($username) {
        $user = $this->iShouldHaveUser($username);
        if($user['type'] == UserManager::TYPE_PSYCHIC) {
            $this->iLogInAsPsychicWithPassword($username);
        }
        else {
            $this->iLogInAsClientWithPassword($username);
        }
    }

    /**
     * Logic to connect as a psychic
     */
    private function iLogInAsPsychicWithPassword($username) {
        $user = $this->iShouldHaveUser($username);
        $processed_username = $this->getUniqueUsernameId($username);
        $this->getSession()->visit('/login');
        $this->getSession()->getPage()->fillField('_username', $processed_username);
        $this->getSession()->getPage()->fillField('_password', $user['password']);
        $button = $this->getSession()->getPage()->find('css', '#login-box button[type="submit"]');
        $button->click();

        $userFromSecurity = $this->getUserFromSecurity();

        $tokenManager = $this->kernel->getContainer()->get('kgc.chat.token.manager');
        $this->users[$username]['token'] = $tokenManager->getToken($userFromSecurity);
    }

    /**
     * Logic to connect as a client
     */
    private function iLogInAsClientWithPassword($name) {
        $user = $this->iShouldHaveUser($name);
        $username = $this->getUniqueEmailId($name);
        $this->iSendARequestTo('POST', '/login_check', array(
            '_username' => $user['usernameOrigin'],
            '_password' => $user['password'],
        ));
        $content = trim($this->getResponseBody());

        $this->theResponseStatusCodeShouldBe(200);
        $this->itsJsonObject();

        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        if(isset($json['token'])) {
            $this->users[$name]['token'] = $json['token'];
        }
        else {
            throw new Exception('No token present: '.$content);
        }
    }

    /**
     * Search thanks to token manager the jwt token
     */
    private function getToken() {
        if($this->token === null) {
            $tokenManager = $this->kernel->getContainer()->get('kgc.chat.token.manager');
            return $tokenManager->getToken();
        }
        else {
            return $this->token;
        }
    }

    /**
     * @When With :username, I :way the previous created room
     */
    public function withUsernameIWayThePreviousCreatedRoom($username, $way) {
        $user = $this->iShouldHaveUser($username);
        $url = '/'.$user['type'].'/room/'.$way.'/'.$this->getPreviousRoomId();
        $this->withUsernameICallAuthenticatedUrl($username, $url);
    }

    /**
     * @Given :username send a message :content
     */
    public function sendAMessage($username, $content) {
        $user = $this->iShouldHaveUser($username);
        $url = '/'.$user['type'].'/room/message/'.$this->getPreviousRoomId();
        $this->withUsernameICallAuthenticatedUrl($username, $url, 'POST', array(
            'content' => $content
        ));
    }

    /**
     * @Then With :username, I ask a conversation with a random psychic available
     */
    public function withUsernameIAskAConversationWithARandomPsychicAvailable($username) {
        $available_psychics = $this->getAvailablesPsychics();

        $this->thereShouldBeAtLeastPsychicsAvailables(1);

        shuffle($available_psychics);
        $url = '/client/room/ask/'.$available_psychics[0];
        $this->withUsernameICallAuthenticatedUrl($username, $url);
        $this->iStoreThePreviousRoomCreated();
    }

    /**
     * @Then there should be at least :count psychics availables
     */
    public function thereShouldBeAtLeastPsychicsAvailables($count) {
        $available_psychics = $this->getAvailablesPsychics();

        if(count($available_psychics) < $count) {
            throw new Exception('There should be at least '.$count.' available(s) psychic(s), but they are '.count($available_psychics).' here.');
        }
    }

    /**
     * @Then there should be :count psychics availables
     */
    public function thereShouldBePsychicsAvailables($count) {
        $available_psychics = $this->getAvailablesPsychics();

        if(count($available_psychics) != $count) {
            throw new Exception('There should be '.$count.' available(s) psychic(s), but they are '.count($available_psychics).' here.');
        }
    }

    /**
     * Convenience method to get availables psychics from last request
     */
    private function getAvailablesPsychics() {
        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        $available_psychics = array();

        foreach ($json['websites'] as $website) {
            foreach ($website['psychics'] as $psychic) {
                if($psychic['is_available']) {
                    $available_psychics[] = $psychic['id'];
                }
            }
        }

        return $available_psychics;
    }

    /**
     * Add a room to current stack
     */
    public function addRoom($room) {
        $this->rooms[] = $room;
    }

    /**
     * Get the last created room
     *
     * @return integer
     */

    public function getPreviousRoomId() {
        $last_room = end($this->rooms);
        return $last_room['id'];
    }

    /**
     * Store an eventually room created. This method does NOT throw error
     */
    private function iStoreThePreviousRoomCreated() {
        if($this->response !== null) {
            if($this->getResponseStatusCode() == 200 && in_array('application/json', $this->getResponse()->getHeader('Content-Type'))) {

                // At this point, we have a room id in response content
                $content = trim($this->getResponseBody());
                $json = json_decode($content, true);

                if(isset($json['status'], $json['room']['id']) && $json['status'] == 'OK') {
                    $this->addRoom($json['room']);
                }
            }
        }
    }

    /**
     * @Then With :username, I :decision to join the conversation
     */
    public function withUsernameIDecisionToJoinTheConversation($username, $decision) {
        $is_accepting = $decision === 'accept';

        $user = $this->iShouldHaveUser($username);

        if($user['type'] !== UserManager::TYPE_PSYCHIC) {
            throw new Exception('User need to be a psychic to make a decision about answer a room request');
        }

        $url = '/'.$user['type'].'/room/answer/'.$this->getPreviousRoomId().'/'.($is_accepting ? '1': '0');
        $this->withUsernameICallAuthenticatedUrl($username, $url);
    }

    protected function initTypeFormulaRateOnWebsite($username, $website_slug, $type = 'random', $open = false)
    {
        $user = $this->iShouldHaveUser($username);

        if($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User need to be a client to choose a formula rate');
        }

        $selected_formula_rate = null;

        if ($type == 'free_offer') {
            $this->iCallUrl('/open/website/'.$website_slug.'/get-free-offer-formula-rate');
            $this->theResponseShouldBeJsonObjectWithStatus('OK');

            $content = trim($this->getResponseBody());
            $json = json_decode($content, true);

            if (isset($json['formulaRate']) && $json['formulaRate']['is_free_offer']) {
                $selected_formula_rate = $json['formulaRate'];
            }
        }
        else {
            if($open) {
                $this->iCallUrl('/open/website/'.$website_slug.'/get-formulas');
            }
            else {
                $this->withUsernameICallAuthenticatedUrl($username, '/client/'.$website_slug.'/get-formulas');
            }

            $this->theResponseShouldBeJsonObjectWithStatus('OK');
            $this->theObjectShouldBeFormatedAs('formulas', 'formulas.json');

            $formula_rates = array();

            $content = trim($this->getResponseBody());
            $json = json_decode($content, true);

            foreach ($json['formulas'] as $formula) {
                foreach ($formula['formula_rates'] as $formula_rate) {
                    $formula_rates[] = $formula_rate;
                }
            }

            if(count($formula_rates) == 0) {
                throw new ChatException\NoFormulaRateAvailableException('No formula rate available');
            }

            shuffle($formula_rates);

            if($type === 'random') {
                $selected_formula_rate = $formula_rates[0];
            }
            else {
                foreach ($formula_rates as $formula_rate) {
                    if(isset($formula_rate['is_'.$type]) && $formula_rate['is_'.$type]) {
                        $selected_formula_rate = $formula_rate;
                        break;
                    }
                }
            }
        }

        if($selected_formula_rate === null) {
            throw new ChatException\NoFormulaRateAvailableException('No formula rate available for type '.$type);
        }

        return '/'.$user['type'].'/'.$website_slug.'/formula/buy/'.$selected_formula_rate['id'];
    }

    /**
     * @When With :username but without his token, I choose a :type formula rate on :website_slug
     */
    public function withUsernameButWithoutHisTokenIChooseAFormulaRateOnWebsite($username, $type, $website_slug)
    {
        $url = $this->initTypeFormulaRateOnWebsite($username, $website_slug, $type, true);
        $this->withUsernameICallAuthenticatedUrl($username, $url, 'POST', $this->generateCreditCardParameters());
    }

    /**
     * @When With ":username", it should be :status" to choose a ":type" formula rate on ":website_slug"
     */
    public function withUsernameItShouldBeStatusToChooseAFormulaRateOnWebsite($username, $status, $type, $website_slug)
    {
        try {
            // If formula is not available because we want to access a formula that we souldn't, initTypeFormulaRateOnWebsite will trigger an exception
            $url = $this->initTypeFormulaRateOnWebsite($username, $website_slug, $type);
            $this->withUsernameICallAuthenticatedUrl($username, $url, 'POST', $this->generateCreditCardParameters());
            $this->theJsonStatusShouldBe($status);
        }
        catch(ChatException\NoFormulaRateAvailableException $e) {
            // If we expected that the status should be KO, there is no problems here
            if($status != 'KO') {
                throw $e;
            }
        }
    }

    /**
     * @Then With :username, I choose a :type formula rate on :website_slug
     * @Then With :username, I choose a :type formula rate on :website_slug with :validity card
     */
    public function withUsernameIChooseATypeFormulaRateOnWebsiteWithCard($username, $type, $website_slug, $validity = 'valid', $parameters = [])
    {
        $url = $this->initTypeFormulaRateOnWebsite($username, $website_slug, $type);

        $this->withUsernameICallAuthenticatedUrl($username, $url, 'POST', $this->generateCreditCardParameters($validity) + $parameters);
    }

    /**
     * @Then With :username, I choose a :type formula rate on :website_slug with :validity card and code :code
     */
    public function withUsernameIChooseATypeFormulaRateOnWebsiteWithCardAndCode($username, $type, $website_slug, $validity, $code)
    {
        $this->withUsernameIChooseATypeFormulaRateOnWebsiteWithCard($username, $type, $website_slug, $validity, ['promotionCode' => $this->realPromoCodes[$code]]);
    }

    /**
     * @Then With :username, I choose a :type formula rate on :website_slug with :validity alias
     */
    public function withUsernameIChooseATypeFormulaRateOnWebsiteWithAlias($username, $type, $website_slug, $validity)
    {
        $url = $this->initTypeFormulaRateOnWebsite($username, $website_slug, $type);

        $this->withUsernameICallAuthenticatedUrl($username, $url, 'POST', $this->generateAliasParameters($username, $website_slug, $validity));
    }

    protected function generateAliasParameters($username, $website_slug, $validity)
    {
        $em = $this->getEntityManager();

        $client = $this->getClientFromUsernameAndWebsiteSlug($username, $website_slug);
        $website = (new SharedWebsiteManager($em))->getWebsiteBySlug($website_slug);

        $parameters = [];

        switch ($validity) {
            case 'valid':
                $alias = (new PaymentAlias)
                    ->setClient($client)
                    ->setGateway($website->getPaymentGateway())
                    ->setName('**********fake')
                    ->setCreatedAt(new \DateTime())
                    ->setExpiredAt((new \DateTime())->modify('+2 year'));
                $em->persist($alias);
                $em->flush();

                $parameters['alias'] = $alias->getId();
                break;
            case 'invalid': // alias on same website but another gateway (cannot be used by default)
                $alias = (new PaymentAlias)
                    ->setClient($client)
                    ->setGateway($website->getPaymentGateway() == 'be2bill' ? 'klikandpay' : 'be2bill')
                    ->setName('**********fake')
                    ->setCreatedAt(new \DateTime())
                    ->setExpiredAt((new \DateTime())->modify('+2 year'));
                $em->persist($alias);
                $em->flush();

                $parameters['alias'] = $alias->getId();
                break;
            default: // alias which does not exist
                $parameters['alias'] = 0;
        }

        return $parameters;
    }

    /**
     * @Then With :username, I already have a random formula rate of type :type on :website_slug
     */
    public function withUsernameIAlreadyHaveARandomFormulaRateOfTypeOnWebsite($username, $type, $website_slug)
    {
        $user = $this->iShouldHaveUser($username);

        if ($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User need to be a client to choose a formula rate');
        }

        $em = $this->getEntityManager();

        $client = $em->getRepository('KGCSharedBundle:Client')->find($user['id']);
        if ($client === null) {
            throw new Exception('Unknown client '.$user['id']);
        }

        switch ($type) {
            case 'discovery':
                $type = ChatFormulaRate::TYPE_DISCOVERY;
                break;
            case 'premium':
                $type = ChatFormulaRate::TYPE_PREMIUM;
                break;
            case 'subscription':
                $type = ChatFormulaRate::TYPE_SUBSCRIPTION;
                break;
            default: // standard
                $type = ChatFormulaRate::TYPE_STANDARD;
                break;
        }-

        $formulaRate = $em->getRepository('KGCChatBundle:ChatFormulaRate')->findOneByWebsiteAndType(
            (new SharedWebsiteManager($em))->getWebsiteBySlug($website_slug),
            $type
        );

        $em->persist(
            (new ChatPayment)
                ->setChatFormulaRate($formulaRate)
                ->setAmount($formulaRate->getPrice() * 100)
                ->setUnit($formulaRate->getUnits())
                ->setClient($client)
                ->setDate(new \DateTime)
                ->setState(ChatPayment::STATE_DONE)
        );
        $em->flush();
    }

    /**
     * create new formula rate
     *
     * @param Website $website
     *
     * @return ChatFormulaRate
     */
    protected function newFormulaRate(Website $website)
    {
        $chatType = $this->getEntityManager()->getRepository('KGCChatBundle:ChatType')->find(1);

        return (new ChatFormulaRate)
            ->setUnit(60)
            ->setPrice(50)
            ->setBonus(0)
            ->setFlexible(false)
            ->setChatFormula(
                (new ChatFormula)
                    ->setWebsite($website)
                    ->setChatType($chatType)
            );
    }

    /**
     * @Then With :username, I choose a formula rate with expired formula on :website_slug
     */
    public function withUsernameIChooseAFormulaRateWithExpiredFormulaOnWebsite($username, $website_slug) {
        $user = $this->iShouldHaveUser($username);

        if($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User need to be a client to choose a formula rate');
        }

        $em = $this->getEntityManager();
        $website = (new SharedWebsiteManager($em))->getWebsiteBySlug($website_slug);

        $formulaRate = $this->newFormulaRate($website);
        $formulaRate->getChatFormula()->setDesactivationDate(new \DateTime('2000-01-01'));

        $em = $this->getEntityManager();
        $em->persist($formulaRate->getChatFormula());
        $em->persist($formulaRate);
        $em->flush();
        $em->refresh($formulaRate);

        $this->expiredFormulaRates[$formulaRate->getId()] = $formulaRate;

        $url = '/'.$user['type'].'/'.$website_slug.'/formula/buy/'.$formulaRate->getId();
        $this->withUsernameICallAuthenticatedUrl($username, $url);
    }

    /**
     * @Then With :username, I choose a formula rate with expired formula rate on :website_slug
     */
    public function withUsernameIChooseAFormulaRateWithExpiredFormulaRateOnWebsite($username, $website_slug) {
        $user = $this->iShouldHaveUser($username);

        if($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User need to be a client to choose a formula rate');
        }

        $em = $this->getEntityManager();
        $website = (new SharedWebsiteManager($em))->getWebsiteBySlug($website_slug);

        $formulaRate = $this->newFormulaRate($website)
            ->setDesactivationDate(new \DateTime('2000-01-01'));

        $em->persist($formulaRate->getChatFormula());
        $em->persist($formulaRate);
        $em->flush();
        $em->refresh($formulaRate);

        $url = '/'.$user['type'].'/'.$website_slug.'/formula/buy/'.$formulaRate->getId();

        $this->withUsernameICallAuthenticatedUrl($username, $url);
    }

    /**
     * @Then the remaining time should be :x seconds less than original formula rate
     */
    public function theRemainingTimeShouldBeSecondsLessThanOriginalFormulaRate($x) {
        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        $remaining_credit = $json['room']['remaining_credit'];
        $normally_remaining_credit = $this->formula_rate_selected['unit'] + $this->formula_rate_selected['bonus'] - $x;

        $difference = $remaining_credit - $normally_remaining_credit;
        // Give a little margin difference (request time)
        if(abs($difference) > 4) {
            throw new Exception('Remaining time does not correspond : we should have '.$normally_remaining_credit.' credit(s) but we got '.$remaining_credit.' credit(s) instead');
        }
    }

    /**
     * @Then the consumed time for this room should be :x seconds
     */
    public function theConsumedTimeForThisRoomShouldBeSecondsLessThanOriginFormulaRate($x)
    {
        $roomId = $this->getPreviousRoomId();

        $chatRoom = $this->getEntityManager()->getRepository('KGCChatBundle:ChatRoom')->find($roomId);

        $consumption = 0;

        foreach ($chatRoom->getChatRoomFormulaRates() as $crfr) {
            $consumption += $crfr->getConsumedUnits();
        }

        if (abs($consumption - $x) > 2) {
            throw new Exception('The consumed time for this room should be '.intval($x).'s, but we got '.intval($consumption).'s');
        }
    }

    /**
     * @Then the remaining question should be :x less than original formula rate
     */
    public function theRemainingQuestionShouldBeLessThanOriginalFormulaRate($x) {
        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);
        $remaining_credit = $json['room']['remaining_credit'];
        $normally_remaining_credit = $this->formula_rate_selected['unit'] + $this->formula_rate_selected['bonus'] - $x;

        $difference = $remaining_credit - $normally_remaining_credit;
        // Give a little margin difference (request time)
        if(abs($difference) > 0) {
            throw new Exception('Remaining questions does not correspond : we should have '.$normally_remaining_credit.' credit(s) but we got '.$remaining_credit.' credit(s) instead');
        }
    }

    /**
     * @When :username receive an answer, psychic decrements a question
     */
    public function receiveAnAnswerPsychicDecrementsAQuestion($username) {
        $user = $this->iShouldHaveUser($username);

        if($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User need to be a client to be decremented');
        }

        $url = '/'.$user['type'].'/room/decrement/'.$this->getPreviousRoomId();
        $this->withUsernameICallAuthenticatedUrl($username, $url);
    }

    /**
     * @Then :username send a way too long message for this conversation
     */
    public function sendAWayTooLongMessageForThisConversation($username) {
        $last_room = end($this->rooms);
        $max_chars = isset($last_room['chat_type']['max_chars']) && is_numeric($last_room['chat_type']['max_chars']) ? $last_room['chat_type']['max_chars']: 0;
        if($max_chars <= 0) {
            throw new Exception('Unable to get max chars from chat type');
        }

        $lorem = 'Drone voodoo god franchise geodesic modem Kowloon artisanal systema decay neon saturation point sunglasses otaku Chiba table sensory convenience store. Hotdog woman boy carbon shoes office digital shanty town. Face forwards convenience store realism fetishism systema ablative dissident physical Shibuya. Savant pistol order-flow augmented reality corporation dead vinyl bomb-ware man numinous denim. Alcohol katana concrete rain bomb rebar construct boat. City rifle bicycle geodesic garage tanto marketing dissident free-market DIY. Augmented reality math-euro-pop rebar 8-bit urban motion order-flow otaku receding paranoid corporation crypto.';

        $content = '';

        do {
            $content .= $lorem;
        } while(strlen($content) <= $max_chars);

        $this->sendAMessage($username, $content);
    }

    /**
     * @When With :username, I cancel my last subscription
     */
    public function withUsernameICancelMyLastSubscription($username) {
        $this->theObjectShouldBeFormatedAs('subscriptions', 'subscriptions.json');

        $user = $this->iShouldHaveUser($username);

        if($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User must be a client');
        }

        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        $subscription_id = null;
        foreach ($json['subscriptions'] as $subscription) {
            $subscription_id = $subscription['id'];
            break;
        }

        if($subscription_id === null) {
            throw new Exception('There is no subscription to cancel');
        }

        $this->withUsernameICallAuthenticatedUrl($username, '/'.$user['type'].'/'.$user['website_slug'].'/subscription/cancel/'.$subscription_id);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // CHAINING STEPS : theses steps are "macro steps", they call two or more steps at same time
    ///////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Given The following logged people exist:
     */
    public function theFollowingLoggedPeopleExist(TableNode $table) {
        foreach ($table as $row) {
            if($row['type'] == UserManager::TYPE_PSYCHIC) {
                $this->iHaveAPsychicNamedUsernameWithPassword($row['username'], $row['password'], (isset($row['chat_type']) ? $row['chat_type']: null), (isset($row['sexe']) ? $row['sexe']: null));
                $this->iLogInWithUsername($row['username']);
                if(!isset($row['is_available']) || $row['is_available'] == 'yes') {
                    $this->withUsernameICallAuthenticatedUrl($row['username'], '/psychic/set-availability/1');
                    $this->theResponseStatusCodeShouldBe(200);
                }
            }
            elseif($row['type'] == UserManager::TYPE_CLIENT) {
                $this->iHaveAClientNamedUsernameWithPassword($row['username'], $row['password'], $row['website'], (isset($row['email']) ? $row['email'] : null));
                $this->iLogInWithUsername($row['username']);
                $this->theResponseStatusCodeShouldBe(200);
            }
            else {
                // Unknown type
                continue;
            }
        }
    }

    /**
     * @Given :client_username ask a conversation with :psychic_username on :website
     */
    public function askAConversationWithOnWebsite($client_username, $psychic_username, $website) {
        $this->withUsernameICallAuthenticatedUrl($client_username, '/client/get-available-psychics/'.$website);
        $this->theResponseShouldBeJsonObjectWithStatus('OK');
        $this->theObjectShouldBeFormatedAs('websites', 'websites.json');
        $this->withUsernameIAskAConversationWithARandomPsychicAvailable($client_username);
        $this->theResponseShouldBeJsonObjectWithStatus('OK');
        $this->theObjectShouldBeFormatedAs('room', 'room.json');
    }

    /**
     * @Given :client_username enter a conversation with :psychic_username on :website
     */
    public function enterAConversationWithOnWebsite($client_username, $psychic_username, $website) {
        $this->withUsernameIChooseATypeFormulaRateOnWebsiteWithCard($client_username, 'discovery', $website, 'valid');

        $this->theObjectShouldBeFormatedAs('formula_rate', 'formula_rate.json');

        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        $this->formula_rate_selected = $json['formula_rate'];

        $this->askAConversationWithOnWebsite($client_username, $psychic_username, $website);
        $this->withUsernameIDecisionToJoinTheConversation($psychic_username, 'accept');
        $this->theResponseShouldBeJsonObjectWithStatus('OK');
    }

    /**
     * @Given :client_username has almost consumed a free offer on this conversation
     */
    public function hasAlmostConsumedAFreeOfferOnThisConversation($client_username) {
        $user = $this->iShouldHaveUser($client_username);
        if($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User need to be a client to choose a formula rate');
        }

        $em = $this->getEntityManager();
        $client = $em->getRepository('KGCSharedBundle:Client')->find($user['id']);

        $chatRoom = $em->getRepository('KGCChatBundle:ChatRoom')->find($this->getPreviousRoomId());
        $firstChatRoomFormulaRate = $chatRoom->getChatRoomFormulaRates()->first();
        $website_slug = SharedWebsiteManager::getSlugFromReference($firstChatRoomFormulaRate->getChatFormulaRate()->getChatFormula()->getWebsite()->getReference());

        $this->withUsernameIChooseATypeFormulaRateOnWebsiteWithCard($client_username, 'free_offer', $website_slug, 'valid');
        $this->theObjectShouldBeFormatedAs('formula_rate', 'formula_rate.json');

        $content = trim($this->getResponseBody());
        $json = json_decode($content, true);

        $formulaRate = $em->getRepository('KGCChatBundle:ChatFormulaRate')->findOneById($json['formula_rate']['id']);
        $chatType = $formulaRate->getChatFormula()->getChatType();

        $chatPayment = $em->getRepository('KGCChatBundle:ChatPayment')->findOneBy(['chatFormulaRate' => $formulaRate, 'client' => $client]);

        $formulaUnits = $formulaRate->getUnit() + $formulaRate->getBonus();
        $consumed = $chatType->getType() == ChatType::TYPE_MINUTE ? $formulaUnits - 5 : $formulaUnits - 1;

        $date = clone $firstChatRoomFormulaRate->getStartDate();
        $date->modify('-10 second');

        $em->persist(
            $chatRoomFormulaRate = (new ChatRoomFormulaRate)
                ->setStartDate($date)
                //->setEndDate(null)
                ->setChatFormulaRate($formulaRate)
                ->setChatRoom($chatRoom)
        );

        $em->persist(
            (new ChatRoomConsumption)
                ->setDate($date)
                ->setUnit($consumed)
                ->setChatRoomFormulaRate($chatRoomFormulaRate)
                ->setChatPayment($chatPayment)
        );

        $em->flush();
    }

    /**
     * @Given :client_username leave this conversation
     */
    public function leaveThisConversation($client_username)
    {
        $user = $this->iShouldHaveUser($client_username);
        if($user['type'] !== UserManager::TYPE_CLIENT && $user['type'] !== UserManager::TYPE_PSYCHIC) {
            throw new Exception('User need to be a client or psychic to leave the conversation');
        }

        $this->withUsernameICallAuthenticatedUrl($client_username, sprintf('/%s/room/leave/%d', $user['type'], $this->getPreviousRoomId()));
    }

    /**
     * @Given :client_username reopen this conversation
     */
    public function reopenThisConversation($client_username)
    {
        $user = $this->iShouldHaveUser($client_username);
        if($user['type'] !== UserManager::TYPE_CLIENT) {
            throw new Exception('User need to be a client to reopen the conversation');
        }

        $this->withUsernameICallAuthenticatedUrl($client_username, sprintf('/%s/room/reopen/%d', $user['type'], $this->getPreviousRoomId()));
    }

    /**
     * @Then this room should be :status
     */
    public function thisRoomShouldBe($status)
    {
        //$content = trim($this->getResponseBody());
        //$json = json_decode($content, true);

        var_dump(__METHOD__, $json, $json['room']['users']);

        //$this->withUsernameICallAuthenticatedUrl($client_username, sprintf('/%s/room/reopen/%d', $user['type'], $this->getPreviousRoomId()));
    }

    /**
     * @When With :client_username, I try to delete this credit card
     */
    public function withUsernameITryToDeleteThisCreditCard($client_username)
    {
        $user = $this->iShouldHaveUser($client_username);

        $id = isset($this->lastCreditCard) && $this->lastCreditCard instanceof CarteBancaire ? $this->lastCreditCard->getId() : 0;

        $this->withUsernameICallAuthenticatedUrl($client_username, sprintf('/client/card/%d/delete', $id));
    }

    /**
     * @Then this credit card should not exist anymore
     */
    public function thisCreditCardShouldNotExistAnymore()
    {
        $id = isset($this->lastCreditCard) && $this->lastCreditCard instanceof CarteBancaire ? $this->lastCreditCard->getId() : 0;

        if ($id) {
            // clear doctrine cache to ensure alias will be searched in db and not in cache
            $this->getEntityManager()->clear();
            $card = $this->getEntityManager()->getRepository('KGCRdvBundle:CarteBancaire')->find($id);

            if ($card !== null) {
                throw new \Exception('Credit card still exists');
            }
        }
    }

    /**
     * @Given :username has a subscription starting at :subscriptionDate on :websitesSlug
     */
    public function hasASubscriptionStartingAt($username, $subscriptionDate, $websiteSlug)
    {
        $this->subscribe($username, $websiteSlug, $subscriptionDate);
    }

    /**
     * @Given :username has a subscription starting at :subscriptionDate with last payment at :lastPaymentDate on :websitesSlug
     */
    public function hasASubscriptionStartingAtWithLastPaymentAt($username, $subscriptionDate, $lastPaymentDate, $websiteSlug)
    {
        $this->subscribe($username, $websiteSlug, $subscriptionDate, null, $lastPaymentDate);
    }

    /**
     * @Given :username has a subscription starting at :subscriptionDate disabled at :disabledDate with last payment at :lastPaymentDate on :websitesSlug
     */
    public function hasASubscriptionStartingAtDisabledAtWithLastPaymentAtOn($username, $subscriptionDate, $disabledDate, $lastPaymentDate, $websiteSlug)
    {
        $this->subscribe($username, $websiteSlug, $subscriptionDate, $disabledDate, $lastPaymentDate);
    }

    /**
     * @Given :username has a subscription starting at :subscriptionDate with successful payment at :successfulPaymentDate and failed payment at :failedPaymentDate on :websiteSlug
     */
    public function hasASubscriptionStartingAtWithSuccessfulPaymentAtAndFailedPaymentAtOn($username, $subscriptionDate, $successfulPaymentDate, $failedPaymentDate, $websiteSlug)
    {
        $this->subscribe($username, $websiteSlug, $subscriptionDate, null, $successfulPaymentDate, $failedPaymentDate);
    }

    protected function subscribe($username, $websiteSlug, $subscriptionDate, $disabledDate = null, $successPaymentDate = null, $failedPaymentDate = null)
    {
        $user = $this->iShouldHaveUser($username);
        $em = $this->getEntityManager();

        $client = $em->getRepository('KGCSharedBundle:Client')->findOneById($user['id']);
        $website = $em->getRepository('KGCSharedBundle:Website')->findOneByReference($reference = SharedWebsiteManager::getReferenceFromSlug($websiteSlug));

        $formulaRate = $em->getRepository('KGCChatBundle:ChatFormulaRate')->findOneByWebsiteAndType($website, ChatFormulaRate::TYPE_SUBSCRIPTION);

        $subscriptionDT = new \DateTime($subscriptionDate);

        if ($successPaymentDate !== null) {
            $successPaymentDT = new \DateTime($successPaymentDate);
        } else {
            $successPaymentDT = null;
        }

        if ($successPaymentDT !== null) {
            $nextPaymentDT = new \DateTime($successPaymentDT->format('Y-m-').$subscriptionDT->format('d'));

            if ($nextPaymentDT < $successPaymentDT) {
                $nextPaymentDT->modify('+1 month');
            }
        } else {
            $nextPaymentDT = clone $subscriptionDT;
            $nextPaymentDT->modify('midnight');
        }

        $chatSubscription = (new ChatSubscription)
            ->setClient($client)
            ->setWebsite($website)
            ->setChatFormulaRate($formulaRate)
            ->setSubscriptionDate($subscriptionDT)
            ->setNextPaymentDate($nextPaymentDT);
        if ($disabledDate) {
            $chatSubscription->setDesactivationDate(new \DateTime($disabledDate));
        }

        $em->persist($chatSubscription);

        if ($successPaymentDate) {
            $succeedPayment = (new ChatPayment)
                ->setClient($client)
                ->setChatFormulaRate($formulaRate)
                ->setAmount($formulaRate->getPrice() * 100)
                ->setUnit($formulaRate->getUnits())
                ->setDate($successPaymentDate = new \DateTime($successPaymentDate))
                ->setState(ChatPayment::STATE_DONE);

            if ($disabledDate !== null && $successPaymentDate > $chatSubscription->getDesactivationDate()) {
                $chatSubscription->setNextPaymentDate(null);
            }

            $em->persist($succeedPayment);
        }

        if ($failedPaymentDate) {
            $failedPayment = (new ChatPayment)
                ->setClient($client)
                ->setChatFormulaRate($formulaRate)
                ->setAmount($formulaRate->getPrice() * 100)
                ->setUnit($formulaRate->getUnits())
                ->setDate(new \DateTime($failedPaymentDate))
                ->setState(ChatPayment::STATE_ERROR);

            $em->persist($failedPayment);
        }

        $em->flush();
    }

    /**
     * @When Subscription batch has current date set at :date
     */
    public function subscriptionBatchHasCurrentDateSetAt($date)
    {
        SubscriptionBatch::setCurrentDate($date);
        $this->clean['subscriptionBatch'] = true;
    }

    /**
     * @Given There is a :type promotion with code :code on :website_slug
     */
    public function thereIsPromotionWithCodeOn($type, $code, $websiteSlug)
    {
        $em = $this->getEntityManager();

        $website = $em->getRepository('KGCSharedBundle:Website')->findOneByReference($reference = SharedWebsiteManager::getReferenceFromSlug($websiteSlug));

        switch ($type) {
            case 'percentage':
                $type = ChatPromotion::UNIT_TYPE_PERCENTAGE;
                $unit = 10;
                break;
            case 'price':
                $type = ChatPromotion::UNIT_TYPE_PRICE;
                $unit = 5;
                break;
            default:
                $type = ChatPromotion::UNIT_TYPE_BONUS;
                $unit = 30;
        }

        $promotion = (new ChatPromotion)
            ->setName(uniqid('testPromo'))
            ->setPromotionCode($realCode = uniqid('test'.$code))
            ->setUnitType($type)
            ->setUnit($unit)
            ->setWebsite($website)
            ->setFormulaFilter(ChatPromotion::FORMULA_FILTER_STANDARD);

        $em->persist($promotion);
        $em->flush();

        $this->realPromoCodes[$code] = $realCode;
        $this->promotions[] = $promotion;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // COMMANDS : theses steps are "micro steps" to do things behind the scene
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @Then No :element clean
     */
    public function noElementClean($element) {
        if ($element == 'element') {
            // we cancel all clean except for psychic_availability
            foreach (['chat_payment', 'chat_subscription', 'room', 'payment_alias', 'expired_formula_rate'] as $elementToClean) {
                $this->clean[$elementToClean] = false;
            }
        } else {
            $this->clean[$element] = false;
        }
    }

    /**
     * @Then Clean :element
     */
    public function cleanElement($element) {
        switch ($element) {
            case 'room':
                $this->cleanRooms();
                break;

            case 'chat_payment':
                $this->cleanChatPayments();
                break;

            case 'psychic_availability':
                $this->cleanPsychicAvailabilities();
                break;
        }
    }

    private function canClean($element) {
        return isset($this->clean[$element]) && $this->clean[$element];
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // HOOK : theses methods are called a specific time execution
    ///////////////////////////////////////////////////////////////////////////////////////////

    /** @BeforeScenario */
    public function beforeScenario(BeforeScenarioScope $scope) {
        $this->clean = array(
            'chat_payment' => true,
            'chat_subscription' => true,
            'room' => true,
            'payment_alias' => true,
            'expired_formula_rate' => true,
            'psychic_availability' => true,
            'carte_bancaire' => true,
            'promotion' => true
        );

        // ensure we make all psychics unavailable for chat before tests run
        $this->getEntityManager()->createQuery('UPDATE KGC\UserBundle\Entity\Utilisateur u SET u.isChatAvailable = 0 WHERE u.isChatAvailable = 1')->getResult();
    }

    /** @AfterScenario */
    public function after(AfterScenarioScope $scope) {
        // Remove the created rooms
        $results = array();
        if ($this->canClean('room')) {
            $results += $this->cleanRooms();
        }

        if ($this->canClean('chat_subscription')) {
            $results = array_merge($results, $this->cleanChatSubscriptions());
        }

        if ($this->canClean('chat_payment')) {
            $results = array_merge($results, $this->cleanChatPayments());
        }

        if ($this->canClean('payment_alias')) {
            $results = array_merge($results, $this->cleanPaymentAliases());
        }

        if ($this->canClean('expired_formula_rate')) {
            $results = array_merge($results, $this->cleanExpiredChatFormulas());
        }

        if ($this->canClean('carte_bancaire')) {
            $results = array_merge($results, $this->cleanCarteBancaires());
        }

        if ($this->canClean('subscriptionBatch')) {
            $results = array_merge($results, $this->cleanSubscriptionBatch());
        }

        if ($this->canClean('promotion')) {
            $results = array_merge($results, $this->cleanPromotions());
        }

        echo 'After scenario clean :'."\n";
        if ($results) {
            foreach ($results as $result) {
                echo $result."\n";
            }
        } else {
            echo "No data clean needed\n";
        }

    }

    private function cleanPaymentAliases() {
        $results = array();
        foreach ($this->users as $username => $user) {
            if($user['type'] == UserManager::TYPE_CLIENT) {
                if ($count = $this->getEntityManager()->createQuery('DELETE KGC\PaymentBundle\Entity\PaymentAlias pa WHERE pa.client = '.$user['id'])->getResult()) {
                    $results[] = $count.' payment alias(es) cleaned';
                }
            }
        }
        return $results;
    }

    /**
     * Remove chat payments
     */
    private function cleanChatPayments() {
        $results = array();
        foreach ($this->users as $username => $user) {
            if($user['type'] == UserManager::TYPE_CLIENT) {
                $em = $this->getEntityManager();
                $qb = $em->createQueryBuilder()
                           ->select('crc')
                           ->from('KGCChatBundle:ChatRoomConsumption', 'crc')
                           ->join('crc.chatPayment', 'cp')
                           ->where('cp.client = :client')
                           ->setParameter('client', $user['id']);
                $crcs = $qb->getQuery()->getResult();

                foreach($crcs as $crc) {
                    $em->remove($crc);
                }

                if ($count = count($crcs)) {
                    $em->flush();
                    $results[] = $count.' chat room consumption(s) cleaned';
                }

                if ($count = $em->createQuery('DELETE KGC\ChatBundle\Entity\ChatPayment cp WHERE cp.client = '.$user['id'])->getResult()) {
                    $results[] = $count.' chat payment(s) cleaned';
                }
                if ($count = $em->createQuery('DELETE KGCPaymentBundle:Payment p WHERE p.clientId = '.$user['id'])->getResult()) {
                    $results[] = $count.' payment(s) cleaned';
                }
            }
        }
        return $results;
    }

    /**
     * Remove chat subscriptions
     */
    private function cleanChatSubscriptions() {
        $results = array();
        foreach ($this->users as $username => $user) {
            if($user['type'] == UserManager::TYPE_CLIENT) {
                if ($count = $this->getEntityManager()->createQuery('DELETE KGC\ChatBundle\Entity\ChatSubscription cs WHERE cs.client = '.$user['id'])->getResult()) {
                    $results[] = $count.' chat subscription(s) cleaned';
                }
            }
        }
        return $results;
    }

    /**
     * Remove expired chat formulas
     */
    private function cleanExpiredChatFormulas() {
        $results = array();

        $em = $this->getEntityManager();

        foreach ($this->expiredFormulaRates as $rate) {
            $em->remove($rate);
            $em->remove($rate->getChatFormula());
        }
        $em->flush();

        return $this->expiredFormulaRates ? [count($this->expiredFormulaRates).' expired chat formulas cleaned'] : [];
    }

    /**
     * Remove rooms
     */
    private function cleanRooms() {
        $results = array();
        if(count($this->rooms) > 0) {
            $room_ids = array_map(function($room){
                return $room['id'];
            }, $this->rooms);
            $results[] = $this->getEntityManager()->createQuery('DELETE KGC\ChatBundle\Entity\ChatMessage cm WHERE cm.chatRoom IN ('.implode($room_ids, ',').')')->getResult().' message(s) cleaned';

            $results[] = $this->getEntityManager()->createQuery('DELETE KGC\ChatBundle\Entity\ChatParticipant cp WHERE cp.chatRoom IN ('.implode($room_ids, ',').')')->getResult().' participant(s) cleaned';

            $qb = $this->getEntityManager()->createQueryBuilder()
                       ->select('crc')
                       ->from('KGCChatBundle:ChatRoomConsumption', 'crc')
                       ->join('crc.chatRoomFormulaRate', 'crfr', 'WITH', 'crfr.chatRoom IN (:room_ids)')
                       ->setParameter('room_ids', $room_ids);
            $crcs = $qb->getQuery()->getResult();

            $results[] = count($crcs).' chat room consumption(s) cleaned';
            foreach($crcs as $crc) {
                $this->getEntityManager()->remove($crc);
            }
            $this->getEntityManager()->flush();

            $results[] = $this->getEntityManager()->createQuery('DELETE KGC\ChatBundle\Entity\ChatRoomFormulaRate crfr WHERE crfr.chatRoom IN ('.implode($room_ids, ',').')')->getResult().' chat room formula rate(s) cleaned';

            $results[] = $this->getEntityManager()->createQuery('DELETE KGC\ChatBundle\Entity\ChatRoom cr WHERE cr.id IN ('.implode($room_ids, ',').')')->getResult().' room(s) cleaned';
        }
        return $results;
    }

    private function cleanCarteBancaires() {
        $results = [];
        $clientIds = [];
        $em = $this->getEntityManager();

        foreach ($this->users as $username => $user) {
            if($user['type'] == UserManager::TYPE_CLIENT) {
                $clientIds[] = $user['id'];
            }
        }

        if ($clientIds) {
            if ($clients = $em->createQuery('SELECT c FROM KGC\Bundle\SharedBundle\Entity\Client c WHERE c.id IN ('.implode($clientIds, ',').')')->getResult()) {
                $count = 0;

                foreach ($clients as $client) {
                    foreach ($client->getCarteBancaires() as $cb) {
                        $client->removeCarteBancaires($cb);
                        ++ $count;
                    }

                    $em->persist($client);
                }

                if ($count) {
                    $em->flush();

                    $results[] = $count.' carte(s) bancaire(s) cleaned';
                }
            }
        }
        return $results;
    }

    protected function cleanSubscriptionBatch()
    {
        SubscriptionBatch::setCurrentDate('now');
        $this->canClean['subscriptionBatch'] = false;

        return ['Subscription batch reset'];
    }

    protected function cleanPromotions()
    {
        $results = [];

        $promoIds = [];
        $em = $this->getEntityManager();

        foreach ($this->promotions as $promotion) {
            $promoIds[] = $promotion->getId();
        }

        if ($promoIds) {
            $count = $em->createQuery('DELETE FROM KGC\ChatBundle\Entity\ChatPromotion cp WHERE cp.id IN ('.implode($promoIds, ',').')')->getResult();

            if ($count) {
                $results[] = $count.' promotion(s) cleaned';
            }
        }

        return $results;
    }
}
