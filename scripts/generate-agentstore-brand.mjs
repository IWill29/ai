import { writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { exportBrandKit } from '../node_modules/@mcpware/logoloom/src/tools/export-brand-kit.mjs';
import { optimizeSvg } from '../node_modules/@mcpware/logoloom/src/tools/optimize-svg.mjs';
import { textToPath } from '../node_modules/@mcpware/logoloom/src/tools/text-to-path.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const outputDir = join(root, 'brand');

const brandColors = {
    primary: '#4338CA',
    secondary: '#6366F1',
    textLight: '#1E1B4B',
    textDark: '#E8EAF2',
    bgLight: '#FAFAFF',
    bgDark: '#0A0B14',
};

const A_BODY =
    'M40 19 25.5 58h7.3l3-9.8h8.4l3 9.8h7.3L40 19Zm-2.8 22.5h5.6L40 33.2l-2.8 8.3Z';
const AGENT_ARC = 'M51.2 17.2a9.2 9.2 0 0 1 10.3 9.1';

function iconGroup({ dark = false }) {
    const markFill = dark ? '#E8EAF2' : '#FFFFFF';
    const arcOpacity = dark ? '0.35' : '0.45';
    const tileStops = dark
        ? '<stop stop-color="#1E2130"/><stop stop-color="#12141F"/>'
        : '<stop stop-color="#3730A3"/><stop stop-color="#6366F1"/>';
    const tileRect = dark
        ? '<rect width="80" height="80" rx="18" fill="url(#tile)" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>'
        : '<rect width="80" height="80" rx="18" fill="url(#tile)"/>';

    return `
    <defs>
      <linearGradient id="tile" x1="12" y1="8" x2="68" y2="72" gradientUnits="userSpaceOnUse">${tileStops}</linearGradient>
      <linearGradient id="shine" x1="40" y1="6" x2="40" y2="38" gradientUnits="userSpaceOnUse">
        <stop stop-color="#FFFFFF" stop-opacity="${dark ? '0.14' : '0.2'}"/>
        <stop stop-color="#FFFFFF" stop-opacity="0"/>
      </linearGradient>
    </defs>
    ${tileRect}
    <rect width="80" height="${dark ? 34 : 38}" rx="18" fill="url(#shine)"/>
    <path d="${AGENT_ARC}" stroke="#34D399" stroke-opacity="${arcOpacity}" stroke-width="1.75" stroke-linecap="round" fill="none"/>
    <circle cx="60.5" cy="25.5" r="6" fill="#34D399" fill-opacity="${dark ? '0.16' : '0.2'}"/>
    <circle cx="60.5" cy="25.5" r="${dark ? 3.5 : 3.75}" fill="#34D399"/>
    <circle cx="60.5" cy="25.5" r="${dark ? 1.2 : 1.35}" fill="${dark ? '#D1FAE5' : '#ECFDF5'}"/>
    <path fill="${markFill}" fill-rule="evenodd" d="${A_BODY}"/>
  `;
}

function iconSvg({ dark = false }) {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" width="80" height="80">${iconGroup({ dark })}</svg>`;
}

function fullLogoSvg({ dark = false }) {
    const titleFill = dark ? '#E8EAF2' : '#1E1B4B';
    const tagFill = dark ? '#8A90A6' : '#64748B';

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 100" width="400" height="100">
  <g transform="translate(10,10)">${iconGroup({ dark })}</g>
  <text x="112" y="46" font-family="Instrument Sans, Inter, system-ui, sans-serif" font-size="27" font-weight="600" fill="${titleFill}" letter-spacing="-0.03em">AgentStore</text>
  <text x="112" y="64" font-family="Instrument Sans, Inter, system-ui, sans-serif" font-size="10" font-weight="500" fill="${tagFill}" letter-spacing="0.16em">COMMERCE AI</text>
</svg>`;
}

async function main() {
    const iconLight = iconSvg({ dark: false });
    const iconDark = iconSvg({ dark: true });
    const logoLight = fullLogoSvg({ dark: false });
    const logoDark = fullLogoSvg({ dark: true });

    writeFileSync(join(outputDir, 'icon-source.svg'), iconLight);

    const textResult = JSON.parse(await textToPath(logoLight));
    const optimized = JSON.parse(await optimizeSvg(textResult.svg ?? logoLight));
    const darkText = JSON.parse(await textToPath(logoDark));
    const darkOptimized = JSON.parse(await optimizeSvg(darkText.svg ?? logoDark));
    const iconOptimized = JSON.parse(await optimizeSvg(iconLight));

    const exportResult = JSON.parse(
        await exportBrandKit({
            svg: optimized.svg,
            darkSvg: darkOptimized.svg,
            outputDir,
            name: 'AgentStore',
            colors: brandColors,
        }),
    );

    writeFileSync(join(outputDir, 'icon-light.svg'), iconOptimized.svg);
    writeFileSync(join(outputDir, 'icon-dark.svg'), iconDark);
    writeFileSync(join(outputDir, 'export-result.json'), JSON.stringify(exportResult, null, 2));

    console.log('AgentStore brand kit exported:', exportResult.files?.length ?? 0, 'files');
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
