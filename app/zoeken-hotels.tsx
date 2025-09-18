"use client";
import { useState, useEffect, useRef } from "react";

type Hotel = {
  id: string;
  name: string;
  description?: string;
  images?: string[];
};

type Option = { id: string; name: string };

export default function ZoekenHotels() {
  // Tab state
  const [tab, setTab] = useState<'snel' | 'uitgebreid'>('snel');

  // Form state
  const [form, setForm] = useState<any>({
    destinationInput: "",
    countryId: "",
    regionId: "",
    cityId: "",
    checkIn: "",
    checkOut: "",
    adults: 2,
    children: 0,
    childrenAges: [],
    rooms: 1,
    mealId: "",
    transfer: "",
    roomtypes: [],
    review: "",
    distance: "",
  });

  // Snel zoeken suggesties
  const [suggestions, setSuggestions] = useState<any[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [selectedSuggestion, setSelectedSuggestion] = useState<any>(null);
  const suggestBoxRef = useRef<HTMLDivElement>(null);

  // Uitgebreid zoeken dropdowns
  const [countries, setCountries] = useState<Option[]>([]);
  const [regions, setRegions] = useState<Option[]>([]);
  const [cities, setCities] = useState<Option[]>([]);

  // Overige opties
  const mealOptions: Option[] = [
    { id: "1", name: "Logies" },
    { id: "2", name: "Ontbijt" },
    { id: "3", name: "Halfpension" },
    { id: "4", name: "Volpension" },
    { id: "5", name: "All Inclusive" },
  ];
  const transferOptions: Option[] = [
    { id: "0", name: "Geen transfer" },
    { id: "1", name: "Inclusief transfer" },
  ];
  const reviewOptions: Option[] = [
    { id: "1", name: "1+" },
    { id: "2", name: "2+" },
    { id: "3", name: "3+" },
    { id: "4", name: "4+" },
    { id: "5", name: "5" },
  ];
  const roomtypeOptions: Option[] = [
    { id: "1", name: "Standaard" },
    { id: "2", name: "Suite" },
    { id: "3", name: "Familiekamer" },
    { id: "4", name: "Appartement" },
  ];

  // Resultaten
  const [results, setResults] = useState<Hotel[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Suggesties ophalen bij snel zoeken
  async function handleDestinationInput(e: React.ChangeEvent<HTMLInputElement>) {
    const value = e.target.value;
    setForm((prev: any) => ({ ...prev, destinationInput: value }));
    setSelectedSuggestion(null);
    if (value.length > 2) {
      const res = await fetch(`/api/suggest?q=${encodeURIComponent(value)}`);
      const data = await res.json();
      setSuggestions(data.results || []);
      setShowSuggestions(true);
    } else {
      setSuggestions([]);
      setShowSuggestions(false);
    }
  }

  function handleSuggestionClick(suggestion: any) {
    setSelectedSuggestion(suggestion);
    setForm((prev: any) => ({
      ...prev,
      destinationInput: suggestion.Name,
      countryId: suggestion.DestinationId || "",
      regionId: suggestion.ResortId || "",
      cityId: suggestion.CityId || "",
    }));
    setSuggestions([]);
    setShowSuggestions(false);
  }

  // Klik buiten suggestiebox sluit deze
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (
        suggestBoxRef.current &&
        !suggestBoxRef.current.contains(event.target as Node)
      ) {
        setShowSuggestions(false);
      }
    }
    if (showSuggestions) {
      document.addEventListener("mousedown", handleClickOutside);
    }
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [showSuggestions]);

  // Kind-leeftijd velden aanpassen aan aantal kinderen
  useEffect(() => {
    if (form.children > 0) {
      setForm((prev: any) => ({
        ...prev,
        childrenAges: Array.from({ length: form.children }, (_, i) => prev.childrenAges[i] || "")
      }));
    } else {
      setForm((prev: any) => ({ ...prev, childrenAges: [] }));
    }
  }, [form.children]);

  function handleChildAgeChange(idx: number, value: string) {
    setForm((prev: any) => {
      const ages = [...prev.childrenAges];
      ages[idx] = value;
      return { ...prev, childrenAges: ages };
    });
  }

  // Uitgebreid zoeken: landen ophalen bij openen tab uitgebreid
  useEffect(() => {
    if (tab === "uitgebreid") {
      fetch("/api/countries")
        .then(res => res.json())
        .then(data => setCountries(data.results || []));
    }
  }, [tab]);

  // Uitgebreid zoeken: regio's ophalen als countryId wijzigt
  useEffect(() => {
    if (tab === "uitgebreid" && form.countryId) {
      fetch(`/api/regions?country_id=${form.countryId}`)
        .then(res => res.json())
        .then(data => setRegions(data.results || []));
    } else {
      setRegions([]);
      setForm((prev: any) => ({ ...prev, regionId: "", cityId: "" }));
      setCities([]);
    }
  }, [form.countryId, tab]);

  // Uitgebreid zoeken: steden ophalen als regionId wijzigt
  useEffect(() => {
    if (tab === "uitgebreid" && form.regionId) {
      fetch(`/api/cities?region_id=${form.regionId}`)
        .then(res => res.json())
        .then(data => setCities(data.results || []));
    } else {
      setCities([]);
      setForm((prev: any) => ({ ...prev, cityId: "" }));
    }
  }, [form.regionId, tab]);

  // Reset velden bij tab wissel
  useEffect(() => {
    if (tab === 'snel') {
      setForm((prev: any) => ({
        ...prev,
        countryId: "",
        regionId: "",
        cityId: "",
        mealId: "",
        transfer: "",
        roomtypes: [],
        review: "",
        distance: "",
      }));
      setCountries([]);
      setRegions([]);
      setCities([]);
    } else {
      setForm((prev: any) => ({
        ...prev,
        destinationInput: "",
      }));
      setSelectedSuggestion(null);
      setSuggestions([]);
      setShowSuggestions(false);
    }
    setResults([]);
    setError(null);
  }, [tab]);

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) {
    const { name, value, type } = e.target;
    if (type === "number") {
      setForm((prev: any) => ({ ...prev, [name]: Number(value) }));
    } else if (name === "roomtypes") {
      // Multi-select
      const options = (e.target as HTMLSelectElement).selectedOptions;
      const values = Array.from(options).map(opt => opt.value);
      setForm((prev: any) => ({ ...prev, roomtypes: values }));
    } else {
      setForm((prev: any) => ({ ...prev, [name]: value }));
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setResults([]);
    setError(null);

    // Validatie snel zoeken: suggestie geselecteerd?
    if (tab === "snel" && !selectedSuggestion) {
      setError("Selecteer een geldige bestemming uit de lijst.");
      setLoading(false);
      return;
    }

    // Bouw de juiste zoekdata
    let searchData: any = {
      searchType: tab,
      ...form,
    };
    if (tab === "snel" && selectedSuggestion) {
      searchData.countryId = selectedSuggestion.DestinationId;
      // Voeg hier eventueel regio/city id's toe als je die uit de suggestie haalt
    }

    const res = await fetch("/api/search", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(searchData),
    });
    const data = await res.json();
    if (data.results && data.results.length > 0) {
      setResults(data.results);
    } else {
      setError("Geen hotels gevonden.");
    }
    setLoading(false);
  }

  return (
    <div className="max-w-xl mx-auto">
      <h1 className="text-2xl font-bold mb-4">Zoek hotels</h1>
      <div className="flex gap-2 mb-4">
        <button
          type="button"
          className={`px-4 py-2 rounded-t ${tab === 'snel' ? 'bg-blue-600 text-white' : 'bg-gray-200'}`}
          onClick={() => setTab('snel')}
        >
          Snel zoeken
        </button>
        <button
          type="button"
          className={`px-4 py-2 rounded-t ${tab === 'uitgebreid' ? 'bg-blue-600 text-white' : 'bg-gray-200'}`}
          onClick={() => setTab('uitgebreid')}
        >
          Uitgebreid zoeken
        </button>
      </div>
      <form onSubmit={handleSubmit} className="space-y-4 bg-white p-4 rounded shadow relative">
        {tab === "snel" && (
          <div className="relative" ref={suggestBoxRef}>
            <input
              className="border p-2 w-full"
              name="destinationInput"
              placeholder="Bestemming (vrij invoer)"
              value={form.destinationInput}
              onChange={handleDestinationInput}
              autoComplete="off"
              onFocus={() => setShowSuggestions(suggestions.length > 0)}
            />
            {showSuggestions && suggestions.length > 0 && (
              <ul className="border bg-white absolute z-10 w-full max-h-48 overflow-auto">
                {suggestions.map(s => (
                  <li
                    key={s.DestinationId}
                    className="p-2 hover:bg-blue-100 cursor-pointer"
                    onClick={() => handleSuggestionClick(s)}
                  >
                    {s.Name}
                  </li>
                ))}
              </ul>
            )}
          </div>
        )}
        {tab === "uitgebreid" && (
          <>
            <select name="countryId" className="border p-2 w-full" value={form.countryId} onChange={handleChange}>
              <option value="">Kies land</option>
              {countries.map(c => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            <select name="regionId" className="border p-2 w-full" value={form.regionId} onChange={handleChange} disabled={!form.countryId}>
              <option value="">Kies regio</option>
              {regions.map(r => (
                <option key={r.id} value={r.id}>{r.name}</option>
              ))}
            </select>
            <select name="cityId" className="border p-2 w-full" value={form.cityId} onChange={handleChange} disabled={!form.regionId}>
              <option value="">Kies stad</option>
              {cities.map(c => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            <select name="mealId" className="border p-2 w-full" value={form.mealId} onChange={handleChange}>
              <option value="">Maaltijdtype</option>
              {mealOptions.map(m => (
                <option key={m.id} value={m.id}>{m.name}</option>
              ))}
            </select>
            <select name="transfer" className="border p-2 w-full" value={form.transfer} onChange={handleChange}>
              <option value="">Transfer</option>
              {transferOptions.map(t => (
                <option key={t.id} value={t.id}>{t.name}</option>
              ))}
            </select>
            <select name="roomtypes" className="border p-2 w-full" value={form.roomtypes} onChange={handleChange} multiple>
              {roomtypeOptions.map(rt => (
                <option key={rt.id} value={rt.id}>{rt.name}</option>
              ))}
            </select>
            <select name="review" className="border p-2 w-full" value={form.review} onChange={handleChange}>
              <option value="">Review</option>
              {reviewOptions.map(r => (
                <option key={r.id} value={r.id}>{r.name}</option>
              ))}
            </select>
            <input
              className="border p-2 w-full"
              name="distance"
              placeholder="Afstand (km)"
              value={form.distance}
              onChange={handleChange}
              type="number"
              min={0}
            />
          </>
        )}
        <div className="flex gap-2">
          <input
            className="border p-2 w-full"
            name="checkIn"
            type="date"
            value={form.checkIn}
            onChange={handleChange}
          />
          <input
            className="border p-2 w-full"
            name="checkOut"
            type="date"
            value={form.checkOut}
            onChange={handleChange}
          />
        </div>
        <div className="flex gap-2">
          <input
            className="border p-2 w-full"
            name="adults"
            type="number"
            min={1}
            max={10}
            value={form.adults}
            onChange={handleChange}
            placeholder="Volwassenen"
          />
          <input
            className="border p-2 w-full"
            name="children"
            type="number"
            min={0}
            max={10}
            value={form.children}
            onChange={handleChange}
            placeholder="Kinderen"
          />
          <input
            className="border p-2 w-full"
            name="rooms"
            type="number"
            min={1}
            max={5}
            value={form.rooms}
            onChange={handleChange}
            placeholder="Kamers"
          />
        </div>
        {/* Kind-leeftijd dropdowns */}
        {form.children > 0 && (
          <div>
            <label className="block text-sm font-semibold mb-1">Leeftijd kinderen</label>
            <div className="flex gap-2">
              {form.childrenAges.map((age: string, idx: number) => (
                <select
                  key={idx}
                  className="border p-2"
                  value={age}
                  onChange={e => handleChildAgeChange(idx, e.target.value)}
                >
                  <option value="">Leeftijd</option>
                  {Array.from({ length: 18 }, (_, i) => (
                    <option key={i + 1} value={i + 1}>{i + 1} jaar</option>
                  ))}
                </select>
              ))}
            </div>
          </div>
        )}
        {error && <div className="text-red-600">{error}</div>}
        <button className="bg-blue-600 text-white px-4 py-2 rounded w-full mt-4" type="submit" disabled={loading}>
          {loading ? "Zoeken..." : "Zoeken"}
        </button>
      </form>
      <div className="mt-8">
        {results.length > 0 && (
          <div className="grid gap-4">
            {results.map(hotel => (
              <div key={hotel.id} className="bg-white rounded shadow p-4">
                <div className="font-bold">{hotel.name}</div>
                {hotel.images && hotel.images.length > 0 && (
                  <img src={hotel.images[0]} alt={hotel.name} className="mt-2 rounded w-full max-h-40 object-cover" />
                )}
                {hotel.description && (
                  <div className="mt-2 text-sm">{hotel.description}</div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}