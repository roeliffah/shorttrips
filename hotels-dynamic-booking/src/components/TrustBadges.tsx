import React from 'react';
import { Shield, Award, Users, Clock, Star, CheckCircle } from 'lucide-react';

export const TrustBadges: React.FC = () => {
  const features = [
    {
      icon: <Shield className="h-6 w-6 text-blue-600" />,
      title: 'Secure Booking',
      description: 'Your data and payments are protected with bank-level security'
    },
    {
      icon: <Award className="h-6 w-6 text-green-600" />,
      title: 'Best Price Guarantee',
      description: 'We guarantee the best rates or we\'ll match the difference'
    },
    {
      icon: <Users className="h-6 w-6 text-purple-600" />,
      title: '24/7 Support',
      description: 'Round-the-clock customer support for all your needs'
    },
    {
      icon: <Clock className="h-6 w-6 text-orange-600" />,
      title: 'Instant Confirmation',
      description: 'Get immediate booking confirmation for your peace of mind'
    }
  ];

  const stats = [
    { number: '2M+', label: 'Happy Customers' },
    { number: '50K+', label: 'Hotels Worldwide' },
    { number: '99.9%', label: 'Uptime' },
    { number: '4.8/5', label: 'Customer Rating' }
  ];

  return (
    <section className="py-16 bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold text-gray-900 mb-4">
            Why Choose Freestays?
          </h2>
          <p className="text-lg text-gray-600 max-w-2xl mx-auto">
            We're committed to providing you with the best hotel booking experience 
            with unmatched service, security, and value.
          </p>
        </div>

        {/* Features Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
          {features.map((feature, index) => (
            <div key={index} className="text-center">
              <div className="flex justify-center mb-4">
                {feature.icon}
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                {feature.title}
              </h3>
              <p className="text-gray-600 text-sm">
                {feature.description}
              </p>
            </div>
          ))}
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-16">
          {stats.map((stat, index) => (
            <div key={index} className="text-center">
              <div className="text-3xl font-bold text-blue-600 mb-2">
                {stat.number}
              </div>
              <div className="text-gray-600">
                {stat.label}
              </div>
            </div>
          ))}
        </div>

        {/* Trust Indicators */}
        <div className="bg-white rounded-lg shadow-sm p-8">
          <div className="text-center mb-8">
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Trusted by Millions Worldwide
            </h3>
            <div className="flex justify-center items-center space-x-2 mb-4">
              {[...Array(5)].map((_, i) => (
                <Star key={i} className="h-5 w-5 text-yellow-400 fill-current" />
              ))}
              <span className="ml-2 text-gray-600">4.8/5 from 50,000+ reviews</span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="flex items-center">
              <CheckCircle className="h-5 w-5 text-green-500 mr-3" />
              <span className="text-gray-700">SSL Encrypted Transactions</span>
            </div>
            <div className="flex items-center">
              <CheckCircle className="h-5 w-5 text-green-500 mr-3" />
              <span className="text-gray-700">PCI DSS Compliant</span>
            </div>
            <div className="flex items-center">
              <CheckCircle className="h-5 w-5 text-green-500 mr-3" />
              <span className="text-gray-700">GDPR Compliant</span>
            </div>
          </div>

          <div className="mt-8 text-center">
            <p className="text-gray-600">
              Join millions of travelers who trust Freestays for their accommodation needs.
            </p>
          </div>
        </div>
      </div>
    </section>
  );
};
