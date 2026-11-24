
<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pagination: Object,
    filters: Object,
    maxVisiblePages: {
        type: Number,
        default: 5
    }
});

const filterQuery = computed(() => {
    const params = new URLSearchParams(props.filters).toString();
    return params ? `&${params}` : '';
});

const buildUrl = (url) => {
    if (!url) return '';
    return url.includes('?') ? `${url}${filterQuery.value}` : `${url}?${filterQuery.value.substring(1)}`;
};

const paginationLinks = computed(() => {
    if (!props.pagination || !props.pagination.links) return [];

    const currentPage = props.pagination.current_page;
    const lastPage = props.pagination.last_page;
    const maxPages = props.maxVisiblePages;
    const links = [];

    // Asegurar que tengamos un número impar de páginas visibles (sin contar primera/última)
    // para que la página actual pueda estar centrada
    const visibleMiddlePages = Math.max(3, maxPages - 2);
    const halfVisible = Math.floor(visibleMiddlePages / 2);

    // Siempre incluir la primera página
    links.push({
        url: buildUrl(props.pagination.first_page_url),
        label: '1',
        active: currentPage === 1
    });

    // Calcular rango centrado alrededor de la página actual
    let startPage = Math.max(2, currentPage - halfVisible);
    let endPage = Math.min(lastPage - 1, startPage + visibleMiddlePages - 1);

    // Reajustar el rango si está cerca del final
    if (endPage >= lastPage - 1) {
        endPage = lastPage - 1;
        startPage = Math.max(2, endPage - visibleMiddlePages + 1);
    }

    // Reajustar el rango si está cerca del inicio
    if (startPage <= 2) {
        startPage = 2;
        endPage = Math.min(lastPage - 1, startPage + visibleMiddlePages - 1);
    }

    // Añadir ellipsis después de la primera página si es necesario
    if (startPage > 2) {
        links.push({ url: null, label: '...', active: false });
    }

    // Añadir páginas intermedias
    for (let i = startPage; i <= endPage; i++) {
        const pageUrl = props.pagination.links.find(link => link.label == i)?.url;
        links.push({
            url: buildUrl(pageUrl),
            label: i.toString(),
            active: currentPage === i
        });
    }

    // Añadir ellipsis antes de la última página si es necesario
    if (endPage < lastPage - 1) {
        links.push({ url: null, label: '...', active: false });
    }

    // Siempre incluir la última página si es distinta a la primera
    if (lastPage > 1) {
        links.push({
            url: buildUrl(props.pagination.last_page_url),
            label: lastPage.toString(),
            active: currentPage === lastPage
        });
    }

    return links;
});
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 sm:flex-row">
        <div class="text-sm text-slate-500 dark:text-slate-400">
            Mostrando
            <span class="font-medium">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</span>
            a
            <span class="font-medium">{{
                Math.min(pagination.current_page * pagination.per_page, pagination.total)
                }}</span>
            de
            <span class="font-medium">{{ pagination.total }}</span>
            resultados
        </div>

        <div class="flex flex-wrap items-center justify-center gap-1">
            <!-- Botón Anterior -->
            <template v-if="pagination.prev_page_url">
                <Link :href="buildUrl(pagination.prev_page_url)" preserve-scroll preserve-state
                    class="ui-btn-secondary px-3 py-1.5 text-xs">
                Anterior
                </Link>
            </template>
            <template v-else>
                <button disabled
                    class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-400 opacity-60 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-600">
                    Anterior
                </button>
            </template>

            <!-- Enlaces de página optimizados -->
            <template v-for="(link, index) in paginationLinks" :key="index">
                <template v-if="link.url">
                    <Link :href="link.url" preserve-scroll :class="{
                        'border-brand-600 bg-brand-600 text-white': link.active,
                        'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800': !link.active
                    }" class="inline-flex min-w-8 items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-semibold" v-html="link.label" />
                </template>
                <template v-else>
                    <span class="px-2 py-1.5 text-xs text-slate-400">{{ link.label }}</span>
                </template>
            </template>

            <!-- Botón Siguiente -->
            <template v-if="pagination.next_page_url">
                <Link :href="buildUrl(pagination.next_page_url)" preserve-scroll preserve-state
                    class="ui-btn-secondary px-3 py-1.5 text-xs">
                Siguiente
                </Link>
            </template>
            <template v-else>
                <button disabled
                    class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-400 opacity-60 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-600">
                    Siguiente
                </button>
            </template>
        </div>
    </div>
</template>