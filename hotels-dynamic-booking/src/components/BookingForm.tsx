import React, { useState } from 'react';
import { Hotel, BookingData } from '@/types/hotel';
import { SunHotelsAPI } from '@/services/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CreditCard, Shield, AlertCircle, CheckCircle } from 'lucide-react';

interface BookingFormProps {
  hotel: Hotel;
  checkIn: string;
  checkOut: string;
  guests: number;
  rooms: number;
  onBookingComplete?: (confirmation: any) => void;
  onCancel?: () => void;
}

export const BookingForm: React.FC<BookingFormProps> = ({
  hotel,
  checkIn,
  checkOut,
  guests,
  rooms,
  onBookingComplete,
  onCancel
}) => {
  const [guestInfo, setGuestInfo] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phone: ''
  });
  const [paymentConfirmed, setPaymentConfirmed] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [errors, setErrors] = useState<string[]>([]);
  const [bookingResult, setBookingResult] = useState<any>(null);

  const totalPrice = hotel.price * rooms * Math.ceil((new Date(checkOut).getTime() - new Date(checkIn).getTime()) / (1000 * 60 * 60 * 24));

  const handleInputChange = (field: keyof typeof guestInfo, value: string) => {
    setGuestInfo(prev => ({ ...prev, [field]: value }));
    setErrors([]);
  };

  const handleBooking = async () => {
    setIsProcessing(true);
    setErrors([]);

    try {
      const bookingData: BookingData = {
        hotelId: hotel.id,
        checkIn,
        checkOut,
        guests,
        rooms,
        totalPrice,
        guestInfo,
        paymentConfirmed
      };

      // Validate booking data
      const validation = SunHotelsAPI.validateBookingData(bookingData);
      if (!validation.valid) {
        setErrors(validation.errors);
        setIsProcessing(false);
        return;
      }

      // Check if payment is required
      if (!paymentConfirmed) {
        setErrors(['Payment confirmation is required to complete the booking']);
        setIsProcessing(false);
        return;
      }

      // Create booking
      const result = await SunHotelsAPI.createBooking(bookingData);
      
      if (result.success) {
        setBookingResult(result);
        onBookingComplete?.(result);
      } else {
        setErrors([result.error || 'Booking failed. Please try again.']);
      }
    } catch (error) {
      setErrors([error instanceof Error ? error.message : 'An unexpected error occurred']);
    } finally {
      setIsProcessing(false);
    }
  };

  const isPaymentEnabled = SunHotelsAPI.isPaymentRequired();

  return (
    <div className="max-w-2xl mx-auto p-6">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center space-x-2">
            <Shield className="h-5 w-5 text-blue-600" />
            <span>Complete Your Booking</span>
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Hotel Summary */}
          <div className="bg-gray-50 p-4 rounded-lg">
            <h3 className="font-semibold text-lg">{hotel.name}</h3>
            <p className="text-gray-600">{hotel.location}</p>
            <div className="flex justify-between items-center mt-2">
              <span>Check-in: {new Date(checkIn).toLocaleDateString()}</span>
              <span>Check-out: {new Date(checkOut).toLocaleDateString()}</span>
            </div>
            <div className="flex justify-between items-center mt-1">
              <span>{guests} guest{guests > 1 ? 's' : ''}, {rooms} room{rooms > 1 ? 's' : ''}</span>
              <span className="font-semibold text-lg">${totalPrice.toFixed(2)}</span>
            </div>
          </div>

          {/* Guest Information */}
          <div className="space-y-4">
            <h3 className="font-semibold text-lg">Guest Information</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="firstName">First Name *</Label>
                <Input
                  id="firstName"
                  value={guestInfo.firstName}
                  onChange={(e) => handleInputChange('firstName', e.target.value)}
                  placeholder="Enter first name"
                />
              </div>
              <div>
                <Label htmlFor="lastName">Last Name *</Label>
                <Input
                  id="lastName"
                  value={guestInfo.lastName}
                  onChange={(e) => handleInputChange('lastName', e.target.value)}
                  placeholder="Enter last name"
                />
              </div>
              <div>
                <Label htmlFor="email">Email *</Label>
                <Input
                  id="email"
                  type="email"
                  value={guestInfo.email}
                  onChange={(e) => handleInputChange('email', e.target.value)}
                  placeholder="Enter email address"
                />
              </div>
              <div>
                <Label htmlFor="phone">Phone *</Label>
                <Input
                  id="phone"
                  value={guestInfo.phone}
                  onChange={(e) => handleInputChange('phone', e.target.value)}
                  placeholder="Enter phone number"
                />
              </div>
            </div>
          </div>

          {/* Payment Section */}
          <div className="space-y-4">
            <h3 className="font-semibold text-lg">Payment</h3>
            
            {!isPaymentEnabled ? (
              <Alert className="border-orange-200 bg-orange-50">
                <AlertCircle className="h-4 w-4 text-orange-600" />
                <AlertDescription>
                  <strong>Test Mode:</strong> Payment system is not enabled. 
                  This booking will not be sent to SunHotels until payment processing is configured.
                </AlertDescription>
              </Alert>
            ) : (
              <div className="space-y-4">
                <div className="bg-blue-50 p-4 rounded-lg">
                  <div className="flex items-center space-x-2 mb-2">
                    <CreditCard className="h-5 w-5 text-blue-600" />
                    <span className="font-medium">Payment Required</span>
                  </div>
                  <p className="text-sm text-gray-600">
                    Complete payment to confirm your booking. Your booking will only be sent to SunHotels after successful payment.
                  </p>
                </div>
                
                <div className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    id="paymentConfirmed"
                    checked={paymentConfirmed}
                    onChange={(e) => setPaymentConfirmed(e.target.checked)}
                    className="rounded border-gray-300"
                  />
                  <Label htmlFor="paymentConfirmed" className="text-sm">
                    I confirm that payment has been processed and I authorize this booking
                  </Label>
                </div>
              </div>
            )}
          </div>

          {/* Errors */}
          {errors.length > 0 && (
            <Alert className="border-red-200 bg-red-50">
              <AlertCircle className="h-4 w-4 text-red-600" />
              <AlertDescription>
                <ul className="list-disc list-inside space-y-1">
                  {errors.map((error, index) => (
                    <li key={index}>{error}</li>
                  ))}
                </ul>
              </AlertDescription>
            </Alert>
          )}

          {/* Booking Result */}
          {bookingResult && (
            <Alert className="border-green-200 bg-green-50">
              <CheckCircle className="h-4 w-4 text-green-600" />
              <AlertDescription>
                <strong>Booking {bookingResult.success ? 'Successful!' : 'Failed'}</strong>
                {bookingResult.message && <p className="mt-1">{bookingResult.message}</p>}
              </AlertDescription>
            </Alert>
          )}

          {/* Action Buttons */}
          <div className="flex space-x-4">
            <Button
              onClick={handleBooking}
              disabled={isProcessing || (!isPaymentEnabled && !paymentConfirmed)}
              className="flex-1"
            >
              {isProcessing ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  Processing...
                </>
              ) : (
                <>
                  <Shield className="h-4 w-4 mr-2" />
                  {isPaymentEnabled ? 'Complete Booking' : 'Create Test Booking'}
                </>
              )}
            </Button>
            {onCancel && (
              <Button variant="outline" onClick={onCancel}>
                Cancel
              </Button>
            )}
          </div>

          {/* Security Notice */}
          <div className="text-xs text-gray-500 text-center">
            <Shield className="h-3 w-3 inline mr-1" />
            Your booking is secure and will only be processed after payment confirmation.
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
