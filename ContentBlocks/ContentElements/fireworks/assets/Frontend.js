$(document).ready(function(){
    const wrapper = document.querySelector('.jve_fireworks-videoContainer');
    const canvas = document.querySelector('.jve_fireworks-canvas');
    const ctx = canvas.getContext('2d');
    const flash = document.querySelector('.jve_fireworks-flash');
    console.log('Fireworks script loaded');
    console.log(wrapper.height, wrapper.width);

    canvas.width = wrapper.clientWidth;
    canvas.height = wrapper.clientHeight;

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

    }, 10000);
    // Video-Container nach 15 Sekunden ausblenden
    setTimeout(() => {
        wrapper.style.transition = 'height 4s, opacity 3s';
        wrapper.style.height = '0';
        wrapper.style.opacity = '0';
        }, 15000);
});


