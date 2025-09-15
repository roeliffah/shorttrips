import React from 'react';
import { Search, Menu, User, Heart, ShoppingCart } from 'lucide-react';
import { Button } from '@/components/ui/button';
import SearchBar from './SearchBar';
import { useAppContext } from '@/contexts/AppContext';

const Header: React.FC = () => {
  const { toggleSidebar } = useAppContext();

  const handleSearch = (filters: any) => {
    // Handle search - this will be connected to the search functionality
    console.log('Search filters:', filters);
  };

  return (
    <header className="bg-white shadow-sm border-b">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo and Brand */}
          <div className="flex items-center">
            <Button
              variant="ghost"
              size="sm"
              className="lg:hidden mr-2"
              onClick={toggleSidebar}
            >
              <Menu className="h-5 w-5" />
            </Button>
            <div className="flex items-center">
              <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-sm">S</span>
              </div>
              <span className="ml-2 text-xl font-bold text-gray-900">ShortTrips</span>
            </div>
          </div>

          {/* Search Bar - Hidden on mobile */}
          <div className="hidden md:flex flex-1 max-w-2xl mx-8">
            <SearchBar onSearch={handleSearch} className="w-full" />
          </div>

          {/* Right side actions */}
          <div className="flex items-center space-x-2">
            <Button variant="ghost" size="sm" className="hidden sm:flex">
              <Heart className="h-4 w-4 mr-1" />
              Favorites
            </Button>
            <Button variant="ghost" size="sm" className="hidden sm:flex">
              <ShoppingCart className="h-4 w-4 mr-1" />
              Bookings
            </Button>
            <Button variant="ghost" size="sm">
              <User className="h-4 w-4 mr-1" />
              Sign In
            </Button>
          </div>
        </div>

        {/* Mobile Search Bar */}
        <div className="md:hidden pb-4">
          <SearchBar onSearch={handleSearch} />
        </div>
      </div>
    </header>
  );
};

export default Header;
