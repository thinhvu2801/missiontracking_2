<!-- <template>
  <div class="stat-card" :class="toneClass">
    <div class="stat-title">{{ title }}</div>
    <div class="stat-value">
      <span class="num">{{ value }}</span>
      <span v-if="suffix" class="suffix">{{ suffix }}</span>
    </div>
    <div v-if="hint" class="stat-hint">{{ hint }}</div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], default: 0 },
  suffix: { type: String, default: "" },
  hint: { type: String, default: "" },
  tone: { type: String, default: "neutral" }, // neutral|good|warn|bad|info
});

const toneClass = computed(() => `tone-${props.tone}`);
</script>

<style scoped>
.stat-card{
  border:1px solid #e5e7eb;
  border-radius:12px;
  padding:12px 12px;
  background:#fff;
  min-height:78px;
}
.stat-title{ font-size:12px; color:#6b7280; font-weight:700; }
.stat-value{ margin-top:4px; display:flex; align-items:baseline; gap:6px; }
.num{ font-size:22px; font-weight:800; color:#111827; letter-spacing:.2px; }
.suffix{ font-size:12px; color:#6b7280; font-weight:700; }
.stat-hint{ margin-top:6px; font-size:12px; color:#4b5563; }

.tone-good{ border-color:#bbf7d0; background:#f0fdf4; }
.tone-warn{ border-color:#fde68a; background:#fffbeb; }
.tone-bad{ border-color:#fecaca; background:#fef2f2; }
.tone-info{ border-color:#bfdbfe; background:#eff6ff; }
</style> -->