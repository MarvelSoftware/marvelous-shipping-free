// ===================================================
// changes tracker
// ===================================================
var optionsChanges = [];
var pricesChanges = [];
var restrictionsChanges = [];
var returnToCloudList = false;
var stopCloudRestore = false;
var rangesChanges = false;
var firstSwap = true;
var disableChanges = true;
var hideDistrictPopupEnable = true;
var confirmMsgAction = "no-action";
const israelDistrictsEn2Heb = {
    tlv: "תל אביב - יפו",
    merkaz: "מחוז מרכז",
    haifa: "מחוז חיפה",
    tzafon: "מחוז צפון",
    upper_south: "דרום עליון",
    lower_south: "דרום תחתון",
    green_zone: "יהודה ושומרון",
    golan: "רמת הגולן",
    jerusalem: "מחוז ירושלים",
};

// Define a function to update or add an option
function updateOrAddOption(name, newValue) {
    // Find the index of an existing entry with the same option
    const existingIndex = optionsChanges.findIndex((change) => change.name === name);

    if (existingIndex !== -1) {
        // Replace the value of the existing entry
        optionsChanges[existingIndex].value = newValue;
    } else {
        // Push a new entry if it doesn't exist
        optionsChanges.push({ name, value: newValue });
    }
}

// ===================================================
// Scribe pricing handler
// ===================================================
function optionsScriberHandler(name, newValue) {
    // scribe option change
    const optionsMap = {
        "send-diagnostics-checkbox": () => updateOrAddOption("send_diagnostics_active", newValue),
        "show-checkout-floor-checkbox": () => updateOrAddOption("floor_field_active", newValue),
        "show-checkout-aprtment-num-checkbox": () => updateOrAddOption("apartment_field_active", newValue),
        "show-checkout-entrance-code-checkbox": () => updateOrAddOption("building_code_field_active", newValue),
        "fast-comments-checkbox": () => updateOrAddOption("fast_msgs_active", newValue),
    };

    // Execute the action based on the name
    if (optionsMap[name]) {
        optionsMap[name]();
    }
}
// ===================================================
// Scribe restrictions handler (allow/disallow areas)
// ===================================================

function restrictionsScriberHandler(name, newValue) {
    // Determine if the input is for a city or a district
    let type = ""; // "city" or "district"
    let entityName = ""; // Extracted city or district name

    if (name.includes("-allow-city")) {
        type = "city";
        entityName = name.replace("-allow-city", ""); // Extract city name
    } else if (name.includes("-allow-region")) {
        type = "district";
        entityName = name.replace("-allow-region", ""); // Extract district name
    }

    if (type) {
        // Construct the name for restrictionsChanges
        const restrictionName = `${type}-${entityName}-allow`;

        // Check if the option already exists in restrictionsChanges
        const existingIndex = restrictionsChanges.findIndex((change) => change.name === restrictionName);

        if (existingIndex !== -1) {
            // If it exists, update the value
            restrictionsChanges[existingIndex].value = newValue;
        } else {
            // If it doesn't exist, add a new entry
            restrictionsChanges.push({ name: restrictionName, value: newValue });
        }
    }
}
// ===================================================
// Scribe options handler
// ===================================================

function pricingScriberHandler(name, newValue) {
    if (name === "global-price-input") {
        // Global price change: clear array and add/update global price
        pricesChanges = [{ name, value: newValue }];
    } else if (name.endsWith("-district-price")) {
        // District price change: add/update district price in the array
        const existingChangeIndex = pricesChanges.findIndex((change) => change.name === name);
        if (existingChangeIndex !== -1) {
            pricesChanges[existingChangeIndex].value = newValue;
        } else {
            pricesChanges.push({ name, value: newValue });
        }
    } else if (name.startsWith("city-price-")) {
        // Specific city price change: add/update city price in the array
        const existingChangeIndex = pricesChanges.findIndex((change) => change.name === name);
        if (existingChangeIndex !== -1) {
            pricesChanges[existingChangeIndex].value = newValue;
        } else {
            pricesChanges.push({ name, value: newValue });
        }
    }
}

