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
async function initializeClient() {
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
                '--disable-gpu',
                '--disable-gpu-sandbox',
                '--disable-software-rasterizer',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-renderer-backgrounding',
                '--disable-features=TranslateUI',
                '--disable-features=VizDisplayCompositor',
                '--disable-features=AudioServiceOutOfProcess',
                '--disable-features=VizServiceDisplayCompositor',
                '--disable-ipc-flooding-protection',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--disable-extensions',
                '--disable-default-apps',
                '--disable-sync',
                '--disable-translate',
                '--hide-scrollbars',
                '--mute-audio',
                '--no-default-browser-check',
                '--disable-component-update',
                '--disable-domain-reliability',
                '--disable-background-networking',
                '--disable-breakpad',
                '--disable-client-side-phishing-detection',
                '--disable-crash-reporter',
                '--disable-default-apps',
                '--disable-dev-shm-usage',
                '--disable-extensions',
                '--disable-features=site-per-process',
                '--disable-hang-monitor',
                '--disable-popup-blocking',
                '--disable-prompt-on-repost',
                '--disable-web-security',
                '--log-level=3',
                '--no-first-run',
                '--no-service-autorun',
                '--password-store=basic',
                '--use-mock-keychain',
                '--disable-accelerated-2d-canvas',
                '--disable-accelerated-jpeg-decoding',
                '--disable-accelerated-mjpeg-decode',
                '--disable-accelerated-video-decode',
                '--disable-accelerated-video-encode',
                '--disable-app-list-dismiss-on-blur',
                '--disable-background-mode',
                '--disable-blinking-features=AutomationControlled',
                '--disable-renderer-accessibility',
                '--force-color-profile=srgb',
                '--metrics-recording-only',
                '--disable-print-preview',
                '--no-pings',
                '--use-gl=swiftshader',
                '--disable-gl-extensions'
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

// Get all chats
app.get('/chats/list', (req, res) => {
    if (!isReady) {
        return res.status(400).json({
            success: false,
            message: 'WhatsApp client is not ready'
        });
    }

    client.getChats().then(chats => {
        const formattedChats = chats.map(chat => ({
            id: chat.id._serialized,
            name: chat.name,
            isGroup: chat.isGroup,
            timestamp: chat.timestamp,
            unreadCount: chat.unreadCount,
            lastMessage: chat.lastMessage ? {
                body: chat.lastMessage.body,
                timestamp: chat.lastMessage.timestamp,
                fromMe: chat.lastMessage.fromMe
            } : null
        }));

        res.json({
            success: true,
            chats: formattedChats
        });
    }).catch(error => {
        console.error('Error getting chats:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    });
});

// Get messages from a chat
app.post('/chats/messages', async (req, res) => {
    if (!isReady) {
        return res.status(400).json({
            success: false,
            message: 'WhatsApp client is not ready'
        });
    }

    const { chatId, limit = 50 } = req.body;

    if (!chatId) {
        return res.status(400).json({
            success: false,
            message: 'Chat ID is required'
        });
    }

    try {
        const chat = await client.getChatById(chatId);
        const messages = await chat.fetchMessages({ limit: parseInt(limit) });

        const formattedMessages = messages.map(msg => ({
            id: msg.id._serialized,
            body: msg.body,
            fromMe: msg.fromMe,
            timestamp: msg.timestamp,
            type: msg.type,
            hasMedia: msg.hasMedia,
            ack: msg.ack,
            isForwarded: msg.isForwarded,
            author: msg.author,
            caption: msg.caption || ''
        }));

        res.json({
            success: true,
            messages: formattedMessages
        });
    } catch (error) {
        console.error('Error getting chat messages:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Mark chat as read
app.post('/chats/mark-read', async (req, res) => {
    if (!isReady) {
        return res.status(400).json({
            success: false,
            message: 'WhatsApp client is not ready'
        });
    }

    const { chatId } = req.body;

    if (!chatId) {
        return res.status(400).json({
            success: false,
            message: 'Chat ID is required'
        });
    }

    try {
        const chat = await client.getChatById(chatId);
        await chat.sendSeen();

        res.json({
            success: true,
            message: 'Chat marked as read'
        });
    } catch (error) {
        console.error('Error marking chat as read:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

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

        const { phone, message, chatId } = req.body;

        if ((!phone && !chatId) || !message) {
            return res.status(400).json({
                success: false,
                message: 'Phone/chatId and message are required'
            });
        }

        let targetId;

        if (chatId) {
            targetId = chatId;
        } else {
            // Format phone number
            let formattedPhone = phone.replace(/[^0-9]/g, '');
            if (formattedPhone.startsWith('0')) {
                formattedPhone = '62' + formattedPhone.substring(1);
            }
            if (!formattedPhone.startsWith('62')) {
                formattedPhone = '62' + formattedPhone;
            }
            targetId = formattedPhone + '@c.us';
        }

        const sentMessage = await client.sendMessage(targetId, message);

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
    setTimeout(async () => {
        console.log('Calling initializeClient()...');
        try {
            await initializeClient();
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
