<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>AI Chat - Markdown Pro</title>
    
    <!-- افزودن کتابخانه‌های مارک‌داون و هایلایت کد -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f0f2f5; margin: 0; direction: rtl; }
        .container { max-width: 800px; margin: 30px auto; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 90vh; }
        #chat { flex: 1; overflow-y: auto; padding: 20px; }
        .message { margin-bottom: 20px; display: flex; flex-direction: column; }
        .user { align-items: flex-start; } /* پیام کاربر سمت راست (با توجه به RTL) */
        .ai { align-items: flex-end; }  /* پیام هوش مصنوعی سمت چپ */
        
        .bubble { 
            display: inline-block; 
            padding: 12px 18px; 
            border-radius: 15px; 
            max-width: 85%; 
            line-height: 1.6;
            word-wrap: break-word;
        }
        .user .bubble { background: #007bff; color: white; border-bottom-right-radius: 2px; direction: rtl; text-align: right; }
        .ai .bubble { background: #f8f9fa; color: #333; border-bottom-left-radius: 2px; border: 1px solid #e0e0e0; direction: rtl; text-align: right; }
        
        /* استایل‌های مخصوص مارک‌داون */
        .bubble pre { background: #2d2d2d; color: #ccc; padding: 10px; border-radius: 8px; overflow-x: auto; direction: ltr; text-align: left; }
        .bubble code { font-family: Consolas, monospace; background: #f0f0f0; padding: 2px 4px; border-radius: 4px; color: #d63384; }
        .bubble pre code { background: none; color: inherit; padding: 0; }
        .bubble ul, .bubble ol { padding-right: 25px; }

        .input-area { display: flex; padding: 15px; border-top: 1px solid #eee; gap: 10px; }
        input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; font-size: 16px; }
        button { padding: 0 25px; border: none; background: #007bff; color: white; border-radius: 8px; cursor: pointer; font-weight: bold; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="container">
    <div id="chat"></div>
    <div class="input-area">
        <input id="messageInput" placeholder="پیام خود را بنویسید..." autocomplete="off" />
        <button onclick="sendMessage()">ارسال</button>
    </div>
</div>

<script>
    const chat = document.getElementById("chat");
    const input = document.getElementById("messageInput");
    let messages = [];

    // تنظیمات Marked برای شناسایی کدها
    marked.setOptions({
        highlight: function(code, lang) {
            return hljs.highlightAuto(code).value;
        },
        breaks: true
    });

    function addMessage(role, text) {
        const wrapper = document.createElement("div");
        wrapper.className = "message " + role;
        const bubble = document.createElement("div");
        bubble.className = "bubble";
        
        // ذخیره متن خام در یک ویژگی دلخواه برای آپدیت راحت‌تر
        bubble.dataset.rawText = text;
        bubble.innerHTML = role === 'ai' ? marked.parse(text) : text;

        wrapper.appendChild(bubble);
        chat.appendChild(wrapper);
        chat.scrollTop = chat.scrollHeight;
        return bubble;
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;
        input.value = "";

        messages.push({ role: "user", content: text });
        addMessage("user", text);

        const aiBubble = addMessage("ai", "..."); // ابتدا یک حالت انتظار نشان می‌دهیم
        let fullAiResponse = "";

        try {
            const response = await fetch("/api/chat/stream", {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "text/event-stream" },
                body: JSON.stringify({ messages: messages })
            });

            const reader = response.body.getReader();
            const decoder = new TextDecoder("utf-8");
            let buffer = "";

            aiBubble.innerHTML = ""; // پاک کردن "..."

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const parts = buffer.split("\n\n");
                buffer = parts.pop();

                for (const part of parts) {
                    if (!part.startsWith("data:")) continue;
                    const data = part.replace("data:", "").trim();

                    if (data === "[DONE]") {
                        messages.push({ role: "assistant", content: fullAiResponse });
                        return;
                    }

                    const json = JSON.parse(data);
                    fullAiResponse += json.content;

                    // تبدیل کل متن جمع‌آوری شده به HTML (مارک‌داون)
                    aiBubble.innerHTML = marked.parse(fullAiResponse);
                    
                    // اسکرول به پایین هنگام تایپ زنده
                    chat.scrollTop = chat.scrollHeight;
                }
            }
        } catch (error) {
            aiBubble.textContent = "خطا در برقراری ارتباط.";
        }
    }

    input.addEventListener("keypress", (e) => { if (e.key === "Enter") sendMessage(); });
</script>

</body>
</html>