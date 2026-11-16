<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    indicator: {
        type: Object,
        required: true,
    },
    formFields: {
        type: Array,
        default: () => [],
    },
    daysInMonth: {
        type: Number,
        default: 31,
    },
    tahun: {
        type: Number,
        default: 2026,
    },
    bulan: {
        type: Number,
        default: 1,
    },
    permissions: {
        type: Object,
        default: () => ({}),
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    isPeriodApproved: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const hariNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const getDayOfWeek = (dayNumber) => {
    const d = new Date(props.tahun, props.bulan - 1, dayNumber);
    return hariNames[d.getDay()];
};

const isSunday = (dayNumber) => {
    const d = new Date(props.tahun, props.bulan - 1, dayNumber);
    return d.getDay() === 0;
};

const metricTotalForDay = (dayNumber, role) =>
    props.formFields
        .filter((field) => field.type === role)
        .reduce(
            (total, field) =>
                total + (Number(props.modelValue[dayNumber]?.[field.key]) || 0),
            0,
        );

const dailyCapaian = (dayNumber) => {
    const denominator = metricTotalForDay(dayNumber, 'denominator');
    if (denominator <= 0) {
        return null;
    }

    const multiplier = Number(props.indicator?.multiplier) || 100;
    return (
        (metricTotalForDay(dayNumber, 'numerator') / denominator) *
        multiplier
    );
};

const isDailyTargetMet = (capaian) => {
    const target = Number(props.indicator?.target_capaian) || 100;
    const operator = props.indicator?.target_operator || '>=';

    if (operator === '<=') {
        return capaian <= target;
    }
    if (operator === '=') {
        return capaian === target;
    }
    return capaian >= target;
};

const isPercentageIndicator = computed(
    () => (Number(props.indicator?.multiplier) || 100) === 100,
);

const onCellChange = (dayNumber, fieldKey, event) => {
    const inputValue = event.target.value;
    const value = inputValue === '' ? '' : Number(inputValue);

    emit('update:modelValue', {
        ...props.modelValue,
        [dayNumber]: {
            ...props.modelValue[dayNumber],
            [fieldKey]: value,
        },
    });
    emit('change');
};
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-gray-50/80 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                    Pencatatan Harian (Kalender 1 s.d. {{ daysInMonth }})
                </h3>
            </div>

            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-white border border-gray-300 rounded shadow-xs">Tab</kbd> /
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-white border border-gray-300 rounded shadow-xs">Enter</kbd>
                    untuk navigasi cepat antar kolom
                </span>
            </div>
        </div>

        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 border-separate border-spacing-0">
                <!-- Table Header -->
                <thead class="bg-slate-100 sticky top-0 z-10 shadow-xs">
                    <tr>
                        <th class="w-16 px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-b border-r border-gray-200">
                            Tgl
                        </th>
                        <th class="w-24 px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-b border-r border-gray-200">
                            Hari
                        </th>

                        <!-- Kolom Variabel Dinamis Sesuai Usulan IMUT -->
                        <th
                            v-for="field in formFields"
                            :key="field.key"
                            class="px-4 py-3 text-left text-xs font-bold text-gray-800 uppercase tracking-wider border-b border-r border-gray-200 min-w-[200px]"
                        >
                            <div class="flex items-center justify-between gap-1">
                                <span>{{ field.name }}</span>
                                <span v-if="field.type === 'numerator'" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                    (N)
                                </span>
                                <span v-else-if="field.type === 'denominator'" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                    (D)
                                </span>
                                <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600">
                                    (Variabel)
                                </span>
                            </div>
                        </th>

                        <!-- Capaian Harian -->
                        <th class="w-28 px-4 py-3 text-center text-xs font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200">
                            Capaian
                        </th>
                    </tr>
                </thead>

                <!-- Table Body: Hari 1 s.d. 31 -->
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr
                        v-for="day in daysInMonth"
                        :key="day"
                        :class="[
                            'transition-colors',
                            isSunday(day) ? 'bg-amber-50/20 hover:bg-amber-50/40' : 'hover:bg-indigo-50/20'
                        ]"
                    >
                        <!-- Tanggal -->
                        <td class="px-3 py-2 text-center text-xs font-bold text-gray-800 border-r border-gray-100 font-mono">
                            {{ day }}
                        </td>

                        <!-- Nama Hari -->
                        <td class="px-3 py-2 text-center text-xs font-medium border-r border-gray-100 font-mono" :class="isSunday(day) ? 'text-amber-600 font-bold' : 'text-gray-500'">
                            {{ getDayOfWeek(day) }}
                        </td>

                        <!-- Kolom Input / Display Variabel -->
                        <td
                            v-for="field in formFields"
                            :key="field.key"
                            class="px-3 py-1.5 border-r border-gray-100"
                        >
                            <!-- Mode Eksekutif (Direktur): Render angka rapi bersih tanpa kotak input form -->
                            <div
                                v-if="permissions.isExecutive"
                                class="w-full text-xs font-mono font-bold py-1.5 px-3 text-center text-slate-800 bg-slate-50/70 rounded-lg border border-slate-100"
                            >
                                {{ (modelValue[day] && modelValue[day][field.key] !== undefined && modelValue[day][field.key] !== null) ? Number(modelValue[day][field.key]).toLocaleString('id-ID') : '0' }}
                            </div>

                            <!-- Mode Entri (Admin, Komite Mutu, PIC Unit) -->
                            <input
                                v-else-if="modelValue[day]"
                                :value="modelValue[day][field.key] ?? ''"
                                @input="onCellChange(day, field.key, $event)"
                                type="number"
                                min="0"
                                step="any"
                                :disabled="
                                    readOnly ||
                                    !permissions.canEditDaily ||
                                    isPeriodApproved
                                "
                                class="w-full text-xs font-mono py-1.5 px-2.5 text-right border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors disabled:bg-gray-50 disabled:text-gray-400"
                                placeholder="0"
                            />
                        </td>

                        <!-- Capaian Harian (%) -->
                        <td class="px-3 py-2 text-center text-xs font-mono font-bold border-gray-100">
                            <span
                                v-if="dailyCapaian(day) !== null"
                                :class="
                                    isDailyTargetMet(dailyCapaian(day))
                                        ? 'text-emerald-600'
                                        : 'text-indigo-600'
                                "
                            >
                                {{ dailyCapaian(day).toFixed(1)
                                }}{{ isPercentageIndicator ? '%' : '' }}
                            </span>
                            <span v-else class="text-gray-300">-</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