// ===================================================
// Scriber
// ===================================================
function changesScriber(name, newValue) {
    if (disableChanges) {
        return;
    }

    // handle pricing
    if (name.includes("price") && !name.includes("extra-")) {
        pricingScriberHandler(name, newValue);
    }
    // handle allow/disallow regions
    else if (name.includes("allow-")) {
        restrictionsScriberHandler(name, newValue);
    }
    // handle options
    else {
        optionsScriberHandler(name, newValue);
    }

    // if name includes
}
// ===================================================
// Limit input length and track changes
// ===================================================
document.addEventListener("DOMContentLoaded", function () {
    // Select all inputs you want to track
    const inputs = document.querySelectorAll(`
        input[type="checkbox"],
        input[type="text"]
    `);

    // Attach listeners to each input
    inputs.forEach((input) => {
        if (
            input.classList.contains("price-input") || // Fix for checking class
            input.id === "base-price-input"
        ) {
            // Limit input to max 6 characters
            input.addEventListener("input", function () {
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }
            });
            if (input.id === "base-price-input") {
                return;
            }
        } else if (input.id === "manual-city-search") {
            // Limit input to max 40 characters
            input.addEventListener("input", function () {
                if (this.value.length > 40) {
                    this.value = this.value.slice(0, 40);
                }
            });
            return;
        }

        // Determine the event type based on input type
        const eventType = input.type === "checkbox" ? "change" : "input";

        // Add event listener for tracking changes
        input.addEventListener(eventType, function () {
            const newValue = input.type === "checkbox" ? input.checked : input.value;
            changesScriber(input.id, newValue);
        });
    });
});

// ===================================================
// fast comments checkbox
// ===================================================
document.addEventListener("DOMContentLoaded", function () {
    const container = document.querySelector(".comments-overlay-container");
    const checkbox = document.getElementById("fast-comments-checkbox");
    if (!checkbox || !container) {
        return;
    }
    // Toggle the disabled state based on the checkbox
    checkbox.addEventListener("change", function () {
        if (this.checked) {
            container.classList.remove("disabled"); // Enable the comments container
        } else {
            container.classList.add("disabled"); // Disable the comments container
        }
    });

    // Initialize the state based on the checkbox value
    if (!checkbox.checked) {
        container.classList.add("disabled");
    }
});

// ===================================================
// JS sleep function
// ===================================================
function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

// ===================================================
// upgrade-to-premium
// ===================================================

document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("upgrade-to-premium-btn")?.addEventListener("click", function (event) {
        event.preventDefault(); // Prevents default behavior (if it's inside a form)
        window.open("https://mrvlsol.co.il/?upgrade_marvelous_shipping&utm_source=wp_admin_free_ver", "_blank");
    });
});

// ===================================================
// Show/Hide msg popup
// ===================================================
function showMsgWindow(msg, type, confirmAction = "no-action", hideCancelButton = false) {
    // Get the overlay and message window elements
    const overlay = document.getElementById("spinner-msg-overlay");
    const msgWindow = document.getElementById("spinner-msg-window");

    // Update the content of the message
    const msgContent = document.querySelector(".msg-content");
    msgContent.innerHTML = msg;

    // Update the SVG based on the type
    const msgIcon = document.getElementById("msg-icon");
    let svg;

    switch (type) {
        case "question":
            svg = mrvlAdmin.questionSvg; // Use localized SVG data
            break;
        case "confirm":
            svg = mrvlAdmin.confirmSvg; // Use localized SVG data
            break;
        case "warning":
            svg = mrvlAdmin.warningSvg; // Use localized SVG data
            break;
        default:
            svg = mrvlAdmin.warningSvg; // Use localized SVG data
    }
    confirmMsgAction = confirmAction;
    msgIcon.innerHTML = svg;

    // Show or hide the cancel button
    const cancelButton = msgWindow.querySelector(".cancel-button");
    if (cancelButton) {
        if (hideCancelButton) {
            cancelButton.classList.add("hidden");
        } else {
            cancelButton.classList.remove("hidden");
        }
    }

    // Remove focus from the currently focused element
    if (document.activeElement && document.activeElement !== document.body) {
        document.activeElement.blur();
    }

    // Show the overlay and apply the animation classes
    overlay.style.display = "flex";
    requestAnimationFrame(() => {
        overlay.classList.add("active");
        msgWindow.classList.add("active");
    });
    // Add the Esc key listener
    function handleEscKey(event) {
        if (event.key === "Escape") {
            hideMsgWindow();
            document.removeEventListener("keydown", handleEscKey);
        }

        if (event.key === "Enter") {
            event.preventDefault();
            const confirmButton = document.querySelector("#msg-confirm-button"); // Correct selector
            if (confirmButton) {
                confirmButton.click(); // Simulate a click on the confirm button
            }
            document.removeEventListener("keydown", handleEscKey);
        }
    }
    document.addEventListener("keydown", handleEscKey);
}

