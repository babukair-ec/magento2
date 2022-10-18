<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 14:38
 */

namespace Delivery\Shipox\Model;
use Magento\Framework\Model\AbstractModel;

class Shipox extends AbstractModel {

     /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Delivery\Shipox\Model\ResourceModel\Shipox');
    }

}