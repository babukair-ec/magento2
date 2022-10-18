<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 31.07.2018
 * Time: 11:23
 */
namespace Delivery\Shipox\Block\Adminhtml\Order\View;
use \Magento\Backend\Block\Template\Context;

class Custom extends \Magento\Backend\Block\Template
{
    protected $code = 'delivery';
    protected $registry;
    protected $scopeConfig;
    protected $_tracking;
    protected $_dataHelper;
    protected $_statusMapping;
    protected $_formKey;


    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Delivery\Shipox\Model\Tracking $tracking,
        \Delivery\Shipox\Helper\Data $dataHelper,
        \Delivery\Shipox\Model\Statusmapping $statusMapping,
        \Magento\Framework\Data\Form\FormKey $key,
        Context $context
    ) {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->_tracking = $tracking;
        $this->_dataHelper = $dataHelper;
        $this->_statusMapping = $statusMapping;
        $this->_formKey = $key;
        parent::__construct($context);
    }

    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    public function isShipoxOrderCreated($order)
    {
        return $this->_tracking->getOrderTrackingData($order);
    }

    public function key() {
        $sessionId = $this->_formKey->getFormKey();
        return $sessionId;
    }

    public function showShipoxOrderCancelField($status) {
        $shipoxHelper = $this->_dataHelper;
        return $shipoxHelper->isShipoxOrderCanReject($status);
    }

    public function getOrderActionStatus() {
        return $this->scopeConfig->getValue('delivery_shipox/messages/cannot_cancel_order', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $order
     * @return array
     */
    public function getProperPackagesForOrder($order)
    {
        $shipoxHelper = $this->_dataHelper;
        return $shipoxHelper->getProperPackagesForOrder($order);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * @param $shipoxOrderId
     * @return null
     */
    public function getShipoxOrderDetailInfo($shipoxOrderId, $order) {
        $shipoxHelper = $this->_dataHelper;
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
        $shipoxHelper = $this->_dataHelper;
        return $shipoxHelper->getShipoxOrderAirWayBill($shipoxOrderId);
    }
}