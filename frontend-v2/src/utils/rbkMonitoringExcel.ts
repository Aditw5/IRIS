import * as XLSX from 'xlsx-js-style'
import { addNativeExcelCharts, downloadExcelFile } from './excelNativeCharts'

type MoneyCategory = {
  rencana?: number
  realisasi?: number
  sisa?: number
}

export type RbkMonitoringExcelUnit = {
  unit_id: number
  namaperusahaan: string
  no_surat?: string
  no_prk?: string
  cost_code?: string
  jadwal_kalibrasi?: string
  summary?: {
    total_rencana?: number
    total_realisasi?: number
    total_sisa?: number
    progress?: number
    kategori?: {
      manpower?: MoneyCategory
      material?: MoneyCategory
      mobilisasi?: MoneyCategory
    }
  }
  alat_monitoring?: {
    surkes?: {
      rencana?: number
      realisasi?: number
      belum?: number
      progress?: number
    }
    non_surkes?: {
      jumlah?: number
    }
  }
}

export type RbkMonitoringExcelOptions = {
  year: string
  filter: string
  sortLabel: string
  fileLabel: string
  generatedAt?: Date
  sections: Array<{
    lokasi: string
    data: RbkMonitoringExcelUnit[]
  }>
}

const SHEET_NAME = 'Monitoring RBK'
const MONEY_FORMAT = '"Rp" #,##0;[Red]-"Rp" #,##0'
const INTEGER_FORMAT = '#,##0'
const PERCENT_FORMAT = '0.0%'

