import { SearchFilters } from '@/types/hotel';

// Utility: altijd volledige searchFilters met defaults voor backend
export function withBackendDefaults(filters: Partial<SearchFilters>): SearchFilters {
  return {
    destination: filters.destination || '',
    checkIn: filters.checkIn || '',
    checkOut: filters.checkOut || '',
    guests: typeof filters.guests === 'number' ? filters.guests : 2,
    rooms: typeof filters.rooms === 'number' ? filters.rooms : 1,
    priceRange: filters.priceRange || [0, 1000],
    starRating: filters.starRating || [],
    amenities: filters.amenities || [],
    sortBy: filters.sortBy || 'popularity',
    country: filters.country && filters.country !== '' ? filters.country : '25', // default Turkije
    city_id: filters.city_id && filters.city_id !== '' ? filters.city_id : '203', // default Alanya
    children: typeof filters.children === 'number' ? filters.children : 0,
  };
}
