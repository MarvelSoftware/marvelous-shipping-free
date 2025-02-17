// ===================================================
// fast comments handler
// ===================================================

document.addEventListener("DOMContentLoaded", function () {
    function checkbox_func(event) {
        // Extract text from the label of the checkbox (the text is inside the <label>)
        var label = event.target.closest(".fast-msg-style").querySelector("label");
        var txt = label ? label.textContent.replace("(אופציונלי)", "").trim() : ""; // Extract the text content of the label

        var commentTxt = document.getElementById("order_comments").value;
        var orderCommentsField = document.getElementById("order_comments");

        if (event.target.checked) {
            // Check if the checkbox is checked
            if (commentTxt.indexOf(txt) === -1) {
                orderCommentsField.value = commentTxt + txt + "\n";
                orderCommentsField.value = orderCommentsField.value.replace("(אופציונלי)", "");
            }
        } else if (commentTxt.indexOf(txt) !== -1) {
            orderCommentsField.value = commentTxt.replace(txt + "\n", "");
        }
    }
    // Get all elements with the class 'fast-msg-style'
    var fastMsgElements = document.querySelectorAll(".fast-msg-style");

    // Add event listeners to each element
    fastMsgElements.forEach(function (element) {
        // Get the checkbox within the element
        var checkbox = element.querySelector('input[type="checkbox"]');

        // If the checkbox exists, add event listeners
        if (checkbox) {
            // Handle checkbox state on change
            checkbox.addEventListener("change", checkbox_func);

            // Make the entire div clickable
            element.addEventListener("click", function (event) {
                // Ensure clicking the checkbox itself doesn't toggle twice
                if (event.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event("change")); // Trigger the "change" event
                }
            });
        }
    });
});

// ===================================================
// address generator
// ===================================================
function address_generator(field = "billing") {
    var selected_city = jQuery(`#mrvl_${field}_city`)?.find(":selected")?.text().trim();
    var selected_street = jQuery(`#mrvl_${field}_street`)?.find(":selected")?.text().trim();

    var house_num = document.querySelector(`input#mrvl_${field}_house_num`)?.value || "";
    var house_floor = document.querySelector(`input#mrvl_${field}_house_floor`)?.value || "";
    var entrance = document.querySelector(`select#mrvl_${field}_entrance`)?.value || "";
    var aprt_num = document.querySelector(`input#mrvl_${field}_aprt_num`)?.value || "";
    var postcode = document.querySelector(`input#mrvl_${field}_postcode`)?.value || "";
    var entry_code = document.querySelector(`input#mrvl_${field}_entry_code`)?.value || "";
    var final_address = [];
    var final_address_str = "";

    if (
        !selected_city ||
        selected_city === "" ||
        selected_city.includes("בחרו עיר") ||
        selected_city.includes("בחר עיר")
    ) {
        selected_city = "";
    }
    if (
        selected_street &&
        selected_street !== "" &&
        !selected_street.includes("בחרו עיר תחילה") &&
        !selected_street.includes("בחרו רחוב") &&
        !selected_street.includes("בחר רחוב")
    ) {
        if (selected_street.includes("רחוב")) {
            final_address.push(selected_street);
        } else {
            // for streets name רחוב 15 for example
            final_address.push("רחוב " + selected_street);
        }
    } else {
        selected_street = "";
    }
    if (house_num !== "") {
        final_address.push(" מספר " + house_num);
    }
    if (entrance !== "") {
        final_address.push(", כניסה " + entrance);
    }
    if (house_floor !== "") {
        final_address.push(", קומה " + house_floor);
    }
    if (aprt_num !== "") {
        final_address.push(", דירה " + aprt_num);
    }
    if (entry_code !== "") {
        final_address.push(", קוד כניסה: " + entry_code);
    }
    for (let index = 0; index < final_address.length; index++) {
        final_address_str += final_address[index];
    }

    var address_input = document.querySelector(`input#${field}_address_1`);
    var city_input = document.querySelector(`input#${field}_city`);
    var postcode_input = document.querySelector(`input#${field}_postcode`);
    if (address_input) {
        address_input.value = final_address_str;
    }
    if (city_input) {
        city_input.value = selected_city;
    }
    if (postcode_input) {
        postcode_input.value = postcode;
    }
}

