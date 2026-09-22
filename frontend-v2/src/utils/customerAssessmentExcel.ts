import * as XLSX from 'xlsx-js-style'
import { addNativeExcelCharts, downloadExcelFile } from './excelNativeCharts'

export type CustomerAssessmentExcelOptions = {
  filters: {
    year: string
    location: string
    unit: string
    surveyStatus: string
    orderType: string
    progress: string
    search: string
  }
  summary: {
    totalRegistrasi: number
    totalIsiSurvey: number
    totalBelumSurvey: number
    persentase: number
    averageRating: number
  }
  rows: any[]
  attributes: Array<{ no: number; dimension: string; attribute: string; score: number }>
  ratingDistribution: Array<{ label: string; value: number }>
  units: Array<{ unit: string; rating: number; total: number; sudah: number }>
  monthly: Array<{ month: string; total: number; sudah: number; belum: number }>
  generatedAt?: Date
}

const DASHBOARD_SHEET = 'Dashboard'
const DATA_SHEET = 'Data Penilaian'
const ATTRIBUTE_SHEET = 'Atribut Layanan'

const COLORS = {
  navy: '172554', blue: '2563EB', blueSoft: 'EFF6FF', green: '059669', greenSoft: 'ECFDF5',
  amber: 'D97706', amberSoft: 'FFF7ED', violet: '7C3AED', violetSoft: 'F5F3FF', red: 'DC2626',
  slate: '475569', muted: '64748B', border: 'CBD5E1', white: 'FFFFFF', rowAlt: 'F8FAFC',
}

const thinBorder = {
  top: { style: 'thin', color: { rgb: COLORS.border } },
  bottom: { style: 'thin', color: { rgb: COLORS.border } },
  left: { style: 'thin', color: { rgb: COLORS.border } },
  right: { style: 'thin', color: { rgb: COLORS.border } },
}

function number(value: unknown) {
  const parsed = Number(value ?? 0)
  return Number.isFinite(parsed) ? parsed : 0
}

function safeFilePart(value: string) {
  return String(value || 'Semua')
    .trim()
    .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '_')
    .replace(/\s+/g, '_')
    .replace(/_+/g, '_')
    .slice(0, 70)
}

function formatDate(value: unknown) {
  if (!value) return '-'
  const date = new Date(String(value))
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString('id-ID')
}

function truncate(value: unknown, maxLength = 34) {
  const text = String(value ?? '-')
  return text.length > maxLength ? `${text.slice(0, maxLength - 1)}…` : text
}

function setCell(sheet: XLSX.WorkSheet, row: number, col: number, value: unknown, style?: any, format?: string) {
  const address = XLSX.utils.encode_cell({ r: row, c: col })
  const cell: any = sheet[address] || { t: typeof value === 'number' ? 'n' : 's', v: value ?? '' }
  cell.v = value ?? ''
  cell.t = typeof value === 'number' ? 'n' : 's'
  if (style) cell.s = style
  if (format) cell.z = format
  sheet[address] = cell
}

function merge(sheet: XLSX.WorkSheet, row: number, startCol: number, endCol: number) {
  if (!sheet['!merges']) sheet['!merges'] = []
  sheet['!merges'].push({ s: { r: row, c: startCol }, e: { r: row, c: endCol } })
}

function styleRange(sheet: XLSX.WorkSheet, startRow: number, startCol: number, endRow: number, endCol: number, style: any) {
  for (let row = startRow; row <= endRow; row += 1) {
    for (let col = startCol; col <= endCol; col += 1) {
      const address = XLSX.utils.encode_cell({ r: row, c: col })
      if (!sheet[address]) sheet[address] = { t: 's', v: '' }
      ;(sheet[address] as any).s = style
    }
  }
}

function addKpi(sheet: XLSX.WorkSheet, startCol: number, endCol: number, label: string, value: number, fill: string, color: string, format = '#,##0') {
  const style = {
    fill: { patternType: 'solid', fgColor: { rgb: fill } },
    font: { name: 'Aptos', size: 9, color: { rgb: color } },
    alignment: { horizontal: 'center', vertical: 'center' },
    border: thinBorder,
  }
  merge(sheet, 5, startCol, endCol)
  merge(sheet, 6, startCol, endCol)
  styleRange(sheet, 5, startCol, 6, endCol, style)
  setCell(sheet, 5, startCol, label, { ...style, font: { name: 'Aptos', size: 9, bold: true, color: { rgb: color } } })
  setCell(sheet, 6, startCol, value, { ...style, font: { name: 'Aptos Display', size: 16, bold: true, color: { rgb: color } } }, format)
}

