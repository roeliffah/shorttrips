import React from 'react';
import AppLayout from '@/components/AppLayout';
import { AppProvider } from '@/contexts/AppContext';
import { ApiStatus } from '@/components/ApiStatus';

const Index: React.FC = () => {
  return (
    <AppProvider>
      <div className="min-h-screen bg-gray-50">
        {/* API Status Banner - Only show in development */}
        {import.meta.env.DEV && (
          <div className="bg-white border-b">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
              <ApiStatus />
            </div>
          </div>
        )}
        <AppLayout />
      </div>
    </AppProvider>
  );
};

export default Index;
