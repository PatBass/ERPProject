<?php

namespace KGC\StatBundle\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Entity\StatisticRenderingRule;
use KGC\StatBundle\Form\StatDateType;
use KGC\StatBundle\Form\StatisticColumnType;
use KGC\StatBundle\Form\StatScopeType;

/**
 * Class SpecificDecorator.
 *
 * @DI\Service("kgc.stat.decorator.specific")
 */
class SpecificDecorator implements DecoratorInterface
{
    const DEFAULT_RENDERING_COLOR = "#000000";

    const LABEL_CATEGORY_CA = 'CA';
    const LABEL_CATEGORY_RDV = 'RDV';
    const LABEL_ROW_PHONIST = 'Phoniste';
    const LABEL_ROW_CONSULTANT = 'Consultant';
    const LABEL_ROW_WEBSITE = 'Site';
    const LABEL_ROW_SOURCE = 'Source';
    const LABEL_ROW_URL = 'Url';
    const LABEL_ROW_CODEPROMO = 'Code promo';
    const LABEL_ROW_SUPPORT = 'Support';
    const LABEL_ROW_PROPRIO = 'Propriétaires';
    const LABEL_ROW_REFLEX_AFFILIATE = 'Affiliate (Reflex)';
    const LABEL_ROW_REFLEX_SOURCE = 'Source (Reflex)';

