<?php

namespace KGC\ClientBundle\Twig\Extension;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Service\HistoriqueManager;
use KGC\RdvBundle\Service\PlanningService;

/**
 * @DI\Service("kgc.historique.twig.extension")
 *
 * @DI\Tag("twig.extension")
 */
class HistoriqueExtension extends \Twig_Extension
{
    const EMPTY_VALUE_TEXT = ' - ';

    /**
     * @var HistoriqueManager
     */
    protected $historiqueManager;

    /**
     * @param mixed $historiqueManager
     *
     * @DI\InjectParams({
     *     "historiqueManager"  = @DI\Inject("kgc.client.historique.manager")
     * })
     */
    public function __construct(HistoriqueManager $historiqueManager)
    {
        $this->historiqueManager = $historiqueManager;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            'historique_type_label' => new \Twig_Filter_Method($this, 'buildHistoriqueType'),
            'historique_value' => new \Twig_Filter_Method($this, 'buildHistoriqueValue'),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('fields_by_section', [$this, 'getFieldsBySection']),
            new \Twig_SimpleFunction('getHistorySectionConstant', [$this, 'getHistorySectionConstant']),
            new \Twig_SimpleFunction('getHistoryTypeConstant', [$this, 'getHistoryTypeConstant']),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'historique';
    }

    /**
     * Return historique label from historique type.
     *
     * @param $type
     *
     * @return string
     */
    public function buildHistoriqueType($type)
    {
        return $this->historiqueManager->getTypeLabelMapping($type);
    }

    /**
     * Return historique value from historique type.
     *
     * @param $type
     * @param $historique
     *
     * @return null|string
     */
    public function buildHistoriqueValue($type, $historique)
    {
        if (!empty($historique[$type])) {
            $line = $historique[$type];

            switch (true) {
                case Historique::BACKEND_TYPE_BOOL === $line['backendType']:
                    return $line['value'] ? 'OUI' : 'NON';
                case Historique::BACKEND_TYPE_OPTION === $line['backendType']:
                    $label = $line['value']->getLabel();

                    return strlen($label) ? $label : self::EMPTY_VALUE_TEXT;
                case Historique::BACKEND_TYPE_OPTIONS === $line['backendType']:
                    $value = implode(', ', $line['value']->toArray());

                    return $value ?: self::EMPTY_VALUE_TEXT;
                case Historique::BACKEND_TYPE_PENDULUM === $line['backendType']:
                    $questionAnswers = $line['value']->toArray();

                    return !empty($questionAnswers) ? $questionAnswers : self::EMPTY_VALUE_TEXT;
                case Historique::BACKEND_TYPE_DRAW === $line['backendType']:
                    $Draws = $line['value']->toArray();

                    return !empty($Draws) ? $Draws : self::EMPTY_VALUE_TEXT;
                case Historique::BACKEND_TYPE_DATETIME === $line['backendType']:
                    if (null !== $line['value']) {
                        return date_format(
                            $line['value'],
                            PlanningService::SIMPLE_DATE_FORMAT.' à '.PlanningService::INTERVALLE_DATE_FORMAT
                        );
                    }

                    return self::EMPTY_VALUE_TEXT;
                default:
                    return strlen($line['value']) ? $line['value'] : self::EMPTY_VALUE_TEXT;
            }
        }

        return self::EMPTY_VALUE_TEXT;
    }

    /**
     * @param $section
     *
     * @return mixed
     */
    public function getFieldsBySection($section)
    {
        return $this->historiqueManager->getHistoryFieldsBySection($section);
    }

    /**
     * @param string $section
     *
     * @return string
     */
    public function getHistorySectionConstant($section)
    {
        return constant('KGC\ClientBundle\Service\HistoriqueManager::HISTORY_SECTION_'.strtoupper($section));
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getHistoryTypeConstant($type)
    {
        return constant('KGC\ClientBundle\Entity\Historique::TYPE_'.strtoupper($type));
    }
}
