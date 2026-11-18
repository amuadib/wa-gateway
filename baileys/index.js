const path = require("path");
const fs = require("fs");
const https = require("https");

// Load shared Laravel root .env (fallback to local .env if present)
require("dotenv").config({ path: path.resolve(__dirname, "../.env") });
if (fs.existsSync(path.resolve(__dirname, ".env"))) {
  require("dotenv").config({
    path: path.resolve(__dirname, ".env"),
    override: true,
  });
}

const express = require("express");
const qrcode = require("qrcode");
const axios = require("axios");
const pLimit = require("p-limit");
const {
  default: makeWASocket,
  Browsers,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  DisconnectReason,
} = require("@whiskeysockets/baileys");

// ===== Configuration (Unified with Laravel .env) =====
function resolvePort() {
  if (process.env.BAILEYS_V7_PORT) return Number(process.env.BAILEYS_V7_PORT);
  if (process.env.PORT) return Number(process.env.PORT);
  if (process.env.BAILEYS_V7_API_URL) {
    try {
      const parsed = new URL(process.env.BAILEYS_V7_API_URL);
      if (parsed.port) return Number(parsed.port);
    } catch (_) {}
  }
  return 3000;
}

const PORT = resolvePort();
const HOST = process.env.BAILEYS_V7_HOST || "127.0.0.1";
const API_TOKEN = process.env.BAILEYS_V7_API_KEY || process.env.API_TOKEN || "";
const APP_NAME = process.env.APP_NAME || "WA-Gateway";
const WEBHOOK_URL =
  process.env.BAILEYS_V7_WEBHOOK_URL ||
  process.env.WEBHOOK_URL ||
  (process.env.APP_URL
    ? `${process.env.APP_URL.replace(/\/+$/, "")}/webhook/baileys-v7`
    : null);
const WEBHOOK_INSECURE =
  String(process.env.BAILEYS_V7_WEBHOOK_INSECURE ?? "false") === "true";

const SESSIONS_DIR = path.resolve(
  __dirname,
  process.env.BAILEYS_V7_SESSIONS_DIR || "../storage/app/baileys-sessions"
);
const WEBHOOK_LOG_DIR = path.resolve(
  __dirname,
  process.env.WEBHOOK_LOG_DIR || "../storage/logs/baileys"
);

if (!fs.existsSync(SESSIONS_DIR)) {
  fs.mkdirSync(SESSIONS_DIR, { recursive: true });
}

const httpsAgent = WEBHOOK_INSECURE
  ? new https.Agent({ rejectUnauthorized: false })
  : undefined;

const mask = (s) =>
  typeof s === "string" ? s.replace(/(\d{3})\d+(\d{3})/, "$1******$2") : "";

function isValidSessionId(id) {
  return (
    typeof id === "string" &&
    id.trim().length > 0 &&
    !id.includes("..") &&
    !id.includes("/") &&
    !id.includes("\\")
  );
}

function getSessionPath(id) {
  return path.join(SESSIONS_DIR, id);
}

// ===== Express App =====
const app = express();
app.use(express.json());

// Health endpoint (for PM2 / Laravel monitoring)
app.get("/health", (req, res) => {
  const activeIds = Object.keys(clients);
  const connectedIds = activeIds.filter((id) => Boolean(status[id]));
  res.json({
    status: true,
    service: "wa-gateway-baileys",
    uptime: Math.floor(process.uptime()),
    webhookUrl: WEBHOOK_URL,
    sessionsDir: SESSIONS_DIR,
    activeSessions: activeIds.length,
    connectedSessions: connectedIds.length,
    connectedIds,
  });
});

// Bearer token middleware for /api/*
app.use((req, res, next) => {
  if (req.path.startsWith("/api")) {
    if (!API_TOKEN) {
      return res
        .status(500)
        .json({ status: false, error: "BAILEYS_V7_API_KEY not configured" });
    }
    if (req.headers.authorization !== `Bearer ${API_TOKEN}`) {
      return res.status(401).json({ status: false, error: "Unauthorized" });
    }
  }
  next();
});

