import { Loader } from "https://cdn.jsdelivr.net/npm/@googlemaps/js-api-loader";

const loader = new Loader({
  apiKey: window.GOOGLE_PLACES_KEY,   // pega do global
  libraries: ["places"]
});

loader.load()
  .then(() => initGooglePlaces())
  .catch(err => console.error("Erro ao carregar Google Maps:", err));
