import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // ApexCharts, on pages with a chart (<x-chart>)
                'resources/js/charts.js',
                // the response visualizer's engine (libs/surveyr_analytics)
                'resources/js/analytics.js',
                // SurveyJS bundles, one per page type (<x-surveyjs entry="...">)
                'resources/js/surveyjs/form.js',
                'resources/js/surveyjs/creator.js',
            ],
            refresh: true,
            fonts: [
                bunny('Nunito Sans', {
                    weights: [400, 500, 600, 700, 800],
                }),
                bunny('IBM Plex Mono', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
