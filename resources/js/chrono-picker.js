// Chrono Picker's panel. An ES module loaded on request by Filament's x-load, whose default export Alpine registers
// under the name the field's x-data calls. Module scope keeps these helpers out of the page's globals, which is what
// the 1.x IIFE was for when the file was served as a classic script.

const OUTER_RADIUS = 84
const INNER_RADIUS = 54
const RING_BOUNDARY = 69
const FACE_SIZE = 224
const FACE_CENTRE = FACE_SIZE / 2

const pad2 = (value) => String(value).padStart(2, '0')

const clamp = (value, min, max) => Math.min(Math.max(value, min), max)

const toSeconds = (hour, minute, second) => (hour * 3600) + (minute * 60) + second

const parseBound = (value) => {
    if (! value) {
        return null
    }

    const parts = String(value).split(':').map((part) => parseInt(part, 10))

    if (parts.some(Number.isNaN)) {
        return null
    }

    return toSeconds(parts[0] ?? 0, parts[1] ?? 0, parts[2] ?? 0)
}

const names = (locale, options, count, dateFor) => Array.from({ length: count }, (_, index) => {
    try {
        return new Intl.DateTimeFormat(locale, options).format(dateFor(index))
    } catch (exception) {
        return new Intl.DateTimeFormat('en', options).format(dateFor(index))
    }
})

const monthNames = (locale, style) => names(locale, { month: style }, 12, (index) => new Date(2020, index, 1))

// 2 Jan 2022 was a Sunday, so index 0 is Sunday as in Date#getDay().
const weekdayNames = (locale, style) => names(locale, { weekday: style }, 7, (index) => new Date(2022, 0, 2 + index))

const weekdayOf = (year, month, day) => new Date(year, month - 1, day).getDay()

const today = () => {
    const now = new Date()

    return { year: now.getFullYear(), month: now.getMonth() + 1, day: now.getDate() }
}

export const formatValue = ({ year, month, day, hour, minute, second }, format, locale = 'en') => {
    const meridiem = hour < 12 ? 'AM' : 'PM'
    const twelve = hour % 12 === 0 ? 12 : hour % 12

    let out = ''

    for (let i = 0; i < format.length; i++) {
        const token = format[i]

        if (token === '\\') {
            out += format[i + 1] ?? ''
            i++

            continue
        }

        switch (token) {
            case 'H': out += pad2(hour); break
            case 'G': out += String(hour); break
            case 'h': out += pad2(twelve); break
            case 'g': out += String(twelve); break
            case 'i': out += pad2(minute); break
            case 's': out += pad2(second); break
            case 'A': out += meridiem; break
            case 'a': out += meridiem.toLowerCase(); break
            case 'Y': out += String(year); break
            case 'y': out += pad2(year % 100); break
            case 'm': out += pad2(month); break
            case 'n': out += String(month); break
            case 'd': out += pad2(day); break
            case 'j': out += String(day); break
            case 'M': out += monthNames(locale, 'short')[month - 1]; break
            case 'F': out += monthNames(locale, 'long')[month - 1]; break
            case 'D': out += weekdayNames(locale, 'short')[weekdayOf(year, month, day)]; break
            case 'l': out += weekdayNames(locale, 'long')[weekdayOf(year, month, day)]; break
            default: out += token
        }
    }

    return out
}

// Filament's state is a whole Y-m-d H:i:s, so each part is matched by its shape, not by the first numbers.
// A date with no time reads as midnight; a time with no date takes today's.
export const parseValue = (value) => {
    if (typeof value !== 'string' || value.trim() === '') {
        return null
    }

    const date = value.match(/(\d{4})-(\d{2})(?:-(\d{2}))?/)
    const rest = date ? value.slice(date.index + date[0].length) : value
    const time = rest.match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/)

    if (! date && ! time) {
        return null
    }

    let hour = time ? parseInt(time[1], 10) : 0
    const minute = time ? parseInt(time[2], 10) : 0
    const second = time ? parseInt(time[3] ?? '0', 10) : 0

    if (time) {
        const meridiem = rest.slice(time.index).toLowerCase().match(/([ap])\.?m/)

        if (meridiem) {
            hour = (hour % 12) + (meridiem[1] === 'p' ? 12 : 0)
        }
    }

    if (hour > 23 || minute > 59 || second > 59) {
        return null
    }

    const now = today()

    return {
        year: date ? parseInt(date[1], 10) : now.year,
        month: date ? parseInt(date[2], 10) : now.month,
        day: date ? parseInt(date[3] ?? '1', 10) : now.day,
        hour,
        minute,
        second,
    }
}

