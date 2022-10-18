<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 27.07.2018
 * Time: 15:54
 */

namespace Delivery\Shipox\Block\Adminhtml\System\Config\Form;
use \Magento\Config\Block\System\Config\Form\Field;
use \Delivery\Shipox\Helper\Data;



class ExpiredToken extends Field
{
    protected $scopeConfig;
    protected $_helper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_helper = $helper;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $helper = $this->_helper;

        return '<label>'.$helper->setExpiredDate($this->scopeConfig->getValue('delivery_shipox/auth/token_time', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)).'</label>';
    }
}