document.addEventListener("DOMContentLoaded", function () {
    // Select the close and cancel buttons
    const closeButton = document.querySelector(".close-button");
    const closeImportButton = document.querySelector("#close-import-button");
    const closeCloudBackupsButton = document.querySelector("#close-cloud-backups-button");
    const cancelButton = document.querySelector(".cancel-button");
    const confirmButton = document.querySelector("#msg-confirm-button");
    const importFromFileButton = document.querySelector("#import-msg-file-button");
    const importFromCloudButton = document.querySelector("#import-msg-cloud-button");

    closeCloudBackupsButton?.addEventListener("click", function (event) {
        event.preventDefault();
        stopCloudRestore = true;
        hideCloudBackupsPopup();
        resetCloudBackupsPopup();
    });
    closeImportButton?.addEventListener("click", function (event) {
        event.preventDefault();
        hideImportPopup();
    });
    closeButton?.addEventListener("click", function (event) {
        event.preventDefault();
        hideMsgWindow(); // Call the hide function when clicked
    });
    cancelButton?.addEventListener("click", function (event) {
        event.preventDefault();
        hideMsgWindow(); // Call the hide function when clicked
    });
    confirmButton?.addEventListener("click", function (event) {
        event.preventDefault();
        confirmMsgHandler(); // Call the hide function when clicked
    });
    importFromFileButton?.addEventListener("click", function (event) {
        event.preventDefault();
        handleImportSettingsFile();
    });
    importFromCloudButton?.addEventListener("click", function (event) {
        event.preventDefault();
        if (mrvlAdmin.licensePackage !== "ultimate") {
            return;
        }
        stopCloudRestore = false;
        handleImportSettingsFromCloud();
    });
});

function hideMsgWindow() {
    // Get the overlay and message window elements
    const overlay = document.getElementById("spinner-msg-overlay");
    const msgWindow = document.getElementById("spinner-msg-window");

    // Add a class to trigger the hide animation
    msgWindow.classList.add("hide");
    overlay.classList.add("hide");

    // Wait for the animation to complete before resetting the display
    setTimeout(() => {
        msgWindow.classList.remove("active", "hide"); // Remove both animation classes
        overlay.classList.remove("active", "hide");
        overlay.style.display = "none";
        if (returnToCloudList) {
            returnToCloudList = false;
            showCloudBackupsPopup();
        }
    }, 300); // Match the CSS transition duration
}
function confirmMsgHandler() {
    if (!confirmMsgAction) {
        confirmMsgAction = "no-action";
        hideMsgWindow();
    } else if (confirmMsgAction === "no-action") {
        hideMsgWindow();
    } else if (confirmMsgAction === "enable-hide-district-popup") {
        confirmMsgAction = "no-action";
        hideMsgWindow();
        setTimeout(() => {
            hideDistrictPopupEnable = true;
        }, 300);
    }
}

// ===================================================
// show/hide spinner
// ===================================================

