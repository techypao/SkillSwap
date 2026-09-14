<?php

namespace App\Http\Requests;

use App\Models\CallSignal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCallSignalRequest extends FormRequest
{
    /**
     * Only the two participants of an accepted swap with a confirmed session may signal.
     */
    public function authorize(): bool
    {
        return $this->user()->can('call', $this->route('swapRequest'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type');
        $carriesPayload = in_array($type, CallSignal::PAYLOAD_TYPES, true);
        $carriesDescription = in_array($type, [CallSignal::TYPE_OFFER, CallSignal::TYPE_ANSWER], true);

        return [
            'type' => ['required', 'string', Rule::in(CallSignal::TYPES)],
            'payload' => [
                Rule::requiredIf($carriesPayload),
                Rule::prohibitedIf(! $carriesPayload),
                'array:call_id,sdp,candidate,audio,video',
            ],
            'payload.call_id' => [
                Rule::requiredIf($carriesDescription || $type === CallSignal::TYPE_CANDIDATE),
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'payload.sdp' => [
                Rule::requiredIf($carriesDescription),
                'string',
                'max:'.CallSignal::MAX_SDP_LENGTH,
                'starts_with:v=0',
            ],
            'payload.candidate' => [
                Rule::requiredIf($type === CallSignal::TYPE_CANDIDATE),
                'array:candidate,sdpMid,sdpMLineIndex,usernameFragment',
            ],
            'payload.candidate.candidate' => ['nullable', 'string', 'max:1024'],
            'payload.candidate.sdpMid' => ['nullable', 'string', 'max:64'],
            'payload.candidate.sdpMLineIndex' => ['nullable', 'integer', 'min:0', 'max:255'],
            'payload.candidate.usernameFragment' => ['nullable', 'string', 'max:256'],
            'payload.audio' => [Rule::requiredIf($type === CallSignal::TYPE_MEDIA), 'boolean'],
            'payload.video' => [Rule::requiredIf($type === CallSignal::TYPE_MEDIA), 'boolean'],
        ];
    }
}
