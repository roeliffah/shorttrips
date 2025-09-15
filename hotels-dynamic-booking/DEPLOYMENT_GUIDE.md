# 🚀 Deployment Guide for shorttrips.eu (Strato Shared Server)

## ✅ Build Complete!

Your Freestays application has been successfully built for production. The `dist/` folder contains all the files needed for deployment.

## 📁 Files Ready for Upload

The following files in the `dist/` folder need to be uploaded to your Strato server:

```
dist/
├── index.html              # Main HTML file
├── assets/
│   ├── index-BuMGGAWA.css  # Compiled CSS
│   └── index-DydEvlRg.js   # Compiled JavaScript
└── placeholder.svg         # Favicon
```

## 🌐 Strato Shared Server Deployment Steps

### Step 1: Access Your Strato Control Panel

1. Log into your Strato customer account
2. Go to "Websites & Domains" or "Webhosting"
3. Select your domain `shorttrips.eu`
4. Access the file manager or FTP

### Step 2: Upload Files

#### Option A: Using Strato File Manager
1. Navigate to the `htdocs` or `public_html` folder for your domain
2. Delete any existing files (if this is a fresh installation)
3. Upload all files from the `dist/` folder to the root directory
4. Ensure `index.html` is in the root directory

#### Option B: Using FTP/SFTP
1. Connect to your Strato server via FTP
2. Navigate to the web root directory (`htdocs` or `public_html`)
3. Upload all files from the `dist/` folder
4. Set proper file permissions (644 for files, 755 for directories)

### Step 3: Configure Domain

1. In Strato control panel, ensure `shorttrips.eu` points to the correct directory
2. Set up SSL certificate (if not already done)
3. Configure any necessary redirects

### Step 4: Environment Configuration

Since this is a static site, you'll need to configure environment variables differently:

#### Create a `.htaccess` file (if needed)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```

#### For production environment variables, you have two options:

**Option 1: Hardcode in build (Current)**
The current build has the test credentials hardcoded. This is fine for testing.

**Option 2: Use a config file**
Create a `config.js` file in your public directory:

```javascript
window.APP_CONFIG = {
  VITE_SUNHOTELS_API_URL: 'https://xml.sunhotels.net/15/PostGet/nonStaticXMLAPI.asmx/',
  VITE_SUNHOTELS_USERNAME: 'FreestaysTEST',
  VITE_SUNHOTELS_PASSWORD: 'Vision2024!@',
  VITE_APP_NAME: 'Freestays',
  VITE_APP_VERSION: '1.0.0',
  VITE_APP_ENVIRONMENT: 'production',
  VITE_PAYMENT_ENABLED: false
};
```

## 🔧 Post-Deployment Configuration

### 1. Test the Site
- Visit `https://shorttrips.eu`
- Check that the site loads correctly
- Test the search functionality
- Verify API status banner shows correct information

### 2. SSL Certificate
- Ensure HTTPS is working
- Check for any mixed content warnings
- Update any HTTP links to HTTPS

### 3. Performance Optimization
- Enable Gzip compression (usually enabled by default on Strato)
- Set up browser caching headers
- Optimize images if needed

## 🛠️ Troubleshooting

### Common Issues

1. **Site not loading**
   - Check that `index.html` is in the root directory
   - Verify file permissions are correct
   - Check Strato error logs

2. **API not working**
   - Check browser console for CORS errors
   - Verify environment variables are set correctly
   - Test API endpoints directly

3. **Styling issues**
   - Check that CSS files are loading
   - Verify file paths are correct
   - Clear browser cache

### Debug Steps

1. **Check browser console** for JavaScript errors
2. **Check network tab** for failed requests
3. **Verify file structure** matches the dist folder
4. **Test API connectivity** using the status banner

## 🔄 Updating the Site

When you make changes:

1. Run `npm run build` locally
2. Upload new files from `dist/` folder
3. Clear browser cache
4. Test the updated site

## 📊 Monitoring

### Check Site Health
- Visit `https://shorttrips.eu`
- Look for the API status banner
- Test search functionality
- Check console for errors

### Performance Monitoring
- Use Google PageSpeed Insights
- Check GTmetrix for performance
- Monitor Core Web Vitals

## 🚀 Going Live Checklist

- [ ] Files uploaded to Strato server
- [ ] Domain pointing to correct directory
- [ ] SSL certificate active
- [ ] Site loads at `https://shorttrips.eu`
- [ ] API status shows "Connected"
- [ ] Search functionality works
- [ ] No console errors
- [ ] Mobile responsive design works
- [ ] Performance is acceptable

## 📞 Support

### Strato Support
- Contact Strato support for server-related issues
- Check Strato documentation for hosting specifics

### Application Support
- Check browser console for errors
- Review the API status banner
- Test with different browsers

---

## 🎯 Current Status

✅ **Build Complete**: Production files ready in `dist/` folder
✅ **Test Credentials**: SunHotels test API configured
✅ **Payment Disabled**: Safe for testing
✅ **Ready for Upload**: All files prepared for Strato deployment

**Next Step**: Upload the `dist/` folder contents to your Strato server's web root directory.

Good luck with your deployment! 🚀
