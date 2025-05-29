# 📱 WhatsApp Integration Deployment Guide

## 🚀 Pre-Deployment Checklist

### ✅ Files Created/Modified:
- **Database Migrations:**
  - `2025_01_15_000000_create_follow_up_templates_table.php`
  - `2025_01_15_000001_create_whatsapp_config_table.php`
  - `2025_01_15_000002_create_whatsapp_messages_table.php`
  - `2025_01_15_000003_enhance_follow_up_templates_for_whatsapp.php`

- **Models:**
  - `app/Models/WhatsAppConfig.php`
  - `app/Models/WhatsAppMessage.php`
  - `app/Models/FollowUpTemplate.php`

- **Services:**
  - `app/Services/WhatsAppService.php`

- **Filament Resources:**
  - `app/Filament/Resources/WhatsAppConfigResource.php`
  - `app/Filament/Resources/WhatsAppMessageResource.php`
  - `app/Filament/Resources/FollowUpTemplateResource.php`
  - All related Pages and Widgets

- **Event Listeners:**
  - `app/Listeners/SendWhatsAppFollowUp.php`
  - Updated `app/Providers/EventServiceProvider.php`

- **Seeders:**
  - `database/seeders/WhatsAppIntegrationSeeder.php`

## 🔧 VPS Deployment Steps

### 1. Git Pull & Update
```bash
git pull origin main
```

### 2. Install Dependencies (if needed)
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Run Migrations
```bash
php artisan migrate --force
```

### 4. Seed Sample Data
```bash
php artisan db:seed --class=WhatsAppIntegrationSeeder
```

### 5. Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 6. Set Permissions (if needed)
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## 📋 Post-Deployment Configuration

### 1. WhatsApp API Server Setup
- Deploy `go-whatsapp-web-multidevice` using Docker
- Configure environment variables
- Scan QR code for authentication

### 2. Admin Panel Configuration
1. Login to admin panel
2. Go to "WhatsApp Integration" → "Konfigurasi WhatsApp"
3. Set API URL (e.g., `http://localhost:3000`)
4. Set credentials if using Basic Auth
5. Test connection

### 3. Template Configuration
1. Go to "Template Follow-up"
2. Review and customize templates
3. Enable auto-send for desired templates

## 🧪 Testing Checklist

### ✅ Basic Tests:
- [ ] Admin panel loads without errors
- [ ] WhatsApp menu appears in navigation
- [ ] Can create WhatsApp configuration
- [ ] Can test WhatsApp connection
- [ ] Can create follow-up templates
- [ ] Can send manual WhatsApp messages

### ✅ Integration Tests:
- [ ] Complete a service → WhatsApp message sent automatically
- [ ] Template variables replaced correctly
- [ ] Message status tracked properly
- [ ] Error handling works

## 🔍 Troubleshooting

### Common Issues:
1. **Routes not found**: Clear route cache
2. **Class not found**: Run `composer dump-autoload`
3. **Migration errors**: Check database permissions
4. **WhatsApp API connection**: Verify API server is running

### Log Locations:
- Laravel logs: `storage/logs/laravel.log`
- WhatsApp service logs: Check service implementation

## 📱 WhatsApp API Requirements

### Server Requirements:
- Docker support
- Go 1.19+ (if building from source)
- FFmpeg for media processing
- Minimum 1GB RAM, 2GB storage
- Network access to WhatsApp servers

### Environment Variables:
```env
APP_PORT=3000
APP_DEBUG=false
APP_OS=HartonoMotor
APP_BASIC_AUTH=admin:secure_password
WHATSAPP_WEBHOOK=https://yourdomain.com/api/whatsapp/webhook
WHATSAPP_WEBHOOK_SECRET=your_secure_webhook_secret
```

## 🎯 Next Steps After Deployment

1. **Setup WhatsApp API server**
2. **Configure webhook endpoints**
3. **Test message sending**
4. **Train staff on new features**
5. **Monitor message delivery rates**

## 📊 Features Available

### Admin Panel Features:
- ✅ WhatsApp configuration management
- ✅ Message log and monitoring
- ✅ Template management with variables
- ✅ Manual message sending
- ✅ Bulk message retry
- ✅ Statistics dashboard
- ✅ Auto-send on service completion

### Template Variables:
- `{customer_name}` - Nama customer
- `{service_type}` - Jenis servis
- `{vehicle_info}` - Info kendaraan
- `{completion_date}` - Tanggal selesai
- `{total_cost}` - Total biaya
- `{workshop_name}` - Nama bengkel
- `{workshop_phone}` - Telepon bengkel
- `{workshop_address}` - Alamat bengkel

## ⚠️ Important Notes

1. **WhatsApp Business Policy**: Ensure compliance with WhatsApp Business API policies
2. **Rate Limiting**: Monitor message sending rates to avoid blocks
3. **Customer Consent**: Implement proper opt-in mechanisms
4. **Data Privacy**: Ensure GDPR compliance for message storage
5. **Backup**: Regular backup of WhatsApp session data

---

**Status**: ✅ Ready for deployment
**Last Updated**: January 15, 2025
**Version**: 1.0.0
