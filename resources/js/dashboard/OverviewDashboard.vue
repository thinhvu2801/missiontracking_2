<template>
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox">
                <div class="ibox-title">
                    <h2>DASHBOARD TỔNG HỢP NHIỆM VỤ</h2>
                </div>

                <div class="ibox-content">
                    <!-- Filters Loading -->
                    <div v-if="loading" class="alert alert-info">
                        Đang tải bộ lọc...
                    </div>

                    <!-- Filters Error -->
                    <div v-else-if="errorMessage" class="alert alert-danger">
                        {{ errorMessage }}
                    </div>

                    <!-- No Filters -->
                    <div
                        v-else-if="!resolutions.length || !reportPeriods.length"
                        class="alert alert-warning"
                    >
                        <div>
                            Dữ liệu bộ lọc đang rỗng. Vui lòng kiểm tra API
                            <strong>/dashboard/overview/filters</strong>.
                        </div>
                    </div>

                    <!-- MAIN -->
                    <div v-else class="page">
                        <!-- FILTER BAR -->
                        <div class="filter-bar">
                            <div class="filter-item w-280">
                                <label>Văn bản</label>
                                <select
                                    class="form-control"
                                    v-model.number="selectedResolutionId"
                                    @change="handleResolutionChange"
                                >
                                    <option
                                        v-for="r in resolutions"
                                        :key="r.id"
                                        :value="r.id"
                                    >
                                        {{ r.resolution_code }}
                                    </option>
                                </select>
                            </div>

                            <div class="filter-item w-180">
                                <label>Loại kỳ</label>
                                <select
                                    class="form-control"
                                    v-model="selectedPeriodType"
                                    @change="handlePeriodTypeChange"
                                >
                                    <option
                                        v-for="t in periodTypeOptions"
                                        :key="t.value"
                                        :value="t.value"
                                    >
                                        {{ t.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="filter-item w-140">
                                <label>Năm</label>
                                <select
                                    class="form-control"
                                    v-model.number="selectedReportYear"
                                    @change="handleReportYearChange"
                                >
                                    <option
                                        v-for="y in reportYearOptions"
                                        :key="y"
                                        :value="y"
                                    >
                                        {{ y }}
                                    </option>
                                </select>
                            </div>

                            <div class="filter-item w-360">
                                <label>Kỳ báo cáo</label>
                                <select
                                    class="form-control"
                                    v-model.number="selectedReportPeriodId"
                                    @change="handlePeriodChange"
                                >
                                    <option
                                        v-for="p in filteredReportPeriods"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ formatPeriodLabel(p) }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- DATA Loading/Error -->
                        <div
                            v-if="dataLoading"
                            class="alert alert-info"
                            style="margin-top: 10px"
                        >
                            Đang tải dữ liệu dashboard...
                        </div>
                        <div
                            v-else-if="dataErrorMessage"
                            class="alert alert-danger"
                            style="margin-top: 10px"
                        >
                            {{ dataErrorMessage }}
                        </div>

                        <!-- DASHBOARD GRID -->
                        <div v-else class="grid">
                            <!-- Donut: tình trạng nhiệm vụ -->
                            <div class="cardx card-status">
                                <div class="cardx-title">
                                    Tình trạng nhiệm vụ
                                </div>
                                <DonutChart
                                    :items="donutMissionStatus"
                                    center-label="Tổng nhiệm vụ"
                                    :center-value="
                                        missionSummary.total_missions
                                    "
                                    @select="onMissionStatusSelect"
                                />
                                <div class="note">
                                    <b>Đã hoàn thành</b>: tất cả đơn vị báo cáo
                                    & hoàn thành<br />
                                    <b>Chưa xử lý</b>: không có đơn vị nào báo
                                    cáo<br />
                                    <b>Đang xử lý</b>: các trường hợp còn lại
                                </div>
                            </div>

                            <!-- Donut: đúng hạn/trễ -->
                            <div class="cardx card-ontime">
                                <div class="cardx-title">
                                    Đúng hạn / Trễ hạn
                                </div>
                                <DonutChart
                                    :items="donutOnTime"
                                    :center-label="`Đã báo cáo (đủ):`"
                                    :center-value="
                                        missionSummary.reported_all_missions
                                    "
                                    @select="onDeadlineStatusSelect"
                                />
                                <div class="note">
                                    Mẫu số =
                                    <b
                                        >số nhiệm vụ đã báo cáo (đủ tất cả đơn
                                        vị)</b
                                    ><br />
                                    Đúng hạn / Trễ hạn tính theo <b>deadline</b> của nhiệm vụ.
                                </div>
                            </div>

                            <!-- Right: Top group (span 2 rows) -->
                            <div class="cardx cardx-tall card-group">
                                <HBarList
                                    title="Top nhóm nhiệm vụ (theo % hoàn thành)"
                                    :rows="missionByGroup"
                                    value-key="completion_rate"
                                    label-key="group_name"
                                    :value-as-percent="true"
                                    :value-is-ratio="false"
                                />
                            </div>

                            <!-- Bottom-left (span 2 cols): Top backlog agencies -->
                            <div class="cardx card-backlog">
                                <HBarList
                                    title="Top 5 cơ quan có nhiệm vụ tồn đọng nhiều nhất"
                                    :rows="topBacklogAgencies"
                                    value-key="backlog_total"
                                    label-key="agency_name"
                                    :value-as-percent="false"
                                    :value-is-ratio="false"
                                    :selectable="true"
                                    @select="onBacklogAgencySelect"
                                />
                            </div>
                        </div>

                        <!-- DEBUG -->
                        <div class="well" style="margin-top: 10px">
                            <strong>Debug:</strong>
                            <pre
                                style="
                                    margin-top: 8px;
                                    max-height: 220px;
                                    overflow: auto;
                                "
                                >{{ debugInfo }}</pre
                            >
                        </div>
                    </div>
                    <!-- /MAIN -->
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import DonutChart from "./components/DonutChart.vue";
import HBarList from "./components/HBarList.vue";

export default {
    name: "OverviewDashboard",
    components: { DonutChart, HBarList },
    data() {
        const now = new Date();
        return {
            loading: false,
            errorMessage: "",
            resolutions: [],
            reportPeriods: [],
            selectedResolutionId: null,
            selectedReportPeriodId: null,

            // Filter UI: mặc định kỳ tháng + tháng hiện tại
            allowedPeriodTypes: [],
            selectedPeriodType: "month",
            selectedReportYear: now.getFullYear(),

            dataLoading: false,
            dataErrorMessage: "",
            dataHttpStatus: null,
            dataErrorPayload: null,
            dashboardData: null,
        };
    },
    computed: {
        // ===== Options for filters =====
        periodTypeOptions() {
            // allowedPeriodTypes có thể là ["month","quarter"] hoặc [{value,label}]
            const raw = Array.isArray(this.allowedPeriodTypes)
                ? this.allowedPeriodTypes
                : [];
            const norm = raw
                .map((t) => {
                    if (typeof t === "string")
                        return { value: t, label: this.prettyPeriodType(t) };
                    if (t && typeof t === "object") {
                        const v = t.value ?? t.period_type ?? t.type ?? "";
                        const label = t.label ?? this.prettyPeriodType(v);
                        return v
                            ? { value: String(v), label: String(label) }
                            : null;
                    }
                    return null;
                })
                .filter(Boolean);

            // fallback nếu API không trả allowedPeriodTypes
            if (!norm.length) {
                return [
                    { value: "month", label: "Tháng" },
                    { value: "quarter", label: "Quý" },
                    { value: "year", label: "Năm" },
                ];
            }

            // đảm bảo tháng luôn có mặt (nếu DB có)
            const hasMonth = norm.some((x) => x.value === "month");
            if (!hasMonth) norm.unshift({ value: "month", label: "Tháng" });
            return norm;
        },
        reportYearOptions() {
            const years = new Set();
            (this.reportPeriods || []).forEach((p) => {
                const y = Number(p?.report_year);
                if (Number.isFinite(y)) years.add(y);
            });
            const arr = Array.from(years).sort((a, b) => b - a);
            // nếu rỗng, cho năm hiện tại
            if (!arr.length) arr.push(new Date().getFullYear());
            return arr;
        },
        filteredReportPeriods() {
            const type = this.selectedPeriodType;
            const year = Number(this.selectedReportYear);

            return (this.reportPeriods || []).filter((p) => {
                const t = this.getPeriodTypeValue(p?.period_type);
                const y = Number(p?.report_year);
                return (!type || t === type) && (!year || y === year);
            });
        },

        // ===== Data =====
        missionSummary() {
            const s = this.dashboardData?.mission?.summary || {};

            // merge để dù API thiếu key thì UI vẫn có số 0 để render
            return {
                total_missions: 0,
                completed_missions: 0,
                not_started_missions: 0,
                in_progress_missions: 0,
                reported_all_missions: 0,
                on_time_missions: 0,
                late_missions: 0,
                ...s,
            };
        },
        missionByGroup() {
            return this.dashboardData?.mission?.by_group || [];
        },
        topBacklogAgencies() {
            return this.dashboardData?.mission?.top_backlog_agencies || [];
        },

        donutMissionStatus() {
            const s = this.missionSummary;
            return [
                {
                    label: "Đã hoàn thành",
                    value: Number(s.completed_missions || 0),
                    color: "#2f855a",
                },
                {
                    label: "Chưa xử lý",
                    value: Number(s.not_started_missions || 0),
                    color: "#718096",
                },
                {
                    label: "Đang xử lý",
                    value: Number(s.in_progress_missions || 0),
                    color: "#b7791f",
                },
            ];
        },
        donutOnTime() {
            const s = this.missionSummary;
            return [
                {
                    label: "Đúng hạn",
                    value: Number(s.on_time_missions || 0),
                    color: "#2b6cb0",
                },
                {
                    label: "Trễ hạn",
                    value: Number(s.late_missions || 0),
                    color: "#c53030",
                },
            ];
        },

        debugInfo() {
            return JSON.stringify(
                {
                    selectedResolutionId: this.selectedResolutionId,
                    selectedPeriodType: this.selectedPeriodType,
                    selectedReportYear: this.selectedReportYear,
                    selectedReportPeriodId: this.selectedReportPeriodId,
                    resolutionsCount: this.resolutions.length,
                    reportPeriodsCount: this.reportPeriods.length,
                    filteredReportPeriodsCount: this.filteredReportPeriods.length,

                    dataLoading: this.dataLoading,
                    dataHttpStatus: this.dataHttpStatus,
                    dataErrorPayload: this.dataErrorPayload,

                    // extra debug
                    dashboardDataType: this.dashboardData === null ? null : typeof this.dashboardData,
                    dashboardDataKeys: this.dashboardData && typeof this.dashboardData === "object"
                        ? Object.keys(this.dashboardData)
                        : null,
                    missionSummary: this.missionSummary,
                },
                null,
                2,
            );
        },
    },
    mounted() {
        this.loadFilters();
    },
    methods: {
        // ===== Redirect builder (format y như link mẫu) =====
        buildMissionDashboardUrl(extra = {}) {
            const params = {
                resolution_id: this.selectedResolutionId || "",
                report_year: this.selectedReportYear || "",
                period_type: this.selectedPeriodType || "",
                report_period_id: this.selectedReportPeriodId || "",
                agency_id: "",
                report_status: "",
                complete_status: "",
                deadline_status: "",
                ...extra,
            };

            const qs = new URLSearchParams();
            Object.keys(params).forEach((k) => {
                qs.set(
                    k,
                    params[k] === null || params[k] === undefined
                        ? ""
                        : String(params[k]),
                );
            });
            return `/missions/dashboard?${qs.toString()}`;
        },

        // ===== Click donut mission status =====
        onMissionStatusSelect(item) {
            if (!item || !item.label) return;
            const label = String(item.label).trim();

            if (label === "Đã hoàn thành") {
                window.location.href = this.buildMissionDashboardUrl({
                    complete_status: "completed",
                });
                return;
            }

            if (label === "Chưa xử lý") {
                window.location.href = this.buildMissionDashboardUrl({
                    report_status: "not_reported",
                    complete_status: "not_completed",
                });
                return;
            }

            if (label === "Đang xử lý") {
                window.location.href = this.buildMissionDashboardUrl({
                    report_status: "reported",
                    complete_status: "not_completed",
                });
                return;
            }
        },

        // ===== Click donut deadline status =====
        onDeadlineStatusSelect(item) {
            if (!item || !item.label) return;
            const label = String(item.label).trim();

            if (label === "Đúng hạn") {
                window.location.href = this.buildMissionDashboardUrl({
                    deadline_status: "on_time",
                });
                return;
            }

            if (label === "Trễ hạn") {
                window.location.href = this.buildMissionDashboardUrl({
                    deadline_status: "overdue",
                });
                return;
            }
        },

        // ===== Click Top backlog agencies (on label) =====
        onBacklogAgencySelect(row) {
            if (!row) return;
            const id = row.agency_id ?? row.id;
            if (id === null || id === undefined || String(id).trim() === "")
                return;

            window.location.href = this.buildMissionDashboardUrl({
                agency_id: String(id),
                deadline_status: "overdue",
            });
        },

        // ===== Load filters =====
        async loadFilters(resolutionId = null) {
            this.loading = true;
            this.errorMessage = "";
            try {
                const response = await axios.get(
                    "/dashboard/overview/filters",
                    {
                        params: resolutionId
                            ? { resolution_id: resolutionId }
                            : {},
                    },
                );

                const payload = response.data || {};
                this.resolutions = Array.isArray(payload.resolutions)
                    ? payload.resolutions
                    : [];
                this.reportPeriods = Array.isArray(payload.reportPeriods)
                    ? payload.reportPeriods
                    : [];
                this.allowedPeriodTypes = Array.isArray(
                    payload.allowedPeriodTypes,
                )
                    ? payload.allowedPeriodTypes
                    : [];

                const now = new Date();
                const currentYear = now.getFullYear();
                const currentMonth = now.getMonth() + 1;

                // default resolution
                const defaultResolutionId =
                    payload.defaultResolutionId ??
                    (this.resolutions[0] ? this.resolutions[0].id : null);
                this.selectedResolutionId =
                    defaultResolutionId !== null
                        ? Number(defaultResolutionId)
                        : null;

                // default period type = month (nếu không có month trong options thì lấy option đầu tiên)
                const typeOpts = this.periodTypeOptions;
                if (!typeOpts.some((x) => x.value === "month")) {
                    this.selectedPeriodType = typeOpts[0]?.value || "month";
                } else {
                    this.selectedPeriodType = "month";
                }

                // default year = current year nếu tồn tại trong data, không thì year mới nhất
                if (this.reportYearOptions.includes(currentYear)) {
                    this.selectedReportYear = currentYear;
                } else {
                    this.selectedReportYear =
                        this.reportYearOptions[0] || currentYear;
                }

                // default report period: tháng hiện tại (nếu có), fallback period mới nhất trong năm/type
                this.selectedReportPeriodId = this.pickDefaultReportPeriodId({
                    periodType: this.selectedPeriodType,
                    reportYear: this.selectedReportYear,
                    preferNumber:
                        this.selectedPeriodType === "month"
                            ? currentMonth
                            : null,
                });

                // nếu API trả defaultReportPeriodId thì ưu tiên nhưng vẫn ép lọc đúng type/year
                const apiDefaultPid = payload.defaultReportPeriodId ?? null;
                if (apiDefaultPid) {
                    const apiPeriod = (this.reportPeriods || []).find(
                        (p) => Number(p.id) === Number(apiDefaultPid),
                    );
                    const okType = apiPeriod
                        ? this.getPeriodTypeValue(apiPeriod.period_type) ===
                          this.selectedPeriodType
                        : false;
                    const okYear = apiPeriod
                        ? Number(apiPeriod.report_year) ===
                          Number(this.selectedReportYear)
                        : false;
                    if (apiPeriod && okType && okYear)
                        this.selectedReportPeriodId = Number(apiDefaultPid);
                }

                if (this.selectedResolutionId && this.selectedReportPeriodId) {
                    await this.loadDashboardData();
                }
            } catch (err) {
                const status = err?.response?.status;
                const data = err?.response?.data;
                this.errorMessage = status
                    ? `Không thể tải bộ lọc. HTTP ${status}.`
                    : "Không thể tải bộ lọc. Không nhận được phản hồi từ server.";
                if (data) {
                    this.errorMessage +=
                        " Chi tiết: " +
                        (typeof data === "string"
                            ? data
                            : JSON.stringify(data));
                }
            } finally {
                this.loading = false;
            }
        },

        pickDefaultReportPeriodId({
            periodType,
            reportYear,
            preferNumber = null,
        }) {
            const list = (this.reportPeriods || []).filter((p) => {
                const t = this.getPeriodTypeValue(p?.period_type);
                const y = Number(p?.report_year);
                return t === periodType && y === Number(reportYear);
            });

            if (!list.length)
                return this.reportPeriods[0]
                    ? Number(this.reportPeriods[0].id)
                    : null;

            if (preferNumber !== null && preferNumber !== undefined) {
                const hit = list.find(
                    (p) => Number(p?.period_number) === Number(preferNumber),
                );
                if (hit) return Number(hit.id);
            }

            // fallback: period_number lớn nhất
            const sorted = [...list].sort(
                (a, b) => Number(b.period_number) - Number(a.period_number),
            );
            return Number(sorted[0].id);
        },

        async loadDashboardData() {
            if (!this.selectedResolutionId || !this.selectedReportPeriodId) return;

            this.dataLoading = true;
            this.dataErrorMessage = "";
            this.dataHttpStatus = null;
            this.dataErrorPayload = null;

            try {
                const res = await axios.get("/dashboard/overview/data", {
                    params: {
                        resolution_id: this.selectedResolutionId,
                        report_period_id: this.selectedReportPeriodId,
                    },
                    headers: { Accept: "application/json" },
                });

                // Nếu server trả HTML (thường do redirect/auth), axios vẫn coi là success
                if (typeof res.data === "string") {
                    this.dashboardData = null;
                    this.dataHttpStatus = res.status ?? null;
                    this.dataErrorPayload = res.data.slice(0, 500);
                    this.dataErrorMessage =
                        "API /dashboard/overview/data trả về HTML thay vì JSON (khả năng bị redirect/auth hoặc route trả view).";
                    return;
                }

                // Chịu cả 2 dạng: { data: {...} } hoặc {...}
                const payload = res?.data?.data ?? res?.data ?? null;
                this.dashboardData = payload;

                // Guard: payload JSON nhưng sai shape => báo rõ
                if (!this.dashboardData || !this.dashboardData.mission) {
                    this.dataHttpStatus = res.status ?? null;
                    this.dataErrorPayload = this.dashboardData;
                    this.dataErrorMessage =
                        "API trả về JSON nhưng không đúng cấu trúc (thiếu key 'mission').";
                }
            } catch (err) {
                this.dataHttpStatus = err?.response?.status ?? null;
                this.dataErrorPayload = err?.response?.data ?? null;

                const status = this.dataHttpStatus;
                const data = this.dataErrorPayload;

                this.dataErrorMessage = status
                    ? `Không thể tải dữ liệu dashboard. HTTP ${status}.`
                    : "Không thể tải dữ liệu dashboard. Không nhận được phản hồi từ server.";

                if (data) {
                    this.dataErrorMessage +=
                        " Chi tiết: " +
                        (typeof data === "string" ? data : JSON.stringify(data));
                }
            } finally {
                this.dataLoading = false;
            }
        },

        async handleResolutionChange() {
            if (!this.selectedResolutionId) return;
            await this.loadFilters(this.selectedResolutionId);
        },

        async handlePeriodTypeChange() {
            // đổi type -> chọn lại kỳ phù hợp (ưu tiên current month nếu type=month)
            const now = new Date();
            const preferNumber =
                this.selectedPeriodType === "month" ? now.getMonth() + 1 : null;

            // nếu year hiện tại không có trong data theo type mới, vẫn giữ year nhưng kỳ sẽ fallback
            this.selectedReportPeriodId = this.pickDefaultReportPeriodId({
                periodType: this.selectedPeriodType,
                reportYear: this.selectedReportYear,
                preferNumber,
            });

            await this.loadDashboardData();
        },

        async handleReportYearChange() {
            // đổi year -> chọn lại kỳ phù hợp
            const now = new Date();
            const preferNumber =
                this.selectedPeriodType === "month" &&
                Number(this.selectedReportYear) === now.getFullYear()
                    ? now.getMonth() + 1
                    : null;

            this.selectedReportPeriodId = this.pickDefaultReportPeriodId({
                periodType: this.selectedPeriodType,
                reportYear: this.selectedReportYear,
                preferNumber,
            });

            await this.loadDashboardData();
        },

        async handlePeriodChange() {
            if (!this.selectedResolutionId || !this.selectedReportPeriodId)
                return;
            await this.loadDashboardData();
        },

        // ===== Helpers =====
        getPeriodTypeValue(typeObj) {
            if (!typeObj) return "";
            if (typeof typeObj === "string") return typeObj;
            if (typeof typeObj === "object")
                return typeObj.value ?? typeObj.type ?? "";
            return "";
        },
        prettyPeriodType(v) {
            const s = String(v || "").toLowerCase();
            if (s === "month") return "Tháng";
            if (s === "quarter") return "Quý";
            if (s === "year") return "Năm";
            return (v || "").toString();
        },
        formatPeriodLabel(period) {
            const typeValue = this.getPeriodTypeValue(period?.period_type);
            const typeLabel =
                typeof period?.period_type === "object"
                    ? period?.period_type?.label
                    : null;

            const reportYear = period?.report_year ?? "";
            const n = period?.period_number ?? "";
            const startDate = period?.start_date ?? "";
            const endDate = period?.end_date ?? "";

            const prettyType =
                typeLabel ||
                (typeValue ? String(typeValue).toUpperCase() : "KHÔNG RÕ LOẠI");
            const prettyYear = reportYear ? String(reportYear) : "KHÔNG RÕ NĂM";
            const suffix = n ? ` ${n}` : "";
            const range =
                startDate && endDate ? ` (${startDate} → ${endDate})` : "";
            return `${prettyType}${suffix} - ${prettyYear}${range}`;
        },
    },
};
</script>

<style scoped>
/* 1 page, màn hình lớn, hạn chế scroll */
.page {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 72vh;
}

.filter-bar {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    padding: 10px;
    background: #f7fafc;
    border: 1px solid #edf2f7;
    border-radius: 12px;
    flex-wrap: wrap;
}

.filter-item label {
    font-weight: 800;
    margin-bottom: 6px;
    display: block;
    color: #1a202c;
}

.filter-item.w-360 {
    width: 360px;
}
.filter-item.w-280 {
    width: 280px;
}
.filter-item.w-180 {
    width: 180px;
}
.filter-item.w-140 {
    width: 140px;
}

/* ✅ grid 2 hàng, card group span 2 rows => không cần scroll và không tràn ngang */
.grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1.35fr;
    grid-template-rows: 1fr 0.75fr;
    grid-template-areas:
        "status ontime group"
        "backlog backlog group";
    gap: 12px;
    align-items: stretch;
    flex: 1;
    min-height: 0;
}

.card-status {
    grid-area: status;
}
.card-ontime {
    grid-area: ontime;
}
.card-group {
    grid-area: group;
}
.card-backlog {
    grid-area: backlog;
}

.cardx {
    background: #fff;
    border: 1px solid #edf2f7;
    border-radius: 14px;
    padding: 12px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 260px;
    min-width: 0; /* ✅ chống tràn ngang */
}

.cardx-tall {
    min-height: 260px;
    max-height: 520px; /* span 2 rows */
}

.cardx-title {
    font-weight: 900;
    color: #1a202c;
}

.note {
    color: #718096;
    font-size: 12px;
    line-height: 1.45;
}

/* màn hình hẹp hơn -> tự co */
@media (max-width: 1400px) {
    .grid {
        grid-template-columns: 1fr 1fr 1.2fr;
    }
    .filter-item.w-360 {
        width: 320px;
    }
    .filter-item.w-280 {
        width: 260px;
    }
}
</style>