function showSpinner(message = "טוען תוסף נא להמתין") {
    const spinnerOverlay = document.querySelector(".spinner-overlay");
    const loaderContainer = document.querySelector(".loader-container");
    const messageElement = loaderContainer.querySelector("h2");

    if (spinnerOverlay && loaderContainer) {
        // Update the message
        messageElement.textContent = message;
        messageElement.style.direction = "rtl";
        // Show the spinner
        spinnerOverlay.style.display = "block";
        requestAnimationFrame(() => {
            spinnerOverlay.classList.add("active");
            loaderContainer.classList.add("active");
        });
    }
}

function hideSpinner() {
    try {
        const spinnerOverlay = document.getElementById("spinner-overlay-id");
        const loaderContainer = document.getElementById("loader-container-id");
        if (spinnerOverlay && loaderContainer) {
            // Hide the spinner
            loaderContainer.classList.remove("active");
            spinnerOverlay.classList.remove("active");

            // Wait for the animation to finish before hiding the element
            setTimeout(() => {
                spinnerOverlay.style.display = "none";
            }, 300); // Match the CSS transition duration
        }
    } catch (error) {
        console.error("hideSpinner exception:\n", error);
        sendDiagnostics("hideSpinner exception\n" + JSON.stringify(error));
    }
}

// ===================================================
// Utility function to format date
// ===================================================

function formatDate(dateString) {
    // Split the date and time parts
    const [datePart, timePart] = dateString.split(" ");
    const [day, month, year] = datePart.split("/").map(Number); // Extract DD, MM, YYYY
    const [hours, minutes, seconds] = timePart.split(":").map(Number); // Extract HH, MM, SS

    // Create a new Date object using the extracted values
    const date = new Date(year, month - 1, day, hours, minutes, seconds);

    const pad = (num) => String(num).padStart(2, "0");

    return {
        date: `${pad(date.getDate())}-${pad(date.getMonth() + 1)}-${date.getFullYear()}`,
        time: `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`,
    };
}
// ===================================================
// ===================================================

function whatsappWithMarvel() {
    window.open("https://wa.me/+972524787482", "_blank");
}
// ===================================================
// ===================================================

function openMarvelWebsite() {
    window.open("https://mrvlsol.co.il/", "_blank");
}

// ===================================================
// on load
// ===================================================
async function resetUpdateStatus() {
    // Make the AJAX request
    const response = await fetch(ajaxurl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            action: "reset_update_status",
            nonce: mrvlAdmin.nonce, // Pass the nonce
        }),
    });
}
// ===================================================
// on load
// ===================================================

window.addEventListener("load", async () => {
    disableChanges = false;
    const toolbar = document.querySelector(".wp-toolbar");
    if (toolbar) {
        requestAnimationFrame(() => {
            setTimeout(() => {
                toolbar.scrollLeft = 0;
            }, 50); // Adjust delay if necessary
        });
    }
    hideSpinner();
});

// ===================================================
// onbeforeunload
// ===================================================

// Define a custom getter and setter for window.onbeforeunload
let originalBeforeUnload = window.onbeforeunload;

Object.defineProperty(window, "onbeforeunload", {
    get: function () {
        return originalBeforeUnload;
    },
    set: function (value) {
        // Prevent overwriting
        originalBeforeUnload = null;
    },
});

// ===================================================
// handle global price setting field
// ===================================================

