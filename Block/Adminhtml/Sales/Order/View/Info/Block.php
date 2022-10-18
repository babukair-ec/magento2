<?php
//namespace Delivery\Shipox\Block\Adminhtml\Sales\Order\Shipment\Info;
//class Block extends \Magento\Backend\Block\Template
//{
//
//}
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 27.08.2019
 * Time: 8:40
 */

namespace Delivery\Shipox\Block\Adminhtml\Sales\Order\Shipment\Info;
use \Magento\Backend\Block\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Delivery\Shipox\Helper\Data;
use Delivery\Shipox\Model\Statusmapping;
use Delivery\Shipox\Model\Tracking;

class Block extends Template
{
    protected $registry;
    protected $scopeConfig;
    protected $_tracking;
    protected $_data;
    protected $_statusMapping;
    protected $code = 'delivery';


    /**
     * Block constructor.
     * @param Template\Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $scopeConfig
     * @param Tracking $tracking
     * @param Data $dataHelper
     * @param Statusmapping $statusMapping
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        Tracking $tracking,
        Data $dataHelper,
        Statusmapping $statusMapping,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->_tracking = $tracking;
        $this->_data = $dataHelper;
        $this->_statusMapping = $statusMapping;
        parent::__construct($context, $data);
    }

    /**
     * @return Registry current order
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    /**
     * @param $order
     * @return Tracking created Shipox order
     */
    public function isShipoxOrderCreated($order)
    {
        return $this->_tracking->getOrderTrackingData($order);
    }

    /**
     * @param $status
     * @return bool
     */
    public function showShipoxOrderCancelField($status) {
        $shipoxHelper = $this->_data;
        return $shipoxHelper->isShipoxOrderCanReject($status);
    }

    /**
     * @param $order
     * @return array
     */
    public function getProperPackagesForOrder($order)
    {
        $shipoxHelper = $this->_data;
        return $shipoxHelper->getProperPackagesForOrder($order);
    }

    /**
     * @param $shipoxOrderId
     * @return null
     */
    public function getShipoxOrderDetailInfo($shipoxOrderId, $order) {
        $shipoxHelper = $this->_data;
        $statusMapping = $this->_statusMapping;
        $shipoxOrderData =  $shipoxHelper->getShipoxOrderDetails($shipoxOrderId, true, $order);

        if($shipoxOrderData) {
            $shipoxOrderData['status_object'] = $statusMapping->getOrderStatus($shipoxOrderData['status']);
            $shipoxOrderData['estimated_delivery_date'] = date('Y-m-d H:i:s', strtotime($shipoxOrderData['deadline_time']));
        }

        return $shipoxOrderData;
    }

    /**
     * @param $order
     * @return bool
     */
    public function isShipoxOrderCreatedFromFrontEnd($order) {
        $shippingMethod = $order->getShippingMethod();
        $shippingMethodArray = explode("_", $shippingMethod);

        if($shippingMethodArray[0] != $this->scopeConfig->getValue('carriers/' . $this->code . '/alias', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
            return false;

        return true;
    }

    /**
     * @param $shipoxOrderId
     * @return null
     */
    public function getOrderAirWayBill($shipoxOrderId) {
        $shipoxHelper = $this->_data;
        return $shipoxHelper->getShipoxOrderAirWayBill($shipoxOrderId);
    }
}