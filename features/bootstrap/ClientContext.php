<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Adresse;
use KGC\ChatBundle\Service\UserManager;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;

class ClientContext extends CommonContext
{
    /**
     * @Given :username has a consultation with new card hash :hash
     */
    public function hasAConsultationWithNewCardHash($username, $hash)
    {
        $user = $this->getClientFromUsername($username);

        $rdv = $this->getMinRdv($user)
            ->setNewCardHash($hash)
            ->setNewCardHashCreatedAt(new \DateTime);

        $em = $this->getEntityManager();

        $em->persist($rdv);
        $em->flush($rdv);
    }

    /**
     * @Given :username has a consultation with expired new card hash :hash
     */
    public function hasAConsultationWithExpiredNewCardHash($username, $hash)
    {
        $user = $this->getClientFromUsername($username);

        $rdv = $this->getMinRdv($user)
            ->setNewCardHash($hash)
            ->setNewCardHashCreatedAt(new \DateTime('-2 day'));

        $em = $this->getEntityManager();

        $em->persist($rdv);
        $em->flush($rdv);
    }

    /**
     * @Given :username has a consultation with existing card and new card hash :hash
     */
    public function hasAConsultationWithExistingCardAndNewCardHash($username, $hash)
    {
        $user = $this->getClientFromUsername($username);

        $rdv = $this->getMinRdv($user)
            ->setNewCardHash($hash)
            ->setNewCardHashCreatedAt(new \DateTime)
            ->addCartebancaires(
                (new CarteBancaire)
                    ->setNumero('5555555555554444')
                    ->setCryptogramme('123')
                    ->setExpiration((new \DateTime('+2 year'))->format('m/y'))
            );

        $em = $this->getEntityManager();

        $em->persist($rdv);
        $em->flush($rdv);
    }

    protected function getMinRdv(Client $client)
    {
        $defaultDate = new \DateTime('-2 day');

        return (new RDV($this->getProprio()))
            ->setClient($client)
            ->setDateContact($defaultDate)
            ->setDateConsultation($defaultDate)
            ->setEtat($this->getEtat())
            ->setNumtel1('0123456789')
            ->setAdresse(
                (new Adresse)
                    ->setClient($client)
                    ->setVoie('Rue tabaga')
                    ->setCodepostal('69002')
                    ->setVille('Lyon')
                    ->setPays('France')
            )
            ->setSupport($this->getSupport())
            ->setSecurisation(false)
            ->setWebsite($this->getWebsite());
    }

    protected function getEtat($etatIdcode = Etat::ADDED)
    {
        return $this->getEntityManager()->getRepository('KGCRdvBundle:Etat')->findOneByIdcode($etatIdcode);
    }

    protected function getProprio($utilisateurId = 1)
    {
        return $this->getEntityManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($utilisateurId);
    }

    protected function getSupport($supportId = 3)
    {
        return $this->getEntityManager()->getRepository('KGCRdvBundle:Support')->findOneById($supportId);
    }

    protected function getWebsite($websiteId = 1)
    {
        return $this->getEntityManager()->getRepository('KGCSharedBundle:Website')->findOneById($websiteId);
    }

    protected function getClientFromUsername($username)
    {
        return $this->getEntityManager()->getRepository('KGCSharedBundle:Client')->findOneBy([
            'nom' => 'BEHAT',
            'prenom' => $username,
            'origin' => 'admin'
        ]);
    }

    /**
     * @Given I have a client named ":username" with password ":password"
     */
    public function iHaveAClientNamedUsernameWithPassword($username, $password, $email = null) {
        $client = $this->getClientFromUsername($username);

        if($client === null) {
            $email = $email ?: $this->getUniqueEmailId($username);

            $dateNaissance = new \DateTime();
            $dateNaissance->setDate(1992, 8, 18);
            $client = new Client();
            $client->setMail($email)
                   ->setUsername($email)
                   ->setNom('Behat')
                   ->setPrenom($username)
                   ->setDateNaissance($dateNaissance)
                   ->setPlainPassword($password)
                   ->setOrigin('admin')
                   ->setEnabled(true);

            $UserManager = $this->kernel->getContainer()->get('fos_user.user_manager');
            $UserManager->updateUser($client);
        }

        $client = $this->getClientFromUsername($username);

        if($client === null) {
            throw new Exception(sprintf('Unable to create client (%s, %s)', $username, $email));
        }

        $this->users[$username] = array(
            'id' => $client->getId(),
            'type' => UserManager::TYPE_CLIENT,
            'token' => null,
            'password' => $password
        );
    }

    /**
     * @Then :username should not have any credit card
     * @Then :username should not have :minForbidden credit cards
     */
    public function shouldNotHaveCreditCard($username, $minForbidden = 1)
    {
        $client = $this->getClientFromUsername($username);
        $rdv = $this->getEntityManager()->getRepository('KGCRdvBundle:RDV')->findLastClientRdv($client);

        if ($rdv->getCartebancaires()->count() >= $minForbidden) {
            throw new \Exception('Username should not have new credit card');
        }
    }

