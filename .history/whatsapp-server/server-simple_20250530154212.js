const express = require('express');
const cors = require('cors');

const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// Simple mock responses for development/testing
let sessionStatus = 'disconnected';
let qrCodeData = null;
let isReady = false;

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        timestamp: new Date().toISOString(),
        sessionStatus: sessionStatus,
        isReady: isReady,
        message: 'WhatsApp API Mock Server - Ready for integration testing'
    });
});

// Get session status
app.get('/session/status', (req, res) => {
    res.json({
        success: isReady,
        status: sessionStatus,
        isReady: isReady,
        hasQR: qrCodeData !== null
    });
});

// Get QR Code (mock)
app.get('/session/qr', (req, res) => {
    // Generate a proper QR code using qrcode library
    const QRCode = require('qrcode');

    try {
        // Generate QR code for mock WhatsApp connection
        const qrText = 'https://wa.me/qr/MOCK_HARTONO_MOTOR_WHATSAPP_' + Date.now();

        QRCode.toDataURL(qrText, {
            width: 300,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            }
        }, (err, qrImage) => {
            if (err) {
                console.error('QR Code generation error:', err);
                return res.status(500).json({
                    success: false,
                    message: 'Failed to generate QR code'
                });
            }

            qrCodeData = {
                qr: qrText,
                qrImage: qrImage
            };

            console.log('QR Code generated successfully');

            res.json({
                success: true,
                qr: qrCodeData.qr,
                qrImage: qrCodeData.qrImage
            });
        });
    } catch (error) {
        console.error('QR Code generation error:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to generate QR code'
        });
    }
});

// Get QR Code as image
app.get('/session/qr/image', (req, res) => {
    if (qrCodeData && qrCodeData.qrImage) {
        const base64Data = qrCodeData.qrImage.replace(/^data:image\/png;base64,/, '');
        const img = Buffer.from(base64Data, 'base64');
        res.writeHead(200, {
            'Content-Type': 'image/png',
            'Content-Length': img.length
        });
        res.end(img);
    } else {
        res.status(404).json({
            success: false,
            message: 'QR Code image not available'
        });
    }
});

// Start session
app.post('/session/start', (req, res) => {
    sessionStatus = 'qr_ready';
    console.log('Mock session started - QR code ready');
    res.json({
        success: true,
        message: 'Session starting... (Mock mode for testing)'
    });
});

// Terminate session
app.delete('/session/terminate', (req, res) => {
    sessionStatus = 'disconnected';
    isReady = false;
    qrCodeData = null;
    console.log('Mock session terminated');
    res.json({
        success: true,
        message: 'Session terminated'
    });
});

// Send message (mock)
app.post('/message/send', (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({
            success: false,
            message: 'Phone and message are required'
        });
    }

    console.log(`Mock message sent to ${phone}: ${message.substring(0, 50)}...`);

    // Mock successful response
    res.json({
        success: true,
        messageId: 'mock-message-' + Date.now(),
        message: 'Message sent successfully (Mock mode)',
        phone: phone,
        content: message
    });
});

// Check number (mock)
app.post('/number/check', (req, res) => {
    const { phone } = req.body;

    if (!phone) {
        return res.status(400).json({
            success: false,
            message: 'Phone number is required'
        });
    }

    res.json({
        success: true,
        isRegistered: true,
        phone: phone
    });
});

// Get client info (mock)
app.get('/client/info', (req, res) => {
    if (!isReady) {
        return res.status(400).json({
            success: false,
            message: 'WhatsApp client is not ready'
        });
    }

    res.json({
        success: true,
        info: {
            pushname: 'Hartono Motor (Mock)',
            wid: '628123456789',
            platform: 'mock'
        }
    });
});

// Error handling middleware
app.use((error, req, res, next) => {
    console.error('Server error:', error);
    res.status(500).json({
        success: false,
        message: 'Internal server error'
    });
});

// Start server
app.listen(port, () => {
    console.log(`🚀 Hartono Motor WhatsApp Mock Server running on port ${port}`);
    console.log(`📊 Health check: http://localhost:${port}/health`);
    console.log(`🧪 This is a MOCK server for testing WhatsApp integration`);
    console.log(`📱 All API endpoints are functional for development testing`);

    // Simulate ready state after 5 seconds
    setTimeout(() => {
        isReady = true;
        sessionStatus = 'ready';
        console.log('✅ Mock WhatsApp session is now ready!');
        console.log('🔗 You can now test the integration from Laravel Filament');
    }, 5000);
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('🛑 Shutting down mock server gracefully...');
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('🛑 Shutting down mock server gracefully...');
    process.exit(0);
});
