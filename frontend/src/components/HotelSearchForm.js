import React, { useState } from 'react';

export default function HotelSearchForm() {
  const [form, setForm] = useState({
    q: '', country: '', city_id: '', resort_id: '',
    start: '', end: '', room: 1, adults: 2, children: 0
  });
  const [results, setResults] = useState(null);

  const handleChange = e => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async e => {
    e.preventDefault();
    const res = await fetch('/wp-json/freestays/v1/search', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form)
    });
    const json = await res.json();
    setResults(json.data);
  };

  return (
    <div>
      <form onSubmit={handleSubmit}>
        <input name="q" placeholder="Bestemming, hotel of plaats" value={form.q} onChange={handleChange} />
        <select name="country" value={form.country} onChange={handleChange}>
          <option value="">Kies land</option>
        </select>
        <select name="city_id" value={form.city_id} onChange={handleChange}>
          <option value="">Kies stad</option>
        </select>
        <select name="resort_id" value={form.resort_id} onChange={handleChange}>
          <option value="">Kies resort</option>
        </select>
        <input type="date" name="start" value={form.start} onChange={handleChange} required />
        <input type="date" name="end" value={form.end} onChange={handleChange} required />
        <input type="number" name="room" min="1" value={form.room} onChange={handleChange} />
        <input type="number" name="adults" min="1" value={form.adults} onChange={handleChange} />
        <input type="number" name="children" min="0" value={form.children} onChange={handleChange} />
        <button type="submit">Zoeken</button>
      </form>
      <div>
        {results && Array.isArray(results) && results.length > 0
          ? results.map((hotel, i) => <div key={i}>{JSON.stringify(hotel)}</div>)
          : results && 'Geen resultaten gevonden.'}
      </div>
    </div>
  );
}