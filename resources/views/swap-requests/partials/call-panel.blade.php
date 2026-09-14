{{--
    1-to-1 voice/video call for an accepted swap.
    Media flows peer-to-peer over WebRTC; Laravel only relays signaling (offer, answer, ICE) between the two participants.
--}}
<section
    class="call"
    aria-label="Voice and video call"
    data-workspace-panel="call"
    @if ($canCall)
        data-call-panel
        data-signal-url="{{ route('swap-requests.call.signals.store', $swapRequest) }}"
        data-signal-cursor="{{ $latestCallSignalId }}"
        data-call-role="{{ $isCallOfferer ? 'offerer' : 'answerer' }}"
        data-peer-name="{{ $otherUser->name }}"
        data-ice-servers="{{ json_encode(config('webrtc.ice_servers')) }}"
        data-csrf-token="{{ csrf_token() }}"
    @endif
>
    <div class="call-header">
        <h2>Voice &amp; Video</h2>
        @if ($canCall)
            <span class="call-status tone-idle" data-call-status role="status" aria-live="polite">Not in call</span>
        @elseif ($isCompleted)
            <span class="call-status tone-idle">Closed</span>
        @else
            <span class="call-status tone-idle">Locked</span>
        @endif
    </div>

    <div class="call-body">
        @if ($canCall)
            <div class="call-stage" data-call-stage>
                <video class="call-remote-video" data-remote-video autoplay playsinline hidden></video>

                <div class="call-placeholder" data-remote-placeholder>
                    <span class="call-avatar-circle" aria-hidden="true">{{ mb_strtoupper(mb_substr($otherUser->name, 0, 1)) }}</span>
                    <span class="call-placeholder-text">
                        <span class="call-placeholder-name">{{ $otherUser->name }}</span>
                        <span class="call-placeholder-state" data-remote-state>Not in the call</span>
                    </span>
                </div>

                <span class="call-remote-muted" data-remote-muted hidden>🔇 Muted</span>

                <div class="call-self" data-local-tile hidden>
                    <video data-local-video autoplay playsinline muted hidden></video>
                    <div class="call-self-placeholder" data-local-placeholder>
                        <span class="call-avatar-circle small" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="call-self-text">
                            <span class="call-self-name">You</span>
                            <span class="call-self-state" data-local-state>Joined</span>
                        </span>
                    </div>
                    <span class="call-self-muted" data-local-muted hidden aria-label="Your microphone is muted">🔇</span>
                </div>

                <audio data-remote-audio autoplay></audio>
            </div>

            <p class="call-notice" data-call-notice role="alert" hidden></p>

            <button type="button" class="btn btn-success btn-block" data-call-join>📞 Join Call</button>

            <div class="call-controls" data-call-controls hidden>
                <button type="button" class="call-control" data-call-mic aria-pressed="false">🎤 <span data-label>Mute</span></button>
                <button type="button" class="call-control" data-call-camera aria-pressed="false">📹 <span data-label>Camera On</span></button>
                <button type="button" class="call-control danger" data-call-leave>📞 <span>Leave</span></button>
            </div>

            <p class="call-footnote">
                You join with voice only; turn your camera on whenever you like. Audio and video travel directly between you and {{ $otherUser->name }} and are never recorded.
            </p>
        @else
            <div class="call-locked">
                @if ($isCompleted)
                    <span class="call-locked-icon" aria-hidden="true">✓</span>
                    <p><strong>Calls are closed.</strong></p>
                    <p class="small muted">This skill swap is completed, so no new calls can be started.</p>
                @else
                    <span class="call-locked-icon" aria-hidden="true">🔒</span>
                    <p><strong>Calls unlock once the session is confirmed.</strong></p>
                    <p class="small muted">Settle on a session time with {{ $otherUser->name }} first, then you can talk right here.</p>
                @endif
            </div>
        @endif
    </div>
</section>

