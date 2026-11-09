{{-- Days, months and years; the month and year headings open their grids. --}}
@php
    $labels = $config['labels'];
@endphp

<div x-show="isOnCalendar" class="fi-chrono-calendar">
    @if ($config['hasCalendar'])
        <div x-show="view === 'date'" class="fi-chrono-calendar-view">
            <div class="fi-chrono-calendar-header">
                <button type="button" x-on:click="previousMonth()" aria-label="{{ $labels['previousMonth'] }}" class="fi-chrono-calendar-nav">
                    <x-filament::icon icon="heroicon-m-chevron-left" class="fi-chrono-calendar-nav-icon" />
                </button>

                <div class="fi-chrono-calendar-title">
                    <button type="button" x-on:click="showMonths()" aria-label="{{ $labels['selectMonth'] }}" class="fi-chrono-calendar-heading">
                        <span x-text="monthNames[focusedMonth]"></span>
                    </button>

                    <button type="button" x-on:click="showYears()" aria-label="{{ $labels['selectYear'] }}" class="fi-chrono-calendar-heading">
                        <span x-text="focusedYear"></span>
                    </button>
                </div>

                <button type="button" x-on:click="nextMonth()" aria-label="{{ $labels['nextMonth'] }}" class="fi-chrono-calendar-nav">
                    <x-filament::icon icon="heroicon-m-chevron-right" class="fi-chrono-calendar-nav-icon" />
                </button>
            </div>

            <div class="fi-chrono-calendar-weekdays">
                <template x-for="(name, index) in dayNames" x-bind:key="index">
                    <div x-text="name" class="fi-chrono-calendar-weekday"></div>
                </template>
            </div>

            <div role="grid" class="fi-chrono-calendar-days">
                <template x-for="blank in leadingBlanks" x-bind:key="'blank-' + blank">
                    <div></div>
                </template>

                {{-- Not `day`: Alpine would resolve this.day in the component to the loop variable. --}}
                <template x-for="calendarDay in daysInFocusedMonth" x-bind:key="calendarDay">
                    <div
                        x-text="calendarDay"
                        x-on:click="selectDay(calendarDay)"
                        role="option"
                        x-bind:aria-selected="dayIsSelected(calendarDay) ? 'true' : 'false'"
                        x-bind:class="{
                            'fi-chrono-calendar-day-today': dayIsToday(calendarDay),
                            'fi-chrono-calendar-day-selected': dayIsSelected(calendarDay),
                            'fi-chrono-calendar-day-disabled': dayIsDisabled(calendarDay),
                        }"
                        class="fi-chrono-calendar-day"
                    ></div>
                </template>
            </div>
        </div>
    @endif

    <div x-show="view === 'months'" class="fi-chrono-calendar-view">
        <div class="fi-chrono-calendar-header">
            <button type="button" x-on:click="previousYear()" aria-label="{{ $labels['previousYear'] }}" class="fi-chrono-calendar-nav">
                <x-filament::icon icon="heroicon-m-chevron-left" class="fi-chrono-calendar-nav-icon" />
            </button>

            <div class="fi-chrono-calendar-title">
                <button type="button" x-on:click="showYears()" aria-label="{{ $labels['selectYear'] }}" class="fi-chrono-calendar-heading">
                    <span x-text="focusedYear"></span>
                </button>
            </div>

            <button type="button" x-on:click="nextYear()" aria-label="{{ $labels['nextYear'] }}" class="fi-chrono-calendar-nav">
                <x-filament::icon icon="heroicon-m-chevron-right" class="fi-chrono-calendar-nav-icon" />
            </button>
        </div>

        <div role="grid" aria-label="{{ $labels['month'] }}" class="fi-chrono-calendar-grid">
            <template x-for="(name, index) in shortMonthNames" x-bind:key="index">
                <button
                    type="button"
                    x-text="name"
                    x-on:click="pickMonth(index)"
                    x-bind:aria-label="monthNames[index]"
                    x-bind:aria-selected="monthIsSelected(index) ? 'true' : 'false'"
                    x-bind:disabled="monthIsDisabled(index)"
                    x-bind:class="{
                        'fi-chrono-calendar-cell-current': monthIsCurrent(index),
                        'fi-chrono-calendar-cell-selected': monthIsSelected(index),
                    }"
                    class="fi-chrono-calendar-cell"
                ></button>
            </template>
        </div>
    </div>

    <div x-show="view === 'years'" class="fi-chrono-calendar-view">
        <div class="fi-chrono-calendar-header">
            <button type="button" x-on:click="previousYears()" aria-label="{{ $labels['previousYears'] }}" class="fi-chrono-calendar-nav">
                <x-filament::icon icon="heroicon-m-chevron-left" class="fi-chrono-calendar-nav-icon" />
            </button>

            <div class="fi-chrono-calendar-title">
                <span x-text="yearsPageLabel" class="fi-chrono-calendar-heading fi-chrono-calendar-heading-static"></span>
            </div>

            <button type="button" x-on:click="nextYears()" aria-label="{{ $labels['nextYears'] }}" class="fi-chrono-calendar-nav">
                <x-filament::icon icon="heroicon-m-chevron-right" class="fi-chrono-calendar-nav-icon" />
            </button>
        </div>

        <div role="grid" aria-label="{{ $labels['year'] }}" class="fi-chrono-calendar-grid">
            <template x-for="pageYear in yearsOnPage" x-bind:key="pageYear">
                <button
                    type="button"
                    x-text="pageYear"
                    x-on:click="pickYear(pageYear)"
                    x-bind:aria-selected="yearIsSelected(pageYear) ? 'true' : 'false'"
                    x-bind:disabled="yearIsDisabled(pageYear)"
                    x-bind:class="{
                        'fi-chrono-calendar-cell-current': yearIsCurrent(pageYear),
                        'fi-chrono-calendar-cell-selected': yearIsSelected(pageYear),
                    }"
                    class="fi-chrono-calendar-cell"
                ></button>
            </template>
        </div>
    </div>
</div>
