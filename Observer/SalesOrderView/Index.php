<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 03.08.2018
 * Time: 9:21
 */

namespace Delivery\Shipox\Observer\SalesOrderView;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Delivery\Shipox\Helper\Data;
use Delivery\Shipox\Model\Shipox;


class Index implements ObserverInterface
{
    protected $_order;
    protected $_data;
    public function __construct(

        Data $data,
        Shipox $shipox,
        Order $order
    ) {
        $this->_data = $data;
        $this->_order = $order;
    }
    public function execute(Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('shipox.order.info.block'))) {
            $transport = $observer->getData();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }

        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('shipox.order.shipment.info.block'))) {
            $transport = $observer->getData();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }
}