<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'UpTracker') }} - Monitoreo de Servicios</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Animaciones sutiles para fondo claro */
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-8px); }
                100% { transform: translateY(0px); }
            }
            .animate-float {
                animation: float 6s ease-in-out infinite;
            }
            .glass-card {
                background: rgba(255, 255, 255, 0.65);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow: 0 20px 40px -15px rgba(13, 25, 43, 0.05), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
            }
            .bg-grid-pattern {
                background-size: 40px 40px;
                background-image: linear-gradient(to right, rgba(13, 25, 43, 0.02) 1px, transparent 1px),
                                  linear-gradient(to bottom, rgba(13, 25, 43, 0.02) 1px, transparent 1px);
            }
        </style>
    </head>
    <body class="bg-brand-ice font-sans text-brand-dark flex flex-col justify-between min-h-screen selection:bg-brand-primary/20 selection:text-brand-primary relative overflow-x-hidden">
        
        <!-- Ambient Background -->
        <div class="fixed inset-0 w-full h-full -z-10 pointer-events-none bg-grid-pattern">
            <!-- Canvas para partículas interactivas -->
            <canvas id="particles-canvas" class="absolute inset-0 w-full h-full opacity-70"></canvas>

            <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[800px] h-[800px] bg-blue-100/50 rounded-full mix-blend-multiply filter blur-[100px]"></div>
            <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[600px] h-[600px] bg-brand-success/10 rounded-full mix-blend-multiply filter blur-[80px]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-gradient-to-b from-transparent via-white/30 to-white/60"></div>
        </div>

        <!-- Header -->
        <header class="w-full max-w-7xl mx-auto px-6 py-8 flex items-center justify-between z-10">
            <div class="flex items-center gap-3 group cursor-pointer" aria-label="UpTracker Logo">
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center text-brand-primary shadow-sm transition-transform duration-300 group-hover:scale-105 group-hover:shadow-md">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 12v6m0-6l-2 2m2-2l2 2" />
                    </svg>
                </div>
                <span class="text-xl font-extrabold tracking-tight text-brand-dark">UpTracker</span>
            </div>

            @auth
                <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-primary hover:text-blue-700 transition-colors">
                    Dashboard 
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            @else
                <div class="hidden sm:flex items-center gap-6">
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-muted hover:text-brand-dark transition-colors">Iniciar Sesión</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-sm font-semibold px-5 py-2.5 bg-white text-brand-dark border border-slate-200/80 rounded-xl shadow-sm hover:shadow-md hover:border-slate-300 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0">
                            Registrarse
                        </a>
                    @endif
                </div>
            @endauth
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-6 py-12 z-10">
            <div class="w-full max-w-md relative animate-float">
                <!-- Decorative element behind the card -->
                <div class="absolute -inset-1 bg-gradient-to-r from-brand-primary/20 to-cyan-400/20 rounded-[2rem] blur-xl opacity-60"></div>
                
                <div class="glass-card rounded-3xl p-8 sm:p-10 text-center relative z-10">
                    
                    <!-- Badge Estado -->
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50/80 border border-emerald-100 text-brand-mint text-xs font-semibold mb-8 shadow-sm" role="status">
                        <span class="relative flex h-2 w-2" aria-hidden="true">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-success"></span>
                        </span>
                        Monitoreo en Tiempo Real
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-extrabold text-brand-dark tracking-tight leading-tight mb-4">
                        Bienvenido a <span class="text-brand-primary">UpTracker</span>
                    </h1>
                    
                    <p class="text-brand-muted text-base leading-relaxed mb-8">
                        Supervisa la disponibilidad, latencia y salud de tus endpoints y microservicios desde un solo lugar.
                    </p>

                    @if (Route::has('login'))
                        <div class="space-y-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="group w-full inline-flex items-center justify-center px-5 py-3.5 bg-brand-primary text-white font-semibold rounded-xl shadow-lg shadow-brand-primary/25 hover:shadow-xl hover:shadow-brand-primary/30 transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0">
                                    <span class="flex items-center gap-2">
                                        Entrar a mi Dashboard
                                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </span>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="group w-full inline-flex items-center justify-center px-5 py-3.5 bg-brand-primary text-white font-semibold rounded-xl shadow-lg shadow-brand-primary/25 hover:shadow-xl hover:shadow-brand-primary/30 transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0">
                                    Iniciar Sesión
                                </a>

                                @if (Route::has('register'))
                                    <div class="pt-4 text-sm text-brand-muted">
                                        ¿Aún no tienes una cuenta?
                                        <a href="{{ route('register') }}" class="font-semibold text-brand-primary hover:text-blue-700 transition-colors ml-1 relative after:absolute after:bottom-0 after:left-0 after:h-[2px] after:w-full after:origin-bottom-right after:scale-x-0 after:bg-blue-700 after:transition-transform after:duration-300 hover:after:origin-bottom-left hover:after:scale-x-100">
                                            Regístrate
                                        </a>
                                    </div>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-8 text-center flex flex-col sm:flex-row justify-between items-center gap-4 z-10">
            <div class="text-xs font-medium text-brand-muted">
                &copy; {{ date('Y') }} UpTracker. Todos los derechos reservados.
            </div>
            <div class="flex items-center gap-6 text-xs font-medium text-brand-muted">
                <a href="#" class="hover:text-brand-dark transition-colors">Privacidad</a>
                <a href="#" class="hover:text-brand-dark transition-colors">Términos</a>
            </div>
        </footer>

        <!-- Script de red de partículas interactivas -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const canvas = document.getElementById('particles-canvas');
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
                        
                        // Rebote suave en los bordes
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
                
                // Inicializar partículas
                for (let i = 0; i < particleCount; i++) {
                    particles.push(new Particle());
                }
                
                function animate() {
                    ctx.clearRect(0, 0, width, height);
                    
                    for (let i = 0; i < particleCount; i++) {
                        particles[i].update();
                        particles[i].draw();
                        
                        // Conectar partículas entre sí
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
                        
                        // Interactuar con el mouse (líneas y física)
                        if (mouse.x != null && mouse.y != null) {
                            const dx = particles[i].x - mouse.x;
                            const dy = particles[i].y - mouse.y;
                            const distance = Math.sqrt(dx * dx + dy * dy);
                            
                            if (distance < mouseDistance) {
                                // Dibujar línea hacia el mouse
                                ctx.beginPath();
                                ctx.moveTo(particles[i].x, particles[i].y);
                                ctx.lineTo(mouse.x, mouse.y);
                                const opacity = 1 - (distance / mouseDistance);
                                ctx.strokeStyle = `rgba(${colorRgb}, ${opacity * 0.3})`;
                                ctx.lineWidth = 1.2;
                                ctx.stroke();
                                
                                // Efecto parallax / atracción sutil hacia el mouse
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
    </body>
</html>
