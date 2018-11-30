<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ChatBundle\Entity\ChatFormula;
use KGC\ChatBundle\Entity\ChatFormulaRate;

/**
 * @DI\Service("kgc.chat.website.manager")
 */
class WebsiteManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SharedWebsiteManager
     */
    protected $sharedWebsiteManager;

    /**
     * @param EntityManager $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "sharedWebsiteManager" = @DI\Inject("kgc.shared.website.manager")
     * })
     */
    public function __construct(EntityManager $em, SharedWebsiteManager $sharedWebsiteManager)
    {
        $this->em = $em;
        $this->sharedWebsiteManager = $sharedWebsiteManager;
    }

    /**
     * Convert website to json array.
     *
     * @param Website $website
     *
     * @return JSON like array
     */
    public function convertWebsiteToJsonArray(Website $website)
    {
        return array(
            'id' => $website->getId(),
            'libelle' => $website->getLibelle(),
            'url' => $website->getUrl(),
            'reference' => $website->getReference(),
        );
    }

    /**
     * Get available psychics by website.
     *
     * @param int $website_id
     *
     * @return JSON like array
     */
    public function getWebsitesWithAvailableVirtualPsychics($website_id = null)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'websites' => array(),
        );

        $WebsiteRepository = $this->em->getRepository('KGCSharedBundle:Website');
        $UtilisateurRepository = $this->em->getRepository('KGCUserBundle:Utilisateur');
        $VoyantRepository = $this->em->getRepository('KGCUserBundle:Voyant');

        // First get all virtual psychics
        $json['websites'] = $WebsiteRepository->getWebsitesAndPsychicsAsArray($website_id);

        // Then we need to know all physicals psychics who can start a conversation
        $result = $UtilisateurRepository->findAvailableUsersForChat(true);

        $users = $psychicIsBusy = $sexIsBusy = [];

        foreach ($result as $row) {
            $users[] = $row[0];
            $chatType = $row[0]->getChatType();
            $psychicIsBusy[$row[0]->getId()] = $isBusy = $row['nbChats'] >= $chatType->getMaxClient();
            if (empty($sexIsBusy[$chatType->getId()])) {
                $sexIsBusy[$chatType->getId()][$row[0]->getSexe()] = $isBusy;
            }
        }

        // Then use $users to know which websites are availables
        // We could have made only one method to get websites (request internally users available)
        // But to do this, we would have to make subqueries ...
        // For more comprehension and control, I choose to separate logic in two requests
        // So if you want to add contraints on available users, you can do it here

        $availables_virtual_psychics = $VoyantRepository->getAvailableVirtualPsychics($users, $website_id);

        // Make the link between websites and available virtual psychics
        foreach ($availables_virtual_psychics as $virtual_psychic) {
            if (isset($json['websites'][$virtual_psychic->getWebsite()->getId()]['psychics'][$virtual_psychic->getId()])) {
                $json['websites'][$virtual_psychic->getWebsite()->getId()]['psychics'][$virtual_psychic->getId()]['is_available'] = true;

                if ($utilisateur = $virtual_psychic->getUtilisateur()) {
                    $json['websites'][$virtual_psychic->getWebsite()->getId()]['psychics'][$virtual_psychic->getId()]['is_busy'] = isset($psychicIsBusy[$utilisateur->getId()]) ? $psychicIsBusy[$utilisateur->getId()] : false;
                    $json['websites'][$virtual_psychic->getWebsite()->getId()]['psychics'][$virtual_psychic->getId()]['real_psy_id'] = $utilisateur->getId();
                } else {
                    $chatTypeId = $virtual_psychic->getWebsite()->getChatFormula()->getChatType()->getId();
                    $json['websites'][$virtual_psychic->getWebsite()->getId()]['psychics'][$virtual_psychic->getId()]['is_busy'] = isset($sexIsBusy[$chatTypeId][$virtual_psychic->getSexe()]) ? $sexIsBusy[$chatTypeId][$virtual_psychic->getSexe()] : false;
                    $json['websites'][$virtual_psychic->getWebsite()->getId()]['psychics'][$virtual_psychic->getId()]['real_psy_id'] = null;
                }
            }
        }

        // For better handling after, set knowns virtual psychics to false if they are not available
        // Also add slug to websites
        foreach ($json['websites'] as &$website) {
            $website['slug'] = SharedWebsiteManager::getSlugFromReference($website['reference']);
            foreach ($website['psychics'] as &$psychic) {
                if (!isset($psychic['is_available']) || $psychic['is_available'] !== true) {
                    $psychic['is_available'] = false;
                }
                if (!isset($psychic['is_busy']) || $psychic['is_busy'] !== true) {
                    $psychic['is_busy'] = false;
                }
            }
        }

        $json['status'] = 'OK';
        $json['message'] = 'Websites retrieved';

        return $json;
    }

    /**
     * Get formulas by website.
     *
     * @param string $website_slug
     * @param bool   $even_expired If true, get also formulas which are expired
     *
     * @return JSON like array
     */
    public function getFormulas($website_slug, $even_expired = false, Client $client = null)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'formulas' => array(),
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($website_slug);

        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        $formulas = $this->em->getRepository('KGCChatBundle:ChatFormula')->findByWebsite($website->getId(), $even_expired);

        if ($client !== null) {
            $filtered_formulas = array();

            $chatPayments = $this->em->getRepository('KGCChatBundle:ChatPayment')->findByClientAndWebsite($client, $website);

            // Never bought at least discovery offers
            $never_bought_any_offer = true;

            // Nver bought at least not discovery offers
            $never_bought_real_offers = true;

            // Search in client's payment history for already bought offers
            foreach ($chatPayments as $chatPayment) {
                if (!$chatPayment->getChatFormulaRate()->isFreeOffer()) {
                    $never_bought_any_offer = false;
                    if (!$chatPayment->getChatFormulaRate()->isDiscovery()) {
                        $never_bought_real_offers = false;
                        break;
                    }
                }
            }

            foreach ($formulas as $formula) {
                if ($never_bought_any_offer) {
                    // If client had never buy a formula, show him discovery offers if they exists,
                    if ($formula->hasDiscoveryOffer()) {
                        $formula->keepOnlyDiscoveryOffers();
                    }
                    // Or standard offer
                    else {
                        // Keep only standard offer
                        $formula->keepOnlyStandardOffers();
                    }
                } elseif ($never_bought_real_offers) {
                    // If we are here, client has bought at least one discovery offer, show him the standard one
                    $formula->keepOnlyStandardOffers();
                } else {
                    // Client has already bought at least one real offer, we can show him standard and premium offers
                    // A.K.A "no discovery" offers if it's a unit website or if we don't know the type
                    if ($website->getType() === Website::TYPE_UNIT || $website->getType() === null) {
                        $formula->removeDiscoveryOffers();
                    }
                    // Or "neither discovery nor subscription" if it's a subscription website (he already subscribed one with discovery automatically)
                    elseif ($website->getType() === Website::TYPE_SUBSCRIPTION) {
                        $formula->removeDiscoveryAndSubscriptionOffers();
                    }
                }

                $filtered_formulas[] = $formula;
            }

            // Because we have altered formulas by filtering them, we can't anymore persist them without damages
            // Detach them to be sure no one could persist them after
            foreach ($filtered_formulas as $formula) {
                $this->em->detach($formula);
            }

            $json['formulas'] = $filtered_formulas;

            $json['cards'] = $client->getCartebancairesForTchat();
        } else {
            $json['formulas'] = $formulas;
        }

        $json['status'] = 'OK';
        $json['message'] = 'Formulas retrieved';

        return $json;
    }

    /**
     * Get unique formula rate by website and type.
     *
     * @param string $website_slug
     * @param int $type
     *
     * @return JSON like array
     */
    public function getUniqueFormulaRate($website_slug, $type, $flexible = false)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'formulaRate' => array(),
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($website_slug);
        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        $formulaRate = $this->em->getRepository('KGCChatBundle:ChatFormulaRate')->findOneByWebsiteAndType($website, $type, $flexible);

        $json['formulaRate'] = $formulaRate;
        $json['status'] = 'OK';
        $json['message'] = 'Formulas retrieved';

        return $json;
    }

    /**
     * Convert formula to JSON array.
     *
     * @param ChatFormula $formula
     *
     * @return JSON like array
     */
    public function convertFormulaToJsonArray(ChatFormula $formula)
    {
        $json_array = array(
            'id' => (int) $formula->getId(),
            'chat_type' => $formula->getChatType()->toJsonArray(),
            'formula_rates' => array(),
        );

        foreach ($formula->getChatFormulaRates() as $rate) {
            $json_array['formula_rates'][] = $rate->toJsonArray();
        }

        $json_array['has_promotion'] = $this->em->getRepository('KGCChatBundle:ChatPromotion')->hasPromotionCompatibleWithChatFormula($formula);

        return $json_array;
    }

    /**
     * Short method for convertFormulaToJsonArray.
     */
    public function convertFormulasToJsonArray($formulas = array())
    {
        $json_array = array();
        foreach ($formulas as $formula) {
            $json_array[] = $this->convertFormulaToJsonArray($formula);
        }

        return $json_array;
    }

    /**
     * Convert cbs to JSON array.
     *
     * @param array $cards
     *
     * @return JSON like array
     */
    public function convertCbToJsonArray($cards = array())
    {
        $json_array = [];
        foreach ($cards as $card) {
            $json_array[] = [
                'id' => (int) $card->getId(),
                'name' => (string) $card->getNom(),
                'expiredAt' => (string) $card->getExpiration(),
            ];
        }

        return $json_array;
    }

    /**
     * Check if client is not allowed on this website.
     *
     * @param Client  $client
     * @param Website $website
     *
     * @return string|false
     */
    public function checkClientIsNotAllowed(Client $client, Website $website)
    {
        if ($client->getOrigin() != $website->getReference()) {
            return 'Invalid client origin';
        }

        return false;
    }
}
