<template>
    <div class="drawio-reference-widget">
        <a :href="editUrl" target="_blank" class="drawio-widget-link">
            <div class="drawio-widget-preview">
                <img v-if="previewUrl && !imageError"
                     :src="previewUrl"
                     :alt="name"
                     class="drawio-widget-image"
                     @error="onImageError" />
                <div v-else class="drawio-widget-placeholder">
                    <img :src="iconUrl"
                         class="drawio-widget-icon"
                         alt="" />
                </div>
            </div>
            <div class="drawio-widget-info">
                <span class="drawio-widget-name">{{ name }}</span>
                <span class="drawio-widget-description">{{ description }}</span>
            </div>
        </a>
    </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'

export default {
    name: 'DrawioReferenceWidget',
    props: {
        richObjectType: {
            type: String,
            default: '',
        },
        richObject: {
            type: Object,
            default: () => ({}),
        },
        accessible: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            imageError: false,
        }
    },
    computed: {
        name() {
            return this.richObject?.name ?? t('drawio', 'Draw.io diagram')
        },
        description() {
            return t('drawio', 'Open in Draw.io')
        },
        previewUrl() {
            return this.richObject?.previewUrl ?? null
        },
        editUrl() {
            return this.richObject?.editUrl ?? '#'
        },
        iconUrl() {
            return imagePath('drawio', 'app.svg')
        },
    },
    methods: {
        onImageError() {
            this.imageError = true
        },
    },
}
</script>

<style scoped>
.drawio-reference-widget {
    width: 100%;
}

.drawio-widget-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
    border: 2px solid var(--color-border);
    border-radius: var(--border-radius-large, 10px);
    overflow: hidden;
    background: var(--color-main-background);
    transition: border-color 0.2s;
}

.drawio-widget-link:hover {
    border-color: var(--color-primary-element);
}

.drawio-widget-preview {
    width: 160px;
    min-width: 160px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-background-hover);
    overflow: hidden;
}

.drawio-widget-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.drawio-widget-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.drawio-widget-icon {
    width: 48px;
    height: 48px;
    opacity: 0.5;
}

.drawio-widget-info {
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    overflow: hidden;
}

.drawio-widget-name {
    font-weight: bold;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.drawio-widget-description {
    font-size: 12px;
    color: var(--color-primary-element);
}
</style>
