<template>
    <div class="ui-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="ui-th"
                            :class="alignClass(column.align)"
                            :style="column.width ? { width: column.width } : {}"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>

                <tbody v-if="loading" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td :colspan="columns.length" class="p-0">
                            <LoadingState :rows="skeletonRows" :columns="columns.length" />
                        </td>
                    </tr>
                </tbody>

                <tbody v-else-if="rows.length" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="(row, index) in rows"
                        :key="row[rowKey] ?? index"
                        class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="ui-td align-middle"
                            :class="alignClass(column.align)"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]" :index="index">
                                {{ displayValue(row[column.key]) }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState
            v-if="!loading && !rows.length"
            :title="emptyTitle"
            :description="emptyDescription"
            :icon="emptyIcon"
        >
            <template v-if="$slots.emptyActions" #actions>
                <slot name="emptyActions" />
            </template>
        </EmptyState>
    </div>
</template>

<script>
import EmptyState from '@/Components/EmptyState.vue';
import LoadingState from '@/Components/LoadingState.vue';

export default {
    name: 'DataTable',
    components: { EmptyState, LoadingState },
    props: {
        columns: { type: Array, required: true },
        rows: { type: Array, default: () => [] },
        rowKey: { type: String, default: 'id' },
        loading: { type: Boolean, default: false },
        skeletonRows: { type: Number, default: 4 },
        emptyTitle: { type: String, default: 'No hay registros' },
        emptyDescription: { type: String, default: '' },
        emptyIcon: { type: String, default: 'clipboard' },
    },
    methods: {
        alignClass(align) {
            if (align === 'right') return 'text-right';
            if (align === 'center') return 'text-center';
            return 'text-left';
        },
        displayValue(value) {
            if (value === null || value === undefined || value === '') return '—';
            if (typeof value === 'boolean') return value ? 'Sí' : 'No';
            if (typeof value === 'object') {
                return value.name ?? value.label ?? value.value ?? '—';
            }
            return value;
        },
    },
};
</script>
