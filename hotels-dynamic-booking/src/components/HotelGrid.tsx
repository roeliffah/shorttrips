import React from 'react';
import { Hotel } from '@/types/hotel';
import HotelCard from './HotelCard';

interface HotelGridProps {
  hotels: Hotel[];
  loading: boolean;
  onHotelSelect: (hotel: Hotel) => void;
}

const HotelGrid: React.FC<HotelGridProps> = ({ hotels, loading, onHotelSelect }) => {
  if (loading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {Array.from({ length: 9 }, (_, i) => (
          <div key={i} className="bg-white rounded-lg shadow-md overflow-hidden animate-pulse">
            <div className="w-full h-48 bg-gray-300"></div>
            <div className="p-4 space-y-3">
              <div className="h-4 bg-gray-300 rounded w-3/4"></div>
              <div className="h-3 bg-gray-300 rounded w-1/2"></div>
              <div className="h-3 bg-gray-300 rounded w-2/3"></div>
              <div className="flex justify-between">
                <div className="h-3 bg-gray-300 rounded w-1/4"></div>
                <div className="h-6 bg-gray-300 rounded w-1/3"></div>
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (hotels.length === 0) {
    return (
      <div className="text-center py-12">
        <div className="text-gray-500 text-lg mb-4">No hotels found</div>
        <p className="text-gray-400">Try adjusting your search criteria or filters</p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {hotels.map((hotel) => (
        <HotelCard
          key={hotel.id}
          hotel={hotel}
          onSelect={onHotelSelect}
        />
      ))}
    </div>
  );
};

export default HotelGrid;