import * as XLSX from 'xlsx-js-style'
import { addNativeExcelCharts, downloadExcelFile } from './excelNativeCharts'

type MonitoringSummary = {
  rencana_alat_surkes?: number
  realisasi_surkes?: number
  sisa_surkes_belum_daftar?: number
  progress?: number
}

type MonitoringGroup = {
  label?: string
  rencana_alat_surkes?: number
  realisasi_surkes?: number
  sisa_surkes_belum_daftar?: number
  progress?: number
}

export type MonitoringSurkesExcelRow = {
  status?: string
  status_label?: string
  namaperusahaan?: string
  namaproduk?: string | null
  namamerk?: string | null
  namatipe?: string | null
  namaserialnumber?: string | null
  lingkup_mapping?: string | null
  lingkup_registrasi?: string | null
  registration_count?: number
  duplicate_count?: number
  lingkup_mismatch?: boolean
  jenisorder?: string[]
  last_nopendaftaran?: string | null
  last_noorderalat?: string | null
  last_tglregistrasi?: string | null
  status_pekerjaan?: string
  last_jenisorder?: string | null
  pelaksana?: string | null
  lokasi?: string | null
  progress_persen?: number
  status_terakhir?: string | null
  status_pengambilan?: string | null
}

export type MonitoringSurkesExcelOptions = {
  year: string
  lokasi: string
  filters: {
    unit: string
    lingkup: string
    status: string
    keyword: string
  }
  summary: MonitoringSummary
  byUnit: MonitoringGroup[]
  rows: MonitoringSurkesExcelRow[]
  generatedAt?: Date
}

export type MonitoringSurkesDetailExcelOptions = {
  title: string
  subtitle: string
  year: string
  lokasi: string
  keyword: string
  rows: MonitoringSurkesExcelRow[]
  generatedAt?: Date
}

const SHEET_NAME = 'Monitoring Surkes'
const INTEGER_FORMAT = '#,##0'
const PERCENT_FORMAT = '0.0%'

const COLORS = {
  navy: '172554',
  blue: '2563EB',
  blueSoft: 'EFF6FF',
  green: '16A34A',
  greenSoft: 'F0FDF4',
  amber: 'F59E0B',
  amberSoft: 'FFFBEB',
  slate: '334155',
  muted: '64748B',
  border: 'CBD5E1',
  rowAlt: 'F8FAFC',
  white: 'FFFFFF',
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
  return String(value || 'Surkes')
    .trim()
    .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '_')
    .replace(/\s+/g, '_')
    .replace(/_+/g, '_')
    .slice(0, 80)
}

