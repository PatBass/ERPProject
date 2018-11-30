<?php

namespace KGC\ClientBundle\Transformer;

use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\Bundle\SharedBundle\Entity\Adresse;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\Bundle\SharedBundle\Service\WebsiteConfiguration;
use KGC\ClientBundle\Entity\Mail;
use KGC\ClientBundle\Entity\Sms;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\RDV;
use KGC\Bundle\SharedBundle\Entity\Client;

/**
 * @DI\Service("kgc.client.sms.transformer")
 */
class SmsTransformer
{
    const CUSTOMER_1STNAME_PLACEHOLDER = '[PRENOM]';
    const CUSTOMER_NAME_PLACEHOLDER = '[NOM]';

    const ADDRESS_1STLINE_PLACEHOLDER = '[ADRESSE1]';
    const ADDRESS_2NDLINE_PLACEHOLDER = '[ADRESSE2]';

    const DATE_CONSULTATION_PLACEHOLDER = '[DATE_CONSULTATION]';
    const AMOUNT_PLACEHOLDER = '[MONTANT]';
    const AMOUNT_TEXT_PLACEHOLDER = '[MONTANT-TEXT]';
    const AMOUNTTOPAY_PLACEHOLDER = '[MONTANTD]';
    const AMOUNTTOPAY_TEXT_PLACEHOLDER = '[MONTANTD-TEXT]';
    const AMOUNTTOPAY_WITHCOSTS_PLACEHOLDER = '[MONTANTDF]';
    const AMOUNTTOPAY_WITHCOSTS_TEXT_PLACEHOLDER = '[MONTANTDF-TEXT]';
    const PAYMENT_PLACEHOLDER = '[MOYEN_PAIEMENT]';
    const ARRANGEMENTS_PLACEHOLDER = '[ARRANGEMENTS]';

    const WEBSITE_PLACEHOLDER = '[SITE]';
    const PHONE_PLACEHOLDER = '[TELEPHONE]';
    const PSYCHIC_PLACEHOLDER = '[VOYANT]';
    const LOGO_PLACEHOLDER = '[LOGO]';
    const PAYMENT_IMG_PLACEHOLDER = '[IMAGE_PAIEMENT]';

    const CURRENT_DATE_PLACEHOLDER = '[AUJOURDHUI]';
    const LIEN_PAIEMENT = '[LIEN_PAIEMENT]';

    const IMAGES_PATH_PREFIX = 'img/mails';

    const COSTS = 30;

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var WebsiteConfiguration
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $imgPrefix;


    protected function getCommonParameters($origin = null)
    {
        $suffix = $origin ? SharedWebsiteManager::getSlugFromReference($origin) : null;

        return [
            'siteUrl' => $this->configuration->get([$origin ?: 'default', 'siteUrl']),
            'sitePrefix' => $this->configuration->get([$origin ?: 'default', 'sitePrefix']).'/'.$suffix
        ];
    }

    /**
     * @param array     $data
     * @param bool|true $isHtml
     *
     * @return string
     */
    protected function buildArrangementList(array $data, $isHtml = true)
    {
        $list = '';

        if ($isHtml) {
            foreach ($data as $line) {
                $date = $line['date'];
                $date = $date->format('d/m/Y');
                $list .= '<li>'.$line['amount'].' € le '.$date.' par '.$line['payment'].'</li>';
            }
            $list = '<ul>'.$list.'</ul>';
        } else {
            foreach ($data as $line) {
                $date = $line['date'];
                $date = $date->format('d/m/Y');
                $list .= '- '.$line['amount'].' € le '.$date.' par '.$line['payment'] + "\n";
            }
        }

        return $list;
    }

    /**
     * @param $string
     * @param $search
     * @param $replace
     *
     * @return mixed
     */
    protected function replaceMatch($string, $search, $replace)
    {
        return str_replace($search, $replace, $string);
    }

    /**
     * @param $rdvId
     *
     * @return mixed
     */
    protected function getRdvAndWebsite($rdvId)
    {
        $rdv = $this->entityManager
            ->getRepository('KGCRdvBundle:RDV')
            ->findRdvWebsite($rdvId)
        ;

        return $rdv;
    }

