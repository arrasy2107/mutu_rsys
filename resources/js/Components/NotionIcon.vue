<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    size: {
        type: [Number, String],
        default: 18,
    },
    strokeWidth: {
        type: [Number, String],
        default: 1.75,
    },
    class: {
        type: String,
        default: '',
    },
});

const sizeClass = computed(() => {
    if (typeof props.size === 'number') {
        return `w-[${props.size}px] h-[${props.size}px]`;
    }
    if (props.size.includes('w-') || props.size.includes('h-')) {
        return props.size;
    }
    return `w-[${props.size}px] h-[${props.size}px]`;
});

const sizeNum = computed(() => {
    if (typeof props.size === 'number') return props.size;
    const parsed = parseInt(props.size, 10);
    return isNaN(parsed) ? 18 : parsed;
});
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="sizeNum"
        :height="sizeNum"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="['inline-block shrink-0 transition-colors', sizeClass, props.class]"
    >
        <!-- TABLE / SPREADSHEET -->
        <g v-if="name === 'table' || name === 'grid'">
            <path d="M12 3v18"/>
            <rect width="18" height="18" x="3" y="3" rx="2"/>
            <path d="M3 9h18"/>
            <path d="M3 15h18"/>
        </g>

        <!-- SETTINGS / GEAR -->
        <g v-else-if="name === 'settings' || name === 'gear'">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
            <circle cx="12" cy="12" r="3"/>
        </g>

        <!-- PLUS / ADD -->
        <g v-else-if="name === 'plus'">
            <path d="M5 12h14"/>
            <path d="M12 5v14"/>
        </g>

        <!-- MINUS / REMOVE -->
        <g v-else-if="name === 'minus'">
            <path d="M5 12h14"/>
        </g>

        <!-- TRASH / DELETE -->
        <g v-else-if="name === 'trash' || name === 'delete'">
            <path d="M3 6h18"/>
            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
            <line x1="10" x2="10" y1="11" y2="17"/>
            <line x1="14" x2="14" y1="11" y2="17"/>
        </g>

        <!-- COPY / DUPLICATE -->
        <g v-else-if="name === 'copy' || name === 'duplicate'">
            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
        </g>

        <!-- CHECK / SUCCESS -->
        <g v-else-if="name === 'check'">
            <path d="m5 12 5 5L20 7"/>
        </g>

        <!-- CHECK CIRCLE -->
        <g v-else-if="name === 'check-circle'">
            <circle cx="12" cy="12" r="10"/>
            <path d="m9 12 2 2 4-4"/>
        </g>

        <!-- X / CLOSE -->
        <g v-else-if="name === 'x' || name === 'close'">
            <path d="M18 6 6 18"/>
            <path d="m6 6 12 12"/>
        </g>

        <!-- CALENDAR -->
        <g v-else-if="name === 'calendar'">
            <path d="M8 2v4"/>
            <path d="M16 2v4"/>
            <rect width="18" height="18" x="3" y="4" rx="2"/>
            <path d="M3 10h18"/>
        </g>

        <!-- CLOCK / TIME -->
        <g v-else-if="name === 'clock' || name === 'time'">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </g>

        <!-- FILE TEXT / DOCUMENT -->
        <g v-else-if="name === 'file-text' || name === 'document'">
            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
            <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
            <path d="M10 9H8"/>
            <path d="M16 13H8"/>
            <path d="M16 17H8"/>
        </g>

        <!-- FOLDER -->
        <g v-else-if="name === 'folder'">
            <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>
        </g>

        <!-- LAYERS / MERGE -->
        <g v-else-if="name === 'layers' || name === 'merge'">
            <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
            <path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
            <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/>
        </g>

        <!-- SPARKLES / MAGIC -->
        <g v-else-if="name === 'sparkles' || name === 'magic'">
            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
            <path d="M5 3v4"/>
            <path d="M19 17v4"/>
            <path d="M3 5h4"/>
            <path d="M17 19h4"/>
        </g>

        <!-- ALERT / WARNING -->
        <g v-else-if="name === 'alert' || name === 'warning'">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
            <line x1="12" x2="12" y1="9" y2="13"/>
            <line x1="12" x2="12.01" y1="17" y2="17"/>
        </g>

        <!-- INFO -->
        <g v-else-if="name === 'info'">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 16v-4"/>
            <path d="M12 8h.01"/>
        </g>

        <!-- HELP / FAQ -->
        <g v-else-if="name === 'help' || name === 'faq'">
            <circle cx="12" cy="12" r="10"/>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
            <path d="M12 17h.01"/>
        </g>

        <!-- CHEVRON LEFT -->
        <g v-else-if="name === 'chevron-left'">
            <path d="m15 18-6-6 6-6"/>
        </g>

        <!-- CHEVRON RIGHT -->
        <g v-else-if="name === 'chevron-right'">
            <path d="m9 18 6-6-6-6"/>
        </g>

        <!-- CHEVRON DOWN -->
        <g v-else-if="name === 'chevron-down'">
            <path d="m6 9 6 6 6-6"/>
        </g>

        <!-- CHEVRON UP -->
        <g v-else-if="name === 'chevron-up'">
            <path d="m18 15-6-6-6 6"/>
        </g>

        <!-- ARROW LEFT -->
        <g v-else-if="name === 'arrow-left'">
            <path d="m12 19-7-7 7-7"/>
            <path d="M19 12H5"/>
        </g>

        <!-- ARROW RIGHT -->
        <g v-else-if="name === 'arrow-right'">
            <path d="M5 12h14"/>
            <path d="m12 5 7 7-7 7"/>
        </g>

        <!-- BAR CHART / STATS -->
        <g v-else-if="name === 'bar-chart' || name === 'chart'">
            <line x1="12" x2="12" y1="20" y2="10"/>
            <line x1="18" x2="18" y1="20" y2="4"/>
            <line x1="6" x2="6" y1="20" y2="16"/>
        </g>

        <!-- CALCULATOR -->
        <g v-else-if="name === 'calculator'">
            <rect width="16" height="20" x="4" y="2" rx="2"/>
            <line x1="8" x2="16" y1="6" y2="6"/>
            <line x1="16" x2="16" y1="14" y2="18"/>
            <path d="M16 10h.01"/>
            <path d="M12 10h.01"/>
            <path d="M8 10h.01"/>
            <path d="M12 14h.01"/>
            <path d="M8 14h.01"/>
            <path d="M12 18h.01"/>
            <path d="M8 18h.01"/>
        </g>

        <!-- ROCKET -->
        <g v-else-if="name === 'rocket'">
            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
            <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/>
            <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>
        </g>

        <!-- LIGHTBULB / TIP -->
        <g v-else-if="name === 'lightbulb' || name === 'tip'">
            <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
            <path d="M9 18h6"/>
            <path d="M10 22h4"/>
        </g>

        <!-- BOOK / GUIDE -->
        <g v-else-if="name === 'book' || name === 'guide'">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
        </g>

        <!-- HOSPITAL / BUILDING -->
        <g v-else-if="name === 'hospital' || name === 'building'">
            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
            <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
            <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
            <path d="M10 6h4"/>
            <path d="M10 10h4"/>
            <path d="M10 14h4"/>
            <path d="M10 18h4"/>
        </g>

        <!-- PILL / MEDICINE -->
        <g v-else-if="name === 'pill' || name === 'medicine'">
            <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/>
            <path d="m8.5 8.5 7 7"/>
        </g>

        <!-- DOWNLOAD / EXCEL -->
        <g v-else-if="name === 'download' || name === 'excel'">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" x2="12" y1="15" y2="3"/>
        </g>

        <!-- EDIT / PENCIL -->
        <g v-else-if="name === 'edit' || name === 'pencil'">
            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
            <path d="m15 5 4 4"/>
        </g>

        <!-- USER -->
        <g v-else-if="name === 'user'">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </g>

        <!-- USERS -->
        <g v-else-if="name === 'users'">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </g>

        <!-- SHIELD / SECURITY -->
        <g v-else-if="name === 'shield' || name === 'security'">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
            <path d="m9 12 2 2 4-4"/>
        </g>

        <!-- CIRCLE DOT / BULLET -->
        <g v-else-if="name === 'circle-dot'">
            <circle cx="12" cy="12" r="10"/>
            <circle cx="12" cy="12" r="3"/>
        </g>

        <!-- CIRCLE -->
        <g v-else-if="name === 'circle'">
            <circle cx="12" cy="12" r="10"/>
        </g>

        <!-- LANDMARK / BANK / EXECUTIVE -->
        <g v-else-if="name === 'landmark' || name === 'building-columns'">
            <line x1="2" x2="22" y1="22" y2="22"/>
            <line x1="18" x2="18" y1="11" y2="18"/>
            <line x1="14" x2="14" y1="11" y2="18"/>
            <line x1="10" x2="10" y1="11" y2="18"/>
            <line x1="6" x2="6" y1="11" y2="18"/>
            <polygon points="12 2 20 7 4 7"/>
        </g>

        <!-- STETHOSCOPE -->
        <g v-else-if="name === 'stethoscope'">
            <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/>
            <path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/>
            <circle cx="20" cy="10" r="2"/>
        </g>

        <!-- LOCK / UNLOCK -->
        <g v-else-if="name === 'lock'">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </g>
        <g v-else-if="name === 'unlock'">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
        </g>

        <!-- HOURGLASS -->
        <g v-else-if="name === 'hourglass'">
            <path d="M5 22h14"/>
            <path d="M5 2h14"/>
            <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/>
            <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/>
        </g>

        <!-- EYE -->
        <g v-else-if="name === 'eye'">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
            <circle cx="12" cy="12" r="3"/>
        </g>

        <!-- TARGET -->
        <g v-else-if="name === 'target'">
            <circle cx="12" cy="12" r="10"/>
            <circle cx="12" cy="12" r="6"/>
            <circle cx="12" cy="12" r="2"/>
        </g>

        <!-- LIST -->
        <g v-else-if="name === 'list'">
            <line x1="8" x2="21" y1="6" y2="6"/>
            <line x1="8" x2="21" y1="12" y2="12"/>
            <line x1="8" x2="21" y1="18" y2="18"/>
            <line x1="3" x2="3.01" y1="6" y2="6"/>
            <line x1="3" x2="3.01" y1="12" y2="12"/>
            <line x1="3" x2="3.01" y1="18" y2="18"/>
        </g>

        <!-- CLIPBOARD -->
        <g v-else-if="name === 'clipboard'">
            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
        </g>

        <!-- SEARCH -->
        <g v-else-if="name === 'search'">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.3-4.3"/>
        </g>

        <!-- DEFAULT / FALLBACK -->
        <g v-else>
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" x2="12" y1="8" y2="12"/>
            <line x1="12" x2="12.01" y1="16" y2="16"/>
        </g>
    </svg>
</template>
