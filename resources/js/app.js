import { createApp } from "vue";
import "../css/app.css";

import OverviewDashboard from "./dashboard/OverviewDashboard.vue";

const overviewElement = document.getElementById("overview-dashboard-app");
if (overviewElement) {
  createApp(OverviewDashboard).mount(overviewElement);
}