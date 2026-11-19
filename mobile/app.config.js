// Konfigurasi tambahan: EXPO_BASE_URL dipakai saat membangun PWA untuk server E-Surat (/m).
// Tanpa variabel ini konfigurasi sama persis dengan app.json.
module.exports = ({ config }) =>
  process.env.EXPO_BASE_URL ? { ...config, experiments: { ...config.experiments, baseUrl: process.env.EXPO_BASE_URL } } : config;
