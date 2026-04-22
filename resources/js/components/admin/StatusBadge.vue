<template>
  <span class="status-badge" :class="`badge-${variant}`">
    <span v-if="dot" class="badge-dot" />
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
  dot:    { type: Boolean, default: false },
})

const MAP = {
  active:    { variant: 'green',  label: 'Active' },
  inactive:  { variant: 'gray',   label: 'Inactive' },
  suspended: { variant: 'red',    label: 'Suspended' },
  draft:     { variant: 'gray',   label: 'Draft' },
  assigned:  { variant: 'blue',   label: 'Assigned' },
  submitted: { variant: 'yellow', label: 'Submitted' },
  reviewed:  { variant: 'purple', label: 'Reviewed' },
  approved:  { variant: 'green',  label: 'Approved' },
  rejected:  { variant: 'red',    label: 'Rejected' },
  pending:   { variant: 'yellow', label: 'Pending' },
  open:        { variant: 'blue',  label: 'Open' },
  in_progress: { variant: 'yellow', label: 'In Progress' },
  completed:   { variant: 'green',  label: 'Completed' },
  overdue:     { variant: 'red',    label: 'Overdue' },
  cancelled:   { variant: 'gray',   label: 'Cancelled' },
  low:      { variant: 'gray',   label: 'Low' },
  medium:   { variant: 'yellow', label: 'Medium' },
  high:     { variant: 'orange', label: 'High' },
  critical: { variant: 'red',    label: 'Critical' },
}

const variant = computed(() => MAP[props.status]?.variant ?? 'gray')
const label   = computed(() => MAP[props.status]?.label   ?? props.status)
</script>

<style scoped>
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 600;
  white-space: nowrap; text-transform: capitalize;
}
.badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.badge-green  { background: #dcfce7; color: #15803d; }
.badge-blue   { background: #dbeafe; color: #1d4ed8; }
.badge-yellow { background: #fef9c3; color: #a16207; }
.badge-red    { background: #fee2e2; color: #b91c1c; }
.badge-gray   { background: #f1f5f9; color: #475569; }
.badge-purple { background: #f3e8ff; color: #7e22ce; }
.badge-orange { background: #ffedd5; color: #c2410c; }
</style>
