import express from 'express';
import qrcode from 'qrcode-terminal';
import pino from 'pino';
import makeWASocket, {
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  DisconnectReason,
} from '@whiskeysockets/baileys';

const PORT = process.env.PORT || 3001;
const API_KEY = process.env.API_KEY || '';

const app = express();
app.use(express.json());

// Simple API key middleware (skip /health)
app.use((req, res, next) => {
  if (req.path === '/health') return next();
  const key = req.header('X-API-KEY') || '';
  if (API_KEY && key !== API_KEY) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
});

let sock = null;
let isReady = false;

async function startSock() {
  const { state, saveCreds } = await useMultiFileAuthState('./baileys_auth');
  const { version } = await fetchLatestBaileysVersion();

  sock = makeWASocket({
    version,
    auth: state,
    logger: pino({ level: 'warn' }),
    printQRInTerminal: false,
    browser: ['SEFTi WA Service', 'Chrome', '1.0.0'],
  });

  sock.ev.on('creds.update', saveCreds);

  // Connection updates
  sock.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect, qr } = update;
    if (qr) {
      console.log('Scan this QR to authenticate WhatsApp:');
      qrcode.generate(qr, { small: true });
    }

    if (connection === 'open') {
      isReady = true;
      console.log('✅ Baileys client is ready.');
    } else if (connection === 'close') {
      isReady = false;
      const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
      console.warn('⚠️ Connection closed. Reconnecting:', shouldReconnect);
      if (shouldReconnect) setTimeout(startSock, 2000);
    }
  });
}

await startSock();

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', ready: isReady });
});

// Endpoint to send OTP message
app.post('/send-otp', async (req, res) => {
  try {
    if (!isReady || !sock) {
      return res.status(503).json({ error: 'WhatsApp client not ready' });
    }

    const { phone, message } = req.body || {};
    if (!phone || !message) {
      return res.status(400).json({ error: 'phone and message are required' });
    }

    // Normalize to WhatsApp JID format for Baileys
    const digits = String(phone).replace(/\D+/g, '');
    const jid = `${digits}@s.whatsapp.net`;

    const sent = await sock.sendMessage(jid, { text: message });
    return res.json({ success: true, id: sent?.key?.id || null });
  } catch (err) {
    console.error('Failed to send OTP', err);
    return res.status(500).json({ error: 'Failed to send OTP' });
  }
});

app.listen(PORT, () => {
  console.log(`🚀 WhatsApp (Baileys) service running on port ${PORT}`);
  if (!API_KEY) {
    console.warn('⚠️ WARNING: API_KEY is empty. Set API_KEY env for basic protection.');
  }
});

