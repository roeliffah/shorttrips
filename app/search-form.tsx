"use client";
import { useState, useEffect } from 'react';

export type SearchParams = {
  query: string;
  checkIn: string;
  checkOut: string;
  adults: number;
  children: { age0_2: number; age3_12: number; age12_18: number };
  rooms: number;
};

export default function SearchForm({ onSearch }: { onSearch: (params: SearchParams) => void }) {
  const [query, setQuery] = useState('');
  const [checkIn, setCheckIn] = useState('');
  const [checkOut, setCheckOut] = useState('');
  const [adults, setAdults] = useState(2);
  const [children, setChildren] = useState({ age0_2: 0, age3_12: 0, age12_18: 0 });
  const [rooms, setRooms] = useState(1);
  const [destinationInput, setDestinationInput] = useState('');
  const [suggestions, setSuggestions] = useState<{ id: string; name: string }[]>([]);
  const [selectedDestination, setSelectedDestination] = useState<{ id: string; name: string } | null>(null);
  const [showSuggestions, setShowSuggestions] = useState(false);

  // Live zoeken op de bridge
  useEffect(() => {
    const controller = new AbortController();
    if (destinationInput.length < 2) {
      setSuggestions([]);
      return;
    }
    fetch(`https://freestays.eu/api.php?action=destinations&query=${encodeURIComponent(destinationInput)}`, {
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

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!selectedDestination) {
      alert('Selecteer een geldige bestemming uit de lijst.');
      return;
    }
    onSearch({
      destination_id: selectedDestination.id,
      query,
      checkIn,
      checkOut,
      adults,
      children,
      rooms
    });
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4 bg-white p-4 rounded shadow max-w-xl mx-auto mt-8">
      <h2 className="text-xl font-bold mb-2">Zoek hotels</h2>
      <label className="block mb-2 relative">
        Bestemming:
        <input
          type="text"
          className="border p-2 w-full mt-1"
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
                </li>
              ))
            ) : (
              <li className="px-2 py-1 text-gray-400">Geen suggesties</li>
            )}
          </ul>
        )}
      </label>
      <input className="border p-2 w-full" placeholder="Naam, stad of land" value={query} onChange={e => setQuery(e.target.value)} />
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
      <div className="flex gap-2">
        <div className="flex-1">
          <label className="block text-sm">Volwassenen</label>
          <input type="number" min={1} max={10} className="border p-2 w-full" value={adults} onChange={e => setAdults(Number(e.target.value))} />
        </div>
        <div className="flex-1">
          <label className="block text-sm">Kamers</label>
          <input type="number" min={1} max={5} className="border p-2 w-full" value={rooms} onChange={e => setRooms(Number(e.target.value))} />
        </div>
      </div>
      <div>
        <label className="block text-sm font-semibold mb-1">Kinderen</label>
        <div className="flex gap-2">
          <div className="flex-1">
            <span className="text-xs">0-2 jaar</span>
            <input type="number" min={0} max={5} className="border p-2 w-full" value={children.age0_2} onChange={e => setChildren(c => ({ ...c, age0_2: Number(e.target.value) }))} />
          </div>
          <div className="flex-1">
            <span className="text-xs">3-12 jaar</span>
            <input type="number" min={0} max={5} className="border p-2 w-full" value={children.age3_12} onChange={e => setChildren(c => ({ ...c, age3_12: Number(e.target.value) }))} />
          </div>
          <div className="flex-1">
            <span className="text-xs">12-18 jaar</span>
            <input type="number" min={0} max={5} className="border p-2 w-full" value={children.age12_18} onChange={e => setChildren(c => ({ ...c, age12_18: Number(e.target.value) }))} />
          </div>
        </div>
      </div>
      <button className="bg-blue-600 text-white px-4 py-2 rounded w-full mt-4" type="submit">Zoeken</button>
    </form>
  );
}