@echo off
echo Starting AI Chatbot SaaS Services...
echo.

echo [1/4] Starting Reverb WebSocket Server...
start "Reverb WebSocket" cmd /k "cd /d %~dp0 && php artisan reverb:start --host=0.0.0.0 --port=8080 --debug"

echo [2/4] Starting Queue Worker...
start "Queue Worker" cmd /k "cd /d %~dp0 && php artisan queue:work --verbose"

echo [3/4] Starting Ollama AI Service...
start "Ollama AI" cmd /k "ollama serve"

echo.
echo ✅ All services are starting...
echo.
echo 🌐 Website: http://ai-chatbot-saas.test/
echo 🎯 Demo: http://ai-chatbot-saas.test/demo
echo 📡 WebSocket: ws://ai-chatbot-saas.test:8080
echo 🤖 Ollama: http://localhost:11434
echo.
echo Press any key to open the demo page...
pause >nul

start "" "http://ai-chatbot-saas.test/demo"