const COLORS = {
  navy: '172554',
  navySoft: 'E8EEF9',
  blue: '2563EB',
  blueSoft: 'EFF6FF',
  green: '059669',
  greenSoft: 'ECFDF5',
  amber: 'D97706',
  amberSoft: 'FFF7ED',
  violet: '7C3AED',
  violetSoft: 'F5F3FF',
  red: 'DC2626',
  slate: '475569',
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
  return String(value || 'RBK')
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
  const cell: any = sheet[address] || {
    t: typeof value === 'number' ? 'n' : 's',
    v: value ?? '',
  }
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

function summaryStyle(fill: string, fontColor: string) {
  return {
    fill: { patternType: 'solid', fgColor: { rgb: fill } },
    font: { name: 'Aptos', size: 10, color: { rgb: fontColor } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  }
}

function addSummaryCard(
  sheet: XLSX.WorkSheet,
  row: number,
  startCol: number,
  endCol: number,
  label: string,
  value: number,
  style: Record<string, unknown>,
  format: string,
) {
  merge(sheet, row, startCol, endCol)
  merge(sheet, row + 1, startCol, endCol)
  styleRange(sheet, row, startCol, row + 1, endCol, style)
  setCell(sheet, row, startCol, label, {
    ...style,
    font: { name: 'Aptos', size: 9, bold: true, color: (style as any).font.color },
  })
  setCell(sheet, row + 1, startCol, value, {
    ...style,
    font: { name: 'Aptos Display', size: 15, bold: true, color: (style as any).font.color },
  }, format)
}

export function buildRbkMonitoringExcel(options: RbkMonitoringExcelOptions) {
  const generatedAt = options.generatedAt || new Date()
  const workbook = XLSX.utils.book_new()
  const sheet = XLSX.utils.aoa_to_sheet([])
  const allUnits = options.sections.flatMap(section => section.data)
  const totalRencana = allUnits.reduce((sum, unit) => sum + number(unit.summary?.total_rencana), 0)
  const totalRealisasi = allUnits.reduce((sum, unit) => sum + number(unit.summary?.total_realisasi), 0)
  const totalSisa = allUnits.reduce((sum, unit) => sum + number(unit.summary?.total_sisa), 0)

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
    alignment: { horizontal: 'left', vertical: 'center' },
  }

  merge(sheet, 0, 0, 14)
  merge(sheet, 1, 0, 14)
  styleRange(sheet, 0, 0, 1, 14, titleStyle)
  setCell(sheet, 0, 0, 'RBK MONITORING', titleStyle)
  setCell(sheet, 1, 0, 'Monitoring anggaran serta realisasi alat Surkes dan Non-Surkes per unit', subtitleStyle)
  merge(sheet, 2, 0, 14)
  setCell(
    sheet,
    2,
    0,
    `Tahun ${options.year}  |  Filter: ${options.filter}  |  Urutan: ${options.sortLabel}  |  Dibuat: ${generatedAt.toLocaleString('id-ID')}`,
    metadataStyle,
  )

  addSummaryCard(sheet, 4, 0, 4, 'TOTAL RENCANA ANGGARAN', totalRencana, summaryStyle(COLORS.blueSoft, COLORS.blue), MONEY_FORMAT)
  addSummaryCard(sheet, 4, 5, 9, 'TOTAL REALISASI ANGGARAN', totalRealisasi, summaryStyle(COLORS.greenSoft, COLORS.green), MONEY_FORMAT)
  addSummaryCard(sheet, 4, 10, 14, 'TOTAL SISA ANGGARAN', totalSisa, summaryStyle(COLORS.amberSoft, totalSisa < 0 ? COLORS.red : COLORS.amber), MONEY_FORMAT)

  const headerLabels = [
    'No', 'Unit', 'No Surat', 'No PRK', 'Cost Code', 'Jadwal Kalibrasi',
    'Rencana Anggaran', 'Realisasi Anggaran', 'Sisa Anggaran', 'Progress Anggaran',
    'Rencana Surkes', 'Realisasi Surkes', 'Belum Surkes', 'Progress Surkes', 'Non-Surkes',
  ]
  const tableHeaderStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 9, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  }
  const locationStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navySoft } },
    font: { name: 'Aptos Display', size: 13, bold: true, color: { rgb: COLORS.navy } },
    alignment: { horizontal: 'left', vertical: 'center' },
    border: thinBorder,
  }
  const dataStyle = {
    font: { name: 'Aptos', size: 9, color: { rgb: COLORS.slate } },
    alignment: { vertical: 'top', wrapText: true },
    border: thinBorder,
  }
  const numberStyle = {
    ...dataStyle,
    alignment: { horizontal: 'right', vertical: 'top' },
  }

  let row = 7
  let firstTable: { headerRow: number; firstDataRow: number; lastDataRow: number } | null = null

  options.sections.forEach((section) => {
    const surkesRencana = section.data.reduce((sum, unit) => sum + number(unit.alat_monitoring?.surkes?.rencana), 0)
    const surkesRealisasi = section.data.reduce((sum, unit) => sum + number(unit.alat_monitoring?.surkes?.realisasi), 0)
    const surkesBelum = section.data.reduce((sum, unit) => sum + number(unit.alat_monitoring?.surkes?.belum), 0)
    const nonSurkes = section.data.reduce((sum, unit) => sum + number(unit.alat_monitoring?.non_surkes?.jumlah), 0)
    const surkesProgress = surkesRencana > 0 ? surkesRealisasi / surkesRencana : 0
    const lokasiRencana = section.data.reduce((sum, unit) => sum + number(unit.summary?.total_rencana), 0)
    const lokasiRealisasi = section.data.reduce((sum, unit) => sum + number(unit.summary?.total_realisasi), 0)
    const lokasiSisa = section.data.reduce((sum, unit) => sum + number(unit.summary?.total_sisa), 0)
    const avgAnggaran = section.data.length
      ? section.data.reduce((sum, unit) => sum + number(unit.summary?.progress), 0) / section.data.length / 100
      : 0

    merge(sheet, row, 0, 14)
    styleRange(sheet, row, 0, row, 14, locationStyle)
    setCell(sheet, row, 0, `LOKASI ${section.lokasi.toLocaleUpperCase('id-ID')}  •  ${section.data.length} UNIT`, locationStyle)
    row += 2

    addSummaryCard(sheet, row, 0, 2, 'SURKES - RENCANA', surkesRencana, summaryStyle(COLORS.blueSoft, COLORS.blue), INTEGER_FORMAT)
    addSummaryCard(sheet, row, 3, 5, 'SURKES - REALISASI', surkesRealisasi, summaryStyle(COLORS.greenSoft, COLORS.green), INTEGER_FORMAT)
    addSummaryCard(sheet, row, 6, 8, 'SURKES - BELUM', surkesBelum, summaryStyle(COLORS.amberSoft, COLORS.amber), INTEGER_FORMAT)
    addSummaryCard(sheet, row, 9, 11, 'PROGRESS SURKES', surkesProgress, summaryStyle(COLORS.greenSoft, COLORS.green), PERCENT_FORMAT)
    addSummaryCard(sheet, row, 12, 14, 'NON-SURKES (TERPISAH)', nonSurkes, summaryStyle(COLORS.violetSoft, COLORS.violet), INTEGER_FORMAT)
    row += 3

    addSummaryCard(sheet, row, 0, 3, 'RENCANA ANGGARAN', lokasiRencana, summaryStyle(COLORS.blueSoft, COLORS.blue), MONEY_FORMAT)
    addSummaryCard(sheet, row, 4, 7, 'REALISASI ANGGARAN', lokasiRealisasi, summaryStyle(COLORS.greenSoft, COLORS.green), MONEY_FORMAT)
    addSummaryCard(sheet, row, 8, 11, 'SISA ANGGARAN', lokasiSisa, summaryStyle(COLORS.amberSoft, lokasiSisa < 0 ? COLORS.red : COLORS.amber), MONEY_FORMAT)
    addSummaryCard(sheet, row, 12, 14, 'RATA-RATA PROGRESS ANGGARAN', avgAnggaran, summaryStyle(COLORS.blueSoft, COLORS.blue), PERCENT_FORMAT)
    row += 3

    const headerRow = row
    headerLabels.forEach((label, col) => setCell(sheet, row, col, label, tableHeaderStyle))
    row += 1
    const firstDataRow = row

    section.data.forEach((unit, index) => {
      const fill = index % 2 === 1 ? COLORS.rowAlt : COLORS.white
      const rowStyle = { ...dataStyle, fill: { patternType: 'solid', fgColor: { rgb: fill } } }
      const rowNumberStyle = { ...numberStyle, fill: { patternType: 'solid', fgColor: { rgb: fill } } }
      const values = [
        index + 1,
        unit.namaperusahaan || '-',
        unit.no_surat || '-',
        unit.no_prk || '-',
        unit.cost_code || '-',
        unit.jadwal_kalibrasi || '-',
        number(unit.summary?.total_rencana),
        number(unit.summary?.total_realisasi),
        number(unit.summary?.total_sisa),
        number(unit.summary?.progress) / 100,
        number(unit.alat_monitoring?.surkes?.rencana),
        number(unit.alat_monitoring?.surkes?.realisasi),
        number(unit.alat_monitoring?.surkes?.belum),
        number(unit.alat_monitoring?.surkes?.progress) / 100,
        number(unit.alat_monitoring?.non_surkes?.jumlah),
      ]

      values.forEach((value, col) => {
        let format: string | undefined
        if (col >= 6 && col <= 8) format = MONEY_FORMAT
        if (col === 9 || col === 13) format = PERCENT_FORMAT
        if (col >= 10 && col <= 12 || col === 14) format = INTEGER_FORMAT
        setCell(sheet, row, col, value, col >= 6 ? rowNumberStyle : rowStyle, format)
      })
      row += 1
    })

    const lastDataRow = Math.max(row - 1, firstDataRow)
    const totalStyle = {
      fill: { patternType: 'solid', fgColor: { rgb: COLORS.navySoft } },
      font: { name: 'Aptos', size: 9, bold: true, color: { rgb: COLORS.navy } },
      alignment: { horizontal: 'right', vertical: 'center' },
      border: thinBorder,
    }
    merge(sheet, row, 0, 5)
    styleRange(sheet, row, 0, row, 14, totalStyle)
    setCell(sheet, row, 0, `TOTAL ${section.lokasi.toLocaleUpperCase('id-ID')}`, totalStyle)
    const totals = [lokasiRencana, lokasiRealisasi, lokasiSisa, avgAnggaran, surkesRencana, surkesRealisasi, surkesBelum, surkesProgress, nonSurkes]
    totals.forEach((value, index) => {
      const col = index + 6
      const format = col <= 8 ? MONEY_FORMAT : (col === 9 || col === 13 ? PERCENT_FORMAT : INTEGER_FORMAT)
      setCell(sheet, row, col, value, totalStyle, format)
    })

    if (!firstTable && section.data.length) {
      firstTable = { headerRow, firstDataRow, lastDataRow }
    }
    row += 3
  })

  merge(sheet, row, 0, 14)
  setCell(
    sheet,
    row,
    0,
    'Catatan: Alat Non-Surkes ditampilkan terpisah dan tidak dijumlahkan ke rencana, realisasi, maupun progress alat Surkes.',
    {
      font: { name: 'Aptos', size: 9, italic: true, color: { rgb: COLORS.muted } },
      alignment: { horizontal: 'left', vertical: 'center', wrapText: true },
    },
  )

  sheet['!cols'] = [
    { wch: 6 }, { wch: 34 }, { wch: 24 }, { wch: 18 }, { wch: 28 }, { wch: 24 },
    { wch: 18 }, { wch: 18 }, { wch: 18 }, { wch: 17 }, { wch: 15 }, { wch: 16 },
    { wch: 14 }, { wch: 15 }, { wch: 14 },
  ]
  sheet['!ref'] = XLSX.utils.encode_range({ r: 0, c: 0 }, { r: row, c: 14 })
  sheet['!rows'] = [{ hpt: 34 }, { hpt: 22 }, { hpt: 20 }]
  ;(sheet as any)['!freeze'] = { xSplit: 2, ySplit: firstTable ? firstTable.headerRow + 1 : 7 }
  ;(sheet as any)['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0, paperSize: 9 }
  ;(sheet as any)['!margins'] = { left: 0.2, right: 0.2, top: 0.45, bottom: 0.45, header: 0.2, footer: 0.2 }

  if (options.sections.length === 1 && firstTable) {
    sheet['!autofilter'] = {
      ref: XLSX.utils.encode_range({ r: firstTable.headerRow, c: 0 }, { r: firstTable.lastDataRow, c: 14 }),
    }
  }

  XLSX.utils.book_append_sheet(workbook, sheet, SHEET_NAME)
  workbook.Props = {
    Title: `RBK Monitoring ${options.fileLabel} ${options.year}`,
    Subject: 'Monitoring anggaran serta alat Surkes dan Non-Surkes per unit',
    Author: 'U-LAB',
    CreatedDate: generatedAt,
  }

  const source = XLSX.write(workbook, {
    type: 'array',
    bookType: 'xlsx',
    cellDates: true,
    compression: true,
  })

  if (!firstTable) return new Uint8Array(source)

  const chartLastDataRow = Math.min(firstTable.lastDataRow, firstTable.firstDataRow + 14)
  const chartUnits = options.sections[0].data.slice(0, chartLastDataRow - firstTable.firstDataRow + 1)
  const excelFirstRow = firstTable.firstDataRow + 1
  const excelLastRow = chartLastDataRow + 1

  return addNativeExcelCharts(source, SHEET_NAME, [{
    title: `Rencana dan Realisasi Alat Surkes - ${options.sections[0].lokasi}`,
    categoryRange: `$B$${excelFirstRow}:$B$${excelLastRow}`,
    categories: chartUnits.map(unit => unit.namaperusahaan || '-'),
    series: [
      {
        name: 'Rencana Surkes',
        titleCell: `$K$${firstTable.headerRow + 1}`,
        valueRange: `$K$${excelFirstRow}:$K$${excelLastRow}`,
        values: chartUnits.map(unit => number(unit.alat_monitoring?.surkes?.rencana)),
        color: '#2563EB',
      },
      {
        name: 'Realisasi Surkes',
        titleCell: `$L$${firstTable.headerRow + 1}`,
        valueRange: `$L$${excelFirstRow}:$L$${excelLastRow}`,
        values: chartUnits.map(unit => number(unit.alat_monitoring?.surkes?.realisasi)),
        color: '#059669',
      },
    ],
    from: { col: 0, row: row + 2 },
    to: { col: 14, row: row + 21 },
    horizontal: true,
    showValues: false,
    valueFormat: '#,##0',
  }])
}

export function exportRbkMonitoringExcel(options: RbkMonitoringExcelOptions) {
  const bytes = buildRbkMonitoringExcel(options)
  const fileName = [
    'RBK_Monitoring',
    safeFilePart(options.fileLabel),
    safeFilePart(options.year),
  ].join('_') + '.xlsx'

  downloadExcelFile(bytes, fileName)
  return fileName
}
