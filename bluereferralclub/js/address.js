// address.js
// =============================================================================
// 1) Define initGooglePlaces no escopo global ANTES do SDK do Google carregar
// =============================================================================
(function (window, document) {
  // Callback que o Google API vai chamar:
  window.initGooglePlaces = function () {
    const autocompleteService = new google.maps.places.AutocompleteService();
    const placesService = new google.maps.places.PlacesService(document.createElement('div'));
    let sessionToken = null;

    // Cria um novo token de sessão a cada foco
    function newSession() {
      sessionToken = new google.maps.places.AutocompleteSessionToken();
    }

    // Preenche os campos escondidos com os componentes de endereço
    function fillAddressFields(input, components) {
      const form = input.closest('form');
      if (!form) return;

      const get = type => components.find(c => c.types.includes(type))?.long_name || '';
      const setVal = (id, val) => {
        const el = form.querySelector(`#${id}`);
        if (el) el.value = val;
      };

      const streetNumber = get('street_number');
      const route        = get('route');

      setVal('address', streetNumber && route ? `${streetNumber} ${route}` : route || '');
      setVal('number',    streetNumber);
      setVal('postcode',  get('postal_code'));
      setVal('suburb',    get('sublocality') || get('locality'));
      setVal('city',      get('locality') || get('administrative_area_level_2'));
      setVal('territory', get('administrative_area_level_1'));
    }

    // Constrói o autocomplete + awesomplete para um campo dado seu ID
    function setupAutocomplete(id) {
      const input = document.getElementById(id);
      if (!input) return;

      // Inicializa Awesomplete
      const awesomplete = new Awesomplete(input, {
        minChars: 3,
        maxItems: 5,
        autoFirst: true
      });

      let predictions = [];
      input.addEventListener('focus', newSession);

      input.addEventListener('input', () => {
        const q = input.value.trim();
        if (q.length < 3) {
          predictions = [];
          awesomplete.list = [];
          return;
        }
        autocompleteService.getPlacePredictions({
          input: q,
          sessionToken,
          componentRestrictions: { country: 'au' },
          types: ['address']
        }, (suggestions, status) => {
          if (status !== 'OK' || !suggestions) {
            predictions = [];
            awesomplete.list = [];
            return;
          }
          predictions = suggestions;
          awesomplete.list = suggestions.map(s => s.description);
        });
      });

      input.addEventListener('awesomplete-selectcomplete', evt => {
        const sel = predictions.find(p => p.description === evt.text.value);
        if (!sel) return;
        placesService.getDetails({
          placeId: sel.place_id,
          sessionToken,
          fields: ['address_components']
        }, (place, status) => {
          if (status === 'OK' && place) {
            fillAddressFields(input, place.address_components);
          }
        });
      });
    }

    // Expõe global para uso eventual
    window.setupAutocomplete = setupAutocomplete;

    // Inicializa todos os campos que você precisar
    function initAllAutocompletes() {
      setupAutocomplete('autocomplete-address-quote');
      setupAutocomplete('autocomplete-address-reservation');
      setupAutocomplete('autocomplete-address-quotation');
    }
    initAllAutocompletes();
    window.initAllAutocompletes = initAllAutocompletes;
  };
})(window, document);
