<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $cards->count() }} ID {{ str('card')->plural($cards->count()) }} · {{ $school->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @include('cards.styles')
    @php($land = \App\Support\IdCards::TEMPLATES[$s['template']][1] === 'landscape')
    @php($cw = $land ? 85.6 : 54)
    @php($ch = $land ? 54 : 85.6)
    @php($cols = $land ? 2 : 3)
    @php($perPage = $land ? 10 : 9)
    <style>
        html, body { margin: 0; }
        body { background: #e2e8f0; }
        .page { background: #fff; margin: 0 auto 8mm; box-shadow: 0 4px 20px rgb(15 23 42 / .15); break-after: page; page-break-after: always; overflow: hidden; }
        .page:last-child { break-after: auto; page-break-after: auto; }
        .sheet { width: 210mm; height: 297mm; display: grid; place-items: center; }
        .grid-cards { display: grid; grid-template-columns: repeat({{ $cols }}, {{ $cw }}mm); gap: 3mm; }
        .cell { position: relative; width: {{ $cw }}mm; height: {{ $ch }}mm; }
        /* Thin cut lines just outside each card. */
        .cell::after { content: ''; position: absolute; inset: -.6mm; border: .15mm dashed #cbd5e1; border-radius: 3.6mm; pointer-events: none; }
        .single { width: {{ $cw }}mm; height: {{ $ch }}mm; }
        .single .idc { border-radius: 0; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; }
            .no-print { display: none !important; }
            @page { size: {{ $layout === 'a4' ? 'A4' : $cw.'mm '.$ch.'mm' }}; margin: 0; }
        }
    </style>
</head>
<body class="font-sans">
    <div class="no-print sticky top-0 z-10 mb-6 flex flex-wrap items-center justify-center gap-3 bg-slate-900 px-4 py-3 text-sm text-white">
        <strong>{{ $cards->count() }} {{ str('card')->plural($cards->count()) }}</strong>
        <span class="text-slate-300">{{ $layout === 'a4' ? 'A4 sheets, '.$perPage.' per page' : 'Card printer, '.$cw.' × '.$ch.' mm' }}{{ $s['show_back'] ? ', with back side' : '' }}</span>
        <button class="btn btn-sm" onclick="window.print()">Print</button>
        <button class="btn btn-sm btn-primary" id="pdf">Download PDF</button>
        <button class="btn btn-sm" onclick="window.close()">Close</button>
        <span id="pdf-status" class="text-amber-300"></span>
        <span class="w-full text-center text-xs text-slate-400">
            @if ($layout === 'a4')
                Print settings: Paper A4, Margins None, Scale 100%, Background graphics on.{{ $s['show_back'] ? ' For two-sided printing, choose "Flip on long edge". Back pages are laid out to line up.' : '' }}
            @else
                Choose your card printer (CR80 card). Margins None, Scale 100%, Background graphics on.{{ $s['show_back'] ? ' Each card is followed by its back.' : '' }}
            @endif
        </span>
    </div>

    @if ($cards->isEmpty())
        <p class="p-10 text-center text-slate-600">Nobody was chosen.</p>
    @elseif ($layout === 'a4')
        @foreach ($cards->chunk($perPage) as $chunk)
            <div class="page sheet pdf-page" data-w="210" data-h="297">
                <div class="grid-cards">
                    @foreach ($chunk as $card)<div class="cell">@include('cards.face', ['side' => 'front'])</div>@endforeach
                </div>
            </div>
            @if ($s['show_back'])
                {{-- Mirrored in each row, so a long-edge flip puts every back behind its own front. --}}
                <div class="page sheet pdf-page" data-w="210" data-h="297">
                    <div class="grid-cards">
                        @foreach ($chunk->values()->chunk($cols) as $row)
                            @foreach ($row->values()->pad($cols, null)->reverse() as $card)
                                <div class="cell">@if ($card)@include('cards.face', ['side' => 'back'])@endif</div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @else
        @foreach ($cards as $card)
            <div class="page single pdf-page" data-w="{{ $cw }}" data-h="{{ $ch }}">@include('cards.face', ['side' => 'front'])</div>
            @if ($s['show_back'])
                <div class="page single pdf-page" data-w="{{ $cw }}" data-h="{{ $ch }}">@include('cards.face', ['side' => 'back'])</div>
            @endif
        @endforeach
    @endif

    <script src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script>
        // Each page is drawn as a sharp picture and placed at its exact size in mm, so the PDF prints like the page.
        const button = document.getElementById('pdf');
        const status = document.getElementById('pdf-status');
        async function downloadPdf() {
            const pages = [...document.querySelectorAll('.pdf-page')];
            if (!pages.length || !window.jspdf || !window.htmlToImage) { status.textContent = 'Could not load the PDF maker. Check the internet connection, or use Print and choose "Save as PDF".'; return; }
            button.disabled = true;
            await document.fonts.ready;
            const first = pages[0];
            const size = (p) => [Number(p.dataset.w), Number(p.dataset.h)];
            const [w0, h0] = size(first);
            const pdf = new window.jspdf.jsPDF({ unit: 'mm', format: [w0, h0], orientation: w0 > h0 ? 'landscape' : 'portrait', compress: true });
            const ratio = {{ $layout === 'a4' ? 3 : 4 }};
            for (let i = 0; i < pages.length; i++) {
                status.textContent = 'Making PDF… page ' + (i + 1) + ' of ' + pages.length;
                const [w, h] = size(pages[i]);
                if (i > 0) pdf.addPage([w, h], w > h ? 'landscape' : 'portrait');
                const image = await window.htmlToImage.toJpeg(pages[i], { quality: 0.95, pixelRatio: ratio, backgroundColor: '#ffffff', style: { margin: '0', boxShadow: 'none' } });
                pdf.addImage(image, 'JPEG', 0, 0, w, h, undefined, 'FAST');
            }
            pdf.save(@js(\Illuminate\Support\Str::slug($school->name).'-'.$type.'-cards-'.now()->format('Y-m-d').'.pdf'));
            status.textContent = 'PDF downloaded.';
            button.disabled = false;
        }
        button.addEventListener('click', () => downloadPdf().catch((e) => { status.textContent = 'The PDF could not be made: ' + e.message; button.disabled = false; }));
        @if ($download)
            window.addEventListener('load', () => button.click());
        @endif
    </script>
</body>
</html>
