"use client";
import HotelCard, { Hotel } from '../components/HotelCard';
import { useState } from 'react';
import dynamic from 'next/dynamic';
import type { SearchParams } from './search-form';
const SearchForm = dynamic(() => import('./search-form'), { ssr: false });

const BRIDGE_URL = 'https://freestays.eu/api.php';
const BRIDGE_KEY = 'hlIGzfFEk5Af0dWNZO4p';

// Simpele lijst met bestemmingen voor autocomplete
const DESTINATIONS = [
  { id: "203", name: "Barcelona" },
  { id: "254", name: "Alanya" },
  { id: "101", name: "Amsterdam" },
  // Voeg meer bestemmingen toe indien gewenst
];

export default function Home() {
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [destinationInput, setDestinationInput] = useState('');
  const [selectedDestination, setSelectedDestination] = useState<{ id: string; name: string } | null>(null);
  const [showSuggestions, setShowSuggestions] = useState(false);

  async function handleSearch(params: Omit<SearchParams, 'query'> & { destination_id: string }) {
    setLoading(true);
    setError(null);
    setHotels([]);
    const url = new URL(BRIDGE_URL);
    url.searchParams.set('action', 'quicksearch');
    url.searchParams.set('key', BRIDGE_KEY);
    url.searchParams.set('destination_id', params.destination_id);
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

  // Suggesties filteren op basis van input
  const filteredDestinations = DESTINATIONS.filter(dest =>
    dest.name.toLowerCase().includes(destinationInput.toLowerCase())
  );

  return (
    <main className="max-w-3xl mx-auto py-10 px-4">
      <h1 className="text-3xl font-bold mb-4">Welkom bij Shorttrips!</h1>
      <p className="mb-6">Boek je volgende zonvakantie eenvoudig en snel.</p>
      {/* Autocomplete zoekveld */}
      <div className="mb-4 relative">
        <label className="block font-semibold mb-1">Bestemming</label>
        <input
          type="text"
          className="border rounded px-2 py-1 w-full"
          placeholder="Typ een bestemming..."
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
            {filteredDestinations.length > 0 ? (
              filteredDestinations.map(dest => (
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
          if (selectedDestination) {
            handleSearch({ ...params, destination_id: selectedDestination.id });
          } else {
            setError('Selecteer een geldige bestemming uit de lijst.');
          }
        }}
      />
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
              // 15% opslag, 2 decimalen, euroteken in HotelCard
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
