<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip } from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

type Vehicle = { id: number; name: string; make: string; model: string; generation?: string | null; year?: number | null; engine?: string | null; fuel_type?: string | null };
type Fuel = { id: number; recorded_at: string; station: string | null; place: string | null; liters: number; total_price: number };
type Metrics = {
    odometer_km: number | null; odometer_at: string | null; today_delta_km: number | null; driven_this_year_km: number;
    insurance: null | { starts_on: string; ends_on: string; start_odometer_km: number; limit_km: number; driven_km: number; remaining_km: number; progress_percent: number };
    last_fuel: null | Fuel & { price_per_liter: number };
    average_consumption_l_per_100km: number | null; fuel_cost_this_year: number; fuel_liters_this_year: number;
    mileage_series: Array<{ month: string; odometer_km: number }>; recent_fuel: Fuel[];
};

const props = defineProps<{ vehicles: Vehicle[]; vehicle: Vehicle | null; metrics: Metrics | null }>();
const chartElement = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;
const vehicleForm = useForm({ name: '', make: '', model: '', generation: '', year: '', engine: '', fuel_type: 'petrol' });
const odometerForm = useForm({ odometer_km: '', recorded_at: localDateTime(), note: '' });
const fuelForm = useForm({ recorded_at: localDateTime(), station: '', place: '', liters: '', price_per_liter: '', full_tank: true, fuel_type: 'petrol', odometer_km: '' });
const insuranceForm = useForm({ starts_on: `${new Date().getFullYear()}-01-01`, ends_on: `${new Date().getFullYear()}-12-31`, start_odometer_km: '', distance_limit_km: '', note: '' });
const fuelTotal = computed(() => Number(fuelForm.liters || 0) * Number(fuelForm.price_per_liter || 0));

