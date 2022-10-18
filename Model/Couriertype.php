<?php
/**
 * Created by Shipox.
 * User: Furkat Djamolov
 * Date: 22.07.2018
 * Time: 13:23
 */
namespace Delivery\Shipox\Model;

use Magento\Framework\Model\AbstractModel;

class Couriertype extends AbstractModel {

    protected $_client;
    protected $_scopeConfig;

    public function __construct(
        \Delivery\Shipox\Helper\Client $client,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_client = $client;
        $this->_scopeConfig = $scopeConfig;
    }

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
        $request = $this->_client;

        $result = $request->getCourierTypeList();

        $arr = array();

        if(isset($result['list'])) {
            foreach ($result['list'] as $type) {
                $arr[] = array('value' => $type['code'], 'id' => $type['id'], 'label' => $type['name']);
            }
        }

        return $arr;
    }
}