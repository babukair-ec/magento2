<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 13:23
 */
namespace Delivery\Shipox\Model;

use Magento\Framework\Model\AbstractModel;

class Vehicletype extends AbstractModel {
    /**
     * @return array
     */
    public function toValueArray()
    {
        $result = array();
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $result[] = $option['value'];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'bike', 'label' => 'Bike'),
            array('value' => 'sedan', 'label' => 'Sedan'),
            array('value' => 'minivan', 'label' => 'Minivan'),
            array('value' => 'panelvan', 'label' => 'Panel Van'),
            array('value' => 'light_truck', 'label' => 'Light Truck'),
            array('value' => 'refrigerated_truck', 'label' => 'Refrigerated Truck'),
            array('value' => 'towing', 'label' => 'Towing'),
            array('value' => 'relocation', 'label' => 'Relocation'),
        );
    }
}