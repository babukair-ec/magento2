<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 03.08.2018
 * Time: 10:02
 */

namespace Delivery\Shipox\Observer\TrackerBlock;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;



class Index implements ObserverInterface
{
    protected $_layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout
    )
    {
        $this->_layout = $layout;

    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer) {

        $block = $observer->getData();

         if (($observer->getData('element_name') == 'sales_order_info') && ($child = $block->getChildBlock('shipox_order_info_customer'))) {
            $transport = $observer->getEvent()->getData('transport');
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }
}