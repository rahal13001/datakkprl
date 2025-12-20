<div class="krl-chat-widget">
    
    {{-- Chat Window --}}
    @if($isOpen)
    <div class="krl-chat-window">
        {{-- Header --}}
        <div class="krl-chat-header">
            <div class="krl-chat-header-left">
                <div class="krl-avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a3 3 0 0 0-3 3v4a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                        <line x1="12" x2="12" y1="19" y2="22"/>
                    </svg>
                </div>
                <div class="krl-header-info">
                    <span class="krl-header-title">Kawan Ruang Laut</span>
                    <span class="krl-header-status">
                        <span class="krl-status-dot"></span>
                        AI Assistant • Online
                    </span>
                </div>
            </div>
            <div class="krl-header-actions">
                <button wire:click="clearChat" class="krl-clear-btn" title="Hapus percakapan">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
                <button wire:click="toggleChat" class="krl-close-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Messages Area --}}
        <div class="krl-messages">
            @foreach($messages as $msg)
                <div class="krl-message {{ $msg['role'] === 'user' ? 'krl-message-user' : 'krl-message-bot' }}">
                    @if($msg['role'] === 'assistant')
                    <div class="krl-bot-avatar">🤖</div>
                    @endif
                    <div class="krl-bubble {{ $msg['role'] === 'user' ? 'krl-bubble-user' : 'krl-bubble-bot' }}">
                        @if($msg['role'] === 'assistant')
                            {!! Str::markdown($msg['content']) !!}
                        @else
                            {!! nl2br(e($msg['content'])) !!}
                        @endif
                    </div>
                </div>
            @endforeach
            
            {{-- Loading --}}
            <div wire:loading wire:target="sendMessage" class="krl-message krl-message-bot">
                <div class="krl-bot-avatar">🤖</div>
                <div class="krl-bubble krl-bubble-bot krl-typing">
                    <span class="krl-dot"></span>
                    <span class="krl-dot"></span>
                    <span class="krl-dot"></span>
                </div>
            </div>
        </div>
        
        {{-- Input Area --}}
        <div class="krl-input-area">
            {{-- Suggested Questions --}}
            @if(count($messages) <= 1)
            <div class="krl-suggestions">
                @foreach($suggestedQuestions as $suggestion)
                    <button wire:click="askQuestion('{{ $suggestion }}')" class="krl-suggestion-chip">
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>
            @endif
            
            <form wire:submit.prevent="sendMessage" class="krl-input-form">
                <input 
                    wire:model="question" 
                    type="text" 
                    class="krl-input"
                    placeholder="Ketik pesan Anda...">
                <button type="submit" class="krl-send-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </form>
            <span class="krl-powered">Didukung AI • Regulasi KKPRL</span>
        </div>
    </div>
    @endif

    {{-- Toggle Button --}}
    <button wire:click="toggleChat" class="krl-toggle-btn {{ $isOpen ? 'krl-toggle-open' : '' }}">
        @if($isOpen)
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        @else
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        @endif
    </button>

    <style>
        .krl-chat-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .krl-toggle-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0057FF 0%, #0041c2 100%);
            color: white;
            box-shadow: 0 8px 32px rgba(0, 87, 255, 0.4), 0 0 0 0 rgba(0, 87, 255, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: pulse-ring 2s ease-out infinite;
        }

        .krl-toggle-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 40px rgba(0, 87, 255, 0.5);
        }

        .krl-toggle-open {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.4);
            animation: none;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 8px 32px rgba(0, 87, 255, 0.4), 0 0 0 0 rgba(0, 87, 255, 0.4); }
            70% { box-shadow: 0 8px 32px rgba(0, 87, 255, 0.4), 0 0 0 12px rgba(0, 87, 255, 0); }
            100% { box-shadow: 0 8px 32px rgba(0, 87, 255, 0.4), 0 0 0 0 rgba(0, 87, 255, 0); }
        }

        .krl-chat-window {
            position: absolute;
            bottom: 76px;
            right: 0;
            width: 380px;
            max-width: calc(100vw - 48px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 80px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            animation: slideUp 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .krl-chat-header {
            background: linear-gradient(135deg, #0057FF 0%, #0041c2 100%);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .krl-chat-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .krl-avatar {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .krl-header-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .krl-header-title {
            color: white;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: -0.3px;
        }

        .krl-header-status {
            color: rgba(255, 255, 255, 0.85);
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .krl-status-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: blink 2s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .krl-close-btn {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .krl-close-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .krl-messages {
            height: 340px;
            overflow-y: auto;
            padding: 20px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .krl-message {
            display: flex;
            gap: 10px;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .krl-message-user {
            justify-content: flex-end;
        }

        .krl-bot-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .krl-bubble {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.55;
            letter-spacing: -0.1px;
        }

        .krl-bubble-bot {
            background: white;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .krl-bubble-user {
            background: linear-gradient(135deg, #0057FF 0%, #0041c2 100%);
            color: white;
            border-bottom-right-radius: 6px;
        }

        .krl-typing {
            display: flex;
            gap: 5px;
            padding: 16px 20px;
        }

        .krl-dot {
            width: 8px;
            height: 8px;
            background: #94a3b8;
            border-radius: 50%;
            animation: typing 1.4s ease-in-out infinite;
        }

        .krl-dot:nth-child(2) { animation-delay: 0.15s; }
        .krl-dot:nth-child(3) { animation-delay: 0.3s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
        }

        .krl-input-area {
            padding: 16px 20px 20px;
            background: white;
            border-top: 1px solid #f1f5f9;
        }

        .krl-input-form {
            display: flex;
            gap: 10px;
        }

        .krl-input {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .krl-input:focus {
            border-color: #0057FF;
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 87, 255, 0.1);
        }

        .krl-input::placeholder {
            color: #94a3b8;
        }

        .krl-send-btn {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #0057FF 0%, #0041c2 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .krl-send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 87, 255, 0.4);
        }

        .krl-powered {
            display: block;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 12px;
        }

        /* Header Actions */
        .krl-header-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .krl-clear-btn {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .krl-clear-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Suggested Questions */
        .krl-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .krl-suggestion-chip {
            padding: 8px 14px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .krl-suggestion-chip:hover {
            background: #0057FF;
            color: white;
            border-color: #0057FF;
        }

        /* Markdown styling in chat bubbles */
        .krl-bubble-bot p {
            margin: 0 0 8px 0;
        }

        .krl-bubble-bot p:last-child {
            margin-bottom: 0;
        }

        .krl-bubble-bot strong {
            font-weight: 600;
        }

        .krl-bubble-bot ul, .krl-bubble-bot ol {
            margin: 8px 0;
            padding-left: 20px;
        }

        .krl-bubble-bot li {
            margin-bottom: 4px;
        }

        .krl-bubble-bot code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
    </style>
</div>

