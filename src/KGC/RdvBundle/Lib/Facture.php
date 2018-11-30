<?php

namespace KGC\RdvBundle\Lib;

use Doctrine\Common\Persistence\ObjectManager;
use KGC\RdvBundle\Entity\RDV;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class Facture.
 *
 * @DI\Service("kgc.billing.service")
 */
class Facture extends \FPDF
{
    const TVA_CONSULTATION = 9.17;
    const TVA_OBJET = 20;

    /**
     * @var array
     */
    protected $products = [];

    /**
     * @var array
     */
    protected $prices = [];

    /**
     * @var float
     */
    protected $totalTTC;

    /**
     * @var float
     */
    protected $totalHT;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string numéro de facture
     */
    protected $num = 'undefined';

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     */
    protected $rdv;

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @param $rootDir
     *
     * @DI\InjectParams({
     *     "rootDir"   = @DI\Inject("%kernel.root_dir%"),
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct($rootDir, ObjectManager $entityManager)
    {
        parent::__construct();
        $this->SetAutoPageBreak(true, 5);
        $this->rootDir = $rootDir;
        $this->entityManager = $entityManager;
    }

    public function create(RDV $rdv)
    {
        $this->rdv = $this->getRdvAndWebsite($rdv->getId());

        $this->initByRdv();
        $this->buildPdf();
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

    private function initByRdv()
    {
        if (is_object($this->rdv)) {
            if ($this->rdv->getEtat()->getId() >= 5) {
                $totalAmount = $this->rdv->getTarification()->getMontantTotal();
                $minutesAmount = $this->rdv->getTarification()->getMontantMinutes();
                $leftAmount = $totalAmount - $minutesAmount;

                $this->products = ['Consultation de voyance - '.$this->rdv->getTarification()->getTemps().' minutes'];
                $this->prices = [$minutesAmount];

                if ($leftAmount > 0) {
                    $this->products[] = 'Vente de produits';
                    $this->prices[] = $leftAmount;
                }

                $this->num = sprintf('%s0%s', $this->rdv->getClient()->getId(), $this->rdv->getId());
            }
        }
    }

    public function SetProducts($products)
    {
        $this->products = $products;

        return $this;
    }

    public function SetPrices($prices)
    {
        $this->prices = $prices;

        return $this;
    }

    public function SetNum($num)
    {
        $this->num = $num;

        return $this;
    }

    public function GetTotal()
    {
        foreach ($this->prices as $price) {
            $this->totalTTC += $price;
        }
        $this->totalHT = $this->totalTTC / (1 + (self::TVA_OBJET / 100));
        $this->totalTTC = number_format($this->totalTTC, 2, ',', ' ');
        $this->totalHT = number_format($this->totalHT, 2, ',', ' ');
    }

    private function buildPdf()
    {
        $site = $this->rdv->getWebsite();
        $contact = $site->getUrl();
        $phone = $site->getPhone();
        $logo = $site->getLogo();
        $logo = $this->rootDir.'/../web/img/mails/'.$logo;

        $this->AddPage();
        $this->Image($logo, 10, 10, 70);
        $this->SetFont('Helvetica', 'B', 18);
        $this->SetTextColor(183, 199, 247);
        $this->Cell(180, 10, 'FACTURE', 0, 1, 'R');
        $this->SetFont('Helvetica', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(180, 5, 'Numero '.$this->num, 0, 1, 'R');
        $this->SetFont('Helvetica', 'B', 12);
        $this->Cell(180, 20, 'Date : '.date('d/m/Y'), 0, 1, 'R');
        $this->SetFont('Helvetica', '', 12);
        $this->SetY(85);
        $this->SetFillColor(183, 199, 247);
        $this->Cell(150, 7, 'DESCRIPTION', 1, 0, 'C', 1);
        $this->Cell(35, 7, 'MONTANT', 1, 1, 'C', 1);
        // We keep the Y data, to know were we have to start both products and prices tables.
        $yBefore = $this->GetY();
        // Here we print products
        $startYProduct = $yBefore;
        foreach ($this->products as $product) {
            $startYProduct += 7;
            $this->Cell(150, 7, utf8_decode($product), 'LB', 0);
            $this->SetXY(10, $startYProduct);
        }
        $this->SetXY(160, $yBefore);
        // Here we print prices
        $startPrices = $yBefore;
        foreach ($this->prices as $price) {
            $startPrices += 7;
            $this->Cell(35, 7, number_format($price, 2, ',', ' ').' '.chr(128), 'LBR', 0, 'R');
            $this->SetXY(160, $startPrices);
        }

        $this->GetTotal();
        $this->SetX(115);
        $this->SetFont('Helvetica', '', 12);
        $this->Cell(45, 7, 'TOTAL HT', 1, 0, 'C', 1);
        $this->Cell(35, 7, $this->totalHT.' '.chr(128), 1, 2, 'R');
        $this->SetX(115);
        $this->Cell(45, 7, 'TOTAL TTC', 1, 0, 'C', 1);
        $this->SetFont('Helvetica', 'B', 12);
        $this->Cell(35, 7, $this->totalTTC.' '.chr(128), 1, 2, 'R');
        $this->SetX(115);
        $this->SetY($this->getY() + 30);
        $this->SetFont('Helvetica', '', 10);

        $this->Cell(0, 10, 'Pour toute question concernant cette facture, veuillez contacter '.$contact.' au '.$phone.'.', 0, 0, 'C');
        $this->SetY($this->getY() + 15);
        $this->SetFont('Helvetica', 'B', 12);
        $this->Cell(190, 10, 'Merci de votre confiance !', 0, 0, 'C');

        $this->SetFont('Helvetica', '', 10);
        $this->SetY(290);
        $this->Cell(0, 0, 'SAS KGCOM - RCS : Lyon B 538 563 917 - Siret : 53856391700029 - 40 rue de Bruxelles 69100 Villeurbanne', 0, 0, 'C');
    }
}
