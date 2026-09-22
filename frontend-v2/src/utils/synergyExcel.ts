import * as XLSX from 'xlsx-js-style'
import { strFromU8, strToU8, unzipSync, zipSync } from 'fflate'

export type SynergyExcelScopeRow = {
  lingkup?: string
  total_jumlah?: number | string
  total_selesai?: number | string
  total_belum?: number | string
  surkes_jumlah?: number | string
  non_jumlah?: number | string
}

export type SynergyExcelUnit = {
  mitra_id: number
  namaperusahaan: string
  total_notes?: number
  chartTotal?: {
    jumlah?: number | string
    jumlahselesai?: number | string
    jumlahbelumselesai?: number | string
    surkes?: { jumlah?: number | string }
    non_surkes?: { jumlah?: number | string }
  }
  chartLingkup?: SynergyExcelScopeRow[]
  details?: Array<{
    jenisorder?: string
    lingkup?: string
    selesai?: boolean | number | string | null
  }>
}

export type SynergyExcelGlobalNote = {
  formatted_date?: string
  nama_petugas?: string
  notes?: string
  note?: string
}

export type SynergyExcelLocation = {
  key: 'jakarta' | 'gresik'
  label: string
  units: SynergyExcelUnit[]
  globalNotes: SynergyExcelGlobalNote[]
}

type SynergyExcelOptions = {
  year: string
  generatedAt: Date
  searchKeyword?: string
  locations: SynergyExcelLocation[]
  notesMap: Map<number, any[]>
}

type LocationMetrics = {
  label: string
  units: number
  total: number
  calibration: number
  calibrationDone: number
  calibrationPending: number
  repair: number
  repairDone: number
  repairPending: number
  surkes: number
  nonSurkes: number
  done: number
  pending: number
}

type ChartSeries = {
  name: string
  range: string
  values: number[]
  color: string
}

const SHEET_RECAP = 'Rekap Progress'
const SHEET_DASHBOARD = 'Dashboard & Chart'
const SHEET_NOTES = 'Catatan Utama'

const COLORS = {
  navy: '17365D',
  navySoft: 'DCE6F1',
  blue: '2F75B5',
  blueSoft: 'D9EAF7',
  teal: '0F766E',
  tealSoft: 'D1FAE5',
  green: '2E7D32',
  greenSoft: 'E2F0D9',
  amber: 'C27C0E',
  amberSoft: 'FFF2CC',
  red: 'C0504D',
  redSoft: 'FCE4D6',
  purple: '7030A0',
  purpleSoft: 'E4DFEC',
  slate: '44546A',
  slateSoft: 'E7E6E6',
  border: 'CBD5E1',
  white: 'FFFFFF',
  text: '1F2937',
  muted: '64748B',
  rowAlt: 'F8FAFC',
}

const thinBorder = {
  top: { style: 'thin', color: { rgb: COLORS.border } },
  bottom: { style: 'thin', color: { rgb: COLORS.border } },
  left: { style: 'thin', color: { rgb: COLORS.border } },
  right: { style: 'thin', color: { rgb: COLORS.border } },
}

function toNumber(value: unknown) {
  const parsed = Number(value ?? 0)
  return Number.isFinite(parsed) ? parsed : 0
}

function isRepairLabel(value: unknown) {
  return String(value ?? '').trim().toLowerCase().includes('repair')
}

function toBool(value: unknown) {
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value === 1
  return ['1', 'true', 't', 'yes', 'y'].includes(String(value ?? '').trim().toLowerCase())
}

function getServiceTotals(unit: SynergyExcelUnit) {
  const total = toNumber(unit.chartTotal?.jumlah)
  const done = toNumber(unit.chartTotal?.jumlahselesai)
  const pending = toNumber(unit.chartTotal?.jumlahbelumselesai)
  const scopeRows = unit.chartLingkup || []
  const repairFromScope = scopeRows.reduce((sum, row) => (
    sum + (isRepairLabel(row.lingkup) ? toNumber(row.total_jumlah) : 0)
  ), 0)
  const repairDetails = (unit.details || []).filter((row) => (
    isRepairLabel(row.jenisorder) || isRepairLabel(row.lingkup)
  ))
  const repair = Math.min(total, scopeRows.length > 0 ? repairFromScope : repairDetails.length)
  const repairDoneFromScope = scopeRows.reduce((sum, row) => (
    sum + (isRepairLabel(row.lingkup) ? toNumber(row.total_selesai) : 0)
  ), 0)
  const repairPendingFromScope = scopeRows.reduce((sum, row) => (
    sum + (isRepairLabel(row.lingkup) ? toNumber(row.total_belum) : 0)
  ), 0)
  const repairDoneFromDetails = repairDetails.filter(row => toBool(row.selesai)).length
  const repairDone = Math.min(done, repair, scopeRows.length > 0 ? repairDoneFromScope : repairDoneFromDetails)
  const repairPending = Math.min(
    pending,
    Math.max(repair - repairDone, 0),
    scopeRows.length > 0 ? repairPendingFromScope : repairDetails.length - repairDoneFromDetails,
  )

  return {
    calibration: Math.max(total - repair, 0),
    calibrationDone: Math.max(done - repairDone, 0),
    calibrationPending: Math.max(pending - repairPending, 0),
    repair,
    repairDone,
    repairPending,
  }
}

function getLocationMetrics(location: SynergyExcelLocation): LocationMetrics {
  return location.units.reduce<LocationMetrics>((metrics, unit) => {
    const services = getServiceTotals(unit)
    metrics.units += 1
    metrics.total += toNumber(unit.chartTotal?.jumlah)
    metrics.calibration += services.calibration
    metrics.calibrationDone += services.calibrationDone
    metrics.calibrationPending += services.calibrationPending
    metrics.repair += services.repair
    metrics.repairDone += services.repairDone
    metrics.repairPending += services.repairPending
    metrics.surkes += toNumber(unit.chartTotal?.surkes?.jumlah)
    metrics.nonSurkes += toNumber(unit.chartTotal?.non_surkes?.jumlah)
    metrics.done += toNumber(unit.chartTotal?.jumlahselesai)
    metrics.pending += toNumber(unit.chartTotal?.jumlahbelumselesai)
    return metrics
  }, {
    label: location.label,
    units: 0,
    total: 0,
    calibration: 0,
    calibrationDone: 0,
    calibrationPending: 0,
    repair: 0,
    repairDone: 0,
    repairPending: 0,
    surkes: 0,
    nonSurkes: 0,
    done: 0,
    pending: 0,
  })
}

