<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 26.07.2018
 * Time: 22:33
 */

namespace Delivery\Shipox\Model;

use Magento\Framework\Model\AbstractModel;

class Paymenttype extends AbstractModel
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
            array('value' => 'cash', 'label' => 'Cash'),
            array('value' => 'credit_balance', 'label' => 'Credit Balance'),
            array('value' => 'cash_online_credit_balance', 'label' => 'Both'),
        );
    }
}