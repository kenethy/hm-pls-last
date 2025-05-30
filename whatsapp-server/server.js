const { Client, LocalAuth } = require('whatsapp-web.js');
const express = require('express');
const cors = require('cors');
const QRCode = require('qrcode');

const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// WhatsApp Client
let client;
let qrCodeData = null;
let isReady = false;
let sessionStatus = 'disconnected';

// Initialize WhatsApp Client
function initializeClient() {
    client = new Client({
        authStrategy: new LocalAuth({
            clientId: 'hartono-motor'
        }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--disable-gpu'
            ]
        }
    });

    // Event Handlers
    client.on('qr', async (qr) => {
        console.log('QR Code received');
        qrCodeData = qr;
        sessionStatus = 'qr_ready';
        
        // Generate QR Code image
        try {
            const qrImage = await QRCode.toDataURL(qr);
            qrCodeData = {
                qr: qr,
                qrImage: qrImage
            };
        } catch (err) {
            console.error('Error generating QR code image:', err);
        }
    });

    client.on('ready', () => {
        console.log('WhatsApp Client is ready!');
        isReady = true;
        sessionStatus = 'ready';
        qrCodeData = null;
    });

    client.on('authenticated', () => {
        console.log('WhatsApp Client authenticated');
        sessionStatus = 'authenticated';
    });

    client.on('auth_failure', (msg) => {
        console.error('Authentication failure:', msg);
        sessionStatus = 'auth_failure';
        isReady = false;
    });

    client.on('disconnected', (reason) => {
        console.log('WhatsApp Client disconnected:', reason);
        sessionStatus = 'disconnected';
        isReady = false;
        qrCodeData = null;
    });

    client.on('message', async (message) => {
        console.log('Message received:', message.body);
        
        // Send webhook to Laravel if configured
        const webhookUrl = process.env.WEBHOOK_URL;
        if (webhookUrl) {
            try {
                const fetch = (await import('node-fetch')).default;
                await fetch(webhookUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': process.env.API_KEY || 'hartonomotor2024'
                    },
                    body: JSON.stringify({
                        event: 'message',
                        data: {
                            id: message.id._serialized,
                            body: message.body,
                            from: message.from,
                            to: message.to,
                            timestamp: message.timestamp,
                            type: message.type
                        }
                    })
                });
            } catch (error) {
                console.error('Webhook error:', error);
            }
        }
    });

    client.on('message_ack', async (message, ack) => {
        console.log('Message ACK:', message.id._serialized, 'ACK:', ack);
        
        // Send webhook to Laravel
        const webhookUrl = process.env.WEBHOOK_URL;
        if (webhookUrl) {
            try {
                const fetch = (await import('node-fetch')).default;
                await fetch(webhookUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': process.env.API_KEY || 'hartonomotor2024'
                    },
                    body: JSON.stringify({
                        event: 'message_ack',
                        data: {
                            messageId: message.id._serialized,
                            ack: ack
                        }
                    })
                });
            } catch (error) {
                console.error('Webhook error:', error);
            }
        }
    });

    // Initialize client
    client.initialize();
}

// API Routes

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        timestamp: new Date().toISOString(),
        sessionStatus: sessionStatus,
        isReady: isReady
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

// Get QR Code
app.get('/session/qr', (req, res) => {
    if (qrCodeData) {
        res.json({
            success: true,
            qr: qrCodeData.qr,
            qrImage: qrCodeData.qrImage
        });
    } else {
        res.status(404).json({
            success: false,
            message: 'QR Code not available'
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
    if (!client) {
        initializeClient();
        res.json({
            success: true,
            message: 'Session starting...'
        });
    } else {
        res.json({
            success: true,
            message: 'Session already exists',
            status: sessionStatus
        });
    }
});

// Terminate session
app.delete('/session/terminate', async (req, res) => {
    try {
        if (client) {
            await client.destroy();
            client = null;
            isReady = false;
            sessionStatus = 'disconnected';
            qrCodeData = null;
        }
        res.json({
            success: true,
            message: 'Session terminated'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Send message
app.post('/message/send', async (req, res) => {
    try {
        if (!isReady) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp client is not ready'
            });
        }

        const { phone, message } = req.body;
        
        if (!phone || !message) {
            return res.status(400).json({
                success: false,
                message: 'Phone and message are required'
            });
        }

        // Format phone number
        let formattedPhone = phone.replace(/[^0-9]/g, '');
        if (formattedPhone.startsWith('0')) {
            formattedPhone = '62' + formattedPhone.substring(1);
        }
        if (!formattedPhone.startsWith('62')) {
            formattedPhone = '62' + formattedPhone;
        }
        formattedPhone += '@c.us';

        const sentMessage = await client.sendMessage(formattedPhone, message);
        
        res.json({
            success: true,
            messageId: sentMessage.id._serialized,
            message: 'Message sent successfully'
        });
    } catch (error) {
        console.error('Send message error:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Check if number is registered
app.post('/number/check', async (req, res) => {
    try {
        if (!isReady) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp client is not ready'
            });
        }

        const { phone } = req.body;
        
        if (!phone) {
            return res.status(400).json({
                success: false,
                message: 'Phone number is required'
            });
        }

        // Format phone number
        let formattedPhone = phone.replace(/[^0-9]/g, '');
        if (formattedPhone.startsWith('0')) {
            formattedPhone = '62' + formattedPhone.substring(1);
        }
        if (!formattedPhone.startsWith('62')) {
            formattedPhone = '62' + formattedPhone;
        }
        formattedPhone += '@c.us';

        const isRegistered = await client.isRegisteredUser(formattedPhone);
        
        res.json({
            success: true,
            isRegistered: isRegistered,
            phone: formattedPhone
        });
    } catch (error) {
        console.error('Check number error:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Get client info
app.get('/client/info', async (req, res) => {
    try {
        if (!isReady) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp client is not ready'
            });
        }

        const info = client.info;
        res.json({
            success: true,
            info: {
                pushname: info.pushname,
                wid: info.wid.user,
                platform: info.platform
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
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
    console.log(`Hartono Motor WhatsApp Server running on port ${port}`);
    console.log(`Health check: http://localhost:${port}/health`);
    
    // Auto-start client
    initializeClient();
});

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('Shutting down gracefully...');
    if (client) {
        await client.destroy();
    }
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('Shutting down gracefully...');
    if (client) {
        await client.destroy();
    }
    process.exit(0);
});