    /**
     * @param RDV $rdv
     *
     * @return array
     */
    protected function getPaymentInfo(RDV $rdv)
    {
        $payment = '';
        $first = true;
        $amountTotal = $rdv->getTarification() ? $rdv->getTarification()->getMontantTotal() : 0;
        $amountPaid = 0;
        $arrangements = [];

        foreach ($rdv->getEncaissements() as $e) {
            if (Encaissement::DONE === $e->getEtat()) {
                // Total paid amount to calculate : amount to pay
                $amountPaid += $e->getMontant();
            } else {
                // We want the payment type of the first NOT DONE payment.
                if ($first) {
                    $first = false;
                    $payment = $e->getMoyenPaiement() ? $e->getMoyenPaiement()->getLibelle() : $payment;
                }
                // We want a list of all "arrangements"
                if (Encaissement::PLANNED === $e->getEtat()) {
                    $arrangements[] = [
                        'amount' => number_format($e->getMontant(), 2, ',', ' '),
                        'date' => $e->getDate(),
                        'payment' => $e->getMoyenPaiement() ? $e->getMoyenPaiement()->getLibelle() : '',
                    ];
                }
            }
        }

        $amountToPaid = $amountTotal - $amountPaid;

        return [$payment, $amountTotal, $amountToPaid, $arrangements];
    }

    /**
     * @param Client $client
     * @param $html
     * @param $text
     *
     * @return array
     */
    protected function replaceClientInfo(Client $client, $html, $text)
    {

        $html = $this->replaceMatch($html, self::CUSTOMER_NAME_PLACEHOLDER, $client->getNom());
        $text = $this->replaceMatch($text, self::CUSTOMER_NAME_PLACEHOLDER, $client->getNom());

        $html = $this->replaceMatch($html, self::CUSTOMER_1STNAME_PLACEHOLDER, $client->getPrenom());
        $text = $this->replaceMatch($text, self::CUSTOMER_1STNAME_PLACEHOLDER, $client->getPrenom());
        if($client->getAdresses()->count()) {
            list($html, $text) = $this->replaceAddress($html, $text, $client->getAdresses()->last());
        } else {
            list($html, $text) = $this->replaceAddress($html, $text, new Adresse());
        }
        return [$html, $text];
    }

    /**
     * @param RDV $rdv
     * @param $html
     * @param $text
     *
     * @return array
     */
    protected function replaceRdvInfo(RDV $rdv, $html, $text)
    {
        $customer = $rdv->getClient();
        $date = $rdv->getDateConsultation()->format('d/m/Y');
        $psychic = !is_null($rdv->getVoyant())? $rdv->getVoyant()->getNom() : '';
        list($payment, $amount, $amountToPay, $arrangements) = $this->getPaymentInfo($rdv);
        $arrangementsHtml = $this->buildArrangementList($arrangements);
        $arrangementsTxt = $this->buildArrangementList($arrangements, false);
        $context = $this->getCommonParameters();

        list($html, $text) = $this->replaceClientInfo($customer, $html, $text);

        $html = $this->replaceMatch($html, self::LIEN_PAIEMENT, $context['siteUrl'].$context['sitePrefix'].'validate-cb/'.$rdv->getNewCardHash());
        $text = $this->replaceMatch($text, self::LIEN_PAIEMENT, $context['siteUrl'].$context['sitePrefix'].'validate-cb/'.$rdv->getNewCardHash());

        $html = $this->replaceMatch($html, self::DATE_CONSULTATION_PLACEHOLDER, $date);
        $text = $this->replaceMatch($text, self::DATE_CONSULTATION_PLACEHOLDER, $date);

        $html = $this->replaceMatch($html, self::PSYCHIC_PLACEHOLDER, $psychic);
        $text = $this->replaceMatch($text, self::PSYCHIC_PLACEHOLDER, $psychic);

        $html = $this->replaceMatch($html, self::PAYMENT_PLACEHOLDER, $payment);
        $text = $this->replaceMatch($text, self::PAYMENT_PLACEHOLDER, $payment);

        $html = $this->replaceMatch($html, self::AMOUNT_PLACEHOLDER, number_format($amount, 2, ',', ' '));
        $text = $this->replaceMatch($text, self::AMOUNT_PLACEHOLDER, number_format($amount, 2, ',', ' '));

        $html = $this->replaceMatch($html, self::AMOUNT_TEXT_PLACEHOLDER, $this->printNumberToText($amount));
        $text = $this->replaceMatch($text, self::AMOUNT_TEXT_PLACEHOLDER, $this->printNumberToText($amount));

        $html = $this->replaceMatch($html, self::AMOUNTTOPAY_PLACEHOLDER, number_format($amountToPay, 2, ',', ' '));
        $text = $this->replaceMatch($text, self::AMOUNTTOPAY_PLACEHOLDER, number_format($amountToPay, 2, ',', ' '));

        $html = $this->replaceMatch($html, self::AMOUNTTOPAY_TEXT_PLACEHOLDER, $this->printNumberToText($amountToPay));
        $text = $this->replaceMatch($text, self::AMOUNTTOPAY_TEXT_PLACEHOLDER, $this->printNumberToText($amountToPay));

        $html = $this->replaceMatch($html, self::AMOUNTTOPAY_WITHCOSTS_PLACEHOLDER, number_format($amountToPay + self::COSTS, 2, ',', ' '));
        $text = $this->replaceMatch($text, self::AMOUNTTOPAY_WITHCOSTS_PLACEHOLDER, number_format($amountToPay + self::COSTS, 2, ',', ' '));

        $html = $this->replaceMatch($html, self::AMOUNTTOPAY_WITHCOSTS_TEXT_PLACEHOLDER, $this->printNumberToText($amountToPay+ self::COSTS));
        $text = $this->replaceMatch($text, self::AMOUNTTOPAY_WITHCOSTS_TEXT_PLACEHOLDER, $this->printNumberToText($amountToPay+ self::COSTS));

        $html = $this->replaceMatch($html, self::ARRANGEMENTS_PLACEHOLDER, $arrangementsHtml);
        $text = $this->replaceMatch($text, self::ARRANGEMENTS_PLACEHOLDER, $arrangementsTxt);

        list($html, $text) = $this->replaceAddress($html, $text, $rdv->getAdresse());

        return [$html, $text];
    }

