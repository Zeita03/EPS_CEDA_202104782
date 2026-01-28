class CelebrationManager {
    constructor() {
        this.container = null;
    }

    // Crear el contenedor de celebración
    createContainer() {
        this.container = document.createElement('div');
        this.container.className = 'celebration-container';
        document.body.appendChild(this.container);
        return this.container;
    }

    // Crear mensaje de felicitaciones
    createMessage() {
        const message = document.createElement('div');
        message.className = 'celebration-message';
        message.innerHTML = `
            <div style="font-size: 32px; margin-bottom: 15px;">🎉 ¡FELICITACIONES! 🎉</div>
            <div style="font-size: 20px; font-weight: normal; margin-bottom: 20px;">Has culminado exitosamente tu carga de méritos académicos</div>
            <div style="font-size: 14px; background-color: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; border-left: 4px solid #ffd700; margin-top: 20px; line-height: 1.4;">
                <strong>⚠️ Información Importante:</strong><br>
                Los méritos académicos cargados en el sistema están sujetos aún a evaluación por parte del comité correspondiente.
            </div>
        `;
        return message;
    }

    // Crear confeti
    createConfetti() {
        const confetti = [];
        for (let i = 0; i < 100; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti';
            piece.style.left = Math.random() * 100 + '%';
            piece.style.animationDelay = Math.random() * 3 + 's';
            piece.style.animationDuration = (Math.random() * 3 + 3) + 's';
            confetti.push(piece);
        }
        return confetti;
    }

    // Crear globos
    createBalloons() {
        const balloons = [];
        const colors = ['red', 'blue', 'green', 'yellow', 'purple'];
        
        for (let i = 0; i < 8; i++) {
            const balloon = document.createElement('div');
            balloon.className = `balloon balloon-${colors[i % colors.length]}`;
            balloon.style.left = (Math.random() * 80 + 10) + '%';
            balloon.style.animationDelay = (Math.random() * 2) + 's';
            balloon.style.animationDuration = (Math.random() * 2 + 5) + 's';
            balloons.push(balloon);
        }
        return balloons;
    }

    // Crear estrellas brillantes
    createStars() {
        const stars = [];
        for (let i = 0; i < 50; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            stars.push(star);
        }
        return stars;
    }

    // Iniciar celebración
    startCelebration() {
        // Crear contenedor
        const container = this.createContainer();

        // Crear y agregar mensaje
        const message = this.createMessage();
        container.appendChild(message);

        // Crear y agregar confeti
        const confetti = this.createConfetti();
        confetti.forEach(piece => container.appendChild(piece));

        // Crear y agregar globos
        const balloons = this.createBalloons();
        balloons.forEach(balloon => container.appendChild(balloon));

        // Crear y agregar estrellas
        const stars = this.createStars();
        stars.forEach(star => container.appendChild(star));

        // Reproducir sonido de celebración (opcional)
        this.playSound();

        // Remover después de 8 segundos
        setTimeout(() => {
            container.classList.add('celebration-fade-out');
            setTimeout(() => {
                if (container.parentNode) {
                    container.parentNode.removeChild(container);
                }
            }, 1500);
        }, 7000);
    }

    // Reproducir sonido (opcional)
    playSound() {
        try {
            // Crear contexto de audio para sonido de celebración
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Crear una melodía simple de celebración
            const frequencies = [523.25, 659.25, 783.99, 1046.50];
            
            frequencies.forEach((freq, index) => {
                setTimeout(() => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.setValueAtTime(freq, audioContext.currentTime);
                    oscillator.type = 'sine';
                    
                    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                    
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.3);
                }, index * 200);
            });
        } catch (e) {
            // Si no se puede reproducir sonido, continuar sin él
            console.log('No se pudo reproducir el sonido de celebración');
        }
    }
}

// Función global para iniciar celebración
function iniciarCelebracion() {
    const celebration = new CelebrationManager();
    celebration.startCelebration();
}