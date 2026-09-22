document.addEventListener('DOMContentLoaded', function () {

    /* 1. Auto-fechar mensagens de alerta após alguns segundos */
    document.querySelectorAll('.alert').forEach(function (alerta) {
        if (!alerta.classList.contains('success')) return;

        setTimeout(function () {
            alerta.style.transition = 'opacity 0.4s ease';
            alerta.style.opacity = '0';

            setTimeout(function () {
                alerta.remove();
            }, 400);
        }, 4000);
    });


    /* 2. Confirmação em links com atributo data-confirmar*/
    
    document.querySelectorAll('a[data-confirmar]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const mensagem = link.getAttribute('data-confirmar');
            if (!confirm(mensagem)) {
                e.preventDefault();
            }
        });
    });


    /*3. Foco automático no primeiro campo do formulário */
    const primeiroCampo = document.querySelector(
        'form input:not([type="hidden"]):not([readonly]), form select'
    );

    if (primeiroCampo && !primeiroCampo.hasAttribute('autofocus')) {
        // Não foca se o usuário já estiver digitando em algum lugar
        if (!document.activeElement || document.activeElement === document.body) {
            primeiroCampo.focus();
        }
    }


    /* 4. Prevenir clique duplo em botões de submit */
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const botao = form.querySelector('button[type="submit"]');

            if (botao && !botao.disabled) {
                // Aguarda o navegador enviar o form antes de desabilitar
                setTimeout(function () {
                    botao.disabled = true;
                    botao.style.opacity = '0.7';
                    botao.style.cursor = 'not-allowed';
                }, 50);
            }
        });
    });

});
