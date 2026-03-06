<?php
require_once '../backend/conexao.php';
include '../includes/header.php';
?>

<main>
    <section class="sobre-inicial">
        <img class="birutinha" src="../img/birutinha.png" alt="">
        <picture>
            <source media="(max-width: 480px)" srcset="../img/sobre-mobile.png">
            <img class="sobre-img-desktop" src="../img/sobrenos.png" alt="">
        </picture>
        
        <!-- Seção de Localização com Mapa -->
        <section class="localizacao-section">
            <h2 class="localizacao-titulo">Região de Atuação</h2>
            <p>Confira a região de funcionamento do nosso delivery!</p>
            <div class="mapa-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d59490.09977866785!2d-48.5296383608477!3d-21.266272154177955!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94b946a1c0645469%3A0xbbf20cec9bd2f648!2sMonte%20Alto%2C%20SP%2C%2015910-000!5e0!3m2!1spt-BR!2sbr!4v1764971134225!5m2!1spt-BR!2sbr" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Localização Beirute Delivery em Monte Alto, SP">
                </iframe>
            </div>
        </section>
        
        <picture>
            <source media="(max-width: 480px)" srcset="../img/3.png">
            <img class="sobre-img-entrega" src="../img/delivery.png" alt="">
        </picture>
    </section>

    <?php include('../includes/footer.php'); ?>
</main>