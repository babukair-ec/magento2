<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 26.07.2018
 * Time: 21:17
 */
namespace Delivery\Shipox\Model;

use Magento\Framework\Model\AbstractModel;

class Menutype extends AbstractModel {
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
            array('value' => '0', 'label' => 'Calculate by Products\' Weight'),
            array('value' => '2', 'label' => 'Up to 2 KG'),
            array('value' => '3', 'label' => 'Up to 3 KG'),
            array('value' => '5', 'label' => 'Up to 5 KG'),
            array('value' => '10', 'label' => 'Up to 10 KG'),
            array('value' => '30', 'label' => 'Up to 30 KG'),
            array('value' => '100', 'label' => 'Up to 100 KG')
        );
    }
}