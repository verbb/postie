import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedPostieFixture } from '../../support/fixtures';

let route = '/admin/postie/providers';

export default defineScreenshotScenario({
    id: 'postie-feature-tour-packing',
    output: 'feature-tour/packing.png',
    route: () => route,
    viewport: { width: 1440, height: 1000, deviceScaleFactor: 2 },
    expectedOutput: { width: 1584, height: 998 },
    async setup(context) {
        route = `${(await seedPostieFixture(context)).providerRoute}#packing`;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Packing Method' },
        { type: 'text', text: 'Box Sizes' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `(() => {
                const panel = document.querySelector('#packing');
                if (!panel) throw new Error('Unable to locate Postie packing settings.');
                panel.classList.remove('hidden');
                panel.id = 'postie-packing-screenshot';
                panel.querySelectorAll('.carrier-settings-verbb-postie-providers-fed-ex').forEach((item) => item.classList.remove('hidden'));

                const markup = panel.querySelector(':scope > .vui-row');
                const tabs = document.querySelector('#tabs');
                if (markup instanceof HTMLElement) markup.style.display = 'none';
                if (tabs instanceof HTMLElement) tabs.style.display = 'none';

                const table = [...panel.querySelectorAll('#packing-boxPacking table')].find((item) => item instanceof HTMLElement && item.offsetParent !== null);
                if (!table) throw new Error('Unable to locate the visible Postie box table.');

                table.querySelectorAll('tbody tr').forEach((row, index) => {
                    if (index >= 6 && row instanceof HTMLElement) row.style.display = 'none';
                });
            })()`,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 200 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '#postie-packing-screenshot',
        x: -24,
        y: -19,
        width: 792,
        height: 499,
    },
    caption: 'Postie’s genuine packing controls with FedEx boxes available in Craft 5.',
    intent: 'Show the choice of packing strategy and the actual box dimensions used for quotes.',
});
