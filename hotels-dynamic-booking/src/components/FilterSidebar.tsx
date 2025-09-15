import React, { useState } from 'react';
import { Filter, Star, Wifi, Car, Coffee, Waves, Dumbbell, Sparkles } from 'lucide-react';

interface FilterSidebarProps {
  onFiltersChange: (filters: any) => void;
  isOpen: boolean;
  onClose: () => void;
}

const FilterSidebar: React.FC<FilterSidebarProps> = ({ onFiltersChange, isOpen, onClose }) => {
  const [priceRange, setPriceRange] = useState([0, 500]);
  const [selectedStars, setSelectedStars] = useState<number[]>([]);
  const [selectedAmenities, setSelectedAmenities] = useState<string[]>([]);

  const amenities = [
    { name: 'WiFi', icon: Wifi },
    { name: 'Parking', icon: Car },
    { name: 'Restaurant', icon: Coffee },
    { name: 'Pool', icon: Waves },
    { name: 'Gym', icon: Dumbbell },
    { name: 'Spa', icon: Sparkles },
  ];

  const handleStarToggle = (stars: number) => {
    setSelectedStars(prev => 
      prev.includes(stars) 
        ? prev.filter(s => s !== stars)
        : [...prev, stars]
    );
  };

  const handleAmenityToggle = (amenity: string) => {
    setSelectedAmenities(prev =>
      prev.includes(amenity)
        ? prev.filter(a => a !== amenity)
        : [...prev, amenity]
    );
  };

  const applyFilters = () => {
    onFiltersChange({
      priceRange,
      starRating: selectedStars,
      amenities: selectedAmenities
    });
    onClose();
  };

  const clearFilters = () => {
    setPriceRange([0, 500]);
    setSelectedStars([]);
    setSelectedAmenities([]);
    onFiltersChange({
      priceRange: [0, 500],
      starRating: [],
      amenities: []
    });
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 lg:relative lg:bg-transparent">
      <div className="absolute right-0 top-0 h-full w-80 bg-white shadow-lg lg:relative lg:w-full lg:shadow-none">
        <div className="p-6">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-2">
              <Filter className="h-5 w-5" />
              <h3 className="text-lg font-semibold">Filters</h3>
            </div>
            <button
              onClick={onClose}
              className="lg:hidden text-gray-500 hover:text-gray-700"
            >
              ×
            </button>
          </div>

          <div className="space-y-6">
            {/* Price Range */}
            <div>
              <h4 className="font-medium mb-3">Price per night</h4>
              <div className="space-y-2">
                <input
                  type="range"
                  min="0"
                  max="1000"
                  value={priceRange[1]}
                  onChange={(e) => setPriceRange([priceRange[0], parseInt(e.target.value)])}
                  className="w-full"
                />
                <div className="flex justify-between text-sm text-gray-600">
                  <span>${priceRange[0]}</span>
                  <span>${priceRange[1]}</span>
                </div>
              </div>
            </div>

            {/* Star Rating */}
            <div>
              <h4 className="font-medium mb-3">Star Rating</h4>
              <div className="space-y-2">
                {[5, 4, 3, 2, 1].map(stars => (
                  <label key={stars} className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={selectedStars.includes(stars)}
                      onChange={() => handleStarToggle(stars)}
                      className="rounded border-gray-300"
                    />
                    <div className="flex items-center gap-1">
                      {Array.from({ length: stars }, (_, i) => (
                        <Star key={i} className="h-4 w-4 text-yellow-400 fill-current" />
                      ))}
                      <span className="text-sm text-gray-600">& up</span>
                    </div>
                  </label>
                ))}
              </div>
            </div>

            {/* Amenities */}
            <div>
              <h4 className="font-medium mb-3">Amenities</h4>
              <div className="space-y-2">
                {amenities.map(({ name, icon: Icon }) => (
                  <label key={name} className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={selectedAmenities.includes(name)}
                      onChange={() => handleAmenityToggle(name)}
                      className="rounded border-gray-300"
                    />
                    <Icon className="h-4 w-4 text-gray-500" />
                    <span className="text-sm">{name}</span>
                  </label>
                ))}
              </div>
            </div>
          </div>

          <div className="mt-8 space-y-3">
            <button
              onClick={applyFilters}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors"
            >
              Apply Filters
            </button>
            <button
              onClick={clearFilters}
              className="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-2 px-4 rounded-lg transition-colors"
            >
              Clear All
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FilterSidebar;