// ===================================================
// check if shipping to different address
// ===================================================

function isShippingToDifferentAddress() {
    return jQuery("#ship-to-different-address-checkbox")?.prop("checked") ?? false;
}

// ===================================================
// ===================================================

document.addEventListener("DOMContentLoaded", function () {
    function initEventListeners() {
        // Define field sets for both billing and shipping
        ["billing", "shipping"].forEach((type) => {
            const fields = document.querySelectorAll(
                `select#mrvl_${type}_city, select#mrvl_${type}_street, select#mrvl_${type}_entrance, ` +
                    `input#mrvl_${type}_house_num, input#mrvl_${type}_house_floor, ` +
                    `input#mrvl_${type}_aprt_num, input#mrvl_${type}_entry_code`
            );
            // Attach event listeners to standard fields
            fields.forEach((field) => {
                field.addEventListener("change", () => handleFieldChange(type, field));
            });
            // Handle Select2 dropdowns separately
            jQuery(`select#mrvl_${type}_city, select#mrvl_${type}_street`).on("select2:select", function (e) {
                handleFieldChange(type, e.target);
            });
        });
    }
    // Function to handle field changes for both billing and shipping
    function handleFieldChange(type, target) {
        address_generator(type);

        if (target.matches(`select#mrvl_${type}_city`)) {
            if (type === "billing" && isShippingToDifferentAddress()) {
                return;
            }
            jQuery("body").trigger("update_checkout");
        }
    }

    // Call function to initialize event listeners
    initEventListeners();
});

