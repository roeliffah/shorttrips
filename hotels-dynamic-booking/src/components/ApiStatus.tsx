import React, { useState, useEffect } from 'react';
import { CheckCircle, XCircle, Loader2, AlertCircle, Shield, Info, Zap } from 'lucide-react';
import { SunHotelsAPI } from '@/services/api';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface ApiStatusProps {
  className?: string;
}

export const ApiStatus: React.FC<ApiStatusProps> = ({ className = '' }) => {
  const [status, setStatus] = useState<'checking' | 'not-configured'>('checking');
  const [message, setMessage] = useState('');
  const [isChecking, setIsChecking] = useState(false);

  const checkApiStatus = async () => {
    setIsChecking(true);
    setStatus('checking');
    setTimeout(() => {
      setStatus('not-configured');
      setMessage('SunHotels API wordt nu alleen nog via de backend benaderd. Credentials zijn veilig.');
      setIsChecking(false);
    }, 500);
  }

  useEffect(() => {
    checkApiStatus();
  }, []);

  const getStatusIcon = () => {
    switch (status) {
      case 'checking':
        return <Loader2 className="h-4 w-4 animate-spin" />;
      case 'not-configured':
        return <AlertCircle className="h-4 w-4 text-yellow-500" />;
      default:
        return <AlertCircle className="h-4 w-4 text-gray-500" />;
    }
  };

  const getStatusColor = () => {
    switch (status) {
      case 'not-configured':
        return 'border-yellow-200 bg-yellow-50';
      case 'checking':
      default:
        return 'border-gray-200 bg-gray-50';
    }
  };

  return (
    <div className={className}>
      <Alert className={getStatusColor()}>
        <div className="flex items-center space-x-2">
          {getStatusIcon()}
          <AlertDescription className="font-medium">
            SunHotels API wordt veilig via de backend benaderd. Credentials zijn niet zichtbaar in de browser.
          </AlertDescription>
        </div>
        {message && (
          <div className="mt-2 text-sm text-gray-600">
            {message}
          </div>
        )}
      </Alert>
    </div>
  );
}
