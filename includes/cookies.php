<?php
/**
 * Banner de consentimento de cookies (LGPD Art. 8º).
 * 
 * Uso: incluir em todas as páginas, antes de </body>.
 * 
 * Este componente:
 *  - Mostra o banner na primeira visita (se ainda não escolheu)
 *  - Permite aceitar todos / só essenciais / personalizar
 *  - Guarda a escolha em cookie + registra no banco (auditoria)
 *  - Pode ser reaberto via window.abrirBannerCookies()
 */
?>

<!-- ============================================================ -->
<!-- BANNER DE COOKIES (LGPD)                                     -->
<!-- ============================================================ -->
<div id="cookie-banner" class="fixed bottom-0 left-0 right-0 z-[200] hidden">
    <div class="max-w-7xl mx-auto p-4">
        <div class="bg-white border border-[#E6D5B8] rounded-2xl shadow-2xl p-5 md:p-6">

            <!-- Modo resumido -->
            <div id="cookie-resumo" class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex items-start gap-3 flex-1">
                    <div class="w-11 h-11 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] shrink-0">
                        <i class="fa-solid fa-cookie-bite text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-serif text-lg text-[#3E352E]">Sua privacidade importa</h3>
                        <p class="text-xs text-stone-600 mt-1 leading-relaxed">
                            Usamos <strong>cookies essenciais</strong> para o funcionamento do site (login, sessão e segurança).
                            Não utilizamos cookies de marketing ou rastreamento.
                            <a href="cookies.php" target="_blank" class="text-[#8C6D36] underline font-medium">Saiba mais</a>.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <button type="button" onclick="cookiePersonalizar()"
                            class="px-4 py-2.5 rounded-xl border border-stone-300 text-stone-700 text-sm font-medium hover:bg-stone-50 transition">
                        Personalizar
                    </button>
                    <button type="button" onclick="cookieRejeitarOpcionais()"
                            class="px-4 py-2.5 rounded-xl border border-stone-300 text-stone-700 text-sm font-medium hover:bg-stone-50 transition">
                        Só essenciais
                    </button>
                    <button type="button" onclick="cookieAceitarTodos()"
                            class="px-4 py-2.5 rounded-xl bg-[#3E352E] hover:bg-[#5A4A3A] text-white text-sm font-medium transition shadow-sm">
                        Aceitar todos
                    </button>
                </div>
            </div>

            <!-- Modo personalizado -->
            <div id="cookie-detalhado" class="hidden space-y-4">
                <div class="flex items-center justify-between border-b border-stone-200 pb-3">
                    <h3 class="font-serif text-lg text-[#3E352E]">Personalizar preferências</h3>
                    <button type="button" onclick="cookieVoltarResumo()" class="text-xs text-stone-500 hover:text-stone-700">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Voltar
                    </button>
                </div>

                <!-- Categoria: Essenciais -->
                <div class="border border-stone-200 rounded-xl p-4 bg-[#FAF9F6]">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-bold text-sm text-[#3E352E]">Cookies essenciais</h4>
                                <span class="text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-full">
                                    Sempre ativos
                                </span>
                            </div>
                            <p class="text-xs text-stone-600">
                                Necessários para autenticação, segurança e funcionamento do site.
                                Sem eles, você não consegue fazer login.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-not-allowed shrink-0">
                            <input type="checkbox" checked disabled class="sr-only peer">
                            <div class="w-11 h-6 bg-emerald-500 rounded-full peer"></div>
                            <div class="absolute left-[calc(100%-1.375rem)] top-0.5 w-5 h-5 bg-white rounded-full transition"></div>
                        </label>
                    </div>
                </div>

                <!-- Categoria: Analíticos -->
                <div class="border border-stone-200 rounded-xl p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <h4 class="font-bold text-sm text-[#3E352E]">Cookies analíticos</h4>
                            <p class="text-xs text-stone-600 mt-1">
                                Ajudariam a entender como o site é usado (estatísticas anônimas).
                                <strong class="text-amber-700">Atualmente não utilizamos.</strong>
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="chk-analiticos" class="sr-only peer">
                            <div class="w-11 h-6 bg-stone-300 peer-checked:bg-emerald-500 rounded-full transition"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></div>
                        </label>
                    </div>
                </div>

                <!-- Categoria: Marketing -->
                <div class="border border-stone-200 rounded-xl p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <h4 class="font-bold text-sm text-[#3E352E]">Cookies de marketing</h4>
                            <p class="text-xs text-stone-600 mt-1">
                                Seriam usados para exibir anúncios personalizados.
                                <strong class="text-amber-700">Atualmente não utilizamos.</strong>
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="chk-marketing" class="sr-only peer">
                            <div class="w-11 h-6 bg-stone-300 peer-checked:bg-emerald-500 rounded-full transition"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></div>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-stone-100">
                    <button type="button" onclick="cookieRejeitarOpcionais()"
                            class="px-4 py-2.5 rounded-xl border border-stone-300 text-stone-700 text-sm font-medium hover:bg-stone-50 transition">
                        Só essenciais
                    </button>
                    <button type="button" onclick="cookieSalvarPersonalizado()"
                            class="px-4 py-2.5 rounded-xl bg-[#3E352E] hover:bg-[#5A4A3A] text-white text-sm font-medium transition shadow-sm">
                        Salvar preferências
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Botão flutuante para reabrir (aparece só se já escolheu) -->
<button id="cookie-reabrir" onclick="abrirBannerCookies()"
        class="hidden fixed bottom-4 right-4 z-[190] w-11 h-11 rounded-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white shadow-lg transition"
        title="Gerenciar cookies">
    <i class="fa-solid fa-cookie-bite"></i>
</button>

<script>
(function () {
    const COOKIE_NAME     = 'lac_cookies_consent';
    const COOKIE_DIAS     = 365;
    const POLITICA_VERSAO = 'v1.0';

        // ---------- Helpers: localStorage (preferido) + cookie (fallback) ----------
    function salvarEscolha(dados) {
        const json = JSON.stringify(dados);

        // 1) localStorage — mais confiável
        try {
            localStorage.setItem(COOKIE_NAME, json);
        } catch (e) {
            console.warn('[cookies] localStorage indisponível:', e);
        }

        // 2) Cookie — como fallback (para navegadores que limpam localStorage)
        try {
            const d = new Date();
            d.setTime(d.getTime() + COOKIE_DIAS * 24 * 60 * 60 * 1000);
            document.cookie = COOKIE_NAME + '=' + encodeURIComponent(json)
                            + ';expires=' + d.toUTCString()
                            + ';path=/;SameSite=Lax';
        } catch (e) {
            console.warn('[cookies] cookie indisponível:', e);
        }
    }

    function lerEscolha() {
        // 1) Tenta localStorage
        try {
            const local = localStorage.getItem(COOKIE_NAME);
            if (local) {
                const obj = JSON.parse(local);
                if (obj && obj.aceitos && obj.acao) {
                    console.log('[cookies] Escolha carregada (localStorage):', obj.acao);
                    return obj;
                }
            }
        } catch (e) {
            console.warn('[cookies] erro lendo localStorage:', e);
        }

        // 2) Fallback para cookie
        try {
            const m = document.cookie.match('(^|;)\\s*' + COOKIE_NAME + '\\s*=\\s*([^;]+)');
            if (m) {
                const obj = JSON.parse(decodeURIComponent(m[2]));
                if (obj && obj.aceitos && obj.acao) {
                    console.log('[cookies] Escolha carregada (cookie):', obj.acao);
                    return obj;
                }
            }
        } catch (e) {
            console.warn('[cookies] erro lendo cookie:', e);
        }

        return null;
    }

    // ---------- Estado ----------
    let escolhaAtual = null;

    // ---------- Abrir/Fechar ----------
    window.abrirBannerCookies = function () {
        document.getElementById('cookie-banner').classList.remove('hidden');
        document.getElementById('cookie-reabrir').classList.add('hidden');
        if (escolhaAtual) {
            document.getElementById('cookie-resumo').classList.add('hidden');
            document.getElementById('cookie-detalhado').classList.remove('hidden');
            document.getElementById('chk-analiticos').checked = !!escolhaAtual.analiticos;
            document.getElementById('chk-marketing').checked  = !!escolhaAtual.marketing;
        } else {
            document.getElementById('cookie-resumo').classList.remove('hidden');
            document.getElementById('cookie-detalhado').classList.add('hidden');
        }
    };

    window.cookiePersonalizar = function () {
        document.getElementById('cookie-resumo').classList.add('hidden');
        document.getElementById('cookie-detalhado').classList.remove('hidden');
    };

    window.cookieVoltarResumo = function () {
        document.getElementById('cookie-detalhado').classList.add('hidden');
        document.getElementById('cookie-resumo').classList.remove('hidden');
    };

    // ---------- Ações ----------
    window.cookieAceitarTodos = function () {
        salvar({ essenciais: true, analiticos: true, marketing: true }, 'aceitou_todos');
    };

    window.cookieRejeitarOpcionais = function () {
        salvar({ essenciais: true, analiticos: false, marketing: false }, 'rejeitou_opcionais');
    };

    window.cookieSalvarPersonalizado = function () {
        const analiticos = document.getElementById('chk-analiticos').checked;
        const marketing  = document.getElementById('chk-marketing').checked;
        salvar({ essenciais: true, analiticos, marketing }, 'personalizou');
    };

        // ---------- Salvar ----------
    async function salvar(aceitos, acao) {
        const dados = {
            aceitos: aceitos,
            acao: acao,
            versao: POLITICA_VERSAO,
            data: new Date().toISOString()
        };

        // 1) Persiste localmente (localStorage + cookie)
        salvarEscolha(dados);
        escolhaAtual = dados;

        // 2) Esconde o banner
        document.getElementById('cookie-banner').classList.add('hidden');
        document.getElementById('cookie-reabrir').classList.remove('hidden');

        // 3) Registra no servidor (auditoria LGPD)
        try {
            const resp = await fetch('<?php echo APP_BASE_PATH; ?>/api/cookies.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados),
                credentials: 'same-origin'
            });

            const json = await resp.json();
            console.log('[cookies] Registrado no servidor:', json);
        } catch (e) {
            console.warn('[cookies] falha ao registrar no servidor:', e);
        }
    }

        // ---------- Init ----------
    document.addEventListener('DOMContentLoaded', function () {
        const salvo = lerEscolha();

        if (salvo) {
            escolhaAtual = salvo;
            document.getElementById('cookie-reabrir').classList.remove('hidden');
            console.log('[cookies] Banner não será exibido (já escolhido em', salvo.data, ')');
            return;
        }

        console.log('[cookies] Nenhuma escolha registrada. Banner será exibido.');

        // Primeira visita → mostra o banner após um pequeno delay
        setTimeout(() => {
            document.getElementById('cookie-banner').classList.remove('hidden');
        }, 800);
    });
})();
</script>