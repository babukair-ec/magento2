<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 27.07.2018
 * Time: 16:51
 */

namespace Delivery\Shipox\Block\Adminhtml\System\Config\Form;
use \Magento\Config\Block\System\Config\Form\Field;


class TokenField extends Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setData('readonly', 1);
        return $element->getElementHtml();

    }
}