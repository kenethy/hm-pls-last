# WhatsApp Integration Testing Guide

## 🚀 **Complete Implementation Steps**

### **Step 1: Authentication Setup**

1. **Access Filament Admin Panel**
   - Go to: WhatsApp Integration > Konfigurasi WhatsApp
   - Verify API URL is set to: `http://whatsapp-api:3000` or `http://hartono-whatsapp-api:3000`

2. **Test Connection**
   - Click "Test Koneksi" button
   - Should show "Koneksi Berhasil" (Connection Successful)

3. **WhatsApp Authentication**
   - Click "Autentikasi WhatsApp" button (opens WhatsApp API web interface)
   - OR click "Dapatkan QR Code" button to generate QR code
   - Scan QR code with your WhatsApp mobile app
   - Wait for authentication to complete

4. **Verify Authentication**
   - Refresh the WhatsApp Configuration page
   - "Status WhatsApp" column should show "Authenticated"

### **Step 2: Message Sending Test**

1. **Test Manual Message**
   - In WhatsApp Configuration, click "Test Pesan" button
   - Enter your phone number (format: 08123456789 or 628123456789)
   - Modify test message if needed
   - Click "Send"
   - Check your WhatsApp for the test message

2. **Verify Message Logs**
   - Go to: WhatsApp Integration > Pesan WhatsApp
   - Should see the test message with status "sent"

### **Step 3: Follow-up Template Configuration**

1. **Create Follow-up Template**
   - Go to: WhatsApp Integration > Template Follow-up
   - Click "Create"
   - Fill in template details:
     ```
     Name: Service Completion Follow-up
     Trigger Event: Selesai Servis
     WhatsApp Enabled: ✓ Yes
     Auto Send on Completion: ✓ Yes
     Message: Halo {customer_name}! Servis {service_type} untuk kendaraan {vehicle_info} telah selesai. Total biaya: {total_cost}. Terima kasih telah mempercayakan kendaraan Anda kepada Hartono Motor!
     ```

2. **Test Variables**
   - Use available variables: `{customer_name}`, `{service_type}`, `{vehicle_info}`, `{total_cost}`, etc.

### **Step 4: Service Completion Integration Test**

1. **Create Test Service**
   - Go to: Services > Create Service
   - Fill in customer details with valid phone number
   - Set service details

2. **Complete Service**
   - Edit the service
   - Change status to "Completed"
   - Save changes

3. **Verify Automatic Follow-up**
   - Check customer's WhatsApp for automatic follow-up message
   - Check WhatsApp Messages log for the automated message
   - Verify template usage count increased

### **Step 5: Webhook Configuration (Optional)**

1. **Configure Webhook in WhatsApp API**
   - Access WhatsApp API web interface: `http://your-domain:3000`
   - Set webhook URL to: `https://your-domain/api/whatsapp/webhook`
   - Set webhook secret (same as in Filament config)

2. **Test Incoming Messages**
   - Send a message to the authenticated WhatsApp number
   - Check WhatsApp Messages log for incoming message

## 🔍 **Troubleshooting Common Issues**

### **Authentication Issues**

**Problem**: QR Code not generating
**Solution**:
```bash
# Check WhatsApp API logs
docker-compose logs whatsapp-api

# Restart WhatsApp API
docker-compose restart whatsapp-api

# Test API endpoint directly
curl http://localhost:3000/app/login
```

**Problem**: Authentication fails after scanning QR
**Solution**:
- Ensure WhatsApp app is updated
- Try using pairing code instead
- Check if WhatsApp number is already linked to another device

### **Message Sending Issues**

**Problem**: Messages not being sent
**Solution**:
1. Verify WhatsApp authentication status
2. Check phone number format (must include country code)
3. Ensure WhatsApp API has internet connection
4. Check Laravel logs: `docker-compose logs app`

**Problem**: Follow-up messages not automatic
**Solution**:
1. Verify template has "Auto Send on Completion" enabled
2. Check if template trigger event is "service_completion"
3. Verify customer has valid phone number
4. Check Laravel logs for event processing

### **Webhook Issues**

**Problem**: Webhook not receiving messages
**Solution**:
1. Verify webhook URL is accessible from internet
2. Check webhook secret configuration
3. Ensure HTTPS is used for webhook URL
4. Test webhook endpoint manually

## 📊 **Performance Monitoring**

### **Key Metrics to Monitor**

1. **Message Delivery Rate**
   - Go to: WhatsApp Integration > Pesan WhatsApp
   - Filter by status: "sent" vs "failed"
   - Target: >95% success rate

2. **Template Usage**
   - Check template usage counts
   - Monitor which templates are most effective

3. **Response Times**
   - Monitor time between service completion and message delivery
   - Target: <2 minutes for automatic follow-ups

### **Log Monitoring**

```bash
# Monitor WhatsApp API logs
docker-compose logs -f whatsapp-api

# Monitor Laravel logs
docker-compose logs -f app

# Check specific WhatsApp integration logs
docker-compose exec app tail -f storage/logs/laravel.log | grep -i whatsapp
```

## ✅ **Success Criteria Checklist**

- [ ] WhatsApp API connection successful
- [ ] WhatsApp authentication completed
- [ ] Manual test message sent and received
- [ ] Follow-up template created and configured
- [ ] Automatic follow-up triggered on service completion
- [ ] Message logs showing successful deliveries
- [ ] Webhook receiving incoming messages (if configured)
- [ ] No errors in application logs
- [ ] Performance metrics within acceptable ranges

## 🚨 **Emergency Procedures**

### **If WhatsApp Integration Fails Completely**

1. **Disable Auto-Send**
   ```sql
   UPDATE follow_up_templates SET auto_send_on_completion = 0;
   ```

2. **Restart Services**
   ```bash
   docker-compose restart whatsapp-api app
   ```

3. **Fallback to Manual Follow-up**
   - Use existing manual WhatsApp URL generation
   - Staff can manually send messages via WhatsApp Web

### **If Messages Are Not Delivering**

1. **Check Authentication Status**
2. **Verify Phone Number Formats**
3. **Test with Different Phone Numbers**
4. **Check WhatsApp API Server Status**

## 📞 **Support Information**

For technical support:
1. Check troubleshooting guide first
2. Collect relevant logs
3. Document exact error messages
4. Note when the issue started occurring
