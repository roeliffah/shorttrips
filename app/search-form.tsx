"use client";
import { useState } from 'react';

export type SearchParams = {
  query: string;
  checkIn: string;
  checkOut: string;
  adults: number;
  children: {
    age0_2: number;
    age3_12: number;
    age12_18: number;
  };
  rooms: number;
  destination_id?: string;
};

function getDefaultDate(offsetDays: number) {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  return d.toISOString().split('T')[0];
}

// Helper om kinderen op te tellen
function getTotalChildren(children: { age0_2: number; age3_12: number; age12_18: number }) {
  return children.age0_2 + children.age3_12 + children.age12_18;
}

export default function SearchForm({ onSearch }: { onSearch?: (params: SearchParams) => void }) {
  const [query, setQuery] = useState('');
  const [checkIn, setCheckIn] = useState(getDefaultDate(1));
  const [checkOut, setCheckOut] = useState(getDefaultDate(2));
  const [adults, setAdults] = useState(2);
  const [children, setChildren] = useState({ age0_2: 0, age3_12: 0, age12_18: 0 });
  const [childrenTotal, setChildrenTotal] = useState(0);
  const [rooms, setRooms] = useState(1);
  const [destinationInput, setDestinationInput] = useState('');
  const [hotels, setHotels] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  // Houd childrenTotal en children in sync
  // (optioneel: als je wilt dat dropdowns altijd optellen tot childrenTotal)
  // useEffect(() => {
  //   const total = getTotalChildren(children);
  //   if (total !== childrenTotal) setChildrenTotal(total);
  // }, [children]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!destinationInput) {
      alert('Vul een bestemming in.');
      return;
    }

    const key = "hlIGzfFEk5Af0dWNZO4p"; // Zet hier je eigen key
    const city_id = destinationInput; // Nu direct uit het invoerveld
    const totalChildren = childrenTotal;

    // Datumformaten voor de bridge
    function formatDate(date: string) {
      // van yyyy-mm-dd naar dd/mm/yyyy
      const [y, m, d] = date.split("-");
      return `${d}/${m}/${y}`;
    }
    const start = formatDate(checkIn);
    const end = formatDate(checkOut);
    const dateRange = `${checkIn} - ${checkOut}`;

    setLoading(true);

    const url = `https://freestays.eu/api.php?action=quicksearch&key=${key}&city_id=${encodeURIComponent(city_id)}&room=${rooms}&adults=${adults}&children=${totalChildren}&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}&date=${encodeURIComponent(dateRange)}`;

    try {
      const response = await fetch(url);
      const data = await response.json();
      setHotels(data.results || []);
    } catch (err) {
      setHotels([]);
    }
    setLoading(false);

    if (onSearch) onSearch({
      destination_id: city_id,
      query,
      checkIn,
      checkOut,
      adults,
      children,
      rooms
    });
  }

  return (
    <div>
      <form onSubmit={handleSubmit} className="space-y-4 bg-white p-4 rounded shadow max-w-xl mx-auto mt-8">
        <h2 className="text-xl font-bold mb-2">Zoek hotels</h2>
        <label className="block mb-2">
          Bestemming (city_id of naam):
          <input
            type="text"
            className="border p-2 w-full mt-1"
            placeholder="Typ een bestemming of city_id..."
            value={destinationInput}
            onChange={e => setDestinationInput(e.target.value)}
            autoComplete="off"
          />
        </label>
        <input className="border p-2 w-full" placeholder="Naam, stad of land" value={query} onChange={e => setQuery(e.target.value)} />
        <div className="flex gap-2">
          <div className="flex-1">
            <label className="block text-sm">Volwassenen</label>
            <input
              type="number"
              min={1}
              max={10}
              className="border p-2 w-full"
              value={adults}
              onChange={e => setAdults(Number(e.target.value))}
            />
          </div>
          <div className="flex-1">
            <label className="block text-sm">Kinderen</label>
            <input
              type="number"
              min={0}
              max={15}
              className="border p-2 w-full"
              value={childrenTotal}
              onChange={e => {
                const val = Number(e.target.value);
                setChildrenTotal(val);
                if (val === 0) {
                  setChildren({ age0_2: 0, age3_12: 0, age12_18: 0 });
                }
              }}
            />
          </div>
          <div className="flex-1">
            <label className="block text-sm">Kamers</label>
            <input
              type="number"
              min={1}
              max={5}
              className="border p-2 w-full"
              value={rooms}
              onChange={e => setRooms(Number(e.target.value))}
            />
          </div>
        </div>
        {childrenTotal > 0 && (
          <div>
            <label className="block text-sm font-semibold mb-1">Leeftijd kinderen</label>
            <div className="flex gap-2">
              <div className="flex-1">
                <span className="text-xs">0-2 jaar</span>
                <select
                  className="border p-2 w-full"
                  value={children.age0_2}
                  onChange={e => setChildren(c => ({ ...c, age0_2: Number(e.target.value) }))}
                >
                  {[0,1,2,3,4,5].map(n => <option key={n} value={n}>{n}</option>)}
                </select>
              </div>
              <div className="flex-1">
                <span className="text-xs">3-12 jaar</span>
                <select
                  className="border p-2 w-full"
                  value={children.age3_12}
                  onChange={e => setChildren(c => ({ ...c, age3_12: Number(e.target.value) }))}
                >
                  {[0,1,2,3,4,5].map(n => <option key={n} value={n}>{n}</option>)}
                </select>
              </div>
              <div className="flex-1">
                <span className="text-xs">12-18 jaar</span>
                <select
                  className="border p-2 w-full"
                  value={children.age12_18}
                  onChange={e => setChildren(c => ({ ...c, age12_18: Number(e.target.value) }))}
                >
                  {[0,1,2,3,4,5].map(n => <option key={n} value={n}>{n}</option>)}
                </select>
              </div>
            </div>
          </div>
        )}
        <div className="flex gap-2">
          <div className="flex-1">
            <label className="block text-sm">Check-in</label>
            <input type="date" className="border p-2 w-full" value={checkIn} onChange={e => setCheckIn(e.target.value)} />
          </div>
          <div className="flex-1">
            <label className="block text-sm">Check-out</label>
            <input type="date" className="border p-2 w-full" value={checkOut} onChange={e => setCheckOut(e.target.value)} />
          </div>
        </div>
        <button className="bg-blue-600 text-white px-4 py-2 rounded w-full mt-4" type="submit" disabled={loading}>
          {loading ? "Zoeken..." : "Zoeken"}
        </button>
      </form>
      <div className="max-w-xl mx-auto mt-8">
        {hotels.length > 0 && (
          <div className="grid gap-4">
            {hotels.map(hotel => (
              <div key={hotel.hotel_id} className="bg-white rounded shadow p-4">
                <div className="font-bold">{hotel.name}</div>
                <div className="text-xs text-gray-500">Hotel ID: {hotel.hotel_id}</div>
                {hotel.images && hotel.images.length > 0 && (
                  <img src={hotel.images[0]} alt={hotel.name} className="mt-2 rounded w-full max-h-40 object-cover" />
                )}
                {hotel.description && (
                  <div className="mt-2 text-sm">{hotel.description}</div>
                )}
                {hotel.features && (
                  <div className="mt-2 text-xs text-gray-700">
                    <b>Features:</b> {hotel.features.join(", ")}
                  </div>
                )}
                {hotel.note && (
                  <div className="mt-2 text-xs text-red-600">{hotel.note}</div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}