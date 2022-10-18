<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 28.07.2018
 * Time: 11:15
 */

namespace Delivery\Shipox\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $connection = $setup->getConnection();
            $column = [
                'type' => Table::TYPE_INTEGER,
                'nullable' => false,
                'default' => 1,
                'comment' => 'Active/Cancelled Order',
            ];
            $connection->addColumn($setup->getTable('delivery_shipox'), 'active_order', $column);
        }
        $setup->endSetup();

    }
}