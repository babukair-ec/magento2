<?php
/**
 * Created by Shipox.
 * User: Ali
 * Date: 19.12.2019
 * Time: 22:33
 */

namespace Delivery\Shipox\Model;

use Magento\Framework\Model\AbstractModel;

class AddressType extends AbstractModel
{
    public function toValueArray()
    {
        $result = array();
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $result[] = $option['value'];
        }
        return $result;
    }

    public function toOptionArray()
    {
        return array(
            array('value' => 'residential', 'label' => 'Residential'),
            array('value' => 'business', 'label' => 'Business'),
        );
    }
}