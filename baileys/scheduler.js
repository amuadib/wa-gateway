const path = require("path");
const { spawn } = require("child_process");

const ROOT_DIR = path.resolve(__dirname, "..");
const PHP_BIN = process.env.PHP_BIN || "php";

let isSending = false;
let lastDeleteDate = null;

function runArtisan(command, onDone) {
  const child = spawn(PHP_BIN, ["artisan", command], {
    cwd: ROOT_DIR,
    windowsHide: true,
    shell: false,
    stdio: ["ignore", "pipe", "pipe"],
  });

  child.stdout.on("data", (chunk) => {
    const text = chunk.toString().trim();
    if (text) console.log(`[${command}] ${text}`);
  });

  child.stderr.on("data", (chunk) => {
    const text = chunk.toString().trim();
    if (text) console.error(`[${command}] ${text}`);
  });

  child.on("error", (err) => {
    console.error(`[${command}] spawn error:`, err.message);
    if (onDone) onDone();
  });

  child.on("close", (code) => {
    if (code !== 0) {
      console.error(`[${command}] exited with code ${code}`);
    }
    if (onDone) onDone();
  });
}

function tick() {
  // 1. message:send-to-api every minute (prevent overlapping runs)
  if (!isSending) {
    isSending = true;
    runArtisan("message:send-to-api", () => {
      isSending = false;
    });
  }

  // 2. message:delete daily at 02:25
  const now = new Date();
  const today = now.toISOString().slice(0, 10);
  if (now.getHours() === 2 && now.getMinutes() === 25 && lastDeleteDate !== today) {
    lastDeleteDate = today;
    runArtisan("message:delete");
  }
}

console.log(
  `WA-Gateway Scheduler started (Windows-safe, no cmd.exe popup). Root: ${ROOT_DIR}`
);

// Run immediately once, then every 60 seconds
tick();
const timer = setInterval(tick, 60 * 1000);

function shutdown(signal) {
  console.log(`Scheduler received ${signal}, stopping...`);
  clearInterval(timer);
  process.exit(0);
}

process.on("SIGINT", () => shutdown("SIGINT"));
process.on("SIGTERM", () => shutdown("SIGTERM"));
