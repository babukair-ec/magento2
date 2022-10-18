<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 03.08.2018
 * Time: 9:15
 */

namespace Delivery\Shipox\Observer\WhenCanceled;

use Delivery\Shipox\Helper\ShipoxLogger;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

class Index implements ObserverInterface
{
    protected $_order;
    protected $_data;
    protected $_shipoxLogger;
    protected $_logFile = 'shipox-cancel-observer';
    public function __construct(

        \Delivery\Shipox\Helper\Data $data,
        \Delivery\Shipox\Model\Shipox $shipox,
        \Magento\Sales\Model\Order $order,
        ShipoxLogger $shipoxLogger
    ) {
        $this->_data = $data;
        $this->_order = $order;
        $this->_shipoxLogger = $shipoxLogger;
    }
    public function execute(Observer $observer) {

        $orderId = $observer->getEvent()->getOrderIds();
        $order = $this->_order->load($orderId);

        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getStatus();

//        $this->_shipoxLogger->setFileName($this->_logFile);
////        $this->_shipoxLogger->message($order);
//        $this->_shipoxLogger->message($oldStatus);
//        $this->_shipoxLogger->message($newStatus);

        if(($oldStatus != $newStatus) && ($newStatus == 'canceled')) {
            $shipoxHelper = $this->_data;

            $order = $observer->getData('order');
            $shipoxHelper->cancelShipoxOrder($order, 'Magento Order cancelled event fired. Merchant cancelling the Order');
        }
        return $this;
    }
}