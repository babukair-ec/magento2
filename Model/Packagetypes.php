<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 26.07.2018
 * Time: 22:31
 */

namespace Delivery\Shipox\Model;


use Magento\Framework\Model\AbstractModel;

class Packagetypes extends AbstractModel
{
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getOptionInHTML()
    {
        $packageTypes = $this->toKeyArray();
        $adminPackageTypes = explode(',', $this->scopeConfig->getValue('delivery_shipox/config/allowed_package_types', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $adminPackageTypes = array_flip($adminPackageTypes);
        $packageTypes = array_intersect_key($packageTypes, $adminPackageTypes);

        return $packageTypes;
    }

    /**
     * @return array
     */
    public function toKeyArray()
    {
        $result = array();
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $result[$option['value']] = $option['label'];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr[] = array('value' => 'd2', 'label' => 'D2');

        $arr[] = array('value' => 'p3', 'label' => 'P3');
        $arr[] = array('value' => 'p5', 'label' => 'P5');
        $arr[] = array('value' => 'p10', 'label' => 'P10');
        $arr[] = array('value' => 'p30', 'label' => 'P30');
        $arr[] = array('value' => 'p100', 'label' => 'P100');

        $arr[] = array('value' => '0.5KG', 'label' => '0.5 Kg');
        $arr[] = array('value' => '1KG', 'label' => '1 Kg');
        $arr[] = array('value' => '1.5KG', 'label' => '1.5 Kg');
        $arr[] = array('value' => '2KG', 'label' => '2 Kg');
        $arr[] = array('value' => '2.5KG', 'label' => '2.5 Kg');
        $arr[] = array('value' => '3KG', 'label' => '3 Kg');
        $arr[] = array('value' => '3.5KG', 'label' => '3.5 Kg');
        $arr[] = array('value' => '4KG', 'label' => '4 Kg');
        $arr[] = array('value' => '4.5KG', 'label' => '4.5 Kg');
        $arr[] = array('value' => '5KG', 'label' => '5 Kg');
        $arr[] = array('value' => '5.5KG', 'label' => '5.5 Kg');
        $arr[] = array('value' => '6KG', 'label' => '6 Kg');
        $arr[] = array('value' => '6.5KG', 'label' => '6.5 Kg');
        $arr[] = array('value' => '7KG', 'label' => '7 Kg');
        $arr[] = array('value' => '7.5KG', 'label' => '7.5 Kg');
        $arr[] = array('value' => '8KG', 'label' => '8 Kg');
        $arr[] = array('value' => '8.5KG', 'label' => '8.5 Kg');
        $arr[] = array('value' => '9KG', 'label' => '9 Kg');
        $arr[] = array('value' => '9.5KG', 'label' => '9.5 Kg');
        $arr[] = array('value' => '10KG', 'label' => '10 Kg');

        return $arr;
    }
}