const clients = {};
const qrCodes = {};
const status = {};
const accountsInfo = {};
const sendQueues = {};

// ===== Webhook Helper =====
async function sendWebhook(data) {
  if (!WEBHOOK_URL) {
    console.error("Failed to send webhook: WEBHOOK_URL not defined");
    return false;
  }

  const retries = Number(process.env.WEBHOOK_RETRIES ?? 3);
  const baseBackoff = Number(process.env.WEBHOOK_BACKOFF_MS ?? 1000);
  const webhookLogEnabled =
    String(process.env.WEBHOOK_LOG_ENABLED ?? "true") === "true";

  let logFile = null;
  if (webhookLogEnabled) {
    const today = new Date().toISOString().slice(0, 10);
    logFile = path.join(WEBHOOK_LOG_DIR, `webhook-${today}.log`);
    try {
      fs.mkdirSync(WEBHOOK_LOG_DIR, { recursive: true });
    } catch (_) {}
  }

  const entryBase = {
    time: new Date().toISOString(),
    url: WEBHOOK_URL,
    payload: data,
  };

  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      const res = await axios({
        method: "POST",
        url: WEBHOOK_URL,
        data,
        timeout: 10000,
        httpsAgent,
      });

      if (webhookLogEnabled && logFile) {
        const logEntry = {
          ...entryBase,
          attempt,
          status: res.status,
          responseData: res.data,
        };
        try {
          fs.appendFileSync(logFile, JSON.stringify(logEntry) + "\n");
        } catch (_) {}
      }

      console.log(`Webhook [${data.event}] sent to ${WEBHOOK_URL} (attempt ${attempt})`);
      return true;
    } catch (err) {
      const errMsg = err?.response
        ? `status ${err.response.status}`
        : err?.message;

      if (webhookLogEnabled && logFile) {
        const logEntry = {
          ...entryBase,
          attempt,
          error: errMsg,
        };
        try {
          fs.appendFileSync(logFile, JSON.stringify(logEntry) + "\n");
        } catch (_) {}
      }

      if (attempt < retries) {
        const wait = baseBackoff * Math.pow(2, attempt - 1);
        console.warn(
          `Webhook [${data.event}] failed (attempt ${attempt}), retrying in ${wait}ms:`,
          errMsg
        );
        await new Promise((r) => setTimeout(r, wait));
      } else {
        console.error(
          `Failed to send webhook [${data.event}] after ${retries} attempts:`,
          errMsg
        );
      }
    }
  }
  return false;
}

// ===== Per-Account Send Queue (concurrency 1 + 700ms delay) =====
function ensureQueue(id) {
  if (!sendQueues[id]) {
    const limit = pLimit(1);
    sendQueues[id] = {
      limit,
      lastPromise: Promise.resolve(),
    };
  }
  return sendQueues[id];
}

async function enqueueSend(id, fn) {
  const q = ensureQueue(id);
  const run = q.lastPromise.catch(() => {}).then(() =>
    q.limit(async () => {
      const res = await fn();
      await new Promise((r) => setTimeout(r, 700));
      return res;
    })
  );
  q.lastPromise = run;
  return run;
}