function buildDashboard(options: CustomerAssessmentExcelOptions) {
  const sheet = XLSX.utils.aoa_to_sheet([])
  const titleStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos Display', size: 20, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'left', vertical: 'center' },
  }
  merge(sheet, 0, 0, 14)
  merge(sheet, 1, 0, 14)
  styleRange(sheet, 0, 0, 1, 14, titleStyle)
  setCell(sheet, 0, 0, 'PENILAIAN PELANGGAN', titleStyle)
  setCell(sheet, 1, 0, 'Dashboard kepuasan pelanggan Laboratorium Kalibrasi U-LAB', {
    ...titleStyle,
    font: { name: 'Aptos', size: 10, color: { rgb: 'DDE7F7' } },
  })

  merge(sheet, 2, 0, 14)
  merge(sheet, 3, 0, 14)
  setCell(sheet, 2, 0, `Tahun ${options.filters.year} | ${options.filters.location} | ${options.filters.unit}`, {
    font: { name: 'Aptos', size: 10, bold: true, color: { rgb: COLORS.slate } },
  })
  setCell(sheet, 3, 0, `${options.filters.surveyStatus} | ${options.filters.orderType} | ${options.filters.progress} | Pencarian: ${options.filters.search}`, {
    font: { name: 'Aptos', size: 9, color: { rgb: COLORS.muted } },
  })

  addKpi(sheet, 0, 2, 'TOTAL PENDAFTARAN', options.summary.totalRegistrasi, COLORS.blueSoft, COLORS.blue)
  addKpi(sheet, 3, 5, 'SUDAH ISI SURVEY', options.summary.totalIsiSurvey, COLORS.greenSoft, COLORS.green)
  addKpi(sheet, 6, 8, 'BELUM ISI SURVEY', options.summary.totalBelumSurvey, COLORS.amberSoft, COLORS.amber)
  addKpi(sheet, 9, 11, 'COVERAGE SURVEY', options.summary.persentase / 100, COLORS.greenSoft, COLORS.green, '0.00%')
  addKpi(sheet, 12, 14, 'RATA-RATA RATING', options.summary.averageRating, COLORS.violetSoft, COLORS.violet, '0.00')

  merge(sheet, 8, 0, 14)
  setCell(sheet, 8, 0, 'Catatan: order standar U-LAB dan kalibrasi internal tidak disertakan dalam data maupun seluruh perhitungan.', {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.blueSoft } },
    font: { name: 'Aptos', size: 9, italic: true, color: { rgb: COLORS.navy } },
    alignment: { horizontal: 'left', vertical: 'center' },
    border: thinBorder,
  })

  const helperHeader = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 9, bold: true, color: { rgb: COLORS.white } },
    border: thinBorder,
  }
  const helperBody = { font: { name: 'Aptos', size: 9, color: { rgb: COLORS.slate } }, border: thinBorder }

  setCell(sheet, 0, 16, 'Rating', helperHeader)
  setCell(sheet, 0, 17, 'Jumlah', helperHeader)
  options.ratingDistribution.forEach((item, index) => {
    setCell(sheet, index + 1, 16, item.label, helperBody)
    setCell(sheet, index + 1, 17, number(item.value), helperBody, '#,##0')
  })

  const topUnits = options.units.filter(item => number(item.rating) > 0).slice(0, 12)
  setCell(sheet, 0, 19, 'Unit', helperHeader)
  setCell(sheet, 0, 20, 'Rating', helperHeader)
  topUnits.forEach((item, index) => {
    setCell(sheet, index + 1, 19, truncate(item.unit), helperBody)
    setCell(sheet, index + 1, 20, number(item.rating), helperBody, '0.00')
  })

  setCell(sheet, 0, 22, 'Bulan', helperHeader)
  setCell(sheet, 0, 23, 'Sudah Isi', helperHeader)
  setCell(sheet, 0, 24, 'Belum Isi', helperHeader)
  options.monthly.forEach((item, index) => {
    setCell(sheet, index + 1, 22, item.month, helperBody)
    setCell(sheet, index + 1, 23, number(item.sudah), helperBody, '#,##0')
    setCell(sheet, index + 1, 24, number(item.belum), helperBody, '#,##0')
  })

  sheet['!ref'] = XLSX.utils.encode_range({ r: 0, c: 0 }, { r: Math.max(48, options.monthly.length + 1, topUnits.length + 1), c: 24 })
  sheet['!cols'] = [
    ...Array.from({ length: 15 }, () => ({ wch: 12 })),
    { wch: 3 }, { wch: 15, hidden: true }, { wch: 10, hidden: true }, { wch: 3, hidden: true },
    { wch: 38, hidden: true }, { wch: 10, hidden: true }, { wch: 3, hidden: true },
    { wch: 12, hidden: true }, { wch: 12, hidden: true }, { wch: 12, hidden: true },
  ]
  sheet['!rows'] = [{ hpt: 34 }, { hpt: 22 }, { hpt: 20 }, { hpt: 20 }, { hpt: 8 }, { hpt: 22 }, { hpt: 30 }]
  ;(sheet as any)['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0 }
  ;(sheet as any)['!margins'] = { left: 0.25, right: 0.25, top: 0.45, bottom: 0.45, header: 0.2, footer: 0.2 }
  return { sheet, topUnits }
}

