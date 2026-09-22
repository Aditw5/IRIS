import * as XLSX from 'xlsx-js-style'
import { addNativeExcelCharts, downloadExcelFile } from './excelNativeCharts'

export type RbkExcelSummary = {
  rencana?: number | string
  realisasi?: number | string
  sisa?: number | string
  progress?: number | string
}

export type RbkExcelCategory = {
  key: string
  label: string
  summary: RbkExcelSummary
}

export type RbkRealisasiExcelOptions = {
  unitName: string
  year: string
  location: string
  generatedAt?: Date
  total: RbkExcelSummary
  categories: RbkExcelCategory[]
}

const SHEET_NAME = 'Realisasi RBK'
const MONEY_FORMAT = '"Rp" #,##0;[Red]-"Rp" #,##0'

const COLORS = {
  navy: '17365D',
  blue: '2563EB',
  blueSoft: 'DBEAFE',
  green: '059669',
  greenSoft: 'D1FAE5',
  amber: 'D97706',
  amberSoft: 'FEF3C7',
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

function toNumber(value: unknown) {
  const parsed = Number(value ?? 0)
  return Number.isFinite(parsed) ? parsed : 0
}

function getProgress(summary: RbkExcelSummary) {
  const explicit = toNumber(summary.progress)
  if (explicit) return explicit / 100
  const plan = toNumber(summary.rencana)
  return plan > 0 ? toNumber(summary.realisasi) / plan : 0
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
  const existing = sheet[address] as any
  const cell = existing || {
    t: typeof value === 'number' ? 'n' : 's',
    v: value ?? '',
  }
  cell.v = value ?? ''
  if (style) cell.s = style
  if (format) cell.z = format
  sheet[address] = cell
  return cell
}

function setFormula(
  sheet: XLSX.WorkSheet,
  row: number,
  col: number,
  formula: string,
  cachedValue: number,
  style: Record<string, unknown>,
  format: string,
) {
  const cell = setCell(sheet, row, col, cachedValue, style, format) as any
  cell.t = 'n'
  cell.f = formula
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

function safeFilePart(value: string) {
  return String(value || 'RBK')
    .trim()
    .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '_')
    .replace(/\s+/g, '_')
    .replace(/_+/g, '_')
    .slice(0, 80)
}

function cardStyle(fill: string, fontColor: string) {
  return {
    fill: { patternType: 'solid', fgColor: { rgb: fill } },
    font: { name: 'Aptos', size: 11, bold: true, color: { rgb: fontColor } },
    alignment: { horizontal: 'center', vertical: 'center' },
    border: thinBorder,
  }
}

export function buildRbkRealisasiExcel(options: RbkRealisasiExcelOptions) {
  const generatedAt = options.generatedAt || new Date()
  const categories = options.categories.slice(0, 3)
  const data: any[][] = []

  data[0] = ['REALISASI RBK HYBRID']
  data[1] = [`${options.unitName} | ${options.year} | ${options.location}`]
  data[2] = [`Dibuat: ${generatedAt.toLocaleString('id-ID')}`]
  data[3] = ['TOTAL RENCANA', '', 'REALISASI HYBRID', '', '', 'SISA', '', '', 'PROGRESS']
  data[4] = [
    toNumber(options.total.rencana), '',
    toNumber(options.total.realisasi), '', '',
    toNumber(options.total.sisa), '', '',
    getProgress(options.total),
  ]
  data[7] = ['Kategori', 'Rencana', 'Realisasi Hybrid', 'Sisa', 'Progress']

  categories.forEach((category, index) => {
    data[8 + index] = [
      category.label,
      toNumber(category.summary.rencana),
      toNumber(category.summary.realisasi),
      toNumber(category.summary.sisa),
      getProgress(category.summary),
    ]
  })

  const categoryEndRow = 8 + Math.max(categories.length, 1)
  data[categoryEndRow] = [
    'TOTAL',
    toNumber(options.total.rencana),
    toNumber(options.total.realisasi),
    toNumber(options.total.sisa),
    getProgress(options.total),
  ]

  const noteRow = categoryEndRow + 2
  data[noteRow] = [
    'Catatan: Realisasi Hybrid merupakan gabungan nilai PBJ yang telah direkonsiliasi dengan PPN dan input manual RBK.',
  ]

  const sheet = XLSX.utils.aoa_to_sheet(data, { cellDates: true })
  const workbook = XLSX.utils.book_new()

  sheet['!merges'] = [
    { s: { r: 0, c: 0 }, e: { r: 0, c: 10 } },
    { s: { r: 1, c: 0 }, e: { r: 1, c: 10 } },
    { s: { r: 2, c: 0 }, e: { r: 2, c: 10 } },
    { s: { r: 3, c: 0 }, e: { r: 3, c: 1 } },
    { s: { r: 4, c: 0 }, e: { r: 4, c: 1 } },
    { s: { r: 3, c: 2 }, e: { r: 3, c: 4 } },
    { s: { r: 4, c: 2 }, e: { r: 4, c: 4 } },
    { s: { r: 3, c: 5 }, e: { r: 3, c: 7 } },
    { s: { r: 4, c: 5 }, e: { r: 4, c: 7 } },
    { s: { r: 3, c: 8 }, e: { r: 3, c: 10 } },
    { s: { r: 4, c: 8 }, e: { r: 4, c: 10 } },
    { s: { r: noteRow, c: 0 }, e: { r: noteRow, c: 10 } },
  ]

  const titleStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.blue } },
    font: { name: 'Aptos Display', size: 18, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'left', vertical: 'center' },
  }
  const subtitleStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.blue } },
    font: { name: 'Aptos', size: 11, color: { rgb: 'DBEAFE' } },
    alignment: { horizontal: 'left', vertical: 'center' },
  }
  const generatedStyle = {
    font: { name: 'Aptos', size: 9, italic: true, color: { rgb: COLORS.muted } },
    alignment: { horizontal: 'left', vertical: 'center' },
  }
  const tableHeaderStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.navy } },
    font: { name: 'Aptos', size: 10, bold: true, color: { rgb: COLORS.white } },
    alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
    border: thinBorder,
  }
  const normalStyle = {
    font: { name: 'Aptos', size: 10, color: { rgb: COLORS.slate } },
    alignment: { vertical: 'top', wrapText: true },
    border: thinBorder,
  }
  const alternateStyle = {
    ...normalStyle,
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.rowAlt } },
  }
  const totalStyle = {
    fill: { patternType: 'solid', fgColor: { rgb: COLORS.blueSoft } },
    font: { name: 'Aptos', size: 10, bold: true, color: { rgb: COLORS.navy } },
    alignment: { vertical: 'center' },
    border: thinBorder,
  }

  styleRange(sheet, 0, 0, 0, 10, titleStyle)
  styleRange(sheet, 1, 0, 1, 10, subtitleStyle)
  styleRange(sheet, 2, 0, 2, 10, generatedStyle)
  styleRange(sheet, 3, 0, 4, 1, cardStyle(COLORS.blueSoft, COLORS.navy))
  styleRange(sheet, 3, 2, 4, 4, cardStyle(COLORS.greenSoft, COLORS.green))
  styleRange(
    sheet,
    3,
    5,
    4,
    7,
    cardStyle(toNumber(options.total.sisa) < 0 ? 'FEE2E2' : COLORS.amberSoft, toNumber(options.total.sisa) < 0 ? COLORS.red : COLORS.amber),
  )
  styleRange(sheet, 3, 8, 4, 10, cardStyle(COLORS.blueSoft, COLORS.blue))

  setCell(sheet, 4, 0, toNumber(options.total.rencana), (sheet.A5 as any).s, MONEY_FORMAT)
  setCell(sheet, 4, 2, toNumber(options.total.realisasi), (sheet.C5 as any).s, MONEY_FORMAT)
  setCell(sheet, 4, 5, toNumber(options.total.sisa), (sheet.F5 as any).s, MONEY_FORMAT)
  setCell(sheet, 4, 8, getProgress(options.total), (sheet.I5 as any).s, '0%')

  styleRange(sheet, 7, 0, 7, 4, tableHeaderStyle)
  categories.forEach((category, index) => {
    const row = 8 + index
    styleRange(sheet, row, 0, row, 4, index % 2 ? alternateStyle : normalStyle)
    setCell(sheet, row, 1, toNumber(category.summary.rencana), (sheet[XLSX.utils.encode_cell({ r: row, c: 1 })] as any).s, MONEY_FORMAT)
    setCell(sheet, row, 2, toNumber(category.summary.realisasi), (sheet[XLSX.utils.encode_cell({ r: row, c: 2 })] as any).s, MONEY_FORMAT)
    setCell(sheet, row, 3, toNumber(category.summary.sisa), (sheet[XLSX.utils.encode_cell({ r: row, c: 3 })] as any).s, MONEY_FORMAT)
    setFormula(
      sheet,
      row,
      4,
      `IF(B${row + 1}=0,0,C${row + 1}/B${row + 1})`,
      getProgress(category.summary),
      (sheet[XLSX.utils.encode_cell({ r: row, c: 4 })] as any).s,
      '0%',
    )
  })

  styleRange(sheet, categoryEndRow, 0, categoryEndRow, 4, totalStyle)
  const categoryFirstExcelRow = 9
  const categoryLastExcelRow = 8 + categories.length
  setFormula(sheet, categoryEndRow, 1, `SUM(B${categoryFirstExcelRow}:B${categoryLastExcelRow})`, toNumber(options.total.rencana), totalStyle, MONEY_FORMAT)
  setFormula(sheet, categoryEndRow, 2, `SUM(C${categoryFirstExcelRow}:C${categoryLastExcelRow})`, toNumber(options.total.realisasi), totalStyle, MONEY_FORMAT)
  setFormula(sheet, categoryEndRow, 3, `B${categoryEndRow + 1}-C${categoryEndRow + 1}`, toNumber(options.total.sisa), totalStyle, MONEY_FORMAT)
  setFormula(sheet, categoryEndRow, 4, `IF(B${categoryEndRow + 1}=0,0,C${categoryEndRow + 1}/B${categoryEndRow + 1})`, getProgress(options.total), totalStyle, '0%')

  styleRange(sheet, noteRow, 0, noteRow, 10, generatedStyle)

  sheet['!cols'] = [
    { wch: 6 }, { wch: 15 }, { wch: 11 }, { wch: 19 }, { wch: 38 }, { wch: 42 },
    { wch: 24 }, { wch: 24 }, { wch: 17 }, { wch: 20 }, { wch: 19 }, { wch: 3 },
    ...Array.from({ length: 10 }, () => ({ wch: 13 })),
  ]
  sheet['!rows'] = [
    { hpt: 30 }, { hpt: 22 }, { hpt: 18 }, { hpt: 22 }, { hpt: 30 },
    { hpt: 8 }, { hpt: 8 }, { hpt: 26 },
  ]
  ;(sheet as any)['!pageSetup'] = { orientation: 'landscape', fitToWidth: 1, fitToHeight: 0 }
  ;(sheet as any)['!margins'] = { left: 0.25, right: 0.25, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 }

  XLSX.utils.book_append_sheet(workbook, sheet, SHEET_NAME)
  workbook.Props = {
    Title: `Realisasi RBK Hybrid - ${options.unitName}`,
    Subject: `RBK ${options.year} ${options.location}`,
    Author: 'U-LAB',
    CreatedDate: generatedAt,
  }

  const source = XLSX.write(workbook, {
    type: 'array',
    bookType: 'xlsx',
    cellDates: true,
    compression: true,
  })

  const chartCategoryLastRow = 8 + categories.length
  return addNativeExcelCharts(source, SHEET_NAME, [{
    title: 'Rencana, Realisasi, dan Sisa per Kategori',
    categoryRange: `$A$9:$A$${chartCategoryLastRow}`,
    categories: categories.map(category => category.label),
    series: [
      {
        name: 'Rencana',
        titleCell: '$B$8',
        valueRange: `$B$9:$B$${chartCategoryLastRow}`,
        values: categories.map(category => toNumber(category.summary.rencana)),
        color: '#94A3B8',
      },
      {
        name: 'Realisasi Hybrid',
        titleCell: '$C$8',
        valueRange: `$C$9:$C$${chartCategoryLastRow}`,
        values: categories.map(category => toNumber(category.summary.realisasi)),
        color: '#10B981',
      },
      {
        name: 'Sisa',
        titleCell: '$D$8',
        valueRange: `$D$9:$D$${chartCategoryLastRow}`,
        values: categories.map(category => toNumber(category.summary.sisa)),
        color: '#F59E0B',
      },
    ],
    from: { col: 12, row: 1 },
    to: { col: 21, row: 20 },
    showValues: false,
    valueFormat: '"Rp" #,##0',
  }])
}

export function exportRbkRealisasiExcel(options: RbkRealisasiExcelOptions) {
  const bytes = buildRbkRealisasiExcel(options)
  const fileName = [
    'Realisasi_RBK',
    safeFilePart(options.unitName),
    safeFilePart(options.year),
    safeFilePart(options.location),
  ].join('_') + '.xlsx'

  downloadExcelFile(bytes, fileName)
  return fileName
}
