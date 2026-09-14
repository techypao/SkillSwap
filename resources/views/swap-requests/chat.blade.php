@php
    use App\Models\SkillSession;

    $skillSession = $swapRequest->skillSession;
    $isCompleted = $skillSession?->status === SkillSession::STATUS_COMPLETED;
    $hasActiveSession = $skillSession && $skillSession->status !== SkillSession::STATUS_CANCELLED;
    $sessionEndsAt = $skillSession?->endsAt();
    $sessionHasEnded = $skillSession?->status === SkillSession::STATUS_CONFIRMED && $skillSession->hasEnded();
    $meetingDetailsIsLink = $skillSession?->meeting_details
        && \Illuminate\Support\Str::startsWith($skillSession->meeting_details, ['http://', 'https://'])
        && filter_var($skillSession->meeting_details, FILTER_VALIDATE_URL);
    $formatMeetingType = fn (string $meetingType): string => ucwords(str_replace('_', ' ', $meetingType));
    $proposalFields = ['teaching_side', 'date', 'time', 'duration_minutes', 'meeting_type', 'meeting_details'];
    $proposalHasErrors = $errors->hasAny($proposalFields);
    $selectedDuration = old('duration_minutes', 60);
    $selectedMeetingType = old('meeting_type', SkillSession::MEETING_TYPE_ONLINE);
    $initialWorkspacePanel = $proposalHasErrors ? 'session' : 'chat';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Swap Chat with {{ $otherUser->name }} - SkillSwap</title>

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #1f2937; display: flex; flex-direction: column; height: 100vh; height: 100dvh; overflow: hidden; }
        .navbar { background: white; border-bottom: 1px solid #e5e7eb; flex: none; padding: 16px 30px; display: flex; align-items: center; justify-content: space-between; }
        .brand { color: #2563eb; font-size: 22px; font-weight: bold; text-decoration: none; }
        .navbar-right { display: flex; align-items: center; gap: 10px; }
        .logout-button, .btn { border: 0; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; padding: 10px 15px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .logout-button { border: 1px solid #d1d5db; background: white; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #15803d; color: white; }
        .btn-success:hover { background: #166534; }
        .btn-outline-danger { background: white; border: 1px solid #fca5a5; color: #b91c1c; }
        .btn-outline-danger:hover { background: #fef2f2; }
        .btn-secondary { background: white; border: 1px solid #d1d5db; color: #374151; }
        .btn-secondary:hover { background: #f3f4f6; }
        .btn-block { width: 100%; }
        .btn:disabled { cursor: progress; opacity: .6; }
        .muted { color: #6b7280; }
        .small { font-size: 13px; }
        .visually-hidden { border: 0; clip: rect(0 0 0 0); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute; white-space: nowrap; width: 1px; }

        .alert { border-radius: 10px; margin-bottom: 8px; padding: 10px 14px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-info { background: #eff6ff; color: #1d4ed8; }
        .alert-error { background: #fef2f2; color: #b91c1c; }

        .badge { border-radius: 999px; display: inline-block; font-size: 11px; font-weight: bold; letter-spacing: .05em; padding: 5px 10px; text-transform: uppercase; white-space: nowrap; }
        .badge-discussing { background: #eff6ff; color: #1d4ed8; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-respond { background: #ffedd5; color: #c2410c; }
        .badge-confirmed { background: #dcfce7; color: #166534; }
        .badge-awaiting { background: #ede9fe; color: #6d28d9; }
        .badge-completed { background: #e5e7eb; color: #374151; }

        /* One full-height application workspace */
        .workspace { background: white; display: grid; flex: 1; grid-template-columns: minmax(200px, 16fr) minmax(250px, 21fr) minmax(0, 40fr) minmax(290px, 25fr); min-height: 0; }
        .workspace.call-video-active { grid-template-columns: minmax(200px, 15fr) minmax(320px, 29fr) minmax(0, 33fr) minmax(290px, 23fr); }
        .workspace > [data-workspace-panel] { min-height: 0; min-width: 0; }
        .workspace-tabs { display: none; }
        .workspace-tab { background: transparent; border: 0; border-radius: 8px; color: #4b5563; cursor: pointer; font-size: 14px; font-weight: bold; padding: 9px 4px; }
        .workspace-tab[aria-selected="true"] { background: #2563eb; color: white; }
        .live-dot { background: #16a34a; border-radius: 999px; display: none; height: 8px; margin-left: 6px; vertical-align: middle; width: 8px; }
        .workspace-tab.is-live .live-dot { display: inline-block; }

        /* Swaps sidebar */
        .swaps-sidebar { background: #eef2f7; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .swaps-header { border-bottom: 1px solid #e2e8f0; padding: 15px 14px 13px; }
        .swaps-header h2 { color: #334155; font-size: 12px; letter-spacing: .08em; margin: 0; text-transform: uppercase; }
        .swaps-scroll { flex: 1; min-height: 0; overflow-y: auto; padding: 10px 8px 16px; }
        .swap-group + .swap-group { margin-top: 16px; }
        .swap-group-label { align-items: center; color: #64748b; display: flex; font-size: 11px; font-weight: bold; gap: 6px; letter-spacing: .06em; margin: 0 6px 6px; text-transform: uppercase; }
        details.swap-group > summary { cursor: pointer; list-style: none; }
        details.swap-group > summary::-webkit-details-marker { display: none; }
        details.swap-group > summary::before { content: '▸'; }
        details.swap-group[open] > summary::before { content: '▾'; }
        .swap-count { background: #e2e8f0; border-radius: 999px; color: #475569; font-size: 10px; letter-spacing: 0; padding: 1px 6px; }
        .swap-item { align-items: flex-start; border-radius: 8px; color: #1f2937; display: flex; gap: 9px; margin-bottom: 2px; padding: 8px; position: relative; text-decoration: none; }
        .swap-item:hover { background: #e2e8f0; }
        .swap-item.is-selected { background: white; box-shadow: 0 1px 2px rgba(15, 23, 42, .08); }
        .swap-item.is-selected::before { background: #2563eb; border-radius: 0 4px 4px 0; content: ''; height: 60%; left: -8px; position: absolute; top: 20%; width: 4px; }
        .swap-avatar { align-items: center; background: #dbeafe; border-radius: 999px; color: #1d4ed8; display: inline-flex; flex: none; font-size: 13px; font-weight: bold; height: 32px; justify-content: center; position: relative; width: 32px; }
        .swap-item.is-pending .swap-avatar { background: #fef3c7; color: #92400e; }
        .swap-item.is-history .swap-avatar { background: #e5e7eb; color: #4b5563; }
        .swap-item.is-history { color: #475569; }
        .swap-status-dot { background: #94a3b8; border: 2px solid #eef2f7; border-radius: 999px; bottom: -1px; height: 11px; position: absolute; right: -1px; width: 11px; }
        .swap-item.is-selected .swap-status-dot { border-color: white; }
        .stage-dot-discussing { background: #3b82f6; }
        .stage-dot-pending { background: #f59e0b; }
        .stage-dot-respond { background: #f97316; }
        .stage-dot-confirmed { background: #16a34a; }
        .stage-dot-awaiting { background: #8b5cf6; }
        .swap-item-body { display: flex; flex-direction: column; min-width: 0; }
        .swap-item-name { font-size: 14px; font-weight: bold; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .swap-item-meta { color: #475569; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .swap-item-status { color: #64748b; font-size: 11px; margin-top: 1px; }
        .swaps-empty { background: white; border: 1px dashed #cbd5e1; border-radius: 10px; color: #475569; font-size: 13px; margin: 0 4px; padding: 12px; text-align: center; }
        .swaps-empty p { margin: 0 0 6px; }
        .swaps-empty .btn { margin-top: 6px; }

        /* Voice & video */
        .call { background: #f8fafc; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; overflow-y: auto; }
        .call [hidden] { display: none !important; }
        .call-header { align-items: center; border-bottom: 1px solid #e5e7eb; display: flex; gap: 8px; justify-content: space-between; padding: 13px 14px; }
        .call-header h2 { color: #334155; font-size: 12px; letter-spacing: .08em; margin: 0; text-transform: uppercase; }
        .call-status { border-radius: 999px; font-size: 11px; font-weight: bold; padding: 4px 9px; text-align: right; }
        .tone-idle { background: #f3f4f6; color: #4b5563; }
        .tone-waiting { background: #fef3c7; color: #92400e; }
        .tone-connecting { background: #eff6ff; color: #1d4ed8; }
        .tone-connected { background: #dcfce7; color: #166534; }
        .tone-error { background: #fef2f2; color: #b91c1c; }
        .call-body { display: flex; flex-direction: column; gap: 10px; padding: 14px; }

        .call-stage { display: flex; flex-direction: column; gap: 8px; }
        .call.has-video .call-stage { aspect-ratio: 3 / 4; background: #0f172a; border-radius: 12px; display: block; overflow: hidden; position: relative; }
        .call-remote-video { background: #0f172a; display: block; height: 100%; object-fit: cover; width: 100%; }
        .call-placeholder { align-items: center; display: flex; gap: 10px; }
        .call-placeholder-text { display: flex; flex-direction: column; min-width: 0; }
        .call-placeholder-name { font-weight: bold; overflow-wrap: anywhere; }
        .call-placeholder-state { color: #64748b; font-size: 13px; }
        .call-avatar-circle { align-items: center; background: #2563eb; border-radius: 999px; color: white; display: inline-flex; flex: none; font-size: 30px; font-weight: bold; height: 72px; justify-content: center; width: 72px; }
        .call-avatar-circle.small, .call.in-call:not(.has-video) .call-avatar-circle { font-size: 15px; height: 34px; width: 34px; }
        .call:not(.in-call) .call-placeholder { flex-direction: column; justify-content: center; padding: 20px 8px 6px; text-align: center; }
        .call.in-call:not(.has-video) .call-placeholder,
        .call.in-call:not(.has-video) .call-self { background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 9px 10px; }
        .call.in-call .call-self .call-avatar-circle,
        .call.is-connected .call-placeholder .call-avatar-circle { box-shadow: 0 0 0 3px #86efac; }
        .call.has-video .call-placeholder { color: white; flex-direction: column; height: 100%; justify-content: center; padding: 16px; text-align: center; }
        .call.has-video .call-placeholder-state { color: #cbd5e1; }
        .call-remote-muted { align-self: flex-start; background: #fef2f2; border-radius: 999px; color: #b91c1c; font-size: 12px; padding: 4px 9px; }
        .call.has-video .call-remote-muted { background: rgba(15, 23, 42, .75); color: white; left: 10px; position: absolute; top: 10px; }
        .call-self { align-items: center; display: flex; order: -1; position: relative; }
        .call.has-video .call-self { aspect-ratio: 4 / 3; background: #1e293b; border: 2px solid rgba(255, 255, 255, .85); border-radius: 10px; bottom: 10px; min-width: 88px; overflow: hidden; position: absolute; right: 10px; width: 34%; }
        .call-self video { display: block; height: 100%; object-fit: cover; transform: scaleX(-1); width: 100%; }
        .call-self-placeholder { align-items: center; display: flex; gap: 10px; }
        .call.has-video .call-self-placeholder { color: white; flex-direction: column; font-size: 11px; gap: 3px; height: 100%; justify-content: center; width: 100%; }
        .call-self-text { display: flex; flex-direction: column; }
        .call-self-name { font-weight: bold; }
        .call-self-state { color: #64748b; font-size: 13px; }
        .call.has-video .call-self-state { display: none; }
        .call-self-muted { margin-left: auto; }
        .call.has-video .call-self-muted { bottom: 3px; font-size: 12px; left: 5px; margin: 0; position: absolute; }

        .call-notice { border-radius: 10px; font-size: 13px; margin: 0; padding: 9px 11px; }
        .call-notice.error { background: #fef2f2; color: #b91c1c; }
        .call-notice.warning { background: #fffbeb; color: #92400e; }
        .call-controls { display: grid; gap: 6px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .call-control { background: white; border: 1px solid #e5e7eb; border-radius: 10px; color: #1f2937; cursor: pointer; font-size: 13px; font-weight: bold; padding: 10px 6px; }
        .call-control:hover { background: #f3f4f6; }
        .call-control.is-off { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .call-control.is-on { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .call-control.danger { background: #dc2626; border-color: #dc2626; color: white; }
        .call-control.danger:hover { background: #b91c1c; }
        .call-control:disabled { cursor: progress; opacity: .6; }
        .call-footnote { color: #6b7280; font-size: 12px; margin: 0; }
        .call-locked { color: #374151; padding: 18px 4px; text-align: center; }
        .call-locked p { margin: 4px 0; }
        .call-locked-icon { font-size: 28px; }

        /* Chat */
        .chat-column { background: white; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; }
        .workspace-header { align-items: center; border-bottom: 1px solid #e5e7eb; display: flex; flex: none; gap: 12px; padding: 10px 16px; }
        .back-link { align-items: center; border: 1px solid #e5e7eb; border-radius: 999px; color: #374151; display: inline-flex; flex: none; font-size: 16px; height: 32px; justify-content: center; text-decoration: none; width: 32px; }
        .back-link:hover { background: #f3f4f6; }
        .avatar { align-items: center; background: #dbeafe; border-radius: 999px; color: #1d4ed8; display: inline-flex; flex: none; font-weight: bold; height: 38px; justify-content: center; width: 38px; }
        .header-main { flex: 1; min-width: 0; }
        .header-name { font-size: 17px; font-weight: bold; margin: 0; }
        .header-sub { color: #6b7280; font-size: 12px; margin: 1px 0 0; }
        .exchange { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 5px; }
        .skill-pill { background: #f3f4f6; border-radius: 999px; color: #374151; font-size: 12px; padding: 3px 9px; }
        .skill-pill strong { color: #111827; }
        .chat-alerts { flex: none; padding: 10px 16px 0; }

        .chat { display: flex; flex: 1; flex-direction: column; min-height: 0; position: relative; }
        .messages { display: flex; flex: 1; flex-direction: column; gap: 4px; min-height: 0; overflow-y: auto; padding: 18px; }
        .message-row { display: flex; flex-direction: column; align-items: flex-start; max-width: 78%; }
        .message-row.mine { align-self: flex-end; align-items: flex-end; }
        .message-row.new-group { margin-top: 10px; }
        .message-sender { color: #6b7280; font-size: 12px; font-weight: bold; margin: 0 4px 3px; }
        .bubble { background: #f3f4f6; border-radius: 16px 16px 16px 4px; color: #1f2937; line-height: 1.4; margin: 0; overflow-wrap: anywhere; padding: 9px 13px; white-space: pre-wrap; }
        .mine .bubble { background: #2563eb; border-radius: 16px 16px 4px 16px; color: white; }
        .message-time { color: #9ca3af; font-size: 11px; margin: 3px 4px 0; }
        .empty-chat { color: #6b7280; margin: auto; text-align: center; }
        .new-messages-button { background: #2563eb; border: 0; border-radius: 999px; bottom: 84px; box-shadow: 0 4px 12px rgba(37, 99, 235, .3); color: white; cursor: pointer; font-size: 13px; font-weight: bold; left: 50%; padding: 7px 14px; position: absolute; transform: translateX(-50%); }
        .new-messages-button[hidden] { display: none; }

        .composer { align-items: flex-end; border-top: 1px solid #e5e7eb; display: flex; flex: none; gap: 10px; padding: 12px 14px; }
        .composer textarea { border: 1px solid #d1d5db; border-radius: 12px; flex: 1; font: inherit; max-height: 140px; min-height: 44px; padding: 11px 13px; resize: none; }
        .composer textarea:focus { border-color: #2563eb; outline: 2px solid #bfdbfe; }
        .composer .btn { height: 44px; }
        .composer-error { color: #b91c1c; flex: none; font-size: 13px; margin: 0; padding: 8px 16px 0; }
        .composer-error[hidden] { display: none; }
        .chat-closed { background: #f9fafb; border-top: 1px solid #e5e7eb; color: #4b5563; flex: none; font-size: 14px; font-weight: bold; padding: 14px 18px; text-align: center; }

        /* Session */
        .sidebar { background: #f9fafb; overflow-y: auto; }
        .panel-section { padding: 16px 18px; }
        .panel-section + .panel-section { border-top: 1px solid #e5e7eb; }
        .section-label { color: #6b7280; font-size: 11px; font-weight: bold; letter-spacing: .06em; margin: 0 0 10px; text-transform: uppercase; }
        .session-heading { margin-bottom: 12px; }
        .session-heading h2 { color: #334155; font-size: 12px; letter-spacing: .08em; margin: 0; text-transform: uppercase; }
        .primary-state { font-size: 14px; font-weight: bold; letter-spacing: .05em; margin: 0 0 10px; }
        .primary-state-pending { color: #92400e; }
        .primary-state-respond { color: #c2410c; }
        .primary-state-confirmed { color: #166534; }
        .primary-state-awaiting { color: #6d28d9; }
        .session-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; gap: 14px; padding: 14px; }
        .session-card.highlight { background: #eff6ff; border-color: #bfdbfe; }
        .session-card.success { background: #f0fdf4; border-color: #bbf7d0; }
        .date-tile { background: white; border: 1px solid #e5e7eb; border-radius: 10px; flex: none; overflow: hidden; text-align: center; width: 56px; }
        .date-tile-month { background: #2563eb; color: white; font-size: 11px; font-weight: bold; letter-spacing: .05em; padding: 3px 0; text-transform: uppercase; }
        .date-tile-day { font-size: 22px; font-weight: bold; padding: 5px 0; }
        .session-facts { flex: 1; min-width: 0; }
        .session-facts p { font-size: 14px; margin: 0 0 4px; }
        .session-facts .primary { font-weight: bold; }
        .meeting-details { background: white; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; margin-top: 12px; overflow-wrap: anywhere; padding: 10px 12px; white-space: pre-wrap; }
        .meeting-details a { color: #2563eb; }
        .actions { display: grid; gap: 8px; grid-template-columns: 1fr 1fr; margin-top: 12px; }
        .actions form, .actions .btn { width: 100%; }
        .stack { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
        .note { font-size: 14px; margin: 12px 0 0; }
        .check { color: #15803d; font-weight: bold; }

        .countdown { border-radius: 12px; margin-bottom: 12px; padding: 12px 14px; }
        .countdown-upcoming { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
        .countdown-live { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .countdown-ended { background: #f3f4f6; border: 1px solid #e5e7eb; color: #4b5563; }
        .countdown-label { font-size: 12px; font-weight: bold; letter-spacing: .04em; text-transform: uppercase; }
        .countdown-time { font-size: 24px; font-weight: bold; margin: 4px 0 2px; font-variant-numeric: tabular-nums; }
        .countdown-meta { font-size: 12px; opacity: .85; }

        .availability-list { display: flex; flex-wrap: wrap; gap: 6px; list-style: none; margin: 0; padding: 0; }
        .availability-list li { background: #f3f4f6; border-radius: 8px; color: #374151; font-size: 13px; padding: 5px 9px; }
        .availability-list.common li { background: #dcfce7; color: #166534; font-weight: bold; }
        .availability-group + .availability-group { margin-top: 12px; }
        .availability-group strong { display: block; font-size: 13px; margin-bottom: 6px; }

        .completed-title { color: #166534; font-size: 16px; font-weight: bold; margin: 0 0 8px; }
        .credit { color: #15803d; font-weight: bold; }
        .stars { color: #d97706; font-size: 18px; letter-spacing: 1px; }

        .propose-toggle { margin-top: 12px; }
        .proposal-form { border-top: 1px solid #e5e7eb; margin-top: 12px; padding-top: 12px; }
        .proposal-form[hidden], .propose-toggle[hidden] { display: none; }
        .field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px; min-width: 0; }
        .field label { color: #374151; font-size: 13px; font-weight: bold; }
        .field input, .field select, .field textarea { border: 1px solid #d1d5db; border-radius: 8px; font: inherit; font-size: 14px; padding: 8px 10px; width: 100%; background: white; }
        .field textarea { resize: vertical; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color: #2563eb; outline: 2px solid #bfdbfe; }
        .field [aria-invalid="true"] { border-color: #dc2626; }
        .field-row { display: grid; gap: 10px; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .field-error { color: #b91c1c; font-size: 12px; margin: 0; }
        .field-hint { color: #6b7280; font-size: 12px; margin: 0 0 10px; }
        .duration-chips { display: flex; flex-wrap: wrap; gap: 6px; margin: -2px 0 6px; }
        .duration-chip { background: white; border: 1px solid #d1d5db; border-radius: 999px; color: #374151; cursor: pointer; font-size: 12px; font-weight: bold; padding: 4px 10px; }
        .duration-chip:hover { background: #f3f4f6; }
        .duration-chip.active { background: #eff6ff; border-color: #2563eb; color: #1d4ed8; }

        @media (max-width: 1180px) {
            .workspace, .workspace.call-video-active { grid-template-columns: minmax(0, 1fr); }
            .workspace > [data-workspace-panel] { border-right: 0; }
            .workspace-tabs { background: white; border-bottom: 1px solid #e5e7eb; display: grid; flex: none; gap: 4px; grid-template-columns: repeat(4, minmax(0, 1fr)); padding: 6px; }
            .workspace[data-active-panel="swaps"] > [data-workspace-panel]:not([data-workspace-panel="swaps"]),
            .workspace[data-active-panel="call"] > [data-workspace-panel]:not([data-workspace-panel="call"]),
            .workspace[data-active-panel="chat"] > [data-workspace-panel]:not([data-workspace-panel="chat"]),
            .workspace[data-active-panel="session"] > [data-workspace-panel]:not([data-workspace-panel="session"]) { display: none; }
            .call.has-video .call-stage { aspect-ratio: auto; height: min(60vh, 520px); }
        }

        @media (max-width: 600px) {
            .navbar { padding: 12px 14px; }
            .navbar-right span { display: none; }
            .workspace-header { padding: 8px 10px; }
            .workspace-header .avatar { display: none; }
            .messages { padding: 12px; }
            .message-row { max-width: 90%; }
            .actions, .field-row { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="brand">SkillSwap</a>
        <div class="navbar-right">
            <span>{{ auth()->user()->name }}</span>
            @include('partials.notification-bell')
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Logout</button>
            </form>
        </div>
    </nav>

    <nav class="workspace-tabs" role="tablist" aria-label="Workspace sections">
        @foreach (['swaps' => 'Swaps', 'call' => 'Call', 'chat' => 'Chat', 'session' => 'Session'] as $panelKey => $panelLabel)
            <button
                type="button"
                class="workspace-tab"
                role="tab"
                data-workspace-tab="{{ $panelKey }}"
                aria-selected="{{ $panelKey === $initialWorkspacePanel ? 'true' : 'false' }}"
            >{{ $panelLabel }}@if ($panelKey === 'call')<span class="live-dot" aria-hidden="true"></span>@endif</button>
        @endforeach
    </nav>

    <main class="workspace" data-workspace data-active-panel="{{ $initialWorkspacePanel }}">
        {{-- Swaps --}}
        @include('swap-requests.partials.swaps-sidebar')

        {{-- Voice & video --}}
        @include('swap-requests.partials.call-panel')

        {{-- Chat --}}
        <section class="chat-column" aria-label="Conversation" data-workspace-panel="chat">
            <header class="workspace-header">
                <a href="{{ route('dashboard') }}" class="back-link" aria-label="Back to Dashboard">←</a>
                <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($otherUser->name, 0, 1)) }}</span>

                <div class="header-main">
                    <h1 class="header-name">{{ $otherUser->name }}</h1>
                    @if ($otherUser->program || $otherUser->year_level)
                        <p class="header-sub">
                            {{ $otherUser->program?->name }}
                            @if ($otherUser->program && $otherUser->year_level)
                                &bull;
                            @endif
                            @if ($otherUser->year_level)
                                Year {{ $otherUser->year_level }}
                            @endif
                        </p>
                    @endif
                    <div class="exchange">
                        <span class="skill-pill">You teach: <strong>{{ $skillYouTeach->name }}</strong></span>
                        <span class="skill-pill">You learn: <strong>{{ $skillYouLearn->name }}</strong></span>
                    </div>
                </div>

                <div class="header-status">
                    <span class="badge badge-{{ $sessionStage['key'] }}" data-session-stage="{{ $sessionStage['key'] }}">
                        {{ $sessionStage['label'] }}
                    </span>
                </div>
            </header>

            @if (session('success') || session('info') || ($errors->any() && ! $proposalHasErrors))
                <div class="chat-alerts">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif

                    @if ($errors->any() && ! $proposalHasErrors)
                        <div class="alert alert-error">{{ $errors->first() }}</div>
                    @endif
                </div>
            @endif

            <div class="chat">
                <h2 class="visually-hidden">Messages</h2>

                <div
                    class="messages"
                    data-chat-messages
                    data-last-message-id="{{ $swapRequest->messages->max('id') ?? 0 }}"
                    @unless ($isCompleted)
                        data-poll-url="{{ route('swap-requests.messages.index', $swapRequest) }}"
                    @endunless
                >
                    @forelse ($swapRequest->messages as $message)
                        @php
                            $isMine = $message->sender_id === auth()->id();
                            $startsGroup = $loop->first || $swapRequest->messages[$loop->index - 1]->sender_id !== $message->sender_id;
                        @endphp
                        <article
                            class="message-row {{ $isMine ? 'mine' : '' }} {{ $startsGroup ? 'new-group' : '' }}"
                            data-message-id="{{ $message->id }}"
                            data-sender-id="{{ $message->sender_id }}"
                        >
                            @if ($startsGroup)
                                <span class="message-sender">{{ $isMine ? 'You' : $message->sender->name }}</span>
                            @endif
                            <p class="bubble">{{ $message->message }}</p>
                            <time class="message-time" datetime="{{ $message->created_at->toIso8601String() }}">
                                {{ $message->created_at->format('M j, g:i A') }}
                            </time>
                        </article>
                    @empty
                        <p class="empty-chat" data-empty-chat>No messages yet. Start discussing your swap.</p>
                    @endforelse
                </div>

                <button type="button" class="new-messages-button" data-new-messages hidden>New messages ↓</button>

                @if ($isCompleted)
                    <div class="chat-closed">This conversation is closed.</div>
                @else
                    <form method="POST" action="{{ route('swap-requests.messages.store', $swapRequest) }}" class="composer" data-composer>
                        @csrf
                        <label for="message" class="visually-hidden">Message</label>
                        <textarea id="message" name="message" rows="1" maxlength="2000" placeholder="Type a message..." required>{{ old('message') }}</textarea>
                        <button type="submit" class="btn btn-primary" aria-label="Send Message">Send ➤</button>
                    </form>
                @endif
            </div>
        </section>

        {{-- Session control center --}}
        <aside class="sidebar" aria-label="Session details" data-workspace-panel="session">
            <div class="panel-section">
                <div class="session-heading">
                    <h2>Session</h2>
                </div>

                @if ($isCompleted)
                    <div class="session-card success">
                        <div class="session-facts">
                            <p class="completed-title">✓ SKILL SWAP COMPLETED</p>
                            <p class="small muted">Both participants confirmed completion.</p>
                            <p>{{ ($skillSession->completed_at ?? $skillSession->scheduled_at)->format('F j, Y') }}</p>
                            <p class="small">{{ $skillYouTeach->name }} ↔ {{ $skillYouLearn->name }}</p>
                            <p class="credit">+1 Skill Credit earned</p>
                        </div>
                    </div>

                    <div class="stack">
                        @if ($currentUserReview)
                            <p class="stars" aria-label="{{ $currentUserReview->rating }} out of 5 stars">
                                {{ str_repeat('★', $currentUserReview->rating) }}{{ str_repeat('☆', 5 - $currentUserReview->rating) }}
                            </p>
                            <p class="check" style="margin: 0;">✓ Review Submitted</p>
                        @else
                            <a href="{{ route('reviews.create', $skillSession) }}" class="btn btn-primary btn-block">Leave Review</a>
                        @endif
                    </div>
                @elseif (! $hasActiveSession)
                    <p class="muted" style="margin-top: 0;">No session proposed yet. Discuss a time that works for both of you, then send a proposal.</p>

                    @if ($commonAvailabilities->isNotEmpty())
                        <div class="availability-group">
                            <strong>Common availability</strong>
                            <ul class="availability-list common">
                                @foreach ($commonAvailabilities as $availability)
                                    <li>✓ {{ ucfirst($availability->day) }} • {{ ucfirst($availability->time_period) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <button
                        type="button"
                        class="btn btn-primary btn-block propose-toggle"
                        data-proposal-toggle
                        aria-controls="session-proposal-form"
                        aria-expanded="{{ $proposalHasErrors ? 'true' : 'false' }}"
                        @if ($proposalHasErrors) hidden @endif
                    >Propose Session</button>

                    <form
                        method="POST"
                        action="{{ route('skill-sessions.store', $swapRequest) }}"
                        id="session-proposal-form"
                        class="proposal-form"
                        data-proposal-form
                        novalidate
                        @unless ($proposalHasErrors) hidden @endunless
                    >
                        @csrf
                        <p class="section-label">Propose a session</p>

                        <div class="field">
                            <label for="proposal_teaching_side">Who will teach this session?</label>
                            <select id="proposal_teaching_side" name="teaching_side" required @error('teaching_side') aria-invalid="true" @enderror>
                                <option value="">Choose a teacher and skill</option>
                                <option value="sender" @selected(old('teaching_side') === 'sender')>{{ $swapRequest->sender->name }} teaches {{ $swapRequest->offeredSkill->name }} to {{ $swapRequest->recipient->name }} — Learner Skill Credits: {{ $swapRequest->recipient->skill_credits }}</option>
                                <option value="recipient" @selected(old('teaching_side') === 'recipient')>{{ $swapRequest->recipient->name }} teaches {{ $swapRequest->requestedSkill->name }} to {{ $swapRequest->sender->name }} — Learner Skill Credits: {{ $swapRequest->sender->skill_credits }}</option>
                            </select>
                            <p class="field-hint">The learner needs at least 1 Skill Credit. Proposing a session does not spend credits.</p>
                            @error('teaching_side')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="proposal_date">Date</label>
                            <input id="proposal_date" type="date" name="date" value="{{ old('date') }}" min="{{ now()->format('Y-m-d') }}" required @error('date') aria-invalid="true" @enderror>
                            @error('date')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field-row">
                            <div class="field">
                                <label for="proposal_time">Time</label>
                                <input id="proposal_time" type="time" name="time" value="{{ old('time') }}" required @error('time') aria-invalid="true" @enderror>
                                @error('time')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="proposal_duration">Duration (min)</label>
                                <input
                                    id="proposal_duration"
                                    type="number"
                                    name="duration_minutes"
                                    value="{{ $selectedDuration }}"
                                    min="{{ SkillSession::MIN_DURATION_MINUTES }}"
                                    max="{{ SkillSession::MAX_DURATION_MINUTES }}"
                                    step="1"
                                    inputmode="numeric"
                                    required
                                    @error('duration_minutes') aria-invalid="true" @enderror
                                >
                            </div>
                        </div>

                        <div class="duration-chips" aria-label="Quick duration picks">
                            @foreach (SkillSession::SUGGESTED_DURATIONS as $duration)
                                <button
                                    type="button"
                                    class="duration-chip {{ (string) $duration === (string) $selectedDuration ? 'active' : '' }}"
                                    data-duration-chip="{{ $duration }}"
                                >{{ $duration }}m</button>
                            @endforeach
                        </div>
                        <p class="field-hint">
                            Type any length from {{ SkillSession::MIN_DURATION_MINUTES }} to {{ SkillSession::MAX_DURATION_MINUTES }} minutes.
                        </p>
                        @error('duration_minutes')
                            <p class="field-error">{{ $message }}</p>
                        @enderror

                        <div class="field">
                            <label for="proposal_meeting_type">Meeting Type</label>
                            <select id="proposal_meeting_type" name="meeting_type" required @error('meeting_type') aria-invalid="true" @enderror>
                                <option value="{{ SkillSession::MEETING_TYPE_ONLINE }}" @selected($selectedMeetingType === SkillSession::MEETING_TYPE_ONLINE)>Online</option>
                                <option value="{{ SkillSession::MEETING_TYPE_IN_PERSON }}" @selected($selectedMeetingType === SkillSession::MEETING_TYPE_IN_PERSON)>In Person</option>
                            </select>
                            @error('meeting_type')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="proposal_meeting_details">Meeting Details <span class="muted">(optional)</span></label>
                            <textarea id="proposal_meeting_details" name="meeting_details" rows="2" maxlength="1000" placeholder="Meeting link or location" @error('meeting_details') aria-invalid="true" @enderror>{{ old('meeting_details') }}</textarea>
                            @error('meeting_details')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="actions">
                            <button type="button" class="btn btn-secondary btn-block" data-proposal-cancel>Cancel</button>
                            <button type="submit" class="btn btn-primary btn-block">Send Proposal</button>
                        </div>
                    </form>
                @else
                    {{-- One primary state; schedule details sit underneath it. --}}
                    <p class="primary-state primary-state-{{ $sessionStage['key'] }}">
                        @if ($skillSession->status === SkillSession::STATUS_PROPOSED)
                            SESSION PROPOSAL
                        @elseif ($sessionHasEnded)
                            AWAITING COMPLETION
                        @else
                            SESSION CONFIRMED <span class="check">✓</span>
                        @endif
                    </p>

                    @if ($sessionEndsAt && $skillSession->status === SkillSession::STATUS_CONFIRMED && ! $sessionHasEnded)
                        @include('swap-requests.partials.session-countdown', ['skillSession' => $skillSession])
                    @endif

                    <div class="session-card {{ $skillSession->status === SkillSession::STATUS_CONFIRMED ? 'success' : 'highlight' }}">
                        @if ($sessionEndsAt)
                            <div class="date-tile" aria-hidden="true">
                                <div class="date-tile-month">{{ $skillSession->scheduled_at->format('M') }}</div>
                                <div class="date-tile-day">{{ $skillSession->scheduled_at->format('j') }}</div>
                            </div>
                        @endif
                        <div class="session-facts">
                            @if ($sessionEndsAt)
                                <p class="primary">{{ $skillSession->scheduled_at->format('F j, Y') }}</p>
                                <p>{{ $skillSession->scheduled_at->format('g:i A') }} – {{ $sessionEndsAt->format('g:i A') }}</p>
                                <p class="muted small">{{ $skillSession->duration_minutes }} minutes · {{ $formatMeetingType($skillSession->meeting_type) }}</p>
                            @else
                                <p class="muted small">This session cannot be marked complete because its schedule is missing or invalid.</p>
                            @endif
                            <p class="muted small">Proposed by {{ $skillSession->scheduledBy->name }}</p>
                            @if ($skillSession->hasResolvedRoles())
                                <p class="muted small">{{ $skillSession->teacher->name }} teaches {{ $skillSession->taughtSkill->name }} to {{ $skillSession->learner->name }}</p>
                            @else
                                <p class="muted small">Teaching direction was not recorded for this session.</p>
                            @endif
                        </div>
                    </div>

                    @if ($skillSession->meeting_details)
                        <div class="meeting-details"><strong>Meeting details:</strong>
@if ($meetingDetailsIsLink)<a href="{{ $skillSession->meeting_details }}" target="_blank" rel="noopener noreferrer">{{ $skillSession->meeting_details }}</a>@else{{ $skillSession->meeting_details }}@endif</div>
                    @endif

                    @if ($skillSession->status === SkillSession::STATUS_PROPOSED)
                        @if ($skillSession->scheduled_by === auth()->id())
                            <p class="note muted">Waiting for {{ $otherUser->name }} to respond.</p>
                        @else
                            <p class="note"><strong>{{ $skillSession->scheduledBy->name }}</strong> is waiting for your response.</p>
                            <div class="actions">
                                <form method="POST" action="{{ route('skill-sessions.decline', $skillSession) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-danger btn-block">Decline</button>
                                </form>
                                <form method="POST" action="{{ route('skill-sessions.agree', $skillSession) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-block">Agree</button>
                                </form>
                            </div>
                        @endif
                    @elseif ($sessionEndsAt && ! $sessionHasEnded)
                        <p class="note muted small">Completion available after the session ends.</p>
                    @endif
                @endif
            </div>

            @if ($sessionHasEnded)
                <div class="panel-section">
                    <p class="section-label">Session Completion</p>

                    @if ($currentUserConfirmedAt)
                        <p class="check" style="margin: 0;">✓ You confirmed completion.</p>
                        <p class="note muted">Waiting for {{ $otherUser->name }} to confirm.</p>
                    @else
                        @if ($otherUserConfirmedAt)
                            <p class="check" style="margin: 0 0 4px;">✓ {{ $otherUser->name }} has confirmed completion.</p>
                        @endif

                        <form method="POST" action="{{ route('skill-sessions.completion.store', $skillSession) }}" class="stack">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success btn-block">Confirm Session Completed</button>
                        </form>
                    @endif
                </div>
            @endif

            @unless ($hasActiveSession)
                <div class="panel-section" data-availability>
                    <p class="section-label">Availability</p>

                    <div class="availability-group">
                        <strong>Your availability</strong>
                        @if ($currentUserAvailabilities->isNotEmpty())
                            <ul class="availability-list">
                                @foreach ($currentUserAvailabilities as $availability)
                                    <li>{{ ucfirst($availability->day) }} • {{ ucfirst($availability->time_period) }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="muted small">No availability provided.</span>
                        @endif
                    </div>

                    <div class="availability-group">
                        <strong>{{ $otherUser->name }}'s availability</strong>
                        @if ($otherUserAvailabilities->isNotEmpty())
                            <ul class="availability-list">
                                @foreach ($otherUserAvailabilities as $availability)
                                    <li>{{ ucfirst($availability->day) }} • {{ ucfirst($availability->time_period) }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="muted small">No availability provided.</span>
                        @endif
                    </div>

                    <p class="note muted small">Availability is guidance only — you can propose any time.</p>
                </div>
            @endunless
        </aside>
    </main>

    <script>
        (() => {
            const messages = document.querySelector('[data-chat-messages]');

            if (messages) {
                messages.scrollTop = messages.scrollHeight;
            }

            const workspace = document.querySelector('[data-workspace]');
            const workspaceTabs = document.querySelectorAll('[data-workspace-tab]');

            workspaceTabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    workspace.dataset.activePanel = tab.dataset.workspaceTab;
                    workspaceTabs.forEach((otherTab) => {
                        otherTab.setAttribute('aria-selected', otherTab === tab ? 'true' : 'false');
                    });

                    if (tab.dataset.workspaceTab === 'chat' && messages) {
                        messages.scrollTop = messages.scrollHeight;
                    }
                });
            });

            let refreshMessages = () => {};

            const pollUrl = messages?.dataset.pollUrl;

            if (messages && pollUrl) {
                const POLL_INTERVAL_MS = 3000;
                const NEAR_BOTTOM_PX = 80;
                const newMessagesButton = document.querySelector('[data-new-messages]');
                let lastMessageId = Number(messages.dataset.lastMessageId) || 0;
                let pollTimer = null;
                let polling = false;

                const isNearBottom = () => messages.scrollHeight - messages.scrollTop - messages.clientHeight < NEAR_BOTTOM_PX;
                const scrollToBottom = () => {
                    messages.scrollTop = messages.scrollHeight;
                    newMessagesButton.hidden = true;
                };

                const renderMessage = (message) => {
                    const lastRow = messages.querySelector('[data-message-id]:last-of-type');
                    const startsGroup = ! lastRow || lastRow.dataset.senderId !== String(message.sender_id);

                    const row = document.createElement('article');
                    row.className = 'message-row';
                    row.classList.toggle('mine', message.is_mine);
                    row.classList.toggle('new-group', startsGroup);
                    row.dataset.messageId = String(message.id);
                    row.dataset.senderId = String(message.sender_id);

                    if (startsGroup) {
                        const sender = document.createElement('span');
                        sender.className = 'message-sender';
                        sender.textContent = message.is_mine ? 'You' : message.sender_name;
                        row.append(sender);
                    }

                    const bubble = document.createElement('p');
                    bubble.className = 'bubble';
                    bubble.textContent = message.message;

                    const time = document.createElement('time');
                    time.className = 'message-time';
                    time.dateTime = message.created_at;
                    time.textContent = message.created_at_label;

                    row.append(bubble, time);
                    messages.append(row);
                };

                const stopPolling = () => {
                    clearInterval(pollTimer);
                    pollTimer = null;
                };

                let refreshQueued = false;

                const poll = async () => {
                    if (polling) {
                        refreshQueued = true;

                        return;
                    }

                    if (document.hidden) {
                        return;
                    }

                    polling = true;

                    try {
                        const response = await fetch(`${pollUrl}?after=${lastMessageId}`, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                            redirect: 'manual',
                        });

                        // Session expired, lost access, or redirected (e.g. to login): stop quietly.
                        if (response.type === 'opaqueredirect' || [401, 403, 404, 419].includes(response.status)) {
                            stopPolling();

                            return;
                        }

                        if (! response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        const freshMessages = payload.messages.filter((message) => message.id > lastMessageId
                            && ! messages.querySelector(`[data-message-id="${message.id}"]`));

                        if (freshMessages.length > 0) {
                            const stickToBottom = isNearBottom();

                            messages.querySelector('[data-empty-chat]')?.remove();
                            freshMessages.forEach(renderMessage);

                            if (stickToBottom) {
                                scrollToBottom();
                            } else if (freshMessages.some((message) => ! message.is_mine)) {
                                newMessagesButton.hidden = false;
                            }
                        }

                        if (payload.messages.length > 0) {
                            lastMessageId = Math.max(lastMessageId, ...payload.messages.map((message) => message.id));
                        }

                        if (payload.closed) {
                            stopPolling();
                        }
                    } catch (error) {
                        // Temporary network failure: retry on the next interval.
                    } finally {
                        polling = false;

                        if (refreshQueued && pollTimer) {
                            refreshQueued = false;
                            poll();
                        }
                    }
                };

                refreshMessages = poll;

                newMessagesButton.addEventListener('click', scrollToBottom);
                messages.addEventListener('scroll', () => {
                    if (isNearBottom()) {
                        newMessagesButton.hidden = true;
                    }
                });
                document.addEventListener('visibilitychange', () => {
                    if (! document.hidden && pollTimer) {
                        poll();
                    }
                });

                pollTimer = setInterval(poll, POLL_INTERVAL_MS);
            }

            const proposalToggle = document.querySelector('[data-proposal-toggle]');
            const proposalForm = document.querySelector('[data-proposal-form]');

            if (proposalToggle && proposalForm) {
                const setProposalOpen = (open) => {
                    proposalForm.hidden = ! open;
                    proposalToggle.hidden = open;
                    proposalToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                };

                proposalToggle.addEventListener('click', () => {
                    setProposalOpen(true);
                    proposalForm.querySelector('input, select')?.focus();
                });

                proposalForm.querySelector('[data-proposal-cancel]').addEventListener('click', () => {
                    proposalForm.reset();
                    proposalForm.querySelectorAll('.field-error').forEach((error) => error.remove());
                    proposalForm.querySelectorAll('[aria-invalid]').forEach((field) => field.removeAttribute('aria-invalid'));
                    setProposalOpen(false);
                    proposalToggle.focus();
                });

                const durationInput = proposalForm.querySelector('[name="duration_minutes"]');
                const durationChips = proposalForm.querySelectorAll('[data-duration-chip]');
                const highlightDurationChip = () => {
                    durationChips.forEach((chip) => {
                        chip.classList.toggle('active', chip.dataset.durationChip === durationInput.value);
                    });
                };

                durationChips.forEach((chip) => {
                    chip.addEventListener('click', () => {
                        durationInput.value = chip.dataset.durationChip;
                        highlightDurationChip();
                    });
                });
                durationInput.addEventListener('input', highlightDurationChip);
                proposalForm.addEventListener('reset', () => setTimeout(highlightDurationChip));

                if (! proposalForm.hidden) {
                    proposalForm.scrollIntoView({ block: 'nearest' });
                }
            }

            const composer = document.querySelector('[data-composer]');

            if (! composer) {
                return;
            }

            const textarea = composer.querySelector('textarea');
            const resize = () => {
                textarea.style.height = 'auto';
                textarea.style.height = `${textarea.scrollHeight}px`;
            };

            textarea.addEventListener('input', resize);
            textarea.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && ! event.shiftKey && ! event.isComposing) {
                    event.preventDefault();
                    composer.requestSubmit();
                }
            });
            resize();

            // Send in the background so a page reload never drops an active call.
            const submitButton = composer.querySelector('[type="submit"]');
            const composerError = document.createElement('p');
            composerError.className = 'composer-error';
            composerError.setAttribute('role', 'alert');
            composerError.hidden = true;
            composer.before(composerError);

            let sending = false;

            const showComposerError = (text) => {
                composerError.textContent = text;
                composerError.hidden = false;
            };

            composer.addEventListener('submit', async (event) => {
                if (! window.fetch) {
                    return;
                }

                event.preventDefault();

                if (sending) {
                    return;
                }

                sending = true;
                submitButton.disabled = true;
                composerError.hidden = true;

                try {
                    const response = await fetch(composer.action, {
                        method: 'POST',
                        body: new FormData(composer),
                        credentials: 'same-origin',
                        redirect: 'manual',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (response.status === 422) {
                        const data = await response.json();

                        showComposerError(data.errors?.message?.[0] ?? data.message ?? 'Your message could not be sent.');

                        return;
                    }

                    if ([401, 419].includes(response.status)) {
                        showComposerError('Your session has expired. Refresh the page to keep chatting.');

                        return;
                    }

                    if (response.status === 403) {
                        showComposerError('You can no longer post in this conversation.');

                        return;
                    }

                    if (response.type !== 'opaqueredirect' && ! response.ok) {
                        throw new Error('message-send-failed');
                    }

                    textarea.value = '';
                    resize();
                    refreshMessages();
                } catch (error) {
                    showComposerError('Message not sent. Check your connection and try again.');
                } finally {
                    sending = false;
                    submitButton.disabled = false;
                    textarea.focus();
                }
            });
        })();
    </script>
</body>

</html>