// ===== Baileys Client Lifecycle =====
async function createClient(id) {
  const authPath = getSessionPath(id);
  if (!fs.existsSync(authPath)) {
    fs.mkdirSync(authPath, { recursive: true });
  }

  const { state, saveCreds } = await useMultiFileAuthState(authPath);
  const { version } = await fetchLatestBaileysVersion();

  const sock = makeWASocket({
    version,
    auth: state,
    printQRInTerminal: false,
    syncFullHistory: false,
    browser: Browsers.macOS(APP_NAME),
  });

  clients[id] = sock;
  status[id] = false;
  qrCodes[id] = null;

  sock.ev.on("connection.update", async (update) => {
    const { connection, qr, lastDisconnect } = update;

    if (qr) {
      try {
        const qrBase64 = await qrcode.toDataURL(qr);
        qrCodes[id] = qrBase64;
        console.log(`QR for ${id} ready`);
        await sendWebhook({
          sessionId: id,
          event: "qrcode.updated",
          data: {
            qr: qrBase64,
            status: "qr",
          },
          time: new Date().toISOString(),
        });
      } catch (e) {
        console.error(e);
      }
    }

    if (connection === "open") {
      status[id] = true;
      qrCodes[id] = null;
      try {
        const user = sock.user || {};
        const number = user.id ? user.id.split(":")[0] : "";
        accountsInfo[id] = {
          id,
          number,
          name: user.name || "",
          connectedAt: new Date().toISOString(),
          status: "connected",
        };
        await sendWebhook({
          sessionId: id,
          event: "connection.update",
          data: accountsInfo[id],
          time: new Date().toISOString(),
        });
        console.log(`Account ${id} connected (${mask(number)})`);
      } catch (e) {
        console.error(e);
      }
    }

    if (connection === "close") {
      status[id] = false;
      const reason = lastDisconnect?.error?.output?.statusCode;
      if (reason && reason !== DisconnectReason.loggedOut) {
        console.log(`Reconnecting ${id} (reason: ${reason})...`);
        setTimeout(() => createClient(id), 2000);
      } else {
        console.log(`Session ${id} logged out`);
        try {
          fs.rmSync(authPath, { recursive: true, force: true });
        } catch (_) {}
        delete clients[id];
        delete status[id];
        delete accountsInfo[id];
        qrCodes[id] = null;
        await sendWebhook({
          sessionId: id,
          event: "connection.update",
          data: {
            status: "disconnected",
          },
          time: new Date().toISOString(),
        });
      }
    }
  });

  // Delivery / read status updates for sent messages
  sock.ev.on("messages.update", async (updates) => {
    try {
      for (const item of updates) {
        if (!item?.key?.id || item?.update?.status == null) continue;
        await sendWebhook({
          sessionId: id,
          event: "messages.update",
          data: {
            messages: {
              id: item.key.id,
              status: item.update.status,
            },
          },
          time: new Date().toISOString(),
        });
      }
    } catch (e) {
      console.error(e);
    }
  });

  // Incoming messages
  sock.ev.on("messages.upsert", async ({ messages }) => {
    try {
      const msg = messages[0];
      if (!msg || msg.key.fromMe) return;
      const text =
        msg.message?.conversation ||
        msg.message?.extendedTextMessage?.text ||
        null;

      await sendWebhook({
        sessionId: id,
        event: "messages.upsert",
        data: {
          messages: {
            id: msg.key.id,
            from: msg.key.remoteJid,
            text,
            status: "received",
          },
        },
        timestamp: msg.messageTimestamp,
      });
    } catch (e) {
      console.error(e);
    }
  });

  sock.ev.on("creds.update", saveCreds);

  return sock;
}

async function ensureClient(id) {
  if (clients[id]) return clients[id];
  return await createClient(id);
}

// Auto-restore saved sessions on startup
async function restoreSavedSessions() {
  if (!fs.existsSync(SESSIONS_DIR)) return;
  const entries = fs.readdirSync(SESSIONS_DIR, { withFileTypes: true });
  for (const entry of entries) {
    if (!entry.isDirectory()) continue;
    const id = entry.name;
    const credsFile = path.join(SESSIONS_DIR, id, "creds.json");
    if (fs.existsSync(credsFile)) {
      console.log(`Restoring saved session: ${id}`);
      try {
        await createClient(id);
        await new Promise((r) => setTimeout(r, 500));
      } catch (e) {
        console.error(`Failed to restore session ${id}:`, e.message);
      }
    }
  }
}

