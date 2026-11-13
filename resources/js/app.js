import './bootstrap';

// Drag and drop for the timetable board. The server re-checks every drop;
// the colours here only warn early that a teacher is busy.
window.timetableBoard = () => ({
    drag: null,
    over: null,

    begin(event, drag) {
        // Firefox will not start a drag without data.
        event.dataTransfer.setData('text/plain', 'timetable');
        event.dataTransfer.effectAllowed = 'move';
        this.drag = drag;
    },

    startNew(event, subject, staff) {
        this.begin(event, { kind: 'new', subject, staff });
    },

    startMove(event, id, staff) {
        this.begin(event, { kind: 'move', id, staff });
    },

    end() {
        this.drag = null;
        this.over = null;
    },

    // The other class the dragged teacher is in during this slot, if any.
    busyIn(slot) {
        if (!this.drag?.staff) return null;

        return this.$wire.busyMap['s' + this.drag.staff]?.[slot] ?? null;
    },

    cellClass(slot) {
        if (!this.drag) return '';
        if (this.busyIn(slot)) return 'bg-red-100 ring-2 ring-red-300';

        return this.over === slot ? 'bg-brand-100 ring-2 ring-brand-500' : 'bg-brand-50/60';
    },

    drop(period, day) {
        const drag = this.drag;
        this.end();
        if (!drag) return;

        if (drag.kind === 'new') {
            this.$wire.placeEntry(period, day, drag.subject, drag.staff);
        } else {
            this.$wire.moveEntry(drag.id, period, day);
        }
    },

    dropTrash() {
        const drag = this.drag;
        this.end();
        if (drag?.kind === 'move') this.$wire.removeEntry(drag.id);
    },
});
