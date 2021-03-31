<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Netresearch\ShippingInventory\Plugin\PackagingPopup;

use Magento\Framework\Serialize\Serializer\Json;
use Netresearch\ShippingCore\Api\PackagingPopup\RequestDataConverterInterface;

class AddSourceToShipmentSaveRequest
{
    public const DATA_KEY_INVENTORY_SOURCE = 'inventorySource';

    /**
     * @var Json
     */
    private $jsonSerializer;

    public function __construct(Json $jsonSerializer)
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Add the inventory source to the request params before forwarding to the Magento Shipping save controller.
     *
     * @param RequestDataConverterInterface $subject
     * @param mixed[] $requestParams
     * @param string $json
     * @return mixed[]
     */
    public function afterGetParams(RequestDataConverterInterface $subject, array $requestParams, string $json): array
    {
        $data = $this->jsonSerializer->unserialize($json);

        if (isset($data[self::DATA_KEY_INVENTORY_SOURCE])) {
            $requestParams['sourceCode'] = $data[self::DATA_KEY_INVENTORY_SOURCE];
        }

        return $requestParams;
    }
}
