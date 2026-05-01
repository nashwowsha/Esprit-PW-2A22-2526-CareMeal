<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assistant Vocal CareMeal</title>
  <style>
    :root {
      --bg: #f4efe6;
      --card: #fffdf8;
      --text: #1f2937;
      --muted: #6b7280;
      --accent: #cc5a20;
      --accent-dark: #a64617;
      --border: #eadbc8;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at 10% 10%, #ffe4c7 0%, transparent 35%),
        radial-gradient(circle at 90% 80%, #ffd7b5 0%, transparent 30%),
        var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .card {
      width: 100%;
      max-width: 720px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    }

    h1 {
      margin: 0 0 8px;
      font-size: 1.6rem;
    }

    p {
      margin: 0 0 20px;
      color: var(--muted);
    }

    .controls {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    button {
      border: 0;
      background: var(--accent);
      color: #fff;
      padding: 12px 20px;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.2s ease;
    }

    button:hover {
      background: var(--accent-dark);
    }

    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .status {
      font-size: 0.95rem;
      color: var(--muted);
    }

    .box {
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 14px;
      margin-top: 12px;
      background: #fff;
    }

    .label {
      font-size: 0.8rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 6px;
    }

    .content {
      white-space: pre-wrap;
      line-height: 1.4;
    }
  </style>
</head>
<body>
  <main class="card">
    <h1>Assistant Vocal CareMeal</h1>
    <p>STT navigateur gratuit -> backend PHP -> Gemini -> TTS navigateur gratuit.</p>

    <div class="controls">
      <button id="mic-btn" type="button">Parler</button>
      <div class="status" id="voice-status">Prêt.</div>
    </div>

    <section class="box">
      <div class="label">Texte reconnu</div>
      <div class="content" id="heard-text"></div>
    </section>

    <section class="box">
      <div class="label">Réponse assistant</div>
      <div class="content" id="assistant-reply"></div>
    </section>
  </main>

  <script src="js/voice-assistant.js"></script>
</body>
</html>