function combineMetrics(metrics: LocationMetrics[]) {
  return metrics.reduce((total, row) => ({
    label: total.label,
    units: total.units + row.units,
    total: total.total + row.total,
    calibration: total.calibration + row.calibration,
    calibrationDone: total.calibrationDone + row.calibrationDone,
    calibrationPending: total.calibrationPending + row.calibrationPending,
    repair: total.repair + row.repair,
    repairDone: total.repairDone + row.repairDone,
    repairPending: total.repairPending + row.repairPending,
    surkes: total.surkes + row.surkes,
    nonSurkes: total.nonSurkes + row.nonSurkes,
    done: total.done + row.done,
    pending: total.pending + row.pending,
  }), {
    label: 'Keseluruhan',
    units: 0,
    total: 0,
    calibration: 0,
    calibrationDone: 0,
    calibrationPending: 0,
    repair: 0,
    repairDone: 0,
    repairPending: 0,
    surkes: 0,
    nonSurkes: 0,
    done: 0,
    pending: 0,
  })
}

function formatUnitNotes(unitId: number, notesMap: Map<number, any[]>) {
  const notes = notesMap.get(unitId) || []
  if (notes.length === 0) return '-'

  return notes.map((note) => (
    `[${note.formatted_date || '-'}] ${note.nama_petugas || 'Admin'}: ${note.note || note.notes || '-'}`
  )).join('\r\n')
}

function encodeCell(row: number, col: number) {
  return XLSX.utils.encode_cell({ r: row, c: col })
}

function ensureCell(sheet: any, row: number, col: number) {
  const address = encodeCell(row, col)
  if (!sheet[address]) sheet[address] = { t: 's', v: '' }
  return sheet[address]
}

function styleRange(sheet: any, range: string, style: any) {
  const decoded = XLSX.utils.decode_range(range)
  for (let row = decoded.s.r; row <= decoded.e.r; row += 1) {
    for (let col = decoded.s.c; col <= decoded.e.c; col += 1) {
      ensureCell(sheet, row, col).s = style
    }
  }
}

function setNumberFormat(sheet: any, range: string, numberFormat: string) {
  const decoded = XLSX.utils.decode_range(range)
  for (let row = decoded.s.r; row <= decoded.e.r; row += 1) {
    for (let col = decoded.s.c; col <= decoded.e.c; col += 1) {
      ensureCell(sheet, row, col).z = numberFormat
    }
  }
}

function setRowHeight(sheet: any, row: number, height: number) {
  sheet['!rows'] = sheet['!rows'] || []
  sheet['!rows'][row - 1] = { hpt: height }
}

function addMerge(sheet: any, range: string) {
  sheet['!merges'] = sheet['!merges'] || []
  sheet['!merges'].push(XLSX.utils.decode_range(range))
}

function applyTitle(sheet: any, lastColumn: string, title: string, subtitle: string, generatedText: string) {
  addMerge(sheet, `A1:${lastColumn}1`)
  addMerge(sheet, `A2:${lastColumn}2`)
  addMerge(sheet, `A3:${lastColumn}3`)

  sheet.A1 = { t: 's', v: title }
  sheet.A2 = { t: 's', v: subtitle }
  sheet.A3 = { t: 's', v: generatedText }

  styleRange(sheet, `A1:${lastColumn}1`, {
    fill: { fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos Display', size: 18, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center' },
  })
  styleRange(sheet, `A2:${lastColumn}2`, {
    fill: { fgColor: { rgb: COLORS.navySoft } },
    font: { name: 'Aptos', size: 11, bold: true, color: { rgb: COLORS.navy } },
    alignment: { horizontal: 'center', vertical: 'center' },
  })
  styleRange(sheet, `A3:${lastColumn}3`, {
    fill: { fgColor: { rgb: 'F1F5F9' } },
    font: { name: 'Aptos', size: 9, italic: true, color: { rgb: COLORS.muted } },
    alignment: { horizontal: 'center', vertical: 'center' },
  })

  setRowHeight(sheet, 1, 30)
  setRowHeight(sheet, 2, 21)
  setRowHeight(sheet, 3, 18)
}

