"use client";
import HotelCard, { Hotel } from '../components/HotelCard';
import { useState } from 'react';
import dynamic from 'next/dynamic';
import type { SearchParams } from './search-form';
const SearchForm = dynamic(() => import('./search-form'), { ssr: false });

const BRIDGE_URL = 'https://freestays.eu/api.php';
const BRIDGE_KEY = 'hlIGzfFEk5Af0dWNZO4p';

export default function Home() {
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSearch(params: SearchParams) {
    setLoading(true);
    setError(null);
    setHotels([]);
    const url = new URL(BRIDGE_URL);
    url.searchParams.set('action', 'quicksearch');
    url.searchParams.set('key', BRIDGE_KEY);
    url.searchParams.set('destination', params.query);
    url.searchParams.set('checkin', params.checkIn);
    url.searchParams.set('checkout', params.checkOut);
    url.searchParams.set('adults', String(params.adults));
    url.searchParams.set('children', String(params.children.age0_2 + params.children.age3_12 + params.children.age12_18));
    url.searchParams.set('rooms', String(params.rooms));
    // TODO: voeg kind-leeftijden toe als childrenAges
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
      <SearchForm onSearch={handleSearch} />
      <div className="mt-10">
        {loading && <div>Hotels laden...</div>}
        {error && <div className="text-red-600">{error}</div>}
        {hotels.map((hotel: any) => (
          <HotelCard key={hotel.hotel_id || hotel.id} hotel={{
            id: hotel.hotel_id || hotel.id,
            name: hotel.name,
            city: hotel.city,
            country: hotel.country,
            imageUrl: hotel.image_url || hotel.mainImage,
            description: hotel.description || '',
            price: hotel.price_total || hotel.price,
            rating: hotel.star_rating || hotel.rating,
            reviews: hotel.reviews,
            roomType: hotel.roomType,
            checkIn: hotel.checkIn,
            checkOut: hotel.checkOut,
            persons: hotel.persons,
            children: hotel.children,
            rooms: hotel.rooms,
          }} />
        ))}
      </div>
      <a href="/bookings" className="text-blue-600 underline block mt-8">Bekijk boekingen</a>
    </main>
  );
}
