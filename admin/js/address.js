// 📌 Executa somente quando o SDK do Google terminar de carregar
window.initGooglePlaces = function () {
  let autocompleteService = new google.maps.places.AutocompleteService();
  let placesService = new google.maps.places.PlacesService(document.createElement('div'));
  let sessionToken;

  function newSession() {
    sessionToken = new google.maps.places.AutocompleteSessionToken();
  }

  function setupAutocomplete(id) {
    const input = document.getElementById(id);
    if (!input) return;

    const awesomplete = new Awesomplete(input, {
      minChars: 3,
      maxItems: 5,
      autoFirst: true
    });

    let predictions = [];

    input.addEventListener('focus', newSession);

    input.addEventListener("input", () => {
      const query = input.value.trim();
      if (query.length < 3) {
        predictions = [];
        awesomplete.list = [];
        return;
      }

      autocompleteService.getPlacePredictions({
        input: query,
        sessionToken: sessionToken,
        componentRestrictions: { country: "au" },
        types: ["address"]
      }, (suggestions, status) => {
        if (status !== "OK" || !suggestions) {
          predictions = [];
          awesomplete.list = [];
          return;
        }
        predictions = suggestions;
        awesomplete.list = suggestions.map(s => s.description);
      });
    });

    input.addEventListener("awesomplete-selectcomplete", evt => {
      const sel = predictions.find(p => p.description === evt.text.value);
      if (!sel) return;

      placesService.getDetails({
        placeId: sel.place_id,
        sessionToken: sessionToken,
        fields: ["address_components"]
      }, (place, status) => {
        if (status !== "OK" || !place) return;
        fillAddressFields(input, place.address_components);
      });
    });
  }

function fillAddressFields(input, components) {
  const form = input.closest("form");
  if (!form) return;

  const get = type => components.find(c => c.types.includes(type))?.long_name || "";

  const setVal = (id, val) => {
    const el = form.querySelector(`#${id}`);
    if (el) el.value = val;
  };

  // Combina rua e número para o campo de endereço principal
  const streetNumber = get("street_number");
  const route = get("route");

  setVal("address", streetNumber && route ? `${streetNumber} ${route}` : route || "");
  setVal("number", streetNumber);
  setVal("postcode", get("postal_code"));
  setVal("suburb", get("sublocality") || get("locality"));
  setVal("city", get("locality") || get("administrative_area_level_2"));
  setVal("territory", get("administrative_area_level_1"));
}

  // Exporta globalmente
  window.setupAutocomplete = setupAutocomplete;

  function initAllAutocompletes() {
    setupAutocomplete("autocomplete-address-quote");
    setupAutocomplete("autocomplete-address-reservation");
    setupAutocomplete("autocomplete-address-quotation");
  }

  initAllAutocompletes();
  window.initAllAutocompletes = initAllAutocompletes;
}