function setCell(
  sheet: XLSX.WorkSheet,
  row: number,
  col: number,
  value: unknown,
  style?: Record<string, unknown>,
  format?: string,
) {
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

function styleRange(
  sheet: XLSX.WorkSheet,
  startRow: number,
  startCol: number,
  endRow: number,
  endCol: number,
  style: Record<string, unknown>,
) {
  for (let row = startRow; row <= endRow; row += 1) {
    for (let col = startCol; col <= endCol; col += 1) {
      const address = XLSX.utils.encode_cell({ r: row, c: col })
      if (!sheet[address]) sheet[address] = { t: 's', v: '' }
      ;(sheet[address] as any).s = style
    }
  }
}

function addSummaryCard(
  sheet: XLSX.WorkSheet,
  startCol: number,
  endCol: number,
  label: string,
  value: number,
  fill: string,
  textColor: string,
  format: string,
) {
  const style = {
    fill: { patternType: 'solid', fgColor: { rgb: fill } },
    font: { name: 'Aptos', size: 9, color: { rgb: textColor } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  }
  merge(sheet, 4, startCol, endCol)
  merge(sheet, 5, startCol, endCol)
  styleRange(sheet, 4, startCol, 5, endCol, style)
  setCell(sheet, 4, startCol, label, {
    ...style,
    font: { name: 'Aptos', size: 9, bold: true, color: { rgb: textColor } },
  })
  setCell(sheet, 5, startCol, value, {
    ...style,
    font: { name: 'Aptos Display', size: 16, bold: true, color: { rgb: textColor } },
  }, format)
}

function dateLabel(value: string | null | undefined) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

export function buildMonitoringSurkesExcel(options: MonitoringSurkesExcelOptions) {
  const generatedAt = options.generatedAt || new Date()
  const workbook = XLSX.utils.book_new()
  const sheet = XLSX.utils.aoa_to_sheet([])
  const titleStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos Display', size: 20, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'left', vertical: 'center' },
  }
  const subtitleStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 10, color: { rgb: 'DDE7F7' } },
    alignment: { horizontal: 'left', vertical: 'center' },
  }
  const metadataStyle = {
    font: { name: 'Aptos', size: 9, color: { rgb: COLORS.muted } },
    alignment: { horizontal: 'left', vertical: 'center', wrapText: true },
  }
  const tableHeaderStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 9, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  }
  const dataStyle = {
    font: { name: 'Aptos', size: 9, color: { rgb: COLORS.slate } },
    alignment: { vertical: 'top', wrapText: true },
    border: thinBorder,
  }

  merge(sheet, 0, 0, 13)
  merge(sheet, 1, 0, 13)
  merge(sheet, 2, 0, 13)
  styleRange(sheet, 0, 0, 1, 13, titleStyle)
  setCell(sheet, 0, 0, `MONITORING ALAT SURKES - ${options.lokasi.toLocaleUpperCase('id-ID')}`, titleStyle)
  setCell(sheet, 1, 0, 'Rencana, realisasi, dan alat Surkes belum daftar per unit', subtitleStyle)
  setCell(
    sheet,
    2,
    0,
    `Tahun ${options.year} | Unit: ${options.filters.unit} | Lingkup: ${options.filters.lingkup} | Status: ${options.filters.status} | Pencarian: ${options.filters.keyword} | Dibuat: ${generatedAt.toLocaleString('id-ID')}`,
    metadataStyle,
  )

  addSummaryCard(sheet, 0, 2, 'RENCANA SURKES', number(options.summary.rencana_alat_surkes), COLORS.blueSoft, COLORS.blue, INTEGER_FORMAT)
  addSummaryCard(sheet, 3, 5, 'REALISASI SURKES', number(options.summary.realisasi_surkes), COLORS.greenSoft, COLORS.green, INTEGER_FORMAT)
  addSummaryCard(sheet, 6, 8, 'SURKES BELUM DAFTAR', number(options.summary.sisa_surkes_belum_daftar), COLORS.amberSoft, COLORS.amber, INTEGER_FORMAT)
  addSummaryCard(sheet, 9, 11, 'PROGRESS REALISASI', number(options.summary.progress) / 100, COLORS.greenSoft, COLORS.green, PERCENT_FORMAT)
  addSummaryCard(sheet, 12, 13, 'JUMLAH UNIT', options.byUnit.length, COLORS.blueSoft, COLORS.blue, INTEGER_FORMAT)

  const chartTopRow = 8
  const chartBottomRow = chartTopRow + Math.max(26, options.byUnit.length * 2 + 10)
  const unitHeaderRow = chartBottomRow + 2
  const unitHeaders = ['No', 'Unit', 'Rencana', 'Realisasi', 'Surkes Belum Daftar', 'Progress']
  unitHeaders.forEach((label, col) => setCell(sheet, unitHeaderRow, col, label, tableHeaderStyle))

  options.byUnit.forEach((unit, index) => {
    const row = unitHeaderRow + index + 1
    const fill = index % 2 === 1 ? COLORS.rowAlt : COLORS.white
    const rowStyle = { ...dataStyle, fill: { patternType: 'solid', fgColor: { rgb: fill } } }
    const values = [
      index + 1,
      unit.label || '-',
      number(unit.rencana_alat_surkes),
      number(unit.realisasi_surkes),
      number(unit.sisa_surkes_belum_daftar),
      number(unit.progress) / 100,
    ]
    values.forEach((value, col) => {
      const format = col >= 2 && col <= 4 ? INTEGER_FORMAT : (col === 5 ? PERCENT_FORMAT : undefined)
      setCell(sheet, row, col, value, rowStyle, format)
    })
  })

  const unitFirstExcelRow = unitHeaderRow + 2
  const unitLastExcelRow = unitHeaderRow + options.byUnit.length + 1
  const detailHeaderRow = unitHeaderRow + Math.max(options.byUnit.length, 1) + 3
  merge(sheet, detailHeaderRow - 1, 0, 13)
  setCell(sheet, detailHeaderRow - 1, 0, 'DETAIL ALAT SESUAI FILTER', {
    fill: { patternType: 'solid', fgColor: { rgb: 'E8EEF9' } },
    font: { name: 'Aptos Display', size: 12, bold: true, color: { rgb: COLORS.navy } },
    alignment: { horizontal: 'left', vertical: 'center' },
    border: thinBorder,
  })

  const detailHeaders = [
    'Status', 'Unit', 'Alat', 'Merk', 'Tipe', 'SN', 'Rencana Surkes', 'Lingkup Pendaftaran',
    'Jumlah Pendaftaran', 'Duplikasi', 'No Pendaftaran Terakhir', 'No Order Terakhir', 'Tanggal Registrasi', 'Pekerjaan',
  ]
  detailHeaders.forEach((label, col) => setCell(sheet, detailHeaderRow, col, label, tableHeaderStyle))

  options.rows.forEach((item, index) => {
    const row = detailHeaderRow + index + 1
    const fill = index % 2 === 1 ? COLORS.rowAlt : COLORS.white
    const rowStyle = { ...dataStyle, fill: { patternType: 'solid', fgColor: { rgb: fill } } }
    const values = [
      item.status_label || '-', item.namaperusahaan || '-', item.namaproduk || '-', item.namamerk || '-',
      item.namatipe || '-', item.namaserialnumber || '-', item.lingkup_mapping || '-', item.lingkup_registrasi || '-',
      number(item.registration_count), number(item.duplicate_count), item.last_nopendaftaran || '-',
      item.last_noorderalat || '-', dateLabel(item.last_tglregistrasi), item.status_pekerjaan || '-',
    ]
    values.forEach((value, col) => setCell(sheet, row, col, value, rowStyle, col === 8 || col === 9 ? INTEGER_FORMAT : undefined))
  })

  const lastRow = detailHeaderRow + Math.max(options.rows.length, 1)
  sheet['!cols'] = [
    { wch: 8 }, { wch: 38 }, { wch: 30 }, { wch: 18 }, { wch: 18 }, { wch: 22 }, { wch: 24 },
    { wch: 24 }, { wch: 20 }, { wch: 14 }, { wch: 25 }, { wch: 22 }, { wch: 20 }, { wch: 18 },
  ]
  sheet['!rows'] = [{ hpt: 34 }, { hpt: 22 }, { hpt: 28 }, undefined, { hpt: 22 }, { hpt: 30 }]
  sheet['!ref'] = XLSX.utils.encode_range({ r: 0, c: 0 }, { r: lastRow, c: 13 })
  ;(sheet as any)['!freeze'] = { xSplit: 2, ySplit: detailHeaderRow + 1 }
  ;(sheet as any)['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0, paperSize: 9 }
  ;(sheet as any)['!margins'] = { left: 0.2, right: 0.2, top: 0.45, bottom: 0.45, header: 0.2, footer: 0.2 }
  if (options.rows.length) {
    sheet['!autofilter'] = {
      ref: XLSX.utils.encode_range({ r: detailHeaderRow, c: 0 }, { r: detailHeaderRow + options.rows.length, c: 13 }),
    }
  }

  XLSX.utils.book_append_sheet(workbook, sheet, SHEET_NAME)
  workbook.Props = {
    Title: `Monitoring Alat Surkes ${options.lokasi} ${options.year}`,
    Subject: 'Rencana, realisasi, dan Surkes belum daftar per unit',
    Author: 'U-LAB',
    CreatedDate: generatedAt,
  }

  const source = XLSX.write(workbook, {
    type: 'array',
    bookType: 'xlsx',
    cellDates: true,
    compression: true,
  })
  if (!options.byUnit.length) return new Uint8Array(source)

  return addNativeExcelCharts(source, SHEET_NAME, [{
    title: `Rencana, Realisasi, dan Surkes Belum Daftar per Unit - ${options.lokasi} (${options.year})`,
    categoryRange: `$B$${unitFirstExcelRow}:$B$${unitLastExcelRow}`,
    categories: options.byUnit.map(unit => unit.label || '-'),
    series: [
      {
        name: 'Rencana',
        titleCell: `$C$${unitHeaderRow + 1}`,
        valueRange: `$C$${unitFirstExcelRow}:$C$${unitLastExcelRow}`,
        values: options.byUnit.map(unit => number(unit.rencana_alat_surkes)),
        color: '#2563EB',
      },
      {
        name: 'Realisasi',
        titleCell: `$D$${unitHeaderRow + 1}`,
        valueRange: `$D$${unitFirstExcelRow}:$D$${unitLastExcelRow}`,
        values: options.byUnit.map(unit => number(unit.realisasi_surkes)),
        color: '#16A34A',
      },
      {
        name: 'Surkes Belum Daftar',
        titleCell: `$E$${unitHeaderRow + 1}`,
        valueRange: `$E$${unitFirstExcelRow}:$E$${unitLastExcelRow}`,
        values: options.byUnit.map(unit => number(unit.sisa_surkes_belum_daftar)),
        color: '#F59E0B',
      },
    ],
    from: { col: 0, row: chartTopRow },
    to: { col: 14, row: chartBottomRow },
    horizontal: true,
    showValues: true,
    valueFormat: INTEGER_FORMAT,
  }])
}