function buildDataSheet(options: CustomerAssessmentExcelOptions) {
  const headers = [
    'No', 'No Pendaftaran', 'Unit', 'Penanggung Jawab', 'Jabatan', 'No. HP', 'Lokasi', 'Jenis Order',
    'Jumlah Alat', 'Alat Selesai', 'Progress Order', 'Rating', 'Status Survey', 'Tanggal Registrasi', 'Catatan',
  ]
  const rows = options.rows.map((item, index) => {
    const total = number(item.jumlahdetail)
    const finished = number(item.jumlahselesai)
    return [
      index + 1, item.nopendaftaran || '-', item.namaperusahaan || '-', item.namapenanggungjawab || '-',
      item.jabatanpenanggungjawab || '-', item.nohppenanggungjawab || '-', item.lokasi || '-', item.jenisorder || '-',
      total, finished, total > 0 ? finished / total : 0, item.rata2Bintang == null ? null : number(item.rata2Bintang),
      item.isikepuasanpelanggan != null ? 'Sudah' : 'Belum', formatDate(item.tglregistrasi), item.catatan || '-',
    ]
  })
  const sheet = XLSX.utils.aoa_to_sheet([headers, ...rows])
  const headerStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 9, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  }
  styleRange(sheet, 0, 0, 0, headers.length - 1, headerStyle)
  rows.forEach((_, index) => {
    const row = index + 1
    const fill = index % 2 ? COLORS.rowAlt : COLORS.white
    styleRange(sheet, row, 0, row, headers.length - 1, {
      fill: { patternType: 'solid', fgColor: { rgb: fill } },
      font: { name: 'Aptos', size: 9, color: { rgb: COLORS.slate } },
      alignment: { vertical: 'top', wrapText: true },
      border: thinBorder,
    })
    const progressCell = sheet[XLSX.utils.encode_cell({ r: row, c: 10 })] as any
    const ratingCell = sheet[XLSX.utils.encode_cell({ r: row, c: 11 })] as any
    if (progressCell) progressCell.z = '0.00%'
    if (ratingCell) ratingCell.z = '0.00'
  })
  sheet['!cols'] = [
    { wch: 6 }, { wch: 22 }, { wch: 34 }, { wch: 24 }, { wch: 20 }, { wch: 17 }, { wch: 12 },
    { wch: 14 }, { wch: 12 }, { wch: 12 }, { wch: 15 }, { wch: 10 }, { wch: 14 }, { wch: 16 }, { wch: 30 },
  ]
  sheet['!autofilter'] = { ref: XLSX.utils.encode_range({ r: 0, c: 0 }, { r: Math.max(rows.length, 1), c: headers.length - 1 }) }
  ;(sheet as any)['!freeze'] = { xSplit: 3, ySplit: 1 }
  ;(sheet as any)['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0 }
  return sheet
}

