#!/bin/bash

# Freestays Environment Setup Script
echo "🏨 Setting up Freestays environment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created successfully!"
else
    echo "⚠️  .env file already exists. Skipping creation."
fi

echo ""
echo "🔧 Next steps:"
echo "1. Edit the .env file with your SunHotels API credentials"
echo "2. Set VITE_SUNHOTELS_API_KEY to your actual API key"
echo "3. Set VITE_SUNHOTELS_PARTNER_ID to your partner ID"
echo "4. Set VITE_SUNHOTELS_SECRET_KEY to your secret key"
echo "5. Run 'npm run dev' to start the development server"
echo ""
echo "📚 For detailed setup instructions, see SUNHOTELS_SETUP.md"
echo ""
echo "🚀 Happy coding with Freestays!"
