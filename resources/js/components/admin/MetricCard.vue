<template>
  <div class="metric-card" :class="[`variant-${variant}`]">
    <div class="mc-icon" v-if="icon">
      <component :is="icon" />
    </div>
    <div class="mc-body">
      <p class="mc-label">{{ label }}</p>
      <p class="mc-value">
        <slot>{{ value }}</slot>
      </p>
      <p v-if="sub" class="mc-sub">{{ sub }}</p>
    </div>
    <div v-if="trend !== undefined" class="mc-trend" :class="trend >= 0 ? 'up' : 'down'">
      <span>{{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label:   { type: String, required: true },
  value:   { type: [String, Number], default: '' },
  sub:     { type: String, default: '' },
  icon:    { type: Object, default: null },
  trend:   { type: Number, default: undefined },
  variant: { type: String, default: 'default' },
})
</script>

<style scoped>
.metric-card {
  background: #fff; border-radius: 12px; padding: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,.07); display: flex; align-items: flex-start; gap: 16px;
  transition: box-shadow .2s;
}
.metric-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.mc-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.variant-blue   .mc-icon { background: #dbeafe; color: #2563EB; }
.variant-green  .mc-icon { background: #dcfce7; color: #16a34a; }
.variant-yellow .mc-icon { background: #fef9c3; color: #d97706; }
.variant-red    .mc-icon { background: #fee2e2; color: #dc2626; }
.variant-default .mc-icon { background: #f1f5f9; color: #475569; }
.mc-body { flex: 1; min-width: 0; }
.mc-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.mc-value { font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1.1; }
.mc-sub   { font-size: 12px; color: #64748b; margin-top: 4px; }
.mc-trend { font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
.mc-trend.up   { background: #dcfce7; color: #15803d; }
.mc-trend.down { background: #fee2e2; color: #b91c1c; }
</style>
