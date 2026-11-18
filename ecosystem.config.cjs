const path = require("path");

module.exports = {
  apps: [
    {
      name: "wa-baileys",
      script: path.join(__dirname, "baileys", "index.js"),
      cwd: __dirname,
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      windowsHide: true,
      max_memory_restart: "500M",
      out_file: path.join(__dirname, "storage", "logs", "pm2-baileys-out.log"),
      error_file: path.join(__dirname, "storage", "logs", "pm2-baileys-error.log"),
      log_date_format: "YYYY-MM-DD HH:mm:ss",
      env: {
        NODE_ENV: "development",
      },
      env_production: {
        NODE_ENV: "production",
      },
    },
    {
      name: "wa-scheduler",
      script: path.join(__dirname, "baileys", "scheduler.js"),
      cwd: __dirname,
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      windowsHide: true,
      max_memory_restart: "150M",
      out_file: path.join(__dirname, "storage", "logs", "pm2-scheduler-out.log"),
      error_file: path.join(__dirname, "storage", "logs", "pm2-scheduler-error.log"),
      log_date_format: "YYYY-MM-DD HH:mm:ss",
    },
  ],
};
