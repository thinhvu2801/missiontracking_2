<template>
  <div class="section-card">
    <div class="section-head">
      <div class="section-title">{{ title }}</div>
      <div v-if="subtitle" class="section-subtitle">{{ subtitle }}</div>
      <div v-if="$slots.right" class="section-right">
        <slot name="right" />
      </div>
    </div>

    <div class="section-body">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: "" },
});
</script>

<style scoped>
.section-card{
  background:#fff;
  border:1px solid #e5e7eb;
  border-radius:12px;
  box-shadow:0 1px 2px rgba(0,0,0,.04);
  overflow:hidden;
}
.section-head{
  padding:12px 14px;
  border-bottom:1px solid #eef2f7;
  display:flex;
  align-items:flex-start;
  gap:12px;
}
.section-title{ font-weight:700; font-size:14px; color:#111827; }
.section-subtitle{ margin-top:2px; font-size:12px; color:#6b7280; }
.section-right{ margin-left:auto; }
.section-body{ padding:12px 14px; }
</style>