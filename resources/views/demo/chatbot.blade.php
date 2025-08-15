<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تجربة الشات بوت المدعوم بـ WebSocket</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Vite Assets -->
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .demo-container {
            padding: 50px 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .demo-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 800px;
            width: 100%;
            margin: 0 20px;
        }
        
        .demo-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .demo-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .demo-header p {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9ff;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .feature-text h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .feature-text p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .demo-controls {
            text-align: center;
            margin: 40px 0;
        }
        
        .demo-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .demo-btn.secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .demo-btn.secondary:hover {
            background: #667eea;
            color: white;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #e8f5e8;
            color: #2d5a2d;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin: 10px 0;
        }
        
        .status-indicator.offline {
            background: #ffeaea;
            color: #d32f2f;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            background: #4caf50;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-indicator.offline .status-dot {
            background: #f44336;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .tech-stack {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            text-align: center;
        }
        
        .tech-badges {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        .tech-badge {
            padding: 8px 16px;
            background: #f1f1f1;
            border-radius: 20px;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-card">
            <div class="demo-header">
                <h1>🤖 تجربة الشات بوت الذكي</h1>
                <p>
                    اختبر قوة الذكاء الاصطناعي مع شات بوت متطور يدعم WebSocket للمحادثة المباشرة.
                    تم تطويره باستخدام Laravel 12 و Laravel Reverb للبث المباشر.
                </p>
                
                <div class="status-indicator" id="connection-status">
                    <span class="status-dot"></span>
                    WebSocket متصل
                </div>
            </div>

            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="feature-text">
                        <h5>استجابة فورية</h5>
                        <p>WebSocket للمحادثة المباشرة</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="feature-text">
                        <h5>ذكاء اصطناعي</h5>
                        <p>مدعوم بـ Ollama AI</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="feature-text">
                        <h5>محادثة طبيعية</h5>
                        <p>يفهم السياق ويتذكر المحادثة</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <div class="feature-text">
                        <h5>الرسائل الصوتية</h5>
                        <p>تحدث بدلاً من الكتابة</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-paperclip"></i>
                    </div>
                    <div class="feature-text">
                        <h5>رفع الملفات</h5>
                        <p>شارك المستندات والصور</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="feature-text">
                        <h5>متجاوب</h5>
                        <p>يعمل على جميع الأجهزة</p>
                    </div>
                </div>
            </div>

            <div class="demo-controls">
                <button class="demo-btn" id="open-chat-btn">
                    <i class="fas fa-comments"></i>
                    ابدأ المحادثة
                </button>
                
                <button class="demo-btn secondary" onclick="window.location.reload()">
                    <i class="fas fa-refresh"></i>
                    إعادة تحميل
                </button>
            </div>

            <div class="tech-stack">
                <h5 style="color: #333; margin-bottom: 15px;">التقنيات المستخدمة</h5>
                <div class="tech-badges">
                    <span class="tech-badge">Laravel 12</span>
                    <span class="tech-badge">Laravel Reverb</span>
                    <span class="tech-badge">WebSocket</span>
                    <span class="tech-badge">Ollama AI</span>
                    <span class="tech-badge">JavaScript ES6</span>
                    <span class="tech-badge">Bootstrap 5</span>
                    <span class="tech-badge">PHP 8.3</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Debug function to check if scripts are loaded
        function checkScriptsLoaded() {
            console.log('=== Script Loading Debug ===');
            console.log('window.Echo available:', typeof window.Echo !== 'undefined');
            console.log('window.ChatbotWidget available:', typeof window.ChatbotWidget !== 'undefined');
            console.log('window.axios available:', typeof window.axios !== 'undefined');
            
            if (typeof window.ChatbotWidget !== 'undefined') {
                console.log('✅ ChatbotWidget is loaded');
            } else {
                console.log('❌ ChatbotWidget is not loaded');
            }
        }

        // Initialize chatbot when page loads
        function initializeChatbot() {
            // Debug check
            checkScriptsLoaded();
            
            if (typeof ChatbotWidget === 'undefined') {
                console.log('ChatbotWidget not loaded yet, retrying...');
                setTimeout(initializeChatbot, 200);
                return;
            }

            console.log('✅ ChatbotWidget loaded successfully, initializing...');
            
            try {
                // Initialize the chatbot widget
                const chatbot = new ChatbotWidget({
                    chatbotId: {{ $chatbot->id }},
                    autoOpen: false,
                    language: 'ar',
                    enableVoice: true,
                    enableFileUpload: true,
                    theme: 'modern'
                });

                console.log('✅ ChatbotWidget initialized successfully');

                // Button to open chat
                const openChatBtn = document.getElementById('open-chat-btn');
                if (openChatBtn) {
                    openChatBtn.addEventListener('click', function() {
                        console.log('Opening chatbot...');
                        chatbot.open();
                    });
                }

                // Monitor WebSocket connection status
                function updateConnectionStatus() {
                    const statusElement = document.getElementById('connection-status');
                    if (!statusElement) return;
                    
                    const isConnected = window.Echo && window.Echo.socketId();
                    
                    if (isConnected) {
                        statusElement.innerHTML = '<span class="status-dot"></span>WebSocket متصل';
                        statusElement.className = 'status-indicator';
                    } else {
                        statusElement.innerHTML = '<span class="status-dot"></span>WebSocket غير متصل';
                    statusElement.className = 'status-indicator offline';
                }
            }

            // Check connection status periodically
            setInterval(updateConnectionStatus, 5000);
            setTimeout(updateConnectionStatus, 1000);
        }

        // Start initialization when DOM is ready
        document.addEventListener('DOMContentLoaded', initializeChatbot);
    </script>
</body>
</html>
