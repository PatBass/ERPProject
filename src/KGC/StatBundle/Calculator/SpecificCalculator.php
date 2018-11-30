<?php
namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\Dossier;
use KGC\StatBundle\Decorator\DecoratorInterface;
use KGC\StatBundle\Decorator\SpecificDecorator;
use KGC\StatBundle\Form\StatisticColumnType;

/**
 * Class SpecificCalculator.
 *
 * @DI\Service("kgc.stat.calculator.specific", parent="kgc.stat.calculator")
 */
class SpecificCalculator extends Calculator
{
    /**
     * @var SpecificDecorator
     */
    private $specificDecorator;

    /**
     * @var DecoratorInterface
     */
    private $csvDecorator;

    /**
     * @param DecoratorInterface $specificDecorator
     *
     * @DI\InjectParams({
     *      "specificDecorator" = @DI\Inject("kgc.stat.decorator.specific"),
     * })
     */
    public function setSpecificDecorator(DecoratorInterface $specificDecorator)
    {
        $this->specificDecorator = $specificDecorator;
    }

    /**
     * @param DecoratorInterface $csvDecorator
     *
     * @DI\InjectParams({
     *      "csvDecorator" = @DI\Inject("kgc.stat.decorator.csv"),
     * })
     */
    public function setCsvcDecorator(DecoratorInterface $csvDecorator)
    {
        $this->csvDecorator = $csvDecorator;
    }

    /**
     * @param $valToRank
     * @param $values
     * @param $config
     * @param bool $phonist
     * @param bool $consultant
     * @param bool $website
     * @param bool $source
     * @param bool $url
     * @param bool $codepromo
     * @return int
     */
    protected function rank($valToRank, $values, $config, $phonist=false, $consultant=false, $website=false, $source=false, $url=false, $codepromo=false) {
        $rank = 0;
        $orderBy = $config['sorting_column'];

        foreach($values as $val)  {

            if((!$phonist || $phonist && isset($val['phonist_id']) && $val['phonist_id'] === $valToRank['phonist_id'])
                && (!$consultant || $consultant && isset($val['consultant_id']) &&  $val['consultant_id'] === $valToRank['consultant_id'])
                &&(!$website || $website && isset($val['website_id']) &&  $val['website_id'] === $valToRank['website_id'])
                && (!$source || $source && isset($val['source_id']) &&  $val['source_id'] === $valToRank['source_id'])
                && (!$url || $url && isset($val['url_id']) &&  $val['url_id'] === $valToRank['url_id'])
                && (!$codepromo || $codepromo && isset($val['codepromo_id']) &&  $val['codepromo_id'] === $valToRank['codepromo_id'])) {
                $rank += $val[$orderBy];
            }
        }
        return $rank;
    }

