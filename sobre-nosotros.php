<?php
session_start();
require_once 'conexion.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nosotros - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { 
            background-color: #F8F9FA;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .content-wrapper {
            flex: 1;
        }
        
        .hero-about {
            background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .hero-about h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: white;
        }
        
        .hero-about p {
            font-size: 1.25rem;
            opacity: 0.95;
        }
        
        .team-member {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
        }
        
        .team-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #25282B;
            margin-bottom: 0.5rem;
        }
        
        .team-code {
            font-size: 1.1rem;
            color: #2F80ED;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .about-section {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .about-section h2 {
            color: #25282B;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .about-section p {
            color: #52575C;
            font-size: 1.1rem;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="content-wrapper">
        <!-- Hero Section -->
        <div class="hero-about">
            <div class="container">
                <h1>Sobre Nosotros</h1>
                <p>Conoce al equipo detrás de Adminia</p>
            </div>
        </div>

        <div class="container mb-5">
            <!-- Información del Proyecto -->
            <div class="about-section">
                <h2>🛒 ¿Qué es Adminia?</h2>
                <p>
                    Adminia es una plataforma de comercio electrónico moderna y completa, desarrollada como proyecto académico. 
                    Nuestro sistema integra funcionalidades de e-commerce con características de red social, permitiendo a los usuarios 
                    no solo comprar productos, sino también interactuar y compartir experiencias con la comunidad.
                </p>
                <p>
                    El proyecto incluye gestión de productos, usuarios, pedidos, contenidos dinámicos y un sistema de publicaciones 
                    sociales, todo construido con tecnologías web modernas y siguiendo las mejores prácticas de desarrollo.
                </p>
            </div>

            <!-- Equipo de Desarrollo -->
            <div class="about-section">
                <h2>👥 Nuestro Equipo</h2>
                <p class="mb-4">Somos un equipo de estudiantes apasionados por el desarrollo web y la tecnología:</p>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="team-member text-center">
                            <div class="team-avatar">JH</div>
                            <div class="team-name">Johan Giovani Huamán Cuba</div>
                            <div class="team-code">u202417448</div>
                            <p class="text-muted">Desarrollador Full Stack</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="team-member text-center">
                            <div class="team-avatar">LB</div>
                            <div class="team-name">Luis Alexis Bardales Tejada</div>
                            <div class="team-code">u201819276</div>
                            <p class="text-muted">Desarrollador Full Stack</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="team-member text-center">
                            <div class="team-avatar">RT</div>
                            <div class="team-name">Rafael Augusto Tasayco Almonacid</div>
                            <div class="team-code">u20231f226</div>
                            <p class="text-muted">Desarrollador Full Stack</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tecnologías -->
            <div class="about-section">
                <h2>💻 Tecnologías Utilizadas</h2>
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">Frontend</h5>
                        <ul>
                            <li>HTML5 & CSS3</li>
                            <li>Bootstrap 5</li>
                            <li>JavaScript</li>
                            <li>Google Fonts (Inter)</li>
                            <li>PHP (Interfaz y lógica)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="about-section text-center">
                <h2>📧 Contacto</h2>
                <p>¿Tienes preguntas o sugerencias? No dudes en contactarnos.</p>
                <a href="index.php" class="btn btn-primary btn-lg mt-3">Volver al Inicio</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p>&copy; 2025 Adminia | <a href="sobre-nosotros.php">Sobre Nosotros</a> | Proyecto Académico</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