document.addEventListener("DOMContentLoaded", function () {
    // Select the input field, clean button, and set button
    const basePriceInput = document.getElementById("base-price-input");
    const cleanButton = document.getElementById("clean-global-price-input");
    const setButton = document.getElementById("set-global-price");

    // Select the container holding all the rows with .price-input
    const rowsContainer = document.querySelector(".rows-container");

    // Clean button functionality
    cleanButton.addEventListener("click", function (event) {
        event.preventDefault();
        basePriceInput.value = ""; // Clear the input field
    });

    // Set button functionality
    setButton.addEventListener("click", function (event) {
        event.preventDefault();
        disableChanges = true;
        let globalPrice;
        try {
            globalPrice = basePriceInput.value.trim();

            // If the global price is empty, do nothing
            if (!globalPrice) {
                showMsgWindow("יש להזין מחיר תחילה!", "warning", "no-action", true);
                // alדert("נא להזין מחיר גלובלי לפני החלת המחיר.");
                disableChanges = false;
                return;
            }

            // Set the value of all .price-input fields within the .rows-container
            const priceInputs = rowsContainer.querySelectorAll(".price-input");
            priceInputs.forEach((input) => {
                input.value = globalPrice;
            });
            // Select all inputs with the class "price-input"
            const districtsPriceInputs = document.querySelectorAll(".district-price-input");

            // Loop through each input and set its value
            districtsPriceInputs.forEach((input) => {
                input.value = globalPrice;
            });

            // showSuccessAnimation();
            showMsgWindow(
                "מחיר גלובלי " + globalPrice + ' ש"ח הוחל בהצלחה על כל היישובים!<br>ניתן להמשיך בהגדרה ידנית',
                "confirm",
                "no-action",
                true
            );

            // alדert(`מחיר גלובלי ${globalPrice} הוחל בהצלחה על כל היישובים.`);
        } catch (error) {
            console.error("Set gloabl price exception:\n", error);
            sendDiagnostics("Set gloabl price exception\n" + JSON.stringify(error));
        }
        disableChanges = false;
        changesScriber("global-price-input", globalPrice);
    });
});

// ===================================================
// price input and localization function
// ===================================================

