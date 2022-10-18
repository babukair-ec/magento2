<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 15:33
 */

namespace Delivery\Shipox\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Shipox extends AbstractDb {

    protected $datetime;

    /**
     * Initialize connection and define main table and primary key
     * @param Context $context
     * @param DateTime $dateTime
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->datetime = $dateTime;
    }

    protected function _construct()
    {
        $this->_init('delivery_shipox', 'id');
    }

    protected function _beforeSave( \Magento\Framework\Model\AbstractModel $object ) {
        if($object->isObjectNew() && !$object->getCreationTime()) {
            $object->setCreationTime($this->datetime->gmtDate());
        }
        return parent::_beforeSave( $object );
    }
}
