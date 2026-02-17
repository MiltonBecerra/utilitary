const log = require('electron-log/main');

log.initialize();
log.transports.file.level = 'info';
log.transports.console.level = 'info';

module.exports = log;
