import React, { useEffect, useState } from "react";

function App() {
  const [countries, setCountries] = useState([]);
  const [cities, setCities] = useState([]);
  const [resorts, setResorts] = useState([]);
  const [form, setForm] = useState({
    country: "",
    city: "",
    resort: "",
    start: "",
    end: "",
    adults: 2,
    children: 0,
    room: 1,
  });
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetch("/wp-json/freestays/v1/countries")
      .then((res) => res.json())
      .then((json) => setCountries(json.data || []));
  }, []);

  useEffect(() => {
    if (form.country) {
      fetch(`/wp-json/freestays/v1/cities?country_id=${form.country}`)
        .then((res) => res.json())
        .then((json) => setCities(json.data || []));
    } else {
      setCities([]);
    }
    setForm((f) => ({ ...f, city: "", resort: "" }));
    setResorts([]);
  }, [form.country]);

  useEffect(() => {
    if (form.city) {
      fetch(`/wp-json/freestays/v1/resorts?city_id=${form.city}`)
        .then((res) => res.json())
        .then((json) => setResorts(json.data || []));
    } else {
      setResorts([]);
    }
    setForm((f) => ({ ...f, resort: "" }));
  }, [form.city]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    const destination_id =
      form.resort || form.city || form.country;
    const payload = {
      destination_id,
      start: form.start,
      end: form.end,
      adults: form.adults,
      children: form.children,
      room: form.room,
    };
    const res = await fetch("/wp-json/freestays/v1/search", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    setResults(json.data || []);
    setLoading(false);
  };

  return (
    <div style={{ maxWidth: 500, margin: "0 auto" }}>
      <h2>Hotel zoeken</h2>
      <form onSubmit={handleSubmit}>
        <label>
          Land:
          <select name="country" value={form.country} onChange={handleChange}>
            <option value="">Kies land</option>
            {countries.map((c) => (
              <option value={c.destinationID} key={c.destinationID}>
                {c.name}
              </option>
            ))}
          </select>
        </label>
        <br />
        <label>
          Stad:
          <select name="city" value={form.city} onChange={handleChange}>
            <option value="">Kies stad</option>
            {cities.map((c) => (
              <option value={c.destinationID} key={c.destinationID}>
                {c.name}
              </option>
            ))}
          </select>
        </label>
        <br />
        <label>
          Resort:
          <select name="resort" value={form.resort} onChange={handleChange}>
            <option value="">Kies resort</option>
            {resorts.map((r) => (
              <option value={r.destinationID} key={r.destinationID}>
                {r.name}
              </option>
            ))}
          </select>
        </label>
        <br />
        <label>
          Check-in:
          <input
            type="date"
            name="start"
            value={form.start}
            onChange={handleChange}
          />
        </label>
        <br />
        <label>
          Check-out:
          <input
            type="date"
            name="end"
            value={form.end}
            onChange={handleChange}
          />
        </label>
        <br />
        <label>
          Volwassenen:
          <input
            type="number"
            name="adults"
            min="1"
            value={form.adults}
            onChange={handleChange}
          />
        </label>
        <br />
        <label>
          Kinderen:
          <input
            type="number"
            name="children"
            min="0"
            value={form.children}
            onChange={handleChange}
          />
        </label>
        <br />
        <label>
          Kamers:
          <input
            type="number"
            name="room"
            min="1"
            value={form.room}
            onChange={handleChange}
          />
        </label>
        <br />
        <button type="submit" disabled={loading}>
          {loading ? "Zoeken..." : "Zoeken"}
        </button>
      </form>
      <div style={{ marginTop: 24 }}>
        {results.length > 0 ? (
          results.map((hotel, i) => (
            <div
              key={i}
              style={{
                border: "1px solid #ccc",
                padding: 12,
                marginBottom: 8,
              }}
            >
              <strong>{hotel.name}</strong>
              <br />
              {hotel.city && <span>{hotel.city}<br /></span>}
              {hotel.country && <span>{hotel.country}<br /></span>}
            </div>
          ))
        ) : (
          <div>Geen hotels gevonden.</div>
        )}
      </div>
    </div>
  );
}

export default App;
