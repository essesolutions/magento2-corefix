<?php

namespace Essesolutions\CoreFix\CatalogSearch\Plugin\Adapter\Mysql\Aggregation\DataProvider;

class QueryBuilder
{

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;

    /** @var \Magento\Framework\App\ScopeResolverInterface */
    private $scopeResolver;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /** @var \Magento\CatalogInventory\Model\Configuration */
    private $inventoryConfig;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $connection;

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\CatalogInventory\Model\Configuration $inventoryConfig,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->resource = $resource;
        $this->scopeResolver = $scopeResolver;
        $this->eavConfig = $eavConfig;
        $this->inventoryConfig = $inventoryConfig;
        $this->connection = $resource->getConnection();
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Search\Request\BucketInterface $bucket
     * @param array $dimensions
     * @param \Magento\Framework\DB\Ddl\Table $entityIdsTable
     * @return \Magento\Framework\DB\Select
     */
    public function buildQuery(
        \Magento\Framework\Search\Request\BucketInterface $bucket,
        array $dimensions,
        \Magento\Framework\DB\Ddl\Table $entityIdsTable
    )
    {

        $currentScope = $this->scopeResolver->getScope($dimensions['scope']->getValue())->getId();
        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $bucket->getField());

        $select = $this->getSelect();

        $select->joinInner(
            ['entities' => $entityIdsTable->getName()],
            'main_table.entity_id  = entities.entity_id',
            []
        );

        $table = $this->resource->getTableName(
            'catalog_product_index_eav' . ($attribute->getBackendType() === 'decimal' ? '_decimal' : '')
        );

        $subSelect = $select;
        $subSelect->from(['main_table' => $table], ['main_table.value'])
            ->joinLeft(
                ['stock_index' => $this->resource->getTableName('cataloginventory_stock_status')],
                'main_table.source_id = stock_index.product_id',
                []
            )
            ->where('main_table.attribute_id = ?', $attribute->getAttributeId())
            ->where('main_table.store_id = ? ', $currentScope)
            ->group(['main_table.entity_id', 'main_table.value']);

        if (!$this->inventoryConfig->isShowOutOfStock($currentScope)){
            $subSelect->where('stock_index.stock_status = ?', \Magento\CatalogInventory\Model\Stock::STOCK_IN_STOCK);
        }

        $parentSelect = $this->getSelect();
        $parentSelect->from(['main_table' => $subSelect], ['main_table.value']);
        $select = $parentSelect;

        return $select;
    }

    /** @return \Magento\Framework\DB\Select */
    private function getSelect()
    {
        return $this->connection->select();
    }

}