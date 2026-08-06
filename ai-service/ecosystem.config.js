module.exports = {
  apps: [
    {
      name: 'codebytushu-ai-service',
      script: './src/server.js',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      env: {
        NODE_ENV: 'production',
        PORT: 3001
      }
    }
  ]
};
