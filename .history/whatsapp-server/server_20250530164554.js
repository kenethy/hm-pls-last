const { Client, LocalAuth } = require('whatsapp-web.js');
const express = require('express');
const cors = require('cors');
const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');

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

// Cleanup function to remove lock files
function cleanupChromiumLocks() {
    try {
        const lockPaths = [
            '/tmp/chromium-user-data/SingletonLock',
            '/tmp/chromium-user-data/SingletonSocket',
            '/tmp/chromium-user-data/SingletonCookie'
        ];

        lockPaths.forEach(lockPath => {
            if (fs.existsSync(lockPath)) {
                fs.unlinkSync(lockPath);
                console.log(`Removed lock file: ${lockPath}`);
            }
        });

        // Also cleanup any existing chromium processes
        const { execSync } = require('child_process');
        try {
            execSync('pkill -f chromium', { stdio: 'ignore' });
            console.log('Killed existing chromium processes');
        } catch (e) {
            // Ignore if no processes found
        }
    } catch (error) {
        console.log('Cleanup warning:', error.message);
    }
}

// Initialize WhatsApp Client
function initializeClient() {
    console.log('🚀 initializeClient() called - starting WhatsApp Web.js initialization');

    // Cleanup before starting
    console.log('🧹 Running cleanup...');
    cleanupChromiumLocks();

    // Clear old session to force QR generation
    console.log('🗑️ Clearing old session data...');
    try {
        const { execSync } = require('child_process');
        execSync('rm -rf /app/.wwebjs_auth/session-*', { stdio: 'ignore' });
        execSync('rm -f /app/.wwebjs_auth/whatsapp.db', { stdio: 'ignore' });
        console.log('✅ Old session data cleared');
    } catch (e) {
        console.log('ℹ️ No old session data to clear');
    }
    client = new Client({
        authStrategy: new LocalAuth({
            clientId: 'hartono-motor',
            dataPath: '/app/.wwebjs_auth'
        }),
        session: null, // Force new session to generate QR
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
                '--disable-web-security',
                '--disable-features=VizDisplayCompositor',
                '--disable-extensions',
                '--disable-plugins',
                '--disable-default-apps',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-renderer-backgrounding',
                '--disable-field-trial-config',
                '--disable-back-forward-cache',
                '--disable-ipc-flooding-protection',
                '--disable-software-rasterizer',
                '--disable-background-networking',
                '--disable-sync',
                '--disable-translate',
                '--hide-scrollbars',
                '--metrics-recording-only',
                '--mute-audio',
                '--no-default-browser-check',
                '--safebrowsing-disable-auto-update',
                '--disable-client-side-phishing-detection',
                '--disable-component-update',
                '--disable-hang-monitor',
                '--disable-logging',
                '--log-level=3',
                '--disable-crash-reporter',
                '--disable-in-process-stack-traces',
                '--disable-breakpad',
                '--disable-canvas-aa',
                '--disable-2d-canvas-clip-aa',
                '--disable-gl-drawing-for-tests',
                '--disable-threaded-animation',
                '--disable-threaded-scrolling',
                '--disable-checker-imaging',
                '--disable-new-content-rendering-timeout',
                '--disable-image-animation-resync',
                '--disable-partial-raster',
                '--disable-skia-runtime-opts',
                '--run-all-compositor-stages-before-draw',
                '--disable-new-video-renderer',
                '--disable-lcd-text',
                '--disable-layer-tree-host-memory-pressure'
            ],
            executablePath: '/usr/bin/chromium-browser',
            timeout: 180000
        }
    });

    console.log('📱 WhatsApp Client created, setting up event handlers...');

    // Event Handlers with detailed logging
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
        console.log('🟢 WhatsApp Client is ready!');
        isReady = true;
        sessionStatus = 'ready';
        qrCodeData = null;
    });

    client.on('authenticated', () => {
        console.log('🔐 WhatsApp Client authenticated');
        sessionStatus = 'authenticated';
    });

    client.on('auth_failure', (msg) => {
        console.error('❌ Authentication failure:', msg);
        sessionStatus = 'auth_failure';
        isReady = false;
    });

    client.on('disconnected', (reason) => {
        console.log('🔴 WhatsApp Client disconnected:', reason);
        sessionStatus = 'disconnected';
        isReady = false;
        qrCodeData = null;
    });

    client.on('loading_screen', (percent, message) => {
        console.log(`⏳ Loading: ${percent}% - ${message}`);
    });

    client.on('change_state', state => {
        console.log('🔄 State changed:', state);
    });

    // Add error handling for client initialization
    client.on('error', (error) => {
        console.error('❌ WhatsApp Client Error:', error);
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

    // Initialize client with error handling
    console.log('🔄 Calling client.initialize()...');
    try {
        await client.initialize();
        console.log('✅ client.initialize() completed successfully!');
    } catch (error) {
        console.error('❌ Failed to initialize WhatsApp client:', error);
        throw error;
    }
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

    // Auto-start client with delay and logging
    console.log('Starting WhatsApp client initialization...');
    setTimeout(() => {
        console.log('Calling initializeClient()...');
        try {
            initializeClient();
        } catch (error) {
            console.error('Error initializing client:', error);
        }
    }, 2000); // 2 second delay
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
