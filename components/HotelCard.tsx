import React from 'react';

export type Hotel = {
  id: number;
  name: string;
  city: string;
  country: string;
  imageUrl?: string;
  description?: string;
  price?: number;
  rating?: number;
  reviews?: number;
  roomType?: string;
  checkIn?: string;
  checkOut?: string;
  persons?: number;
  children?: { age0_2: number; age3_12: number; age12_18: number };
  rooms?: number;
};

export default function HotelCard({ hotel }: { hotel: Hotel }) {
  return (
    <div className="flex flex-col md:flex-row border rounded-lg shadow bg-white overflow-hidden mb-6">
      <div className="md:w-1/3 w-full h-48 md:h-auto bg-gray-100 flex items-center justify-center">
        {hotel.imageUrl ? (
          <img src={hotel.imageUrl} alt={hotel.name} className="object-cover w-full h-full" />
        ) : (
          <span className="text-5xl text-gray-300">🏨</span>
        )}
      </div>
      <div className="flex-1 p-4 flex flex-col justify-between">
        <div>
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-bold text-blue-900">{hotel.name}</h2>
            {hotel.rating && (
              <span className="bg-blue-600 text-white px-2 py-1 rounded text-sm font-semibold ml-2">{hotel.rating}★</span>
            )}
          </div>
          <div className="text-gray-600 text-sm mb-2">{hotel.city}, {hotel.country}</div>
          <div className="text-gray-700 mb-2 line-clamp-2">{hotel.description || 'Geen beschrijving beschikbaar.'}</div>
          <div className="flex flex-wrap gap-2 text-xs text-gray-500 mb-2">
            {hotel.roomType && <span>Kamer: {hotel.roomType}</span>}
            {hotel.checkIn && <span>Check-in: {hotel.checkIn}</span>}
            {hotel.checkOut && <span>Check-out: {hotel.checkOut}</span>}
            {hotel.persons && <span>Volwassenen: {hotel.persons}</span>}
            {hotel.children && (
              <span>
                Kinderen: {hotel.children.age0_2} (0-2), {hotel.children.age3_12} (3-12), {hotel.children.age12_18} (12-18)
              </span>
            )}
            {hotel.rooms && <span>Kamers: {hotel.rooms}</span>}
          </div>
        </div>
        <div className="flex items-end justify-between mt-4">
          <div>
            {hotel.reviews !== undefined && (
              <span className="text-xs text-gray-400">{hotel.reviews} beoordelingen</span>
            )}
          </div>
          <div className="text-right">
            {hotel.price !== undefined ? (
              <span className="text-lg font-bold text-green-700">€ {hotel.price}</span>
            ) : (
              <span className="text-gray-400">Prijs onbekend</span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
