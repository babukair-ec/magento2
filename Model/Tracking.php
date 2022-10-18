<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 16:47
 */

namespace Delivery\Shipox\Model;
use Magento\Framework\Model\AbstractModel;

class  Tracking extends AbstractModel {
    protected $modelDbClient;
    protected $salesOrder;
    private $_objectManager;

    public function __construct(
        \Delivery\Shipox\Helper\Dbclient $db_client,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Sales\Model\Order $sales_order
    ) {
        $this->modelDbClient = $db_client;
        $this->_objectManager = $objectmanager;
        $this->salesOrder = $sales_order;

    }
    /**
     * @param $orderId
     * @return mixed
     */
    public function getOrderTrackingDataByIncrementId($orderId) {
        $order = $this->salesOrder->loadByIncrementId($orderId);
        return $this->getOrderTrackingData($order);
    }

    public function getOrderTrackingData($order) {
        $dbClient = $this->modelDbClient;

        if(isset($order)) {
            $shipoxOrderData = $dbClient->getData($order->getData('quote_id'));
            if($shipoxOrderData && $shipoxOrderData->getOrderId() && $shipoxOrderData->getIsCompleted()) {
                return $shipoxOrderData->getData();
            }
        }

        return null;
    }
}