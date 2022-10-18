<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 26.07.2018
 * Time: 22:34
 */

namespace Delivery\Shipox\Model;


use Magento\ImportExport\Model\AbstractModel;

class Shippingoption extends AbstractModel
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'bike_in_5_days', 'label' => 'Bike: Delivery: 1-4 working days'),
            array('value' => 'bike_next_day', 'label' => 'Bike: Delivery: within 24 hours'),
            array('value' => 'bike_same_day', 'label' => 'Bike: Delivery: within same day'),
            array('value' => 'bike_bullet', 'label' => 'Bike: Delivery: within 4 hours'),

            array('value' => 'sedan_in_5_days', 'label' => 'Sedan: Delivery: 1-4 working days'),
            array('value' => 'sedan_next_day', 'label' => 'Sedan: Delivery: within 24 hours'),
            array('value' => 'sedan_same_day', 'label' => 'Sedan: Delivery: within same day'),
            array('value' => 'sedan_bullet', 'label' => 'Sedan: Delivery: within 4 hours'),

            array('value' => 'minivan_in_5_days', 'label' => 'Small Van: Delivery: 1-4 working days'),
            array('value' => 'minivan_next_day', 'label' => 'Small Van: Delivery: within 24 hours'),
            array('value' => 'minivan_same_day', 'label' => 'Small Van: Delivery: within same day'),
            array('value' => 'minivan_bullet', 'label' => 'Small Van: Delivery: within 4 hours'),

            array('value' => 'panelvan_in_5_days', 'label' => 'Panel Van: Delivery: 1-4 working days'),
            array('value' => 'panelvan_next_day', 'label' => 'Panel Van: Delivery: within 24 hours'),
            array('value' => 'panelvan_same_day', 'label' => 'Panel Van: Delivery: within same day'),
            array('value' => 'panelvan_bullet', 'label' => 'Panel Van: Delivery: within 4 hours'),

            array('value' => 'light_truck_in_5_days', 'label' => 'International: Delivery: 1-4 working days'),
            array('value' => 'light_truck_next_day', 'label' => 'International: Delivery: within 24 hours'),
            array('value' => 'light_truck_same_day', 'label' => 'International: Delivery: within same day'),
            array('value' => 'light_truck_bullet', 'label' => 'International: Delivery: within 4 hours'),

            array('value' => 'refrigerated_truck_in_5_days', 'label' => 'Refrigerated Truck: Delivery: 1-4 working days'),
            array('value' => 'refrigerated_truck_next_day', 'label' => 'Refrigerated Truck: Delivery: within 24 hours'),
            array('value' => 'refrigerated_truck_same_day', 'label' => 'Refrigerated Truck: Delivery: within same day'),
            array('value' => 'refrigerated_truck_bullet', 'label' => 'Refrigerated Truck: Delivery: within 4 hours'),

            array('value' => 'towing_in_5_days', 'label' => 'Towing: Delivery: 1-4 working days'),
            array('value' => 'towing_next_day', 'label' => 'Towing: Delivery: within 24 hours'),
            array('value' => 'towing_same_day', 'label' => 'Towing: Delivery: within same day'),
            array('value' => 'towing_bullet', 'label' => 'Towing: Delivery: within 4 hours'),

            array('value' => 'relocation_in_5_days', 'label' => 'Relocation: Delivery: 1-4 working days'),
            array('value' => 'relocation_next_day', 'label' => 'Relocation: Delivery: within 24 hours'),
            array('value' => 'relocation_same_day', 'label' => 'Relocation: Delivery: within same day'),
            array('value' => 'relocation_bullet', 'label' => 'Relocation: Delivery: within 4 hours'),
        );
    }
}