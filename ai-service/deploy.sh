#!/bin/bash
# Deployment Script for CodeByTushu AI Service

echo "🚀 Deploying CodeByTushu AI Service..."

# 1. Install Dependencies
echo "📦 Installing npm dependencies..."
npm install --production

# 2. Check for .env file
if [ ! -f .env ]; then
  echo "⚠️ .env file not found! Please create one using .env.example"
  exit 1
fi

# 3. Start or Restart PM2 Process
echo "🔄 Starting/Restarting PM2 Service..."
pm2 start ecosystem.config.js --env production
pm2 save

echo "✅ Deployment Successful!"
pm2 status
