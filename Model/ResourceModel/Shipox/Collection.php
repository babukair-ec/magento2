<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 15:54
 */
namespace Delivery\Shipox\Model\ResourceModel\Shipox;
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection {
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            'Delivery\Shipox\Model\Shipox',
            'Delivery\Shipox\Model\ResourceModel\Shipox'
        );
    }
}