const dateOf = (value) => {
    const parsed = parseValue(value)

    return parsed ? { year: parsed.year, month: parsed.month, day: parsed.day } : today()
}

const monthKey = (year, month) => `${year}-${pad2(month)}`

// Filament's x-float only places a panel it opened itself, never one x-show reveals, so the panel sits under the
// field as laid out. A range's two calendars are wide enough to run off the right of the window from a field on
// the right of a form: once shown, the panel is moved left by whatever overhangs, never past the window's left.
const fitPanel = (panel) => {
    if (! panel) {
        return
    }

    panel.style.marginInlineStart = ''

    requestAnimationFrame(() => {
        const rect = panel.getBoundingClientRect()
        const gutter = 8
        const overhang = rect.right - (document.documentElement.clientWidth - gutter)

        if (overhang > 0) {
            panel.style.marginInlineStart = `-${Math.min(overhang, Math.max(0, rect.left - gutter))}px`
        }
    })
}

// A range: the field, the footer, and two ends. Each end is a single picker of its own (below) in `embedded`
// mode, which reads and writes this component's draft rather than Livewire. Every name here starts with `range`
// so the ends, whose scope sits inside this one, never shadow it or are shadowed by it.
const rangePicker = ({ config, state }) => {
    let root = null
    const keyOf = (which) => config.keys[which]

    return {
        rangeConfig: config,
        rangeState: state,
        rangeOpen: false,
        rangeActive: 'start',
        rangeDraft: { start: null, end: null },
        rangeText: { start: '', end: '' },
        rangeFlushing: false,

        init() {
            root = this.$el
            this.rangeSync()

            // Follow server-side changes (other actions, form resets) while closed.
            this.$watch('rangeState', () => {
                if (! this.rangeOpen) {
                    this.rangeSync()
                }
            })
        },

        rangeStored(which) {
            const stored = this.rangeState

            return (stored && typeof stored === 'object') ? (stored[keyOf(which)] ?? null) : null
        },

        rangeDraftOf(which) {
            return this.rangeDraft[which]
        },

        rangeFormat(value) {
            const parsed = parseValue(value)

            return parsed ? formatValue(parsed, config.displayFormat, config.locale) : ''
        },

        // A time range compares the time of day: the date in front of it is whatever day it was picked on.
        rangeComparable(value) {
            return config.mode === 'time' ? String(value).slice(-8) : String(value)
        },

        get rangeHasValue() {
            return Boolean(this.rangeText.start || this.rangeText.end)
        },

        rangeSync() {
            this.rangeDraft = { start: this.rangeStored('start'), end: this.rangeStored('end') }
            this.rangeText = { start: this.rangeFormat(this.rangeDraft.start), end: this.rangeFormat(this.rangeDraft.end) }
        },

        rangeEnds() {
            return root ? [...root.querySelectorAll('[data-chrono-end]')].map((el) => window.Alpine.$data(el)) : []
        },

        rangeEnd(which) {
            return this.rangeEnds().find((end) => end.embedded === which)
        },

        rangeShow(which = 'start') {
            if (config.isDisabled) {
                return
            }

            if (! this.rangeOpen) {
                this.rangeSync()
                this.rangeEnds().forEach((end) => end.syncFromState())
            }

            this.rangeActive = which
            this.rangeOpen = true
            this.$nextTick(() => fitPanel(root?.querySelector('.fi-chrono-range-panel')))
        },

        rangeClose() {
            this.rangeOpen = false
            root?.querySelector('[data-chrono-range-trigger]')?.focus()
        },

        rangeCancel() {
            this.rangeSync()
            this.rangeClose()
        },

        // An end hands over what was picked in it.
        rangePick(which, value) {
            this.rangeDraft[which] = value

            if (this.rangeFlushing) {
                return
            }

            if (which === 'start') {
                const end = this.rangeDraft.end

                // A new start after the end leaves no range; the end is picked again.
                if (end && value && this.rangeComparable(end) < this.rangeComparable(value)) {
                    this.rangeDraft.end = null
                }

                // The To panel follows: it opens near the start and dims what comes before it.
                this.rangeEnd('end')?.syncFromState()

                if (config.layout === 'one-at-a-time') {
                    this.rangeActive = 'end'
                }

                return
            }

            if (config.closeOnComplete && this.rangeDraft.start) {
                this.rangeConfirm()
            }
        },

        rangeConfirm() {
            // A time typed into an end but not finished on the dial is still taken.
            this.rangeFlushing = true
            this.rangeEnds().forEach((end) => end.commit())
            this.rangeFlushing = false

            this.rangeState = { [keyOf('start')]: this.rangeDraft.start, [keyOf('end')]: this.rangeDraft.end }
            this.rangeText = { start: this.rangeFormat(this.rangeDraft.start), end: this.rangeFormat(this.rangeDraft.end) }
            this.rangeClose()
        },

        rangeClear() {
            this.rangeState = { [keyOf('start')]: null, [keyOf('end')]: null }
            this.rangeSync()
        },
    }
}

