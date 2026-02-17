const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const DEFAULT_CONFIG = {
    serverBaseUrl: 'http://127.0.0.1:8000',
    apiToken: '',
    deviceId: '',
    pollSeconds: 8,
    keepBrowserOpen: true,
    headless: false,
};

class ConfigStore {
    constructor(app) {
        this.app = app;
        this.configPath = path.join(this.app.getPath('userData'), 'config.json');
        this.config = null;
    }

    ensureLoaded() {
        if (this.config) {
            return this.config;
        }

        if (!fs.existsSync(this.configPath)) {
            this.config = {
                ...DEFAULT_CONFIG,
                deviceId: crypto.randomUUID(),
            };
            this.save();
            return this.config;
        }

        try {
            const raw = fs.readFileSync(this.configPath, 'utf-8');
            const parsed = JSON.parse(raw);
            this.config = {
                ...DEFAULT_CONFIG,
                ...parsed,
                deviceId: parsed.deviceId || crypto.randomUUID(),
            };
            this.save();
        } catch (error) {
            this.config = {
                ...DEFAULT_CONFIG,
                deviceId: crypto.randomUUID(),
            };
            this.save();
        }

        return this.config;
    }

    get() {
        return this.ensureLoaded();
    }

    save(nextConfig = null) {
        if (nextConfig) {
            this.config = {
                ...this.ensureLoaded(),
                ...nextConfig,
            };
        }

        fs.mkdirSync(path.dirname(this.configPath), { recursive: true });
        fs.writeFileSync(this.configPath, JSON.stringify(this.config, null, 2));
        return this.config;
    }

    getConfigPath() {
        return this.configPath;
    }
}

module.exports = {
    ConfigStore,
    DEFAULT_CONFIG,
};