@if ($canCall)
    <script>
        (() => {
            const panel = document.querySelector('[data-call-panel]');

            if (! panel) {
                return;
            }

            const find = (name) => panel.querySelector(`[data-${name}]`);
            const workspace = document.querySelector('[data-workspace]');
            const callTab = document.querySelector('[data-workspace-tab="call"]');

            const signalUrl = panel.dataset.signalUrl;
            const isOfferer = panel.dataset.callRole === 'offerer';
            const peerName = panel.dataset.peerName;
            const csrfToken = panel.dataset.csrfToken;
            const iceServers = (() => {
                try {
                    return JSON.parse(panel.dataset.iceServers || '[]');
                } catch (error) {
                    return [];
                }
            })();

            const IDLE_POLL_MS = 4000;
            const NEGOTIATING_POLL_MS = 1000;
            const CONNECTED_POLL_MS = 2000;
            const MAX_RESTARTS = 3;

            const ui = {
                status: find('call-status'),
                notice: find('call-notice'),
                join: find('call-join'),
                controls: find('call-controls'),
                mic: find('call-mic'),
                camera: find('call-camera'),
                leave: find('call-leave'),
                remoteVideo: find('remote-video'),
                remoteAudio: find('remote-audio'),
                remotePlaceholder: find('remote-placeholder'),
                remoteState: find('remote-state'),
                remoteMuted: find('remote-muted'),
                localTile: find('local-tile'),
                localVideo: find('local-video'),
                localPlaceholder: find('local-placeholder'),
                localMuted: find('local-muted'),
                localState: find('local-state'),
            };

            const state = {
                inCall: false,
                joining: false,
                disabled: false,
                generation: 0,
                cursor: Number(panel.dataset.signalCursor) || 0,
                audioTrack: null,
                videoTrack: null,
                pc: null,
                callId: null,
                audioTransceiver: null,
                videoTransceiver: null,
                remoteCandidates: [],
                localCandidates: [],
                descriptionSent: false,
                connected: false,
                everConnected: false,
                restarts: 0,
                peerInCall: false,
                peerAudio: true,
                peerVideo: false,
                pollTimer: null,
                polling: false,
            };

            /* ---------- UI helpers ---------- */

            const setStatus = (text, tone = 'idle') => {
                ui.status.textContent = text;
                ui.status.className = `call-status tone-${tone}`;
            };

            const showNotice = (text, tone = 'error') => {
                ui.notice.textContent = text;
                ui.notice.className = `call-notice ${tone}`;
                ui.notice.hidden = false;
            };

            const clearNotice = () => {
                ui.notice.hidden = true;
                ui.notice.textContent = '';
            };

            const describeMediaError = (error, device) => {
                switch (error?.name) {
                    case 'NotAllowedError':
                    case 'SecurityError':
                        return `${device === 'microphone' ? 'Microphone' : 'Camera'} permission was denied. You can allow it from your browser's site settings.`;
                    case 'NotFoundError':
                    case 'OverconstrainedError':
                        return `No ${device} was found on this device.`;
                    case 'NotReadableError':
                    case 'AbortError':
                        return `Your ${device} is unavailable. It may be in use by another app.`;
                    default:
                        return `Could not access your ${device}.`;
                }
            };

            const updateLayout = () => {
                const videoActive = state.inCall && (Boolean(state.videoTrack) || (state.connected && state.peerVideo));

                panel.classList.toggle('has-video', videoActive);
                panel.classList.toggle('in-call', state.inCall);
                panel.classList.toggle('is-connected', state.inCall && state.connected);
                ui.localState.textContent = state.connected ? 'Connected' : 'Joined';
                workspace?.classList.toggle('call-video-active', videoActive);
                callTab?.classList.toggle('is-live', state.inCall);
            };

            const renderLocal = () => {
                const hasVideo = Boolean(state.videoTrack);
                const micOn = Boolean(state.audioTrack?.enabled);

                ui.join.hidden = state.inCall;
                ui.controls.hidden = ! state.inCall;
                ui.localTile.hidden = ! state.inCall;
                ui.localVideo.hidden = ! hasVideo;
                ui.localPlaceholder.hidden = hasVideo;
                ui.localMuted.hidden = ! state.inCall || micOn;

                if (! hasVideo) {
                    ui.localVideo.srcObject = null;
                } else if (ui.localVideo.srcObject?.getVideoTracks()[0] !== state.videoTrack) {
                    ui.localVideo.srcObject = new MediaStream([state.videoTrack]);
                }

                ui.mic.setAttribute('aria-pressed', micOn ? 'false' : 'true');
                ui.mic.classList.toggle('is-off', state.inCall && ! micOn);
                ui.mic.querySelector('[data-label]').textContent = state.audioTrack
                    ? (micOn ? 'Mute' : 'Unmute')
                    : 'Enable Mic';

                ui.camera.setAttribute('aria-pressed', hasVideo ? 'true' : 'false');
                ui.camera.classList.toggle('is-on', hasVideo);
                ui.camera.querySelector('[data-label]').textContent = hasVideo ? 'Camera Off' : 'Camera On';

                updateLayout();
            };

            const renderRemote = () => {
                const remoteTrack = ui.remoteVideo.srcObject?.getVideoTracks()[0];
                const showVideo = state.inCall && state.connected && state.peerVideo && Boolean(remoteTrack);

                ui.remoteVideo.hidden = ! showVideo;
                ui.remotePlaceholder.hidden = showVideo;
                ui.remoteMuted.hidden = ! (state.inCall && state.connected && ! state.peerAudio);

                if (! state.peerInCall) {
                    ui.remoteState.textContent = 'Not in the call';
                } else if (! state.inCall) {
                    ui.remoteState.textContent = 'In the call';
                } else if (! state.connected) {
                    ui.remoteState.textContent = 'Connecting…';
                } else {
                    ui.remoteState.textContent = state.peerVideo ? 'Starting video…' : 'Camera off';
                }

                if (showVideo) {
                    ui.remoteVideo.play().catch(() => {});
                }

                updateLayout();
            };

            /* ---------- Signaling transport ---------- */

            const accessLost = (response) => response.type === 'opaqueredirect'
                || [401, 403, 404, 419].includes(response.status);

            const sendSignal = async (type, payload = null) => {
                const response = await fetch(signalUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    redirect: 'manual',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload === null ? { type } : { type, payload }),
                });

                if (accessLost(response)) {
                    disableCalling();

                    throw new Error('call-access-lost');
                }

                if (! response.ok) {
                    throw new Error(`call-signal-failed-${response.status}`);
                }

                return response.json();
            };

            const sendMediaState = () => {
                if (! state.inCall) {
                    return;
                }

                sendSignal('media', {
                    audio: Boolean(state.audioTrack?.enabled),
                    video: Boolean(state.videoTrack),
                }).catch(() => {});
            };

            /* ---------- Local media ---------- */

            const stopLocalMedia = () => {
                state.audioTrack?.stop();
                state.videoTrack?.stop();
                state.audioTrack = null;
                state.videoTrack = null;
            };

            const attachLocalTracks = async () => {
                await state.audioTransceiver?.sender.replaceTrack(state.audioTrack);
                await state.videoTransceiver?.sender.replaceTrack(state.videoTrack);
            };

            /* ---------- Peer connection ---------- */

            const newCallId = () => window.crypto?.randomUUID?.()
                ?? `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`;

            const resetPeerConnection = () => {
                if (state.pc) {
                    const pc = state.pc;

                    state.pc = null;
                    pc.onicecandidate = null;
                    pc.ontrack = null;
                    pc.onconnectionstatechange = null;
                    pc.oniceconnectionstatechange = null;
                    pc.close();
                }

                Object.assign(state, {
                    callId: null,
                    audioTransceiver: null,
                    videoTransceiver: null,
                    remoteCandidates: [],
                    localCandidates: [],
                    descriptionSent: false,
                    connected: false,
                    everConnected: false,
                    peerAudio: true,
                    peerVideo: false,
                });

                ui.remoteVideo.srcObject = null;
                ui.remoteAudio.srcObject = null;
                renderRemote();
            };

            const handleConnectionState = (pc) => {
                if (pc !== state.pc) {
                    return;
                }

                const connectionState = pc.connectionState || pc.iceConnectionState;

                if (connectionState === 'connected' || connectionState === 'completed') {
                    if (! state.connected) {
                        state.connected = true;
                        state.everConnected = true;
                        state.peerInCall = true;
                        state.restarts = 0;
                        clearNotice();
                        setStatus(`Connected with ${peerName}`, 'connected');
                        sendMediaState();
                        renderRemote();
                    }

                    return;
                }

                if (connectionState === 'disconnected') {
                    state.connected = false;
                    setStatus('Reconnecting…', 'connecting');

                    return;
                }

                if (connectionState === 'failed') {
                    state.connected = false;
                    setStatus('Connection lost', 'error');
                    renderRemote();

                    if (! state.everConnected) {
                        showNotice('Could not open a direct connection. Some networks block peer-to-peer calls and need a TURN relay server.', 'warning');
                    }

                    if (isOfferer && state.peerInCall && state.restarts < MAX_RESTARTS) {
                        state.restarts += 1;
                        setTimeout(() => {
                            if (state.inCall && state.pc === pc) {
                                startNegotiation();
                            }
                        }, 1500);
                    }
                }
            };

            const createPeerConnection = () => {
                const pc = new RTCPeerConnection({ iceServers });

                pc.onicecandidate = ({ candidate }) => {
                    if (pc !== state.pc || ! candidate) {
                        return;
                    }

                    const signal = { call_id: state.callId, candidate: candidate.toJSON() };

                    // Candidates must never reach the other side before the description they belong to.
                    if (state.descriptionSent) {
                        sendSignal('candidate', signal).catch(() => {});
                    } else {
                        state.localCandidates.push(signal);
                    }
                };

                pc.ontrack = ({ track }) => {
                    if (pc !== state.pc) {
                        return;
                    }

                    if (track.kind === 'audio') {
                        ui.remoteAudio.srcObject = new MediaStream([track]);
                        ui.remoteAudio.play().catch(() => {});
                    } else {
                        ui.remoteVideo.srcObject = new MediaStream([track]);
                        track.onunmute = renderRemote;
                    }

                    renderRemote();
                };

                pc.onconnectionstatechange = () => handleConnectionState(pc);
                pc.oniceconnectionstatechange = () => handleConnectionState(pc);

                return pc;
            };

            const flushLocalCandidates = () => {
                state.descriptionSent = true;
                state.localCandidates.splice(0).forEach((signal) => sendSignal('candidate', signal).catch(() => {}));
            };

            const flushRemoteCandidates = async () => {
                const pc = state.pc;

                for (const candidate of state.remoteCandidates.splice(0)) {
                    await pc?.addIceCandidate(candidate).catch(() => {});
                }
            };

            // The swap sender always creates the offer, so both sides never offer at once.
            const startNegotiation = async () => {
                if (! state.inCall || ! isOfferer) {
                    return;
                }

                resetPeerConnection();

                const pc = createPeerConnection();

                state.pc = pc;
                state.callId = newCallId();
                state.audioTransceiver = pc.addTransceiver('audio', { direction: 'sendrecv' });
                state.videoTransceiver = pc.addTransceiver('video', { direction: 'sendrecv' });
                setStatus('Connecting…', 'connecting');

                try {
                    await attachLocalTracks();
                    await pc.setLocalDescription(await pc.createOffer());

                    if (pc !== state.pc) {
                        return;
                    }

                    await sendSignal('offer', { call_id: state.callId, sdp: pc.localDescription.sdp });

                    if (pc === state.pc) {
                        flushLocalCandidates();
                    }
                } catch (error) {
                    if (pc === state.pc && error.message !== 'call-access-lost') {
                        setStatus('Connection lost', 'error');
                    }
                }
            };

            const handleOffer = async ({ call_id: callId, sdp }) => {
                if (! state.inCall || isOfferer) {
                    return;
                }

                resetPeerConnection();

                const pc = createPeerConnection();

                state.pc = pc;
                state.callId = callId;
                setStatus('Connecting…', 'connecting');

                await pc.setRemoteDescription({ type: 'offer', sdp });

                pc.getTransceivers().forEach((transceiver) => {
                    transceiver.direction = 'sendrecv';

                    if (transceiver.receiver.track.kind === 'audio') {
                        state.audioTransceiver ??= transceiver;
                    } else if (transceiver.receiver.track.kind === 'video') {
                        state.videoTransceiver ??= transceiver;
                    }
                });

                await attachLocalTracks();
                await pc.setLocalDescription(await pc.createAnswer());

                if (pc !== state.pc) {
                    return;
                }

                await flushRemoteCandidates();
                await sendSignal('answer', { call_id: callId, sdp: pc.localDescription.sdp });

                if (pc === state.pc) {
                    flushLocalCandidates();
                }
            };

            const handleAnswer = async ({ call_id: callId, sdp }) => {
                const pc = state.pc;

                if (! isOfferer || ! pc || callId !== state.callId || pc.signalingState !== 'have-local-offer') {
                    return;
                }

                await pc.setRemoteDescription({ type: 'answer', sdp });
                await flushRemoteCandidates();
            };

            const handleCandidate = async ({ call_id: callId, candidate }) => {
                const pc = state.pc;

                if (! pc || callId !== state.callId) {
                    return;
                }

                if (! pc.remoteDescription) {
                    state.remoteCandidates.push(candidate);

                    return;
                }

                await pc.addIceCandidate(candidate).catch(() => {});
            };

            /* ---------- Presence ---------- */

            const handlePeerJoined = async () => {
                state.peerInCall = true;
                state.restarts = 0;

                if (! state.inCall) {
                    setStatus(`${peerName} is in the call`, 'waiting');
                    renderRemote();

                    return;
                }

                setStatus(`${peerName} joined`, 'connecting');
                renderRemote();

                if (isOfferer) {
                    await startNegotiation();
                }
            };

            const handlePeerLeft = () => {
                state.peerInCall = false;

                if (state.inCall) {
                    resetPeerConnection();
                    setStatus(`${peerName} left the call`, 'waiting');
                } else {
                    setStatus('Not in call', 'idle');
                }

                renderRemote();
            };

            const updatePeerPresence = (peerInCall) => {
                // A live peer connection is stronger evidence than a missed poll.
                if (state.connected || peerInCall === state.peerInCall) {
                    return;
                }

                state.peerInCall = peerInCall;

                if (! state.inCall) {
                    setStatus(peerInCall ? `${peerName} is in the call` : 'Not in call', peerInCall ? 'waiting' : 'idle');
                } else if (peerInCall) {
                    setStatus('Connecting…', 'connecting');

                    if (isOfferer && ! state.pc) {
                        startNegotiation();
                    }
                } else {
                    const hadConnected = state.everConnected;

                    resetPeerConnection();
                    setStatus(hadConnected ? 'Connection lost' : `Waiting for ${peerName}…`, hadConnected ? 'error' : 'waiting');
                }

                renderRemote();
            };

            const handleSignal = async (signal) => {
                switch (signal.type) {
                    case 'join':
                        return handlePeerJoined();
                    case 'leave':
                        return handlePeerLeft();
                    case 'offer':
                        return handleOffer(signal.payload);
                    case 'answer':
                        return handleAnswer(signal.payload);
                    case 'candidate':
                        return handleCandidate(signal.payload);
                    case 'media':
                        state.peerAudio = Boolean(signal.payload?.audio);
                        state.peerVideo = Boolean(signal.payload?.video);
                        renderRemote();

                        return undefined;
                    default:
                        return undefined;
                }
            };

            /* ---------- Polling ---------- */

            const poll = async () => {
                if (state.polling || state.disabled) {
                    return;
                }

                state.polling = true;
                const generation = state.generation;

                try {
                    const query = new URLSearchParams({
                        after: String(state.cursor),
                        in_call: state.inCall ? '1' : '0',
                    });
                    const response = await fetch(`${signalUrl}?${query}`, {
                        credentials: 'same-origin',
                        redirect: 'manual',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (accessLost(response)) {
                        disableCalling();

                        return;
                    }

                    // Joined or left while this request was in flight: its view of the call is stale.
                    if (! response.ok || generation !== state.generation) {
                        return;
                    }

                    const data = await response.json();

                    for (const signal of data.signals) {
                        if (generation !== state.generation) {
                            return;
                        }

                        state.cursor = Math.max(state.cursor, signal.id);

                        try {
                            await handleSignal(signal);
                        } catch (error) {
                            // One unusable signal must not break the call.
                        }
                    }

                    state.cursor = Math.max(state.cursor, Number(data.cursor) || 0);
                    updatePeerPresence(Boolean(data.peer_in_call));
                } catch (error) {
                    // Temporary network failure: try again on the next tick.
                } finally {
                    state.polling = false;
                }
            };

            const schedulePoll = (delay = null) => {
                clearTimeout(state.pollTimer);

                if (state.disabled) {
                    return;
                }

                const interval = delay ?? (state.inCall
                    ? (state.connected ? CONNECTED_POLL_MS : NEGOTIATING_POLL_MS)
                    : IDLE_POLL_MS);

                state.pollTimer = setTimeout(async () => {
                    await poll();
                    schedulePoll();
                }, interval);
            };

            /* ---------- Call actions ---------- */

            const warnBeforeUnload = (event) => {
                event.preventDefault();
                event.returnValue = '';
            };

            const join = async () => {
                if (state.inCall || state.joining || state.disabled) {
                    return;
                }

                if (! window.RTCPeerConnection || ! navigator.mediaDevices?.getUserMedia) {
                    showNotice('Calling needs a browser with WebRTC support, and the site must be opened over HTTPS (or localhost).');

                    return;
                }

                state.joining = true;
                ui.join.disabled = true;
                clearNotice();
                setStatus('Connecting…', 'connecting');

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });

                    state.audioTrack = stream.getAudioTracks()[0] ?? null;
                } catch (error) {
                    state.audioTrack = null;
                    showNotice(`${describeMediaError(error, 'microphone')} You joined without a microphone, so you can listen but ${peerName} can't hear you.`, 'warning');
                }

                try {
                    const result = await sendSignal('join');

                    state.cursor = Math.max(state.cursor, Number(result.cursor) || 0);
                    state.peerInCall = Boolean(result.peer_in_call);
                } catch (error) {
                    stopLocalMedia();

                    if (error.message !== 'call-access-lost') {
                        setStatus('Not in call', 'idle');
                        showNotice('Could not join the call. Check your connection and try again.');
                    }

                    return;
                } finally {
                    state.joining = false;
                    ui.join.disabled = state.disabled;
                }

                state.inCall = true;
                state.generation += 1;
                state.restarts = 0;
                window.addEventListener('beforeunload', warnBeforeUnload);
                renderLocal();

                if (state.peerInCall) {
                    setStatus('Connecting…', 'connecting');

                    if (isOfferer) {
                        startNegotiation();
                    }
                } else {
                    setStatus(`Waiting for ${peerName}…`, 'waiting');
                }

                renderRemote();
                schedulePoll(0);
            };

            const leave = ({ notify = true, status = 'Call ended', tone = 'idle' } = {}) => {
                if (! state.inCall) {
                    return;
                }

                state.inCall = false;
                state.generation += 1;
                window.removeEventListener('beforeunload', warnBeforeUnload);
                resetPeerConnection();
                stopLocalMedia();
                renderLocal();
                setStatus(status, tone);

                if (notify) {
                    sendSignal('leave').catch(() => {});
                }

                schedulePoll();
            };

            const disableCalling = () => {
                if (state.disabled) {
                    return;
                }

                state.disabled = true;
                clearTimeout(state.pollTimer);
                leave({ notify: false, status: 'Call unavailable', tone: 'error' });
                setStatus('Call unavailable', 'error');
                ui.join.disabled = true;
                showNotice('Calling is no longer available here. Refresh the page to see the latest session status.');
            };

            const toggleMic = async () => {
                if (! state.inCall) {
                    return;
                }

                if (state.audioTrack) {
                    state.audioTrack.enabled = ! state.audioTrack.enabled;
                } else {
                    ui.mic.disabled = true;

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                        const track = stream.getAudioTracks()[0] ?? null;

                        if (! state.inCall) {
                            track?.stop();

                            return;
                        }

                        state.audioTrack = track;
                        await state.audioTransceiver?.sender.replaceTrack(track);
                        clearNotice();
                    } catch (error) {
                        showNotice(describeMediaError(error, 'microphone'), 'warning');
                    } finally {
                        ui.mic.disabled = false;
                    }
                }

                renderLocal();
                sendMediaState();
            };

            const turnCameraOff = async () => {
                state.videoTrack?.stop();
                state.videoTrack = null;
                await state.videoTransceiver?.sender.replaceTrack(null).catch(() => {});
                renderLocal();
                sendMediaState();
            };

            const toggleCamera = async () => {
                if (! state.inCall) {
                    return;
                }

                if (state.videoTrack) {
                    await turnCameraOff();

                    return;
                }

                ui.camera.disabled = true;

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: { width: { ideal: 1280 }, height: { ideal: 720 } },
                    });
                    const track = stream.getVideoTracks()[0] ?? null;

                    if (! state.inCall) {
                        track?.stop();

                        return;
                    }

                    state.videoTrack = track;

                    // Unplugged camera or revoked permission.
                    track?.addEventListener('ended', () => {
                        if (state.videoTrack === track) {
                            turnCameraOff();
                        }
                    });

                    await state.videoTransceiver?.sender.replaceTrack(track);
                    clearNotice();
                } catch (error) {
                    showNotice(`${describeMediaError(error, 'camera')} You can keep talking with voice only.`, 'warning');
                } finally {
                    ui.camera.disabled = false;
                }

                renderLocal();
                sendMediaState();
            };

            ui.join.addEventListener('click', join);
            ui.leave.addEventListener('click', () => leave());
            ui.mic.addEventListener('click', toggleMic);
            ui.camera.addEventListener('click', toggleCamera);

            window.addEventListener('pagehide', () => {
                if (! state.inCall) {
                    return;
                }

                const data = new FormData();
                data.append('_token', csrfToken);
                data.append('type', 'leave');
                navigator.sendBeacon?.(signalUrl, data);
                stopLocalMedia();
            });

            renderLocal();
            renderRemote();
            poll().finally(() => schedulePoll());
        })();
    </script>
@endif
