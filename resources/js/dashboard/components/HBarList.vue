<template>
  <div class="hbar">
    <div class="head">
      <div class="title">{{ title }}</div>
      <div v-if="subTitle" class="sub">{{ subTitle }}</div>
    </div>

    <div v-if="!safeRows.length" class="empty">
      Không có dữ liệu.
    </div>

    <!-- body tự cuộn, không đẩy layout xuống -->
    <div v-else class="body">
      <div v-for="(r, idx) in safeRows" :key="idx" class="item">
        <div class="left">
          <div class="rank">#{{ idx + 1 }}</div>
          <div
            class="label"
            :class="{ clickable: selectable }"
            :title="labelText(r)"
            @click="selectable && pick(r, idx)"
          >
            {{ labelText(r) }}
          </div>
        </div>

        <div class="mid">
          <div class="track">
            <div class="fill" :style="{ width: barWidth(r) }"></div>
          </div>
        </div>

        <div class="right">
          <div class="value">{{ valueText(r) }}</div>
          <div v-if="valueAsPercent" class="unit">%</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "HBarList",
  props: {
    title: { type: String, default: "" },
    subTitle: { type: String, default: "" },
    rows: { type: Array, default: () => [] },

    labelKey: { type: String, default: "label" },
    valueKey: { type: String, default: "value" },

    // percent mode
    valueAsPercent: { type: Boolean, default: true }, // true => 0..100
    valueIsRatio: { type: Boolean, default: false },  // true => 0..1 (x100)
    decimals: { type: Number, default: 1 },

    // absolute mode => scale theo max
    scaleToMaxWhenNotPercent: { type: Boolean, default: true },

    // enable click-to-select on label
    selectable: { type: Boolean, default: false },
  },
  computed: {
    safeRows() {
      return Array.isArray(this.rows) ? this.rows : [];
    },
    maxAbsValue() {
      if (this.valueAsPercent) return 100;
      const vals = this.safeRows.map(r => Number(r?.[this.valueKey] ?? 0)).filter(v => !Number.isNaN(v));
      const mx = Math.max(0, ...vals);
      return mx || 1;
    },
  },
  methods: {
    pick(row, idx) {
      this.$emit("select", row, idx);
    },
    labelText(r) {
      const v = r?.[this.labelKey];
      return v === null || v === undefined || String(v).trim() === "" ? "(Không tên)" : String(v);
    },
    rawValue(r) {
      const v = Number(r?.[this.valueKey] ?? 0);
      return Number.isNaN(v) ? 0 : v;
    },
    percentValue(r) {
      const v = this.rawValue(r);

      if (this.valueAsPercent) {
        const pv = this.valueIsRatio ? v * 100 : v;
        return Math.max(0, Math.min(100, pv));
      }

      if (!this.scaleToMaxWhenNotPercent) return 0;
      return Math.max(0, Math.min(100, (v / this.maxAbsValue) * 100));
    },
    barWidth(r) {
      return `${this.percentValue(r).toFixed(2)}%`;
    },
    valueText(r) {
      const v = this.rawValue(r);

      if (this.valueAsPercent) {
        const pv = this.valueIsRatio ? v * 100 : v;
        return pv.toFixed(this.decimals);
      }

      // absolute number
      return `${Math.round(v)}`;
    },
    hintText(r) {
      // hint nhỏ bên dưới track: % (nếu percent mode) hoặc "so với max"
      if (this.valueAsPercent) {
        const pv = this.percentValue(r);
        return `${pv.toFixed(1)}%`;
      }
      const v = this.rawValue(r);
      return `~ ${(v / this.maxAbsValue * 100).toFixed(1)}% so với max (${this.maxAbsValue})`;
    },
  },
};
</script>

<style scoped>
/* Bắt buộc: component tự fit chiều cao card */
.hbar {
  height: 100%;
  min-height: 0;              /* quan trọng để overflow hoạt động */
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Header gọn */
.head {
  flex: 0 0 auto;
}
.title {
  font-weight: 900;
  color: #1a202c;
  letter-spacing: 0.2px;
  font-size: 14px;
  line-height: 1.1;
}
.sub {
  margin-top: 4px;
  font-size: 12px;
  color: #718096;
}

/* Empty */
.empty {
  flex: 1 1 auto;
  min-height: 0;
  display: grid;
  place-items: center;
  border: 1px dashed #e2e8f0;
  border-radius: 12px;
  color: #718096;
}

/* Body: tự cuộn, không làm page scroll */
.body {
  flex: 1 1 auto;
  min-height: 0;              /* quan trọng */
  overflow: auto;
  padding-right: 6px;         /* chừa chỗ scrollbar */
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Item layout: 3 cột, chống tràn ngang */
.item {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.6fr) 64px;
  gap: 12px;
  align-items: center;

  padding: 10px 10px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: #fff;
}

/* LEFT: rank + label */
.left {
  display: flex;
  gap: 8px;
  align-items: center;
  min-width: 0;
}
.rank {
  flex: 0 0 auto;
  font-size: 11px;
  font-weight: 800;
  color: #4a5568;
  background: #f7fafc;
  border: 1px solid #edf2f7;
  border-radius: 999px;
  padding: 2px 8px;
}
.label {
  min-width: 0;
  font-size: 13px;
  color: #2d3748;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.label.clickable {
  cursor: pointer;
  text-decoration: underline;
  text-decoration-thickness: 1px;
  text-underline-offset: 2px;
}

/* MID: track + hint */
.mid {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.track {
  height: 10px;
  border-radius: 999px;
  background: #edf2f7;
  overflow: hidden;
}
.fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #2b6cb0, #63b3ed);
}
.hint {
  font-size: 11px;
  color: #718096;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* RIGHT: value */
.right {
  display: flex;
  justify-content: flex-end;
  align-items: baseline;
  gap: 4px;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.value {
  font-size: 14px;
  font-weight: 900;
  color: #1a202c;
}
.unit {
  font-size: 11px;
  color: #718096;
}

/* Responsive: giảm padding & cột value */
@media (max-width: 1400px) {
  .item {
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.6fr) 56px;
    padding: 9px 9px;
  }
}
</style>