function buildAttributeSheet(options: CustomerAssessmentExcelOptions) {
  const rows = options.attributes.map(item => [item.no, item.dimension, item.attribute, number(item.score)])
  const sheet = XLSX.utils.aoa_to_sheet([['No', 'Dimensi', 'Atribut Layanan', 'Rata-rata Kepuasan'], ...rows])
  styleRange(sheet, 0, 0, 0, 3, {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 10, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center' },
    border: thinBorder,
  })
  rows.forEach((_, index) => {
    styleRange(sheet, index + 1, 0, index + 1, 3, {
      fill: { patternType: 'solid', fgColor: { rgb: index % 2 ? COLORS.rowAlt : COLORS.white } },
      font: { name: 'Aptos', size: 9, color: { rgb: COLORS.slate } },
      alignment: { vertical: 'top', wrapText: true },
      border: thinBorder,
    })
    ;(sheet[XLSX.utils.encode_cell({ r: index + 1, c: 3 })] as any).z = '0.00'
  })
  sheet['!cols'] = [{ wch: 7 }, { wch: 20 }, { wch: 72 }, { wch: 22 }]
  sheet['!autofilter'] = { ref: `A1:D${Math.max(rows.length + 1, 2)}` }
  ;(sheet as any)['!freeze'] = { ySplit: 1 }
  return sheet
}

export function buildCustomerAssessmentExcel(options: CustomerAssessmentExcelOptions) {
  const workbook = XLSX.utils.book_new()
  const { sheet: dashboard, topUnits } = buildDashboard(options)
  XLSX.utils.book_append_sheet(workbook, dashboard, DASHBOARD_SHEET)
  XLSX.utils.book_append_sheet(workbook, buildDataSheet(options), DATA_SHEET)
  XLSX.utils.book_append_sheet(workbook, buildAttributeSheet(options), ATTRIBUTE_SHEET)
  workbook.Props = {
    Title: `Penilaian Pelanggan ${options.filters.location} ${options.filters.year}`,
    Subject: 'Kepuasan pelanggan eksternal U-LAB sesuai filter',
    Author: 'U-LAB',
    CreatedDate: options.generatedAt || new Date(),
  }

  const source = XLSX.write(workbook, { type: 'array', bookType: 'xlsx', compression: true, cellDates: true })
  const charts: any[] = []
  if (options.ratingDistribution.length) {
    const lastRow = options.ratingDistribution.length + 1
    charts.push({
      title: 'Distribusi Rating Pelanggan', categoryRange: `$Q$2:$Q$${lastRow}`,
      categories: options.ratingDistribution.map(item => item.label),
      series: [{ name: 'Jumlah', titleCell: '$R$1', valueRange: `$R$2:$R$${lastRow}`, values: options.ratingDistribution.map(item => number(item.value)), color: '#F59E0B' }],
      from: { col: 0, row: 10 }, to: { col: 7, row: 27 }, showValues: true, valueFormat: '#,##0',
    })
  }
  if (topUnits.length) {
    const lastRow = topUnits.length + 1
    charts.push({
      title: 'Rating Pelanggan per Unit', categoryRange: `$T$2:$T$${lastRow}`,
      categories: topUnits.map(item => truncate(item.unit)),
      series: [{ name: 'Rating', titleCell: '$U$1', valueRange: `$U$2:$U$${lastRow}`, values: topUnits.map(item => number(item.rating)), color: '#2563EB' }],
      from: { col: 8, row: 10 }, to: { col: 15, row: 27 }, horizontal: true, showValues: true, valueFormat: '0.00',
    })
  }
  if (options.monthly.length) {
    const lastRow = options.monthly.length + 1
    charts.push({
      title: 'Pengisian Survey per Bulan', categoryRange: `$W$2:$W$${lastRow}`,
      categories: options.monthly.map(item => item.month),
      series: [
        { name: 'Sudah Isi', titleCell: '$X$1', valueRange: `$X$2:$X$${lastRow}`, values: options.monthly.map(item => number(item.sudah)), color: '#059669' },
        { name: 'Belum Isi', titleCell: '$Y$1', valueRange: `$Y$2:$Y$${lastRow}`, values: options.monthly.map(item => number(item.belum)), color: '#D97706' },
      ],
      from: { col: 0, row: 29 }, to: { col: 15, row: 47 }, showValues: false, valueFormat: '#,##0',
    })
  }
  return addNativeExcelCharts(source, DASHBOARD_SHEET, charts)
}

export function exportCustomerAssessmentExcel(options: CustomerAssessmentExcelOptions) {
  const bytes = buildCustomerAssessmentExcel(options)
  const fileName = ['Penilaian_Pelanggan', safeFilePart(options.filters.location), safeFilePart(options.filters.year)].join('_') + '.xlsx'
  downloadExcelFile(bytes, fileName)
  return fileName
}
