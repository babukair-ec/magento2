<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 27.07.2018
 * Time: 16:08
 */

namespace Delivery\Shipox\Block\Adminhtml\System\Config\Form;
use \Magento\Config\Block\System\Config\Form\Field;
use  \Magento\Backend\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;


class GetTokenButton extends Field
{
    protected $_data;
    protected $_template = '/shipox/system/config/get_token_button.phtml';
//    protected $_template = '/delivery/shipox/view/adminhtml/templates/shipox/system/config/get_token_button.phtml';
    protected $_backendUrl;

    public function __construct(
        Data $clientData,
        Context $context,
        array $data = [])
    {
        $this->_data = $clientData;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $template = $this->setTemplate($this->_template);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxGetTokenUrl()
    {
        $url = $this->getUrl('Adminhtml/ShipoxadminController/getToken');
        return $this->getUrl('delivery_shipox/Token/Token');

    }

    public function getButtonHtml()
    {
        try {
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(
                [
                    'id' => 'get_shipox_token',
                    'label' => __('Get Token'),
                    'onclick' => 'getJWTToken();'
                ]
            );
        } catch (LocalizedException $e) {
        }

        return $button->toHtml();
    }
}