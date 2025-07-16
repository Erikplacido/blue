document.addEventListener("DOMContentLoaded", function () {
    const selectors = [
        "#autocomplete-address-reservation",
        "#autocomplete-address-quotation",
        "#autocomplete-address-quote"
    ];

    selectors.forEach(id => {
        const input = document.querySelector(id);
        if (!input) return;

        const awesomplete = new Awesomplete(input, {
            minChars: 3,
            maxItems: 5,
            autoFirst: true
        });

        let suggestions = [];

        input.addEventListener("input", async () => {
            const query = input.value.trim();
            if (query.length < 3) {
                awesomplete.list = [];
                return;
            }

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=au`);
                const results = await response.json();

                suggestions = results.map(result => ({
                    label: result.display_name,
                    value: result
                }));

                awesomplete.list = suggestions.map(s => s.label);
            } catch (error) {
                console.error("Autocomplete error:", error);
            }
        });

        input.addEventListener("awesomplete-selectcomplete", function (event) {
            const match = suggestions.find(s => s.label === event.text.value);
            if (match) {
                const address = match.value.address || {};
                input.form.querySelector('[name="address"]').value = address.road || "";
                input.form.querySelector('[name="number"]').value = address.house_number || "";
                input.form.querySelector('[name="postcode"]').value = address.postcode || "";
                input.form.querySelector('[name="suburb"]').value = address.suburb || address.village || "";
                input.form.querySelector('[name="city"]').value = address.city || address.town || address.county || "";
                input.form.querySelector('[name="territory"]').value = address.state || "";
            }
        });
    });
});
