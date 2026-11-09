@php
    $isTwelveHour = $config['hoursCycle'] === 12;
    $hasSeconds = $config['hasSeconds'];
@endphp

<div x-show="view === 'time'" class="fi-chrono-time">
    <div class="fi-chrono-readout">
        <input
            x-ref="hourInput"
            type="text"
            inputmode="numeric"
            maxlength="2"
            autocomplete="off"
            aria-label="{{ $config['labels']['hour'] }}"
            x-model="hourText"
            x-on:focus="stage = 'hour'; $event.target.select()"
            x-on:input="onTyped('hour', $event)"
            x-on:blur="commitTyped('hour')"
            x-on:keydown.up.prevent="nudge(1)"
            x-on:keydown.down.prevent="nudge(-1)"
            x-bind:class="{ 'fi-chrono-readout-active': stage === 'hour' }"
            class="fi-chrono-readout-input"
        />

        <span class="fi-chrono-readout-separator">:</span>

        <input
            x-ref="minuteInput"
            type="text"
            inputmode="numeric"
            maxlength="2"
            autocomplete="off"
            aria-label="{{ $config['labels']['minute'] }}"
            x-model="minuteText"
            x-on:focus="stage = 'minute'; $event.target.select()"
            x-on:input="onTyped('minute', $event)"
            x-on:blur="commitTyped('minute')"
            x-on:keydown.up.prevent="nudge(1)"
            x-on:keydown.down.prevent="nudge(-1)"
            x-bind:class="{ 'fi-chrono-readout-active': stage === 'minute' }"
            class="fi-chrono-readout-input"
        />

        @if ($hasSeconds)
            <span class="fi-chrono-readout-separator">:</span>

            <input
                x-ref="secondInput"
                type="text"
                inputmode="numeric"
                maxlength="2"
                autocomplete="off"
                aria-label="{{ $config['labels']['second'] }}"
                x-model="secondText"
                x-on:focus="stage = 'second'; $event.target.select()"
                x-on:input="onTyped('second', $event)"
                x-on:blur="commitTyped('second')"
                x-on:keydown.up.prevent="nudge(1)"
                x-on:keydown.down.prevent="nudge(-1)"
                x-bind:class="{ 'fi-chrono-readout-active': stage === 'second' }"
                class="fi-chrono-readout-input"
            />
        @endif

        @if ($isTwelveHour)
            <div class="fi-chrono-meridiem" role="group">
                @foreach (['am', 'pm'] as $meridiem)
                    <button
                        type="button"
                        x-on:click="setMeridiem('{{ $meridiem }}')"
                        x-bind:class="{ 'fi-chrono-meridiem-active': meridiem === '{{ $meridiem }}' }"
                        x-bind:disabled="! canUseMeridiem('{{ $meridiem }}')"
                        class="fi-chrono-meridiem-option"
                    >{{ $config['labels']['meridiem'][$meridiem] }}</button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="fi-chrono-dial">
        <svg
            x-ref="face"
            viewBox="0 0 224 224"
            class="fi-chrono-face"
            x-on:pointerdown="onPointerDown($event)"
            x-on:pointermove="onPointerMove($event)"
            x-on:pointerup="onPointerUp($event)"
            x-on:pointercancel="onPointerUp($event)"
            x-bind:aria-label="stageLabel"
        >
            <circle cx="112" cy="112" r="110" class="fi-chrono-face-plate" />

            <g x-show="hasSelection" class="fi-chrono-hand">
                <line
                    x1="112"
                    y1="112"
                    x2="112"
                    x-bind:y2="112 - handLength"
                    x-bind:transform="`rotate(${handAngle} 112 112)`"
                    class="fi-chrono-hand-line"
                />
                <circle
                    cx="112"
                    x-bind:cy="112 - handLength"
                    r="17"
                    x-bind:transform="`rotate(${handAngle} 112 112)`"
                    class="fi-chrono-hand-knob"
                />
            </g>

            <circle cx="112" cy="112" r="4" class="fi-chrono-hand-pin" />

            <text x="112" y="96" text-anchor="middle" dominant-baseline="central" x-text="config.labels.dial[stage]" class="fi-chrono-dial-label"></text>

            @foreach ($face as $stage => $rings)
                @if ($stage === 'second' && ! $hasSeconds)
                    @continue
                @endif

                <g x-show="stage === '{{ $stage }}'">
                    @foreach ($rings as $ring)
                        @foreach ($ring as $numeral)
                            <text
                                x="{{ $numeral['x'] }}"
                                y="{{ $numeral['y'] }}"
                                text-anchor="middle"
                                dominant-baseline="central"
                                x-bind:class="numeralClass('{{ $stage }}', {{ $numeral['value'] }})"
                                @class([
                                    'fi-chrono-numeral',
                                    'fi-chrono-numeral-inner' => ! $numeral['outer'],
                                ])
                            >{{ $numeral['label'] }}</text>
                        @endforeach
                    @endforeach
                </g>
            @endforeach
        </svg>
    </div>
</div>
