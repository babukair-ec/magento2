<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 30.07.2018
 * Time: 19:14
 */

namespace Delivery\Shipox\Controller\Adminhtml\Token;


use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use \Delivery\Shipox\Helper\Client;
use \Magento\Framework\App\Response\Http;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;

class Index extends Action
{

    protected $resultJsonFactory;
    protected $response;
    protected $client;
    protected $date;
    protected $writer;

    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        Client $client,
        Http $response,
        TimezoneInterface $date,
        WriterInterface $writer
    )
    {
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
        $this->client = $client;
        $this->response = $response;
        $this->date = $date;
        $this->writer = $writer;
    }


    public function execute()
    {
        /* @var Json $result */

        $params = $this->getRequest()->getParams();

        $shipoxApiClient = $this->client;

        $response = $this->response->setHeader('content-type', 'application/json; charset=utf-8');

        $result = array(
            'status' => 0,
            'description' => 'An error has occurred. Please, contact the store administrator.'
        );

        $requestData = array(
            'username' => $params['username'],
            'password' => $params['password'],
        );

        $responsedData = $shipoxApiClient->authenticate($requestData);
//        print 321;
//        print_r($responsedData);
//        die();

        if ($responsedData['status'] == 'success') {
            $data = $responsedData['data'];
            $this->writer->save('delivery_shipox/auth/jwt_token', $data['id_token']);
            $this->writer->save('delivery_shipox/auth/token_time',$this->date->date()->getTimestamp());

            $shipoxApiClient->updateCustomerMarketplace();

            $result['status'] = 1;
            $result['description'] = '';
        }

        //$response->setBody(json_encode($result));

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $resultJson->setData($responsedData);
    }
}