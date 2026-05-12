(function () {
  const supportRecognition = "SpeechRecognition" in window || "webkitSpeechRecognition" in window;
  const recognitionClass = window.SpeechRecognition || window.webkitSpeechRecognition;

  const micBtn = document.getElementById("mic-btn");
  const statusEl = document.getElementById("voice-status");
  const heardEl = document.getElementById("heard-text");
  const replyEl = document.getElementById("assistant-reply");

  if (!supportRecognition || !recognitionClass) {
    statusEl.textContent = "Web Speech API non supportée par ce navigateur.";
    micBtn.disabled = true;
    return;
  }

  const recognition = new recognitionClass();
  recognition.lang = "fr-FR";
  recognition.interimResults = false;
  recognition.continuous = false;

  let listening = false;

  micBtn.addEventListener("click", () => {
    if (listening) {
      recognition.stop();
      return;
    }

    replyEl.textContent = "";
    heardEl.textContent = "";
    statusEl.textContent = "Ecoute en cours...";
    micBtn.textContent = "Stop";
    listening = true;
    recognition.start();
  });

  recognition.onresult = async (event) => {
    const text = event.results?.[0]?.[0]?.transcript?.trim() || "";
    heardEl.textContent = text || "(rien reconnu)";
    if (!text) {
      statusEl.textContent = "Aucun texte reconnu.";
      return;
    }

    statusEl.textContent = "Envoi au serveur...";
    try {
      const response = await fetch("api/voice-chat.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ text }),
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.error || "Erreur serveur");
      }

      const reply = (data.reply || "").trim();
      replyEl.textContent = reply || "(réponse vide)";
      statusEl.textContent = "Réponse reçue.";

      if (reply) {
        const utterance = new SpeechSynthesisUtterance(reply);
        utterance.lang = "fr-FR";
        utterance.rate = 1;
        utterance.pitch = 1;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utterance);
      }
    } catch (error) {
      statusEl.textContent = "Erreur: " + error.message;
    }
  };

  recognition.onerror = (event) => {
    statusEl.textContent = "Erreur micro: " + (event.error || "unknown");
  };

  recognition.onend = () => {
    listening = false;
    micBtn.textContent = "Parler";
    if (statusEl.textContent === "Ecoute en cours...") {
      statusEl.textContent = "Ecoute arrêtée.";
    }
  };
})();
