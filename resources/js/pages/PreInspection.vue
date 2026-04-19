<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

interface Equipment {
    id: number;
    location: string | null;
    description: string;
    brand: string | null;
    model: string | null;
    serial_number: string | null;
    tag_number: string | null;
    pm_date_done: string | null;
    status: string | null;
    admin_approval: string | null;
    pending_action: string | null;
    updated_at: string | null;
    has_pir_form: boolean;
    documents_count: number;
}

interface EquipmentDocument {
    id: number;
    equipment_id: number;
    file_name: string;
    file_path: string;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    from: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
    to: number;
    total: number;
}

const props = defineProps<{
    equipments: PaginatedData<Equipment>;
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pre Inspection', href: '/pre-inspection' }];

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user.account_type === 'Admin');
const isModerator = computed(() => page.props.auth.user.account_type === 'Moderator');
const isTechnician = computed(() => page.props.auth.user.account_type === 'Biomed_Technician');
const canAct = computed(() => isAdmin.value || isModerator.value || isTechnician.value);

// Error modal
const errorModalVisible = ref(false);
const errorMessage = ref('');
const showErrorModal = (msg: string) => {
    errorMessage.value = msg;
    errorModalVisible.value = true;
};

// Generic confirm modal
const confirmModalVisible = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref<(() => void) | null>(null);
const confirmModalDanger = ref(false);

const openConfirmModal = (title: string, message: string, action: () => void, danger = false) => {
    confirmModalTitle.value = title;
    confirmModalMessage.value = message;
    confirmModalAction.value = action;
    confirmModalDanger.value = danger;
    confirmModalVisible.value = true;
};
const runConfirmAction = () => {
    confirmModalVisible.value = false;
    confirmModalAction.value?.();
};

// Toast
const toastVisible = ref(false);
const toastMessage = ref('');
let toastTimeout: ReturnType<typeof setTimeout>;
const showToast = (message: string) => {
    toastMessage.value = message;
    toastVisible.value = true;
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toastVisible.value = false;
    }, 3000);
};

// Search
const search = ref(props.filters.search || '');
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/pre-inspection', { search: search.value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

// Tracks live PIR state per equipment id without needing a page refresh
const pirStateOverride = ref<Record<number, { hasPirForm: boolean; documentsCount: number }>>({});

const checkPirReady = (item: Equipment): boolean => {
    const live = pirStateOverride.value[item.id];
    const hasPirForm = live ? live.hasPirForm : item.has_pir_form;
    const documentsCount = live ? live.documentsCount : item.documents_count;

    if (!hasPirForm) {
        showErrorModal(
            'The PIR form for this equipment has not been filled yet. Please open the Pre Inspection Documents, complete the PIR form, and upload the required documents before proceeding.',
        );
        return false;
    }
    if (documentsCount === 0) {
        showErrorModal('No supporting documents have been uploaded for this equipment. Please upload at least one document before proceeding.');
        return false;
    }
    return true;
};

// Restore to Functional
const restoreEquipment = (item: Equipment) => {
    if (!checkPirReady(item)) return;
    openConfirmModal('Send for Approval - Restore', `Submit "${item.description}" for admin approval to restore to Functional?`, () =>
        router.put(
            `/pre-inspection/${item.id}/restore`,
            {},
            { preserveScroll: true, onSuccess: () => showToast(`${item.description} sent for admin approval (Restore).`) },
        ),
    );
};

