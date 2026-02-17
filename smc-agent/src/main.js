const path = require('path');
const { app, Tray, Menu, nativeImage, shell, Notification } = require('electron');
const { ConfigStore } = require('./config-store');
const { AgentService } = require('./agent-service');
const log = require('./logger');

let tray = null;
let configStore = null;
let agentService = null;

const notify = (title, body) => {
    try {
        const notification = new Notification({ title, body });
        notification.show();
    } catch (error) {
        log.warn('Notification failed', error?.message || error);
    }
};

const iconData = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABhUlEQVR4AbWTP0sDQRTHv7M3d2JnY2PjA0WQECxM7GwstLS0tLQw8Q8QKxsbGxsbGwtrKys7A0tbS0sDMxMFgYjG7zbsxV/JB8fHfved7/vefM8YhX7n8FQHnS8mFKr3l0zJq6k7vE1w1x6D7NQy8VclvA2s5sU3Yabku9Xh2WQ7o4l0wXQ9LkR4D8I0p9f3uJ0o2T6QxY2P8h5B+Q8lQn7IYh6Y8+V2Sx8J1fJ2Lrb6Q3kKQF9hXzO00w8xWQq2r5SgK9x7n2fP1b8I3h8mI4C7QW8WJdM8w63x3XwQeTaD7P0i2k5yR3BsvQd7YcVd7mJ7y9bE2LwqQUPbY3x8F5v0hQeD2kq8h2cY7gY6V7f8v4QmHn3y8uGm7SgQvWQx8c0X2nW2YQ+v8A5m8Y6fJj9l6VxM6hHh8zV0h+W4rQHnq2lV0wD1Y1WJvG+S4L0Qq0k8w5n8O6nQq9yYdF2wV5q8Y2J2s0fQv2m9kD4V7w9G6cL8Y6J7I0pJmJ4B+QAAAABJRU5ErkJggg==';

const buildMenu = () => {
    const isRunning = agentService?.isRunning() || false;
    const config = configStore.get();
    return Menu.buildFromTemplate([
        {
            label: isRunning ? 'Detener agente' : 'Iniciar agente',
            click: () => {
                if (agentService.isRunning()) {
                    agentService.stop();
                } else {
                    agentService.start();
                }
                tray.setContextMenu(buildMenu());
            },
        },
        { type: 'separator' },
        {
            label: `Servidor: ${config.serverBaseUrl}`,
            enabled: false,
        },
        {
            label: 'Abrir archivo de configuracion',
            click: async () => {
                const target = configStore.getConfigPath();
                await shell.openPath(target);
            },
        },
        {
            label: 'Abrir carpeta de logs',
            click: async () => {
                const folder = path.dirname(log.transports.file.getFile().path);
                await shell.openPath(folder);
            },
        },
        { type: 'separator' },
        {
            label: 'Salir',
            click: () => {
                app.quit();
            },
        },
    ]);
};

const createTray = () => {
    const icon = nativeImage.createFromDataURL(`data:image/png;base64,${iconData}`);
    tray = new Tray(icon.resize({ width: 16, height: 16 }));
    tray.setToolTip('SMC Agent');
    tray.setContextMenu(buildMenu());
    tray.on('click', () => tray.popUpContextMenu(buildMenu()));
};

app.whenReady().then(() => {
    app.setAppUserModelId('com.utilitary.smcagent');
    configStore = new ConfigStore(app);
    configStore.get();

    agentService = new AgentService(configStore, log, notify);

    createTray();
    agentService.start();
    log.info('SMC Agent ready');
});

app.on('window-all-closed', (event) => {
    event.preventDefault();
});

app.on('before-quit', () => {
    if (agentService) {
        agentService.stop();
    }
});
