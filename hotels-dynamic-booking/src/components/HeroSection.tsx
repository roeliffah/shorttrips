import React from 'react';
import SearchBar from './SearchBar';

interface HeroSectionProps {
  onSearch: (filters: any) => void;
}

const HeroSection: React.FC<HeroSectionProps> = ({ onSearch }) => {
  return (
    <div 
      className="relative h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat"
      style={{
        backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://d64gsuwffb70l.cloudfront.net/68c40fc0fd2d2032f693e8e5_1757679588539_3eaaac3c.webp')`
      }}
    >
      <div className="absolute inset-0 bg-gradient-to-b from-blue-900/20 to-blue-900/40"></div>
      
      <div className="relative z-10 w-full max-w-6xl mx-auto px-4">
        <div className="text-center mb-8">
          <h1 className="text-5xl md:text-6xl font-bold text-white mb-4">
            Find Your Perfect
            <span className="block text-yellow-400">Hotel Stay</span>
          </h1>
          <p className="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto">
            Discover amazing hotels worldwide with the best prices guaranteed. 
            Book now and save up to 60% on your next adventure.
          </p>
        </div>

        <SearchBar 
          onSearch={onSearch}
          className="max-w-5xl mx-auto"
        />

        <div className="mt-8 text-center">
          <div className="flex flex-wrap justify-center gap-8 text-white/80">
            <div className="flex flex-col items-center">
              <div className="text-2xl font-bold">2M+</div>
              <div className="text-sm">Properties</div>
            </div>
            <div className="flex flex-col items-center">
              <div className="text-2xl font-bold">150+</div>
              <div className="text-sm">Countries</div>
            </div>
            <div className="flex flex-col items-center">
              <div className="text-2xl font-bold">50M+</div>
              <div className="text-sm">Happy Guests</div>
            </div>
            <div className="flex flex-col items-center">
              <div className="text-2xl font-bold">24/7</div>
              <div className="text-sm">Support</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default HeroSection;