// ===================================================
// show/hide custom fields based on country
// ===================================================
document.addEventListener("DOMContentLoaded", function () {
    // show hide custom fields based on the country selected
    function showHideFields(country, fieldset, targetedCountry) {
        if (country === targetedCountry) {
            address_generator(fieldset);
            // Handle city fields
            document.getElementById(`mrvl_${fieldset}_city_field`)?.classList.remove("hidden");
            // show custome fields
            document.getElementById(`mrvl_${fieldset}_street_field`)?.classList.remove("hidden");
            document.getElementById(`mrvl_${fieldset}_house_num_field`)?.classList.remove("hidden");
            document.getElementById(`mrvl_${fieldset}_aprt_num_field`)?.classList.remove("hidden");
            document.getElementById(`mrvl_${fieldset}_house_floor_field`)?.classList.remove("hidden");
            document.getElementById(`mrvl_${fieldset}_entry_code_field`)?.classList.remove("hidden");
            document.getElementById(`mrvl_${fieldset}_entrance_field`)?.classList.remove("hidden");
            // hide defaults fields
            document.getElementById(`${fieldset}_city_field`)?.classList.add("hidden");
            document.getElementById(`${fieldset}_address_1_field`)?.classList.add("hidden");
            document.getElementById(`${fieldset}_address_2_field`)?.classList.add("hidden");
        } else {
            const cityInput = document.querySelector(`input#${fieldset}_city`);
            const address1Input = document.querySelector(`input#${fieldset}_address_1`);
            const address2Input = document.querySelector(`input#${fieldset}_address_2`);

            // Function to clear the values
            if (cityInput) cityInput.value = "";
            if (address1Input) address1Input.value = "";
            if (address2Input) address2Input.value = "";

            // hide custome fields
            document.getElementById(`mrvl_${fieldset}_city_field`)?.classList.add("hidden");
            document.getElementById(`mrvl_${fieldset}_street_field`)?.classList.add("hidden");
            document.getElementById(`mrvl_${fieldset}_house_num_field`)?.classList.add("hidden");
            document.getElementById(`mrvl_${fieldset}_aprt_num_field`)?.classList.add("hidden");
            document.getElementById(`mrvl_${fieldset}_house_floor_field`)?.classList.add("hidden");
            document.getElementById(`mrvl_${fieldset}_entry_code_field`)?.classList.add("hidden");
            document.getElementById(`mrvl_${fieldset}_entrance_field`)?.classList.add("hidden");

            // show defaults fields
            document.getElementById(`${fieldset}_city_field`)?.classList.remove("hidden");
            document.getElementById(`${fieldset}_address_1_field`)?.classList.remove("hidden");
            document.getElementById(`${fieldset}_address_2_field`)?.classList.remove("hidden");
        }
    }

    // select2 dependant in jQuery
    jQuery(document).ready(function () {
        // Listen for the change event on the Select2 element
        jQuery("#billing_country").on("change", async function () {
            // await new Promise((resolve) => setTimeout(resolve, 100));
            // Get the selected value
            const selectedValue = jQuery(this).val();
            // Get the selected text
            const selectedText = jQuery(this).find(":selected").text().trim();
            // Perform actions
            showHideFields(selectedValue, "billing", "IL");
            if (!isShippingToDifferentAddress()) {
                jQuery("body").trigger("update_checkout");
            }
        });
        jQuery("#shipping_country").on("change", async function () {
            // await new Promise((resolve) => setTimeout(resolve, 100));
            // Get the selected value
            const selectedValue = jQuery(this).val();
            // Get the selected text
            const selectedText = jQuery(this).find(":selected").text().trim();
            // Perform actions
            showHideFields(selectedValue, "shipping", "IL");
            jQuery("body").trigger("update_checkout");
        });
        let selectedShippingValue = jQuery("#shipping_country").val();
        let selectedBillingValue = jQuery("#billing_country").val();
        showHideFields(selectedShippingValue, "shipping", "IL");
        showHideFields(selectedBillingValue, "billing", "IL");
    });
});

// ===================================================
// JS sleep function
// ===================================================
function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

// ===================================================
// dynamic streets
// ===================================================
jQuery(document).ready(async function ($) {
    // Selectors for billing and shipping fields
    const $billingCitySelect = $("#mrvl_billing_city");
    const $billingStreetSelect = $("#mrvl_billing_street");
    const $shippingCitySelect = $("#mrvl_shipping_city");
    const $shippingStreetSelect = $("#mrvl_shipping_street");

    // Example object from `readAndDecryptCitiesAndStreets`
    const citiesAndStreets = await readAndDecryptCitiesAndStreets();

    // Function to update street options based on selected city
    function updateStreetOptions(cityValue, $streetSelect) {
        // Clear current options
        $streetSelect.empty();

        if (!cityValue || !citiesAndStreets || !citiesAndStreets[cityValue]) {
            // If no valid city is selected, show a default option
            $streetSelect.append(
                $("<option>", {
                    value: "",
                    text: "בחרו עיר תחילה",
                })
            );
            $streetSelect.trigger("change"); // Refresh Select2
            return;
        }

        // Populate streets for the selected city
        const streets = citiesAndStreets[cityValue]?.city_streets || [];
        streets.forEach((street) => {
            $streetSelect.append(
                $("<option>", {
                    value: street, // Use the street name as the value
                    text: street, // Use the street name as the display text
                })
            );
        });

        $streetSelect.trigger("change"); // Refresh Select2
    }

    // Event listeners for billing fields
    $billingCitySelect.on("change", function () {
        const selectedCityValue = $(this).val();
        if (!isShippingToDifferentAddress()) {
            jQuery("body").trigger("update_checkout");
        }

        updateStreetOptions(selectedCityValue, $billingStreetSelect);
    });

    $billingStreetSelect.on("change", function () {
        address_generator("billing"); // Adjust this function to handle billing address
    });

    // Event listeners for shipping fields
    $shippingCitySelect.on("change", function () {
        const selectedCityValue = $(this).val();
        updateStreetOptions(selectedCityValue, $shippingStreetSelect);
    });

    $shippingStreetSelect.on("change", function () {
        address_generator("shipping"); // Adjust this function to handle shipping address
    });

    // Initialize both billing and shipping street fields with the default option
    updateStreetOptions(null, $billingStreetSelect);
    updateStreetOptions(null, $shippingStreetSelect);
});

