<template>
  <div class="donut-wrap">
    <div class="donut">
      <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
        <!-- background ring -->
        <circle
          :cx="c"
          :cy="c"
          :r="r"
          class="ring-bg"
        />
        <!-- segments -->
        <circle
          v-for="(seg, idx) in segments"
          :key="idx"
          :cx="c"
          :cy="c"
          :r="r"
          class="ring-seg ring-click"
          :style="{
            stroke: seg.color,
            strokeDasharray: `${seg.len} ${circumference - seg.len}`,
            strokeDashoffset: seg.offset,
          }"
          @click="pick(items[idx], idx)"
        />
        <!-- center text -->
        <text :x="c" :y="c - 2" text-anchor="middle" class="center-title">
          {{ centerTitle }}
        </text>
        <text :x="c" :y="c + 18" text-anchor="middle" class="center-sub">
          {{ centerSub }}
        </text>
      </svg>
    </div>

    <div class="legend">
      <div
        v-for="(it, i) in items"
        :key="i"
        class="legend-row legend-click"
        @click="pick(it, i)"
      >
        <span class="dot" :style="{ background: it.color || '#999' }"></span>
        <span class="label">{{ it.label }}</span>
        <span class="value">{{ fmt(it.value) }}</span>
        <span class="pct">{{ percent(it.value) }}%</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "DonutChart",
  props: {
    items: { type: Array, default: () => [] }, // [{label,value,color}]
    size: { type: Number, default: 170 },
    centerLabel: { type: String, default: "" },
    centerValue: { type: [String, Number], default: "" },
  },
  computed: {
    total() {
      return this.items.reduce((s, x) => s + (Number(x.value) || 0), 0);
    },
    c() {
      return this.size / 2;
    },
    r() {
      return Math.max(10, this.size / 2 - 18);
    },
    circumference() {
      return 2 * Math.PI * this.r;
    },
    segments() {
      const total = this.total || 1;
      let acc = 0;
      return this.items.map((it) => {
        const v = Number(it.value) || 0;
        const len = (v / total) * this.circumference;
        const offset = -acc;
        acc += len;
        return {
          color: it.color || "#999",
          len,
          offset,
        };
      });
    },
    centerTitle() {
      return this.centerLabel || "";
    },
    centerSub() {
      const v = this.centerValue !== "" ? this.centerValue : this.total;
      return `${this.fmt(v)}`;
    },
  },
  methods: {
    pick(it, idx) {
      this.$emit("select", it, idx);
    },
    fmt(v) {
      const n = Number(v);
      if (!Number.isFinite(n)) return String(v ?? "");
      return n.toLocaleString("vi-VN");
    },
    percent(v) {
      const total = this.total;
      if (!total) return 0;
      return Math.round(((Number(v) || 0) / total) * 100);
    },
  },
};
</script>

<style scoped>
.donut-wrap{
  display:flex;
  gap:14px;
  align-items:center;
}
.donut{
  width:170px;
  height:170px;
  display:flex;
  align-items:center;
  justify-content:center;
}
.ring-bg{
  fill:none;
  stroke:#edf2f7;
  stroke-width:16;
}
.ring-seg{
  fill:none;
  stroke-width:16;
  stroke-linecap:butt;
  transform: rotate(-90deg);
  transform-origin: 50% 50%;
}
.ring-click{ cursor: pointer; }
.center-title{  
  font-size:14px;
  font-weight:700;
  fill:#2d3748;
}
.center-sub{
  font-size:16px;
  font-weight:800;
  fill:#1a202c;
}
.legend{
  flex:1;
  min-width: 180px;
}
.legend-row{
  display:flex;
  align-items:center;
  gap:8px;
  padding:4px 0;
  border-bottom: 1px dashed #edf2f7;
}
.legend-click{ cursor: pointer; }
.dot{
  width:10px;
  height:10px;
  border-radius:50%;
  display:inline-block;
}
.label{
  flex:1;
  color:#2d3748;
  font-size:13px;
}
.value{
  width:64px;
  text-align:right;
  font-weight:700;
  color:#1a202c;
}
.pct{
  width:44px;
  text-align:right;
  color:#718096;
  font-weight:700;
}
</style>