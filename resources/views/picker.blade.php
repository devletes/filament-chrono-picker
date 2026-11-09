@php
    $config = $getPickerConfig();
    $statePath = $getStatePath();
    $id = $getId();
    $livewireKey = $getLivewireKey();
    $isDisabled = $config['isDisabled'];
    $isReadOnly = $config['isReadOnly'];
    $hasDate = $config['hasDate'];
    $hasTime = $config['hasTime'];

    // The panel is built server-side, so it is rebuilt whenever its shape changes.
    $shapeKey = substr(md5(serialize([
        $config['mode'], $config['hoursCycle'], $config['hasSeconds'], $isDisabled, $isReadOnly,
        $config['minTime'], $config['maxTime'], $config['minutesStep'],
        $config['minDate'], $config['maxDate'], $config['disabledDates'],
    ])), 0, 16);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{-- The stylesheet and the script both load on request, so a page without a picker fetches neither. --}}
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
            x-on:keydown.esc="open && ($event.stopPropagation(), close())"
            {{ $getExtraAlpineAttributeBag()->class(['fi-chrono']) }}
        >
            <x-filament::input.wrapper
                :disabled="$isDisabled"
                :valid="! $errors->has($statePath)"
                :prefix-icon="$hasDate ? 'heroicon-m-calendar-days' : 'heroicon-m-clock'"
                prefix-icon-color="gray"
                x-bind:class="{ 'fi-chrono-open': open }"
            >
                <button
                    type="button"
                    x-ref="trigger"
                    x-on:click="toggle()"
                    x-on:keydown.enter.prevent.stop="toggle()"
                    x-on:keydown.backspace.prevent.stop="clear()"
                    x-on:keydown.delete.prevent.stop="clear()"
                    x-bind:aria-expanded="open ? 'true' : 'false'"
                    aria-haspopup="dialog"
                    @disabled($isDisabled || $isReadOnly)
                    @if ($isAutofocused()) autofocus @endif
                    class="fi-chrono-trigger"
                >
                    <input
                        @disabled($isDisabled)
                        readonly
                        tabindex="-1"
                        @if ($id) id="{{ $id }}" @endif
                        placeholder="{{ $getPlaceholder() }}"
                        x-model="displayText"
                        class="fi-input fi-chrono-display"
                    />
                </button>
            </x-filament::input.wrapper>

            @unless ($isDisabled || $isReadOnly)
                <div
                    x-ref="panel"
                    x-cloak
                    x-show="open"
                    x-float.placement.bottom-start.offset.flip.shift="{ offset: 8 }"
                    x-on:click.outside="close()"
                    role="dialog"
                    aria-modal="false"
                    @class(['fi-chrono-panel', 'fi-chrono-panel-with-date' => $hasDate])
                >
                    @if ($config['hasCalendar'] && $hasTime)
                        <div class="fi-chrono-views" role="tablist">
                            <button
                                type="button"
                                role="tab"
                                x-on:click="showDate()"
                                x-bind:aria-selected="isOnCalendar ? 'true' : 'false'"
                                x-bind:class="{ 'fi-chrono-view-active': isOnCalendar }"
                                class="fi-chrono-view"
                            >
                                <x-filament::icon icon="heroicon-m-calendar-days" class="fi-chrono-view-icon" />
                                <span x-text="dateLabel || @js($config['labels']['date'])"></span>
                            </button>

                            <button
                                type="button"
                                role="tab"
                                x-on:click="showTime()"
                                x-bind:aria-selected="view === 'time' ? 'true' : 'false'"
                                x-bind:class="{ 'fi-chrono-view-active': view === 'time' }"
                                class="fi-chrono-view"
                            >
                                <x-filament::icon icon="heroicon-m-clock" class="fi-chrono-view-icon" />
                                <span x-text="timeLabel || @js($config['labels']['time'])"></span>
                            </button>
                        </div>
                    @endif

                    @if ($hasDate)
                        @include('devletes-chrono-picker::partials.calendar', ['config' => $config])
                    @endif

                    @if ($hasTime)
                        @include('devletes-chrono-picker::partials.clock', ['config' => $config, 'face' => $getClockFace()])
                    @endif

                    <div class="fi-chrono-footer">
                        @if ($hasNowButton())
                            <x-filament::button type="button" color="gray" size="sm" x-on:click="setNow()">
                                {{ $getNowLabel() }}
                            </x-filament::button>
                        @endif

                        <div class="fi-chrono-footer-end">
                            <x-filament::button type="button" color="gray" size="sm" x-on:click="cancel()">
                                {{ $getCancelLabel() }}
                            </x-filament::button>

                            @unless ($config['closeOnComplete'])
                                <x-filament::button type="button" size="sm" x-on:click="confirm()">
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
