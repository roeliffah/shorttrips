import React, { useState } from "react";

function SearchForm() {
  const [city, setCity] = useState("");
  const [checkin, setCheckin] = useState("");
  const [checkout, setCheckout] = useState("");
  const [adults, setAdults] = useState(2);
  const [children, setChildren] = useState(0);
  const [rooms, setRooms] = useState(1);
  const [hotels, setHotels] = useState([]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setHotels([]);
    setLoading(true);

    if (!city.trim() || !checkin || !checkout) {
      setError("Vul alle verplichte velden in.");
      setLoading(false);
      return;
    }

    try {
      const bridgeKey = 'hlIGzfFEk5Af0dWNZO4p';
      if (!city.trim() || !checkin || !checkout || !bridgeKey) {
        setError("Vul alle verplichte velden in.");
        setLoading(false);
        return;
      }
      // Eerst destination_id ophalen uit de bridge/database
      const resId = await fetch(
        `/bridge/api.php?action=destination-id&city=${encodeURIComponent(city)}&key=${bridgeKey}`
      );
      const dataId = await resId.json();
      if (!dataId.destination_id) {
        setError("Geen destination_id gevonden voor deze plaatsnaam.");
        setLoading(false);
        return;
      }

      // Daarna hotels zoeken met de gevonden destination_id
      if (!dataId.destination_id) {
        setError("Geen geldige destination_id.");
        setLoading(false);
        return;
      }
      const params = new URLSearchParams({
        action: "quicksearch",
        checkin,
        checkout,
        adults,
        children,
        rooms,
        destination_id: dataId.destination_id,
        key: bridgeKey
      });
      const url = `/bridge/api.php?${params.toString()}`;
      if (!url.includes('destination_id=') || url.includes('destination_id=&')) {
        setError("Geen geldige zoekopdracht.");
        setLoading(false);
        return;
      }
      const resHotels = await fetch(url);
      const dataHotels = await resHotels.json();
      if (dataHotels.results && dataHotels.results.length > 0) {
        setHotels(dataHotels.results);
      } else {
        setError("Geen hotels gevonden voor deze zoekopdracht.");
      }
    } catch (err) {
      setError("Fout bij ophalen hotels.");
    }
    setLoading(false);
  };

  return (
    <form onSubmit={handleSubmit} style={{ maxWidth: 500, margin: "2em auto" }}>
      <h2>Hotel zoeken</h2>
      <div>
        <label>Plaatsnaam*:<br />
          <input
            type="text"
            value={city}
            onChange={(e) => setCity(e.target.value)}
            placeholder="Bijv. Alanya"
            required
          />
        </label>
      </div>
      <div>
        <label>Check-in*:<br />
          <input
            type="date"
            value={checkin}
            onChange={(e) => setCheckin(e.target.value)}
            required
          />
        </label>
      </div>
      <div>
        <label>Check-out*:<br />
          <input
            type="date"
            value={checkout}
            onChange={(e) => setCheckout(e.target.value)}
            required
          />
        </label>
      </div>
      <div>
        <label>Volwassenen:<br />
          <input
            type="number"
            min="1"
            value={adults}
            onChange={(e) => setAdults(e.target.value)}
          />
        </label>
      </div>
      <div>
        <label>Kinderen:<br />
          <input
            type="number"
            min="0"
            value={children}
            onChange={(e) => setChildren(e.target.value)}
          />
        </label>
      </div>
      <div>
        <label>Kamers:<br />
          <input
            type="number"
            min="1"
            value={rooms}
            onChange={(e) => setRooms(e.target.value)}
          />
        </label>
      </div>
      <button type="submit" disabled={loading}>
        {loading ? "Zoeken..." : "Zoek hotels"}
      </button>
      {error && <div style={{ color: "red", marginTop: 10 }}>{error}</div>}
      {hotels.length > 0 && (
        <div style={{ marginTop: 20 }}>
          <h3>Resultaten:</h3>
          <ul>
            {hotels.map((hotel) => (
              <li key={hotel.hotel_id}>
                <strong>{hotel.name}</strong> ({hotel.city})<br />
                {hotel.star_rating && <>★ {hotel.star_rating}<br /></>}
                {hotel.price_total && <>Prijs: {hotel.price_total} {hotel.currency}<br /></>}
                {hotel.address && <>{hotel.address}<br /></>}
              </li>
            ))}
          </ul>
        </div>
      )}
    </form>
  );
}

export default SearchForm;