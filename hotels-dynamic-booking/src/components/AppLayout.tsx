import { HotelList } from "./HotelList";
import React, { useState, useEffect } from 'react';
import { useAppContext } from '@/contexts/AppContext';
import { useIsMobile } from '@/hooks/use-mobile';
import { Hotel, SearchFilters } from '@/types/hotel';
import { SunHotelsAPI } from '@/services/api';
import Header from './Header';
import HeroSection from './HeroSection';
import HotelGrid from './HotelGrid';
import FilterSidebar from './FilterSidebar';
import Footer from './Footer';
import { Filter, SortAsc } from 'lucide-react';

const AppLayout: React.FC = () => {
  const { sidebarOpen, toggleSidebar } = useAppContext();
  const isMobile = useIsMobile();
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [filteredHotels, setFilteredHotels] = useState<Hotel[]>([]);
  const [loading, setLoading] = useState(false);
  const [showFilters, setShowFilters] = useState(false);
  const [searchFilters, setSearchFilters] = useState<SearchFilters>({
    destination: '',
    checkIn: '',
    checkOut: '',
    guests: 2,
    rooms: 1,
    priceRange: [0, 1000],
    starRating: [],
    amenities: [],
    sortBy: 'popularity'
  });
  const [showResults, setShowResults] = useState(false);

  useEffect(() => {
    // Load initial hotels on component mount
    loadInitialHotels();
  }, []);

  useEffect(() => {
    // Apply filters whenever hotels or filters change
    applyFilters();
  }, [hotels, searchFilters]);

  const loadInitialHotels = async () => {
    setLoading(true);
    try {
      const mockFilters: SearchFilters = {
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
      const results = await SunHotelsAPI.searchHotels(mockFilters);
      setHotels(results);
    } catch (error) {
      console.error('Failed to load hotels:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async (filters: SearchFilters) => {
    setLoading(true);
    setShowResults(true);
    setSearchFilters(filters);
    
    try {
      const results = await SunHotelsAPI.searchHotels(filters);
      setHotels(results);
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const applyFilters = () => {
    let filtered = [...hotels];

    // Price filter
    filtered = filtered.filter(hotel => 
      hotel.price >= searchFilters.priceRange[0] && 
      hotel.price <= searchFilters.priceRange[1]
    );

    // Star rating filter
    if (searchFilters.starRating.length > 0) {
      filtered = filtered.filter(hotel => 
        searchFilters.starRating.includes(hotel.starRating)
      );
    }

    // Amenities filter
    if (searchFilters.amenities.length > 0) {
      filtered = filtered.filter(hotel =>
        searchFilters.amenities.every(amenity =>
          hotel.amenities.some(hotelAmenity => 
            hotelAmenity.toLowerCase().includes(amenity.toLowerCase())
          )
        )
      );
    }

    // Sort results
    switch (searchFilters.sortBy) {
      case 'price':
        filtered.sort((a, b) => a.price - b.price);
        break;
      case 'rating':
        filtered.sort((a, b) => b.rating - a.rating);
        break;
      case 'distance':
        filtered.sort((a, b) => a.distanceFromCenter - b.distanceFromCenter);
        break;
      default:
        filtered.sort((a, b) => b.reviewCount - a.reviewCount);
    }

    setFilteredHotels(filtered);
  };

  const handleFiltersChange = (newFilters: Partial<SearchFilters>) => {
    setSearchFilters(prev => ({ ...prev, ...newFilters }));
  };

  const handleHotelSelect = (hotel: Hotel) => {
    console.log('Selected hotel:', hotel);
    // Here you would typically navigate to hotel details page
    alert(`Selected: ${hotel.name}`);
  };

  const handleSortChange = (sortBy: SearchFilters['sortBy']) => {
    setSearchFilters(prev => ({ ...prev, sortBy }));
  };

  return (
    <div className="min-h-screen bg-gray-50">
  <Header />
      
      {!showResults ? (
        <HeroSection onSearch={handleSearch} />
      ) : (
        <div className="pt-6">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {/* Results Header */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">
                  {searchFilters.destination || 'Search Results'}
                </h2>
                <p className="text-gray-600">
                  {filteredHotels.length} properties found
                  {searchFilters.checkIn && searchFilters.checkOut && (
                    <span> • {searchFilters.checkIn} - {searchFilters.checkOut}</span>
                  )}
                </p>
              </div>
              
              <div className="flex items-center gap-4 mt-4 sm:mt-0">
                <select
                  value={searchFilters.sortBy}
                  onChange={(e) => handleSortChange(e.target.value as SearchFilters['sortBy'])}
                  className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="popularity">Sort by Popularity</option>
                  <option value="price">Sort by Price</option>
                  <option value="rating">Sort by Rating</option>
                  <option value="distance">Sort by Distance</option>
                </select>
                
                <button
                  onClick={() => setShowFilters(true)}
                  className="lg:hidden flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                  <Filter className="h-4 w-4" />
                  Filters
                </button>
              </div>
            </div>

            <div className="flex gap-8">
              {/* Desktop Filters */}
              <div className="hidden lg:block w-80 flex-shrink-0">
                <div className="bg-white rounded-lg shadow-sm border border-gray-200">
                  <FilterSidebar
                    onFiltersChange={handleFiltersChange}
                    isOpen={true}
                    onClose={() => {}}
                  />
                </div>
              </div>

              {/* Mobile Filters */}
              <FilterSidebar
                onFiltersChange={handleFiltersChange}
                isOpen={showFilters}
                onClose={() => setShowFilters(false)}
              />

              {/* Hotel Results */}
              <div className="flex-1">
                <HotelGrid
                  hotels={filteredHotels}
                  loading={loading}
                  onHotelSelect={handleHotelSelect}
                />
                  {/* Extra hotel lijst uit database */}
                  <div className="mt-8">
                    <HotelList searchFilters={searchFilters} />
                  </div>
              </div>
            </div>
          </div>
        </div>
      )}

      <Footer />
    </div>
  );
};

export default AppLayout;