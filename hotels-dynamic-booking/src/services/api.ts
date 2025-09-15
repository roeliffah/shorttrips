import { Hotel, SearchFilters } from '@/types/hotel';
import { withBackendDefaults } from '@/utils/searchDefaults';
import { useQuery } from '@tanstack/react-query';
import { fetchHotels } from '../services/api';

// SunHotels API via eigen PHP-proxy
const SUNHOTELS_PROXY_URL = 'https://freestays.eu/api.php?action=sunhotels_search&key=hlIGzfFEk5Af0dWNZO4p';
const PAYMENT_ENABLED = import.meta.env.VITE_PAYMENT_ENABLED === 'true';

// Freestays branding configuration
const APP_NAME = import.meta.env.VITE_APP_NAME || 'ShortTrips';
const APP_VERSION = import.meta.env.VITE_APP_VERSION || '1.0.0';

export interface SunHotelsAuth {
  username: string;
  password: string;
}

export interface SunHotelsResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  error?: string;
}

export interface BookingRequest {
  hotelId: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  rooms: number;
  guestInfo: {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
  };
  paymentConfirmed: boolean;
}

export class SunHotelsAPI {

  // Vervang proxy-POST door GET naar api.php?action=quicksearch
  public static async searchHotels(filters: SearchFilters): Promise<Hotel[]> {
    const key = "hlIGzfFEk5Af0dWNZO4p";
    const fullFilters = withBackendDefaults(filters);
    // city_id mapping: als destination bekend is en city_id ontbreekt, zet city_id op Alanya (203) als voorbeeld
    let city_id = fullFilters.city_id;
    if (!city_id && fullFilters.destination && fullFilters.destination.toLowerCase() === 'alanya') {
      city_id = '203';
    }
    const params = new URLSearchParams({
      action: 'quicksearch',
      key,
      destination_id: city_id || '',
      destination: fullFilters.destination || '',
      checkin: fullFilters.checkIn,
      checkout: fullFilters.checkOut,
      rooms: String(fullFilters.rooms),
      adults: String(fullFilters.guests),
      children: String(fullFilters.children || 0),
      sortBy: fullFilters.sortBy || '',
    });
    const url = `/api.php?${params.toString()}`;
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error('Hotel search failed: No data received from SunHotels API proxy');
    }
    const data = await response.json();
    return data.results || [];
  }

  private static parseXMLResponse(xmlText: string): any {
    try {
      const parser = new DOMParser();
      const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
      
      // Check for SOAP faults
      const fault = xmlDoc.querySelector('soap\\:Fault, Fault');
      if (fault) {
        const faultString = fault.querySelector('faultstring, soap\\:faultstring')?.textContent || 'SOAP Fault';
        throw new Error(`SunHotels API Error: ${faultString}`);
      }
      
      // Extract data from XML response
      // This is a simplified implementation - adjust based on actual SunHotels XML structure
      const result = xmlDoc.querySelector('*');
      if (result) {
        return this.xmlToObject(result);
      }
      
      return { hotels: [], success: true };
    } catch (error) {
      console.error('XML parsing error:', error);
      throw error;
    }
  }

  private static xmlToObject(node: Element): any {
    const result: any = {};
    
    // Handle text content
    if (node.childNodes.length === 1 && node.childNodes[0].nodeType === Node.TEXT_NODE) {
      return node.textContent?.trim() || '';
    }
    
    // Handle child elements
    for (let i = 0; i < node.childNodes.length; i++) {
      const child = node.childNodes[i];
      if (child.nodeType === Node.ELEMENT_NODE) {
        const element = child as Element;
        const key = element.tagName;
        const value = this.xmlToObject(element);
        
        if (result[key]) {
          if (Array.isArray(result[key])) {
            result[key].push(value);
          } else {
            result[key] = [result[key], value];
          }
        } else {
          result[key] = value;
        }
      }
    }
    
    return result;
  }

  // Oude searchHotels verwijderd, alleen GET-implementatie blijft over

  static async getHotelDetails(hotelId: string): Promise<Hotel | null> {
    try {
      console.log(`SunHotels API: Getting details for hotel ${hotelId}...`);
      
      const detailsBody = `<hotelId>${hotelId}</hotelId>`;
  // Niet meer ondersteund: backend werkt alleen met quicksearch
  throw new Error('GetHotelDetails is niet meer ondersteund in deze build.');
      
      // unreachable
    } catch (error) {
      console.error('SunHotels API hotel details failed:', error);
      throw new Error(`Hotel details failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  static async getHotelAvailability(hotelId: string, checkIn: string, checkOut: string, guests: number, rooms: number): Promise<any> {
    try {
      console.log(`SunHotels API: Checking availability for hotel ${hotelId}...`);
      
      const availabilityBody = `
        <hotelId>${hotelId}</hotelId>
        <checkin>${checkIn}</checkin>
        <checkout>${checkOut}</checkout>
        <guests>${guests}</guests>
        <rooms>${rooms}</rooms>
        <currency>USD</currency>
          <currency>EUR</currency>
      `;

  // Niet meer ondersteund: backend werkt alleen met quicksearch
  throw new Error('GetHotelAvailability is niet meer ondersteund in deze build.');
      
      // unreachable
    } catch (error) {
      console.error('SunHotels API availability check failed:', error);
      throw new Error(`Availability check failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  static async createBooking(bookingData: BookingRequest): Promise<any> {
    try {
      console.log('SunHotels API: Creating booking...', bookingData);
      
      // Check if payment is confirmed
      if (!bookingData.paymentConfirmed) {
        throw new Error('Payment must be confirmed before creating a booking. Please complete payment first.');
      }

      // Check if payment system is enabled
      if (!PAYMENT_ENABLED) {
        console.warn('Payment system is not enabled. Booking will not be sent to SunHotels.');
        return {
          success: false,
          message: 'Payment system is not enabled. Please enable payment processing to create real bookings.',
          bookingId: null
        };
      }

      const bookingBody = `
        <hotelId>${bookingData.hotelId}</hotelId>
        <checkin>${bookingData.checkIn}</checkin>
        <checkout>${bookingData.checkOut}</checkout>
        <guests>${bookingData.guests}</guests>
        <rooms>${bookingData.rooms}</rooms>
        <firstName>${bookingData.guestInfo.firstName}</firstName>
        <lastName>${bookingData.guestInfo.lastName}</lastName>
        <email>${bookingData.guestInfo.email}</email>
        <phone>${bookingData.guestInfo.phone}</phone>
        <currency>USD</currency>
          <currency>EUR</currency>
      `;

  // Niet meer ondersteund: backend werkt alleen met quicksearch
  throw new Error('CreateBooking is niet meer ondersteund in deze build.');
      
      // unreachable
    } catch (error) {
      console.error('SunHotels API booking creation failed:', error);
      throw new Error(`Booking creation failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  static async cancelBooking(bookingId: string): Promise<boolean> {
    try {
      console.log(`SunHotels API: Cancelling booking ${bookingId}...`);
      
      const cancelBody = `<bookingId>${bookingId}</bookingId>`;
  // Niet meer ondersteund: backend werkt alleen met quicksearch
  throw new Error('CancelBooking is niet meer ondersteund in deze build.');
      
      // unreachable
    } catch (error) {
      console.error('SunHotels API booking cancellation failed:', error);
      throw new Error(`Booking cancellation failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  private static transformHotelData(apiData: any[]): Hotel[] {
    console.log('SunHotels API: Transforming hotel data...', apiData);
    
    return apiData.map((hotel: any, index: number) => {
      // Handle both array and single object responses
      const hotelData = Array.isArray(hotel) ? hotel[0] : hotel;
      
      return {
        id: hotelData.id || hotelData.hotel_id || hotelData.hotelId || `hotel-${index}`,
        name: hotelData.name || hotelData.hotel_name || hotelData.hotelName || 'Unknown Hotel',
        location: hotelData.address || hotelData.location || hotelData.address1 || 'Unknown Location',
        city: hotelData.city || hotelData.destination || 'Unknown City',
        country: hotelData.country || 'Unknown Country',
        rating: parseFloat(hotelData.guest_rating || hotelData.rating || hotelData.guestRating || '4.0'),
        reviewCount: parseInt(hotelData.review_count || hotelData.reviews || hotelData.reviewCount || '0'),
        price: parseFloat(hotelData.price_per_night || hotelData.rate || hotelData.price || hotelData.ratePerNight || '100'),
        originalPrice: hotelData.original_price || hotelData.originalPrice,
    currency: hotelData.currency || 'EUR',
        image: hotelData.main_image || hotelData.image_url || hotelData.image || hotelData.photo || 'https://via.placeholder.com/400x300?text=Hotel',
        images: hotelData.images || hotelData.photos || [hotelData.main_image || hotelData.image_url || hotelData.image],
        amenities: hotelData.amenities || hotelData.facilities || [],
        description: hotelData.description || hotelData.summary || '',
        coordinates: {
          lat: parseFloat(hotelData.latitude || hotelData.lat || hotelData.latitude || '0'),
          lng: parseFloat(hotelData.longitude || hotelData.lng || hotelData.longitude || '0')
        },
        availability: hotelData.available !== false && hotelData.available !== 'false',
        roomsAvailable: parseInt(hotelData.rooms_available || hotelData.roomsAvailable || hotelData.availableRooms || '1'),
        starRating: parseInt(hotelData.star_rating || hotelData.stars || hotelData.starRating || '3'),
        distanceFromCenter: parseFloat(hotelData.distance_km || hotelData.distance || hotelData.distanceFromCenter || '1.0'),
        cancellationPolicy: hotelData.cancellation || hotelData.cancellationPolicy || 'Standard cancellation policy',
        breakfast: hotelData.breakfast_included || hotelData.breakfast || false,
        wifi: hotelData.free_wifi || hotelData.wifi || hotelData.freeWifi || true,
        parking: hotelData.parking || hotelData.parkingAvailable || false,
        pool: hotelData.swimming_pool || hotelData.pool || hotelData.swimmingPool || false,
        gym: hotelData.fitness_center || hotelData.gym || hotelData.fitnessCenter || false,
        spa: hotelData.spa || hotelData.spaAvailable || false
      };
    });
  }

  // Health check method
  static async healthCheck(): Promise<boolean> {
    try {
      console.log('SunHotels API: Performing health check...');
  // Niet meer ondersteund: backend werkt alleen met quicksearch
  throw new Error('HealthCheck is niet meer ondersteund in deze build.');
  // unreachable
    } catch (error) {
      console.error('SunHotels API: Health check failed:', error);
      return false;
    }
  }

  // Method to check if payment is required for booking
  static isPaymentRequired(): boolean {
    return PAYMENT_ENABLED;
  }

  // Method to validate booking data before sending to SunHotels
  static validateBookingData(bookingData: Partial<BookingRequest>): { valid: boolean; errors: string[] } {
    const errors: string[] = [];

    if (!bookingData.hotelId) errors.push('Hotel ID is required');
    if (!bookingData.checkIn) errors.push('Check-in date is required');
    if (!bookingData.checkOut) errors.push('Check-out date is required');
    if (!bookingData.guests || bookingData.guests < 1) errors.push('Number of guests is required');
    if (!bookingData.rooms || bookingData.rooms < 1) errors.push('Number of rooms is required');
    if (!bookingData.guestInfo?.firstName) errors.push('Guest first name is required');
    if (!bookingData.guestInfo?.lastName) errors.push('Guest last name is required');
    if (!bookingData.guestInfo?.email) errors.push('Guest email is required');
    if (!bookingData.guestInfo?.phone) errors.push('Guest phone is required');
    if (!bookingData.paymentConfirmed) errors.push('Payment confirmation is required');

    return {
      valid: errors.length === 0,
      errors
    };
  }
}

// src/services/api.ts

export async function fetchHotels(searchParams: Record<string, any>) {
  const response = await fetch('/api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(searchParams).toString(),
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error(data.error || 'Unknown error');
  }

  return data.data.hotels || [];
}

