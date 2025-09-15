# Freestays - Hotel Booking Platform

A modern, responsive hotel booking platform built with React, TypeScript, and integrated with SunHotels API for real-time hotel data and bookings.

## 🚀 Features

- **Real-time Hotel Search**: Powered by SunHotels API
- **Advanced Filtering**: Filter by price, rating, amenities, and more
- **Responsive Design**: Works seamlessly on desktop and mobile
- **Modern UI**: Built with Tailwind CSS and shadcn/ui components
- **TypeScript**: Full type safety throughout the application
- **API Integration**: Seamless connection to SunHotels API
- **Mock Data Fallback**: Graceful fallback when API is unavailable

## 🛠️ Tech Stack

- **Frontend**: React 18, TypeScript, Vite
- **Styling**: Tailwind CSS, shadcn/ui
- **State Management**: React Query, React Context
- **API Integration**: SunHotels API
- **Icons**: Lucide React
- **Build Tool**: Vite

## 📋 Prerequisites

- Node.js 18+ 
- npm or yarn
- SunHotels API credentials (API key, Partner ID, Secret key)

## 🚀 Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd hotels-dynamic-booking
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Set up environment variables**
   ```bash
   # Run the setup script
   ./setup-env.sh
   
   # Or manually copy and edit
   cp .env.example .env
   ```

4. **Configure SunHotels API**
   Edit the `.env` file with your SunHotels credentials:
   ```env
   VITE_SUNHOTELS_API_KEY=your_api_key_here
   VITE_SUNHOTELS_PARTNER_ID=your_partner_id_here
   VITE_SUNHOTELS_SECRET_KEY=your_secret_key_here
   ```

5. **Start the development server**
   ```bash
   npm run dev
   ```

6. **Open your browser**
   Navigate to `http://localhost:8080`

## 🔧 Configuration

### Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `VITE_SUNHOTELS_API_KEY` | Your SunHotels API key | Yes |
| `VITE_SUNHOTELS_API_BASE` | SunHotels API base URL | No (defaults to https://api.sunhotels.com/v1) |
| `VITE_SUNHOTELS_PARTNER_ID` | Your SunHotels partner ID | Yes |
| `VITE_SUNHOTELS_SECRET_KEY` | Your SunHotels secret key | Yes |
| `VITE_APP_NAME` | Application name | No (defaults to Freestays) |
| `VITE_APP_VERSION` | Application version | No (defaults to 1.0.0) |

### API Status

The application includes a built-in API status checker that:
- Shows connection status in development mode
- Provides helpful error messages
- Guides you through the setup process
- Automatically falls back to mock data when API is unavailable

## 📁 Project Structure

```
src/
├── components/          # React components
│   ├── ui/             # shadcn/ui components
│   ├── ApiStatus.tsx   # API connection status
│   ├── Header.tsx      # Application header
│   ├── Footer.tsx      # Application footer
│   └── ...
├── contexts/           # React contexts
├── hooks/              # Custom React hooks
├── services/           # API services
│   ├── api.ts         # SunHotels API integration
│   └── database.ts    # Database service
├── types/              # TypeScript type definitions
└── pages/              # Page components
```

## 🔌 API Integration

### SunHotels API Endpoints

The application integrates with the following SunHotels API endpoints:

- `GET /hotels/search` - Search for hotels
- `GET /hotels/{id}` - Get hotel details
- `GET /hotels/{id}/availability` - Check hotel availability
- `POST /bookings` - Create a booking
- `POST /bookings/{id}/cancel` - Cancel a booking
- `GET /health` - API health check

### Error Handling

The application includes comprehensive error handling:
- Automatic fallback to mock data when API calls fail
- Detailed error logging for debugging
- User-friendly error messages
- Retry mechanisms for failed requests

## 🎨 Customization

### Branding

To customize the branding:
1. Update the app name in `.env` file
2. Modify the logo in `Header.tsx` and `Footer.tsx`
3. Update colors in `tailwind.config.ts`
4. Change the favicon in `index.html`

### Styling

The application uses Tailwind CSS for styling. You can:
- Modify the color scheme in `tailwind.config.ts`
- Update component styles in individual component files
- Add custom CSS in `src/index.css`

## 🚀 Deployment

### Production Build

```bash
npm run build
```

### Environment Variables for Production

Make sure to set all required environment variables in your production environment:
- `VITE_SUNHOTELS_API_KEY`
- `VITE_SUNHOTELS_PARTNER_ID`
- `VITE_SUNHOTELS_SECRET_KEY`

### Hosting

The application can be deployed to any static hosting service:
- Vercel
- Netlify
- AWS S3 + CloudFront
- GitHub Pages

## 🐛 Troubleshooting

### Common Issues

1. **API not connecting**
   - Check your API credentials in `.env`
   - Verify your SunHotels account has API access
   - Check the browser console for error messages

2. **Mock data showing instead of real data**
   - This is expected when API calls fail
   - Check your API credentials and network connection
   - Review the API status component for details

3. **Build errors**
   - Make sure all dependencies are installed
   - Check for TypeScript errors
   - Verify environment variables are properly set

### Getting Help

- Check the browser console for error messages
- Review the API status component for connection issues
- See `SUNHOTELS_SETUP.md` for detailed setup instructions
- Contact SunHotels support for API-related issues

## 📄 License

This project is licensed under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📞 Support

For support and questions:
- Check the troubleshooting section above
- Review the setup documentation
- Contact the development team

---

**Happy booking with Freestays! 🏨✨**