function priceInputFormatter(inputs) {
    // Localization formatter
    const formatter = new Intl.NumberFormat("he-IL", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    inputs.forEach((input) => {
        // Add input event listener
        input.addEventListener("input", function () {
            let rawValue = input.value;

            // Allow only valid characters: digits and a single decimal point
            rawValue = rawValue.replace(/[^0-9.]/g, "");

            // Prevent multiple decimal points
            const parts = rawValue.split(".");

            if (parts.length > 2) {
                rawValue = parts[0] + "." + parts.slice(1).join("");
            }

            // Allow incomplete decimal input like "0." or "0.3"
            if (rawValue.endsWith(".")) {
                input.value = rawValue.toString(); // Save as-is for temporary state
                return; // Don't format yet
            }

            // Format the value only when it's a valid number
            const numberValue = parseFloat(rawValue);

            if (!isNaN(numberValue)) {
                input.value = formatter.format(numberValue).toString();
            } else {
                input.value = "0";
            }
        });
    });
}

// ===================================================
// handle price input and localize it
// ===================================================

document.addEventListener("DOMContentLoaded", function () {
    // Select all inputs with the class 'price-input' and #base-price-input
    const priceInputs = document.querySelectorAll(".price-input, #base-price-input");
    priceInputFormatter(priceInputs);
});

// ===================================================
// change WC default save button
// ===================================================

document.addEventListener("DOMContentLoaded", function () {
    // Find the Save Changes button in the WooCommerce settings tab
    const saveButton = document.querySelector('button[name="save"]');
    const notValidMsg = document.querySelector(".license-message");

    // Replace its class with a new one if it exists
    if (notValidMsg) {
        saveButton.classList.add("hidden"); // Add your custom class
    } else if (saveButton) {
        saveButton.classList.add("cool-button"); // Add your custom class
        saveButton.removeAttribute("disabled");
    }
});

// ===================================================
// animate the info SVGs
// ===================================================
var isPopupVisible = false;
document.addEventListener("DOMContentLoaded", function () {
    const infoIconContainers = document.querySelectorAll(".info-icon-container"); // Select all icon containers

    infoIconContainers.forEach((infoIconContainer) => {
        const svgElement = infoIconContainer.querySelector("svg"); // Get the SVG inside the container
        const infoWindow = infoIconContainer.querySelector(".info-window"); // Get the associated info window
        let fadeOutTimeout;

        // Show the popup only when hovering over the SVG
        svgElement.addEventListener("mouseenter", () => {
            clearTimeout(fadeOutTimeout); // Prevent fade-out if the mouse re-enters
            infoWindow.classList.add("visible");
        });

        svgElement.addEventListener("mouseleave", () => {
            fadeOutTimeout = setTimeout(() => {
                infoWindow.classList.remove("visible");
            }, 200); // Delay fade-out by 200ms
        });

        // Ensure the popup stays open if the mouse moves over it
        infoWindow.addEventListener("mouseenter", () => {
            clearTimeout(fadeOutTimeout); // Cancel fade-out on re-enter
        });

        infoWindow.addEventListener("mouseleave", () => {
            fadeOutTimeout = setTimeout(() => {
                infoWindow.classList.remove("visible");
            }, 200); // Delay fade-out on leaving the popup
        });
    });
});

// ===================================================
// buttons tooltip
// ===================================================
document.addEventListener("DOMContentLoaded", function () {
    // Create the floating tooltip
    const floatingTooltip = document.createElement("div");
    floatingTooltip.classList.add("buttons-floating-tooltip"); // Add the CSS class
    document.body.appendChild(floatingTooltip);

    // Button-to-tooltip mapping
    const tooltips = {
        "main-mrvl-image": "Marvelous Software Solutions",
        "whatsapp-svg": "דברו איתנו ב-Whatsapp",
    };
    const colors = {
        "main-mrvl-image": "lightblue",
        "whatsapp-svg": "greenyellow",
    };

    // Attach event listeners to the buttons
    ["main-mrvl-image", "whatsapp-svg"].forEach(function (buttonId) {
        const button = document.getElementById(buttonId);
        if (!button) return;

        button.addEventListener("mouseenter", function (event) {
            // Set the tooltip content based on the button
            floatingTooltip.textContent = tooltips[buttonId];

            // Position and show the tooltip
            const buttonRect = event.target.getBoundingClientRect();
            if (button.id === "main-mrvl-image") {
                floatingTooltip.style.left = `${
                    buttonRect.left + window.scrollX + buttonRect.width / 2 - floatingTooltip.offsetWidth / 2
                }px`;
            } else {
                floatingTooltip.style.left = `${
                    buttonRect.left + window.scrollX + buttonRect.width / 2 - floatingTooltip.offsetWidth / 2
                }px`;
            }

            floatingTooltip.style.top = `${buttonRect.top + window.scrollY - floatingTooltip.offsetHeight - 10}px`;
            floatingTooltip.style.fontWeight = 500; // Show the tooltip
            floatingTooltip.style.color = colors[buttonId]; // Show the tooltip
            floatingTooltip.style.display = "block"; // Show the tooltip
            floatingTooltip.style.zIndex = "20000"; // Show the tooltip
        });

        button.addEventListener("mouseleave", function () {
            floatingTooltip.style.display = "none"; // Hide the tooltip on mouse leave
        });

        button.addEventListener("mousemove", function (event) {
            // Make the tooltip follow the mouse
            if (button.id === "main-mrvl-image") {
                floatingTooltip.style.left = `${event.pageX - floatingTooltip.offsetWidth}px`;
            } else {
                floatingTooltip.style.left = `${event.pageX + 10}px`;
            }

            floatingTooltip.style.top = `${event.pageY - floatingTooltip.offsetHeight - 10}px`;
        });
    });
});

// ===================================================
// manual cities checkboxes
// ===================================================

document.addEventListener("DOMContentLoaded", () => {
    // Select all checkboxes with the class `allow-region-checkbox`
    const checkboxes = document.querySelectorAll(".allow-region-checkbox");

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
            const row = checkbox.closest(".row"); // Find the parent row
            if (!row) {
                return;
            }
            const priceInput = row.querySelector(".price-input"); // Find the associated price input
            if (checkbox.checked) {
                // Enable price input and set SVG to active class
                priceInput.disabled = false;
            } else {
                // Disable price input and set SVG to disabled class
                priceInput.disabled = true;
            }
        });

        // Trigger the event listener on page load to set the initial state
        checkbox.dispatchEvent(new Event("change"));
    });
});