// ===== API Routes =====
app.post("/api/sessions/create", async (req, res) => {
  const { id } = req.body;
  if (!isValidSessionId(id)) {
    return res.status(400).json({ status: false, message: "valid id required" });
  }
  try {
    await ensureClient(id);
    res.json({
      status: true,
      message: `Account ${id} created. Scan QR to connect.`,
    });
  } catch (e) {
    res.status(500).json({ status: false, message: e.toString() });
  }
});

app.get("/api/sessions/:id/validate", async (req, res) => {
  const id = req.params.id;
  if (!isValidSessionId(id)) {
    return res.status(400).json({ status: false, message: "valid id required" });
  }
  try {
    const authPath = getSessionPath(id);
    const sessionExists = fs.existsSync(authPath);
    const isConnected = Boolean(clients[id] && status[id]);
    const hasCreds =
      sessionExists && fs.existsSync(path.join(authPath, "creds.json"));

    let valid = false;
    let message = "session not found";
    if (isConnected) {
      valid = true;
      message = "connected";
    } else if (hasCreds) {
      valid = true;
      message = "credentials present (not connected)";
    } else if (sessionExists) {
      valid = false;
      message = "session folder exists but no credentials found";
    }

    res.json({
      status: true,
      event: "connection.update",
      data: {
        status: isConnected ? "connected" : "disconnected",
      },
      sessionId: id,
      valid,
      connected: isConnected,
      hasCredentials: Boolean(hasCreds),
      info: accountsInfo[id] || null,
      message,
    });
  } catch (e) {
    res.status(500).json({ status: false, message: e.toString() });
  }
});

app.get("/api/sessions/:id/reconnect", async (req, res) => {
  const id = req.params.id;
  if (!isValidSessionId(id)) {
    return res.status(400).json({ status: false, message: "valid id required" });
  }
  try {
    const authPath = getSessionPath(id);
    const hasCreds = fs.existsSync(path.join(authPath, "creds.json"));
    if (!hasCreds) {
      // Allow re-creating session cleanly so a new QR can be scanned
      await ensureClient(id);
      return res.json({
        status: true,
        id,
        connected: false,
        message: "no saved credentials, new QR session initiated",
      });
    }

    if (clients[id] && status[id]) {
      return res.json({
        status: true,
        id,
        connected: true,
        message: "already connected",
      });
    }

    await ensureClient(id);

    const timeout = Number(process.env.SESSION_RECONNECT_WAIT_MS ?? 10000);
    const interval = 500;
    let waited = 0;
    while (waited < timeout) {
      if (status[id]) break;
      await new Promise((r) => setTimeout(r, interval));
      waited += interval;
    }

    const connected = Boolean(status[id]);
    return res.json({
      status: true,
      id,
      connected,
      message: connected
        ? "reconnected"
        : "reconnect initiated (not yet connected)",
    });
  } catch (e) {
    res.status(500).json({ status: false, message: e.toString() });
  }
});

app.delete("/api/sessions/:id", async (req, res) => {
  const id = req.params.id;
  if (!isValidSessionId(id)) {
    return res.status(400).json({ status: false, message: "valid id required" });
  }
  try {
    const authPath = getSessionPath(id);

    if (clients[id]) {
      try {
        if (typeof clients[id].logout === "function") {
          await clients[id].logout();
        } else if (typeof clients[id].end === "function") {
          clients[id].end();
        } else if (clients[id].ws && typeof clients[id].ws.close === "function") {
          clients[id].ws.close();
        }
      } catch (_) {}
      delete clients[id];
      delete status[id];
      delete accountsInfo[id];
      qrCodes[id] = null;
    }

    try {
      if (fs.existsSync(authPath)) {
        fs.rmSync(authPath, { recursive: true, force: true });
      }
    } catch (_) {}

    return res.json({ status: true, id, message: "session deleted" });
  } catch (e) {
    res.status(500).json({ status: false, message: e.toString() });
  }
});

