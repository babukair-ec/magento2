<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 28.07.2018
 * Time: 10:53
 */
namespace Delivery\Shipox\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface {
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {

        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'delivery_shipox'
         */
        try {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('delivery_shipox')
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'auto_increment' => true,
                    'unsigned' => true,
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Entity id'
            )->addColumn(
                'quote_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Quote ID'
            )->addColumn(
                'shipox_menu_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'default' => 0
                ],
                'Shipox Menu Id'
            )->addColumn(
                'shipox_package_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'default' => 0
                ],
                'Shipox Package Id'
            )->addColumn(
                'shipox_order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                [
                    'nullable' => true,
                ],
                'Shipox Order Id'
            )->addColumn(
                'shipox_order_number',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                [
                    'nullable' => true,
                ],
                'Shipox Order number'
            )->addColumn(
                'shipox_order_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                [
                    'nullable' => true,
                ],
                'Shipox Order status'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                [
                    'nullable' => true,
                ],
                'Order Id'
            )->addColumn(
                'destination',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true,
                ],
                'Order Destination'
            )->addColumn(
                'destination_latlon',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                [
                    'nullable' => true,
                ],
                'Order Destination Lat Lon'
            )->addColumn(
                'create_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                    'default'  => null,
                ],
                'Created Qoute At'
            )->addColumn(
                'completed_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                    'default'  => null,
                ],
                'Completed At'
            )->addColumn(
                'is_completed',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'default'  => 0,
                ],
                'Is Completed'
            )->addIndex(
                $setup->getIdxName(
                    $installer->getTable('delivery_shipox'),
                    ['shipox_order_id'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['shipox_order_id'],
                ['type' => AdapterInterface::INDEX_TYPE_INDEX]
            )->setComment(
                'Shipox Order Items'
            );
        } catch (\Zend_Db_Exception $e) {
        }
        try {
            $installer->getConnection()->createTable($table);
        } catch (\Zend_Db_Exception $e) {
        }
        $installer->endSetup();

    }
}