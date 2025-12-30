$(document).ready(function(){
    const canvas = document.querySelector('.jve_fireworks-canvas');
    const ctx = canvas.getContext('2d');
    const flash = document.querySelector('.jve_fireworks-flash');

    canvas.width = 480;
    canvas.height = 854;

    class Particle {
        constructor(x, y, color) {
            this.x = x;
            this.y = y;
            this.color = color;
            this.velocity = {
                x: (Math.random() - 0.5) * 6,
                y: (Math.random() - 0.5) * 6
            };
            this.alpha = 1;
            this.decay = Math.random() * 0.02 + 0.01;
        }

        update() {
            this.velocity.y += 0.1;
            this.x += this.velocity.x;
            this.y += this.velocity.y;
            this.alpha -= this.decay;
        }

        draw() {
            ctx.save();
            ctx.globalAlpha = this.alpha;
            ctx.fillStyle = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, 2, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    }

    let particles = [];

    function createFirework() {
        const x = Math.random() * canvas.width;
        const y = Math.random() * canvas.height * 0.6;
        const colors = ['#FFD700', '#FF6B6B', '#4ECDC4', '#95E1D3', '#F38181', '#AA96DA'];
        const color = colors[Math.floor(Math.random() * colors.length)];

        for (let i = 0; i < 30; i++) {
            particles.push(new Particle(x, y, color));
        }
    }

    function animate() {
        ctx.fillStyle = 'rgba(0, 4, 40, 0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        particles = particles.filter(p => p.alpha > 0);

        particles.forEach(p => {
            p.update();
            p.draw();
        });

        if (Math.random() < 0.03) {
            createFirework();
        }

        requestAnimationFrame(animate);
    }

    animate();

    // Böller-Knall am Ende (nach 10 Sekunden)
    setTimeout(() => {
        // Weißer Blitz
        flash.style.opacity = '1';
        flash.style.transition = 'opacity 0.1s';

        // Knall-Sound simulieren (visuell)
        setTimeout(() => {
            flash.style.transition = 'opacity 0.5s';
            flash.style.opacity = '0';
        }, 100);

        // Große Explosion von Partikeln
        for (let i = 0; i < 5; i++) {
            setTimeout(() => {
                const x = canvas.width / 2;
                const y = canvas.height / 2;
                for (let j = 0; j < 100; j++) {
                    particles.push(new Particle(x, y, '#FFD700'));
                }
            }, i * 50);
        }

        // Versuche einen Ton zu spielen (funktioniert möglicherweise nicht ohne User-Interaktion)
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 100;
        oscillator.type = 'sawtooth';

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    }, 10000);
});


