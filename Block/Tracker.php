<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 26.07.2018
 * Time: 22:42
 */

namespace Delivery\Shipox\Block;


class Tracker extends  \Magento\Checkout\Block\Onepage\Success
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $_data;
    protected $_customerSession;
    protected $_track;
    protected $checkoutSession;
    protected $_orderFactory;
    protected $orderRepository;
    protected $renderer;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Address\Renderer $renderer,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Delivery\Shipox\Helper\Data $dataHelper,
        \Magento\Checkout\Model\Session $customerSession,
        \Delivery\Shipox\Model\Tracking $track,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_data = $dataHelper;
        $this->_customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->_track = $track;
        $this->orderRepository = $orderRepository;
        $this->renderer = $renderer;
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
    }

    /**
     * @return mixed
     */
    public function trackOrder()
    {
        $helper = $this->_data;

        $orderId = $this->_customerSession->getLastRealOrder()->getRealOrderId();
        $shipoxOrder = $this->_track->getOrderTrackingDataByIncrementId($orderId);

        if($shipoxOrder) {
            $shipoxOrder['shipox_order_url'] = $helper->getShipoxSiteURL().$this->scopeConfig->getValue('tracking/config/path', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).$shipoxOrder['shipox_order_number'];
        }

        return $shipoxOrder ;
    }

    public function getServiceTitle()
    {
        return $this->_scopeConfig->getValue('delivery_shipox/service/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getSiteUrl()
    {
        return $this->_scopeConfig->getValue('delivery_shipox/merchant/host', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . '/';
    }

    public function getOrder()
    {
        return  $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId());
    }

    public function getCustomerId()
    {
        return $this->_customerSession->getCustomer()->getId();
    }
}