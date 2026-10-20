<?php
/**
 * Rodapé padrão do LAC Centro Médico
 * Inclua este arquivo antes de </body> em qualquer página
 * 
 * Uso: <?php include __DIR__ . '/caminho/para/includes/rodape.php'; ?>
 */
?>

<footer class="bg-[#3E352E] text-stone-300 mt-12 border-t border-[#C5A059]/30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Linha 1: 4 colunas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- Coluna 1: Identificação -->
            <div>
                <h3 class="font-serif text-white text-lg mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-hospital text-[#C5A059]"></i>
                    LAC Centro Médico
                </h3>
                <p class="text-xs text-stone-400 leading-relaxed">
                    Atendimento médico humanizado, presencial e por teleconsulta.
                    Cuidando da sua saúde com tecnologia e acolhimento.
                </p>
                <div class="mt-4 space-y-1 text-xs text-stone-400">
                    <div><strong class="text-stone-300">CNPJ:</strong> 55.807.708/0001-40</div>
                    <div><strong class="text-stone-300">CNES:</strong> 8133670</div>
                </div>
            </div>

            <!-- Coluna 2: Contato -->
            <div>
                <h3 class="font-serif text-white text-lg mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-headset text-[#C5A059]"></i>
                    Atendimento
                </h3>
                <ul class="space-y-2.5 text-sm">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-location-dot text-[#C5A059] mt-1 shrink-0"></i>
                        <span class="text-xs text-stone-400 leading-relaxed">
                            Av. Maringá, 2729<br>
                            Atuba — Pinhais / PR<br>
                            CEP 83324-442
                        </span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-phone text-[#C5A059] shrink-0"></i>
                        <a href="tel:+554135008072" class="text-xs text-stone-400 hover:text-[#C5A059] transition">
                            (41) 3500-8072
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-envelope text-[#C5A059] shrink-0"></i>
                        <a href="mailto:atendimento@laccentromedico.com.br" class="text-xs text-stone-400 hover:text-[#C5A059] transition break-all">
                            atendimento@laccentromedico.com.br
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Coluna 3: Redes Sociais -->
            <div>
                <h3 class="font-serif text-white text-lg mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-share-nodes text-[#C5A059]"></i>
                    Redes & Contato Direto
                </h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="https://wa.me/5541987268262" target="_blank" rel="noopener"
                           class="flex items-center gap-2 text-xs text-stone-400 hover:text-emerald-400 transition group">
                            <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-emerald-500/20 group-hover:border-emerald-500/40 transition">
                                <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                            </span>
                            <span>
                                <strong class="block text-stone-300">WhatsApp</strong>
                                (41) 98726-8262
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="https://instagram.com/laccentromedico" target="_blank" rel="noopener"
                           class="flex items-center gap-2 text-xs text-stone-400 hover:text-pink-400 transition group">
                            <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-pink-500/20 group-hover:border-pink-500/40 transition">
                                <i class="fa-brands fa-instagram text-pink-400"></i>
                            </span>
                            <span>
                                <strong class="block text-stone-300">Instagram</strong>
                                @laccentromedico
                            </span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Coluna 4: Links e LGPD -->
            <div>
                <h3 class="font-serif text-white text-lg mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-[#C5A059]"></i>
                    Institucional
                </h3>
                <ul class="space-y-2 text-xs">
    <li>
        <a href="<?php echo APP_BASE_PATH; ?>/termos-de-uso.php" class="text-stone-400 hover:text-[#C5A059] transition">
            <i class="fa-solid fa-chevron-right text-[10px] mr-1 text-[#C5A059]"></i> Termos de Uso
        </a>
    </li>
    <li>
        <a href="<?php echo APP_BASE_PATH; ?>/politica-privacidade.php" class="text-stone-400 hover:text-[#C5A059] transition">
            <i class="fa-solid fa-chevron-right text-[10px] mr-1 text-[#C5A059]"></i> Política de Privacidade
        </a>
    </li>
    <li>
        <a href="<?php echo APP_BASE_PATH; ?>/lgpd.php" class="text-stone-400 hover:text-[#C5A059] transition">
            <i class="fa-solid fa-chevron-right text-[10px] mr-1 text-[#C5A059]"></i> LGPD Saúde
        </a>
    </li>
    <li>
        <a href="<?php echo APP_BASE_PATH; ?>/termo-consentimento.php" class="text-stone-400 hover:text-[#C5A059] transition">
            <i class="fa-solid fa-chevron-right text-[10px] mr-1 text-[#C5A059]"></i> Termo de Consentimento
        </a>
    </li>
    <li>
        <a href="<?php echo APP_BASE_PATH; ?>/cookies.php" class="text-stone-400 hover:text-[#C5A059] transition">
            <i class="fa-solid fa-chevron-right text-[10px] mr-1 text-[#C5A059]"></i> Política de Cookies
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="abrirBannerCookies()" class="text-stone-400 hover:text-[#C5A059] transition">
            <i class="fa-solid fa-chevron-right text-[10px] mr-1 text-[#C5A059]"></i> Gerenciar Cookies
        </a>
    </li>
    <li>
        <a href="<?php echo APP_BASE_PATH; ?>/dpo.php" class="text-stone-400 hover:text-[#C5A059] transition">
            <i class="fa-solid fa-chevron-right text-[10px] mr-1 text-[#C5A059]"></i> Canal do DPO
        </a>
    </li>
</ul>

                <div class="mt-5 pt-4 border-t border-white/10">
                    <div class="flex items-center gap-2 text-[10px] text-stone-500">
                        <i class="fa-solid fa-lock text-emerald-500"></i>
                        <span>Site seguro · SSL · LGPD</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Linha 2: Copyright -->
        <div class="mt-10 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-stone-500">
            <div class="text-center sm:text-left">
                &copy; <?php echo date('Y'); ?> <strong class="text-stone-400">LAC Centro Médico</strong>. Todos os direitos reservados.
            </div>
            <div class="text-center sm:text-right">
                Responsável Técnico: <em>a definir</em> · CRM-PR
            </div>
        </div>

    </div>
</footer>

<!-- ============================================================ -->
<!-- Banner de Cookies (LGPD) — aparece em todas as páginas       -->
<!-- ============================================================ -->
<?php include __DIR__ . '/cookies.php'; ?>