"use client";
import { useState, useEffect } from "react";

// Zet hier je echte API-key!
const API_KEY = "hlIGzfFEk5Af0dWNZO4p";
const API_BASE = "https://freestays.eu/api.php"; // <-- aangepast naar api.php

// Helpers voor API-calls
async function fetchCountries() {
  const url = `${API_BASE}?action=countries&key=${API_KEY}`;
  const res = await fetch(url);
  return res.json();
}
async function fetchRegions(countryId: string) {
  const url = `${API_BASE}?action=regions&key=${API_KEY}&country_id=${countryId}`;
  const res = await fetch(url);
  return res.json();
}
async function fetchCities(regionId: string) {
  const url = `${API_BASE}?action=cities&key=${API_KEY}&region_id=${regionId}`;
  const res = await fetch(url);
  return res.json();
}
async function fetchDestinations(query: string) {
  const url = `${API_BASE}?action=destinations&key=${API_KEY}&query=${encodeURIComponent(query)}`;
  const res = await fetch(url);
  return res.json();
}

// Datum helper
function getDatePlus(days: number) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().split("T")[0];
}

export default function ZoekenHotels() {
  const [tab, setTab] = useState<"snel" | "uitgebreid">("snel");
  const [results, setResults] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  async function handleSearch(params: Record<string, string | number>) {
    setLoading(true);
    setResults([]);
    const query = new URLSearchParams({
      action: "quicksearch",
      key: API_KEY,
      ...params,
    }).toString();
    const url = `${API_BASE}?${query}`;
    const res = await fetch(url);
    const data = await res.json();
    setResults(data.results || []);
    setLoading(false);
  }

  return (
    <div className="max-w-xl mx-auto">
      <div className="flex mb-4">
        <button
          className={`flex-1 p-2 ${tab === "snel" ? "bg-blue-600 text-white" : "bg-gray-200"}`}
          onClick={() => setTab("snel")}
        >
          Snel zoeken
        </button>
        <button
          className={`flex-1 p-2 ${tab === "uitgebreid" ? "bg-blue-600 text-white" : "bg-gray-200"}`}
          onClick={() => setTab("uitgebreid")}
        >
          Uitgebreid zoeken
        </button>
      </div>
      {tab === "snel" ? (
        <SnelZoekenForm onSearch={handleSearch} />
      ) : (
        <UitgebreidZoekenForm onSearch={handleSearch} />
      )}

      {/* Resultaten */}
      <div className="mt-8">
        {loading && <div className="text-center text-blue-600">Zoeken...</div>}
        {!loading && results.length > 0 && (
          <div className="grid gap-4">
            {results.map((hotel: any) => (
              <div key={hotel.hotel_id || hotel.id} className="border rounded shadow p-4 flex gap-4 bg-white">
                {hotel.image_url && (
                  <img
                    src={hotel.image_url}
                    alt={hotel.name}
                    className="w-32 h-24 object-cover rounded"
                  />
                )}
                <div className="flex-1">
                  <h3 className="font-bold text-lg">{hotel.name}</h3>
                  <div className="text-gray-600 text-sm">
                    {hotel.city && <span>{hotel.city}, </span>}
                    {hotel.country}
                  </div>
                  {hotel.star_rating && (
                    <div className="text-yellow-500">
                      {"★".repeat(Number(hotel.star_rating))}
                    </div>
                  )}
                  {hotel.price_total && (
                    <div className="mt-2 font-semibold">
                      {hotel.price_total} {hotel.currency}
                    </div>
                  )}
                  {/* Hier kun je later een link naar de detailpagina toevoegen */}
                </div>
              </div>
            ))}
          </div>
        )}
        {!loading && results.length === 0 && (
          <div className="text-center text-gray-500 mt-8">Geen resultaten gevonden.</div>
        )}
      </div>
    </div>
  );
}

// Snel zoeken (autocomplete)
function SnelZoekenForm({ onSearch }: { onSearch: (params: Record<string, string | number>) => void }) {
  const [query, setQuery] = useState("");
  const [suggestions, setSuggestions] = useState<any[]>([]);
  const [selected, setSelected] = useState<any>(null);
  const [form, setForm] = useState({
    checkin: getDatePlus(1),
    checkout: getDatePlus(7),
    adults: 2,
    children: 0,
    rooms: 1,
  });

  useEffect(() => {
    if (query.length > 2) {
      fetchDestinations(query).then(data => setSuggestions(data.results || []));
    } else {
      setSuggestions([]);
    }
  }, [query]);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!selected) return;
    onSearch({
      destination_id: selected.id,
      checkin: form.checkin,
      checkout: form.checkout,
      adults: form.adults,
      children: form.children,
      rooms: form.rooms,
    });
  }

  return (
    <form className="space-y-4" onSubmit={handleSubmit}>
      <div className="relative">
        <label className="block mb-1">Bestemming (vrij zoeken)</label>
        <input
          className="border p-2 w-full"
          value={selected ? selected.name : query}
          onChange={e => {
            setQuery(e.target.value);
            setSelected(null);
          }}
          placeholder="Typ een bestemming..."
          autoComplete="off"
        />
        {suggestions.length > 0 && !selected && (
          <ul className="border bg-white absolute z-10 w-full max-h-48 overflow-auto">
            {suggestions.map(s => (
              <li
                key={s.id}
                className="p-2 hover:bg-blue-100 cursor-pointer"
                onClick={() => {
                  setSelected(s);
                  setQuery(s.name);
                  setSuggestions([]);
                }}
              >
                {s.name}
              </li>
            ))}
          </ul>
        )}
      </div>
      <input
        className="border p-2 w-full"
        type="date"
        name="checkin"
        value={form.checkin}
        onChange={e => setForm(f => ({ ...f, checkin: e.target.value }))}
      />
      <input
        className="border p-2 w-full"
        type="date"
        name="checkout"
        value={form.checkout}
        onChange={e => setForm(f => ({ ...f, checkout: e.target.value }))}
      />
      <input
        className="border p-2 w-full"
        type="number"
        name="adults"
        min={1}
        value={form.adults}
        onChange={e => setForm(f => ({ ...f, adults: Number(e.target.value) }))}
        placeholder="Volwassenen"
      />
      <input
        className="border p-2 w-full"
        type="number"
        name="children"
        min={0}
        value={form.children}
        onChange={e => setForm(f => ({ ...f, children: Number(e.target.value) }))}
        placeholder="Kinderen"
      />
      <input
        className="border p-2 w-full"
        type="number"
        name="rooms"
        min={1}
        value={form.rooms}
        onChange={e => setForm(f => ({ ...f, rooms: Number(e.target.value) }))}
        placeholder="Kamers"
      />
      <button
        className="bg-blue-600 text-white px-4 py-2 rounded w-full mt-4"
        type="submit"
        disabled={!selected}
      >
        Zoeken
      </button>
    </form>
  );
}

