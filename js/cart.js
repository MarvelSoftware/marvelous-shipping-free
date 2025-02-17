document.addEventListener("DOMContentLoaded", () => {
    // Check if we are on the /cart page
    if (window.location.pathname.includes("/cart")) {
        // Observe changes in the DOM to handle dynamically added elements
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                const totalsItems = document.querySelectorAll(".wc-block-components-totals-item__value strong");

                totalsItems.forEach((element) => {
                    if (element.textContent.trim() === "חינם") {
                        element.textContent = "מחושב בקופה";
                    }
                });
            });
        });

        // Start observing the DOM for changes
        const targetNode = document.body;
        const config = { childList: true, subtree: true };
        observer.observe(targetNode, config);
    }
});
document.addEventListener("DOMContentLoaded", () => {
    // Check if we are on the /cart page
    if (window.location.pathname.includes("/cart")) {
        // Observe changes in the DOM to handle dynamically added elements
        const observer = new MutationObserver(() => {
            // Find all radio inputs for shipping methods
            const shippingRadios = document.querySelectorAll("input.wc-block-components-radio-control__input");

            shippingRadios.forEach((radio) => {
                // Check if this is the marvelous_shipping shipping method
                if (radio.value.startsWith("marvelous_shipping")) {
                    // Find the associated label using the "for" attribute
                    const label = document.querySelector(`label[for="${radio.id}"]`);

                    if (label) {
                        // Find the "חינם" element within the label
                        const freeShippingElement = label.querySelector(".wc-block-components-shipping-rates-control__package__description--free");

                        if (freeShippingElement && freeShippingElement.textContent.trim() === "חינם") {
                            freeShippingElement.textContent = "מחושב בקופה"; // Replace with desired text
                        }
                    }
                }
            });
        });

        // Start observing the DOM for changes
        const targetNode = document.body;
        const config = { childList: true, subtree: true };
        observer.observe(targetNode, config);
    }
});
