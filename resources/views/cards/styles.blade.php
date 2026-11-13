{{-- Card styles in real sizes (mm and pt) so the screen preview, the print and the PDF match. CR80 = 85.6 × 54 mm. --}}
<style>
    .idc { --p: #0e6a5a; --a: #d9822b; --pd: color-mix(in srgb, var(--p) 62%, #000); --pl: color-mix(in srgb, var(--p) 10%, #fff);
        position: relative; overflow: hidden; width: 54mm; height: 85.6mm; border-radius: 3.2mm; background: #fff; color: #0f172a;
        font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size: 6.2pt; line-height: 1.25; box-sizing: border-box;
        -webkit-print-color-adjust: exact; print-color-adjust: exact; flex: none; }
    .idc.land { width: 85.6mm; height: 54mm; }
    .idc *, .idc *::before, .idc *::after { box-sizing: border-box; }
    .idc img { display: block; }
    .idc .ab { position: absolute; }
    .idc .logo { width: 8mm; height: 8mm; border-radius: 50%; background: #fff; display: grid; place-items: center; overflow: hidden; flex: none; }
    .idc .logo img { width: 100%; height: 100%; object-fit: contain; padding: .5mm; }
    .idc .logo span { font-weight: 800; font-size: 6.5pt; color: var(--p); }
    .idc .photo { overflow: hidden; background: var(--pl); display: grid; place-items: center; flex: none; }
    .idc .photo img { width: 100%; height: 100%; object-fit: cover; }
    .idc .photo span { font-weight: 800; font-size: 15pt; color: var(--p); opacity: .55; }
    .idc .school { font-weight: 800; text-transform: uppercase; letter-spacing: .02em; line-height: 1.1; }
    .idc .name { font-weight: 800; line-height: 1.1; }
    .idc .rows { width: 100%; border-collapse: collapse; }
    .idc .rows td { padding: .45mm 0; vertical-align: top; }
    .idc:not(.land) .rows { font-size: 6.9pt; }
    .idc:not(.land) .rows td { padding: .75mm 0; }
    .idc:not(.land) .kids div { font-size: 6.3pt; padding: .55mm 0; }
    .idc .rows td:first-child { color: #64748b; white-space: nowrap; padding-inline-end: 2mm; }
    .idc .rows td:last-child { font-weight: 600; text-align: end; word-break: break-word; }
    .idc .kids { margin-top: .6mm; }
    .idc .kids div { display: flex; justify-content: space-between; gap: 1.5mm; padding: .35mm 0; border-top: .15mm dashed #cbd5e1; font-size: 5.8pt; }
    .idc .kids b { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .idc .kids i { font-style: normal; color: #64748b; white-space: nowrap; }
    .idc .qr { background: #fff; padding: .7mm; border-radius: 1mm; }
    .idc .qr svg { width: 100%; height: 100%; display: block; }
    .idc .pill { display: inline-block; border-radius: 99mm; padding: .5mm 2.2mm; font-weight: 700; font-size: 6pt; }
    .idc .clip { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* Classic */
    .t-classic .head { inset: 0 0 auto 0; height: 31mm; background: linear-gradient(140deg, var(--p), var(--pd)); clip-path: ellipse(85% 100% at 50% 0); }
    .t-classic .brand { top: 3mm; inset-inline: 3mm; display: flex; flex-direction: column; align-items: center; gap: 1.2mm; color: #fff; text-align: center; }
    .t-classic .brand .school { font-size: 6.4pt; }
    .t-classic .role { top: 15.2mm; inset-inline: 0; text-align: center; color: color-mix(in srgb, #fff 80%, var(--a)); font-size: 5.3pt; font-weight: 700; letter-spacing: .25em; text-transform: uppercase; }
    .t-classic .photo { top: 19mm; left: 50%; transform: translateX(-50%); width: 22mm; height: 22mm; border-radius: 50%; border: .9mm solid #fff; box-shadow: 0 .8mm 2mm rgb(0 0 0 / .25); }
    .t-classic .who { top: 42.5mm; inset-inline: 3mm; text-align: center; }
    .t-classic .who .name { font-size: 9.5pt; }
    .t-classic .who .pill { margin-top: 1mm; background: color-mix(in srgb, var(--a) 16%, #fff); color: color-mix(in srgb, var(--a) 70%, #000); }
    .t-classic .body { top: 52.5mm; inset-inline: 4mm; }
    .t-classic .foot { inset: auto 0 0 0; height: 6.5mm; background: var(--a); color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 3.5mm; font-weight: 700; font-size: 5.8pt; }

    /* Wave */
    .t-wave .wave-top { inset: 0 0 auto 0; height: 30mm; }
    .t-wave .wave-bot { inset: auto 0 0 0; height: 11mm; }
    .t-wave svg.w { width: 100%; height: 100%; display: block; }
    .t-wave .brand { top: 3mm; inset-inline: 3.5mm; display: flex; align-items: center; gap: 1.8mm; color: #fff; }
    .t-wave .brand .school { font-size: 6.1pt; }
    .t-wave .brand .logo { width: 7.5mm; height: 7.5mm; }
    .t-wave .role { top: 12.6mm; inset-inline: 3.5mm; color: #fff; opacity: .85; font-size: 5.2pt; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; }
    .t-wave .photo { top: 17mm; left: 50%; transform: translateX(-50%); width: 21mm; height: 25mm; border-radius: 3mm; border: .8mm solid var(--a); background-clip: padding-box; box-shadow: 0 .8mm 2.5mm rgb(0 0 0 / .18); }
    .t-wave .who { top: 43.5mm; inset-inline: 3mm; text-align: center; }
    .t-wave .who .name { font-size: 9.5pt; color: var(--pd); }
    .t-wave .who .sub { margin-top: .6mm; color: var(--a); font-weight: 700; font-size: 6.6pt; }
    .t-wave .body { top: 52.5mm; inset-inline: 4mm; }
    .t-wave .foot { inset: auto 3.5mm 1.8mm 3.5mm; display: flex; justify-content: space-between; color: #fff; font-weight: 700; font-size: 5.6pt; }

    /* Royal (crest) */
    .t-crest { color: #fff; background: radial-gradient(120% 70% at 50% 0, color-mix(in srgb, var(--p) 80%, #fff), var(--p) 45%, var(--pd)); }
    .t-crest .pattern { inset: 0; background-image: repeating-linear-gradient(45deg, rgb(255 255 255 / .05) 0 .4mm, transparent .4mm 3mm); }
    .t-crest .brand { top: 3.2mm; inset-inline: 3mm; display: flex; flex-direction: column; align-items: center; gap: 1.1mm; text-align: center; }
    .t-crest .brand .school { font-size: 6.3pt; }
    .t-crest .role { top: 15.5mm; inset-inline: 0; text-align: center; color: var(--a); font-size: 5.3pt; font-weight: 800; letter-spacing: .3em; text-transform: uppercase; }
    .t-crest .ring { top: 19.3mm; left: 50%; transform: translateX(-50%); width: 25mm; height: 25mm; border-radius: 50%; border: .35mm solid color-mix(in srgb, var(--a) 70%, transparent); }
    .t-crest .photo { top: 20.8mm; left: 50%; transform: translateX(-50%); width: 22mm; height: 22mm; border-radius: 50%; border: .8mm solid var(--a); }
    .t-crest .who { top: 46mm; inset-inline: 3mm; text-align: center; }
    .t-crest .who .name { font-size: 9.5pt; }
    .t-crest .who .sub { margin-top: .6mm; color: var(--a); font-weight: 700; font-size: 6.5pt; }
    .t-crest .body { top: 55mm; inset-inline: 4.5mm; }
    .t-crest .rows td:first-child, .t-crest .kids i { color: rgb(255 255 255 / .65); }
    .t-crest .kids div { border-top-color: rgb(255 255 255 / .2); }
    .t-crest .foot { inset: auto 3.5mm 2.5mm 3.5mm; display: flex; justify-content: space-between; align-items: center; font-size: 5.6pt; }
    .t-crest .foot .pill { border: .3mm solid var(--a); color: var(--a); font-family: ui-monospace, monospace; }

    /* Horizon */
    .t-horizon .head { inset: 0 0 auto 0; height: 13mm; background: linear-gradient(100deg, var(--pd), var(--p)); display: flex; align-items: center; gap: 2mm; padding: 0 3.5mm; color: #fff; }
    .t-horizon .head .logo { width: 9mm; height: 9mm; }
    .t-horizon .head .school { font-size: 7.4pt; flex: 1; min-width: 0; }
    .t-horizon .head .pill { background: var(--a); color: #fff; font-size: 5.4pt; letter-spacing: .12em; text-transform: uppercase; }
    .t-horizon .photo { top: 16mm; left: 3.5mm; width: 20mm; height: 24.5mm; border-radius: 2mm; border: .6mm solid var(--pl); }
    .t-horizon .who { top: 16mm; left: 27mm; right: 3.5mm; }
    .t-horizon .who .name { font-size: 9.6pt; color: var(--pd); }
    .t-horizon .who .sub { margin-top: .5mm; color: var(--a); font-weight: 700; font-size: 6.5pt; }
    .t-horizon .body { top: 25mm; left: 27mm; right: 19mm; }
    .t-horizon .qr { bottom: 5.2mm; right: 3.5mm; width: 14.5mm; height: 14.5mm; border: .3mm solid #e2e8f0; }
    .t-horizon .num { bottom: 4.6mm; left: 3.5mm; width: 20mm; text-align: center; font-weight: 800; font-size: 6.2pt; color: var(--pd); }
    .t-horizon .foot { inset: auto 0 0 0; height: 3mm; background: linear-gradient(90deg, var(--a), color-mix(in srgb, var(--a) 60%, var(--p))); }
    .t-horizon .valid { bottom: 4.6mm; left: 27mm; color: #64748b; font-size: 5.4pt; }

    /* Minimal (slate) */
    .t-slate .stripe { inset: 0 auto 0 0; width: 8.5mm; background: linear-gradient(180deg, var(--p), var(--pd)); }
    .t-slate .stripe span { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(-90deg); white-space: nowrap; color: #fff; font-weight: 800; font-size: 6pt; letter-spacing: .35em; text-transform: uppercase; }
    .t-slate .brand { top: 3.2mm; left: 11.5mm; right: 18mm; display: flex; align-items: center; gap: 1.8mm; }
    .t-slate .brand .logo { width: 7.5mm; height: 7.5mm; border: .3mm solid #e2e8f0; }
    .t-slate .brand .school { font-size: 6.6pt; color: var(--pd); }
    .t-slate .line { top: 12.6mm; left: 11.5mm; width: 14mm; height: .7mm; border-radius: 1mm; background: var(--a); }
    .t-slate .photo { top: 16mm; left: 11.5mm; width: 19mm; height: 23mm; border-radius: 1.6mm; }
    .t-slate .who { top: 16mm; left: 33.5mm; right: 4mm; }
    .t-slate .who .name { font-size: 9.6pt; }
    .t-slate .who .sub { margin-top: .5mm; color: var(--p); font-weight: 700; font-size: 6.5pt; }
    .t-slate .body { top: 25mm; left: 33.5mm; right: 4mm; }
    .t-slate .qr { top: 2.8mm; right: 3mm; width: 13mm; height: 13mm; }
    .t-slate .foot { bottom: 3mm; left: 11.5mm; right: 4mm; display: flex; justify-content: space-between; color: #64748b; font-size: 5.6pt; }
    .t-slate .foot b { color: #0f172a; font-weight: 800; }

    /* Back side */
    .idc.back { background: linear-gradient(180deg, #fff, var(--pl)); }
    .idc.back .band { inset: 0 0 auto 0; height: 2.2mm; background: linear-gradient(90deg, var(--p), var(--a)); }
    .idc.back .bk { position: absolute; inset: 5mm 4mm 4mm; display: flex; flex-direction: column; gap: 1.8mm; text-align: center; align-items: center; }
    .idc.land.back .bk { flex-direction: row; text-align: start; align-items: stretch; gap: 3.5mm; inset: 5.5mm 4mm 4mm; }
    .idc.back .col { display: flex; flex-direction: column; gap: 1.6mm; flex: 1; min-width: 0; }
    .idc.back .school { font-size: 6.6pt; color: var(--pd); }
    .idc.back .muted { color: #475569; }
    .idc.back .note { color: #334155; font-size: 5.8pt; }
    .idc.back .qr { width: 19mm; height: 19mm; border: .3mm solid #e2e8f0; }
    .idc.back:not(.land) .bk { inset: 6mm 4mm 4.5mm; }
    .idc.back:not(.land) .col { gap: 2.6mm; font-size: 6.8pt; }
    .idc.back:not(.land) .note { font-size: 6.6pt; }
    .idc.back:not(.land) .school { font-size: 7pt; }
    .idc.back .sign { margin-top: auto; text-align: center; }
    .idc.back .sign img { height: 7mm; max-width: 26mm; object-fit: contain; margin: 0 auto; }
    .idc.back .sign .ln { border-top: .25mm solid #334155; padding-top: .6mm; min-width: 24mm; font-weight: 700; font-size: 5.6pt; }
    .idc.back .valid { font-weight: 700; color: var(--pd); }
</style>
