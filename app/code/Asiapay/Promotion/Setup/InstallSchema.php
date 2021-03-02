<?php

namespace Asiapay\Promotion\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('salesrule'),
            'asiapay_promotion_enable',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'length' => 6,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Asiapay Promotion Enable'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('salesrule'),
            'asiapay_promotion_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 32,
                'nullable' => false,
                'default' => '',
                'comment' => 'Asiapay Promotion Code'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('salesrule'),
            'asiapay_promotion_rule_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 32,
                'nullable' => false,
                'default' => '',
                'comment' => 'Asiapay Promotion Rule Code'
            ]
        );

        $installer->endSetup();
    }
}