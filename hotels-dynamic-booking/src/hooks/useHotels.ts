import { useState, useEffect, useCallback } from 'react';
import { Hotel, SearchFilters } from '@/types/hotel';
import { SunHotelsAPI } from '@/services/api';
import { DatabaseService, StaticHotelData } from '@/services/database';

export interface UseHotelsResult {
  hotels: Hotel[];
  loading: boolean;
  error: string | null;
  searchHotels: (filters: SearchFilters) => Promise<void>;
  refreshHotels: () => Promise<void>;
  getHotelDetails: (hotelId: string) => Promise<Hotel | null>;
}

export const useHotels = (): UseHotelsResult => {
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const mergeApiAndStaticData = async (apiHotels: Hotel[]): Promise<Hotel[]> => {
    try {
      const mergedHotels = await Promise.all(
        apiHotels.map(async (apiHotel) => {
          try {
            const staticData = await DatabaseService.getStaticHotelData(apiHotel.id);
            if (staticData) {
              // Merge API data with static database data
              return {
                ...apiHotel,
                description: staticData.description || apiHotel.description,
                amenities: [...new Set([...apiHotel.amenities, ...staticData.amenities])],
                images: staticData.images.length > 0 ? staticData.images : apiHotel.images,
                // Add additional static data
                policies: staticData.policies,
                contact: staticData.contact
              } as Hotel & { policies?: any; contact?: any };
            }
            return apiHotel;
          } catch (err) {
            console.warn(`Failed to merge static data for hotel ${apiHotel.id}:`, err);
            return apiHotel;
          }
        })
      );
      return mergedHotels;
    } catch (err) {
      console.error('Failed to merge API and static data:', err);
      return apiHotels;
    }
  };

  const searchHotels = useCallback(async (filters: SearchFilters) => {
    setLoading(true);
    setError(null);
    
    try {
      // Fetch data from SunHotels API
      const apiResults = await SunHotelsAPI.searchHotels(filters);
      
      // Merge with static database data
      const mergedResults = await mergeApiAndStaticData(apiResults);
      
      setHotels(mergedResults);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to search hotels';
      setError(errorMessage);
      console.error('Hotel search error:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  const refreshHotels = useCallback(async () => {
    if (hotels.length === 0) return;
    
    setLoading(true);
    setError(null);
    
    try {
      // Re-fetch current hotels
      const mockFilters: SearchFilters = {
        destination: 'Current Location',
        checkIn: new Date().toISOString().split('T')[0],
        checkOut: new Date(Date.now() + 86400000).toISOString().split('T')[0],
        guests: 2,
        rooms: 1,
        priceRange: [0, 1000],
        starRating: [],
        amenities: [],
        sortBy: 'popularity'
      };
      
      await searchHotels(mockFilters);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to refresh hotels';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  }, [hotels.length, searchHotels]);

  const getHotelDetails = useCallback(async (hotelId: string): Promise<Hotel | null> => {
    try {
      // Get detailed hotel information from API
      const apiHotel = await SunHotelsAPI.getHotelDetails(hotelId);
      if (!apiHotel) return null;

      // Merge with static data
      const mergedHotels = await mergeApiAndStaticData([apiHotel]);
      return mergedHotels[0] || null;
    } catch (err) {
      console.error('Failed to get hotel details:', err);
      return null;
    }
  }, []);

  // Load initial hotels on mount
  useEffect(() => {
    const loadInitialHotels = async () => {
      const initialFilters: SearchFilters = {
        destination: 'Popular Destinations',
        checkIn: new Date().toISOString().split('T')[0],
        checkOut: new Date(Date.now() + 86400000).toISOString().split('T')[0],
        guests: 2,
        rooms: 1,
        priceRange: [0, 1000],
        starRating: [],
        amenities: [],
        sortBy: 'popularity'
      };
      
      await searchHotels(initialFilters);
    };

    loadInitialHotels();
  }, [searchHotels]);

  return {
    hotels,
    loading,
    error,
    searchHotels,
    refreshHotels,
    getHotelDetails
  };
};