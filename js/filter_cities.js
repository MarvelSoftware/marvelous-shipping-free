document.addEventListener("DOMContentLoaded", function () {
    const searchBar = document.querySelector(".search-bar");
    const rows = document.querySelectorAll(".rows-container .row");
    const noResultsImage = document.querySelector(".no-results");
    const clearButton = document.querySelector(".clean-button-style");

    // Listen for input events on the search bar
    searchBar.addEventListener("input", function () {
        const query = searchBar.value.toLowerCase();
        let hasResults = false;

        rows.forEach((row) => {
            const cityHeb = row.dataset.cityHeb.toLowerCase();
            const cityEn = row.dataset.cityEn.toLowerCase();

            // Check if the query matches the Hebrew or English city name
            if (cityHeb.includes(query) || cityEn.includes(query)) {
                row.style.display = ""; // Show row
                hasResults = true; // At least one result
            } else {
                row.style.display = "none"; // Hide row
            }
        });

        // Toggle the no-results image
        if (hasResults) {
            noResultsImage.style.display = "none"; // Hide image
        } else {
            noResultsImage.style.display = "block"; // Show image
        }
    });

    // Listen for button click to clear the search bar
    clearButton.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent default button behavior

        searchBar.value = ""; // Clear the input field

        // Reset all rows to visible
        rows.forEach((row) => {
            row.style.display = ""; // Show all rows
        });

        // Hide the no-results image
        if (noResultsImage) {
            noResultsImage.style.display = "none";
        }
    });
});