// ===================================================
// decrypt
// ===================================================

async function readAndDecryptCitiesAndStreets() {
    const dbName = "MarvelousDB";
    const storeName = "ConfigStore";

    try {
        await manageCitiesAndStreets();

        const db = await openDatabase(dbName, storeName);

        // Read the encrypted data and IV from IndexedDB
        const encryptedData = await getField(db, storeName, "citiesAndStreets");

        if (!encryptedData) {
            console.error("Missing data in DB.");
            sendDiagnostics("readAndDecryptCitiesAndStreets: Missing data in IndexedDB");
            return null;
        }
        // Save new data in IndexedDB
        const domain = window.location.hostname;
        const key = CryptoJS.SHA256(domain).toString(CryptoJS.enc.Hex);
        const decrypted = CryptoJS.AES.decrypt(encryptedData, key);
        const citiesAndStreets = JSON.parse(decrypted.toString(CryptoJS.enc.Utf8));

        if (citiesAndStreets) {
            return citiesAndStreets;
        } else {
            return null;
        }
    } catch (error) {
        sendDiagnostics("readAndDecryptCitiesAndStreets: error reading and decrypting Cities and Streets:" + error);
        console.error("readAndDecryptCitiesAndStreets: error reading and decrypting Cities and Streets:\n", error);
        return null;
    }
}

// ===================================================
// Decrypt and parse the Marvel data
// ===================================================

function mrvlDecrypt(encodedData) {
    try {
        const decodedData = atob(encodedData); // Decode Base64
        const parsedObject = JSON.parse(decodedData); // Parse JSON if necessary
        return parsedObject;
    } catch (error) {
        sendDiagnostics("mrvlDecrypt: error:" + JSON.stringify(error));
        console.error("mrvlDecrypt error:\n", error);

        return null;
    }
}

// ===================================================
// get/update cities data
// ===================================================

async function manageCitiesAndStreets() {
    const dbName = "MarvelousDB";
    const storeName = "ConfigStore";
    const keyName = "configSignature";

    // Check IndexedDB and fetch data if necessary
    try {
        const db = await openDatabase(dbName, storeName);
        const currentSignature = await getField(db, storeName, keyName);

        if (currentSignature !== marvelousShipping.configSignature) {
            // Fetch data via AJAX
            const response = await fetch(marvelousShipping.ajaxurl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "get_site_cities",
                    nonce: marvelousShipping.nonce,
                }),
            });

            if (!response.ok) {
                return;
            }

            const result = await response.json();
            if (!result || !result.data || !result.data.configSignature || !result.data.citiesAndStreets) {
                return;
            }

            // Save new data in IndexedDB
            const domain = window.location.hostname;
            const decryptedObj = mrvlDecrypt(result.data.citiesAndStreets);
            const key = CryptoJS.SHA256(domain).toString(CryptoJS.enc.Hex);
            const encrypted = CryptoJS.AES.encrypt(JSON.stringify(decryptedObj), key).toString();
            // Encrypt and save new data in IndexedDB
            await saveField(db, storeName, keyName, result.data.configSignature);
            await saveField(db, storeName, "citiesAndStreets", encrypted);
        }
    } catch (error) {
        sendDiagnostics("manageCitiesAndStreets: error\n" + JSON.stringify(error));
        console.error("manageCitiesAndStreets error:\n", error);
    }
}

