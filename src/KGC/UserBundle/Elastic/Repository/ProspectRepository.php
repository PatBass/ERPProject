<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:55
 */

namespace KGC\UserBundle\Elastic\Repository;

use Elastica\Query as ElasticQuery;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Elastic builder for prospect search.
 *
 * Class ProspectRepository
 *
 * @DI\Service("kgc.elastic.prospect.repository")
 */
class ProspectRepository implements RepositoryInterface
{
    /**
     * @var \Elastica\Query\Bool
     */
    protected $query;

    /**
     * @var ElasticQuery
     */
    protected $finalQuery;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * Create a nested query part.
     *
     * @param $path
     *
     * @return ElasticQuery\Nested
     */
    protected function createNestedQuery($path)
    {
        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setPath($path);
        $nestedQuery->setScoreMode('avg');

        return $nestedQuery;
    }

    /**
     * @param $path
     * @param $fields
     * @param $value
     *
     * @return ElasticQuery\MultiMatch
     */
    protected function createFuzzyQuery($path, $fields, $value)
    {
        $newFields = [];
        if (!empty($path)) {
            foreach ($fields as $f) {
                $newFields[] = $path . '.' . $f;
            }
        } else {
            $newFields = $fields;
        }

        $fuzzyQuery = new \Elastica\Query\MultiMatch();
        $fuzzyQuery->setFields($newFields);
        $fuzzyQuery->setQuery($value);
        $fuzzyQuery->setOperator('OR');
        $fuzzyQuery->setFuzziness(0.7);

        return $fuzzyQuery;
    }

    /**
     * @param $field
     * @param \Datetime $from
     * @param \Datetime $to
     *
     * @return ElasticQuery\Range
     */
    protected function createDatetimeRangeQuery($field, \Datetime $from = null, \Datetime $to = null)
    {
        $conf = [];
        if ($from) {
            $conf['gte'] = date_format($from, 'Y-m-d');
        }
        if ($to) {
            $conf['lte'] = date_format($to, 'Y-m-d');
        }

        return new \Elastica\Query\Range($field, $conf);
    }

    /**
     * @param $field
     * @param null $from
     * @param null $to
     *
     * @return ElasticQuery\Range
     */
    protected function createNumericRangeQuery($field, $from = null, $to = null)
    {
        $conf = [];
        if (null !== $from) {
            $conf['gte'] = $from;
        }
        if (null !== $to) {
            $conf['lte'] = $to;
        }

        return new \Elastica\Query\Range($field, $conf);
    }

    public function __construct()
    {
        $this->query = new \Elastica\Query\Bool();
        $this->finalQuery = new \Elastica\Query();
    }

    /**
     * @param $path
     * @param $field
     * @param \Datetime $from
     * @param \Datetime $to
     */
    public function addNestedNumericRangeFilter($path, $field, $from = null, $to = null)
    {
        $q = $this->createNumericRangeQuery($field, $from, $to);

        $nestedQuery = $this->createNestedQuery($path);
        $nestedQuery->setQuery($q);

        $this->query->addMust($nestedQuery);
    }

    /**
     * @param $path
     * @param $field
     * @param \Datetime $from
     * @param \Datetime $to
     */
    public function addNestedDatetimeRangeFilter($path, $field, \Datetime $from, \Datetime $to)
    {
        $q = $this->createDatetimeRangeQuery($field, $from, $to);

        $nestedQuery = $this->createNestedQuery($path);
        $nestedQuery->setQuery($q);

        $this->query->addMust($nestedQuery);
    }

    /**
     * @param $field
     * @param \Datetime $from
     * @param \Datetime $to
     */
    public function addDatetimeRangeFilter($field, \Datetime $from = null, \Datetime $to = null)
    {
        $q = $this->createDatetimeRangeQuery($field, $from, $to);
        $this->query->addMust($q);
    }

    /**
     * @param $field
     * @param $value
     * @param string $pos
     */
    public function addWildcard($field, $value, $pos = 'both')
    {
        $value = $pos === 'both' ? sprintf('*%s*', $value) : $value;
        $value = $pos === 'end' ? sprintf('%s*', $value) : $value;
        $value = $pos === 'begin' ? sprintf('*%s', $value) : $value;

        $q = new \Elastica\Query\Wildcard();
        $q->setValue($field, $value);

        $this->query->addMust($q);
    }

    /**
     * Add a "must" query to filter results.
     *
     * @param $field
     * @param $value
     */
    public function addFilter($field, $value)
    {
        $filter = new \Elastica\Filter\Term(array($field => $value));
        $this->filters[] = $filter;
    }

    /**
     * Add a "must" query to filter results.
     *
     * @param $field
     * @param $value
     */
    public function addMultipleFilter($field, $values)
    {
        $filter = new \Elastica\Filter\Terms($field, $values);
        $this->filters[] = $filter;
    }

    /**
     * Add a "must" nested query to filter results.
     *
     * @param $path
     * @param $field
     * @param $value
     */
    public function addNestedFilter($path, $field, $value)
    {
        $filter = new \Elastica\Filter\Term(array($path . '.' . $field => $value));
        $this->filters[] = $filter;
    }

    /**
     * Add a query fuzzy part.
     *
     * @param $path
     * @param $fields
     * @param $value
     */
    public function addFuzzy($path, $fields, $value)
    {
        $fuzzyQuery = $this->createFuzzyQuery($path, $fields, $value);
        $this->query->addShould($fuzzyQuery);
    }

    /**
     * Add a nested query fuzzy part.
     *
     * @param $path
     * @param $fields
     * @param $value
     * @param int $boost
     */
    public function addNestedFuzzy($path, $fields, $value, $boost = 15)
    {
        $fuzzyQuery = $this->createFuzzyQuery($path, $fields, $value, $boost);

        $nestedQuery = $this->createNestedQuery($path);
        $nestedQuery->setQuery($fuzzyQuery);

        $this->query->addShould($nestedQuery);
    }

    /**
     * @param array $sortOptions
     */
    public function addSorting(array $sortOptions = [])
    {
        $this->finalQuery->setSort($sortOptions);
    }

    /**
     * @return ElasticQuery\Bool
     */
    public function getQuery($score = 1)
    {
        $hasFilters = !empty($this->filters);
        $zeroTest = null;
        if ($hasFilters) {
            $filters = new \Elastica\Filter\Bool();
            foreach ($this->filters as $f) {
                $filters->addMust($f);
            }

            $this->query = new \Elastica\Query\Filtered($this->query, $filters);
        }

        $this->finalQuery->setQuery($this->query);
        $params = $this->finalQuery->getParams();

        $zeroTest = $hasFilters
            ? $params['query']['filtered']['query']['bool']
            : $params['query']['bool'];

        if (!$hasFilters && (empty($params) || 0 === count($zeroTest))) {
            $this->finalQuery->setQuery(new \Elastica\Query\Term([
                'empty' => 'empty',
            ]));
        }
        if ($score) {
            $this->finalQuery->setParam('min_score', $score);
        }
        $this->finalQuery->setParam('fields', ['id']);

        return $this->finalQuery;
    }

    /**
     * Returns the repository name.
     *
     * @return string
     */
    public function getName()
    {
        return 'prospect';
    }
}
