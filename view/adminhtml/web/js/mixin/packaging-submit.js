define([
    'mage/utils/wrapper'
], function (wrapper) {
    return function (submit) {
        /**
         * Add MSI related sourceCode to the shipment parameters
         */
        return wrapper.wrap(submit, function (originalFunction, submitUrl, data) {
            var inventorySourceCode = document.querySelector("input[name='sourceCode']");
            if (inventorySourceCode) {
                data.inventorySource = inventorySourceCode.value;
            }
            return originalFunction(submitUrl, data);
        });
    }
});