app.get("/api/accounts", (req, res) => {
  try {
    const ids = new Set(Object.keys(clients));
    if (fs.existsSync(SESSIONS_DIR)) {
      for (const entry of fs.readdirSync(SESSIONS_DIR, { withFileTypes: true })) {
        if (entry.isDirectory()) ids.add(entry.name);
      }
    }
    const list = Array.from(ids).map((id) => ({
      id,
      name: accountsInfo[id]?.name || id,
      number: accountsInfo[id]?.number ? mask(accountsInfo[id].number) : "",
      status: status[id] ? "online" : "offline",
      connectedAt: accountsInfo[id]?.connectedAt || null,
    }));
    res.json({ status: true, data: list });
  } catch (e) {
    res.status(500).json({ status: false, message: e.toString() });
  }
});

app.post("/api/message/send", async (req, res) => {
  const { id, number, message } = req.body;
  if (!isValidSessionId(id) || !number || !message) {
    return res
      .status(400)
      .json({ status: false, message: "id, number, message required" });
  }

  if (!clients[id] || !status[id]) {
    const authPath = getSessionPath(id);
    const hasCreds = fs.existsSync(path.join(authPath, "creds.json"));
    if (!hasCreds) {
      return res
        .status(503)
        .json({ status: false, message: `Account ${id} not connected` });
    }

    try {
      await ensureClient(id);
      const timeout = Number(process.env.SEND_WAIT_FOR_RECONNECT_MS ?? 8000);
      const interval = 500;
      let waited = 0;
      while (waited < timeout) {
        if (status[id]) break;
        await new Promise((r) => setTimeout(r, interval));
        waited += interval;
      }
      if (!status[id]) {
        return res.status(503).json({
          status: false,
          message: `Account ${id} not connected after reconnect attempt`,
        });
      }
    } catch (e) {
      return res.status(500).json({ status: false, message: e.toString() });
    }
  }

  try {
    const sent = await enqueueSend(id, async () => {
      return clients[id].sendMessage(number + "@s.whatsapp.net", {
        text: message,
      });
    });
    res.json({
      status: true,
      message: "Message sent",
      key: sent?.key || null,
    });
  } catch (e) {
    res.status(500).json({ status: false, message: e.toString() });
  }
});

app.post("/api/broadcast", async (req, res) => {
  const { id, numbers, message } = req.body;
  if (!isValidSessionId(id) || !Array.isArray(numbers) || !message) {
    return res
      .status(400)
      .json({ status: false, message: "id, numbers[], message required" });
  }
  if (!clients[id] || !status[id]) {
    return res
      .status(503)
      .json({ status: false, message: `Account ${id} not connected` });
  }

  let success = 0;
  let fail = 0;
  for (const no of numbers) {
    try {
      await enqueueSend(id, async () =>
        clients[id].sendMessage(no + "@s.whatsapp.net", { text: message })
      );
      success++;
    } catch (_) {
      fail++;
    }
  }
  res.json({ status: true, success, fail });
});

app.get("/api/webhook/test", async (req, res) => {
  const ok = await sendWebhook({
    event: "webhook.test",
    data: { message: "This is a test webhook from wa-gateway-baileys" },
    time: new Date().toISOString(),
  });
  return res.json({
    success: ok,
    message: ok ? "Webhook delivered" : "Webhook delivery failed",
  });
});

// ===== Start Server & Graceful Shutdown =====
const server = app.listen(PORT, HOST, () => {
  console.log(`WA-Gateway Baileys v7 running on http://${HOST}:${PORT}`);
  console.log(`Webhook target : ${WEBHOOK_URL || "(not configured)"}`);
  console.log(`Sessions dir   : ${SESSIONS_DIR}`);
  restoreSavedSessions();
});

function shutdown(signal) {
  console.log(`Received ${signal}, shutting down gracefully...`);
  server.close(() => {
    process.exit(0);
  });
  setTimeout(() => process.exit(0), 5000).unref();
}

process.on("SIGINT", () => shutdown("SIGINT"));
process.on("SIGTERM", () => shutdown("SIGTERM"));
