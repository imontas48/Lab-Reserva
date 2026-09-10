<template>
  <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
    <p v-if="rows.length === 0" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Sin datos en el rango elegido.</p>
    <table v-else class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
          <th class="py-1 pr-4">#</th>
          <th class="py-1 pr-4">Nombre</th>
          <th v-for="col in columns" :key="col.key" class="py-1 text-right">{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, i) in rows" :key="i" class="border-t border-gray-100 dark:border-gray-700">
          <td class="py-1.5 pr-4 text-gray-500 dark:text-gray-400">{{ i + 1 }}</td>
          <td class="py-1.5 pr-4 font-medium text-gray-900 dark:text-white">{{ row[nameKey] }}</td>
          <td v-for="col in columns" :key="col.key" class="py-1.5 text-right tabular-nums text-gray-800 dark:text-gray-200">{{ format(row[col.key]) }}</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  rows: { type: Array, default: () => [] },
  nameKey: { type: String, required: true },
  /** [{ key, label }] */
  columns: { type: Array, required: true },
});

const ROLE_LABELS = { admin: 'Admin', teacher: 'Profesor', student: 'Estudiante' };
const format = (value) => ROLE_LABELS[value] ?? value;
</script>
