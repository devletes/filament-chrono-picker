@php
    $config = $getRangeConfig();
    $statePath = $getStatePath();
    $livewireKey = $getLivewireKey();
    $id = $getId();
    $isDisabled = $config['isDisabled'];
    $isReadOnly = $isReadOnly();
    $isOneAtATime = $config['layout'] === 'one-at-a-time';
    $hasTime = $config['mode'] === 'time';
    $ends = ['start' => $getEndConfig('start'), 'end' => $getEndConfig('end')];
    $face = $hasTime ? $getEndClockFace() : [];
    $placeholder = $getPlaceholder();

    // The panels are rendered on the server, so a change of shape re-keys them.
    $shapeKey = substr(md5(serialize([$config, $ends, $isReadOnly])), 0, 16);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{}" x-load-css="[@js($getStylesheetHref())]" class="fi-chrono-assets">
        <div
            x-load
            x-load-src="{{ $getAlpineComponentSrc() }}"
            x-data="devletesChronoPicker({
                config: @js($config),
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            })"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{ $shapeKey }}"
            x-on:keydown.esc="rangeOpen && ($event.stopPropagation(), rangeClose())"
            x-on:keydown.escape.window="rangeOpen && rangeDismiss()"
            x-on:click.outside="rangeOpen && rangeDismiss()"
            @class(['fi-chrono', 'fi-chrono-range'])
        >
            <x-filament::input.wrapper
                :disabled="$isDisabled"
                :valid="! $errors->has($statePath)"
                :prefix-icon="$hasTime ? 'heroicon-m-clock' : 'heroicon-m-calendar-days'"
                prefix-icon-color="gray"
                x-ref="field"
                x-bind:class="{ 'fi-chrono-open': rangeOpen }"
            >
                <div @if ($id) id="{{ $id }}" @endif class="fi-input fi-chrono-range-field">
                    @foreach (['start', 'end'] as $which)
                        @if ($which === 'end')
                            <span class="fi-chrono-range-separator" aria-hidden="true">{{ trim($config['separator']) }}</span>
                        @endif

                        <button
                            type="button"
                            @if ($which === 'start') data-chrono-range-trigger @endif
                            x-on:click="rangeShow('{{ $which }}')"
                            x-on:keydown.enter.prevent.stop="rangeShow('{{ $which }}')"
                            x-on:keydown.backspace.prevent.stop="rangeClear()"
                            x-on:keydown.delete.prevent.stop="rangeClear()"
                            x-bind:aria-expanded="rangeOpen ? 'true' : 'false'"
                            x-bind:class="{ 'fi-chrono-range-half-active': rangeOpen && rangeActive === '{{ $which }}' }"
                            aria-haspopup="dialog"
                            aria-label="{{ $config['labels'][$which] }}"
                            @disabled($isDisabled || $isReadOnly)
                            class="fi-chrono-range-half"
                        >
                            <span x-show="rangeText.{{ $which }}" x-text="rangeText.{{ $which }}"></span>
                            <span x-show="! rangeText.{{ $which }}" class="fi-chrono-range-placeholder">{{ $which === 'start' ? ($placeholder ?? $config['labels']['start']) : $config['labels']['end'] }}</span>
                        </button>
                    @endforeach
                </div>
            </x-filament::input.wrapper>

            @unless ($isDisabled || $isReadOnly)
                <div
                    x-ref="panel"
                    x-cloak
                    x-float.placement.bottom-start.offset.flip.shift="{ offset: 8 }"
                    role="dialog"
                    aria-modal="false"
                    @class([
                        'fi-chrono-panel',
                        'fi-chrono-panel-with-date' => ! $hasTime,
                        'fi-chrono-range-panel',
                        'fi-chrono-range-one-at-a-time' => $isOneAtATime,
                        'fi-chrono-range-side-by-side' => ! $isOneAtATime,
                    ])
                >
                    @if ($isOneAtATime)
                        <div class="fi-chrono-views" role="tablist">
                            @foreach (['start', 'end'] as $which)
                                <button
                                    type="button"
                                    role="tab"
                                    x-on:click="rangeActive = '{{ $which }}'"
                                    x-bind:aria-selected="rangeActive === '{{ $which }}' ? 'true' : 'false'"
                                    x-bind:class="{ 'fi-chrono-view-active': rangeActive === '{{ $which }}' }"
                                    class="fi-chrono-view"
                                >
                                    {{ $config['labels'][$which] }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="fi-chrono-range-ends">
                        @foreach ($ends as $which => $endConfig)
                            <div
                                @if ($isOneAtATime) x-show="rangeActive === '{{ $which }}'" @endif
                                class="fi-chrono-range-end"
                            >
                                @unless ($isOneAtATime)
                                    <div class="fi-chrono-range-end-heading">{{ $config['labels'][$which] }}</div>
                                @endunless

                                <div
                                    data-chrono-end="{{ $which }}"
                                    x-data="devletesChronoPicker({ config: @js($endConfig), embedded: '{{ $which }}' })"
                                    class="fi-chrono-range-end-panel"
                                >
                                    @if ($hasTime)
                                        @include('devletes-chrono-picker::partials.clock', ['config' => $endConfig, 'face' => $face])
                                    @else
                                        @include('devletes-chrono-picker::partials.calendar', ['config' => $endConfig])
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="fi-chrono-footer">
                        <x-filament::button type="button" color="gray" size="sm" x-show="rangeDraft.start || rangeDraft.end" x-on:click="rangeReset()">
                            {{ __('devletes-chrono-picker::chrono-picker.range.clear') }}
                        </x-filament::button>

                        <div class="fi-chrono-footer-end">
                            <x-filament::button type="button" color="gray" size="sm" x-on:click="rangeCancel()">
                                {{ $getCancelLabel() }}
                            </x-filament::button>

                            @unless ($config['closeOnComplete'])
                                <x-filament::button type="button" size="sm" x-on:click="rangeConfirm()">
                                    {{ $getOkLabel() }}
                                </x-filament::button>
                            @endunless
                        </div>
                    </div>
                </div>
            @endunless
        </div>
    </div>
</x-dynamic-component>
