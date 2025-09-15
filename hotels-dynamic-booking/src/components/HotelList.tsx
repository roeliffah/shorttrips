
import { useEffect, useState } from "react";
import { SearchFilters } from '@/types/hotel';
import { withBackendDefaults } from '@/utils/searchDefaults';
import HotelCard from './HotelCard';

type Props = {
  searchFilters: SearchFilters;
};



export function HotelList({ searchFilters }: Props) {
  const [hotels, setHotels] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const key = "hlIGzfFEk5Af0dWNZO4p";
    const filters = withBackendDefaults(searchFilters);
    const params = new URLSearchParams({
      action: 'quicksearch',
      key,
      destination_id: filters.city_id || '',
      checkin: filters.checkIn,
      checkout: filters.checkOut,
      rooms: String(filters.rooms),
      adults: String(filters.guests),
      children: String(filters.children || 0),
    });
    const url = `/api.php?${params.toString()}`;
  // ...existing code...
  setLoading(true);
  fetch(url)
      .then((res) => {
        if (!res.ok) throw new Error("Fout bij laden hotels");
        return res.json();
      })
      .then((data) => {
        let sunHotels = data.results || [];
        // Fallback: als geen id, gebruik hotel_id als key
        sunHotels = sunHotels.map((h: any, idx: number) => ({
          ...h,
          id: h.id || h.hotel_id || idx,
        }));
        setHotels(sunHotels);
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message);
        setLoading(false);
      });
  }, [searchFilters]);

  if (loading) return <div>Hotels laden...</div>;
  if (error) return <div>Fout: {error}</div>;

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      {hotels.map((hotel) => (
        <HotelCard key={hotel.id} hotel={hotel} onSelect={() => {}} />
      ))}
    </div>
  );
}