export default function devletesChronoPicker({ config, state = null, embedded = null }) {
    if (config.range) {
        return rangePicker({ config, state })
    }

    // The range this picker is one end of, when it is; held outside Alpine's reactive data.
    let range = null

    return {
        config,
        state,
        embedded,

        open: false,
        stage: 'hour',
        view: config.initialView,
        // The view a month or year grid goes back to once something is picked in it.
        returnView: config.initialView,

        year: null,
        month: null,
        day: null,
        focusedYear: null,
        focusedMonth: null,
        yearsPageStart: null,

        hour: null,
        minute: null,
        second: null,

        hourText: '',
        minuteText: '',
        secondText: '',

        displayText: '',

        dragging: false,

        init() {
            if (this.embedded) {
                range = window.Alpine.$data(this.$el.parentElement.closest('[data-chrono-range]'))
                this.syncFromState()

                return
            }

            this.syncFromState()

            // Follow server-side changes (other actions, form resets) while closed.
            this.$watch('state', () => {
                if (! this.open) {
                    this.syncFromState()
                }
            })

            if (this.config.isAutofocused) {
                this.$nextTick(() => this.$refs.trigger?.focus())
            }
        },

        get mode() {
            return this.config.mode
        },

        // Reading and writing the field: Livewire's state, or the range's draft for an end of a range

        readState() {
            return this.embedded ? (range?.rangeDraftOf(this.embedded) ?? null) : this.state
        },

        writeState(value) {
            if (this.embedded) {
                range?.rangePick(this.embedded, value)

                return
            }

            this.state = value
        },

        // The earliest date this panel offers: the field's minimum and, on a range's To panel, its From.
        get lowerDate() {
            const own = this.config.minDate
            const start = (this.embedded === 'end' && this.config.hasDate) ? range?.rangeDraftOf('start') : null

            if (! start) {
                return own
            }

            return (! own || start > own) ? start : own
        },

        get upperDate() {
            return this.config.maxDate
        },

        syncFromState() {
            const parsed = parseValue(this.readState())

            this.hour = parsed?.hour ?? null
            this.minute = parsed?.minute ?? null
            this.second = parsed?.second ?? null
            this.stage = 'hour'

            if (this.config.hasDate) {
                this.year = parsed?.year ?? null
                this.month = parsed?.month ?? null
                this.day = parsed?.day ?? null

                // An empty To opens where From is, rather than wherever today is.
                const fromStart = this.embedded === 'end' ? parseValue(range?.rangeDraftOf('start')) : null
                const focus = parsed ?? fromStart ?? parseValue(this.config.defaultFocusedDate) ?? today()

                this.focusedYear = focus.year
                this.focusedMonth = focus.month - 1
                this.yearsPageStart = this.pageStartFor(focus.year)
            }

            this.view = this.config.initialView
            this.returnView = this.config.initialView

            this.syncText()
            this.refreshDisplay()
        },

        refreshDisplay() {
            const parsed = parseValue(this.readState())

            this.displayText = parsed ? formatValue(parsed, this.config.displayFormat, this.config.locale) : ''
        },

        get draftValue() {
            if (this.config.hasDate) {
                if (this.year === null) {
                    return null
                }

                return formatValue({
                    year: this.year,
                    month: this.month ?? 1,
                    day: this.day ?? 1,
                    hour: this.hour ?? 0,
                    minute: this.minute ?? 0,
                    second: this.second ?? 0,
                }, this.config.internalFormat)
            }

            if (this.hour === null) {
                return null
            }

            return formatValue({
                ...dateOf(this.readState()),
                hour: this.hour,
                minute: this.minute ?? 0,
                second: this.second ?? 0,
            }, this.config.internalFormat)
        },

        commit() {
            const value = this.draftValue

            if (value === null) {
                return
            }

            this.writeState(value)
            this.refreshDisplay()
        },

        clear() {
            this.writeState(null)
            this.syncFromState()
        },

        // The panel

        toggle() {
            this.open ? this.close() : this.show()
        },

        show() {
            this.syncFromState()
            this.open = true
            this.$nextTick(() => fitPanel(this.$refs.panel))
        },

        close() {
            this.open = false
            this.dragging = false
            this.$refs.trigger?.focus()
        },

        cancel() {
            this.syncFromState()
            this.open = false
            this.$refs.trigger?.focus()
        },

        // An end of a range has no panel of its own to close: confirming hands the pick to the range.
        confirm() {
            this.commit()

            if (! this.embedded) {
                this.close()
            }
        },

        setNow() {
            const now = new Date()

            if (this.config.hasDate) {
                this.year = now.getFullYear()
                this.month = this.mode === 'year' ? 1 : now.getMonth() + 1
                this.day = this.config.hasCalendar ? now.getDate() : 1
                this.focusedYear = this.year
                this.focusedMonth = this.month - 1
                this.yearsPageStart = this.pageStartFor(this.year)
            }

            if (this.config.hasTime) {
                this.hour = now.getHours()
                this.minute = this.snap(now.getMinutes(), this.config.minutesStep, 60)
                this.second = this.config.hasSeconds
                    ? this.snap(now.getSeconds(), this.config.secondsStep, 60)
                    : 0
            } else {
                this.hour = this.minute = this.second = 0
            }

            this.pullIntoBounds()
            this.syncText()

            if (this.config.closeOnComplete) {
                this.confirm()
            }
        },

        // The calendar

        get monthNames() {
            return monthNames(this.config.locale, 'long')
        },

        get shortMonthNames() {
            return monthNames(this.config.locale, 'short')
        },

        get dayNames() {
            const short = weekdayNames(this.config.locale, 'short')

            return Array.from({ length: 7 }, (_, index) => short[(index + this.config.firstDayOfWeek) % 7])
        },

        get daysInFocusedMonth() {
            const count = new Date(this.focusedYear, this.focusedMonth + 1, 0).getDate()

            return Array.from({ length: count }, (_, index) => index + 1)
        },

        get leadingBlanks() {
            const first = new Date(this.focusedYear, this.focusedMonth, 1).getDay()

            return Array.from({ length: (first - this.config.firstDayOfWeek + 7) % 7 }, (_, index) => index)
        },

        previousMonth() {
            this.focusedMonth === 0
                ? (this.focusedMonth = 11, this.focusedYear--)
                : this.focusedMonth--
        },

        nextMonth() {
            this.focusedMonth === 11
                ? (this.focusedMonth = 0, this.focusedYear++)
                : this.focusedMonth++
        },

        dateKey(year, month, day) {
            return `${year}-${pad2(month)}-${pad2(day)}`
        },

        get selectedKey() {
            return this.day === null ? null : this.dateKey(this.year, this.month, this.day)
        },

        focusedKey(day) {
            return this.dateKey(this.focusedYear, this.focusedMonth + 1, day)
        },

        dayIsSelected(day) {
            return this.selectedKey === this.focusedKey(day)
        },

        dayIsToday(day) {
            const now = today()

            return this.dateKey(now.year, now.month, now.day) === this.focusedKey(day)
        },

        dayIsDisabled(day) {
            const key = this.focusedKey(day)
            const min = this.lowerDate?.slice(0, 10)
            const max = this.upperDate?.slice(0, 10)

            return (min && key < min) || (max && key > max) || this.config.disabledDates.includes(key)
        },

        selectDay(day) {
            if (this.dayIsDisabled(day)) {
                return
            }

            this.year = this.focusedYear
            this.month = this.focusedMonth + 1
            this.day = day

            this.pullIntoBounds()
            this.syncText()

            if (this.config.closeOnDateSelection || (! this.config.hasTime && this.config.closeOnComplete)) {
                this.confirm()

                return
            }

            if (this.config.hasTime) {
                this.showTime()
            }
        },

        showTime() {
            this.view = 'time'
            this.stage = 'hour'
        },

        showDate() {
            this.view = 'date'
        },

        get isOnCalendar() {
            return this.view !== 'time'
        },

        // The month grid: twelve months of the focused year

        showMonths() {
            if (this.view !== 'years') {
                this.returnView = this.view
            }

            this.view = 'months'
        },

        monthIsDisabled(index) {
            const key = monthKey(this.focusedYear, index + 1)
            const min = this.lowerDate?.slice(0, 7)
            const max = this.upperDate?.slice(0, 7)

            return Boolean((min && key < min) || (max && key > max))
        },

        monthIsSelected(index) {
            return this.year === this.focusedYear && this.month === index + 1
        },

        monthIsCurrent(index) {
            const now = today()

            return now.year === this.focusedYear && now.month === index + 1
        },

        pickMonth(index) {
            if (this.monthIsDisabled(index)) {
                return
            }

            this.focusedMonth = index

            if (this.mode !== 'month') {
                this.view = 'date'

                return
            }

            this.year = this.focusedYear
            this.month = index + 1
            this.day = 1
            this.hour = this.minute = this.second = 0

            if (this.config.closeOnComplete) {
                this.confirm()
            }
        },

        previousYear() {
            this.focusedYear--
        },

        nextYear() {
            this.focusedYear++
        },

        // The year grid: a page of years, paged with the arrows

        pageStartFor(year) {
            const size = this.config.yearsPerPage

            return Math.floor(year / size) * size
        },

        get yearsOnPage() {
            return Array.from({ length: this.config.yearsPerPage }, (_, index) => this.yearsPageStart + index)
        },

        get yearsPageLabel() {
            return `${this.yearsPageStart} – ${this.yearsPageStart + this.config.yearsPerPage - 1}`
        },

        showYears() {
            if (this.view !== 'years') {
                this.returnView = this.view
            }

            this.yearsPageStart = this.pageStartFor(this.focusedYear)
            this.view = 'years'
        },

        previousYears() {
            this.yearsPageStart -= this.config.yearsPerPage
        },

        nextYears() {
            this.yearsPageStart += this.config.yearsPerPage
        },

        yearIsDisabled(year) {
            const min = this.lowerDate ? parseInt(this.lowerDate.slice(0, 4), 10) : null
            const max = this.upperDate ? parseInt(this.upperDate.slice(0, 4), 10) : null

            return Boolean((min !== null && year < min) || (max !== null && year > max))
        },

        yearIsSelected(year) {
            return this.year === year
        },

        yearIsCurrent(year) {
            return today().year === year
        },

        pickYear(year) {
            if (this.yearIsDisabled(year)) {
                return
            }

            this.focusedYear = year

            if (this.mode !== 'year') {
                // Back to whichever grid sent us here: the days, or the months.
                this.view = ['date', 'months'].includes(this.returnView) ? this.returnView : 'date'

                return
            }

            this.year = year
            this.month = 1
            this.day = 1
            this.hour = this.minute = this.second = 0

            if (this.config.closeOnComplete) {
                this.confirm()
            }
        },

        get dateLabel() {
            return this.day === null
                ? ''
                : formatValue({ year: this.year, month: this.month, day: this.day, hour: 0, minute: 0, second: 0 }, 'D, M j', this.config.locale)
        },

        get timeLabel() {
            return this.hour === null
                ? ''
                : formatValue({ ...today(), hour: this.hour, minute: this.minute ?? 0, second: this.second ?? 0 }, this.config.timeDisplayFormat, this.config.locale)
        },

        // Bounds for the selected day: the daily window, narrowed by minDate/maxDate on their own day

        boundOnSelectedDay(value) {
            if (! this.config.hasTime || ! this.config.hasDate || ! value || this.selectedKey !== value.slice(0, 10)) {
                return null
            }

            return parseBound(value.slice(11))
        },

        get minBound() {
            const own = parseBound(this.config.minTime)
            // A time range's To may not come before its From.
            const start = (this.embedded === 'end' && this.mode === 'time') ? parseBound(range?.rangeDraftOf('start')?.slice(11)) : null
            const daily = own === null ? start : (start === null ? own : Math.max(own, start))
            const dated = this.boundOnSelectedDay(this.config.minDate)

            return daily === null ? dated : (dated === null ? daily : Math.max(daily, dated))
        },

        get maxBound() {
            const daily = parseBound(this.config.maxTime)
            const dated = this.boundOnSelectedDay(this.config.maxDate)

            return daily === null ? dated : (dated === null ? daily : Math.min(daily, dated))
        },

        get stageLabel() {
            const labels = this.config.labels

            return {
                hour: labels.selectHour,
                minute: labels.selectMinute,
                second: labels.selectSecond,
            }[this.stage]
        },

        // The hand

        get hasSelection() {
            return this.currentStageValue !== null
        },

        get currentStageValue() {
            return { hour: this.hour, minute: this.minute, second: this.second }[this.stage]
        },

        get handAngle() {
            const value = this.currentStageValue

            if (value === null) {
                return 0
            }

            if (this.stage !== 'hour') {
                return value * 6
            }

            return (value % 12) * 30
        },

        get handLength() {
            return this.stage === 'hour' && this.isInnerHour(this.hour)
                ? INNER_RADIUS
                : OUTER_RADIUS
        },

        isInnerHour(hour) {
            return this.config.hoursCycle === 24 && (hour === 0 || hour >= 13)
        },

        // Numerals

        numeralClass(stage, value) {
            return {
                'fi-chrono-numeral-selected': this.isNumeralSelected(stage, value),
                'fi-chrono-numeral-disabled': ! this.isNumeralAllowed(stage, value),
            }
        },

        isNumeralSelected(stage, value) {
            if (stage === 'minute') {
                return this.minute === value
            }

            if (stage === 'second') {
                return this.second === value
            }

            if (this.hour === null) {
                return false
            }

            return this.config.hoursCycle === 24
                ? this.hour === value
                : this.hour % 12 === value % 12
        },

        // Allowed when any time the numeral could still lead to is inside the bounds.
        isNumeralAllowed(stage, value) {
            const min = this.minBound
            const max = this.maxBound

            if (min === null && max === null) {
                return true
            }

            let from
            let to

            if (stage === 'hour') {
                const hour = this.hourFromNumeral(value, this.config.hoursCycle === 24 && this.isInnerNumeral(value))

                from = toSeconds(hour, 0, 0)
                to = toSeconds(hour, 59, 59)
            } else if (stage === 'minute') {
                const hour = this.hour ?? 0

                from = toSeconds(hour, value, 0)
                to = toSeconds(hour, value, 59)
            } else {
                const point = toSeconds(this.hour ?? 0, this.minute ?? 0, value)

                from = point
                to = point
            }

            if (min !== null && to < min) {
                return false
            }

            return ! (max !== null && from > max)
        },

        isInnerNumeral(value) {
            return value === 0 || value >= 13
        },

        canUseMeridiem(meridiem) {
            const offset = meridiem === 'pm' ? 12 : 0

            for (let hour = offset; hour < offset + 12; hour++) {
                if (this.isNumeralAllowed('hour', this.config.hoursCycle === 24 ? hour : (hour % 12 || 12))) {
                    if (this.config.hoursCycle === 24) {
                        return true
                    }

                    // A 12-hour numeral has no half of day, so check the hour this switch would produce.
                    const min = this.minBound
                    const max = this.maxBound
                    const from = toSeconds(hour, 0, 0)
                    const to = toSeconds(hour, 59, 59)

                    if ((min === null || to >= min) && (max === null || from <= max)) {
                        return true
                    }
                }
            }

            return false
        },

        // Meridiem

        get meridiem() {
            if (this.hour === null) {
                return 'am'
            }

            return this.hour < 12 ? 'am' : 'pm'
        },

        setMeridiem(meridiem) {
            if (this.hour === null) {
                this.hour = meridiem === 'pm' ? 12 : 0
            } else if (meridiem === 'pm' && this.hour < 12) {
                this.hour += 12
            } else if (meridiem === 'am' && this.hour >= 12) {
                this.hour -= 12
            }

            this.pullIntoBounds()
            this.syncText()
        },

        // Pointer

        onPointerDown(event) {
            if (event.button !== undefined && event.button !== 0) {
                return
            }

            this.dragging = true

            try {
                this.$refs.face.setPointerCapture(event.pointerId)
            } catch (exception) {
                // Capture is optional; without it the drag ends when the pointer leaves the face.
            }

            this.applyPointer(event)
            event.preventDefault()
        },

        onPointerMove(event) {
            if (! this.dragging) {
                return
            }

            this.applyPointer(event)
            event.preventDefault()
        },

        onPointerUp(event) {
            if (! this.dragging) {
                return
            }

            this.dragging = false

            // A release on a dimmed numeral picks nothing, so it must not move on to the next dial.
            if (this.applyPointer(event)) {
                this.advanceStage()
            }
        },

        readPointer(event) {
            const rect = this.$refs.face.getBoundingClientRect()
            const scale = FACE_SIZE / rect.width
            const x = ((event.clientX - rect.left) * scale) - FACE_CENTRE
            const y = ((event.clientY - rect.top) * scale) - FACE_CENTRE

            let angle = Math.atan2(x, -y) * (180 / Math.PI)

            if (angle < 0) {
                angle += 360
            }

            return { angle, radius: Math.sqrt((x * x) + (y * y)) }
        },

        applyPointer(event) {
            const { angle, radius } = this.readPointer(event)

            if (this.stage === 'hour') {
                const index = Math.round(angle / 30) % 12
                const isInner = this.config.hoursCycle === 24 && radius < RING_BOUNDARY
                const numeral = this.config.hoursCycle === 24
                    ? (isInner ? (index === 0 ? 0 : index + 12) : (index === 0 ? 12 : index))
                    : (index === 0 ? 12 : index)

                if (! this.isNumeralAllowed('hour', numeral)) {
                    return false
                }

                this.hour = this.hourFromNumeral(numeral, isInner)

                if (this.minute === null) {
                    this.minute = 0
                }

                if (this.second === null && this.config.hasSeconds) {
                    this.second = 0
                }
            } else {
                const step = this.stage === 'minute' ? this.config.minutesStep : this.config.secondsStep
                const value = this.snap(Math.round(angle / 6) % 60, step, 60)

                if (! this.isNumeralAllowed(this.stage, value)) {
                    return false
                }

                this[this.stage] = value
            }

            this.syncText()

            return true
        },

        hourFromNumeral(numeral, isInner) {
            if (this.config.hoursCycle === 24) {
                return isInner ? numeral : numeral % 24
            }

            const base = numeral % 12

            return this.meridiem === 'pm' ? base + 12 : base
        },

        advanceStage() {
            if (this.stage === 'hour') {
                this.stage = 'minute'

                return
            }

            if (this.stage === 'minute' && this.config.hasSeconds) {
                this.stage = 'second'

                return
            }

            if (this.config.closeOnComplete) {
                this.confirm()
            }
        },

        // Typing

        onTyped(part, event) {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 2)

            this[`${part}Text`] = digits

            if (digits === '') {
                return
            }

            const typed = parseInt(digits, 10)

            if (part === 'hour') {
                const max = this.config.hoursCycle === 24 ? 23 : 12
                const min = this.config.hoursCycle === 24 ? 0 : 1

                if (typed > max || (digits.length === 2 && typed < min)) {
                    return
                }

                this.hour = this.config.hoursCycle === 24
                    ? typed
                    : this.hourFromNumeral(typed, false)

                if (this.minute === null) {
                    this.minute = 0
                }
            } else {
                if (typed > 59) {
                    return
                }

                this[part] = typed
            }

            // Move on after two digits, or after a first digit no second digit could extend.
            const cannotExtend = part === 'hour'
                ? typed > (this.config.hoursCycle === 24 ? 2 : 1)
                : typed > 5

            if (digits.length === 2 || cannotExtend) {
                this.$nextTick(() => this.focusNextPart(part))
            }
        },

        focusNextPart(part) {
            if (part === 'hour') {
                this.stage = 'minute'
                this.$refs.minuteInput?.focus()

                return
            }

            if (part === 'minute' && this.config.hasSeconds) {
                this.stage = 'second'
                this.$refs.secondInput?.focus()
            }
        },

        commitTyped() {
            this.pullIntoBounds()
            this.syncText()
        },

        nudge(direction) {
            const part = this.stage

            if (part === 'hour') {
                const step = this.config.hoursStep
                const current = this.hour ?? 0

                this.hour = (current + (direction * step) + 24) % 24
            } else {
                const step = part === 'minute' ? this.config.minutesStep : this.config.secondsStep
                const current = this[part] ?? 0

                this[part] = (current + (direction * step) + 60) % 60

                if (this.hour === null) {
                    this.hour = 0
                }
            }

            this.pullIntoBounds()
            this.syncText()
        },

        syncText() {
            this.hourText = this.hour === null
                ? ''
                : (this.config.hoursCycle === 24
                    ? pad2(this.hour)
                    : String(this.hour % 12 === 0 ? 12 : this.hour % 12))

            this.minuteText = this.minute === null ? '' : pad2(this.minute)
            this.secondText = this.second === null ? '' : pad2(this.second)
        },

        // Bounds

        snap(value, step, wrap) {
            if (step <= 1) {
                return value
            }

            return (Math.round(value / step) * step) % wrap
        },

        // Typing and arrow keys can reach times the dial refuses, so clamp the draft here.
        pullIntoBounds() {
            if (! this.config.hasTime || this.hour === null) {
                return
            }

            const min = this.minBound
            const max = this.maxBound
            const current = toSeconds(this.hour, this.minute ?? 0, this.second ?? 0)
            const bounded = clamp(current, min ?? current, max ?? current)

            if (bounded === current) {
                return
            }

            this.hour = Math.floor(bounded / 3600)
            this.minute = Math.floor((bounded % 3600) / 60)
            this.second = this.config.hasSeconds ? bounded % 60 : 0
        },
    }
}
