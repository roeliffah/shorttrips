
import React, { useState } from 'react';
import './HotelSearchForm.css';

export default function HotelSearchForm() {
  const [form, setForm] = useState({
    destination: '',
    checkin: '',
    checkout: '',
    guests: 2,
    rooms: 1,
  });
  const [results, setResults] = useState(null);

  const handleChange = e => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async e => {
    e.preventDefault();
    setResults(null);
    // Check op verplichte velden
    if (!form.destination || !form.checkin || !form.checkout) {
      setResults([]);
      return;
    }
    const payload = {
      destination_id: form.destination,
      start: form.checkin,
      end: form.checkout,
      adults: form.guests,
      room: form.rooms
    };
    try {
      if (!payload.destination_id) {
        setResults([]);
        return;
      }
  const res = await fetch('https://2.59.115.196/wp-json/freestays/v1/search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        setResults(json.data);
      } else {
        setResults([]);
      }
    } catch (err) {
      setResults([]);
    }
  };

  return (
    <div className="hotel-search-bg">
      <div className="hotel-search-header">
        <h1>Find Your Perfect <span className="hotel-search-highlight">Hotel Stay</span></h1>
        <p>Discover amazing hotels worldwide with the best prices guaranteed. Book now and save up to 60% on your next adventure.</p>
      </div>
      <form className="hotel-search-form" onSubmit={handleSubmit}>
        <div className="hotel-search-fields">
          <div className="hotel-search-field">
            <label>Destination</label>
            <input name="destination" placeholder="Where are you going?" value={form.destination} onChange={handleChange} />
          </div>
          <div className="hotel-search-field">
            <label>Check-in</label>
            <input type="date" name="checkin" value={form.checkin} onChange={handleChange} />
          </div>
          <div className="hotel-search-field">
            <label>Check-out</label>
            <input type="date" name="checkout" value={form.checkout} onChange={handleChange} />
          </div>
          <div className="hotel-search-field">
            <label>Guests & Rooms</label>
            <select name="guests" value={form.guests} onChange={handleChange}>
              {[...Array(10)].map((_, i) => <option key={i+1} value={i+1}>{i+1} Guest{(i+1)>1?'s':''}</option>)}
            </select>
            <select name="rooms" value={form.rooms} onChange={handleChange}>
              {[...Array(5)].map((_, i) => <option key={i+1} value={i+1}>{i+1} Room{(i+1)>1?'s':''}</option>)}
            </select>
          </div>
          <div className="hotel-search-field hotel-search-btn-field">
            <button type="submit" className="hotel-search-btn">Search</button>
          </div>
        </div>
      </form>
      <div style={{marginTop:32}}>
        {results === null ? null : (
          results.length > 0 ? (
            <div className="hotel-results">
              {results.map((hotel, i) => (
                <div key={i} className="hotel-card">
                  <div className="hotel-card-img">
                    {hotel.image ? <img src={hotel.image} alt={hotel.name} /> : <div className="hotel-card-img-placeholder" />}
                  </div>
                  <div className="hotel-card-info">
                    <h3>{hotel.name}</h3>
                    <div>{hotel.city}, {hotel.country}</div>
                    <div className="hotel-card-price">{hotel.price ? `€${hotel.price}` : 'Prijs op aanvraag'}</div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="hotel-no-results">Geen hotels gevonden voor deze zoekopdracht.</div>
          )
        )}
      </div>
    </div>
  );
}