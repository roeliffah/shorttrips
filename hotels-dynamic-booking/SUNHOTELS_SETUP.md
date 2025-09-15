# SunHotels XML API Setup for Freestays

This guide will help you connect your Freestays application to the SunHotels XML API using the provided test credentials.

## Prerequisites

1. **SunHotels Test Account**: You have test credentials provided
2. **Environment Variables**: Set up your environment configuration
3. **Payment System**: Currently disabled for testing (bookings won't be sent to SunHotels)

## Current Test Credentials

- **API URL**: `https://xml.sunhotels.net/15/PostGet/nonStaticXMLAPI.asmx/`
- **Username**: `FreestaysTEST`
- **Password**: `Vision2024!@`

## Step 1: Environment Configuration

The environment variables are already configured in your `.env` file:

```env
# SunHotels API Configuration (TEST CREDENTIALS)
VITE_SUNHOTELS_API_URL=https://xml.sunhotels.net/15/PostGet/nonStaticXMLAPI.asmx/
VITE_SUNHOTELS_USERNAME=FreestaysTEST
VITE_SUNHOTELS_PASSWORD=Vision2024!@

# Application Configuration
VITE_APP_NAME=Freestays
VITE_APP_VERSION=1.0.0
VITE_APP_ENVIRONMENT=development

# Payment Configuration (Currently Disabled)
VITE_PAYMENT_ENABLED=false
```

## Step 2: Test the Connection

1. Start the development server:
   ```bash
   npm run dev
   ```

2. Check the API status banner at the top of the page
3. The application will show connection status and any errors

## Step 3: Understanding the API Integration

### SunHotels XML API

The application now uses SunHotels XML API with SOAP requests:

- **Base URL**: `https://xml.sunhotels.net/15/PostGet/nonStaticXMLAPI.asmx/`
- **Protocol**: SOAP/XML
- **Authentication**: Username/Password in SOAP envelope
- **Content-Type**: `application/xml`

### Available Endpoints

- `SearchHotels` - Search for available hotels
- `GetHotelDetails` - Get detailed hotel information
- `GetHotelAvailability` - Check room availability
- `CreateBooking` - Create a new booking (requires payment confirmation)
- `CancelBooking` - Cancel an existing booking
- `HealthCheck` - Test API connection

## Step 4: Payment Security

### Current Status: Payment Disabled

- **VITE_PAYMENT_ENABLED=false** - Payment system is disabled
- **No Real Bookings**: Bookings will NOT be sent to SunHotels until payment is enabled
- **Test Mode**: You can test the booking flow without creating real reservations

### When Payment is Enabled

To enable real bookings:

1. Set `VITE_PAYMENT_ENABLED=true` in your `.env` file
2. Integrate with Stripe or your preferred payment processor
3. Ensure payment confirmation before sending to SunHotels

### Booking Validation

The system includes strict validation:
- Payment confirmation is required for all bookings
- Guest information must be complete
- Hotel availability is checked before booking
- All data is validated before sending to SunHotels

## Step 5: Testing the Application

### Hotel Search
- Search for hotels using the search bar
- Apply filters (price, rating, amenities)
- View hotel details and images

### Booking Flow
1. Select a hotel and dates
2. Fill in guest information
3. **Note**: Payment confirmation is required but currently disabled
4. Complete booking (will show test mode message)

### API Status Monitoring
- Real-time connection status
- Error messages and troubleshooting
- Payment system status indicator

## Step 6: Production Setup

When ready for production:

1. **Get Production Credentials**:
   - Contact SunHotels for production API credentials
   - Update environment variables with production values

2. **Enable Payment Processing**:
   - Set `VITE_PAYMENT_ENABLED=true`
   - Integrate with Stripe or payment processor
   - Test payment flow thoroughly

3. **Update API URL**:
   - Change to production SunHotels API URL
   - Test all endpoints with production data

## Troubleshooting

### Common Issues

1. **"Credentials not configured" error**
   - Check that all environment variables are set
   - Restart the development server after changes

2. **API connection failing**
   - Verify your test credentials are correct
   - Check network connectivity
   - Review browser console for detailed errors

3. **Mock data showing instead of real data**
   - This is expected when API calls fail
   - Check API status banner for connection issues
   - Verify SunHotels API is accessible

4. **Booking not working**
   - Check that payment confirmation is checked
   - Verify all guest information is filled
   - Review error messages for specific issues

### Debug Information

The application provides detailed debug information:
- API status in the top banner
- Console logs for API requests/responses
- Error messages with specific details
- Payment system status indicator

## Security Features

### Payment Protection
- No bookings sent without payment confirmation
- Payment system can be disabled for testing
- Clear indicators when in test mode

### Data Validation
- All booking data is validated before sending
- Guest information is required and validated
- Hotel availability is checked before booking

### Error Handling
- Graceful fallback to mock data
- Detailed error messages for debugging
- User-friendly error display

## Next Steps

1. **Test Current Setup**: Verify API connection and search functionality
2. **Test Booking Flow**: Complete booking process (test mode)
3. **Integrate Payment**: Add Stripe or payment processor
4. **Production Credentials**: Get production SunHotels credentials
5. **Go Live**: Enable payment and switch to production

## Support

For issues with:
- **SunHotels API**: Contact SunHotels support
- **Application**: Check console logs and error messages
- **Payment Integration**: Review payment processor documentation

---

**Current Status**: ✅ Test credentials configured, Payment disabled for safety
