<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UpTracker') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .bg-grid-pattern {
                background-size: 40px 40px;
                background-image: linear-gradient(to right, rgba(13, 25, 43, 0.02) 1px, transparent 1px),
                                  linear-gradient(to bottom, rgba(13, 25, 43, 0.02) 1px, transparent 1px);
            }
            .glass-card {
                background: rgba(255, 255, 255, 0.65);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow: 0 20px 40px -15px rgba(13, 25, 43, 0.05), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
            }
        </style>
    </head>
    <body class="min-h-full font-sans antialiased text-brand-dark bg-gradient-to-br from-brand-ice via-white to-slate-100 selection:bg-brand-primary selection:text-white flex flex-col justify-between relative overflow-x-hidden">
        
        <!-- Ambient decorative background glows -->
        <div class="fixed inset-0 w-full h-full -z-10 pointer-events-none bg-grid-pattern">
            <!-- Canvas para partículas interactivas -->
            <canvas id="particles-canvas" class="absolute inset-0 w-full h-full opacity-70"></canvas>
            
            <div class="fixed -top-32 -left-32 w-80 h-80 bg-brand-primary/10 rounded-full blur-[100px] pointer-events-none -z-10"></div>
            <div class="fixed -bottom-32 -right-32 w-96 h-96 bg-brand-primary/5 rounded-full blur-[100px] pointer-events-none -z-10"></div>
            <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-gradient-to-tr from-brand-primary/5 to-transparent rounded-full blur-[100px] pointer-events-none -z-10"></div>
        </div>

        <!-- Header -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
            <a href="/" class="group flex items-center gap-3 transition-transform duration-200 hover:scale-[1.02]">
                <div class="w-10 h-10 rounded-xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary shadow-sm shadow-brand-primary/10 group-hover:bg-brand-primary/15 transition-all">
                    <x-application-logo class="w-6 h-6" />
                </div>
                <span class="text-xl font-bold tracking-tight text-brand-dark">UpTracker</span>
            </a>

            <a href="/" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-muted hover:text-brand-primary transition-colors py-2 px-3 rounded-lg hover:bg-white/80 border border-transparent hover:border-slate-200/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Volver al inicio</span>
            </a>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col justify-center items-center px-4 py-8 sm:px-6 relative z-10">
            <div class="w-full sm:max-w-md relative">
                <!-- Decorative element behind the card (glow) -->
                <div class="absolute -inset-1 bg-gradient-to-r from-brand-primary/20 to-cyan-400/20 rounded-[2rem] blur-xl opacity-60"></div>
                
                <div class="glass-card rounded-3xl p-7 sm:p-9 relative z-10">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-5 text-center text-xs text-brand-muted">
            &copy; {{ date('Y') }} UpTracker &bull; Monitoreo de Disponibilidad y Servicios
        </footer>
    </body>

        <!-- Script de red de partículas interactivas -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const canvas = document.getElementById('particles-canvas');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                let width, height;
                let particles = [];
                
                // Configuración
                const particleCount = 205;
                const maxDistance = 150; // Distancia para unir puntos
                const mouseDistance = 220; // Radio de interacción del mouse
                const colorRgb = '0, 60, 130';
                
                let mouse = { x: null, y: null };
                
                function resize() {
                    width = window.innerWidth;
                    height = window.innerHeight;
                    canvas.width = width;
                    canvas.height = height;
                }
                
                window.addEventListener('resize', resize);
                resize();
                
                document.addEventListener('mousemove', (e) => {
                    mouse.x = e.clientX;
                    mouse.y = e.clientY;
                });
                
                document.addEventListener('mouseleave', () => {
                    mouse.x = null;
                    mouse.y = null;
                });

                class Particle {
                    constructor() {
                        this.x = Math.random() * width;
                        this.y = Math.random() * height;
                        this.vx = (Math.random() - 0.5) * 0.8;
                        this.vy = (Math.random() - 0.5) * 0.8;
                        this.radius = Math.random() * 1.5 + 0.5;
                    }
                    
                    update() {
                        this.x += this.vx;
                        this.y += this.vy;
                        
                        if (this.x < 0 || this.x > width) this.vx *= -1;
                        if (this.y < 0 || this.y > height) this.vy *= -1;
                    }
                    
                    draw() {
                        ctx.beginPath();
                        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                        ctx.fillStyle = `rgba(${colorRgb}, 0.4)`;
                        ctx.fill();
                    }
                }
                
                for (let i = 0; i < particleCount; i++) {
                    particles.push(new Particle());
                }
                
                function animate() {
                    ctx.clearRect(0, 0, width, height);
                    
                    for (let i = 0; i < particleCount; i++) {
                        particles[i].update();
                        particles[i].draw();
                        
                        for (let j = i + 1; j < particleCount; j++) {
                            const dx = particles[i].x - particles[j].x;
                            const dy = particles[i].y - particles[j].y;
                            const distance = Math.sqrt(dx * dx + dy * dy);
                            
                            if (distance < maxDistance) {
                                ctx.beginPath();
                                ctx.moveTo(particles[i].x, particles[i].y);
                                ctx.lineTo(particles[j].x, particles[j].y);
                                const opacity = 1 - (distance / maxDistance);
                                ctx.strokeStyle = `rgba(${colorRgb}, ${opacity * 0.15})`;
                                ctx.lineWidth = 1;
                                ctx.stroke();
                            }
                        }
                        
                        if (mouse.x != null && mouse.y != null) {
                            const dx = particles[i].x - mouse.x;
                            const dy = particles[i].y - mouse.y;
                            const distance = Math.sqrt(dx * dx + dy * dy);
                            
                            if (distance < mouseDistance) {
                                ctx.beginPath();
                                ctx.moveTo(particles[i].x, particles[i].y);
                                ctx.lineTo(mouse.x, mouse.y);
                                const opacity = 1 - (distance / mouseDistance);
                                ctx.strokeStyle = `rgba(${colorRgb}, ${opacity * 0.3})`;
                                ctx.lineWidth = 1.2;
                                ctx.stroke();
                                
                                const forceDirectionX = dx / distance;
                                const forceDirectionY = dy / distance;
                                const force = (mouseDistance - distance) / mouseDistance;
                                
                                particles[i].x -= forceDirectionX * force * 1.2;
                                particles[i].y -= forceDirectionY * force * 1.2;
                            }
                        }
                    }
                    requestAnimationFrame(animate);
                }
                
                animate();
            });
        </script>
</html>
