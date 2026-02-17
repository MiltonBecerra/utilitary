const { runPlazaVeaFill } = require('./plazavea-fill-cart');

class AgentService {
    constructor(configStore, logger, notify) {
        this.configStore = configStore;
        this.logger = logger;
        this.notify = notify;
        this.timer = null;
        this.running = false;
        this.busy = false;
    }

    start() {
        if (this.running) {
            return;
        }

        this.running = true;
        this.logger.info('SMC Agent started');
        this.tick();
    }

    stop() {
        this.running = false;
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }
        this.logger.info('SMC Agent stopped');
    }

    isRunning() {
        return this.running;
    }

    async tick() {
        if (!this.running) {
            return;
        }

        const config = this.configStore.get();
        const intervalMs = Math.max(3, Number(config.pollSeconds || 8)) * 1000;

        if (!config.apiToken) {
            this.logger.info('SMC Agent waiting token. Edit config.json to set apiToken');
            this.timer = setTimeout(() => this.tick(), intervalMs);
            return;
        }

        if (this.busy) {
            this.timer = setTimeout(() => this.tick(), intervalMs);
            return;
        }

        this.busy = true;
        try {
            const job = await this.fetchNextJob(config);
            if (job) {
                await this.handleJob(config, job);
            }
        } catch (error) {
            this.logger.error('Tick error', error);
        } finally {
            this.busy = false;
            this.timer = setTimeout(() => this.tick(), intervalMs);
        }
    }

    async fetchNextJob(config) {
        const url = `${config.serverBaseUrl.replace(/\/$/, '')}/api/smc/agent/jobs/next?device_id=${encodeURIComponent(config.deviceId)}`;
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${config.apiToken}`,
            },
        });

        if (response.status === 404) {
            this.logger.warn('Jobs endpoint not found yet. Backend integration pending.');
            return null;
        }

        if (!response.ok) {
            const body = await response.text();
            throw new Error(`fetchNextJob failed: ${response.status} ${body}`);
        }

        const payload = await response.json();
        return payload?.job || null;
    }

    async reportStatus(config, jobId, stage, payload = {}) {
        const url = `${config.serverBaseUrl.replace(/\/$/, '')}/api/smc/agent/jobs/${jobId}/status`;
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${config.apiToken}`,
            },
            body: JSON.stringify({
                device_id: config.deviceId,
                stage,
                ...payload,
            }),
        });

        if (!response.ok && response.status !== 404) {
            const body = await response.text();
            this.logger.warn(`reportStatus failed ${response.status}: ${body}`);
        }
    }

    async handleJob(config, job) {
        const jobId = job.id;
        const store = String(job.store || '').toLowerCase();
        const items = Array.isArray(job.items) ? job.items : [];

        this.logger.info(`Handling job ${jobId} for store ${store} (${items.length} items)`);
        this.notify('SMC Agent', `Iniciando llenado de carrito (${store})`);
        await this.reportStatus(config, jobId, 'started', { total_items: items.length });

        try {
            if (store !== 'plaza_vea') {
                throw new Error(`Store no soportada por ahora: ${store}`);
            }

            const result = await runPlazaVeaFill({
                items,
                headless: Boolean(config.headless),
                keepOpen: Boolean(config.keepBrowserOpen),
                onProgress: async (current, total, title) => {
                    await this.reportStatus(config, jobId, 'progress', {
                        current,
                        total,
                        title,
                    });
                },
            });

            await this.reportStatus(config, jobId, 'completed', {
                added: result.added,
                failed: result.failed,
            });

            this.notify('SMC Agent', `Proceso terminado. Agregados ${result.added.length}, fallidos ${result.failed.length}`);
        } catch (error) {
            this.logger.error(`Job ${jobId} failed`, error);
            await this.reportStatus(config, jobId, 'failed', {
                error: error?.message || 'Error inesperado',
            });
            this.notify('SMC Agent', `Error en job ${jobId}: ${error?.message || 'desconocido'}`);
        }
    }
}

module.exports = {
    AgentService,
};