export function exportMonitoringSurkesExcel(options: MonitoringSurkesExcelOptions) {
  const bytes = buildMonitoringSurkesExcel(options)
  const fileName = `Monitoring_Alat_Surkes_${safeFilePart(options.lokasi)}_${safeFilePart(options.year)}.xlsx`
  downloadExcelFile(bytes, fileName)
  return fileName
}

export function buildMonitoringSurkesDetailExcel(options: MonitoringSurkesDetailExcelOptions) {
  const generatedAt = options.generatedAt || new Date()
  const workbook = XLSX.utils.book_new()
  const sheet = XLSX.utils.aoa_to_sheet([])
  const totalSurkes = options.rows.filter(row => row.status !== 'alat_non_surkes').length
  const totalNonSurkes = options.rows.filter(row => row.status === 'alat_non_surkes').length
  const totalPendaftaran = options.rows.reduce((sum, row) => sum + number(row.registration_count), 0)
  const totalDuplikasi = options.rows.reduce((sum, row) => sum + number(row.duplicate_count), 0)
  const titleStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos Display', size: 18, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'left', vertical: 'center' },
  }
  const subtitleStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 10, color: { rgb: 'DDE7F7' } },
    alignment: { horizontal: 'left', vertical: 'center', wrapText: true },
  }
  const metadataStyle = {
    font: { name: 'Aptos', size: 9, color: { rgb: COLORS.muted } },
    alignment: { horizontal: 'left', vertical: 'center', wrapText: true },
  }
  const headerStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 9, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  }
  const dataStyle = {
    font: { name: 'Aptos', size: 9, color: { rgb: COLORS.slate } },
    alignment: { vertical: 'top', wrapText: true },
    border: thinBorder,
  }

  merge(sheet, 0, 0, 21)
  merge(sheet, 1, 0, 21)
  merge(sheet, 2, 0, 21)
  styleRange(sheet, 0, 0, 1, 21, titleStyle)
  setCell(sheet, 0, 0, options.title.toLocaleUpperCase('id-ID'), titleStyle)
  setCell(sheet, 1, 0, options.subtitle || 'Rincian alat monitoring Surkes', subtitleStyle)
  setCell(
    sheet,
    2,
    0,
    `Lokasi ${options.lokasi} | Tahun ${options.year} | Pencarian modal: ${options.keyword} | Dibuat: ${generatedAt.toLocaleString('id-ID')}`,
    metadataStyle,
  )

  addSummaryCard(sheet, 0, 2, 'ALAT DITEMUKAN', options.rows.length, COLORS.blueSoft, COLORS.blue, INTEGER_FORMAT)
  addSummaryCard(sheet, 3, 5, 'ALAT SURKES', totalSurkes, COLORS.greenSoft, COLORS.green, INTEGER_FORMAT)
  addSummaryCard(sheet, 6, 8, 'ALAT NON SURKES', totalNonSurkes, 'FEF2F2', 'DC2626', INTEGER_FORMAT)
  addSummaryCard(sheet, 9, 11, 'TOTAL PENDAFTARAN', totalPendaftaran, COLORS.blueSoft, COLORS.blue, INTEGER_FORMAT)
  addSummaryCard(sheet, 12, 21, 'DUPLIKASI PENDAFTARAN', totalDuplikasi, COLORS.amberSoft, COLORS.amber, INTEGER_FORMAT)

  const headerRow = 7
  const headers = [
    'No', 'Status', 'Unit', 'Alat', 'Merk', 'Tipe', 'SN', 'Lingkup Mapping', 'Lingkup Pendaftaran',
    'Jumlah Pendaftaran', 'Duplikasi', 'No Pendaftaran Terakhir', 'No Order Terakhir', 'Tanggal Registrasi',
    'Jenis Order', 'Pelaksana', 'ULAB', 'Progress', 'Pekerjaan', 'Posisi / Status Terakhir',
    'Pengambilan', 'Lingkup Berbeda',
  ]
  headers.forEach((label, col) => setCell(sheet, headerRow, col, label, headerStyle))

  options.rows.forEach((item, index) => {
    const row = headerRow + index + 1
    const fill = index % 2 === 1 ? COLORS.rowAlt : COLORS.white
    const rowStyle = { ...dataStyle, fill: { patternType: 'solid', fgColor: { rgb: fill } } }
    const values = [
      index + 1,
      item.status_label || '-',
      item.namaperusahaan || '-',
      item.namaproduk || '-',
      item.namamerk || '-',
      item.namatipe || '-',
      item.namaserialnumber || '-',
      item.lingkup_mapping || '-',
      item.lingkup_registrasi || '-',
      number(item.registration_count),
      number(item.duplicate_count),
      item.last_nopendaftaran || '-',
      item.last_noorderalat || '-',
      dateLabel(item.last_tglregistrasi),
      item.last_jenisorder || item.jenisorder?.join(', ') || '-',
      item.pelaksana || '-',
      item.lokasi || '-',
      number(item.progress_persen) / 100,
      item.status_pekerjaan || '-',
      item.status_terakhir || item.status_label || '-',
      item.status_pengambilan || '-',
      item.lingkup_mismatch ? 'Ya' : 'Tidak',
    ]
    values.forEach((value, col) => {
      const format = col === 9 || col === 10 ? INTEGER_FORMAT : (col === 17 ? PERCENT_FORMAT : undefined)
      setCell(sheet, row, col, value, rowStyle, format)
    })
  })

  const lastRow = headerRow + Math.max(options.rows.length, 1)
  sheet['!cols'] = [
    { wch: 6 }, { wch: 24 }, { wch: 36 }, { wch: 32 }, { wch: 18 }, { wch: 18 }, { wch: 22 },
    { wch: 24 }, { wch: 24 }, { wch: 18 }, { wch: 12 }, { wch: 25 }, { wch: 22 }, { wch: 20 },
    { wch: 20 }, { wch: 20 }, { wch: 12 }, { wch: 14 }, { wch: 18 }, { wch: 34 },
    { wch: 18 }, { wch: 16 },
  ]
  sheet['!rows'] = [{ hpt: 32 }, { hpt: 28 }, { hpt: 24 }, undefined, { hpt: 22 }, { hpt: 30 }, undefined, { hpt: 30 }]
  sheet['!ref'] = XLSX.utils.encode_range({ r: 0, c: 0 }, { r: lastRow, c: 21 })
  sheet['!autofilter'] = {
    ref: XLSX.utils.encode_range({ r: headerRow, c: 0 }, { r: headerRow + options.rows.length, c: 21 }),
  }
  ;(sheet as any)['!freeze'] = { xSplit: 3, ySplit: headerRow + 1 }
  ;(sheet as any)['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0, paperSize: 9 }
  ;(sheet as any)['!margins'] = { left: 0.2, right: 0.2, top: 0.45, bottom: 0.45, header: 0.2, footer: 0.2 }

  XLSX.utils.book_append_sheet(workbook, sheet, 'Detail Alat')
  workbook.Props = {
    Title: options.title,
    Subject: options.subtitle || 'Rincian alat monitoring Surkes',
    Author: 'U-LAB',
    CreatedDate: generatedAt,
  }

  return new Uint8Array(XLSX.write(workbook, {
    type: 'array',
    bookType: 'xlsx',
    cellDates: true,
    compression: true,
  }))
}

export function exportMonitoringSurkesDetailExcel(options: MonitoringSurkesDetailExcelOptions) {
  const bytes = buildMonitoringSurkesDetailExcel(options)
  const fileName = `${safeFilePart(options.title || 'Detail_Alat_Surkes')}.xlsx`
  downloadExcelFile(bytes, fileName)
  return fileName
}