// Condemn
const condemnEquipment = (item: Equipment) => {
    if (!checkPirReady(item)) return;
    openConfirmModal(
        'Send for Approval — Condemn',
        `Submit "${item.description}" for admin approval to condemn for disposal?`,
        () =>
            router.put(
                `/pre-inspection/${item.id}/condemn`,
                {},
                { preserveScroll: true, onSuccess: () => showToast(`${item.description} sent for admin approval (Condemn).`) },
            ),
        true,
    );
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '—';
    const date = new Date(dateStr + (dateStr.includes('T') ? '' : 'T00:00:00'));
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const normalizeDate = (dateStr: string | null | undefined): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return '';
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${date.getFullYear()}-${month}-${day}`;
};

// Documents modal
const documentsModalVisible = ref(false);
const selectedEquipment = ref<Equipment | null>(null);
const documents = ref<EquipmentDocument[]>([]);
const previewDocument = ref<EquipmentDocument | null>(null);
const uploading = ref(false);

const openDocumentsModal = async (item: Equipment) => {
    selectedEquipment.value = item;
    previewDocument.value = null;
    documents.value = [];
    activeTab.value = 1;
    resetPirForm();

    try {
        const response = await axios.post(`/equipment/${item.id}/pre-inspection`);
        pirForm.value.pir_control_no = response.data.pre_inspection_control_no;
        pirForm.value.end_user = response.data.end_user ?? '';
        pirForm.value.requested_by = response.data.end_user ?? '';
        pirForm.value.acquisition_date = normalizeDate(response.data.pre_inspectioned_at ?? '');
        if (response.data.form) {
            pirForm.value = { ...pirForm.value, ...response.data.form };
            pirForm.value.acquisition_date = normalizeDate(pirForm.value.acquisition_date);
        }
    } catch (e: any) {
        showErrorModal(`Unable to generate PIR control number. ${e.response?.data?.message ?? ''}`);
    }

    pirForm.value.location_ward = item.location || '';
    documentsModalVisible.value = true;
    await fetchDocuments(item.id);
};

const closeDocumentsModal = () => {
    // Persist live state so actions work without a page refresh
    if (selectedEquipment.value) {
        const id = selectedEquipment.value.id;
        const f = pirForm.value as Record<string, unknown>;
        const anyFilled = ['defects_complaints', 'nature_of_work', 'parts_to_supply', 'findings', 'recommendation', 'inspector_name'].some(
            (k) => String(f[k] ?? '').trim().length > 0,
        );
        pirStateOverride.value[id] = {
            hasPirForm: selectedEquipment.value.has_pir_form || anyFilled,
            documentsCount: documents.value.length,
        };
    }
    documentsModalVisible.value = false;
    previewDocument.value = null;
    selectedEquipment.value = null;
    activeTab.value = 1;
    resetPirForm();
};

const fetchDocuments = async (equipmentId: number) => {
    const res = await axios.get(`/equipment/${equipmentId}/documents`);
    documents.value = res.data;
};

const uploadDocuments = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files.length || !selectedEquipment.value) return;
    uploading.value = true;
    const formData = new FormData();
    for (const file of input.files) {
        formData.append('files[]', file);
    }
    try {
        await axios.post(`/equipment/${selectedEquipment.value.id}/documents`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        showToast('Documents uploaded successfully!');
    } catch (e: any) {
        showErrorModal(`Upload failed. Server responded with status ${e.response?.status ?? 'unknown'}.`);
    } finally {
        uploading.value = false;
        input.value = '';
    }
    if (selectedEquipment.value) await fetchDocuments(selectedEquipment.value.id);
};

const deleteDocument = async (doc: EquipmentDocument) => {
    openConfirmModal(
        'Delete Document',
        `Delete "${doc.file_name}"?`,
        async () => {
            await axios.delete(`/equipment/documents/${doc.id}`);
            showToast('Document deleted.');
            if (previewDocument.value?.id === doc.id) previewDocument.value = null;
            if (selectedEquipment.value) await fetchDocuments(selectedEquipment.value.id);
        },
        true,
    );
};

// PIR Form — Step 1 of the documents modal
const activeTab = ref(1);

const pirForm = ref({
    date: new Date().toISOString().split('T')[0],
    pir_control_no: '',
    property_no: '',
    location_ward: '',
    end_user: '',
    acquisition_date: '',
    acquisition_cost: '',
    defects_complaints: '',
    nature_of_work: '',
    parts_to_supply: '',
    requested_by: '',
    findings: '',
    recommendation: '',
    inspector_name: '',
    checked_by: '',
    recommending_approval: '',
    approved_by: '',
});

const fieldErrors = ref<Record<string, boolean>>({});

const fieldClass = (field: string): string =>
    fieldErrors.value[field]
        ? 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-200'
        : 'border-gray-400 bg-transparent focus:border-orange-400 focus:ring-orange-400';

const resetPirForm = () => {
    const currentControlNo = pirForm.value.pir_control_no;
    const currentEndUser = pirForm.value.end_user;
    const currentAcquisitionDate = pirForm.value.acquisition_date;

    pirForm.value = {
        date: new Date().toISOString().split('T')[0],
        pir_control_no: currentControlNo,
        property_no: '',
        location_ward: '',
        end_user: currentEndUser,
        acquisition_date: currentAcquisitionDate,
        acquisition_cost: '',
        defects_complaints: '',
        nature_of_work: '',
        parts_to_supply: '',
        requested_by: '',
        findings: '',
        recommendation: '',
        inspector_name: '',
        checked_by: '',
        recommending_approval: '',
        approved_by: '',
    };
    fieldErrors.value = {};
};

const downloadPirForm = async () => {
    const eq = selectedEquipment.value;
    if (!eq) return;

    const f = pirForm.value;

    const requiredFields = [
        'pir_control_no',
        'location_ward',
        'end_user',
        'acquisition_date',
        'defects_complaints',
        'nature_of_work',
        'parts_to_supply',
        'requested_by',
        'findings',
        'recommendation',
        'inspector_name',
        'checked_by',
        'recommending_approval',
        'approved_by',
    ];

    const missing = requiredFields.filter((field) => {
        const value = (f as any)[field];
        return !value || !String(value).trim();
    });

    fieldErrors.value = Object.fromEntries(missing.map((field) => [field, true]));

    if (missing.length) {
        showToast('Please complete all required PIR fields before downloading.');
        return;
    }

    try {
        await axios.post(`/equipment/${eq.id}/pre-inspection/save`, pirForm.value);
    } catch (e: any) {
        showErrorModal(`Unable to save PIR form before download. ${e.response?.data?.message ?? ''}`);
        return;
    }

    const fmtDate = (d: string | null | undefined): string => {
        if (!d) return '';
        const date = new Date(d);
        if (Number.isNaN(date.getTime())) {
            return d;
        }
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const year = String(date.getFullYear());
        return `${month}/${day}/${year}`;
    };

    // Load jsPDF from CDN
    const loadJsPDF = (): Promise<any> =>
        new Promise((resolve, reject) => {
            if ((window as any).jspdf?.jsPDF) {
                resolve((window as any).jspdf.jsPDF);
                return;
            }
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            s.onload = () => resolve((window as any).jspdf.jsPDF);
            s.onerror = reject;
            document.head.appendChild(s);
        });

    // Convert logo to base64
    const getLogoBase64 = (): Promise<string> =>
        new Promise((resolve) => {
            const img = new Image();
            img.onload = () => {
                const c = document.createElement('canvas');
                c.width = img.naturalWidth;
                c.height = img.naturalHeight;
                c.getContext('2d')!.drawImage(img, 0, 0);
                resolve(c.toDataURL('image/jpeg', 0.85));
            };
            img.onerror = () => resolve('');
            img.src = '/logo.JPG?' + Date.now();
        });

    const [JsPDF, logoBase64] = await Promise.all([loadJsPDF(), getLogoBase64()]);

    const doc = new JsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
    const W = 210,
        ml = 18,
        mr = 18,
        cw = W - ml - mr;
    let y = 15;

    const txt = (text: string, x: number, yy: number, opts: any = {}) => {
        doc.setFontSize(opts.size || 9);
        doc.setFont('helvetica', opts.style || 'normal');
        if (opts.color) doc.setTextColor(opts.color[0], opts.color[1], opts.color[2]);
        else doc.setTextColor(0, 0, 0);
        const settings: any = { align: opts.align || 'left' };
        if (opts.maxWidth) settings.maxWidth = opts.maxWidth;
        doc.text(text || '', x, yy, settings);
        doc.setTextColor(0, 0, 0);
    };

    const hline = (x1: number, y1: number, x2: number, y2: number, lw = 0.2) => {
        doc.setLineWidth(lw);
        doc.setDrawColor(100, 100, 100);
        doc.line(x1, y1, x2, y2);
    };

    const hrect = (x: number, yy: number, w: number, h: number, lw = 0.2) => {
        doc.setLineWidth(lw);
        doc.setDrawColor(100, 100, 100);
        doc.rect(x, yy, w, h);
    };

    const fillRect = (x: number, yy: number, w: number, h: number, r: number, g: number, b: number) => {
        doc.setFillColor(r, g, b);
        doc.setDrawColor(100, 100, 100);
        doc.setLineWidth(0.2);
        doc.rect(x, yy, w, h, 'FD');
    };

    const fieldLine = (label: string, value: string, x: number, yy: number, maxW: number, red = false) => {
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(0, 0, 0);
        doc.text(label, x, yy);
        const lw = doc.getTextWidth(label) + 1;
        const vx = x + lw,
            vw = maxW - lw;
        hline(vx, yy + 0.5, vx + vw, yy + 0.5);
        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        if (red) doc.setTextColor(180, 0, 0);
        else doc.setTextColor(0, 0, 0);
        if (value) doc.text(value, vx + 1, yy - 0.2, { maxWidth: vw - 2 });
        doc.setTextColor(0, 0, 0);
    };

    const wrapLine = (label: string, value: string, x: number, yy: number, maxW: number, red = false): number => {
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(0, 0, 0);
        doc.text(label, x, yy);
        const lw = doc.getTextWidth(label) + 1;
        const vx = x + lw,
            vw = maxW - lw;
        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        if (red) doc.setTextColor(180, 0, 0);
        else doc.setTextColor(0, 0, 0);
        const wrappedLines: string[] = doc.splitTextToSize(value || '', vw - 2);
        const lh = 5.5;
        wrappedLines.forEach((l: string, i: number) => doc.text(l, vx + 1, yy - 0.2 + i * lh, { maxWidth: vw - 2 }));
        hline(vx, yy + 0.5, vx + vw, yy + 0.5);
        doc.setTextColor(0, 0, 0);
        return Math.max(1, wrappedLines.length) * lh;
    };

    // HEADER
    if (logoBase64) doc.addImage(logoBase64, 'JPEG', ml, y, 20, 20);
    txt('ADELA SERRA TY MEMORIAL MEDICAL CENTER', W / 2, y + 4, { size: 9, style: 'bold', align: 'center' });
    txt('PRE-INSPECTION REPORT (BIOMEDICAL)', W / 2, y + 9, { size: 13, style: 'bold', align: 'center' });
    txt('Tandag City, Surigao del Sur  \u2022  Department of Health', W / 2, y + 13.5, { size: 7, align: 'center', color: [80, 80, 80] });
    doc.setLineWidth(0.5);
    doc.setDrawColor(60, 60, 60);
    y += 5;
    doc.line(ml, y + 16, W - mr, y + 16);
    y += 22;

    // DATE
    txt('Date:', W - mr - 36, y, { size: 11, style: 'bold' });
    hline(W - mr - 28, y + 0.5, W - mr, y + 0.5);
    txt(fmtDate(f.date), W - mr - 27, y - 0.3, { size: 11 });
    y += 7;

    // DESCRIPTION OF PROPERTY
    fillRect(ml, y, cw, 5.5, 216, 216, 216);
    txt('DESCRIPTION OF PROPERTY', W / 2, y + 3.8, { size: 11, style: 'bold', align: 'center' });
    y += 5.5;

    const half = cw / 2,
        rowH = 6;
    const ly = y,
        lx = ml,
        rx = ml + half + 1;
    fieldLine('Name of Equipment:', eq.description, lx + 1, ly + rowH * 1 - 1, half - 2);
    fieldLine('Brand:', eq.brand || 'N/A', lx + 1, ly + rowH * 2 - 1, half - 2);
    fieldLine('Model:', eq.model || 'N/A', lx + 1, ly + rowH * 3 - 1, half - 2);
    fieldLine('Serial Number:', eq.serial_number || 'N/A', lx + 1, ly + rowH * 4 - 1, half - 2);
    fieldLine('Date of Acquisition:', fmtDate(f.acquisition_date) || 'N/A', lx + 1, ly + rowH * 5 - 1, half - 2);
    fieldLine('P.I.R. Control No.:', f.pir_control_no, rx, ly + rowH * 1 - 1, half - 2, true);
    fieldLine('Property No.:', f.property_no || 'N/A', rx, ly + rowH * 2 - 1, half - 2);
    fieldLine('Location/Ward:', f.location_ward, rx, ly + rowH * 3 - 1, half - 2, true);
    fieldLine('End User:', f.end_user, rx, ly + rowH * 4 - 1, half - 2);
    fieldLine('Acquisition Cost:', f.acquisition_cost || 'N/A', rx, ly + rowH * 5 - 1, half - 2);
    const propH = rowH * 5 + 2;
    hrect(ml, y, half, propH);
    hrect(ml + half, y, half, propH);
    y += propH + 4;

    // FIELDS
    y += 7;
    const fh1 = wrapLine('Defects/Complaints:', f.defects_complaints, ml, y, cw, true);
    y += Math.max(fh1, 5) + 2;
    const fh2 = wrapLine('Nature and scope of work to be done:', f.nature_of_work, ml, y, cw);
    y += Math.max(fh2, 5) + 2;
    const fh3 = wrapLine('Parts to be supplied/replaced:', f.parts_to_supply, ml, y, cw);
    y += Math.max(fh3, 5) + 10;

    // REQUESTED BY
    txt('Requested by:', W - mr - 60, y, { size: 11, style: 'bold' });
    y += 10;
    hline(W - mr - 55, y + 0.5, W - mr, y + 0.5);
    txt(f.requested_by, W - mr - 27.5, y - 0.3, { size: 11, maxWidth: 53, align: 'center' });
    y += 7;

    // EVALUATION REPORT
    fillRect(ml, y, cw, 5.5, 216, 216, 216);
    txt('EVALUATION REPORT', W / 2, y + 3.8, { size: 11, style: 'bold', align: 'center' });
    y += 5.5;
    const evalY = y;
    y += 7;
    const evalH1 = wrapLine('Findings:', f.findings, ml + 2, y, cw - 4, true);
    y += Math.max(evalH1, 5) + 2;
    const evalH2 = wrapLine('Recommendation: (by the technical inspector)', f.recommendation, ml + 2, y, cw - 4, true);
    y += Math.max(evalH2, 5) + 3;
    hrect(ml, evalY, cw, y - evalY);

    // SIGNATURES
    y += 8;
    const sc = cw / 2,
        sy = y + 18,
        sigW = sc - 10;
    hline(ml + 2, sy, ml + sigW + 2, sy);
    txt(f.inspector_name, ml + 2 + sigW / 2, sy - 1, { size: 11, maxWidth: sigW, align: 'center' });
    txt('MET-II', ml + sc / 2, sy + 5, { size: 11, align: 'center', color: [80, 80, 80] });
    txt('Checked by:', ml + sc + sc / 2, y + 4, { size: 11, style: 'bold', align: 'center' });
    hline(ml + sc + 4, sy, ml + sc + sigW + 4, sy);
    txt(f.checked_by, ml + sc + 4 + sigW / 2, sy - 1, { size: 11, maxWidth: sigW, align: 'center' });
    txt('MET-II', ml + sc + sc / 2, sy + 5, { size: 11, align: 'center', color: [80, 80, 80] });

    y = sy + 30;
    txt('Recommending Approval :', ml + sc / 2, y, { size: 11, style: 'bold', align: 'center' });
    hline(ml + 2, y + 14, ml + sigW + 2, y + 14);
    txt(f.recommending_approval, ml + 2 + sigW / 2, y + 13, { size: 11, maxWidth: sigW, align: 'center' });
    txt('Section Head', ml + sc / 2, y + 20, { size: 11, align: 'center', color: [80, 80, 80] });
    txt('Approved :', ml + sc + sc / 2, y, { size: 11, style: 'bold', align: 'center' });
    hline(ml + sc + 4, y + 14, ml + sc + sigW + 4, y + 14);
    txt(f.approved_by, ml + sc + 4 + sigW / 2, y + 13, { size: 11, maxWidth: sigW, align: 'center' });
    txt('Medical Center Chief II', ml + sc + sc / 2, y + 20, { size: 11, align: 'center', color: [80, 80, 80] });

    const safeName = eq.description.replace(/[^a-zA-Z0-9]/g, '-');
    doc.save('PIR-' + safeName + '.pdf');
};
</script>
<template>
    <Head title="Pre Inspection" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50 p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <section class="mb-8 overflow-hidden rounded-2xl bg-orange-50 shadow-sm ring-1 ring-orange-200">
                <div class="flex items-center gap-6 p-6 sm:p-8">
                    <div
                        class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-inner ring-1 ring-orange-200"
                    >
                        <img src="/logo.JPG" alt="" class="h-full w-full object-contain" onerror="this.style.display='none'" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Pre Inspection Queue</h1>
                        <p class="mt-2 text-sm font-medium uppercase tracking-wide text-orange-600">Final assessment before disposition</p>
                    </div>
                </div>
            </section>

            <!-- Info banner -->
            <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">
                <p>
                    Equipment listed here has been flagged as <strong>Defective</strong> and requires a final inspection. You can restore equipment to
                    <strong class="text-green-700">Functional</strong> or mark it as <strong class="text-red-700">Condemned</strong> for disposal.
                </p>
            </div>

            <!-- Search bar -->
            <div class="mb-4 flex items-center justify-between rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
                <div
                    class="flex items-center rounded-lg bg-white px-4 py-2 shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-orange-600 sm:max-w-xs"
                >
                    <svg class="size-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Search equipment..."
                        class="ml-3 w-48 border-0 bg-transparent py-1 text-sm text-gray-900 placeholder-gray-400 outline-none"
                    />
                </div>
                <p class="text-sm text-gray-500">{{ equipments.total }} equipment in queue</p>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="min-w-full border-collapse border border-gray-300">
                    <thead class="bg-orange-500">
                        <tr>
                            <th class="border-r border-orange-400 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6">Item #</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Location</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Equipment Description</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Brand</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Model</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Serial #</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">TAG #</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Sent On</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Status</th>
                            <th class="px-3 py-3.5 text-center text-sm font-semibold text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr v-if="equipments.data.length === 0">
                            <td colspan="10" class="border-b border-gray-200 py-12 text-center text-sm text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        vector-effect="non-scaling-stroke"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No equipment in Pre Inspection queue</h3>
                            </td>
                        </tr>
                        <tr
                            v-for="(item, index) in equipments.data"
                            :key="item.id"
                            class="border-b border-gray-200 transition-colors hover:bg-gray-50"
                        >
                            <td class="whitespace-nowrap border-r border-gray-200 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                {{ equipments.from + index }}
                            </td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.location || 'ΓÇö' }}</td>
                            <td class="border-r border-gray-200 px-3 py-4 text-sm font-medium text-gray-900">{{ item.description }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.brand || 'ΓÇö' }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.model || 'ΓÇö' }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">
                                {{ item.serial_number || 'ΓÇö' }}
                            </td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.tag_number || 'ΓÇö' }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">
                                {{ formatDate(item.updated_at) }}
                            </td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm">
                                <span
                                    v-if="item.status === 'Awaiting Approval'"
                                    class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20"
                                >
                                    Awaiting Approval
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/10"
                                >
                                    Pre Inspection
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Documents -->
                                    <button
                                        type="button"
                                        @click="openDocumentsModal(item)"
                                        class="rounded bg-blue-50 px-2 py-2 text-blue-600 hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        title="View / Upload Documents"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Restore to Functional -->
                                    <button
                                        v-if="canAct && item.status === 'Pre Inspection'"
                                        type="button"
                                        @click="restoreEquipment(item)"
                                        class="rounded bg-green-50 px-2 py-2 text-green-600 hover:bg-green-100 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        title="Send for Approval - Restore to Functional"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Condemn -->
                                    <button
                                        v-if="canAct && item.status === 'Pre Inspection'"
                                        type="button"
                                        @click="condemnEquipment(item)"
                                        class="rounded bg-red-50 px-2 py-2 text-red-600 hover:bg-red-100 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                                        title="Send for Approval - Condemn"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Pending approval label -->
                                    <span
                                        v-if="item.status === 'Awaiting Approval'"
                                        class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        {{ item.pending_action }} Awaiting Admin
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="equipments.links.length > 3" class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ equipments.from }}</span> to <span class="font-medium">{{ equipments.to }}</span> of
                            <span class="font-medium">{{ equipments.total }}</span> results
                        </p>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <template v-for="(link, idx) in equipments.links" :key="idx">
                                <div
                                    v-if="link.url === null"
                                    class="relative inline-flex cursor-not-allowed items-center bg-gray-50 px-4 py-2 text-sm font-medium text-gray-500 opacity-50 ring-1 ring-inset ring-gray-300"
                                    :class="{ 'rounded-l-md': idx === 0, 'rounded-r-md': idx === equipments.links.length - 1 }"
                                    v-html="link.label"
                                />
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0"
                                    :class="{
                                        'rounded-l-md': idx === 0,
                                        'rounded-r-md': idx === equipments.links.length - 1,
                                        'z-10 bg-orange-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600':
                                            link.active,
                                        'text-gray-900 hover:bg-gray-50 focus-visible:outline-0': !link.active,
                                    }"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <transition name="toast">
            <div
                v-if="toastVisible"
                class="fixed right-6 top-6 z-50 flex items-center gap-3 rounded-xl bg-gray-900 px-5 py-3 text-sm font-medium text-white shadow-lg"
            >
                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ toastMessage }}
            </div>
        </transition>

        <!-- Documents Modal -->
        <div v-if="documentsModalVisible" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="modal-content flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Pre Inspection Documents</h2>
                        <p v-if="selectedEquipment" class="text-sm text-gray-500">{{ selectedEquipment.description }}</p>
                    </div>
                    <button @click="closeDocumentsModal" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tab Buttons -->
                <div class="flex border-b border-gray-200 bg-gray-50 px-6">
                    <button
                        @click="activeTab = 1"
                        class="mr-1 px-5 py-3 text-sm font-medium transition-colors"
                        :class="activeTab === 1 ? 'border-b-2 border-orange-500 text-orange-600' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Step 1: PIR Form
                    </button>
                    <button
                        @click="activeTab = 2"
                        class="px-5 py-3 text-sm font-medium transition-colors"
                        :class="activeTab === 2 ? 'border-b-2 border-orange-500 text-orange-600' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Step 2: Upload Documents
                    </button>
                </div>

                <!-- TAB 1: PIR FORM -->
                <div v-if="activeTab === 1" class="flex-1 overflow-y-auto p-6">
                    <!-- Hospital Header -->
                    <div class="mb-4 flex items-center gap-3 border-b-2 border-double border-gray-400 pb-3">
                        <img src="/logo.JPG" alt="ASTMMC" class="h-16 w-16 shrink-0 object-contain" />
                        <div class="flex-1 text-center">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-600">Adela Serra Ty Memorial Medical Center</p>
                            <h2 class="text-base font-bold uppercase tracking-wide text-gray-900">Pre-Inspection Report (Biomedical)</h2>
                            <p class="text-xs text-gray-500">Tandag City, Surigao del Sur &bull; Department of Health</p>
                        </div>
                        <div class="w-16 shrink-0"></div>
                    </div>

                    <!-- Date -->
                    <div class="mb-4 flex justify-end">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                            Date:
                            <input
                                type="date"
                                v-model="pirForm.date"
                                class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-orange-400"
                            />
                        </label>
                    </div>

                    <!-- Description of Property -->
                    <div class="mb-4">
                        <div class="bg-gray-200 px-3 py-1.5 text-center text-xs font-bold uppercase tracking-widest text-gray-700">
                            Description of Property
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 border border-t-0 border-gray-300 p-3">
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Name of Equipment:</span>
                                <span class="flex-1 border-b border-gray-400 pl-1 text-gray-900">{{ selectedEquipment?.description }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">P.I.R. Control No.:</span>
                                <input
                                    v-model="pirForm.pir_control_no"
                                    readonly
                                    class="flex-1 cursor-not-allowed border-b border-gray-400 bg-gray-50 pl-1 text-xs font-bold text-red-600 focus:outline-none"
                                />
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Brand:</span>
                                <span class="flex-1 border-b border-gray-400 pl-1 text-gray-900">{{ selectedEquipment?.brand || 'N/A' }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Property No.:</span>
                                <input
                                    v-model="pirForm.property_no"
                                    class="flex-1 border-b border-gray-400 bg-transparent pl-1 text-xs focus:outline-none"
                                />
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Model:</span>
                                <span class="flex-1 border-b border-gray-400 pl-1 text-gray-900">{{ selectedEquipment?.model || 'N/A' }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Location/Ward:</span>
                                <input
                                    v-model="pirForm.location_ward"
                                    :class="['flex-1 border-b pl-1 text-xs font-bold focus:outline-none', fieldClass('location_ward')]"
                                />
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Serial Number:</span>
                                <span class="flex-1 border-b border-gray-400 pl-1 text-gray-900">{{
                                    selectedEquipment?.serial_number || 'N/A'
                                }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">End User:</span>
                                <input
                                    readonly
                                    v-model="pirForm.end_user"
                                    :class="['flex-1 border-b pl-1 text-xs focus:outline-none', fieldClass('end_user')]"
                                />
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Date of Acquisition:</span>
                                <input
                                    readonly
                                    type="date"
                                    v-model="pirForm.acquisition_date"
                                    :class="['flex-1 border-b pl-1 text-xs focus:outline-none', fieldClass('acquisition_date')]"
                                />
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <span class="whitespace-nowrap font-semibold text-gray-700">Acquisition Cost:</span>
                                <input
                                    v-model="pirForm.acquisition_cost"
                                    class="flex-1 border-b border-gray-400 bg-transparent pl-1 text-xs focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Fields -->
                    <div class="mb-3 flex items-start gap-2 text-xs">
                        <span class="whitespace-nowrap pt-0.5 font-semibold text-gray-700">Defects / Complaints:</span>
                        <textarea
                            v-model="pirForm.defects_complaints"
                            rows="2"
                            :class="['flex-1 resize-none border-b pl-1 text-xs focus:outline-none', fieldClass('defects_complaints')]"
                        ></textarea>
                    </div>
                    <div class="mb-3 flex items-start gap-2 text-xs">
                        <span class="whitespace-nowrap pt-0.5 font-semibold text-gray-700">Nature and scope of work to be done:</span>
                        <textarea
                            v-model="pirForm.nature_of_work"
                            rows="2"
                            :class="['flex-1 resize-none border-b pl-1 text-xs focus:outline-none', fieldClass('nature_of_work')]"
                        ></textarea>
                    </div>
                    <div class="mb-3 flex items-start gap-2 text-xs">
                        <span class="whitespace-nowrap pt-0.5 font-semibold text-gray-700">Parts to be supplied / replaced:</span>
                        <textarea
                            v-model="pirForm.parts_to_supply"
                            rows="2"
                            :class="['flex-1 resize-none border-b pl-1 text-xs focus:outline-none', fieldClass('parts_to_supply')]"
                        ></textarea>
                    </div>

                    <!-- Requested by -->
                    <div class="mb-4 flex justify-end text-xs">
                        <div class="text-right">
                            <p class="font-semibold text-gray-700">Requested by:</p>
                            <input
                                v-model="pirForm.requested_by"
                                :class="['mt-4 w-52 border-b text-center text-xs focus:outline-none', fieldClass('requested_by')]"
                            />
                        </div>
                    </div>

                    <!-- Evaluation Report -->
                    <div class="mb-4 border border-gray-300">
                        <div class="bg-gray-200 px-3 py-1.5 text-center text-xs font-bold uppercase tracking-widest text-gray-700">
                            Evaluation Report
                        </div>
                        <div class="p-3">
                            <div class="mb-3 flex items-start gap-2 text-xs">
                                <span class="whitespace-nowrap pt-0.5 font-semibold italic text-gray-700">Findings:</span>
                                <textarea
                                    v-model="pirForm.findings"
                                    rows="2"
                                    :class="['flex-1 resize-none border-b pl-1 text-xs italic focus:outline-none', fieldClass('findings')]"
                                ></textarea>
                            </div>
                            <div class="flex items-start gap-2 text-xs">
                                <span class="whitespace-nowrap pt-0.5 font-semibold italic text-gray-700"
                                    >Recommendation: <span class="font-normal not-italic">(by the technical inspector)</span></span
                                >
                                <textarea
                                    v-model="pirForm.recommendation"
                                    rows="2"
                                    :class="[
                                        'flex-1 resize-none border-b pl-1 text-xs font-bold italic focus:outline-none',
                                        fieldClass('recommendation'),
                                    ]"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Signature Grid -->
                    <div class="mb-6 grid grid-cols-2 gap-6 text-center text-xs">
                        <div>
                            <input
                                v-model="pirForm.inspector_name"
                                :class="['w-full border-b text-center text-xs focus:outline-none', fieldClass('inspector_name')]"
                            />
                            <p class="mt-0.5 italic text-gray-500">MET-II</p>
                        </div>
                        <div>
                            <p class="mb-1 font-semibold text-gray-700">Checked by:</p>
                            <input
                                v-model="pirForm.checked_by"
                                :class="['w-full border-b text-center text-xs focus:outline-none', fieldClass('checked_by')]"
                            />
                            <p class="mt-0.5 italic text-gray-500">MET-II</p>
                        </div>
                        <div>
                            <p class="mb-1 font-semibold text-gray-700">Recommending Approval:</p>
                            <input
                                v-model="pirForm.recommending_approval"
                                :class="['w-full border-b text-center text-xs focus:outline-none', fieldClass('recommending_approval')]"
                            />
                            <p class="mt-0.5 italic text-gray-500">Section Head</p>
                        </div>
                        <div>
                            <p class="mb-1 font-semibold text-gray-700">Approved:</p>
                            <input
                                v-model="pirForm.approved_by"
                                :class="['w-full border-b text-center text-xs focus:outline-none', fieldClass('approved_by')]"
                            />
                            <p class="mt-0.5 italic text-gray-500">Medical Center Chief II</p>
                        </div>
                    </div>

                    <!-- Tab 1 Actions -->
                    <div class="flex justify-between border-t border-gray-200 pt-4">
                        <button
                            type="button"
                            @click="downloadPirForm()"
                            class="flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Download Form
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 2"
                            class="flex items-center gap-2 rounded-lg bg-orange-500 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-600"
                        >
                            Next: Upload Documents
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- TAB 2: UPLOAD DOCUMENTS -->
                <div v-if="activeTab === 2" class="flex flex-1 overflow-hidden">
                    <!-- File list -->
                    <div class="flex w-64 flex-col border-r border-gray-200 bg-gray-50">
                        <div class="flex-1 overflow-y-auto p-4">
                            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Documents</h3>
                            <div v-if="documents.length === 0" class="py-8 text-center text-sm text-gray-400">No documents yet</div>
                            <div
                                v-for="doc in documents"
                                :key="doc.id"
                                @click="previewDocument = doc"
                                class="group mb-2 flex w-full cursor-pointer items-center gap-2 rounded-lg p-2 text-left hover:bg-white"
                                :class="previewDocument?.id === doc.id ? 'bg-white shadow-sm ring-1 ring-orange-200' : ''"
                            >
                                <svg class="h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                                </svg>
                                <span class="flex-1 truncate text-xs text-gray-700">{{ doc.file_name }}</span>
                                <button
                                    @click.stop="deleteDocument(doc)"
                                    class="hidden shrink-0 rounded p-0.5 text-gray-400 hover:bg-red-50 hover:text-red-500 group-hover:block"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <!-- Upload -->
                        <div class="border-t border-gray-200 p-4">
                            <label
                                class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed border-gray-300 p-3 text-center text-xs text-gray-500 hover:border-orange-400 hover:text-orange-600"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ uploading ? 'Uploading...' : 'Upload PDFs' }}</span>
                                <input type="file" accept=".pdf" multiple class="hidden" :disabled="uploading" @change="uploadDocuments" />
                            </label>
                        </div>
                    </div>
                    <!-- PDF preview -->
                    <div class="flex flex-1 items-center justify-center bg-gray-100 p-4">
                        <div v-if="!previewDocument" class="text-center text-sm text-gray-400">
                            <svg class="mx-auto mb-2 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Select a document to preview
                        </div>
                        <iframe
                            v-else
                            :src="`/storage/${previewDocument.file_path}`"
                            class="h-full w-full rounded border border-gray-200"
                            style="min-height: 500px"
                        />
                    </div>
                </div>
            </div>
        </div>
        <!-- Confirm Modal -->
        <Teleport to="body">
            <div v-if="confirmModalVisible" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="confirmModalVisible = false" />
                <div class="relative z-10 mx-4 w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-gray-200">
                    <div
                        class="flex items-center justify-between rounded-t-2xl px-6 py-4"
                        :class="confirmModalDanger ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-orange-500 to-orange-600'"
                    >
                        <h3 class="text-lg font-bold text-white">{{ confirmModalTitle }}</h3>
                        <button @click="confirmModalVisible = false" class="rounded-lg p-1 text-white hover:bg-white/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-gray-700">{{ confirmModalMessage }}</p>
                    </div>
                    <div class="flex gap-3 rounded-b-2xl border-t bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="runConfirmAction"
                            class="flex-1 rounded-lg px-4 py-2 font-semibold text-white transition-all duration-200"
                            :class="
                                confirmModalDanger
                                    ? 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800'
                                    : 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700'
                            "
                        >
                            Confirm
                        </button>
                        <button
                            type="button"
                            @click="confirmModalVisible = false"
                            class="flex-1 rounded-lg bg-gray-300 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Error Modal -->
        <Teleport to="body">
            <div v-if="errorModalVisible" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="errorModalVisible = false" />
                <!-- Dialog -->
                <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-200">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900">Upload Error</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ errorMessage }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button
                            @click="errorModalVisible = false"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        >
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style>
.toast-enter-active {
    animation: toastIn 0.3s ease-out;
}
.toast-leave-active {
    animation: toastOut 0.2s ease-in forwards;
}

@keyframes toastIn {
    from {
        opacity: 0;
        transform: translateY(-12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes toastOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-12px);
    }
}

.modal-backdrop {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.modal-content {
    animation: slideScale 0.3s ease-out;
}
@keyframes slideScale {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>