function applyKpiCard(sheet: any, labelRange: string, valueRange: string, label: string, value: number, color: string, softColor: string, numberFormat = '#,##0') {
  addMerge(sheet, labelRange)
  addMerge(sheet, valueRange)

  const labelCell = labelRange.split(':')[0]
  const valueCell = valueRange.split(':')[0]
  sheet[labelCell] = { t: 's', v: label }
  sheet[valueCell] = { t: 'n', v: value, z: numberFormat }

  styleRange(sheet, labelRange, {
    fill: { fgColor: { rgb: color } },
    font: { name: 'Aptos', size: 9, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center' },
    border: thinBorder,
  })
  styleRange(sheet, valueRange, {
    fill: { fgColor: { rgb: softColor } },
    font: { name: 'Aptos Display', size: 15, bold: true, color: { rgb: color } },
    alignment: { horizontal: 'center', vertical: 'center' },
    border: thinBorder,
  })
  ensureCell(sheet, XLSX.utils.decode_cell(valueCell).r, XLSX.utils.decode_cell(valueCell).c).z = numberFormat
}

function applyTableStyle(sheet: any, headerRow: number, lastRow: number, lastColumn: string, numericColumns: string[], percentColumns: string[] = []) {
  styleRange(sheet, `A${headerRow}:${lastColumn}${headerRow}`, {
    fill: { fgColor: { rgb: COLORS.teal } },
    font: { name: 'Aptos', size: 10, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  })
  setRowHeight(sheet, headerRow, 30)

  for (let row = headerRow + 1; row <= lastRow; row += 1) {
    styleRange(sheet, `A${row}:${lastColumn}${row}`, {
      fill: { fgColor: { rgb: row % 2 === 0 ? COLORS.rowAlt : COLORS.white } },
      font: { name: 'Aptos', size: 10, color: { rgb: COLORS.text } },
      alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
      border: thinBorder,
    })
    setRowHeight(sheet, row, 24)
  }

  numericColumns.forEach((column) => setNumberFormat(sheet, `${column}${headerRow + 1}:${column}${lastRow}`, '#,##0'))
  percentColumns.forEach((column) => setNumberFormat(sheet, `${column}${headerRow + 1}:${column}${lastRow}`, '0%'))
}

function buildLegacyRecapSheet(options: SynergyExcelOptions, metrics: LocationMetrics[], totalMetrics: LocationMetrics) {
  const locationLabel = options.locations.map((location) => location.label).join(' & ')
  const exportedAt = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(options.generatedAt)
  const filterText = options.searchKeyword?.trim()
    ? ` • Filter unit: “${options.searchKeyword.trim()}”`
    : ''

  const rows: any[][] = [
    ['LAPORAN MONITORING SYNERGY PAGI'],
    [`${locationLabel} • Tahun ${options.year}${filterText}`],
    [`Diekspor pada ${exportedAt}`],
    [],
    ['TOTAL UNIT', '', 'TOTAL ORDER', '', 'KALIBRASI', '', 'REPAIR', '', 'SELESAI', '', 'PROGRESS', ''],
    [totalMetrics.units, '', totalMetrics.total, '', totalMetrics.calibration, '', totalMetrics.repair, '', totalMetrics.done, '', totalMetrics.total ? totalMetrics.done / totalMetrics.total : 0, ''],
    [],
    ['No', 'Nama Unit', 'Lokasi', 'Total Order', 'Kalibrasi', 'Repair', 'Surkes', 'Non Surkes', 'Selesai', 'Belum Selesai', 'Progress (%)', 'Isi Catatan'],
  ]

  let sequence = 1
  options.locations.forEach((location) => {
    location.units.forEach((unit) => {
      const services = getServiceTotals(unit)
      const total = toNumber(unit.chartTotal?.jumlah)
      const done = toNumber(unit.chartTotal?.jumlahselesai)
      rows.push([
        sequence,
        unit.namaperusahaan || '-',
        location.label,
        total,
        services.calibration,
        services.repair,
        toNumber(unit.chartTotal?.surkes?.jumlah),
        toNumber(unit.chartTotal?.non_surkes?.jumlah),
        done,
        toNumber(unit.chartTotal?.jumlahbelumselesai),
        total ? done / total : 0,
        formatUnitNotes(unit.mitra_id, options.notesMap),
      ])
      sequence += 1
    })
  })

  if (sequence === 1) {
    rows.push(['-', 'Tidak ada data untuk filter yang dipilih', locationLabel, 0, 0, 0, 0, 0, 0, 0, 0, '-'])
  }

  const lastDataRow = rows.length
  rows.push([
    'TOTAL KESELURUHAN', '', '',
    totalMetrics.total,
    totalMetrics.calibration,
    totalMetrics.repair,
    totalMetrics.surkes,
    totalMetrics.nonSurkes,
    totalMetrics.done,
    totalMetrics.pending,
    totalMetrics.total ? totalMetrics.done / totalMetrics.total : 0,
    '',
  ])
  const totalRow = rows.length
  const sheet = XLSX.utils.aoa_to_sheet(rows)

  applyTitle(
    sheet,
    'L',
    'LAPORAN MONITORING SYNERGY PAGI',
    `${locationLabel} • Tahun ${options.year}${filterText}`,
    `Diekspor pada ${exportedAt}`,
  )
  applyKpiCard(sheet, 'A5:B5', 'A6:B6', 'TOTAL UNIT', totalMetrics.units, COLORS.navy, COLORS.navySoft)
  applyKpiCard(sheet, 'C5:D5', 'C6:D6', 'TOTAL ORDER', totalMetrics.total, COLORS.blue, COLORS.blueSoft)
  applyKpiCard(sheet, 'E5:F5', 'E6:F6', 'KALIBRASI', totalMetrics.calibration, COLORS.teal, COLORS.tealSoft)
  applyKpiCard(sheet, 'G5:H5', 'G6:H6', 'REPAIR', totalMetrics.repair, COLORS.purple, COLORS.purpleSoft)
  applyKpiCard(sheet, 'I5:J5', 'I6:J6', 'SELESAI', totalMetrics.done, COLORS.green, COLORS.greenSoft)
  applyKpiCard(sheet, 'K5:L5', 'K6:L6', 'PROGRESS', totalMetrics.total ? totalMetrics.done / totalMetrics.total : 0, COLORS.amber, COLORS.amberSoft, '0%')
  setRowHeight(sheet, 5, 18)
  setRowHeight(sheet, 6, 27)

  applyTableStyle(sheet, 8, lastDataRow, 'L', ['A', 'D', 'E', 'F', 'G', 'H', 'I', 'J'], ['K'])
  addMerge(sheet, `A${totalRow}:C${totalRow}`)
  styleRange(sheet, `A${totalRow}:L${totalRow}`, {
    fill: { fgColor: { rgb: COLORS.slateSoft } },
    font: { name: 'Aptos', size: 10, bold: true, color: { rgb: COLORS.navy } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  })
  setNumberFormat(sheet, `D${totalRow}:J${totalRow}`, '#,##0')
  setNumberFormat(sheet, `K${totalRow}`, '0%')
  setRowHeight(sheet, totalRow, 26)

  sheet['!cols'] = [
    { wch: 6 },
    { wch: 38 },
    { wch: 13 },
    { wch: 13 },
    { wch: 13 },
    { wch: 12 },
    { wch: 12 },
    { wch: 14 },
    { wch: 12 },
    { wch: 15 },
    { wch: 13 },
    { wch: 52 },
  ]
  sheet['!autofilter'] = { ref: `A8:L${lastDataRow}` }
  sheet['!margins'] = { left: 0.25, right: 0.25, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 }
  sheet['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0, paperSize: 9 }

  return sheet
}

function applyLocationSummaryBlock(sheet: any, metrics: LocationMetrics, startColumn: number, endColumn: number) {
  const blockWidth = endColumn - startColumn + 1
  const labelEndColumn = startColumn + (blockWidth <= 8 ? blockWidth - 3 : Math.floor(blockWidth / 2) - 1)
  const valueStartColumn = labelEndColumn + 1
  const startColumnName = XLSX.utils.encode_col(startColumn)
  const endColumnName = XLSX.utils.encode_col(endColumn)
  const labelEndColumnName = XLSX.utils.encode_col(labelEndColumn)
  const valueStartColumnName = XLSX.utils.encode_col(valueStartColumn)
  const headerRange = `${startColumnName}5:${endColumnName}5`

  addMerge(sheet, headerRange)
  sheet[encodeCell(4, startColumn)] = { t: 's', v: `U-LAB ${metrics.label.toUpperCase()}` }
  styleRange(sheet, headerRange, {
    fill: { fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos Display', size: 13, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center' },
    border: thinBorder,
  })
  setRowHeight(sheet, 5, 25)

  const summaryRows = [
    { label: 'Total Order', value: metrics.total, format: '#,##0 "Alat"', fill: COLORS.blueSoft, color: COLORS.blue },
    { label: 'Surkes', value: metrics.surkes, format: '#,##0 "Alat"', fill: 'EEF6FC', color: COLORS.blue },
    { label: 'Non Surkes', value: metrics.nonSurkes, format: '#,##0 "Alat"', fill: COLORS.slateSoft, color: COLORS.slate },
    { label: 'Selesai', value: metrics.done, format: '#,##0 "Alat"', fill: COLORS.greenSoft, color: COLORS.green },
    { label: 'Belum Selesai', value: metrics.pending, format: '#,##0 "Alat"', fill: COLORS.amberSoft, color: COLORS.amber },
    { label: 'Progress Keseluruhan', value: metrics.total ? metrics.done / metrics.total : 0, format: '0%', fill: COLORS.blueSoft, color: COLORS.blue },
    { label: 'Kalibrasi', value: metrics.calibration, format: '#,##0 "Alat"', fill: COLORS.tealSoft, color: COLORS.teal },
    { label: 'Kalibrasi Selesai', value: metrics.calibrationDone, format: '#,##0 "Alat"', fill: COLORS.tealSoft, color: COLORS.teal },
    { label: 'Kalibrasi Belum Selesai', value: metrics.calibrationPending, format: '#,##0 "Alat"', fill: COLORS.tealSoft, color: COLORS.teal },
    { label: 'Progress Kalibrasi', value: metrics.calibration ? metrics.calibrationDone / metrics.calibration : 0, format: '0%', fill: COLORS.tealSoft, color: COLORS.teal },
    { label: 'Repair', value: metrics.repair, format: '#,##0 "Alat"', fill: COLORS.purpleSoft, color: COLORS.purple },
    { label: 'Repair Selesai', value: metrics.repairDone, format: '#,##0 "Alat"', fill: COLORS.purpleSoft, color: COLORS.purple },
    { label: 'Repair Belum Selesai', value: metrics.repairPending, format: '#,##0 "Alat"', fill: COLORS.purpleSoft, color: COLORS.purple },
    { label: 'Progress Repair', value: metrics.repair ? metrics.repairDone / metrics.repair : 0, format: '0%', fill: COLORS.purpleSoft, color: COLORS.purple },
  ]

  summaryRows.forEach((item, index) => {
    const row = 6 + index
    const labelRange = `${startColumnName}${row}:${labelEndColumnName}${row}`
    const valueRange = `${valueStartColumnName}${row}:${endColumnName}${row}`
    addMerge(sheet, labelRange)
    addMerge(sheet, valueRange)
    sheet[encodeCell(row - 1, startColumn)] = { t: 's', v: item.label }
    sheet[encodeCell(row - 1, valueStartColumn)] = { t: 'n', v: item.value, z: item.format }
    styleRange(sheet, labelRange, {
      fill: { fgColor: { rgb: item.fill } },
      font: { name: 'Aptos', size: 10, bold: true, color: { rgb: item.color } },
      alignment: { horizontal: 'left', vertical: 'center' },
      border: thinBorder,
    })
    styleRange(sheet, valueRange, {
      fill: { fgColor: { rgb: item.fill } },
      font: { name: 'Aptos', size: 10, bold: true, color: { rgb: item.color } },
      alignment: { horizontal: 'center', vertical: 'center' },
      border: thinBorder,
    })
    ensureCell(sheet, row - 1, valueStartColumn).z = item.format
    setRowHeight(sheet, row, 20)
  })
}

function buildRecapSheet(options: SynergyExcelOptions, metrics: LocationMetrics[], totalMetrics: LocationMetrics) {
  const locationLabel = options.locations.map(location => location.label).join(' & ')
  const exportedAt = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(options.generatedAt)
  const filterText = options.searchKeyword?.trim()
    ? ` | Filter unit: "${options.searchKeyword.trim()}"`
    : ''
  const sheet = XLSX.utils.aoa_to_sheet([
    ['LAPORAN MONITORING SYNERGY PAGI'],
    [`${locationLabel} | Tahun ${options.year}${filterText}`],
    [`Diekspor pada ${exportedAt}`],
  ])

  applyTitle(
    sheet,
    'Q',
    'LAPORAN MONITORING SYNERGY PAGI',
    `${locationLabel} | Tahun ${options.year}${filterText}`,
    `Diekspor pada ${exportedAt}`,
  )

  if (metrics.length === 1) {
    applyLocationSummaryBlock(sheet, metrics[0], 0, 16)
  } else {
    applyLocationSummaryBlock(sheet, metrics[0], 0, 7)
    applyLocationSummaryBlock(sheet, metrics[1], 9, 16)
  }

  const detailHeaderRow = 21
  const detailRows: any[][] = []
  let sequence = 1
  options.locations.forEach((location) => {
    location.units.forEach((unit) => {
      const services = getServiceTotals(unit)
      const total = toNumber(unit.chartTotal?.jumlah)
      const done = toNumber(unit.chartTotal?.jumlahselesai)
      detailRows.push([
        sequence,
        unit.namaperusahaan || '-',
        location.label,
        total,
        services.calibration,
        services.calibrationDone,
        services.calibrationPending,
        services.repair,
        services.repairDone,
        services.repairPending,
        toNumber(unit.chartTotal?.surkes?.jumlah),
        toNumber(unit.chartTotal?.non_surkes?.jumlah),
        done,
        toNumber(unit.chartTotal?.jumlahbelumselesai),
        services.calibration ? services.calibrationDone / services.calibration : 0,
        total ? done / total : 0,
        formatUnitNotes(unit.mitra_id, options.notesMap),
      ])
      sequence += 1
    })
  })

  if (detailRows.length === 0) {
    detailRows.push(['-', 'Tidak ada data untuk filter yang dipilih', locationLabel, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '-'])
  }

  XLSX.utils.sheet_add_aoa(sheet, [
    ['No', 'Nama Unit', 'Lokasi', 'Total Order', 'Kalibrasi', 'Kalibrasi Selesai', 'Kalibrasi Belum Selesai', 'Repair', 'Repair Selesai', 'Repair Belum Selesai', 'Surkes', 'Non Surkes', 'Selesai', 'Belum Selesai', 'Progress Kalibrasi (%)', 'Progress Total (%)', 'Isi Catatan'],
    ...detailRows,
  ], { origin: `A${detailHeaderRow}` })

  const lastDataRow = detailHeaderRow + detailRows.length
  const totalRow = lastDataRow + 1
  XLSX.utils.sheet_add_aoa(sheet, [[
    'TOTAL KESELURUHAN', '', '',
    totalMetrics.total,
    totalMetrics.calibration,
    totalMetrics.calibrationDone,
    totalMetrics.calibrationPending,
    totalMetrics.repair,
    totalMetrics.repairDone,
    totalMetrics.repairPending,
    totalMetrics.surkes,
    totalMetrics.nonSurkes,
    totalMetrics.done,
    totalMetrics.pending,
    totalMetrics.calibration ? totalMetrics.calibrationDone / totalMetrics.calibration : 0,
    totalMetrics.total ? totalMetrics.done / totalMetrics.total : 0,
    '',
  ]], { origin: `A${totalRow}` })

  applyTableStyle(sheet, detailHeaderRow, lastDataRow, 'Q', ['A', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'], ['O', 'P'])
  addMerge(sheet, `A${totalRow}:C${totalRow}`)
  styleRange(sheet, `A${totalRow}:Q${totalRow}`, {
    fill: { fgColor: { rgb: COLORS.slateSoft } },
    font: { name: 'Aptos', size: 10, bold: true, color: { rgb: COLORS.navy } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  })
  setNumberFormat(sheet, `D${totalRow}:N${totalRow}`, '#,##0')
  setNumberFormat(sheet, `O${totalRow}:P${totalRow}`, '0%')
  setRowHeight(sheet, totalRow, 26)

  sheet['!cols'] = [
    { wch: 6 },
    { wch: 38 },
    { wch: 13 },
    { wch: 13 },
    { wch: 13 },
    { wch: 18 },
    { wch: 21 },
    { wch: 12 },
    { wch: 16 },
    { wch: 19 },
    { wch: 12 },
    { wch: 14 },
    { wch: 12 },
    { wch: 15 },
    { wch: 20 },
    { wch: 17 },
    { wch: 52 },
  ]
  sheet['!ref'] = `A1:Q${totalRow}`
  sheet['!autofilter'] = { ref: `A${detailHeaderRow}:Q${lastDataRow}` }
  sheet['!margins'] = { left: 0.25, right: 0.25, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 }
  sheet['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0, paperSize: 9 }

  return sheet
}

function aggregateScopes(locations: SynergyExcelLocation[]) {
  const scopeMap = new Map<string, { total: number; done: number; pending: number }>()
  locations.forEach((location) => {
    location.units.forEach((unit) => {
      ;(unit.chartLingkup || []).forEach((scope) => {
        const key = String(scope.lingkup || 'Lainnya').trim() || 'Lainnya'
        const current = scopeMap.get(key) || { total: 0, done: 0, pending: 0 }
        current.total += toNumber(scope.total_jumlah)
        current.done += toNumber(scope.total_selesai)
        current.pending += toNumber(scope.total_belum)
        scopeMap.set(key, current)
      })
    })
  })

  return Array.from(scopeMap.entries())
    .map(([scope, value]) => ({ scope, ...value }))
    .sort((a, b) => b.total - a.total || a.scope.localeCompare(b.scope, 'id'))
}

function styleDashboardTable(sheet: any, headerRow: number, lastRow: number, lastColumn: string) {
  applyTableStyle(sheet, headerRow, lastRow, lastColumn, Array.from({ length: XLSX.utils.decode_col(lastColumn) }, (_, index) => XLSX.utils.encode_col(index + 1)))
}

function buildDashboardSheet(options: SynergyExcelOptions, metrics: LocationMetrics[], totalMetrics: LocationMetrics) {
  const locationLabel = options.locations.map((location) => location.label).join(' & ')
  const scopeTotals = aggregateScopes(options.locations)
  const topScopes = scopeTotals.slice(0, 10)
  const rows: any[][] = []
  const sheet = XLSX.utils.aoa_to_sheet(rows)

  XLSX.utils.sheet_add_aoa(sheet, [
    ['DASHBOARD SYNERGY PAGI'],
    [`${locationLabel} • Tahun ${options.year} • Ringkasan visual dan data lingkup`],
    [`Kalibrasi: seluruh lingkup selain Repair • Repair: lingkup/order Repair`],
  ], { origin: 'A1' })
  applyTitle(
    sheet,
    'N',
    'DASHBOARD SYNERGY PAGI',
    `${locationLabel} • Tahun ${options.year} • Ringkasan visual dan data lingkup`,
    'Kalibrasi: seluruh lingkup selain Repair • Repair: lingkup/order Repair',
  )

  applyKpiCard(sheet, 'A5:B5', 'A6:B6', 'TOTAL ORDER', totalMetrics.total, COLORS.navy, COLORS.navySoft)
  applyKpiCard(sheet, 'C5:D5', 'C6:D6', 'KALIBRASI', totalMetrics.calibration, COLORS.teal, COLORS.tealSoft)
  applyKpiCard(sheet, 'E5:F5', 'E6:F6', 'REPAIR', totalMetrics.repair, COLORS.purple, COLORS.purpleSoft)
  applyKpiCard(sheet, 'G5:H5', 'G6:H6', 'SURKES', totalMetrics.surkes, COLORS.blue, COLORS.blueSoft)
  applyKpiCard(sheet, 'I5:J5', 'I6:J6', 'NON SURKES', totalMetrics.nonSurkes, COLORS.slate, COLORS.slateSoft)
  applyKpiCard(sheet, 'K5:L5', 'K6:L6', 'SELESAI', totalMetrics.done, COLORS.green, COLORS.greenSoft)
  applyKpiCard(sheet, 'M5:N5', 'M6:N6', 'BELUM', totalMetrics.pending, COLORS.amber, COLORS.amberSoft)

  const locationRows = metrics.length > 0 ? metrics : [{
    label: 'Tidak ada data', units: 0, total: 0,
    calibration: 0, calibrationDone: 0, calibrationPending: 0,
    repair: 0, repairDone: 0, repairPending: 0,
    surkes: 0, nonSurkes: 0, done: 0, pending: 0,
  }]
  XLSX.utils.sheet_add_aoa(sheet, [
    ['Lokasi', 'Total Order', 'Selesai', 'Belum Selesai'],
    ...locationRows.map((row) => [row.label, row.total, row.done, row.pending]),
  ], { origin: 'A8' })
  styleDashboardTable(sheet, 8, 8 + locationRows.length, 'D')

  XLSX.utils.sheet_add_aoa(sheet, [
    ['Jenis Layanan', 'Total', 'Selesai', 'Belum Selesai'],
    ['Kalibrasi', totalMetrics.calibration, totalMetrics.calibrationDone, totalMetrics.calibrationPending],
    ['Repair', totalMetrics.repair, totalMetrics.repairDone, totalMetrics.repairPending],
  ], { origin: 'A14' })
  styleDashboardTable(sheet, 14, 16, 'D')

  XLSX.utils.sheet_add_aoa(sheet, [
    ['Jenis Pendaftaran', 'Jumlah'],
    ['Surkes', totalMetrics.surkes],
    ['Non Surkes', totalMetrics.nonSurkes],
  ], { origin: 'A18' })
  styleDashboardTable(sheet, 18, 20, 'B')

  const topScopeRows = topScopes.length > 0 ? topScopes : [{ scope: 'Tidak ada data', total: 0, done: 0, pending: 0 }]
  XLSX.utils.sheet_add_aoa(sheet, [
    ['Top Lingkup', 'Total Order', 'Selesai', 'Belum Selesai'],
    ...topScopeRows.map((row) => [row.scope, row.total, row.done, row.pending]),
  ], { origin: 'A23' })
  styleDashboardTable(sheet, 23, 23 + topScopeRows.length, 'D')

  const detailHeaderRow = 70
  const detailRows: any[][] = []
  options.locations.forEach((location) => {
    location.units.forEach((unit) => {
      ;(unit.chartLingkup || []).forEach((scope) => {
        const total = toNumber(scope.total_jumlah)
        const repair = isRepairLabel(scope.lingkup) ? total : 0
        detailRows.push([
          location.label,
          unit.namaperusahaan || '-',
          scope.lingkup || 'Lainnya',
          total,
          total - repair,
          repair,
          toNumber(scope.surkes_jumlah),
          toNumber(scope.non_jumlah),
          toNumber(scope.total_selesai),
          toNumber(scope.total_belum),
        ])
      })
    })
  })
  if (detailRows.length === 0) {
    detailRows.push([locationLabel, 'Tidak ada data', '-', 0, 0, 0, 0, 0, 0, 0])
  }

  XLSX.utils.sheet_add_aoa(sheet, [
    ['Lokasi', 'Nama Unit', 'Lingkup Pekerjaan', 'Total Order', 'Kalibrasi', 'Repair', 'Surkes', 'Non Surkes', 'Selesai', 'Belum Selesai'],
    ...detailRows,
  ], { origin: `A${detailHeaderRow}` })
  const detailLastRow = detailHeaderRow + detailRows.length
  applyTableStyle(sheet, detailHeaderRow, detailLastRow, 'J', ['D', 'E', 'F', 'G', 'H', 'I', 'J'])
  sheet['!autofilter'] = { ref: `A${detailHeaderRow}:J${detailLastRow}` }
  sheet['!ref'] = `A1:N${detailLastRow}`

  sheet['!cols'] = [
    { wch: 16 },
    { wch: 38 },
    { wch: 28 },
    { wch: 14 },
    { wch: 13 },
    { wch: 12 },
    { wch: 12 },
    { wch: 14 },
    { wch: 12 },
    { wch: 15 },
    { wch: 10 },
    { wch: 10 },
    { wch: 10 },
    { wch: 10 },
  ]
  sheet['!margins'] = { left: 0.25, right: 0.25, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 }
  sheet['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0, paperSize: 9 }

  return {
    sheet,
    chartData: {
      locations: locationRows,
      topScopes: topScopeRows,
      totalMetrics,
    },
  }
}

function buildNotesSheet(options: SynergyExcelOptions) {
  const rows: any[][] = [
    ['CATATAN UTAMA SYNERGY PAGI'],
    [`${options.locations.map((location) => location.label).join(' & ')} • Tahun ${options.year}`],
    ['Catatan utama per lokasi pada saat laporan diekspor'],
    [],
    ['No', 'Lokasi', 'Tanggal', 'Oleh', 'Isi Catatan'],
  ]

  let sequence = 1
  options.locations.forEach((location) => {
    location.globalNotes.forEach((note) => {
      rows.push([
        sequence,
        location.label,
        note.formatted_date || '-',
        note.nama_petugas || 'Admin',
        note.notes || note.note || '-',
      ])
      sequence += 1
    })
  })
  if (sequence === 1) rows.push(['-', '-', '-', '-', 'Tidak ada catatan utama'])

  const sheet = XLSX.utils.aoa_to_sheet(rows)
  applyTitle(
    sheet,
    'E',
    'CATATAN UTAMA SYNERGY PAGI',
    `${options.locations.map((location) => location.label).join(' & ')} • Tahun ${options.year}`,
    'Catatan utama per lokasi pada saat laporan diekspor',
  )
  applyTableStyle(sheet, 5, rows.length, 'E', ['A'])
  sheet['!cols'] = [{ wch: 7 }, { wch: 15 }, { wch: 22 }, { wch: 24 }, { wch: 80 }]
  sheet['!autofilter'] = { ref: `A5:E${rows.length}` }
  return sheet
}

function escapeXml(value: unknown) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')
}

function chartTitleXml(title: string) {
  return `<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:r><a:rPr lang="id-ID" sz="1200" b="1"/><a:t>${escapeXml(title)}</a:t></a:r></a:p></c:rich></c:tx><c:layout/><c:overlay val="0"/></c:title>`
}

function stringCache(values: string[]) {
  return `<c:strCache><c:ptCount val="${values.length}"/>${values.map((value, index) => `<c:pt idx="${index}"><c:v>${escapeXml(value)}</c:v></c:pt>`).join('')}</c:strCache>`
}

function numberCache(values: number[]) {
  return `<c:numCache><c:formatCode>#,##0</c:formatCode><c:ptCount val="${values.length}"/>${values.map((value, index) => `<c:pt idx="${index}"><c:v>${toNumber(value)}</c:v></c:pt>`).join('')}</c:numCache>`
}

function buildSeriesXml(series: ChartSeries, index: number, categoryRange: string, categories: string[]) {
  return `<c:ser><c:idx val="${index}"/><c:order val="${index}"/><c:tx><c:v>${escapeXml(series.name)}</c:v></c:tx><c:spPr><a:solidFill><a:srgbClr val="${series.color}"/></a:solidFill><a:ln><a:noFill/></a:ln></c:spPr><c:cat><c:strRef><c:f>${escapeXml(categoryRange)}</c:f>${stringCache(categories)}</c:strRef></c:cat><c:val><c:numRef><c:f>${escapeXml(series.range)}</c:f>${numberCache(series.values)}</c:numRef></c:val></c:ser>`
}

function buildBarChartXml(config: {
  title: string
  categoryRange: string
  categories: string[]
  series: ChartSeries[]
  barDirection?: 'bar' | 'col'
  grouping?: 'clustered' | 'stacked'
  axisBase: number
}) {
  const categoryAxisId = config.axisBase
  const valueAxisId = config.axisBase + 1
  const barDirection = config.barDirection || 'col'
  const grouping = config.grouping || 'clustered'

  return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><c:date1904 val="0"/><c:lang val="id-ID"/><c:roundedCorners val="0"/><c:style val="10"/><c:chart>${chartTitleXml(config.title)}<c:autoTitleDeleted val="0"/><c:plotArea><c:layout/><c:barChart><c:barDir val="${barDirection}"/><c:grouping val="${grouping}"/><c:varyColors val="0"/>${config.series.map((series, index) => buildSeriesXml(series, index, config.categoryRange, config.categories)).join('')}<c:dLbls><c:showLegendKey val="0"/><c:showVal val="1"/><c:showCatName val="0"/><c:showSerName val="0"/><c:showPercent val="0"/><c:showLeaderLines val="0"/></c:dLbls><c:gapWidth val="80"/>${grouping === 'stacked' ? '<c:overlap val="100"/>' : ''}<c:axId val="${categoryAxisId}"/><c:axId val="${valueAxisId}"/></c:barChart><c:catAx><c:axId val="${categoryAxisId}"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="${barDirection === 'bar' ? 'l' : 'b'}"/><c:tickLblPos val="nextTo"/><c:crossAx val="${valueAxisId}"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/></c:catAx><c:valAx><c:axId val="${valueAxisId}"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="${barDirection === 'bar' ? 'b' : 'l'}"/><c:numFmt formatCode="#,##0" sourceLinked="0"/><c:majorGridlines/><c:tickLblPos val="nextTo"/><c:crossAx val="${categoryAxisId}"/><c:crosses val="autoZero"/><c:crossBetween val="between"/></c:valAx></c:plotArea><c:legend><c:legendPos val="b"/><c:layout/><c:overlay val="0"/></c:legend><c:plotVisOnly val="1"/><c:dispBlanksAs val="zero"/><c:showDLblsOverMax val="0"/></c:chart><c:printSettings><c:headerFooter/><c:pageMargins b="0.75" l="0.7" r="0.7" t="0.75" header="0.3" footer="0.3"/><c:pageSetup/></c:printSettings></c:chartSpace>`
}

function buildDoughnutChartXml(config: {
  title: string
  categoryRange: string
  categories: string[]
  valueRange: string
  values: number[]
  colors: string[]
}) {
  const points = config.colors.map((color, index) => `<c:dPt><c:idx val="${index}"/><c:spPr><a:solidFill><a:srgbClr val="${color}"/></a:solidFill><a:ln><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></a:ln></c:spPr></c:dPt>`).join('')
  return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><c:date1904 val="0"/><c:lang val="id-ID"/><c:roundedCorners val="0"/><c:style val="10"/><c:chart>${chartTitleXml(config.title)}<c:autoTitleDeleted val="0"/><c:plotArea><c:layout/><c:doughnutChart><c:varyColors val="1"/><c:ser><c:idx val="0"/><c:order val="0"/><c:tx><c:v>Jumlah</c:v></c:tx>${points}<c:cat><c:strRef><c:f>${escapeXml(config.categoryRange)}</c:f>${stringCache(config.categories)}</c:strRef></c:cat><c:val><c:numRef><c:f>${escapeXml(config.valueRange)}</c:f>${numberCache(config.values)}</c:numRef></c:val></c:ser><c:dLbls><c:showLegendKey val="0"/><c:showVal val="1"/><c:showCatName val="1"/><c:showSerName val="0"/><c:showPercent val="1"/><c:showLeaderLines val="1"/></c:dLbls><c:firstSliceAng val="270"/><c:holeSize val="58"/></c:doughnutChart></c:plotArea><c:legend><c:legendPos val="b"/><c:layout/><c:overlay val="0"/></c:legend><c:plotVisOnly val="1"/><c:dispBlanksAs val="zero"/></c:chart><c:printSettings><c:headerFooter/><c:pageMargins b="0.75" l="0.7" r="0.7" t="0.75" header="0.3" footer="0.3"/><c:pageSetup/></c:printSettings></c:chartSpace>`
}

function drawingAnchorXml(chartIndex: number, fromCol: number, fromRow: number, toCol: number, toRow: number) {
  return `<xdr:twoCellAnchor><xdr:from><xdr:col>${fromCol}</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>${fromRow}</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from><xdr:to><xdr:col>${toCol}</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>${toRow}</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:graphicFrame macro=""><xdr:nvGraphicFramePr><xdr:cNvPr id="${chartIndex + 1}" name="Chart ${chartIndex}"/><xdr:cNvGraphicFramePr/></xdr:nvGraphicFramePr><xdr:xfrm/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart"><c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="rId${chartIndex}"/></a:graphicData></a:graphic></xdr:graphicFrame><xdr:clientData/></xdr:twoCellAnchor>`
}

function addChartsToWorkbook(bytes: Uint8Array, chartData: ReturnType<typeof buildDashboardSheet>['chartData']) {
  const files = unzipSync(bytes)
  const dashboardSheetPath = 'xl/worksheets/sheet2.xml'
  const contentTypesPath = '[Content_Types].xml'
  const dashboardName = `'${SHEET_DASHBOARD}'`

  const locationLastRow = 8 + chartData.locations.length
  const topScopeLastRow = 23 + chartData.topScopes.length
  const locationCategories = chartData.locations.map((row) => row.label)
  const topScopeCategories = chartData.topScopes.map((row) => row.scope)

  const charts = [
    buildBarChartXml({
      title: 'Progress Penyelesaian per Lokasi',
      categoryRange: `${dashboardName}!$A$9:$A$${locationLastRow}`,
      categories: locationCategories,
      series: [
        { name: 'Selesai', range: `${dashboardName}!$C$9:$C$${locationLastRow}`, values: chartData.locations.map((row) => row.done), color: COLORS.green },
        { name: 'Belum Selesai', range: `${dashboardName}!$D$9:$D$${locationLastRow}`, values: chartData.locations.map((row) => row.pending), color: COLORS.amber },
      ],
      grouping: 'stacked',
      axisBase: 71234501,
    }),
    buildBarChartXml({
      title: 'Status Kalibrasi dan Repair',
      categoryRange: `${dashboardName}!$A$15:$A$16`,
      categories: ['Kalibrasi', 'Repair'],
      series: [
        { name: 'Selesai', range: `${dashboardName}!$C$15:$C$16`, values: [chartData.totalMetrics.calibrationDone, chartData.totalMetrics.repairDone], color: COLORS.green },
        { name: 'Belum Selesai', range: `${dashboardName}!$D$15:$D$16`, values: [chartData.totalMetrics.calibrationPending, chartData.totalMetrics.repairPending], color: COLORS.amber },
      ],
      grouping: 'stacked',
      axisBase: 71234551,
    }),
    buildDoughnutChartXml({
      title: 'Komposisi Surkes vs Non Surkes',
      categoryRange: `${dashboardName}!$A$19:$A$20`,
      categories: ['Surkes', 'Non Surkes'],
      valueRange: `${dashboardName}!$B$19:$B$20`,
      values: [chartData.totalMetrics.surkes, chartData.totalMetrics.nonSurkes],
      colors: [COLORS.blue, COLORS.slate],
    }),
    buildBarChartXml({
      title: 'Top 10 Lingkup berdasarkan Status',
      categoryRange: `${dashboardName}!$A$24:$A$${topScopeLastRow}`,
      categories: topScopeCategories,
      series: [
        { name: 'Selesai', range: `${dashboardName}!$C$24:$C$${topScopeLastRow}`, values: chartData.topScopes.map((row) => row.done), color: COLORS.green },
        { name: 'Belum Selesai', range: `${dashboardName}!$D$24:$D$${topScopeLastRow}`, values: chartData.topScopes.map((row) => row.pending), color: COLORS.amber },
      ],
      barDirection: 'bar',
      grouping: 'stacked',
      axisBase: 71234601,
    }),
  ]

  charts.forEach((chartXml, index) => {
    files[`xl/charts/chart${index + 1}.xml`] = strToU8(chartXml)
  })

  const anchors = [
    drawingAnchorXml(1, 5, 7, 13, 20),
    drawingAnchorXml(2, 5, 21, 13, 34),
    drawingAnchorXml(3, 5, 35, 13, 48),
    drawingAnchorXml(4, 5, 49, 13, 66),
  ]
  files['xl/drawings/drawing1.xml'] = strToU8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">${anchors.join('')}</xdr:wsDr>`)
  files['xl/drawings/_rels/drawing1.xml.rels'] = strToU8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${charts.map((_, index) => `<Relationship Id="rId${index + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart${index + 1}.xml"/>`).join('')}</Relationships>`)
  files['xl/worksheets/_rels/sheet2.xml.rels'] = strToU8('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>')

  let dashboardXml = strFromU8(files[dashboardSheetPath])
  if (!dashboardXml.includes('xmlns:r=')) {
    dashboardXml = dashboardXml.replace('<worksheet ', '<worksheet xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ')
  }
  dashboardXml = dashboardXml.replace('</worksheet>', '<drawing r:id="rId1"/></worksheet>')
  files[dashboardSheetPath] = strToU8(dashboardXml)

  let contentTypes = strFromU8(files[contentTypesPath])
  const overrides = [
    '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>',
    ...charts.map((_, index) => `<Override PartName="/xl/charts/chart${index + 1}.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>`),
  ].join('')
  contentTypes = contentTypes.replace('</Types>', `${overrides}</Types>`)
  files[contentTypesPath] = strToU8(contentTypes)

  return zipSync(files, { level: 6 })
}

function addFreezePane(bytes: Uint8Array, sheetNumber: number, frozenRows: number) {
  const files = unzipSync(bytes)
  const sheetPath = `xl/worksheets/sheet${sheetNumber}.xml`
  let xml = strFromU8(files[sheetPath])
  const pane = `<pane ySplit="${frozenRows}" topLeftCell="A${frozenRows + 1}" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A${frozenRows + 1}" sqref="A${frozenRows + 1}"/>`

  if (/<sheetView[^>]*\/>/.test(xml)) {
    xml = xml.replace(/<sheetView([^>]*)\/>/, `<sheetView$1>${pane}</sheetView>`)
  } else if (/<sheetViews>/.test(xml)) {
    xml = xml.replace(/<sheetView([^>]*)>([\s\S]*?)<\/sheetView>/, `<sheetView$1>${pane}</sheetView>`)
  } else {
    xml = xml.replace(/(<worksheet[^>]*>)/, `$1<sheetViews><sheetView workbookViewId="0">${pane}</sheetView></sheetViews>`)
  }
  files[sheetPath] = strToU8(xml)
  return zipSync(files, { level: 6 })
}

export function buildSynergyExcel(options: SynergyExcelOptions) {
  const workbook = XLSX.utils.book_new()
  const metrics = options.locations.map(getLocationMetrics)
  const totalMetrics = combineMetrics(metrics)
  const dashboard = buildDashboardSheet(options, metrics, totalMetrics)

  XLSX.utils.book_append_sheet(workbook, buildRecapSheet(options, metrics, totalMetrics), SHEET_RECAP)
  XLSX.utils.book_append_sheet(workbook, dashboard.sheet, SHEET_DASHBOARD)
  XLSX.utils.book_append_sheet(workbook, buildNotesSheet(options), SHEET_NOTES)

  const baseBytes = new Uint8Array(XLSX.write(workbook, {
    bookType: 'xlsx',
    type: 'array',
    cellStyles: true,
    compression: true,
  }))
  const withCharts = addChartsToWorkbook(baseBytes, dashboard.chartData)
  const withRecapFreeze = addFreezePane(withCharts, 1, 3)
  return addFreezePane(withRecapFreeze, 2, 6)
}
