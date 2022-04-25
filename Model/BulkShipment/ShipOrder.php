<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Netresearch\ShippingInventory\Model\BulkShipment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsExtensionInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentPackageCreationInterface;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Exception\CouldNotShipException;
use Netresearch\ShippingInventory\Model\Inventory\SourceProvider;

/**
 * Wrapper around the Magento webapi endpoint to add inventory arguments.
 */
class ShipOrder implements ShipOrderInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ShipmentCreationArgumentsInterfaceFactory
     */
    private $argumentsFactory;

    /**
     * @var ShipmentCreationArgumentsExtensionInterfaceFactory
     */
    private $argumentsExtensionFactory;

    /**
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @var ShipOrderInterface
     */
    private $shipOrder;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ShipmentCreationArgumentsInterfaceFactory $argumentsFactory,
        ShipmentCreationArgumentsExtensionInterfaceFactory $argumentsExtensionFactory,
        SourceProvider $sourceProvider,
        ShipOrderInterface $shipOrder
    ) {
        $this->orderRepository = $orderRepository;
        $this->argumentsFactory = $argumentsFactory;
        $this->argumentsExtensionFactory = $argumentsExtensionFactory;
        $this->sourceProvider = $sourceProvider;
        $this->shipOrder = $shipOrder;
    }

    /**
     * Translate OrderItemInterface array to sku => product quantity array.
     *
     * @param OrderItemInterface[] $items
     * @return float[]
     */
    private function getQuantitiesFromOrderItems(array $items): array
    {
        $shipmentItems = [];
        foreach ($items as $item) {
            if (!$item->getIsVirtual() && (!$item->getParentItem() || $item->isShipSeparately())) {
                $qty = $shipmentItems[$item->getSku()] ?? 0;
                $shipmentItems[$item->getSku()] = $qty + ($item->getQtyOrdered() - $item->getQtyShipped());
            }
        }
        return array_filter($shipmentItems);
    }

    /**
     * Translate ShipmentItemCreationInterface array to sku => product quantity array.
     *
     * @param OrderInterface $order
     * @param ShipmentItemCreationInterface[] $items
     * @return float[]
     */
    private function getQuantitiesFromShipmentItems(OrderInterface $order, array $items): array
    {
        $requestedItemIds = array_reduce(
            $items,
            static function (array $result, ShipmentItemCreationInterface $item): array {
                $result[] = $item->getOrderItemId();
                return $result;
            },
            []
        );

        $orderItems = array_filter(
            $order->getAllItems(),
            static function (OrderItemInterface $orderItem) use ($requestedItemIds): bool {
                return in_array($orderItem->getId(), $requestedItemIds);
            }
        );

        return $this->getQuantitiesFromOrderItems($orderItems);
    }

    /**
     * Find one source that can fulfill all ordered items.
     *
     * @param int $orderId
     * @param float[] $itemQuantities quantities to be shipped, indexed by sku
     * @return string the inventory source's identifier
     * @throws CouldNotShipException thrown if partial shipments are necessary to fulfill the order
     */
    private function getSourceForOrderItems(int $orderId, array $itemQuantities): string
    {
        $sources = [];
        foreach ($itemQuantities as $sku => $qty) {
            $sources[] = $this->sourceProvider->getSourcesForSku((int) $orderId, $sku, $qty);
        }

        if (count($sources) > 1) {
            $sources = array_intersect(...$sources);
        } else {
            $sources = array_shift($sources);
        }

        if (empty($sources)) {
            throw new CouldNotShipException(__('Unable to detect a common inventory source for shipping all items.'));
        }

        return array_shift($sources);
    }

    /**
     * @param int $orderId
     * @param ShipmentItemCreationInterface[] $items
     * @param bool $notify
     * @param bool $appendComment
     * @param ShipmentCommentCreationInterface|null $comment
     * @param ShipmentTrackCreationInterface[] $tracks
     * @param ShipmentPackageCreationInterface[] $packages
     * @param ShipmentCreationArgumentsInterface|null $arguments
     * @return int
     * @throws LocalizedException
     * @throws \Exception
     */
    public function execute(
        $orderId,
        array $items = [],
        $notify = false,
        $appendComment = false,
        ShipmentCommentCreationInterface $comment = null,
        array $tracks = [],
        array $packages = [],
        ShipmentCreationArgumentsInterface $arguments = null
    ) {
        if ($arguments === null) {
            $arguments = $this->argumentsFactory->create();
        }

        $argumentsExtension = $arguments->getExtensionAttributes();
        if ($argumentsExtension === null) {
            $argumentsExtension = $this->argumentsExtensionFactory->create();
        }

        // collect items to ship
        $order = $this->orderRepository->get((int) $orderId);
        $itemQuantities = empty($items)
            ? $this->getQuantitiesFromOrderItems($order->getItems())
            : $this->getQuantitiesFromShipmentItems($order, $items);

        // find the best source for all items and set its code to the creation arguments
        $sourceCode = $this->getSourceForOrderItems((int) $orderId, $itemQuantities);
        $argumentsExtension->setSourceCode($sourceCode);
        $arguments->setExtensionAttributes($argumentsExtension);

        return $this->shipOrder->execute(
            $orderId,
            $items,
            $notify,
            $appendComment,
            $comment,
            $tracks,
            $packages,
            $arguments
        );
    }
}
