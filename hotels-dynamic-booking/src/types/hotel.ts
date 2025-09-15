export interface Hotel {
  id: string;
  name: string;
  location: string;
  city: string;
  country: string;
  rating: number;
  reviewCount: number;
  price: number;
  originalPrice?: number;
  currency: string;
  image: string;
  images: string[];
  amenities: string[];
  description: string;
  coordinates: {
    lat: number;
    lng: number;
  };
  availability: boolean;
  roomsAvailable: number;
  starRating: number;
  distanceFromCenter: number;
  cancellationPolicy: string;
  breakfast: boolean;
  wifi: boolean;
  parking: boolean;
  pool: boolean;
  gym: boolean;
  spa: boolean;
  // Extra velden uit Sunhotels XML
  reviewText?: string;
  priceWithCheque?: number;
}

export interface SearchFilters {
  destination: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  rooms: number;
  priceRange: [number, number];
  starRating: number[];
  amenities: string[];
  sortBy: 'price' | 'rating' | 'distance' | 'popularity';
  country?: string; // ISO of Sunhotels landcode
  city_id?: string; // Sunhotels city_id
  children?: number;
}

export interface BookingData {
  hotelId: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  rooms: number;
  totalPrice: number;
  guestInfo: {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
  };
  paymentConfirmed: boolean; // Required for actual booking
  paymentMethod?: {
    type: 'card' | 'paypal' | 'bank_transfer';
    last4?: string;
    brand?: string;
  };
}

export interface PaymentData {
  amount: number;
  currency: string;
  paymentMethodId: string;
  customerEmail: string;
  description: string;
}

export interface BookingConfirmation {
  bookingId: string;
  hotelName: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  rooms: number;
  totalPrice: number;
  confirmationNumber: string;
  status: 'confirmed' | 'pending' | 'cancelled';
  createdAt: string;
}

export interface SunHotelsCredentials {
  username: string;
  password: string;
  apiUrl: string;
}
