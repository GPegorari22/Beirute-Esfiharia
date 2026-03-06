// Ajustes dinâmicos para responsividade
(function() {
  'use strict';

  function ajustarPadding() {
    const main = document.querySelector('main');
    const width = window.innerWidth;

    if (width <= 768) {
      main.style.marginLeft = '0 !important';
      main.style.marginBottom = '90px !important';
    } else if (width <= 991) {
      main.style.marginLeft = '0 !important';
    } else {
      main.style.marginLeft = '280px !important';
    }
  }

  function ocultarElementosDesktop() {
    const elementosDesktop = document.querySelectorAll('[data-desktop-only]');
    const elementosMobile = document.querySelectorAll('[data-mobile-only]');

    elementosDesktop.forEach(el => {
      el.style.display = window.innerWidth <= 768 ? 'none' : 'block';
    });

    elementosMobile.forEach(el => {
      el.style.display = window.innerWidth <= 768 ? 'block' : 'none';
    });
  }

  // Executar ao carregar
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      ajustarPadding();
      ocultarElementosDesktop();
    });
  } else {
    ajustarPadding();
    ocultarElementosDesktop();
  }

  // Executar ao redimensionar
  window.addEventListener('resize', () => {
    ajustarPadding();
    ocultarElementosDesktop();
  });

  // Debounce para melhor performance
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      ajustarPadding();
      ocultarElementosDesktop();
    }, 250);
  });
})();