// ===================================================
// Shipping by Districts Chart
// ===================================================
document.addEventListener("DOMContentLoaded", () => {
    // Extract the data from the hidden <p> tag
    const dataTag = document.getElementById("districts-chart-data");
    const rawData = dataTag.textContent.trim(); // Get the text inside the <p> tag
    const dataArray = rawData.split(","); // Split the string into an array

    // Parse the data into labels and values
    const labels = [];
    const chartData = [];
    for (let i = 0; i < dataArray.length; i += 2) {
        if (dataArray[i] === "") {
            continue;
        }
        if (dataArray[i] === "תל אביב - יפו") {
            labels.push("מחוז מרכז (ללא תל-אביב)"); // Add district name
        } else {
            labels.push(dataArray[i]); // Add district name
        }
        chartData.push(parseInt(dataArray[i + 1], 10)); // Add count as an integer
    }

    // Remove the hidden <p> tag from the DOM
    dataTag.remove();

    // Handle edge cases
    if (chartData.reduce((sum, value) => sum + value, 0) === 0) {
        // Case 2: Size is 9 but all values are zero
        labels.length = 0;
        chartData.length = 0;
        labels.push("לא קיימים עדיין נתונים");
        chartData.push(1);
    }
    const ctx = document.getElementById("deliveryPieChart").getContext("2d");
    const data = {
        labels: labels,
        datasets: [
            {
                label: "התפלגות משלוחים לפי אזור",
                data: chartData,
                backgroundColor: [
                    "rgba(255, 99, 132, 0.6)",
                    "rgba(54, 162, 235, 0.6)",
                    "rgba(255, 206, 86, 0.6)",
                    "rgba(75, 192, 192, 0.6)",
                    "rgba(153, 102, 255, 0.6)",
                    "rgba(255, 159, 64, 0.6)",
                    "rgba(99, 255, 132, 0.6)",
                    "rgba(192, 75, 192, 0.6)",
                    "rgba(255, 159, 132, 0.6)",
                ],
                borderColor: [
                    "rgba(255, 99, 132, 1)",
                    "rgba(54, 162, 235, 1)",
                    "rgba(255, 206, 86, 1)",
                    "rgba(75, 192, 192, 1)",
                    "rgba(153, 102, 255, 1)",
                    "rgba(255, 159, 64, 1)",
                    "rgba(99, 255, 132, 1)",
                    "rgba(192, 75, 192, 1)",
                    "rgba(255, 159, 132, 1)",
                ],
                borderWidth: 1,
            },
        ],
    };

    const config = {
        type: "doughnut",
        data: data,
        options: {
            responsive: true, // Chart adjusts to canvas size
            maintainAspectRatio: true, // Ensures aspect ratio is maintained
            plugins: {
                legend: {
                    position: "right",
                    align: "center", // Align legend items to the start for proper layout

                    rtl: true, // Enable right-to-left layout
                    textDirection: "rtl", // Force text direction to be RTL
                    labels: {
                        // usePointStyle: true, // Use circle instead of rectangle for color box (optional)
                        padding: 20, // Adds spacing between legend items
                        font: {
                            size: 20, // Sets label font size to 20px
                        },
                    },
                    onHover: function (event) {
                        const canvas = event.native.target;
                        canvas.style.cursor = "pointer"; // Change cursor to pointer
                    },
                },
                tooltip: {
                    rtl: true, // Enable RTL layout in tooltips
                    textDirection: "rtl", // Set text flow to RTL
                    bodyFont: {
                        size: 18, // Tooltip body font size
                    },
                    titleFont: {
                        size: 20, // Tooltip title font size
                    },
                    callbacks: {
                        labelTextColor: () => "right", // Align text to the right
                    },
                    padding: 20,
                    callbacks: {
                        label: function (context) {
                            return `${context.label}: ${context.raw}`;
                        },
                    },
                },
            },
            layout: {
                padding: {
                    left: 50, // Adds some padding for RTL layout
                    right: 50,
                },
            },
            hover: {
                mode: "nearest", // Determines how hover interacts with the chart
                onHover: function (event, elements) {
                    const canvas = event.native.target;
                },
            },
            elements: {
                arc: {
                    hoverBackgroundColor: function (ctx) {
                        const originalColor = ctx.dataset.backgroundColor[ctx.dataIndex];
                        return originalColor.replace("0.6", "1"); // Lightens the color by 20%
                    },
                    hoverBorderColor: "black", // Sets border color when hovered
                    hoverBorderWidth: 2, // Sets border width when hovered
                },
            },
        },
    };

    new Chart(ctx, config);
});