// ===================================================
// Helper: Open IndexedDB
// ===================================================
async function openDatabase(dbName, storeName) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName, 1);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(storeName)) {
                db.createObjectStore(storeName, { keyPath: "key" });
            }
        };

        request.onsuccess = (event) => resolve(event.target.result);
        request.onerror = (event) => reject(event.target.error);
    });
}
// ===================================================
// Helper: Get field from IndexedDB
// ===================================================

async function getField(db, storeName, key) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], "readonly");
        const store = transaction.objectStore(storeName);
        const request = store.get(key);

        request.onsuccess = (event) => resolve(event.target.result?.value || null);
        request.onerror = (event) => reject(event.target.error);
    });
}
// ===================================================
// Helper: Save field in IndexedDB
// ===================================================

async function saveField(db, storeName, key, value) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], "readwrite");
        const store = transaction.objectStore(storeName);
        const request = store.put({ key, value });

        request.onsuccess = () => resolve(true);
        request.onerror = (event) => reject(event.target.error);
    });
}

// ===================================================
// remove Select2 Options
// ===================================================
// Function to disable specific options in a Select2 dropdown with tooltips
function removeSelect2Options(textsToDisable) {
    ["#mrvl_billing_city", "#mrvl_shipping_city"].forEach((field) => {
        const selectElement = jQuery(field);

        // Disable options and add necessary attributes to the original options
        textsToDisable.forEach((value) => {
            const option = selectElement.find(`option`).filter(function () {
                return jQuery(this).val().trim() === value; // Compare the value attribute
            });

            if (option.length) {
                option.remove(); // Remove the option
            }
        });
    });
}
// ===================================================
// init select2 fields
// ===================================================

jQuery(document).ready(async function () {
    try {
        jQuery(`#mrvl_billing_city`).select2({
            searching: function () {
                return "מחפש...";
            },
            dir: "rtl",
            language: {
                noResults: function () {
                    // Fetch the search input value directly from the DOM
                    const searchInput = document.querySelector(".select2-search__field");
                    const searchText = searchInput ? searchInput.value : "לאזור הזה";

                    // Use the fetched text in your custom noResults message
                    return `אנחנו לא שולחים לאזור של ${searchText}`;
                },
            },
            escapeMarkup: function (markup) {
                return markup; // Allow HTML if needed
            },
        });
        jQuery(`#mrvl_shipping_city`).select2({
            searching: function () {
                return "מחפש...";
            },
            dir: "rtl",
            language: {
                noResults: function () {
                    // Fetch the search input value directly from the DOM
                    const searchInput = document.querySelector(".select2-search__field");
                    const searchText = searchInput ? searchInput.value : "לאזור הזה";

                    // Use the fetched text in your custom noResults message
                    return `אנחנו לא שולחים לאזור של ${searchText}`;
                },
            },
            escapeMarkup: function (markup) {
                return markup; // Allow HTML if needed
            },
        });
        jQuery(`#mrvl_billing_street`).select2({
            searching: function () {
                return "מחפש...";
            },
            dir: "rtl",
            language: {
                noResults: function () {
                    // Fetch the search input value directly from the DOM
                    const searchInput = document.querySelector(".select2-search__field");
                    const searchText = searchInput ? searchInput.value : "לאזור הזה";

                    // Use the fetched text in your custom noResults message
                    return `אין תוצאות עבור רחוב ${searchText}`;
                },
            },
            escapeMarkup: function (markup) {
                return markup; // Allow HTML if needed
            },
        });
        jQuery(`#mrvl_shipping_street`).select2({
            searching: function () {
                return "מחפש...";
            },
            dir: "rtl",
            language: {
                noResults: function () {
                    // Fetch the search input value directly from the DOM
                    const searchInput = document.querySelector(".select2-search__field");
                    const searchText = searchInput ? searchInput.value : "לאזור הזה";

                    // Use the fetched text in your custom noResults message
                    return `אין תוצאות עבור רחוב ${searchText}`;
                },
            },
            escapeMarkup: function (markup) {
                return markup; // Allow HTML if needed
            },
        });

        // remove all places where we doent send
        let removeCities = [];
        const citiesAndStreets = await readAndDecryptCitiesAndStreets();
        for (const cityName in citiesAndStreets) {
            const cityData = citiesAndStreets[cityName];
            if (cityData && !cityData.city_allowed) {
                removeCities.push(cityName);
            }
        }
        removeSelect2Options(removeCities);

        // set the current shipping city
        if (marvelousShipping.shippingCity) {
            // Find the option with the matching text
            const matchingOption = jQuery(`#mrvl_billing_city option`).filter(function () {
                return jQuery(this).text().trim() === marvelousShipping.shippingCity;
            });

            // If a matching option is found, set it as the value
            if (matchingOption.length > 0) {
                const matchingValue = matchingOption.val(); // Get the value of the matching option
                jQuery("#mrvl_billing_city").val(matchingValue).trigger("change"); // Set and trigger change
            }
        }
    } catch (error) {
        sendDiagnostics("init select2 function got error:\n" + JSON.stringify(error));
        console.error("init select2 function got error:\n", error);
    }
});