// Uitgebreid zoeken (dropdowns)
function UitgebreidZoekenForm({ onSearch }: { onSearch: (params: Record<string, string | number>) => void }) {
  const [countries, setCountries] = useState<any[]>([]);
  const [regions, setRegions] = useState<any[]>([]);
  const [cities, setCities] = useState<any[]>([]);
  const [country, setCountry] = useState("");
  const [region, setRegion] = useState("");
  const [city, setCity] = useState("");
  const [form, setForm] = useState({
    checkin: getDatePlus(1),
    checkout: getDatePlus(7),
    adults: 2,
    children: 0,
    rooms: 1,
  });

  useEffect(() => {
    fetchCountries().then(data => setCountries(data.results || []));
  }, []);

  useEffect(() => {
    if (country) {
      fetchRegions(country).then(data => setRegions(data.results || []));
      setRegion("");
      setCities([]);
      setCity("");
    }
  }, [country]);

  useEffect(() => {
    if (region) {
      fetchCities(region).then(data => setCities(data.results || []));
      setCity("");
    }
  }, [region]);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!country || !region) return;
    // Zoekopdracht: stuur altijd country en resort_id, city_id alleen als gekozen
    const params: Record<string, string | number> = {
      country,
      resort_id: region,
      checkin: form.checkin,
      checkout: form.checkout,
      adults: form.adults,
      children: form.children,
      rooms: form.rooms,
    };
    if (city) params.city_id = city;
    onSearch(params);
  }

  return (
    <form className="space-y-4" onSubmit={handleSubmit}>
      <div>
        <label className="block mb-1">Land</label>
        <select
          className="border p-2 w-full"
          value={country}
          onChange={e => setCountry(e.target.value)}
        >
          <option value="">Kies een land</option>
          {countries.map((c: any) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
      </div>
      {country && (
        <div>
          <label className="block mb-1">Regio</label>
          <select
            className="border p-2 w-full"
            value={region}
            onChange={e => setRegion(e.target.value)}
          >
            <option value="">Kies een regio</option>
            {regions.map((r: any) => (
              <option key={r.resort_id || r.id} value={r.resort_id || r.id}>
                {r.name}
              </option>
            ))}
          </select>
        </div>
      )}
      {region && (
        <div>
          <label className="block mb-1">Stad (optioneel)</label>
          <select
            className="border p-2 w-full"
            value={city}
            onChange={e => setCity(e.target.value)}
          >
            <option value="">Kies een stad (optioneel)</option>
            {cities.map((s: any) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </select>
        </div>
      )}
      <input
        className="border p-2 w-full"
        type="date"
        name="checkin"
        value={form.checkin}
        onChange={e => setForm(f => ({ ...f, checkin: e.target.value }))}
      />
      <input
        className="border p-2 w-full"
        type="date"
        name="checkout"
        value={form.checkout}
        onChange={e => setForm(f => ({ ...f, checkout: e.target.value }))}
      />
      <input
        className="border p-2 w-full"
        type="number"
        name="adults"
        min={1}
        value={form.adults}
        onChange={e => setForm(f => ({ ...f, adults: Number(e.target.value) }))}
        placeholder="Volwassenen"
      />
      <input
        className="border p-2 w-full"
        type="number"
        name="children"
        min={0}
        value={form.children}
        onChange={e => setForm(f => ({ ...f, children: Number(e.target.value) }))}
        placeholder="Kinderen"
      />
      <input
        className="border p-2 w-full"
        type="number"
        name="rooms"
        min={1}
        value={form.rooms}
        onChange={e => setForm(f => ({ ...f, rooms: Number(e.target.value) }))}
        placeholder="Kamers"
      />
      <button
        className="bg-blue-600 text-white px-4 py-2 rounded w-full mt-4"
        type="submit"
        disabled={!country || !region}
      >
        Zoeken
      </button>
    </form>
  );
}