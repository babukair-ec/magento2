<?php
/**
 * Created by Shipox.
 * User: Furkat Djamolov
 * Date: 26.07.2018
 * Time: 21:12
 */
namespace Delivery\Shipox\Model;

use Magento\Framework\Model\AbstractModel;

class Countrylist extends AbstractModel {

    protected $_client;

    public function __construct(
        \Delivery\Shipox\Helper\Client $client
    ) {
        $this->_client = $client;
    }
    /**
     * @return array
     */
    public function toKeyArray()
    {
        $result = array();
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $result[$option['value']] = $option['name'];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $request = $this->_client;

        $result = $request->getCountryList();

        $arr = array();

        foreach ($result as $country) {
            $arr[] = array('value' => $country['id'], 'label' => $country['name']);
        }

        return $arr;
    }
}
