<?php

declare(strict_types=1);

namespace Withings\ExportOrder\Model\Order;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class Export
{
    const API_URL_WMS = 'www.monlogisticien.com/exemple/of/endpoint';

    /** @var CollectionFactory */
    private $orderCollection;

    /** @var Curl */
    private $client;

    /** @var LoggerInterface  */
    private $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        Curl $client,
        LoggerInterface $logger
    ) {
        $this->orderCollection = $collectionFactory;
        $this->client = $client;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $orders = $this->getOrderCollection();

        foreach ($orders as $order) {
            try {

                $params = $this->prepareData($order);

                $this->client->addHeader("Content-Type", "application/json");
                $this->client->addHeader("Content-Length", 200);
                $this->client->setCredentials('username', 'password');
                $this->client->post(self::API_URL_WMS, $params);

                $result = $this->client->getBody();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    private function getOrderCollection(): Collection
    {
        $orderCollection = $this->orderCollection->create();

        $orderCollection
            ->addAttributeToSelect('*');
        // Can add other filters on the statuses for example

        return $orderCollection;
    }

    private function prepareData(Order $order): array
    {
        $shippingAddress = $order->getShippingAddress();
        $orderItems = $order->getAllItems();
        $items = [];

        foreach ($orderItems as $item) {
            $items = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'unit_price' => $item->getPrice(),
            ];
        }

        return [
            'customer' => [
                'email' => $order->getCustomerEmail(),
                'firstnam' => $order->getCustomerFirstname(),
                'lastname' => $order->getCustomerLastname(),
                ],
            'order' => [
                'shipping_method' => $order->getShippingMethod(),
                'shipping_address' => [
                    'street' => $shippingAddress->getStreet(),
                    'postcode' => $shippingAddress->getPostcode(),
                    'city' => $shippingAddress->getCity(),
                    'country' => $shippingAddress->getCountryId(),
                    'telephone' => $shippingAddress->getTelephone(),
                ],
                'total' => $order->getGrandTotal(),
                'currency' => $order->getOrderCurrencyCode(),
                'items' => $items,
            ],
        ];
    }
}
