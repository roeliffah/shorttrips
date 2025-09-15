
import React from 'react';
import { Star, MapPin, Wifi, Car, Coffee, Waves, BedDouble, Info } from 'lucide-react';
import { Hotel } from '@/types/hotel';

interface HotelCardProps {
  hotel: any;
  onSelect: (hotel: any) => void;
}

const HotelCard: React.FC<HotelCardProps> = ({ hotel, onSelect }) => {
  // Fallbacks voor ontbrekende data
  const name = hotel.name || hotel.title || 'Hotel';
  const image = hotel.image_url || hotel.image || 'https://via.placeholder.com/400x300?text=Hotel';
  const city = hotel.city || '';
  const address = hotel.address || '';
  const stars = hotel.stars || hotel.starRating || 0;
  const price = hotel.price_total || hotel.price || hotel.priceWithCheque || null;
  const currency = hotel.currency || 'EUR';
  const available = hotel.availability !== undefined ? hotel.availability : null;
  const roomName = hotel.room_name || '';
  const note = hotel.note || '';
  const reviewText = hotel.reviewText || '';
  const reviewCount = hotel.reviewCount || 0;
  const cancellation = hotel.cancellationPolicy || '';

  const renderStars = (rating: number) => (
    <span className="flex items-center gap-0.5">
      {Array.from({ length: 5 }, (_, i) => (
        <Star key={i} className={`h-4 w-4 ${i < rating ? 'text-yellow-400 fill-current' : 'text-gray-300'}`} />
      ))}
    </span>
  );

  const getAmenityIcon = (amenity: string) => {
    const icons: { [key: string]: React.ReactNode } = {
      'WiFi': <Wifi className="h-4 w-4" />, 'wifi': <Wifi className="h-4 w-4" />, 'Parking': <Car className="h-4 w-4" />, 'parking': <Car className="h-4 w-4" />, 'Restaurant': <Coffee className="h-4 w-4" />, 'Pool': <Waves className="h-4 w-4" />
    };
    return icons[amenity] || null;
  };

  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 cursor-pointer group border border-blue-100"
         onClick={() => onSelect(hotel)}>
      <div className="relative">
        <img
          src={image}
          alt={name}
          className="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
        />
        {available === false && (
          <div className="absolute top-3 left-3 bg-gray-400 text-white px-2 py-1 rounded text-xs font-semibold">
            Niet beschikbaar
          </div>
        )}
        {available === true && price && (
          <div className="absolute top-3 left-3 bg-blue-600 text-white px-2 py-1 rounded text-xs font-semibold">
            {roomName ? roomName : 'Beschikbaar'}
          </div>
        )}
      </div>

      <div className="p-4">
        <div className="flex justify-between items-start mb-2">
          <h3 className="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
            {name}
          </h3>
          <div className="flex items-center gap-1">
            {renderStars(Number(stars))}
          </div>
        </div>

        <div className="flex items-center text-gray-600 text-sm mb-2">
          <MapPin className="h-4 w-4 mr-1" />
          {address ? `${address}, ` : ''}{city}
        </div>

        <div className="flex items-center mb-3">
          <div className="bg-blue-600 text-white px-2 py-1 rounded text-sm font-semibold mr-2">
            {reviewText || 'Score'}
          </div>
          <span className="text-sm text-gray-600">
            {reviewCount ? `(${reviewCount} reviews)` : ''}
          </span>
        </div>

        <div className="flex flex-wrap gap-2 mb-3">
          {hotel.amenities && hotel.amenities.slice(0, 5).map((amenity: string, index: number) => (
            <div key={index} className="flex items-center gap-1 text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
              {getAmenityIcon(amenity)}
              {amenity}
            </div>
          ))}
        </div>

        <div className="flex justify-between items-end">
          <div>
            {cancellation && <div className="text-xs text-green-600 mb-1">{cancellation}</div>}
            {note && <div className="text-xs text-gray-500 mb-1 flex items-center"><Info className="h-3 w-3 mr-1" />{note}</div>}
          </div>
          <div className="text-right">
            {price ? (
              <div className="text-2xl font-bold text-blue-600">
                {currency === 'EUR' ? '€' : currency}{price}
              </div>
            ) : (
              <div className="text-sm text-gray-400">Prijs onbekend</div>
            )}
            {roomName && <div className="text-xs text-blue-700">{roomName}</div>}
            <div className="text-xs text-gray-500">per nacht</div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default HotelCard;