    private $TABLE_HEADERS = [
        [
            'code' => StatisticColumnType::CODE_RDV_TOTAL,
            'label' => 'Total',
            'descr' => 'Nombre de consultation effectuées sur la période. (Annulés + Traités)',
            'ratio' => false, 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_CANCELLED,
            'label' => 'Annulés',
            'descr' => 'Nombre de consultation annulés effectuées sur la période.',
            'ratio' => true , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_510_MIN,
            'label' => '5x10min',
            'descr' => 'Nombre de consultation annulés effectuées sur la période pour cause de 5 consultations gratuites ont déjà été effectuées.',
            'ratio' => false , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_CB_BELGE,
            'label' => 'CB Belge',
            'descr' => 'Nombre de consultation annulés effectuées sur la période pour cause de carte bleue Belge.',
            'ratio' => false , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_CB_VIDE,
            'label' => 'CB non remplie',
            'descr' => 'Nombre de consultation annulés effectuées sur la période pour cause de carte bleue non renseignée.',
            'ratio' => false , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_FNA,
            'label' => 'FNA',
            'descr' => 'Nombre de consultation annulés effectuées sur la période pour cause de FNA.',
            'ratio' => false , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_NRP,
            'label' => 'NRP',
            'descr' => 'Nombre de consultation annulés effectuées sur la période cas ne répond pas.',
            'ratio' => false , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_NVP,
            'label' => 'NVP',
            'descr' => 'Nombre de consultation annulés effectuées sur la période car ne veux plus.',
            'ratio' => false , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_REFUSED_SECU,
            'label' => 'Refus sécu',
            'descr' => 'Nombre de consultation annulées effectuées sur la période pour cause de refus de sécurisation.',
            'ratio' => false , 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_WAITORREPORT,
            'label' => 'En attente',
            'descr' => 'Nombre de consultation en attente ou reportées effectuées sur la période.',
            'ratio' => false, 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_TREATED,
            'label' => 'Traités',
            'descr' => 'Nombre de consultation non annulées effectuées sur la période. (10min + >10min)',
            'ratio' => true, 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_TEN_MINUTES,
            'label' => '10min',
            'descr' => 'Nombre de consultation non annulées effectuées sur la période dont le temps tarifé est <= à 10min.',
            'ratio' => true, 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_OVER_TEN_MINUTES,
            'label' => '>10min',
            'descr' => 'Nombre de consultation non annulées effectuées sur la période dont le temps tarifé est > à 10min. (Validés + Impayés).',
            'ratio' => true, 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_VALIDATED,
            'label' => 'Validés',
            'descr' => 'Nombre de consultations non annulés de plus de 10min dont au moins un encaissement a été percut.',
            'ratio' => true, 'isCa' => false, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_RDV_UNPAID,
            'label' => 'Impayés',
            'descr' => 'Nombre de consultations non annulés de plus de 10min dont aucun encaissement à été percut.',
            'ratio' => true, 'isCa' => false, 'details' => true
        ],

        [
            'code' => StatisticColumnType::CODE_CA_REAL,
            'label' => 'Réalisé',
            'descr' => 'Somme des tarifications des consultations effectuées dans  la période. (Impayés + Enc MM).',
            'ratio' => false, 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_UNPAID,
            'label' => 'Impayé',
            'descr' => 'Somme des tarification des consultations effectuées dans la période qu\'il reste à payer (Ca real - Enc MM).',
            'ratio' => true, 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_ENC,
            'label' => 'Encaissé',
            'descr' => 'Somme des encaissements perçus dans la période. (Enc MM + Recup MP)',
            'ratio' => true, 'symbol' => '€', 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_ENC_MM,
            'label' => 'Encaissé MM',
            'descr' => 'Somme des encaissements perçus dans période (Ca real - Impayés), liés à des consultations du mois de la période.',
            'ratio' => true, 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_ENC_JM,
            'label' => 'Encaissé JM',
            'descr' => 'Somme des encaissements perçus dans période (Ca real - Impayés), liés à des consultations du même jour.',
            'ratio' => false, 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_RECUP_MM,
            'label' => 'Récup MM',
            'descr' => 'Somme des encaissements perçus dans période (Ca real - Impayés), liés à des consultations d\'un jour précédent mais dans la période.',
            'ratio' => false, 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_RECUP_MP,
            'label' => 'Recup MP',
            'descr' => 'Somme des encaissements perçus dans la période, liés à des consultations d\'un mois antérieures à la période.',
            'ratio' => false, 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_ENC_OPPO,
            'label' => 'Dont oppositions',
            'descr' => 'Somme des encaissements opposés lors de la période.',
            'ratio' => true, 'symbol' => '€', 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_ENC_REFUND,
            'label' => 'Dont remboursements',
            'descr' => 'Somme des encaissements remboursés lors de la période.',
            'ratio' => true, 'symbol' => '€', 'isCa' => true, 'details' => true
        ],
        [
            'code' => StatisticColumnType::CODE_CA_ENC_SECU,
            'label' => 'Sécurisation',
            'descr' => 'Somme des sécurisations perçus dans la période.',
            'ratio' => false, 'symbol' => '€', 'isCa' => true, 'details' => false
        ],
        [
            'code' => StatisticColumnType::CODE_CA_AVERAGE_REAL,
            'label' => 'Moyenne réa.',
            'descr' => 'Moyenne de CA encaissée par consultation effectuées dans la période.',
            'ratio' => false, 'isCa' => true, 'details' => false
        ],
        [
            'code' => StatisticColumnType::CODE_CA_AVERAGE_PAYMENT,
            'label' => 'Moyenne paie.',
            'descr' => 'Moyenne paie.',
            'ratio' => false, 'isCa' => true, 'details' => false
        ]
    ];

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param EntityManagerInterface $em
     * @DI\InjectParams({
     *                                   "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *                                   })
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param $data
     * @param $config
     * @return mixed
     */
    protected function setTableName(&$data, $config)
    {
        $data['label'] = $config['begin']->format('d/m/Y')
            . "-" . $config['end']->format('d/m/Y')
            . " [";
        if($config['statScope'] === StatScopeType::KEY_CONSULTATION) {
            $data['label'] = $data['label'] . 'Consultations';
        }
        else if($config['statScope'] === StatScopeType::KEY_FOLLOW) {
            $data['label'] = $data['label'] . 'Suivis';
        }
        else if($config['statScope'] === StatScopeType::KEY_ALL) {
            $data['label'] = $data['label'] . 'Consultations + Suivis';
        }
        $data['label'] = $data['label'] . "]";

        if(isset($config['dateType'])) {
            if($config['dateType'] == StatDateType::KEY_DATE_TYPE_CONSULTATION) {
                $data['label'] = $data['label'] . '['. StatDateType::DATE_TYPE_CONSULTATION . ']';
            } else {
                $data['label'] = $data['label'] . '['. StatDateType::DATE_TYPE_HISTORIQUE . ']';
            }
        }

        return $data;
    }

    /**
     * @param $label
     * @param int $colSize
     * @param string $descr
     * @param bool $ordered
     * @param bool $code
     * @return array
     */
    protected function newTableHeader($label, $colSize=1, $descr='', $ordered=false, $code=false)
    {
        $data = ['label' => $label, 'colSize' => $colSize, 'descr' => $descr];
        if($ordered) {
            $data['ordered'] = $ordered;
        }
        if($code) {
            $data['code'] = $code;
        }
        return $data;
    }

    /**
     * @param $label
     * @param $rowSize
     * @return array
     */
    protected function newLineHeader($label, $rowSize=1, $shown=true) {
        return ['label' => $label, 'rowSize' => $rowSize, 'shown' => $shown];
    }

    /**
     * @param $valueToDecorate
     * @param $isCa
     * @param $ratio
     * @param $decoration
     * @param $colCode
     * @param $details
     * @return array
     */
    protected function newValue($valueToDecorate, $isCa, $ratio, $decoration, $colCode, $details)
    {
        return [
            'value' => $valueToDecorate,
            'ratio' => $ratio,
            'isCa' => $isCa,
            'decoration' => $decoration,
            'colCode' => $colCode,
            'details' => $details
        ];
    }

    public function newBlankLine() {
        $line = [];
        foreach($this->TABLE_HEADERS as $header) {
            $line[$header['code']] = 0;
        }
        return $line;
    }

    /**
     * @param $config
     * @return bool
     */
    function isNoFilterQuery($config) {
        return (count($config['phonists']) ==0
            && count($config['consultants']) ==0
            && count($config['websites']) ==0
            && count($config['urls']) == 0
            && count($config['sources']) == 0
            && count($config['codesPromo']) == 0
            && isset($config['proprios']) && count($config['proprios']) == 0
            && isset($config['reflex_affiliates']) && count($config['reflex_affiliates']) == 0
            && isset($config['reflex_sources']) && count($config['reflex_sources']) == 0
            && count($config['supports']) == 0 );
    }

    /**
     * @param $data
     * @param $config
     */
    protected function setColumnsHeader(&$data, $config)  {
        $displayedColumnCa = $displayedColumnRdv = $offset = 0;
        $data['headers'] = [];

        if($this->isNoFilterQuery( $config)) {
            $data['headers'][] = $this->newTableHeader("", 1);
        }
        else  {
            if(count($config['phonists']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_PHONIST, 1);
                $offset++;
            }
            if(isset($config['proprios']) && count($config['proprios']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_PROPRIO, 1);
                $offset++;
            }
            if(isset($config['reflex_affiliates']) && count($config['reflex_affiliates']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_REFLEX_AFFILIATE, 1);
                $offset++;
            }
            if(isset($config['reflex_sources']) && count($config['reflex_sources']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_REFLEX_SOURCE, 1);
                $offset++;
            }
            if(count($config['consultants']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_CONSULTANT, 1);
                $offset++;
            }
            if(count($config['websites']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_WEBSITE, 1);
                $offset++;
            }
            if(count($config['sources']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_SOURCE, 1);
                $offset++;
            }
            if(count($config['urls']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_URL, 1);
                $offset++;
            }
            if(count($config['codesPromo']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_CODEPROMO, 1);
                $offset++;
            }
            if(count($config['supports']) > 0) {
                $data['headers'][] = $this->newTableHeader(self::LABEL_ROW_SUPPORT, 1);
                $offset++;
            }
        }

        foreach($this->TABLE_HEADERS as $header) {

            if( (($isrdv = in_array($header['code'], $config['rdv'])) || in_array($header['code'], $config['ca']) ) && ! in_array($header['code'], $config['empty_columns'])) {

                $ordered = $header['code'] === $config['sorting_column'] ? $config['sorting_dir'] : false;
                $data['headers'][] = $this->newTableHeader($header['label'], $header['ratio'] ? 2 : 1, $header['descr'], $ordered, $header['code']);

                if($isrdv) {
                    $displayedColumnRdv += $header['ratio'] ? 2 : 1;

                } else {
                    $displayedColumnCa += $header['ratio'] ? 2 : 1;
                }
            }
        }

        $data['categories'] = array();
        if(0 < $displayedColumnRdv) {
            $data['categories'][] = $this->newTableHeader(self::LABEL_CATEGORY_RDV, $displayedColumnRdv);
        }
        if(0 < $displayedColumnCa) {
            $data['categories'][] = $this->newTableHeader(self::LABEL_CATEGORY_CA, $displayedColumnCa);
        }
        $data['categories_offset'] = $offset;

        return $data;
    }

    /**
     * @param $data
     */
    protected function setTotals(&$data) {
        $data['total'] = [];
        $i_header = 0;

        foreach ($data['headers'] as $j_jheader => $header) {
            if ($j_jheader != 0) {
                for($i = 0; $i < $header['colSize']; $i++) {
                    if (0 == $i
                        && self::LABEL_ROW_PHONIST != $header['label']
                        && self::LABEL_ROW_PROPRIO != $header['label']
                        && self::LABEL_ROW_REFLEX_AFFILIATE != $header['label']
                        && self::LABEL_ROW_REFLEX_SOURCE != $header['label']
                        && self::LABEL_ROW_CONSULTANT != $header['label']
                        && self::LABEL_ROW_WEBSITE != $header['label']
                        && self::LABEL_ROW_SOURCE != $header['label']
                        && self::LABEL_ROW_URL != $header['label']
                        && self::LABEL_ROW_CODEPROMO != $header['label']
                        && self::LABEL_ROW_SUPPORT != $header['label']
                        && 'Moyenne réa.' != $header['label']
                        && 'Moyenne paie.' != $header['label']
                    ) {

                        $total = 0;
                        $isCa = false;

                        foreach ($data['lines'] as $line) {
                            $total += $line['values'][$i_header]['value'];
                            $isCa = $line['values'][$i_header]['isCa'];
                        }

                        $i_header++;
                        $data['total'][] = ['value' => $total, 'isCa' => $isCa];
                    } else {
                        $data['total'][] = ['value' => '', 'isCa' => ''];
                    }
                }
            }
        }

        return $data;
    }



    /**
     * @param $a
     * @param $b
     * @return float|int
     */
    protected function prct($a, $b)
    {
        return ($b != 0) ? $a / $b * 100 : 0;
    }

    /**
     * @param $values
     * @param $config
     * @return array
     */
    protected function decoratesValues($values, $config) {
        $valueToReturn = [];

        foreach($this->TABLE_HEADERS as $header) {

            if((in_array($header['code'], $config['rdv']) || in_array($header['code'], $config['ca'])) && ! in_array($header['code'], $config['empty_columns'])) {
                $ratio = null;
                $decoration = self::DEFAULT_RENDERING_COLOR;

                switch($header['code']) {
                    case StatisticColumnType::CODE_RDV_CANCELLED:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_RDV_CANCELLED], $values[StatisticColumnType::CODE_RDV_TOTAL]);
                        break;
                    case StatisticColumnType::CODE_RDV_TREATED:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_RDV_TREATED], $values[StatisticColumnType::CODE_RDV_TOTAL]);
                        break;
                    case StatisticColumnType::CODE_RDV_TEN_MINUTES:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_RDV_TEN_MINUTES], $values[StatisticColumnType::CODE_RDV_TREATED]);
                        break;
                    case StatisticColumnType::CODE_RDV_OVER_TEN_MINUTES:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_RDV_OVER_TEN_MINUTES], $values[StatisticColumnType::CODE_RDV_TREATED]);
                        break;
                    case StatisticColumnType::CODE_RDV_VALIDATED:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_RDV_VALIDATED], $values[StatisticColumnType::CODE_RDV_OVER_TEN_MINUTES]);
                        break;
                    case StatisticColumnType::CODE_RDV_UNPAID:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_RDV_UNPAID], $values[StatisticColumnType::CODE_RDV_OVER_TEN_MINUTES]);
                        break;

                    case StatisticColumnType::CODE_CA_UNPAID:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_CA_UNPAID], $values[StatisticColumnType::CODE_CA_REAL]);
                        break;
                    case StatisticColumnType::CODE_CA_ENC:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_CA_ENC], $config['total_enc']);
                        break;
                    case StatisticColumnType::CODE_CA_ENC_OPPO:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_CA_ENC_OPPO], $values[StatisticColumnType::CODE_CA_ENC]);
                        break;
                    case StatisticColumnType::CODE_CA_ENC_REFUND:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_CA_ENC_REFUND], $values[StatisticColumnType::CODE_CA_ENC]);
                        break;
                    case StatisticColumnType::CODE_CA_ENC_MM:
                        $ratio = $this->prct($values[StatisticColumnType::CODE_CA_ENC_MM], $values[StatisticColumnType::CODE_CA_REAL]);
                        break;
                    default:
                        $prct = null;
                        break;
                }

                // Setting decoration
                foreach($config['renderingRules'] as $renderingRule) {
                    if($renderingRule->isConcerned($header['code'], $values[$header['code']], $ratio)) {
                        $decoration = $renderingRule->getColor();
                    }
                }

                $valueToReturn[] = $this->newValue($values[$header['code']], $header['isCa'], $ratio, $decoration, $header['code'], $header['details']);
            }

        }

        return $valueToReturn;
    }

    /**
     * @param $values
     * @param null $phonist
     * @param null $consultant
     * @param null $website
     * @param null $source
     * @param null $url
     * @param null $codepromo
     * @param null $support
     * @return int
     */
    protected function getRowSizeOf($values, $phonist=null, $consultant=null, $website=null, $source=null, $url=null, $codepromo=null, $support=null, $proprio=null, $reflexAffiliate=null, $reflexSource=null) {
        $rowsize = 0;
        foreach($values as $value) {
            if(($phonist === null || $phonist == $value['phonist_id'] )
                && ($consultant === null || $consultant == $value['consultant_id'] )
                && ($website === null || $website == $value['website_id'] )
                && ($source === null || $source == $value['source_id'] )
                && ($url === null || $url == $value['url_id'])
                && ($proprio === null || $proprio == $value['proprio_id'])
                && ($reflexAffiliate === null || $reflexAffiliate == $value['reflex_affiliate_id'])
                && ($reflexSource === null || $reflexSource == $value['reflex_source_id'])
                && ($codepromo === null || $codepromo == $value['codepromo_id'])
                && ($support === null || $support == $value['support_id'])) {
                $rowsize++;
            }
        }
        return $rowsize;

    }

    /**
     * @param $data
     * @param $column
     * @return int
     */
    protected function getColumnTotal($data, $column) {
        $total = 0;
        foreach($data as $values) {
            $total += $values[$column];
        }
        return $total;
    }

    /**
     * @param $data
     * @return array
     */
    protected function getEmptyColumns($data)
    {
        $emptyColumns = array();
        foreach($this->TABLE_HEADERS as $header) {
            if($this->getColumnTotal($data, $header['code']) === 0) {
               $emptyColumns[] = $header['code'];
            }
        }
        return $emptyColumns;
    }

    /**
     * @param $line
     */
    protected function isEmptyLine($line) {
        foreach($this->TABLE_HEADERS as $header) {
            $v = $line[$header['code']];
            if($v != 0) {
               return false;
            }
        }
        return true;
    }

    /**
     * @param $dataToNormalise
     * @param $config
     * @return array
     */
    protected function getNormalisedData($dataToNormalise, $config)
    {
        $lines = [];

        $lastPhonistHeader = null;
        $lastProprioHeader = null;
        $lastReflexAffiliateHeader = null;
        $lastReflexSourceHeader = null;
        $lastConsultantHeader = null;
        $lastWebsiteHeader = null;
        $lastSourceHeader = null;
        $lastUrlHeader = null;
        $lastCodepromoHeader = null;

        $isPhonistFiltered = count($config['phonists']) > 0;
        $isConsultantsFiltered = count($config['consultants']) > 0;
        $isWebsiteFiltered = count($config['websites']) > 0;
        $isSourceFiltered = count($config['sources']) > 0;
        $isUrlFiltered = count($config['urls']) > 0;
        $isCodePromoFiltered = count($config['codesPromo']) > 0;
        $isSupportFiltered = count($config['supports']) > 0;
        $isReflexAffiliatesFiltered = isset($config['reflex_affiliates']) && count($config['reflex_affiliates']) > 0;
        $isReflexSourcesFiltered = isset($config['reflex_sources']) &&  count($config['reflex_sources']) > 0;
        $isPropriosFiltered = isset($config['proprios']) &&   count($config['proprios']) > 0;
        $dataToNormaliseWithoutEmpty = array();
        foreach($dataToNormalise as $values) {
            if (!$this->isEmptyLine($values)) {
                $dataToNormaliseWithoutEmpty[] = $values;
            }
        }
        $dataToNormalise = $dataToNormaliseWithoutEmpty;
        foreach($dataToNormalise as $values) {
            if($this->isEmptyLine($values)) {
                continue;
            }

            // Decorating values
            $line = [
                'values' => $this->decoratesValues($values, $config),
                'headers' => []
            ];

            // Adding a blanck line header if there is no filter (adjust with totals column)
            if ($this->isNoFilterQuery($config)) {
                $line['headers'][] = $this->newLineHeader("", 1);
            }
            else {
                // Setting line headers & metadatas
                if ($isPhonistFiltered) {
                    $line['phonist_id'] = $values['phonist_id'];
                    $shown = $lastPhonistHeader != $values['phonist_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise, $values['phonist_id']);
                    $line['headers'][] = $this->newLineHeader($values['phonist_label'], $rowsize, $shown);

                }

                if ($isPropriosFiltered) {
                    $line['proprio_id'] = $values['proprio_id'];
                    $shown = $lastProprioHeader != $values['proprio_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise,
                        $isPhonistFiltered ? $values['phonist_id'] : null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $values['proprio_id']);
                    $line['headers'][] = $this->newLineHeader($values['proprio_label'], $rowsize, $shown);

                }

                if ($isReflexAffiliatesFiltered) {
                    $line['reflex_affiliate_id'] = $values['reflex_affiliate_id'];
                    $shown = $lastReflexAffiliateHeader != $values['reflex_affiliate_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise,
                        $isPhonistFiltered ? $values['phonist_id'] : null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $isPropriosFiltered ? $values['proprio_id'] : null,
                        $values['reflex_affiliate_id']);
                    $line['headers'][] = $this->newLineHeader($values['reflex_affiliate_label'], $rowsize, $shown);

                }

                if ($isReflexSourcesFiltered) {
                    $line['reflex_source_id'] = $values['reflex_source_id'];
                    $shown = $isReflexAffiliatesFiltered && $lastReflexAffiliateHeader != $values['reflex_affiliate_id']
                        || $lastReflexSourceHeader != $values['reflex_source_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise,
                        $isPhonistFiltered ? $values['phonist_id'] : null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $isPropriosFiltered ? $values['proprio_id'] : null,
                        $isReflexAffiliatesFiltered ? $values['reflex_affiliate_id'] : null,
                        $values['reflex_source_id']);
                    $line['headers'][] = $this->newLineHeader($values['reflex_source_label'], $rowsize, $shown);

                }

                if ($isConsultantsFiltered) {
                    $line['consultant_id'] = $values['consultant_id'];
                    $shown = $isPhonistFiltered && $lastPhonistHeader != $values['phonist_id']
                        || $lastConsultantHeader != $values['consultant_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise, $isPhonistFiltered ? $values['phonist_id'] : null, $values['consultant_id']);
                    $line['headers'][] = $this->newLineHeader($values['consultant_label'], $rowsize, $shown);

                }

                if ($isWebsiteFiltered) {
                    $line['website_id'] = $values['website_id'];
                    $shown = $isPhonistFiltered && $lastPhonistHeader != $values['phonist_id']
                        || $isConsultantsFiltered && $lastConsultantHeader != $values['consultant_id']
                        || $lastWebsiteHeader != $values['website_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise,
                        $isPhonistFiltered ? $values['phonist_id'] : null,
                        $isConsultantsFiltered ? $values['consultant_id'] : null,
                        $values['website_id'],
                        null,
                        null,
                        null,
                        null,
                        $isPropriosFiltered ? $values['proprio_id'] : null);
                    $line['headers'][] = $this->newLineHeader($values['website_label'], $rowsize, $shown);

                }

                if ($isSourceFiltered) {
                    $line['source_id'] = $values['source_id'];
                    $shown = $isPhonistFiltered && $lastPhonistHeader != $values['phonist_id']
                        || $isConsultantsFiltered && $lastConsultantHeader != $values['consultant_id']
                        || $isWebsiteFiltered && $lastWebsiteHeader != $values['website_id']
                        || $lastSourceHeader != $values['source_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise,
                        $isPhonistFiltered ? $values['phonist_id'] : null,
                        $isConsultantsFiltered ? $values['consultant_id'] : null,
                        $isWebsiteFiltered ? $values['website_id'] : null,
                        $values['source_id'],
                        null,
                        null,
                        null,
                        $isPropriosFiltered ? $values['proprio_id'] : null);
                    $line['headers'][] = $this->newLineHeader($values['source_label'], $rowsize, $shown);
                }

                if ($isUrlFiltered) {
                    $line['url_id'] = $values['url_id'];
                    $shown = $isPhonistFiltered && $lastPhonistHeader != $values['phonist_id']
                        || $isConsultantsFiltered && $lastConsultantHeader != $values['consultant_id']
                        || $isWebsiteFiltered && $lastWebsiteHeader != $values['website_id']
                        || $isSourceFiltered && $lastSourceHeader != $values['source_id']
                        || $lastUrlHeader != $values['url_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise,
                        $isPhonistFiltered ? $values['phonist_id'] : null,
                        $isConsultantsFiltered ? $values['consultant_id'] : null,
                        $isWebsiteFiltered ? $values['website_id'] : null,
                        $isSourceFiltered ? $values['source_id'] : null,
                        $values['url_id'],
                        null,
                        null,
                        $isPropriosFiltered ? $values['proprio_id'] : null);

                    $line['headers'][] = $this->newLineHeader($values['url_label'], $rowsize, $shown);
                }

                if ($isCodePromoFiltered) {
                    $line['codepromo_id'] = $values['codepromo_id'];

                    $shown = $isPhonistFiltered && $lastPhonistHeader != $values['phonist_id']
                        || $isConsultantsFiltered && $lastConsultantHeader != $values['consultant_id']
                        || $isWebsiteFiltered && $lastWebsiteHeader != $values['website_id']
                        || $isSourceFiltered && $lastSourceHeader != $values['source_id']
                        || $isUrlFiltered && $lastUrlHeader != $values['url_id']
                        || $lastCodepromoHeader != $values['codepromo_id'];
                    $rowsize = $this->getRowSizeOf($dataToNormalise,
                        $isPhonistFiltered ? $values['phonist_id'] : null,
                        $isConsultantsFiltered ? $values['consultant_id'] : null,
                        $isWebsiteFiltered ? $values['website_id'] : null,
                        $isSourceFiltered ? $values['source_id'] : null,
                        $isUrlFiltered ? $values['url_id'] : null,
                        $values['codepromo_id'],
                        null,
                        $isPropriosFiltered ? $values['proprio_id'] : null);
                    $line['headers'][] = $this->newLineHeader($values['codepromo_label'], $rowsize, $shown);
                }

                if ($isSupportFiltered) {
                    $line['support_id'] = $values['support_id'];
                    $line['headers'][] = $this->newLineHeader($values['support_label']);
                }

                $lastPhonistHeader = array_key_exists('phonist_id', $values) ? $values['phonist_id'] : null;
                $lastConsultantHeader = array_key_exists('consultant_id', $values) ? $values['consultant_id'] : null;
                $lastWebsiteHeader = array_key_exists('website_id', $values) ? $values['website_id'] : null;
                $lastSourceHeader = array_key_exists('source_id', $values) ? $values['source_id'] : null;
                $lastUrlHeader = array_key_exists('url_id', $values) ? $values['url_id'] : null;
                $lastCodepromoHeader = array_key_exists('codepromo_id', $values) ? $values['codepromo_id'] : null;
                $lastProprioHeader= array_key_exists('proprio_id', $values) ? $values['proprio_id'] : null;
                $lastReflexAffiliateHeader = array_key_exists('reflex_affiliate_id', $values) ? $values['reflex_affiliate_id'] : null;
                $lastReflexSourceHeader = array_key_exists('reflex_source_id', $values) ? $values['reflex_source_id'] : null;
            }
            $lines[] = $line;
        }

        return $lines;
    }


    /**
     * @param array $dataToDecorate
     * @return array
     */
    protected function buildCaDetails(array $dataToDecorate) {
        $result = [];

        foreach ($dataToDecorate['ca'] as $v) {
            $result[] = ['rdv' => $v['id'], 'nb' => $v['nb_enc'], 'amount' => $v['nb'], 'user' => $v['prenom'].' '.$v['nom']];
        }

        return $result;
    }

    /**
     * @param array $dataToDecorate
     * @param array $config
     *
     * @return array
     */
    public function decorate(array $dataToDecorate, array $config)
    {
        $dataToReturn = [];

        if(isset($config['ca_details']) && $config['ca_details']) {
            $dataToReturn['details'] = $this->buildCaDetails($dataToDecorate);
            $dataToReturn['title'] = $dataToDecorate['title'];
        }
        elseif(isset($config['rdv_details']) && $config['rdv_details']) {
            $dataToReturn = $dataToDecorate;
        }
        else {
            $config['renderingRules'] = $this->em->getRepository(StatisticRenderingRule::class)->findAll(true);
            $config['empty_columns'] = $this->getEmptyColumns($dataToDecorate);
            $config['total_enc'] = $this->getColumnTotal($dataToDecorate, StatisticColumnType::CODE_CA_ENC);

            $dataToReturn['lines'] = $this->getNormalisedData($dataToDecorate, $config);

            $this->setTableName($dataToReturn, $config);
            $this->setColumnsHeader($dataToReturn, $config);
            $this->setTotals($dataToReturn);
        }

        return $dataToReturn;
    }
}
