<?php

namespace Essesolutions\CoreFix\Quote\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\SalesSequence\Model\Manager;

class Quote extends \Magento\Quote\Model\ResourceModel\Quote{

    /** @var  \Magento\Framework\App\ProductMetadata */
    protected $productMetadata;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param Manager $sequenceManager
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        Manager $sequenceManager,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        $connectionName = null
    ) {
        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $sequenceManager, $connectionName);
        $this->productMetadata = $productMetadata;
    }


    /**
     * Subtract product from all quotes quantities
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function substractProductFromQuotes($product)
    {
        if (!version_compare($this->productMetadata->getVersion(), '2.2.0', '<')){
            return parent::substractProductFromQuotes($product);
        }

        $productId = (int)$product->getId();
        if (!$productId) {
            return $this;
        }
        $connection = $this->getConnection();
        $subSelect = $connection->select();

        $subSelect->from(
            false,
            [
                'items_qty' => new \Zend_Db_Expr(
                    $connection->quoteIdentifier('q.items_qty') . ' - ' . $connection->quoteIdentifier('qi.qty')
                ),
                'items_count' => new \Zend_Db_Expr('CASE WHEN q.items_count > 0 THEN ' . $connection->quoteIdentifier('q.items_count') . ' - 1 ELSE 0 END')
            ]
        )->join(
            ['qi' => $this->getTable('quote_item')],
            implode(
                ' AND ',
                [
                    'q.entity_id = qi.quote_id',
                    'qi.parent_item_id IS NULL',
                    $connection->quoteInto('qi.product_id = ?', $productId)
                ]
            ),
            []
        );

        $updateQuery = $connection->updateFromSelect($subSelect, ['q' => $this->getTable('quote')]);

        $connection->query($updateQuery);

        return $this;
    }

}