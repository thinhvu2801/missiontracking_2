<template>
  <div class="tiles">
    <div v-for="(t, idx) in tiles" :key="idx" class="tile">
      <div class="k">{{ t.label }}</div>
      <div class="v">{{ formatNumber(t.value) }}</div>
      <div v-if="t.note" class="n">{{ t.note }}</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "StatTiles",
  props: {
    tiles: { type: Array, default: () => [] }, // [{label,value,note}]
  },
  methods: {
    formatNumber(n) {
      const x = Number(n) || 0;
      return x.toLocaleString("vi-VN");
    },
  },
};
</script>

<style scoped>
.tiles{
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  height: 100%;
}
.tile{
  border: 1px solid #e6edf5;
  border-radius: 10px;
  padding: 10px 12px;
  background: #fff;
  overflow: hidden;
}
.k{
  font-size: 11px;
  color: #627d98;
  font-weight: 700;
  margin-bottom: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.v{
  font-size: 20px;
  font-weight: 900;
  color: #102a43;
  line-height: 1.1;
}
.n{
  margin-top: 6px;
  font-size: 11px;
  color: #829ab1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>