function localDate() {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
function localDateTime() { return `${localDate()}T${new Date().toTimeString().slice(0, 5)}`; }
function number(value: number | string | null | undefined, digits = 0) {
    return value === null || value === undefined ? '—' : new Intl.NumberFormat('de-DE', { minimumFractionDigits: digits, maximumFractionDigits: digits }).format(Number(value));
}
function money(value: number | null | undefined) {
    return value === null || value === undefined ? '—' : `${number(value, 2)} €`;
}
function date(value: string | null | undefined) {
    return value ? new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium' }).format(new Date(value)) : 'Noch keine';
}
function addVehicle() { vehicleForm.post('/vehicles', { preserveScroll: true }); }
function addOdometer() {
    if (!props.vehicle) return;
    odometerForm.post(`/vehicles/${props.vehicle.id}/odometer`, { preserveScroll: true, onSuccess: () => { odometerForm.reset('odometer_km', 'note'); odometerForm.recorded_at = localDateTime(); } });
}
function addFuel() {
    if (!props.vehicle) return;
    fuelForm.post(`/vehicles/${props.vehicle.id}/fuel`, { preserveScroll: true, onSuccess: () => { fuelForm.reset(); fuelForm.recorded_at = localDateTime(); fuelForm.full_tank = true; } });
}
function saveInsurance() {
    if (!props.vehicle) return;
    insuranceForm.post(`/vehicles/${props.vehicle.id}/insurance`, { preserveScroll: true });
}
function switchVehicle(event: Event) {
    const id = (event.target as HTMLSelectElement).value;
    router.get('/dashboard', { vehicle: id }, { preserveState: true, preserveScroll: true });
}
function paintChart() {
    chart?.destroy();
    chart = null;
    if (!chartElement.value || !props.metrics) return;
    const values = props.metrics.mileage_series;
    chart = new Chart(chartElement.value, {
        type: 'line',
        data: { labels: values.map((item) => new Intl.DateTimeFormat('de-DE', { month: 'short' }).format(new Date(`${item.month}-01`))), datasets: [{ data: values.map((item) => item.odometer_km), borderColor: '#3b9cff', backgroundColor: 'rgba(43,137,242,.18)', fill: true, tension: .32, pointRadius: 3, pointBackgroundColor: '#60b5ff', borderWidth: 2 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (item) => `${number(item.parsed.y)} km` } } }, scales: { x: { grid: { display: false }, ticks: { color: '#8494a5' } }, y: { grid: { color: 'rgba(130,151,171,.13)' }, ticks: { color: '#8494a5', callback: (value) => `${number(Number(value) / 1000, 0)}k` } } } },
    });
}
onMounted(paintChart);
watch(() => props.metrics?.mileage_series, paintChart, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <Head title="Dashboard" />
    <main class="cartrack-page">
        <header class="page-head">
            <div><p class="eyebrow">FAHRZEUGÜBERSICHT</p><h1>Dein Polo im Überblick <span>✦</span></h1><p class="subhead">Kilometer, Tankungen und Fahrleistung an einem Ort.</p></div>
            <label v-if="vehicles.length > 1" class="vehicle-switch"><span>Fahrzeug</span><select :value="vehicle?.id" @change="switchVehicle"><option v-for="item in vehicles" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
        </header>

        <section v-if="!vehicle" class="onboarding card-surface">
            <div class="onboarding-copy"><p class="eyebrow">WILLKOMMEN BEI CARTRACK</p><h2>Lege dein erstes Fahrzeug an</h2><p>Fahrzeugdaten und Kilometerstände werden pro Nutzer gespeichert. Du kannst später weitere Fahrzeuge hinzufügen.</p></div>
            <form class="form-grid" @submit.prevent="addVehicle">
                <label>Fahrzeugname<input v-model="vehicleForm.name" placeholder="z. B. Mein Polo" required /><small v-if="vehicleForm.errors.name">{{ vehicleForm.errors.name }}</small></label>
                <label>Marke<input v-model="vehicleForm.make" placeholder="z. B. Volkswagen" required /></label>
                <label>Modell<input v-model="vehicleForm.model" placeholder="z. B. Polo" required /></label>
                <label>Baureihe<input v-model="vehicleForm.generation" placeholder="z. B. AW" /></label>
                <label>Baujahr<input v-model="vehicleForm.year" type="number" min="1886" :max="new Date().getFullYear() + 1" placeholder="z. B. 2017" /></label>
                <label>Motorisierung (optional)<input v-model="vehicleForm.engine" placeholder="frei lassen, wenn unbekannt" /></label>
                <label>Kraftstoffart<select v-model="vehicleForm.fuel_type"><option value="petrol">Benzin</option><option value="diesel">Diesel</option><option value="hybrid">Hybrid</option><option value="electric">Elektro</option><option value="other">Sonstiges</option></select></label>
                <button class="primary-button" :disabled="vehicleForm.processing">Fahrzeug speichern</button>
            </form>
        </section>

        <template v-else-if="metrics">
            <section class="vehicle-hero">
                <div class="vehicle-identity"><div class="vw-emblem">VW</div><div><p class="eyebrow">DEIN FAHRZEUG</p><h2>{{ vehicle.make }} {{ vehicle.model }}</h2><p>{{ [vehicle.generation, vehicle.year ? `Baujahr ${vehicle.year}` : null, vehicle.engine].filter(Boolean).join(' · ') }}</p></div></div>
                <div class="hero-odometer"><span>Aktueller Kilometerstand</span><strong>{{ number(metrics.odometer_km) }} <small>km</small></strong><i>{{ metrics.today_delta_km !== null ? `+ ${number(metrics.today_delta_km)} km heute` : metrics.odometer_at ? `Stand ${date(metrics.odometer_at)}` : 'Noch kein Stand erfasst' }}</i></div>
            </section>

            <section class="metric-grid">
                <article class="metric-card"><span class="metric-icon blue">↗</span><p>Jahresfahrleistung</p><strong>{{ metrics.insurance ? `${number(metrics.insurance.driven_km)} / ${number(metrics.insurance.limit_km)} km` : 'Nicht eingerichtet' }}</strong><div v-if="metrics.insurance" class="progress"><i :style="{ width: `${metrics.insurance.progress_percent}%` }" /></div><footer v-if="metrics.insurance"><b>{{ number(metrics.insurance.remaining_km) }} km übrig</b><span>{{ metrics.insurance.progress_percent }}%</span></footer><small v-else>Versicherungszeitraum und Limit eintragen</small></article>
                <article class="metric-card"><span class="metric-icon cyan">⛽</span><p>Letzte Tankung</p><strong>{{ metrics.last_fuel ? date(metrics.last_fuel.recorded_at) : 'Noch keine' }}</strong><small v-if="metrics.last_fuel">{{ number(metrics.last_fuel.liters, 1) }} L · {{ money(metrics.last_fuel.total_price) }}</small><small v-else>Tankvorgang eintragen, um zu starten</small></article>
                <article class="metric-card"><span class="metric-icon violet">⌁</span><p>Ø Verbrauch</p><strong>{{ metrics.average_consumption_l_per_100km === null ? '—' : number(metrics.average_consumption_l_per_100km, 1) }} <small>L/100 km</small></strong><small>Aus Volltankungen berechnet</small></article>
                <article class="metric-card"><span class="metric-icon amber">€</span><p>Kraftstoffkosten {{ new Date().getFullYear() }}</p><strong>{{ money(metrics.fuel_cost_this_year) }}</strong><small>{{ number(metrics.fuel_liters_this_year, 1) }} Liter erfasst</small></article>
            </section>

            <section class="dashboard-grid">
                <article id="kilometer" class="panel chart-panel"><div class="panel-title"><div><h3>Kilometerentwicklung</h3><p>Erfasste Kilometerstände im laufenden Jahr</p></div><span class="year-pill">{{ new Date().getFullYear() }}</span></div><div class="chart-area"><canvas v-if="metrics.mileage_series.length" ref="chartElement" /><div v-else class="empty-chart">Noch keine Kilometerhistorie.<br />Erfasse unten den ersten Kilometerstand.</div></div></article>
                <article id="tanken" class="panel recent-panel"><div class="panel-title"><div><h3>Letzte Tankungen</h3><p>Deine letzten Einträge</p></div><span class="subtle-count">{{ metrics.recent_fuel.length }}</span></div><div v-if="metrics.recent_fuel.length" class="fuel-list"><div v-for="entry in metrics.recent_fuel" :key="entry.id" class="fuel-row"><span>{{ date(entry.recorded_at) }}</span><div><b>{{ entry.station || 'Tankung' }}</b><small>{{ [entry.place, `${number(entry.liters, 1)} L`].filter(Boolean).join(' · ') }}</small></div><strong>{{ money(entry.total_price) }}</strong></div></div><p v-else class="empty-list">Noch keine Tankungen erfasst.</p></article>
            </section>

            <section id="versicherung" class="panel insurance-panel"><div class="panel-title"><div><h3>Versicherungsjahr</h3><p>Vereinbarte Fahrleistung und verbleibende Kilometer</p></div><span class="metric-icon blue">◉</span></div><div v-if="metrics.insurance" class="insurance-summary"><div class="insurance-ring" :style="{ '--progress': `${metrics.insurance.progress_percent}%` }"><b>{{ metrics.insurance.progress_percent }}%</b></div><div><p class="eyebrow">GEFAHRENE KILOMETER</p><h2>{{ number(metrics.insurance.driven_km) }} km <small>von {{ number(metrics.insurance.limit_km) }} km</small></h2><b class="remaining">Noch {{ number(metrics.insurance.remaining_km) }} km</b></div><div class="insurance-dates"><span>Zeitraum <b>{{ date(metrics.insurance.starts_on) }} – {{ date(metrics.insurance.ends_on) }}</b></span><span>Start-Kilometerstand <b>{{ number(metrics.insurance.start_odometer_km) }} km</b></span></div></div><form v-else class="inline-form insurance-form" @submit.prevent="saveInsurance"><label>Beginn<input v-model="insuranceForm.starts_on" type="date" required /></label><label>Ende<input v-model="insuranceForm.ends_on" type="date" required /></label><label>Kilometerstand zu Beginn<input v-model="insuranceForm.start_odometer_km" type="number" min="0" required /></label><label>Limit für den Zeitraum (km)<input v-model="insuranceForm.distance_limit_km" type="number" min="1" required /></label><button class="primary-button" :disabled="insuranceForm.processing">Versicherungsjahr speichern</button></form></section>

            <section class="entry-grid">
                <article class="panel entry-panel"><div class="panel-title"><div><h3>Kilometerstand erfassen</h3><p>Der Wert wird chronologisch gespeichert.</p></div><span class="metric-icon blue">⌁</span></div><form class="inline-form" @submit.prevent="addOdometer"><label>Datum<input v-model="odometerForm.recorded_at" type="datetime-local" required /></label><label>Kilometerstand<input v-model="odometerForm.odometer_km" type="number" min="0" step="1" placeholder="km" required /></label><label class="span-two">Notiz (optional)<input v-model="odometerForm.note" maxlength="500" placeholder="z. B. iOS Kurzbefehl, Home Assistant" /></label><p v-if="odometerForm.errors.odometer_km" class="form-error">{{ odometerForm.errors.odometer_km }}</p><button class="primary-button" :disabled="odometerForm.processing">Eintrag speichern</button></form></article>
                <article class="panel entry-panel"><div class="panel-title"><div><h3>Tankung erfassen</h3><p>Preis, Menge, Tankstelle und Kilometerstand.</p></div><span class="metric-icon cyan">⛽</span></div><form class="inline-form" @submit.prevent="addFuel"><label>Datum<input v-model="fuelForm.recorded_at" type="datetime-local" required /></label><label>Kilometerstand<input v-model="fuelForm.odometer_km" type="number" min="0" placeholder="optional" /></label><label>Tankstelle<input v-model="fuelForm.station" placeholder="z. B. Aral" /></label><label>Ort<input v-model="fuelForm.place" placeholder="z. B. Berlin" /></label><label>Liter<input v-model="fuelForm.liters" type="number" min="0.01" step="0.001" required /></label><label>Preis je Liter<input v-model="fuelForm.price_per_liter" type="number" min="0.001" step="0.001" required /></label><label>Kraftstoffart<select v-model="fuelForm.fuel_type"><option value="petrol">Benzin</option><option value="diesel">Diesel</option><option value="hybrid">Hybrid</option><option value="electric">Elektro</option><option value="other">Sonstiges</option></select></label><label>Vollgetankt?<select v-model="fuelForm.full_tank"><option :value="true">Ja</option><option :value="false">Nein</option></select></label><p class="total-preview">Gesamt {{ money(fuelTotal) }}</p><button class="primary-button" :disabled="fuelForm.processing">Tankung speichern</button></form></article>
            </section>
        </template>
    </main>
</template>

<style scoped>
.cartrack-page{min-height:100%;padding:28px clamp(16px,3vw,38px) 42px;background:radial-gradient(ellipse at 68% -12%,#14283c 0%,transparent 36%),#0a1118;color:#edf3f9}.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:22px;margin:0 0 22px}.eyebrow{font-size:10px;font-weight:700;letter-spacing:1.35px;color:#64b3ff;margin:0 0 7px}.page-head h1{font-size:27px;line-height:1.2;letter-spacing:-.7px;margin:0;font-weight:750}.page-head h1 span{color:#66b7ff;font-size:18px}.subhead{color:#8f9dad;font-size:13px;margin:6px 0 0}.card-surface,.panel,.metric-card{background:linear-gradient(140deg,#121d27,#101922);border:1px solid #22313d;border-radius:13px;box-shadow:0 10px 32px #0002}.vehicle-switch{display:grid;gap:5px;font-size:11px;color:#93a2b0}.vehicle-switch select,.form-grid input,.form-grid select,.inline-form input{color:#eaf1f7;background:#18242f;border:1px solid #314150;border-radius:7px;padding:10px 11px;min-width:0}.vehicle-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;min-height:180px;padding:24px 28px;margin-bottom:16px;border:1px solid #263746;border-radius:13px;overflow:hidden;background:linear-gradient(90deg,#101a24 0%,#101a24e8 40%,#111b2880 100%),radial-gradient(ellipse at 78% 100%,#31516c 0%,#172b3b 42%,#101923 72%);position:relative}.vehicle-hero:after{content:'';position:absolute;right:13%;bottom:17px;width:36%;height:58px;border-radius:50% 45% 18% 18%;background:linear-gradient(150deg,#dce6ed,#8296a5 45%,#384957 75%);opacity:.15;filter:blur(2px);transform:skewX(-17deg)}.vehicle-identity,.hero-odometer{z-index:1;position:relative}.vehicle-identity{display:flex;align-items:center;gap:17px}.vw-emblem{height:53px;width:53px;border:2px solid #dce9f5;border-radius:50%;display:grid;place-items:center;font-size:15px;font-weight:800;color:white;background:#0a1219aa}.vehicle-identity h2{font-size:23px;margin:0 0 4px;font-weight:750}.vehicle-identity p:last-child{font-size:12px;color:#c5d0d9;margin:0}.hero-odometer{display:grid;gap:4px;padding:13px 17px;border:1px solid #ffffff1c;border-radius:10px;background:#0b141ddd;min-width:204px}.hero-odometer span,.hero-odometer i{font-size:11px;color:#a7b4c1;font-style:normal}.hero-odometer strong{font-size:27px;letter-spacing:-.6px}.hero-odometer small{font-size:13px}.metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:15px}.metric-card{padding:15px 17px;min-height:144px}.metric-icon{width:32px;height:32px;border-radius:9px;display:grid;place-items:center;font-size:16px;flex:0 0 auto}.metric-icon.blue{color:#66bcff;background:#16447788}.metric-icon.cyan{color:#5dd9f3;background:#12556a88}.metric-icon.violet{color:#c3a4ff;background:#4e387788}.metric-icon.amber{color:#ffd17d;background:#76552b88}.metric-card p{color:#9aa9b7;font-size:11px;margin:9px 0 5px}.metric-card>strong{display:block;font-size:18px;letter-spacing:-.2px;white-space:nowrap}.metric-card>strong small{font-size:10px;color:#a4b1bd}.metric-card>small{display:block;color:#8b9aa8;font-size:10px;margin-top:9px}.metric-card footer{display:flex;justify-content:space-between;color:#48d6a5;font-size:10px;margin-top:8px}.progress{height:5px;border-radius:9px;background:#283744;margin-top:11px;overflow:hidden}.progress i{display:block;height:100%;border-radius:9px;background:linear-gradient(90deg,#1680ed,#5bb6ff)}.dashboard-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(330px,.85fr);gap:14px;margin-bottom:14px}.panel{padding:18px 19px;min-width:0}.panel-title{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.panel-title h3{font-size:14px;font-weight:700;margin:0}.panel-title p{font-size:10px;color:#8695a3;margin:4px 0 0}.year-pill,.subtle-count{background:#182a39;border:1px solid #2c3d4b;border-radius:7px;padding:5px 9px;color:#b6c5d1;font-size:10px}.chart-area{height:214px;position:relative}.chart-area canvas{height:100%;width:100%}.empty-chart,.empty-list{height:100%;display:grid;place-content:center;text-align:center;color:#8493a1;font-size:11px;line-height:1.7}.fuel-list{display:grid}.fuel-row{display:grid;grid-template-columns:73px minmax(80px,1fr) 75px;align-items:center;gap:9px;padding:11px 1px;border-bottom:1px solid #202e39;font-size:10px;color:#aebac5}.fuel-row:last-child{border:0}.fuel-row b{display:block;color:#e1e9ef;font-size:11px}.fuel-row small{display:block;color:#82919f;font-size:9px;margin-top:3px}.fuel-row strong{text-align:right;color:#e5edf4}.entry-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.entry-panel .panel-title{align-items:flex-start}.inline-form,.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.inline-form label,.form-grid label{display:grid;gap:5px;font-size:10px;color:#aab7c3}.inline-form input,.form-grid input,.form-grid select{width:100%;font-size:11px}.inline-form input::placeholder,.form-grid input::placeholder{color:#71818f}.primary-button{border:0;border-radius:7px;padding:10px 13px;font-size:11px;font-weight:700;color:white;background:linear-gradient(110deg,#1b78df,#318ef5);align-self:end}.primary-button:disabled{opacity:.55;cursor:wait}.onboarding{padding:26px;max-width:760px}.onboarding-copy h2{font-size:22px;margin:0}.onboarding-copy>p:last-child{color:#92a0ad;font-size:12px}.form-grid{margin-top:18px}.form-grid small,.form-error{color:#ff8d93}.form-grid label:nth-child(1),.form-grid label:nth-child(3){grid-column:span 1}.form-grid button{grid-column:1/-1}.span-two{grid-column:span 2}.total-preview{align-self:center;color:#dce6ee;font-size:11px;margin:0}.subtle-count{color:#74bafa}.empty-list{height:180px}.@media(max-width:1100px){.metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-grid{grid-template-columns:1fr}.entry-grid{grid-template-columns:1fr}}@media(max-width:640px){.cartrack-page{padding:20px 13px 32px}.page-head{align-items:flex-start;flex-direction:column}.page-head h1{font-size:23px}.vehicle-hero{min-height:220px;align-items:flex-start;flex-direction:column;padding:19px;gap:20px}.vehicle-hero:after{right:4%;width:56%;bottom:20px}.vehicle-identity h2{font-size:19px}.hero-odometer{min-width:0}.metric-grid{gap:8px}.metric-card{padding:12px;min-height:134px}.metric-card>strong{font-size:15px}.metric-card p{font-size:10px}.metric-card footer{font-size:9px}.panel{padding:14px}.chart-area{height:185px}.fuel-row{grid-template-columns:59px minmax(70px,1fr) 62px;gap:5px}.inline-form,.form-grid{grid-template-columns:1fr}.span-two,.form-grid button{grid-column:auto}}
 .insurance-panel{margin-bottom:14px}.insurance-summary{display:grid;grid-template-columns:88px 1fr 1fr;align-items:center;gap:20px}.insurance-ring{width:82px;height:82px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(#2789f2 var(--progress),#263541 0);position:relative}.insurance-ring:before{content:'';position:absolute;inset:7px;background:#111b25;border-radius:50%}.insurance-ring b{position:relative;font-size:17px}.insurance-summary h2{font-size:20px;margin:0 0 7px}.insurance-summary h2 small{font-size:11px;color:#9aa9b7;font-weight:500}.insurance-summary .eyebrow{font-size:9px}.remaining{color:#4bd5a5;font-size:11px}.insurance-dates{border-left:1px solid #283845;padding-left:20px;display:grid;gap:11px;font-size:10px;color:#8796a4}.insurance-dates b{display:block;color:#dce5ed;margin-top:4px}.insurance-form{max-width:850px}.insurance-form button{grid-column:1/-1}@media(max-width:640px){.insurance-summary{grid-template-columns:75px 1fr;gap:13px}.insurance-ring{width:70px;height:70px}.insurance-dates{grid-column:1/-1;border-left:0;border-top:1px solid #283845;padding:12px 0 0}.insurance-form button{grid-column:auto}}
</style>