// ===================================================
// show user msgs about fees
// ===================================================
jQuery(document).ready(function ($) {
    try {
        // Create a MutationObserver to monitor changes
        const observer = new MutationObserver(function (mutationsList) {
            mutationsList.forEach(function (mutation) {
                if (mutation.addedNodes.length > 0) {
                    const messages = $(".custom-shipping-fees-message");

                    if (messages.length > 0) {
                        const lastMessage = messages.last();
                        const messageContent = lastMessage.find("p").html();

                        // Split the message by <br> tag
                        const [cityMsg] = messageContent.split("\n").map((msg) => msg.trim());

                        // Target the shipping method list
                        const shippingUl = $(".woocommerce-shipping-methods");
                        const cityMsgId = "marvelous-city-fees-msg";

                        // Add or update the city shipping fee message
                        let cityLi = shippingUl.find(`#${cityMsgId}`);
                        if (cityLi.length === 0) {
                            // If the <li> doesn't exist, create and append it
                            shippingUl.append(`<li id="${cityMsgId}">${cityMsg || ""}</li>`);
                        } else {
                            // If the <li> exists, update its content
                            cityLi.html(cityMsg || "");
                        }

                        // Remove all instances of .custom-shipping-fees-message
                        messages.remove();
                    }
                }
            });
        });

        // Target WooCommerce checkout container
        const targetNode = document.querySelector("#order_review");

        if (targetNode) {
            observer.observe(targetNode, { childList: true, subtree: true });
        }

        // Clean up observer when not needed
        $("body").on("checkout_place_order", function () {
            observer.disconnect();
        });
    } catch (error) {
        console.error("MutationObserver JS func got exception:\n", error);
        sendDiagnostics("MutationObserver JS func got exception:\n" + JSON.stringify(error));
    }
});

// ===================================================
// send Diagnostics
// ===================================================

async function sendDiagnostics(message) {
    try {
        if (marvelousShipping.diagnosticsAllowed !== "1") {
            return;
        }

        // Capture the stack trace
        const error = new Error();
        const stackTrace = error.stack || "Stack trace not available";

        // Prepare the payload
        const payload = new URLSearchParams({
            action: "mrvl_send_diagnostics",
            message: message,
            stack_trace: stackTrace,
            timestamp: new Date().toISOString(),
            nonce: marvelousShipping.nonce,
        });

        // Send the POST request
        await fetch(marvelousShipping.ajaxurl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: payload,
        });
    } catch (error) {
        console.error("exception in sendDiagnostics:", error);
    }
}

// ===================================================
//
// ===================================================
