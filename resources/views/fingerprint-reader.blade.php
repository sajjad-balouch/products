{{--
    HID DigitalPersona browser libraries for USB readers (U.are.U 4500, 5100, 5300 and others HID supports).
    They talk to "HID DigitalPersona Lite Client", which must be installed and running on this computer.

    window.fingerReader(onPicture, onStatus) finds the reader by itself, starts reading, and reconnects
    when the reader is plugged back in or the HID client starts. Each press calls onPicture with a PNG
    as standard base64. onStatus(state, message) reports 'ready', 'retry' or 'error'.

    window.fingerHelper(settings) talks to the fingerprint helper running on this computer, used when
    the school's website is on shared hosting.
--}}
<script src="https://cdn.jsdelivr.net/npm/@digitalpersona/websdk@1.1.0/dist/websdk.client.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@digitalpersona/core@0.2.6/dist/es5.bundles/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@digitalpersona/devices@0.2.6/dist/es5.bundles/index.umd.min.js"></script>
<script>
    window.fingerReader = (onPicture, onStatus) => {
        if (! window.dp || ! window.dp.devices) {
            onStatus('error', 'The fingerprint library did not load. Check the internet connection.');
            return null;
        }

        const reader = new dp.devices.FingerprintReader();
        const noClient = 'Cannot reach the reader. Make sure "HID DigitalPersona Lite Client" is installed and running on this computer.';
        let reading = false;
        let retry = null;

        const toBase64 = (value) => {
            let b64 = String(value).replace(/-/g, '+').replace(/_/g, '/');
            while (b64.length % 4) b64 += '=';
            return b64;
        };

        // Looks for any connected reader and starts the first one. Tries again every few seconds until one is found.
        const connect = async () => {
            clearTimeout(retry);
            try {
                const readers = await Promise.race([
                    reader.enumerateDevices(),
                    new Promise((_, fail) => setTimeout(() => fail(new Error('timeout')), 8000)),
                ]);
                if (! readers || ! readers.length) {
                    reading = false;
                    onStatus('error', 'No fingerprint reader found. Plug the reader into this computer.');
                    retry = setTimeout(connect, 3000);
                    return;
                }
                if (! reading) {
                    await reader.startAcquisition(dp.devices.SampleFormat.PngImage, readers[0]);
                    reading = true;
                }
                onStatus('ready', readers.length > 1 ? 'Reader connected (using the first of ' + readers.length + ').' : 'Reader connected.');
            } catch (e) {
                reading = false;
                onStatus('error', noClient);
                retry = setTimeout(connect, 5000);
            }
        };

        reader.on('DeviceConnected', () => { reading = false; connect(); });
        reader.on('DeviceDisconnected', () => { reading = false; onStatus('error', 'The fingerprint reader was unplugged. Plug it back in.'); retry = setTimeout(connect, 2000); });
        reader.on('CommunicationFailed', () => { reading = false; onStatus('error', noClient); retry = setTimeout(connect, 5000); });
        reader.on('QualityReported', (e) => { if (e.quality !== 0) onStatus('retry', 'Not a clear print. Press the finger flat and try again.'); });
        reader.on('ErrorOccurred', () => onStatus('retry', 'The reader had a problem. Try again.'));
        reader.on('SamplesAcquired', (e) => {
            const sample = (e.samples || [])[0];
            const data = typeof sample === 'string' ? sample : (sample && (sample.Data || sample.data));
            if (data) onPicture(toBase64(data));
        });

        connect();
        return reader;
    };

    window.fingerHelper = (settings) => {
        const call = async (path, body) => {
            let response;
            try {
                response = await fetch(settings.url + path, body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {});
            } catch {
                throw new Error('The fingerprint helper is not running on this computer. Start FingerprintHelper (it can start by itself at sign-in).');
            }
            if (response.status === 403) throw new Error('The fingerprint helper on this computer does not allow this website. Download the helper again from Attendance machines.');
            if (response.status === 422) throw new Error('The picture was not clear. Press the finger flat on the reader and try again.');
            if (! response.ok) throw new Error('The fingerprint helper had a problem (code ' + response.status + ').');
            return response.json();
        };

        return {
            health: () => call('/health'),
            extract: async (png) => (await call('/extract', { image: png, dpi: settings.dpi })).template,
            identify: (png, candidates) => call('/identify', { image: png, dpi: settings.dpi, threshold: settings.threshold, candidates }),
        };
    };
</script>