// ===================================================
// save settings AJAX
// ===================================================
document.addEventListener("DOMContentLoaded", () => {
    const saveButton = document.querySelector('[name="save"]');

    if (saveButton) {
        saveButton.addEventListener("click", async (event) => {
            event.preventDefault();

            if (!rangesChanges && optionsChanges?.length + pricesChanges?.length + restrictionsChanges?.length === 0) {
                showMsgWindow("אין מה לשמור - לא ביצעתם שינויים", "question", "no-action", true);
                setTimeout(() => {
                    saveButton.classList.remove("is-busy");
                }, 100);
                return;
            }
            showSpinner("מחיל שינויים");

            let settings = {
                optionsChanges,
                pricesChanges,
                restrictionsChanges,
            };
            const security = mrvlAdmin.nonce; // Your localized nonce

            try {
                // Prepare data for URL-encoded format
                const formData = new URLSearchParams({
                    action: "marvelous_shipping_save_settings", // WordPress AJAX action
                    nonce: security, // Security nonce
                    settings: JSON.stringify(settings), // Encode settings as a JSON string
                });

                const response = await fetch(ajaxurl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: formData.toString(), // Convert to URL-encoded string
                });

                const result = await response.json();

                if (result.success) {
                    showMsgWindow("הגדרות נשמרו בהצלחה!<br>אין צורך לרענן את העמוד.", "confirm", "no-action", true);
                    // reset changes
                    optionsChanges = [];
                    pricesChanges = [];
                    restrictionsChanges = [];
                    rangesChanges = false;
                    saveButton.classList.remove("is-busy");
                    hideSpinner();
                } else {
                    showMsgWindow("התרחשה שגיאה בעת שמירת ההגדרות", "warning", "no-action", true);
                    saveButton.classList.remove("is-busy");
                    hideSpinner();
                }
            } catch (error) {
                showMsgWindow("התרחשה שגיאה בעת שמירת ההגדרות", "warning", "no-action", true);
                saveButton.classList.remove("is-busy");
                hideSpinner();
                console.error("Exception in saving settings:\n", error);
                sendDiagnostics("Save settings exception:\n" + JSON.stringify(error));
            }
        });
    }
});

// ===================================================
// decrypt
// ===================================================

async function readAndDecryptCitiesAndStreets() {
    const dbName = "MarvelousDB";
    const storeName = "ConfigStore";

    try {
        const db = await openDatabase(dbName, storeName);

        // Read the encrypted data and IV from IndexedDB
        const encryptedData = await getField(db, storeName, "citiesAndStreets");

        if (!encryptedData) {
            // console.error("Missing data in IndexedDB.");
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
            // console.error("Failed to decrypt and parse Cities and Streets.");
            return null;
        }
    } catch (error) {
        console.error("Exception in readAndDecryptCitiesAndStreets:\n", error);
        sendDiagnostics(
            "readAndDecryptCitiesAndStreets: exception when reading and decrypting Cities and Streets:\n" +
                JSON.stringify(error)
        );
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
        console.error("Exception in mrvlDecrypt:\n", error);
        sendDiagnostics("mrvlDecrypt: error:\n" + JSON.stringify(error));
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

        if (currentSignature !== mrvlAdmin.configSignature) {
            // Fetch data via AJAX
            const response = await fetch(mrvlAdmin.ajaxurl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "get_site_cities",
                    nonce: mrvlAdmin.nonce,
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
        console.error("Exception in manageCitiesAndStreets:\n", error);
        sendDiagnostics("manageCitiesAndStreets: error:\n" + JSON.stringify(error));
    }
}

document.addEventListener("DOMContentLoaded", () => {
    manageCitiesAndStreets();
});

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
// send Diagnostics
// ===================================================
async function sendDiagnostics(message) {
    try {
        if (mrvlAdmin.diagnosticsAllowed !== "1") {
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
            nonce: mrvlAdmin.nonce,
        });

        // Send the POST request
        await fetch(mrvlAdmin.ajaxurl, {
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
