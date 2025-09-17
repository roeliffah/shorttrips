"use client";
import HotelCard, { Hotel } from '../components/HotelCard';
import { useState, useEffect } from 'react';
import dynamic from 'next/dynamic';
import type { SearchParams } from './search-form';
const SearchForm = dynamic(() => import('./search-form'), { ssr: false });

const BRIDGE_URL = 'https://freestays.eu/api.php';
const BRIDGE_KEY = 'hlIGzfFEk5Af0dWNZO4p';

export default function Home() {
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Vrij zoeken states
  const [destinationInput, setDestinationInput] = useState('');
  const [suggestions, setSuggestions] = useState<{ id: string; name: string; country_id?: string; region_id?: string; city_id?: string }[]>([]);
  const [selectedDestination, setSelectedDestination] = useState<{ id: string; name: string; country_id?: string; region_id?: string; city_id?: string } | null>(null);
  const [showSuggestions, setShowSuggestions] = useState(false);

  // Tab state
  const [activeTab, setActiveTab] = useState<'vrij' | 'uitgebreid'>('vrij');

  // Uitgebreid zoeken states
  const [countries, setCountries] = useState<any[]>([]);
  const [regions, setRegions] = useState<any[]>([]);
  const [cities, setCities] = useState<any[]>([]);
  const [selectedCountry, setSelectedCountry] = useState<string>('');
  const [selectedRegion, setSelectedRegion] = useState<string>('');
  const [selectedCity, setSelectedCity] = useState<string>('');

  // Ophalen suggesties uit de bridge (live zoeken)
  useEffect(() => {
    const controller = new AbortController();
    if (destinationInput.length < 2) {
      setSuggestions([]);
      return;
    }
    fetch(`${BRIDGE_URL}?action=destinations&key=${BRIDGE_KEY}&query=${encodeURIComponent(destinationInput)}`, {
      signal: controller.signal,
    })
      .then(res => res.json())
      .then(data => {
        if (Array.isArray(data.results)) {
          setSuggestions(data.results);
        } else {
          setSuggestions([]);
        }
      })
      .catch(() => setSuggestions([]));
    return () => controller.abort();
  }, [destinationInput]);

  // Ophalen landen bij openen tab uitgebreid zoeken
  useEffect(() => {
    if (activeTab === 'uitgebreid' && countries.length === 0) {
      fetch(`${BRIDGE_URL}?action=countries&key=${BRIDGE_KEY}`)
        .then(res => res.json())
        .then(data => setCountries(data.results || []));
    }
  }, [activeTab]);

  // Ophalen regio's bij selectie land
  useEffect(() => {
    if (selectedCountry) {
      fetch(`${BRIDGE_URL}?action=regions&key=${BRIDGE_KEY}&country_id=${selectedCountry}`)
        .then(res => res.json())
        .then(data => setRegions(data.results || []));
      setCities([]);
      setSelectedRegion('');
      setSelectedCity('');
    } else {
      setRegions([]);
      setCities([]);
      setSelectedRegion('');
      setSelectedCity('');
    }
  }, [selectedCountry]);

  // Ophalen steden bij selectie regio
  useEffect(() => {
    if (selectedRegion) {
      fetch(`${BRIDGE_URL}?action=cities&key=${BRIDGE_KEY}&region_id=${selectedRegion}`)
        .then(res => res.json())
        .then(data => setCities(data.results || []));
      setSelectedCity('');
    } else {
      setCities([]);
      setSelectedCity('');
    }
  }, [selectedRegion]);

  // Vrij zoeken: call met country, region, optioneel city
  async function handleSearch(params: Omit<SearchParams, 'query'> & { destination_id: string }) {
    setLoading(true);
    setError(null);
    setHotels([]);
    if (!selectedDestination || !selectedDestination.country_id || !selectedDestination.region_id) {
      setError('Selecteer een geldige bestemming (minimaal land en regio).');
      setLoading(false);
      return;
    }
    const url = new URL(BRIDGE_URL);
    url.searchParams.set('action', 'quicksearch');
    url.searchParams.set('key', BRIDGE_KEY);
    url.searchParams.set('country', selectedDestination.country_id);
    url.searchParams.set('resort_id', selectedDestination.region_id);
    if (selectedDestination.city_id) {
      url.searchParams.set('city_id', selectedDestination.city_id);
    }
    url.searchParams.set('destination_id', params.destination_id); // fallback voor bridge
    url.searchParams.set('checkin', params.checkIn);
    url.searchParams.set('checkout', params.checkOut);
    url.searchParams.set('adults', String(params.adults));
    url.searchParams.set('children', String(params.children.age0_2 + params.children.age3_12 + params.children.age12_18));
    url.searchParams.set('rooms', String(params.rooms));
    try {
      const res = await fetch(url.toString());
      const data = await res.json();
      if (data.results) setHotels(data.results);
      else setError('Geen hotels gevonden');
    } catch (e) {
      setError('Fout bij ophalen hotels');
    }
    setLoading(false);
  }

  // Uitgebreid zoeken: call met gekozen land, regio, stad
  async function handleAdvancedSearch(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setHotels([]);
    if (!selectedCountry || !selectedRegion) {
      setError('Selecteer minimaal een land en regio.');
      setLoading(false);
      return;
    }
    const url = new URL(BRIDGE_URL);
    url.searchParams.set('action', 'quicksearch');
    url.searchParams.set('key', BRIDGE_KEY);
    url.searchParams.set('country', selectedCountry);
    url.searchParams.set('resort_id', selectedRegion);
    if (selectedCity) {
      url.searchParams.set('city_id', selectedCity);
      url.searchParams.set('destination_id', selectedCity); // fallback
    }
    // Voorbeelddata, pas aan naar wens of maak inputs
    url.searchParams.set('checkin', '2025-10-06');
    url.searchParams.set('checkout', '2025-10-12');
    url.searchParams.set('adults', '2');
    url.searchParams.set('children', '0');
    url.searchParams.set('rooms', '1');
    try {
      const res = await fetch(url.toString());
      const data = await res.json();
      if (data.results) setHotels(data.results);
      else setError('Geen hotels gevonden');
    } catch (e) {
      setError('Fout bij ophalen hotels');
    }
    setLoading(false);
  }

  return (
    <main className="max-w-3xl mx-auto py-10 px-4">
      <h1 className="text-3xl font-bold mb-4">Welkom bij Shorttrips!</h1>
      <p className="mb-6">Boek je volgende zonvakantie eenvoudig en snel.</p>

      {/* Tabs */}
      <div className="flex mb-6">
        <button
          className={`px-4 py-2 border-b-2 ${activeTab === 'vrij' ? 'border-blue-600 font-bold' : 'border-transparent'}`}
          onClick={() => setActiveTab('vrij')}
        >
          Vrij zoeken
        </button>
        <button
          className={`px-4 py-2 border-b-2 ${activeTab === 'uitgebreid' ? 'border-blue-600 font-bold' : 'border-transparent'}`}
          onClick={() => setActiveTab('uitgebreid')}
        >
          Uitgebreid zoeken
        </button>
      </div>

      {/* Vrij zoeken */}
      {activeTab === 'vrij' && (
        <>
          {/* Autocomplete zoekveld */}
          <div className="mb-4 relative">
            <label className="block font-semibold mb-1">Bestemming</label>
            <input
              type="text"
              className="border rounded px-2 py-1 w-full"
              placeholder="Typ een bestemming, stad of land..."
              value={destinationInput}
              onChange={e => {
                setDestinationInput(e.target.value);
                setShowSuggestions(true);
                setSelectedDestination(null);
              }}
              onFocus={() => setShowSuggestions(true)}
              autoComplete="off"
            />
            {showSuggestions && destinationInput && (
              <ul className="absolute z-10 bg-white border w-full mt-1 max-h-40 overflow-auto">
                {suggestions.length > 0 ? (
                  suggestions.map(dest => (
                    <li
                      key={dest.id}
                      className="px-2 py-1 hover:bg-blue-100 cursor-pointer"
                      onClick={() => {
                        setDestinationInput(dest.name);
                        setSelectedDestination(dest);
                        setShowSuggestions(false);
                      }}
                    >
                      {dest.name}
                      <span className="text-xs text-gray-400 ml-2">
                        {dest.country_id && `Land: ${dest.country_id} `}
                        {dest.region_id && `Regio: ${dest.region_id} `}
                        {dest.city_id && `Stad: ${dest.city_id}`}
                      </span>
                    </li>
                  ))
                ) : (
                  <li className="px-2 py-1 text-gray-400">Geen suggesties</li>
                )}
              </ul>
            )}
          </div>
          {/* Zoekformulier, alleen actief als een bestemming is gekozen */}
          <SearchForm
            onSearch={params => {
              if (selectedDestination && selectedDestination.country_id && selectedDestination.region_id) {
                handleSearch({ ...params, destination_id: selectedDestination.id });
              } else {
                setError('Selecteer een geldige bestemming uit de lijst (minimaal land en regio).');
              }
            }}
          />
        </>
      )}

      {/* Uitgebreid zoeken */}
      {activeTab === 'uitgebreid' && (
        <form onSubmit={handleAdvancedSearch} className="space-y-4 bg-white p-4 rounded shadow">
          <div>
            <label>Land:</label>
            <select className="border p-2 w-full" value={selectedCountry} onChange={e => setSelectedCountry(e.target.value)}>
              <option value="">Kies een land</option>
              {countries.map((c: any) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>
          <div>
            <label>Regio:</label>
            <select className="border p-2 w-full" value={selectedRegion} onChange={e => setSelectedRegion(e.target.value)}>
              <option value="">Kies een regio</option>
              {regions.map((r: any) => (
                <option key={r.id} value={r.id}>{r.name}</option>
              ))}
            </select>
          </div>
          <div>
            <label>Stad:</label>
            <select className="border p-2 w-full" value={selectedCity} onChange={e => setSelectedCity(e.target.value)}>
              <option value="">Kies een stad</option>
              {cities.map((c: any) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>
          {/* Voeg hier extra velden toe zoals check-in, check-out, adults, etc. */}
          <button className="bg-blue-600 text-white px-4 py-2 rounded w-full mt-4" type="submit">Uitgebreid zoeken</button>
        </form>
      )}

      {/* Resultaten */}
      <div className="mt-10">
        {loading && <div>Hotels laden...</div>}
        {error && <div className="text-red-600">{error}</div>}
        {hotels.length > 0 && hotels.map((hotel: any) => (
          <HotelCard
            key={hotel.hotel_id || hotel.id}
            hotel={{
              id: Number(hotel.hotel_id || hotel.id),
              name: hotel.name,
              city: hotel.city || "",
              country: hotel.country || "",
              imageUrl: hotel.images?.[0] || hotel.image_url || hotel.mainImage,
              description: hotel.description || '',
              price: hotel.price
                ? Number((hotel.price * 1.15).toFixed(2))
                : hotel.price_total
                ? Number((hotel.price_total * 1.15).toFixed(2))
                : undefined,
              rating: hotel.star_rating || hotel.rating,
              reviews: hotel.reviews,
              roomType: hotel.roomType,
              checkIn: hotel.checkIn,
              checkOut: hotel.checkOut,
              persons: hotel.persons,
              children: hotel.children,
              rooms: hotel.rooms,
            }}
          />
        ))}
        {hotels.length === 0 && !loading && !error && (
          <div className="text-gray-500">Geen hotels gevonden.</div>
        )}
      </div>
      <a href="/bookings" className="text-blue-600 underline block mt-8">Bekijk boekingen</a>
    </main>
  );
}