    /**
     * @param $html
     * @param $text
     * @param Website|null $website
     *
     * @return array
     */
    protected function replacePhone($html, $text, Website $website = null)
    {
        $replace = $website ? $website->getPhone() : '';
        $replace = $replace ? 'Tel : '.$replace : '';

        $html = $this->replaceMatch($html, self::PHONE_PLACEHOLDER, $replace);
        $text = $this->replaceMatch($text, self::PHONE_PLACEHOLDER, $replace);

        return [$html, $text];
    }

    /**
     * @param $html
     * @param $text
     * @param Adresse|null $address
     *
     * @return array
     */
    protected function replaceAddress($html, $text, Adresse $address)
    {
        $html = $this->replaceMatch($html, self::ADDRESS_1STLINE_PLACEHOLDER, $address->getVoie());
        $text = $this->replaceMatch($html, self::ADDRESS_1STLINE_PLACEHOLDER, $address->getVoie());

        $html = $this->replaceMatch($html, self::ADDRESS_2NDLINE_PLACEHOLDER, $address->getCodepostal().' '.$address->getVille());
        $text = $this->replaceMatch($html, self::ADDRESS_2NDLINE_PLACEHOLDER, $address->getCodepostal().' '.$address->getVille());

        return [$html, $text];
    }

    /**
     * @param $html
     * @param $text
     * @param Website|null $website
     *
     * @return array
     */
    protected function replaceWebsite($html, $text, Website $website = null)
    {
        $replace = $website ? $website->getUrl() : '';
        $replace = $replace ?: '';

        if (!empty($replace)) {
            $replace = '<a href="'.$replace.'" alt="'.$replace.'">'.$replace.'</a>';
        }

        $html = $this->replaceMatch($html, self::WEBSITE_PLACEHOLDER, $replace);
        $text = $this->replaceMatch($text, self::WEBSITE_PLACEHOLDER, $replace);

        return [$html, $text];
    }

    /**
     * @param $baseUrl
     * @param $html
     * @param $text
     * @param Website|null $website
     *
     * @return array
     */
    protected function replaceLogo($baseUrl, $html, $text, Website $website = null)
    {
        $replace = $website ? sprintf('%s/%s/%s', $baseUrl, self::IMAGES_PATH_PREFIX, $website->getLogo()) : '';
        $replace = $replace ?: '';

        if (!empty($replace)) {
            $replace = '<img src="'.$replace.'" alt="Logo '.$website->getLibelle().'" width="300" />';
        }

        $html = $this->replaceMatch($html, self::LOGO_PLACEHOLDER, $replace);
        $text = $this->replaceMatch($text, self::LOGO_PLACEHOLDER, $replace);

        return [$html, $text];
    }