    /**
     * @Then :username should now have credit card with parameters:
     */
    public function shouldNowHaveCreditCardWithParameters($username, TableNode $table)
    {
        $client = $this->getClientFromUsername($username);
        $rdv = $this->getEntityManager()->getRepository('KGCRdvBundle:RDV')->findLastClientRdv($client);
        $this->getEntityManager()->refresh($rdv);

        $cbs = $rdv->getCartebancaires();

        if (!$cbs->first()) {
            throw new \Exception('User should have a credit card');
        }

        foreach ($table as $i => $row) {
            $cb = $cbs->get($i);
            $cbExpiration = $cb->getExpiration();
            $expireAt = json_decode($row['expireAt'], true);
            $expireAtDt = new \DateTime($expireAt['year'].'-'.$expireAt['month'].'-'.$expireAt['day']);

            if (
                $row['number'] !== $cb->getNumero() ||
                $row['securityCode'] !== $cb->getCryptogramme() ||
                $expireAtDt->format('m/y') !== $cbExpiration
            ) {
                throw new \Exception('Unexpected credit card value');
            }
        }
    }

    /**
     * @Then :username consultation should not be confirmed
     */
    public function consultationShouldNotBeConfirmed($username)
    {
        $client = $this->getClientFromUsername($username);
        $rdv = $this->getEntityManager()->getRepository('KGCRdvBundle:RDV')->findLastClientRdv($client);

        if ($rdv->getEtat()->getIdcode() !== Etat::ADDED) {
            throw new \Exception('Unexpected status '.$rdv->getEtat()->getIdcode());
        }
    }

    /**
     * @Then :username consultation should be confirmed
     */
    public function consultationShouldBeConfirmed($username)
    {
        $client = $this->getClientFromUsername($username);
        $rdv = $this->getEntityManager()->getRepository('KGCRdvBundle:RDV')->findLastClientRdv($client);

        if ($rdv->getEtat()->getIdcode() !== Etat::CONFIRMED) {
            throw new \Exception('Unexpected status '.$rdv->getEtat()->getIdcode());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // COMMANDS : theses steps are "micro steps" to do things behind the scene
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function canClean($element) {
        return isset($this->clean[$element]) && $this->clean[$element];
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // HOOK : theses methods are called a specific time execution
    ///////////////////////////////////////////////////////////////////////////////////////////

    /** @BeforeScenario */
    public function beforeScenario(BeforeScenarioScope $scope) {
        $this->clean = array(
            'consultation' => true
        );
    }

    /** @AfterScenario */
    public function after(AfterScenarioScope $scope) {
        // Remove the created rooms
        $results = array();
        if ($this->canClean('consultation')) {
            $results += $this->cleanConsultations();
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

    private function cleanConsultations() {
        $results = [];
        $em = $this->getEntityManager();

        foreach ($this->users as $username => $user) {
            if($user['type'] == UserManager::TYPE_CLIENT) {
                $rdvs = $cbs = $preAuths = [];

                $rows = $em->createQuery('SELECT rdv.id AS rdv_id, preAuth.id AS pre_auth_id, cb.id AS cb_id FROM KGC\RdvBundle\Entity\RDV rdv LEFT JOIN rdv.cartebancaires cb LEFT JOIN rdv.preAuthorization preAuth WHERE rdv.client = '.$user['id'])->getResult();

                foreach ($rows as $row) {
                    $rdvs[$row['rdv_id']] = $row['rdv_id'];
                    if ($row['cb_id']) {
                        $cbs[$row['cb_id']] = $row['cb_id'];
                    }
                    if ($row['pre_auth_id']) {
                        $preAuths[$row['pre_auth_id']] = $row['pre_auth_id'];
                    }
                }

                if ($cbs) {
                    if ($count = $em->createQuery('DELETE KGC\RdvBundle\Entity\CarteBancaire cb WHERE cb.id IN ('.implode($cbs, ',').')')->getResult()) {
                        $results[] = $count.' credit card(s) cleaned';
                    }
                }

                if ($rdvs) {
                    if ($count = $em->createQuery('DELETE KGC\RdvBundle\Entity\RDV rdv WHERE rdv.id IN ('.implode($rdvs, ',').')')->getResult()) {
                        $results[] = $count.' consultation(s) cleaned';
                    }
                }

                if ($preAuths) {
                    if ($count = $em->createQuery('DELETE KGC\PaymentBundle\Entity\Authorization auth WHERE auth.id IN ('.implode($preAuths, ',').')')->getResult()) {
                        $results[] = $count.' pre-authorization(s) cleaned';
                    }
                }
            }
        }

        return $results;
    }
}
