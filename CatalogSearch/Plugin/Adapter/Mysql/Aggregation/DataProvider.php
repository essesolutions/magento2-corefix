<?php

namespace Essesolutions\CoreFix\CatalogSearch\Plugin\Adapter\Mysql\Aggregation;

use Magento\Customer\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\DB\Select;

class DataProvider
{

    /** @var DataProvider\QueryBuilder */
    protected $queryBuilder;

    public function __construct(
        DataProvider\QueryBuilder $queryBuilder
    )
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param \Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider $subject
     * @param callable|\Closure $proceed
     * @param BucketInterface $bucket
     * @param Dimension[] $dimensions
     *
     * @param Table $entityIdsTable
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetDataSet(
        \Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider $subject,
        \Closure $proceed,
        BucketInterface $bucket,
        array $dimensions,
        Table $entityIdsTable
    )
    {
        if ($bucket->getField() == 'category_ids') {
            return $proceed($bucket, $dimensions, $entityIdsTable);
        }


        if ($bucket->getField() == 'price') {
            return $proceed($bucket, $dimensions, $entityIdsTable);
        }

        $select = $this->queryBuilder->buildQuery($bucket, $dimensions, $entityIdsTable);
        return $select;
    }

}