    /**
     * @param $baseUrl
     * @param $html
     * @param $text
     *
     * @return array
     */
    protected function replaceImage($baseUrl, $html, $text)
    {
        $replace = sprintf('%s/%s/paiement.jpg', $baseUrl, self::IMAGES_PATH_PREFIX);
        $replace = '<img src="'.$replace.'" alt="Image aide paiement" />';

        $html = $this->replaceMatch($html, self::PAYMENT_IMG_PLACEHOLDER, $replace);
        $text = $this->replaceMatch($text, self::PAYMENT_IMG_PLACEHOLDER, $replace);

        return [$html, $text];
    }

    /**
     * @param integer $n
     *
     * @return string
     */
    protected function printNumberToText($n)
    {
        $nf = new \NumberFormatter("fr-FR", \NumberFormatter::SPELLOUT);
        $s = $nf->format($n);
        $s = str_replace('-', ' ', $s);
        $s = ucwords($s);

        return $s;
    }

    /**
     * @param ObjectManager $entityManager
     *
     * @DI\InjectParams({
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "configuration" = @DI\Inject("kgc.shared.website.configuration"),
     * })
     */
    public function __construct(ObjectManager $entityManager, WebsiteConfiguration $configuration)
    {
        $this->entityManager = $entityManager;
        $this->configuration = $configuration;
    }

    /**
     * @param $baseUrl
     * @param Sms $sms
     * @param $rdvId
     *
     * @return Sms
     */
    public function transform($baseUrl, Sms $sms, $rdvId, $rdv = null)
    {
        $html = $sms->getText();
        $text = $sms->getText();
        if(!is_null($rdvId)) {
            $rdv = $this->getRdvAndWebsite($rdvId);
        }
        $website = $rdv->getWebsite();

        list($html, $text) = $this->replaceRdvInfo($rdv, $html, $text);

        list($html, $text) = $this->replaceWebsite($html, $text, $website);
        list($html, $text) = $this->replacePhone($html, $text, $website);
        list($html, $text) = $this->replaceLogo($baseUrl, $html, $text, $website);
        list($html, $text) = $this->replaceImage($baseUrl, $html, $text);

        $html = $this->replaceMatch($html, self::CURRENT_DATE_PLACEHOLDER, date('d/m/Y'));
        $text = $this->replaceMatch($text, self::CURRENT_DATE_PLACEHOLDER, date('d/m/Y'));

        $sms->setText($text);

        return $sms;
    }

    /**
     * @param $clientId
     *
     * @return mixed
     */
    protected function getClientEntity($clientId)
    {
        $client = $this->entityManager
            ->getRepository('KGCSharedBundle:Client')
            ->find($clientId)
        ;

        return $client;
    }

    /**
     * @param Client $client
     *
     * @return mixed
     */
    protected function getWebsiteClient(Client $client)
    {
        $website = $this->entityManager
            ->getRepository('KGCSharedBundle:Client')
            ->getWebsiteByCLient($client)
        ;

        return $website;
    }
    
    /**
     * @param $baseUrl
     * @param Sms $sms
     * @param $clientId
     *
     * @return Sms
     */
    public function transformChat($baseUrl, Sms $sms, $clientId)
    {
        $html = $sms->getText();
        $text = $sms->getText();
        $client = $this->getClientEntity($clientId);
        $website = $this->getWebsiteClient($client);

        list($html, $text) = $this->replaceClientInfo($client, $html, $text);

        list($html, $text) = $this->replaceWebsite($html, $text, $website);
        list($html, $text) = $this->replacePhone($html, $text, $website);
        list($html, $text) = $this->replaceLogo($baseUrl, $html, $text, $website);
        list($html, $text) = $this->replaceImage($baseUrl, $html, $text);

        $html = $this->replaceMatch($html, self::CURRENT_DATE_PLACEHOLDER, date('d/m/Y'));
        $text = $this->replaceMatch($text, self::CURRENT_DATE_PLACEHOLDER, date('d/m/Y'));

        $sms->setText($text);

        return $sms;
    }
}