    /**
     * @param $val1
     * @param $val2
     * @param $config
     * @return int
     */
    protected function compareValues($val1, $val2, $values, $config) {
        $orderBy = $config['sorting_column'];
        $orderDir = $config['sorting_dir'];

        $vcmp1 = $orderDir == 'ASC' ? $val1: $val2;
        $vcmp2 = $orderDir == 'ASC' ? $val2: $val1;

        if( $config['isPhonistFiltered'] && $val1['phonist_id'] != $val2['phonist_id']
            && ($config['isConsultantFiltered'] || $config['isWebsiteFiltered'] || $config['isSourceFiltered'] || $config['isUrlFiltered'] || $config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, true)
            - $this->rank($vcmp2, $values, $config, true);
        }
        else if( $config['isPropriosFiltered'] && $val1['proprio_id'] != $val2['proprio_id']
            && ($config['isConsultantFiltered'] || $config['isWebsiteFiltered'] || $config['isSourceFiltered'] || $config['isUrlFiltered'] || $config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, true)
            - $this->rank($vcmp2, $values, $config, true);
        }
        else if( $config['isReflexAffiliatesFiltered'] && $val1['reflex_affiliate_id'] != $val2['reflex_affiliate_id']
            && ($config['isConsultantFiltered'] || $config['isWebsiteFiltered'] || $config['isSourceFiltered'] || $config['isUrlFiltered'] || $config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, true)
            - $this->rank($vcmp2, $values, $config, true);
        }
        else if( $config['isReflexSourcesFiltered'] && $val1['reflex_source_id'] != $val2['reflex_source_id']
            && ($config['isConsultantFiltered'] || $config['isWebsiteFiltered'] || $config['isSourceFiltered'] || $config['isUrlFiltered'] || $config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, true)
            - $this->rank($vcmp2, $values, $config, true);
        }
        else if( $config['isConsultantFiltered'] && $val1['consultant_id'] != $val2['consultant_id']
            && ($config['isWebsiteFiltered'] || $config['isSourceFiltered'] || $config['isUrlFiltered'] || $config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, $config['isPhonistFiltered'], true)
            - $this->rank($vcmp2, $values, $config, $config['isPhonistFiltered'], true);
        }
        else if( $config['isWebsiteFiltered'] && $val1['website_id'] != $val2['website_id']
            && ($config['isSourceFiltered'] || $config['isUrlFiltered'] || $config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], true)
                - $this->rank($vcmp2, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], true);
        }
        else if( $config['isSourceFiltered'] && $val1['source_id'] != $val2['source_id']
            && ($config['isUrlFiltered'] || $config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], $config['isWebsiteFiltered'], true)
                - $this->rank($vcmp2, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], $config['isWebsiteFiltered'], true);
        }
        else if( $config['isUrlFiltered']&& $val1['url_id'] != $val2['url_id']
            && ($config['isCodePromoFiltered'] || $config['isSupportFiltered'])) {
            return $this->rank($vcmp1, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], $config['isWebsiteFiltered'], $config['isSourceFiltered'], true)
                - $this->rank($vcmp2, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], $config['isWebsiteFiltered'], $config['isSourceFiltered'], true);
        }
        else if( $config['isCodePromoFiltered'] && $val1['codepromo_id'] != $val2['codepromo_id']
            && $config['isSupportFiltered']) {
            return $this->rank($vcmp1, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], $config['isWebsiteFiltered'], $config['isSourceFiltered'], $config['isUrlFiltered'], true)
                - $this->rank($vcmp2, $values, $config, $config['isPhonistFiltered'], $config['isConsultantFiltered'], $config['isWebsiteFiltered'], $config['isSourceFiltered'], $config['isUrlFiltered'], true);
        }

        return $vcmp1[$orderBy]-$vcmp2[$orderBy];
    }

    /**
     * @param $values
     * @param $config
     */
    protected function sortValues(& $values, $config) {

        $size = count($values);
        $continue = true;
        while($continue) {
            $continue = false;
            for($i = 0; $i < $size - 1; $i++) {
                $cmp = $this->compareValues($values[$i], $values[$i+1], $values,  $config);
                if($cmp > 0) {
                    $temp = $values[$i+1];
                    $values[$i+1] = $values[$i];
                    $values[$i] = $temp;
                    $continue = true;
                }
            }
            $size--;
        }
    }

    /**
     * @param $t1
     * @param $t2
     * @param $config
     * @return array
     */
    protected function merge($t1, $t2, $config) {
        $emptyLine = $this->specificDecorator->newBlankLine() + ['ca_enc_oppo_mm' => 0, 'billing_rea' => 0, 'billing_count' => 0, 'rdv_mm' => 0, 'rdv_recup_mp_nb' => 0, 'rdv_consult_enc_mm' => 0, 'rdv_consult_recup_mp' => 0];
        $result = array();
        foreach($t1 as $a) {
            $a_merged = false;
            for($i_b = 0; $i_b < count($t2); $i_b++) {
                $b =  $t2[$i_b];
                $toMerge = true;
                if( $config['isPhonistFiltered'] > 0 && $a['phonist_id'] !== $b["phonist_id"] ||
                    $config['isConsultantFiltered'] > 0 && $a['consultant_id'] !== $b["consultant_id"] ||
                    $config['isWebsiteFiltered'] > 0 && $a['website_id'] !== $b["website_id"] ||
                    $config['isSourceFiltered'] > 0 && $a['source_id'] !== $b["source_id"] ||
                    $config['isUrlFiltered'] > 0 && $a['url_id'] !== $b["url_id"] ||
                    $config['isCodePromoFiltered'] > 0 && $a['codepromo_id'] !== $b["codepromo_id"] ||
                    $config['isReflexAffiliatesFiltered'] > 0 && $a['reflex_affiliate_id'] !== $b["reflex_affiliate_id"] ||
                    $config['isReflexSourcesFiltered'] > 0 && $a['reflex_source_id'] !== $b["reflex_source_id"] ||
                    $config['isPropriosFiltered'] > 0 && $a['proprio_id'] !== $b["proprio_id"] ||
                    $config['isSupportFiltered'] > 0 && $a['support_id'] !== $b["support_id"]) {
                    $toMerge = false;
                }

                if($toMerge) {
                    $result[] = array_merge($a, $b);
                    $a_merged = true;
                    $t2[$i_b]['merged'] = 'merged';
                }
            }

            if(!$a_merged) {
                $result[] = array_merge($emptyLine, $a);
            }
        }

        foreach($t2 as $b) {
            if(!isset($b['merged'])) {
                $result[] = array_merge($emptyLine, $b);
            }
        }


        return $result;
    }

    /**
     * @param $config
     * @return array
     */
    protected function getDayInterval($config)
    {
        $begin = new \Datetime($config['begin']->format('Y-m-d '.self::DAY_TIME_REFERENCE));
        $end =  new \Datetime($config['end']->format('Y-m-d '.self::DAY_TIME_REFERENCE));
        $end = $end->add(new \DateInterval('P01D'));

        return [$begin, $end];
    }

    /**
     * @param $filter
     * @param $config
     * @return array
     */
    protected function getSpecificStatistics($filter, $config) {

        $config['isPhonistFiltered'] = count($config['phonists']) > 0;
        $config['isConsultantFiltered'] = count($config['consultants']) > 0;
        $config['isWebsiteFiltered'] = count($config['websites']) > 0;
        $config['isSourceFiltered'] = count($config['sources']) > 0;
        $config['isUrlFiltered'] = count($config['urls']) > 0;
        $config['isCodePromoFiltered'] = count($config['codesPromo']) > 0;
        $config['isSupportFiltered'] = count($config['supports']) > 0;
        $config['isReflexAffiliatesFiltered'] = isset($config['reflex_affiliates']) && count($config['reflex_affiliates']) > 0;
        $config['isReflexSourcesFiltered'] = isset($config['reflex_sources']) && count($config['reflex_sources']) > 0;
        $config['isPropriosFiltered'] = isset($config['proprios']) && count($config['proprios']) > 0;

        $tmp1 = $this->getStatRepository()->getAdminConsultSpecific($filter);
        $tmp2 = $this->getStatRepository()->getAdminBillingSpecific($filter);
        $tmp3 = $this->getStatRepository()->getAdminSpecificRealisedCA($filter);
        $tmp4 = $this->getStatRepository()->getAdminBillingSecuSpecific($filter);
        $tmp5 = $this->getStatRepository()->getAdminRecupMpFNANbSpecific($filter);
        $tmp6 = $this->getStatRepository()->getAdminMMNbSpecific($filter);
        $tmp7 = $this->getStatRepository()->getVoyantTurnoverSpecific($filter, true);
        $tmp8 = $this->getStatRepository()->getVoyantTurnoverSpecific($filter, false, true);
        $tmp9 = $this->getStatRepository()->getAdminSpecificBillingRea($filter);

        $merged = $this->merge($tmp1, $tmp2, $config);
        $merged = $this->merge($merged, $tmp3, $config);
        $merged = $this->merge($merged, $tmp4, $config);
        $merged = $this->merge($merged, $tmp5, $config);
        $merged = $this->merge($merged, $tmp6, $config);
        $merged = $this->merge($merged, $tmp7, $config);
        $merged = $this->merge($merged, $tmp8, $config);
        $merged = $this->merge($merged, $tmp9, $config);

        $results = array();
        foreach($merged as $result) {
            $result[StatisticColumnType::CODE_CA_UNPAID] = $result[StatisticColumnType::CODE_CA_REAL] - $result[StatisticColumnType::CODE_CA_ENC_MM];

            $paie_count = $result['rdv_validated'];
            $paie_ca = $result['rdv_consult_enc_mm'] + $result['rdv_consult_recup_mp'];
            $result[StatisticColumnType::CODE_CA_AVERAGE_PAYMENT] = $paie_count == 0 ? 0 : $paie_ca / $paie_count;

            $rea_ca = $result['billing_rea'];
            $rea_count = $result['billing_count'];
            $result[StatisticColumnType::CODE_CA_AVERAGE_REAL] = $rea_count == 0 ? 0 : $rea_ca /  $rea_count;

            $results[] = $result;
        }

        $this->sortValues($results, $config);

        return $results;
    }

    /**
     * @param $column
     * @param $filter
     * @return mixed
     */
    protected function getSpecificRdvDetails($column, $filter) {
        $data = array();
        $title = "";
        switch($column) {
            case StatisticColumnType::CODE_RDV_TOTAL:
                $data = $this->getStatRepository()->getAdminSpecificTotalConsultDetails($filter);
                $title = 'total';
                break;
            case StatisticColumnType::CODE_RDV_CANCELLED:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter);
                $title = 'annulés';
                break;
            case StatisticColumnType::CODE_RDV_510_MIN:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter, "5FOIS10MIN");
                $title = '5 x 10 minutes';
                break;
            case StatisticColumnType::CODE_RDV_CB_BELGE:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter, Dossier::CB_BELGE);
                $title = 'CB Belge';
                break;
            case StatisticColumnType::CODE_RDV_CB_VIDE:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter, Dossier::CB_VIDE);
                $title = 'CB non remplie';
                break;
            case StatisticColumnType::CODE_RDV_FNA:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter, Dossier::EN_FNA);
                $title = 'FNA';
                break;
            case StatisticColumnType::CODE_RDV_NRP:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter, Dossier::NRP);
                $title = 'NRP';
                break;
            case StatisticColumnType::CODE_RDV_NVP:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter, Dossier::NVP);
                $title = 'NVP';
                break;
            case StatisticColumnType::CODE_RDV_REFUSED_SECU:
                $data = $this->getStatRepository()->getAdminSpecificCancelledConsultDetails($filter, Dossier::REFUS_2E);
                $title = 'refus de la sécurisation';
                break;
            case StatisticColumnType::CODE_RDV_WAITORREPORT:
                $data = $this->getStatRepository()->getAdminSpecificWaitOrReportConsultDetails($filter);
                $title = 'en attente ou reporté';
                break;
            case StatisticColumnType::CODE_RDV_TREATED:
                $data = $this->getStatRepository()->getAdminSpecificTreatedConsultDetails($filter);
                $title = 'traités';
                break;
            case StatisticColumnType::CODE_RDV_TEN_MINUTES:
                $data = $this->getStatRepository()->getAdminSpecificTenMinutesConsultDetails($filter);
                $title = '10 minutes';
                break;
            case StatisticColumnType::CODE_RDV_OVER_TEN_MINUTES:
                $data = $this->getStatRepository()->getAdminSpecificOverTenMinutesConsultDetails($filter);
                $title = '> 10 minutes';
                break;
            case StatisticColumnType::CODE_RDV_VALIDATED:
                $data = $this->getStatRepository()->getAdminSpecificValidatedConsultDetails($filter);
                $title = 'validés';
                break;
            case StatisticColumnType::CODE_RDV_UNPAID:
                $data = $this->getStatRepository()->getAdminSpecificUnpaidConsultDetails($filter);
                $title = 'impayés';
                break;
        }
        return ['details' => $data, 'title' => $title];
    }

    /**
     * @param $column
     * @param $filter
     * @return mixed
     */
    protected function getSpecificCaDetails($column, $filter) {
        $data = array();
        $title = "";
        switch($column) {
            case StatisticColumnType::CODE_CA_REAL:
                $data = $this->getStatRepository()->getAdminSpecificCaRealDetails($filter);
                $title = 'réalisé';
                break;
            case StatisticColumnType::CODE_CA_UNPAID:
                $data = $this->getStatRepository()->getAdminSpecificCaUnpaidDetails($filter);
                $title = 'impayé';
                break;
            case StatisticColumnType::CODE_CA_ENC:
                $data = $this->getStatRepository()->getAdminSpecificCaEncTotalDetails($filter);
                $title = 'encaissé';
                break;
            case StatisticColumnType::CODE_CA_ENC_OPPO:
                $data = $this->getStatRepository()->getAdminSpecificCaEncOppoDetails($filter);
                $title = 'en oppositions';
                break;
            case StatisticColumnType::CODE_CA_ENC_REFUND:
                $data = $this->getStatRepository()->getAdminSpecificCaEncRefundDetails($filter);
                $title = 'remboursés';
                break;
            case StatisticColumnType::CODE_CA_ENC_SECU:
                $data = $this->getStatRepository()->getAdminSpecificSecuDetails($filter);
                $title = 'sécurisations';
                break;
            case StatisticColumnType::CODE_CA_RECUP_MP:
                $data = $this->getStatRepository()->getAdminSpecificCaRecupMPDetails($filter);
                $title = 'recupéré des mois précédents';
                break;
            case StatisticColumnType::CODE_CA_ENC_MM:
                $data = $this->getStatRepository()->getAdminSpecificCaEncMMDetails($filter);
                $title = 'encaissé sur le mois même';
                break;
        case StatisticColumnType::CODE_CA_ENC_JM:
                $data = $this->getStatRepository()->getAdminSpecificCaEncJMDetails($filter);
                $title = 'encaissé sur le jour même';
                break;
        case StatisticColumnType::CODE_CA_RECUP_MM:
                $data = $this->getStatRepository()->getAdminSpecificCaRecupMMDetails($filter);
                $title = 'récupéré du mois même';
                break;
        }

        return ['ca' => $data, 'title' => $title];
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function calculate(array $config = [])
    {
        list($begin, $end) = $this->getDayInterval($config);
        $data = array();
        if(isset($config['ca_details']) && $config['ca_details']) {
            $filter = [
                'phonists' => $config['phonist_id'] == -1 ? [] : [$config['phonist_id']],
                'proprios' => $config['proprio_id'] == -1 ? [] : [$config['proprio_id']],
                'reflex_affiliates' => $config['reflex_affiliate_id'] == -1 ? [] : [$config['reflex_affiliate_id']],
                'reflex_sources' => $config['reflex_source_id'] == -1 ? [] : [$config['reflex_source_id']],
                'consultants' => $config['consultant_id'] == -1 ? [] : [$config['consultant_id']],
                'websites' => $config['website_id'] == -1 ? [] : [$config['website_id']],
                'sources' => $config['source_id'] == -1 ? [] : [$config['source_id']],
                'urls' => $config['url_id'] == -1 ? [] : [$config['url_id']],
                'codesPromo' => $config['codepromo_id'] == -1 ? [] : [$config['codepromo_id']],
                'supports' => $config['support_id'] == -1 ? [] : [$config['support_id']],
                'statScope' => $config['statScope'],
                'begin' => $begin,
                'end' => $end,
                'role' => !empty($config['role']) ? $config['role'] : '',
                'dateType' => isset($config['dateType']) ? $config['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE
            ];

            $data = $this->getSpecificCaDetails($config['columnCode'], $filter);
        }
        else if(isset($config['rdv_details']) && $config['rdv_details']) {
            $filter = [
                'phonists' => $config['phonist_id'] == -1 ? [] : [$config['phonist_id']],
                'proprios' => $config['proprio_id'] == -1 ? [] : [$config['proprio_id']],
                'reflex_affiliates' => $config['reflex_affiliate_id'] == -1 ? [] : [$config['reflex_affiliate_id']],
                'reflex_sources' => $config['reflex_source_id'] == -1 ? [] : [$config['reflex_source_id']],
                'consultants' => $config['consultant_id'] == -1 ? [] : [$config['consultant_id']],
                'websites' => $config['website_id'] == -1 ? [] : [$config['website_id']],
                'sources' => $config['source_id'] == -1 ? [] : [$config['source_id']],
                'urls' => $config['url_id'] == -1 ? [] : [$config['url_id']],
                'codesPromo' => $config['codepromo_id'] == -1 ? [] : [$config['codepromo_id']],
                'supports' => $config['support_id'] == -1 ? [] : [$config['support_id']],
                'statScope' => $config['statScope'],
                'begin' => $begin,
                'end' => $end,
                'role' => !empty($config['role']) ? $config['role'] : '',
                'dateType' => isset($config['dateType']) ? $config['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE
            ];

            $data = $this->getSpecificRdvDetails($config['columnCode'], $filter);
        }
        elseif(isset($config['specific_full']) && $config['specific_full']) {
            $filter = [
                'phonists' => $config['phonists'],
                'proprios' => isset($config['proprios']) ? $config['proprios'] : [],
                'reflex_affiliates' => isset($config['reflex_affiliates']) ? $config['reflex_affiliates'] : [],
                'reflex_sources' => isset($config['reflex_sources']) ? $config['reflex_sources'] : [],
                'consultants' => $config['consultants'],
                'websites' =>  $config['websites'],
                'sources' =>  $config['sources'],
                'urls' =>  $config['urls'],
                'codesPromo' =>  $config['codesPromo'],
                'supports' =>  $config['supports'],
                'statScope' => $config['statScope'],
                'begin' => $begin,
                'end' => $end,
                'role' => !empty($config['role']) ? $config['role'] : '',
                'dateType' => isset($config['dateType']) ? $config['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE
            ];

            $data = $this->getSpecificStatistics($filter, $config);
        }

        $data = $this->specificDecorator->decorate($data, $config);

        if(isset($config['export']) && $config['export']) {
            return $this->csvDecorator->decorate($data, $config);
        }

        return $data;
    }

}

?>