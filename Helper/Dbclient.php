<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 14:30
 */
namespace Delivery\Shipox\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;


class Dbclient extends AbstractHelper {

    protected $_logFile = 'shipox_api.log';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    protected $shipoxCollection;
    protected $shipoxFactory;

    /**
     * Dbclient constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Delivery\Shipox\Model\Shipox $shipoxFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Delivery\Shipox\Model\ResourceModel\Shipox\Collection $shipoxCollection,
        \Delivery\Shipox\Model\ShipoxFactory $shipoxFactory
    ) {
        $this->logger = $logger;
        $this->shipoxFactory = $shipoxFactory;
        $this->shipoxCollection = $shipoxCollection;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function insertData($data)
    {
        $model = $this->shipoxCollection;
        $collection = $model->addFieldToFilter('quote_id', ['eq'=>$data['quote_id']]);

        if (!$collection->getData()) {

            $model = $this->shipoxFactory->create()->addData($data);
            try {
               $model->save();

                $insertId = $model->getData('id');

                return $insertId;
            } catch (\Exception $e) {
                //$this->logger->log(null, 'DB Insert Error: '.$e->getMessage());
            }

        } else {
            $itemId = $collection->getData();
            return $this->updateData($itemId[0]['id'], $data);
        }

        return false;
    }


    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateData($id, $data)
    {
        $model = $this->shipoxFactory->create();

        try {
            $item = $model->load($id)->addData($data);
        } catch (\Exception $e) {
            print $e->getMessage();
        }
        try {
            $item->setId($id)->save();
            return true;

        } catch (\Exception $e){
            print $e->getMessage();
            //$this->logger->log(null, 'DB Update Error: '.$e->getMessage());
        }

        return false;
    }


    /**
     * @param $quoteId
     * @return mixed|null
     * @internal param $id
     */
    public function getData($quoteId)
    {
        $model = $this->shipoxCollection;
        $factory = $this->shipoxFactory->create();
        $collection = $model->addFieldToFilter('quote_id', ['eq'=>$quoteId])->getFirstItem();
        $modelId = $collection->getData('id');

        if ($modelId) {
            return $factory->load($modelId);
        }

        return null;
    }
}