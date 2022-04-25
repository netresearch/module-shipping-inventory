<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Netresearch\ShippingInventory\Model\Inventory;

use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterface;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventorySourceSelectionApi\Model\GetInventoryRequestFromOrder;

class SourceProvider
{
    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @var GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    private $getDefaultSourceSelectionAlgorithmCode;

    /**
     * @var GetInventoryRequestFromOrder
     */
    private $getInventoryRequestFromOrder;

    public function __construct(
        ItemRequestInterfaceFactory $itemRequestFactory,
        SourceSelectionServiceInterface $sourceSelectionService,
        GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        GetInventoryRequestFromOrder $getInventoryRequestFromOrder
    ) {
        $this->itemRequestFactory = $itemRequestFactory;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->getInventoryRequestFromOrder = $getInventoryRequestFromOrder;
    }

    /**
     * Get possible source locations for shipping fulfillment
     *
     * @param int $orderId
     * @param string $sku
     * @param float $qty
     * @return string[] inventory source codes
     */
    public function getSourcesForSku(int $orderId, string $sku, float $qty): array
    {
        $algorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();

        $requestItem = $this->itemRequestFactory->create([
            'sku' => $sku,
            'qty' => $qty
        ]);

        $inventoryRequest = $this->getInventoryRequestFromOrder->execute($orderId, [$requestItem]);
        $sourceSelectionResult = $this->sourceSelectionService->execute($inventoryRequest, $algorithmCode);

        return array_map(
            static function (SourceSelectionItemInterface $item) {
                return $item->getSourceCode();
            },
            $sourceSelectionResult->getSourceSelectionItems()
        );
    }
}
